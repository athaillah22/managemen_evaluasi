<?php

namespace App\Filament\Widgets;

use App\Services\WorkloadService;
use Filament\Widgets\Widget;

class WorkloadBurnoutWidget extends Widget
{
    protected static string $view = 'filament.widgets.workload-burnout-widget';

    protected int | string | array $columnSpan = 'full';

    protected static ?int $sort = 2;

    protected function getViewData(): array
    {
        // Bab 6.3 & Bab 7: data + cakupan sesuai role yang login
        return app(WorkloadService::class)->forUser(auth()->user());
    }
}