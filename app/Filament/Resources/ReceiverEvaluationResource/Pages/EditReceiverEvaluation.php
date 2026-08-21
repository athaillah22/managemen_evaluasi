<?php

namespace App\Filament\Resources\ReceiverEvaluationResource\Pages;

use App\Filament\Resources\ReceiverEvaluationResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditReceiverEvaluation extends EditRecord
{
    protected static string $resource = ReceiverEvaluationResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
