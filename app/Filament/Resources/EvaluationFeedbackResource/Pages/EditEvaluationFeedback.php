<?php

namespace App\Filament\Resources\EvaluationFeedbackResource\Pages;

use App\Filament\Resources\EvaluationFeedbackResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditEvaluationFeedback extends EditRecord
{
    protected static string $resource = EvaluationFeedbackResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
