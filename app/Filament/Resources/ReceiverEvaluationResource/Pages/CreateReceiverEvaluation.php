<?php

namespace App\Filament\Resources\ReceiverEvaluationResource\Pages;

use App\Filament\Resources\ReceiverEvaluationResource;
use Filament\Resources\Pages\CreateRecord;

class CreateReceiverEvaluation extends CreateRecord
{
    protected static string $resource = ReceiverEvaluationResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['receiver_id'] = auth()->id();

        return $data;
    }
}