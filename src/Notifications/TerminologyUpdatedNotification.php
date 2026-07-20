<?php

namespace Cloudcake\Terms\Notifications;

use Filament\Notifications\Notification;

class TerminologyUpdatedNotification extends Notification
{
    public static function make(?string $id = null): static
    {
        return parent::make($id)
                     ->success()
                     ->icon('tabler-language')
                     ->title('Terminology Updated')
                     ->body('Your terminologies have been updated successfully.');
    }
}