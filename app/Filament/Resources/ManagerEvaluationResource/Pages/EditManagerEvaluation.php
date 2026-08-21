<?php

namespace App\Filament\Resources\ManagerEvaluationResource\Pages;

use App\Filament\Resources\ManagerEvaluationResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditManagerEvaluation extends EditRecord
{
    protected static string $resource = ManagerEvaluationResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
