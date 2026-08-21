<?php

namespace App\Filament\Resources\ReceiverEvaluationResource\Pages;

use App\Filament\Resources\ReceiverEvaluationResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListReceiverEvaluations extends ListRecords
{
    protected static string $resource = ReceiverEvaluationResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
