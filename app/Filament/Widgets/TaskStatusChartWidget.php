<?php

namespace App\Filament\Widgets;

use App\Models\Task;
use App\Models\User;
use Filament\Widgets\ChartWidget;
use Illuminate\Database\Eloquent\Builder;

class TaskStatusChartWidget extends ChartWidget
{
    protected static ?string $heading = 'Distribusi Status Tugas';

    protected static ?int $sort = 4;

    protected int | string | array $columnSpan = ['md' => 1];

    protected function getData(): array
    {
        $user = auth()->user();

        $q = match ($user->role) {
            'hr' => Task::query(),
            'manager' => Task::query()->where(fn ($q) => $q
                ->where('assigned_to_user_id', $user->id)
                ->orWhere('assigned_by', $user->id)
                ->orWhereHas('assignedTo', fn ($s) => $s->where('manager_id', $user->id))),
            default => Task::query()->where(fn ($q) => $q
                ->where('assigned_to_user_id', $user->id)
                ->orWhere('assigned_by', $user->id)),
        };

        $counts = $q->selectRaw('status, COUNT(*) as total')->groupBy('status')->pluck('total', 'status');
        $keys   = ['pending', 'in_progress', 'waiting_review', 'completed', 'cancelled'];

        return [
            'datasets' => [
                [
                    'label' => 'Tugas',
                    'data'  => collect($keys)->map(fn ($k) => $counts[$k] ?? 0)->all(),
                    'backgroundColor' => [
                        'rgba(156, 163, 175, 0.8)', 'rgba(96, 165, 250, 0.8)',
                        'rgba(245, 158, 11, 0.8)', 'rgba(16, 185, 129, 0.8)',
                        'rgba(239, 68, 68, 0.8)',
                    ],
                ],
            ],
            'labels' => ['Menunggu', 'Dikerjakan', 'Review', 'Selesai', 'Dibatalkan'],
        ];
    }

    protected function getType(): string
    {
        return 'doughnut';
    }
}