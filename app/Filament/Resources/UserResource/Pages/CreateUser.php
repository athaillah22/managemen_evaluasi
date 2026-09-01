<?php

namespace App\Filament\Resources\UserResource\Pages;

use App\Filament\Resources\UserResource;
use App\Models\User;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;

class CreateUser extends CreateRecord
{
    protected static string $resource = UserResource::class;

    public static function mutateFormDataBeforeSave(array $data): array
    {
        $currentId = request()->route('record')?->id ?? null;

        if (! empty($data['manager_id'])) {
            abort_unless(
                $data['manager_id'] != $currentId,
                422, 'Seorang user tidak boleh menjadi atasan bagi dirinya sendiri.'
            );

            $manager = User::find($data['manager_id']);
            abort_unless(
                $manager !== null && in_array($manager->role, ['manager', 'hr'], true),
                422, 'Atasan harus ber-role Manager atau HR.'
            );
        }

        return $data;
    }

}
