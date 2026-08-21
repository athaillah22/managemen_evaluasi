<?php

namespace App\Filament\Resources\ManagerEvaluationResource\Pages;

use App\Filament\Resources\ManagerEvaluationResource;
use Filament\Resources\Pages\CreateRecord;

class CreateManagerEvaluation extends CreateRecord
{
    protected static string $resource = ManagerEvaluationResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['manager_id'] = auth()->id();

        return $data;
    }
}