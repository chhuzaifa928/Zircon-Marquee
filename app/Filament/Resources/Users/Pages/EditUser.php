<?php

namespace App\Filament\Resources\Users\Pages;

use App\Filament\Resources\Users\UserResource;
use Filament\Actions\DeleteAction;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;

class EditUser extends EditRecord
{
    protected static string $resource = UserResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make()
                ->visible(fn (): bool => UserResource::canDelete($this->record)),
        ];
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
        // A user may not deactivate their own account.
        if (auth()->user()?->is($this->record) && array_key_exists('is_active', $data) && ! $data['is_active']) {
            $data['is_active'] = true;

            Notification::make()
                ->warning()
                ->title('You cannot deactivate your own account')
                ->send();
        }

        return $data;
    }
}
