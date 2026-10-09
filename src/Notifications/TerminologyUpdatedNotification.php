<?php

namespace Cloudcake\Terms\Notifications;

use Cloudcake\Components\Enums\Tabler;
use Filament\Notifications\Notification;

class TerminologyUpdatedNotification extends Notification
{
    public static function make(?string $id = null): static
    {
        return parent::make($id)
                     ->success()
                     ->icon(Tabler::Language)
                     ->title('Terminology Updated')
                     ->body('Your terminologies have been updated successfully.');
    }
}