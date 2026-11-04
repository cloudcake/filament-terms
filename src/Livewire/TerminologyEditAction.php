<?php

namespace Cloudcake\Terms\Livewire;

use Cloudcake\Icons\SelectIcon;
use Cloudcake\Terms\Contracts\Renamable;
use Cloudcake\Terms\Contracts\RenamableColumns;
use Cloudcake\Terms\Models\PanelTerminology;
use Cloudcake\Terms\Notifications\TerminologyUpdatedNotification;
use Cloudcake\Terms\Terminology;
use Filament\Actions\Action;
use Filament\Actions\Concerns\InteractsWithActions;
use Filament\Actions\Contracts\HasActions;
use Filament\Actions\Exceptions\ActionNotResolvableException;
use Filament\Forms\Components\Field;
use Filament\Forms\Components\RichEditor;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Component;
use Filament\Schemas\Components\Flex;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Concerns\InteractsWithSchemas;
use Filament\Schemas\Contracts\HasSchemas;
use Filament\Support\Enums\Width;
use Illuminate\Support\HtmlString;
use Livewire\Component as LivewireComponent;

class TerminologyEditAction extends LivewireComponent implements HasActions, HasSchemas
{
    use InteractsWithActions;
    use InteractsWithSchemas;

    public ?string $resource = null;
    public ?string $label = null;
    public bool $descriptionsEnabled = false;

    /**
     * @throws ActionNotResolvableException
     */
    public function terminologyAction(): Action
    {
        return Action::make('terminology')
            ->label($this->label)
            ->outlined()
            ->modal()
            ->keyBindings(['command+s', 'ctrl+s'])
            ->modalWidth(Width::Medium)
            ->slideOver()
            ->schema($this->getResourceSchema($this->resource))
            ->modalHeading("{$this->resource::getNavigationLabel()} Terminology")
            ->modalDescription(new HtmlString(Terminology::getTerm($this->resource . '.desc')))
            ->modalIcon($this->resource::getNavigationIcon())
            ->action(function (array $data, $livewire) {
                $record = PanelTerminology::current();
                $record->terms = array_merge($record->terms ?? [], $data);
                $record->save();

                TerminologyUpdatedNotification::make()->send();

                $livewire->js("window.location.reload()");
            });
    }

    public function mount(string $resource, string $label = 'Edit', bool $descriptionEnabled = false): void
    {
        $this->resource = $resource;
        $this->label = $label;
        $this->descriptionsEnabled = $descriptionEnabled;
    }

    /**
     */
    public function render(): string
    {
        $label = $this->label ?? 'Edit';
        $tooltip = "Edit {$this->resource::getNavigationLabel()} terminology";

        return <<<HTML
            <div>
                <x-filament::button
                            class="cursor-pointer"
                            :icon="\Cloudcake\Icons\Enums\Tabler::Language"
                            color="gray"
                            tooltip="$tooltip"
                            wire:click="mountAction('terminology')">
                            $label
                </x-filament::button>
                <x-filament-actions::modals/>
            </div>
        HTML;
    }


    /**
     * @param class-string $class
     * @return array<Component>
     */
    private function getResourceSchema(string $class): array
    {
        $components = [];

        if (has_interface(Renamable::class, $class)) {
            $components[] = $this->resolveFieldWrapper($class);

            if (method_exists($class, 'getModel')) {
                $modelClass = $class::getModel();

                if (has_interface(RenamableColumns::class, $modelClass)) {

                    /** @var RenamableColumns $model */
                    $model = new ($modelClass);

                    $components[] = Terminology::resolveModelWrapperHint($class);

                    foreach ($model->renamable() as $column) {
                        $components[] = Terminology::resolveModelColumnField(
                            Terminology::getModelDisplayName($column),
                            "$modelClass.columns.$column"
                        );
                    }
                }
            }
        }

        return $components;
    }

    /**
     * @param string $baseStatePath
     * @return Component
     */
    private function resolveFieldWrapper(string $baseStatePath): Component
    {
        return Grid::make(1)->schema([

            Flex::make([
                static::resolveIconField("$baseStatePath.icon")
                    ->statePath(Terminology::sanitizeStatePath("$baseStatePath.icon"))
                    ->default(Terminology::getTerm("$baseStatePath.icon")),
            ]),

            Flex::make([
                static::resolveNameField(Terminology::sanitizeStatePath("$baseStatePath.name"))
                    ->statePath(Terminology::sanitizeStatePath("$baseStatePath.name"))
                    ->default(Terminology::getTerm("$baseStatePath.name")),
            ]),

            static::resolveBadgeField(Terminology::sanitizeStatePath("$baseStatePath.badge"))
                ->statePath(Terminology::sanitizeStatePath("$baseStatePath.badge"))
                ->default(Terminology::getTerm("$baseStatePath.badge")),

            static::resolveDescriptionEditor("$baseStatePath.desc")
                ->visible($this->descriptionsEnabled)
                ->statePath(Terminology::sanitizeStatePath("$baseStatePath.desc"))
                ->default(Terminology::getTerm("$baseStatePath.desc")),
        ]);
    }

    /**
     * @param string $statePath
     * @return Field
     */
    private function resolveNameField(string $statePath): Field
    {
        return TextInput::make($statePath)
            ->grow(false)
            ->columnSpanFull()
            ->belowContent('Provide the singular of the name of the resource, pluralization will occur where applicable.')
            ->label('Name');
    }

    /**
     * @param string $statePath
     * @return Field
     */
    private function resolveBadgeField(string $statePath): Field
    {
        return TextInput::make($statePath)
            ->grow(false)
            ->columnSpanFull()
            ->belowContent('Provide a label for the badge.')
            ->label('Badge');
    }

    /**
     * @param string $statePath
     * @return Field
     */
    private function resolveIconField(string $statePath): Field
    {
        return SelectIcon::make($statePath)
            ->grow(false)
            ->placeholder('Icon')
            ->label('Icon');
    }

    /**
     * @param string $statePath
     * @return Field
     */
    private function resolveDescriptionEditor(string $statePath): Field
    {
        return RichEditor::make($statePath)
            ->columnSpanFull()
            ->label('Description')
            ->maxLength($maxLength = 512)
            ->aboveContent("A short description of the resource, this will be displayed in the page header.")
            ->belowContent("Maximum length of $maxLength characters.")
            ->toolbarButtons([
                'bold',
                'italic',
                'strike',
                'underline',
                'redo',
                'undo',
            ]);
    }
}
