<?php

namespace App\Filament\Widgets;

use App\Services\WorkloadService;
use Filament\Widgets\ChartWidget;

class WorkloadChartWidget extends ChartWidget
{
    protected static ?string $heading = 'Perbandingan Beban Kerja (W) per Karyawan';

    protected static ?int $sort = 3;

    protected int | string | array $columnSpan = ['md' => 1];

    protected function getData(): array
    {
        $employees = app(WorkloadService::class)->forUser(auth()->user())['employees'];

        return [
            'datasets' => [
                [
                    'label' => 'Skor Beban (W)',
                    'data'  => $employees->pluck('score')->all(),
                    'backgroundColor' => $employees->map(fn ($e) => match ($e['color']) {
                        'danger'  => 'rgba(239, 68, 68, 0.8)',
                        'warning' => 'rgba(245, 158, 11, 0.8)',
                        default   => 'rgba(16, 185, 129, 0.8)',
                    })->all(),
                    'borderRadius' => 8,
                ],
            ],
            'labels' => $employees->pluck('name')->all(),
        ];
    }

    protected function getType(): string
    {
        return 'bar';
    }
}