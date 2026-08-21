<?php

namespace App\Filament\Resources\ManagerEvaluationResource\Pages;

use App\Filament\Resources\ManagerEvaluationResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListManagerEvaluations extends ListRecords
{
    protected static string $resource = ManagerEvaluationResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
