<?php

namespace App\Filament\Widgets;

use App\Models\Task;
use App\Models\User;
use App\Services\WorkloadService;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Database\Eloquent\Builder;

class TaskStatsWidget extends BaseWidget
{
    protected static ?int $sort = 1;

    protected function getStats(): array
    {
        $user  = auth()->user();
        $q     = $this->scopedTasks($user);

        $aktif = (clone $q)->whereIn('status', Task::ACTIVE_STATUSES)->count();
        $done  = (clone $q)->where('status', 'completed')->count();
        $telat = (clone $q)->whereIn('status', Task::ACTIVE_STATUSES)
            ->whereNotNull('due_date')->whereDate('due_date', '<', now())->count();
        $overload = collect(app(WorkloadService::class)->forUser($user)['employees'])
            ->filter(fn ($e) => $e['color'] !== 'success')->count();

        return [
            Stat::make('Tugas Aktif', $aktif)
                ->description('To Do / In Progress / Review')
                ->descriptionIcon('heroicon-m-clipboard-document-list')
                ->color('info'),
            Stat::make('Selesai (Done)', $done)
                ->description('Tugas yang sudah diselesaikan')
                ->descriptionIcon('heroicon-m-check-circle')
                ->color('success'),
            Stat::make('Lewat Deadline', $telat)
                ->description($telat > 0 ? 'Perlu perhatian segera!' : 'Semua tepat waktu')
                ->descriptionIcon('heroicon-m-exclamation-triangle')
                ->color($telat > 0 ? 'danger' : 'gray'),
            Stat::make('Karyawan Overload', $overload)
                ->description('Waspada / Berisiko / Critical')
                ->descriptionIcon('heroicon-m-fire')
                ->color($overload > 0 ? 'danger' : 'success'),
        ];
    }

    private function scopedTasks(User $user): Builder
    {
        return match ($user->role) {
            'hr' => Task::query(),
            'manager' => Task::query()->where(fn ($q) => $q
                ->where('assigned_to_user_id', $user->id)
                ->orWhere('assigned_by', $user->id)
                ->orWhereHas('assignedTo', fn ($s) => $s->where('manager_id', $user->id))),
            default => Task::query()->where(fn ($q) => $q
                ->where('assigned_to_user_id', $user->id)
                ->orWhere('assigned_by', $user->id)),
        };
    }
}