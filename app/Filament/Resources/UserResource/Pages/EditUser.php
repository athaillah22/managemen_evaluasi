<?php

namespace App\Filament\Resources\UserResource\Pages;

use App\Filament\Resources\UserResource;
use App\Models\User;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditUser extends EditRecord
{
    protected static string $resource = UserResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }

    /** Validasi backend: cegah assign diri sendiri & pastikan manager valid. */
    public static function mutateFormDataBeforeCreate(array $data): array
    {
        if (! empty($data['manager_id'])) {
            $manager = User::find($data['manager_id']);
            abort_unless(
                $manager !== null && in_array($manager->role, ['manager', 'hr'], true),
                422, 'Atasan harus ber-role Manager atau HR.'
            );
        }

        return $data;
    }
}