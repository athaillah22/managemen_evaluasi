<?php

namespace App\Services;

use App\Models\Task;
use App\Models\User;
use Illuminate\Support\Collection;

class WorkloadService
{
    /**
     * Data dashboard sesuai role (Bab 6.3 & Bab 7):
     * HR = global, Manager = tim sendiri, Employee = pribadi (Z-Score tetap terhadap tim).
     */
   public function forUser(User $user): array
{
    if ($user->isHr()) {
        $population = User::query()->where('is_active', true)->get();
        $displayIds = null;
        $scope = 'Cakupan: Global (seluruh karyawan non-HR)';
    } elseif ($user->isManager()) {
        $population = $this->teamOf($user);
        $displayIds = null;
        $scope = 'Cakupan: Tim ' . $user->name;
    } else {
        $population = $this->teamOf($user);
        $displayIds = collect([$user->id]);
        $scope = 'Cakupan: Pribadi (Z-Score terhadap tim Anda)';
    }

    // HR = pemantau, bukan objek pantauan:
    // akun HR tidak tampil di dashboard beban kerja (cakupan mana pun)
    $population = $population->filter(fn (User $u) => ! $u->isHr())->values();

    $result = $this->calculate($population, $displayIds);
    $result['scope'] = $scope;

    return $result;
}

    /**
     * Langkah 1–5 sesuai dokumen Rekomendasi Metode Statistika:
     * 1) saring tugas aktif, 2) bobot prioritas 1/2/3/4, 3) W = Σ wᵢ,
     * 4) μ, σ, Z = (X−μ)/σ (σ=0 atau n<2 → Z=0), 5) status + warna + CRITICAL.
     */
    public function calculate(Collection $population, ?Collection $displayIds = null): array
    {
        $population->load([
            'assignedTasks' => fn ($query) => $query->whereIn('status', Task::ACTIVE_STATUSES),
        ]);

        $rows = $population->map(fn (User $user) => [
            'id'      => $user->id,
            'name'    => $user->name,
            'count'   => $user->assignedTasks->count(),
            'score'   => (int) $user->assignedTasks->sum(fn (Task $task) => $task->getPriorityWeight()),
            'overdue' => $user->assignedTasks->filter(fn (Task $task) => $task->isOverdue())->count(),
        ])->values();

        $scores = $rows->pluck('score');
        $mean   = $scores->avg() ?? 0;
        $stddev = $this->populationStdDev($scores);

        $rows = $rows->map(function (array $row) use ($mean, $stddev) {
            $z = $stddev > 0 ? ($row['score'] - $mean) / $stddev : 0;

            $row['z_score']  = round($z, 2);
            $row['critical'] = $z > 2.0;
            $row['status']   = $row['score'] <= 10 ? 'Aman' : ($row['score'] <= 20 ? 'Waspada' : 'Berisiko');
            $row['color']    = ($row['critical'] || $row['score'] > 20)
                ? 'danger'
                : ($row['score'] > 10 ? 'warning' : 'success');

            return $row;
        });

        if ($displayIds !== null) {
            $rows = $rows->filter(fn (array $row) => $displayIds->contains($row['id']));
        }

        return [
            'employees' => $rows->sortByDesc('score')->values(),
            'mean'      => round($mean, 2),
            'stddev'    => round($stddev, 2),
        ];
    }

    /** Standar deviasi populasi; 0 bila data < 2. */
    public function populationStdDev(Collection $scores): float
    {
        if ($scores->count() < 2) {
            return 0.0;
        }

        $mean     = $scores->avg();
        $variance = $scores->reduce(fn ($carry, $x) => $carry + ($x - $mean) ** 2, 0) / $scores->count();

        return sqrt($variance);
    }

    /** Tim pembanding Z-Score: manager = bawahan+diri; employee = saudara satu atasan+atasan. */
    private function teamOf(User $user): Collection
    {
        if ($user->isManager()) {
            return User::query()->where('manager_id', $user->id)->orWhere('id', $user->id)->get();
        }

        if ($user->manager_id !== null) {
            return User::query()
                ->where('manager_id', $user->manager_id)
                ->orWhere('id', $user->manager_id)
                ->get();
        }

        return User::query()->where('is_active', true)->get();
    }
}