<?php

namespace Cloudcake\Terms;

use Cloudcake\Terms\Contracts\Renamable;
use Cloudcake\Terms\Models\PanelTerminology;
use Closure;
use Filament\Contracts\Plugin;
use Filament\Forms\Components\TextInput;
use Filament\Panel;
use Filament\Forms\Components\Field;
use Filament\Infolists\Components\Entry;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Callout;
use Filament\Schemas\Components\Component;
use Filament\Tables\Columns\Column;
use Filament\Tables\Filters\BaseFilter;
use Filament\Tables\Grouping\Group;
use Filament\View\PanelsRenderHook;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Stringable;

class Terminology implements Plugin
{
    private Closure|bool $terminologyEnabled = true;
    private Closure|bool $descriptionsEnabled = false;
    private Closure|bool $pageHeaderActionEnabled = false;
    private string|null $pageHeaderActionLabel = null;
    private Closure|bool $topbarActionEnabled = false;
    private string|null $topbarActionLabel = null;

    /**
     * @return static
     */
    public static function make(): static
    {
        return app(static::class);
    }

    public function getId(): string
    {
        return 'cloudcake-terms';
    }

    public function register(Panel $panel): void
    {
        if (!$panel->evaluate($this->terminologyEnabled))
            return;

        $panel->bootUsing(function () {
            $this->configureComponents();
        });

        $descriptionEnabled = $panel->evaluate($this->descriptionsEnabled);

        $panel->when(
            $descriptionEnabled,
            fn($panel) => $panel->renderHook(PanelsRenderHook::PAGE_HEADER_WIDGETS_BEFORE, function (array $scopes): string {
                $resourceOrPage = '';

                if (!$this->resolveResourceFromScopes($scopes, $resourceOrPage))
                    return '';

                $description = static::getTerm(static::sanitizeStatePath("$resourceOrPage.desc"));

                if (str($description)->stripTags()->length() === 0)
                    return '';

                return Blade::render("<div class='term-description text-gray-500'>$description</div>");
            })
        );

        $panel
            ->when(
                fn($panel) => $panel->evaluate($this->pageHeaderActionEnabled),
                fn($panel) => $panel->renderHook(PanelsRenderHook::PAGE_HEADER_ACTIONS_BEFORE, function (array $scopes) use ($descriptionEnabled): string {
                    $resourceOrPage = '';

                    if (!$this->resolveResourceFromScopes($scopes, $resourceOrPage))
                        return '';

                    $descriptionEnabled = $descriptionEnabled ? 1 : 0;

                    return Blade::render(
                        "@livewire('cloudcake-terms::terminology-edit-action', ['resource' => '$resourceOrPage', 'label' => '$this->pageHeaderActionLabel', 'descriptionEnabled' => $descriptionEnabled])"
                    );
                })
            );

        $panel
            ->when(
                fn($panel) => $panel->evaluate($this->topbarActionEnabled),
                fn($panel) => $panel->renderHook(PanelsRenderHook::USER_MENU_BEFORE, function () use ($panel, $descriptionEnabled): string {

                    $resourceOrPage = request()->route()?->getAction()['controller'] ?? null;

                    if ($resourceOrPage && has_interface(Renamable::class, $resourceOrPage)) {
                        $descriptionEnabled = $descriptionEnabled ? 1 : 0;

                        return Blade::render(
                            "@livewire('cloudcake-terms::terminology-edit-action', ['resource' => '$resourceOrPage', 'label' => '$this->topbarActionLabel', 'descriptionEnabled' => $descriptionEnabled])"
                        );
                    }

                    return '';
                })
            );
    }

    public function boot(Panel $panel): void
    {
    }

    private function resolveResourceFromScopes(array $scopes, string &$resource): bool
    {
        foreach ($scopes as $scope) {
            if (has_interface(Renamable::class, $scope)) {
                $resource = $scope;
                break;
            }
        }

        return !blank($resource);
    }

    public function apply(Closure|bool $condition = true): static
    {
        $this->terminologyEnabled = $condition;

        return $this;
    }

    public function descriptions(Closure|bool $condition = true): static
    {
        $this->descriptionsEnabled = $condition;

        return $this;
    }

    public function pageHeaderAction(Closure|bool $condition = true, string|null $label = null): static
    {
        $this->pageHeaderActionEnabled = $condition;
        $this->pageHeaderActionLabel = $label;

        return $this;
    }

    public function topbarAction(Closure|bool $condition = true, string|null $label = null): static
    {
        $this->topbarActionEnabled = $condition;
        $this->topbarActionLabel = $label;

        return $this;
    }


    public static function getTerm(?string $key = null, mixed $default = null): mixed
    {
        $value = data_get(PanelTerminology::current()->terms ?? [], self::sanitizeStatePath($key), $default);

        if (blank($value))
            return $default;

        return $value;
    }

    public static function getModelDisplayName(string $name): string
    {
        return str($name)
            ->classBasename()
            ->kebab()
            ->replace(['_'], ' ')
            ->ucwords()
            ->when(
                fn($str) => $str->endsWith([' id', '_id', ' I D', '-i-d']),
                fn($str) => $str->replace(['id', ' id', '_id', ' I D', '-i-d'], ['ID', ' ID', ' ID', 'ID', 'ID'])
            )
            ->toString();
    }

    /**
     * @param ?class-string<Model> $model
     * @param ?string $column
     * @return ?string
     */
    public static function getModelColumnName(?string $model, ?string $column): ?string
    {
        if (!$model || !$column)
            return null;

        // Caters for when column names reference relationships
        $column = str($column)->afterLast('.')->toString();

        return static::getModelDisplayName(static::getTerm(static::sanitizeStatePath($model . '.columns.' . $column), $column));
    }

    public static function getCurrentPanelComponents(): array
    {
        $panel = filament()->getCurrentPanel();

        return collect([...$panel->getPages(), ...$panel->getResources(),])
            ->filter(fn($class) => is_a($class, Renamable::class, true))
            ->filter(fn($class) => $class::shouldRegisterNavigation())
            ->values()
            ->all();
    }

    public static function getResourceLang(string $model, string|null $key = null, $default = null): mixed
    {
        $path = static::getResourceStatePath($model, $key);

        return static::getTerm($path, $default);
    }

    public static function getResourceColumnName(Model|string|null $model, string $column): string|null
    {
        if (!$model)
            return null;

        // Caters for when column names reference relationships
        $column = str($column)->afterLast('.')->toString();

        return static::getResourceLang(
            $model,
            'columns.' . $column,
            str($column)
                ->replace('-', ' ')
                ->replace('_', ' ')
                ->apa()
                ->when(fn(Stringable $str) => $str->is(['Id', ' Id']), fn($str) => $str->upper())
                ->toString()
        );
    }

    public static function getResourceStatePath(string $resource, string|null $key = null): string
    {
        $resource = strtolower(class_basename($resource));

        return $resource . ($key ? '.' . $key : '');
    }

    private function configureComponents(): void
    {
        Column::configureUsing(fn(Column $component) => $component
            ->label(fn() => $component->getTable()
                ? Terminology::getModelColumnName($component->getTable()->getModel(), $component->getName())
                : null
            )
        );

        BaseFilter::configureUsing(fn(BaseFilter $component) => $component
            ->label(fn() => $component->getTable()
                ? Terminology::getModelColumnName($component->getTable()->getModel(), $component->getName())
                : null
            )
        );

        Group::configureUsing(fn(Group $component) => $component
            ->label(fn() => $component->getTable()
                ? Terminology::getModelColumnName($component->getTable()->getModel(), $component->getColumn())
                : null
            )
        );

        Field::configureUsing(fn(Field $component) => $component
            ->label(fn($model) => Terminology::getResourceColumnName($model, $component->getName()))
        );

        Entry::configureUsing(fn(Entry $component) => $component
            ->label(fn($model) => Terminology::getResourceColumnName($model, $component->getName()))
        );
    }

    public static function getTableName(): string
    {
        return config('terms.migration.table.terminologies', 'panel_terminologies');
    }

    public static function sanitizeStatePath(string $statePath): string
    {
        return str($statePath)->replace('\\', '-')->toString();
    }

    /**
     * @param string $label
     * @param string $statePath
     * @return Field
     */
    public static function resolveModelColumnField(string $label, string $statePath): Field
    {
        $statePath = Terminology::sanitizeStatePath($statePath);

        return TextInput::make($statePath)
                        ->default(Terminology::getTerm($statePath))
                        ->label($label)
                        ->placeholder($label)
                        ->inlineLabel();
    }

    /**
     * @param class-string<Renamable> $class
     * @return Component
     */
    public static function resolveModelWrapperHint(string $class): Component
    {
        return Callout::make("{$class::getSingular()} Columns")
                      ->icon($class::getNavigationIcon())
                      ->description("{$class::getPlural()} have a table listing which allows the renaming of its columns.");
    }
}
