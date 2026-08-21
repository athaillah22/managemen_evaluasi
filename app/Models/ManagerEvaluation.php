<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ManagerEvaluation extends Model
{
    use HasFactory;

    protected $fillable = [
        'task_id',
        'manager_id',
        'score',
        'note',
        'employee_feedback',     // tanggapan balik karyawan ke atasan (fitur Feedback Karyawan)
        'employee_feedback_at',
    ];

    protected function casts(): array
    {
        return [
            'employee_feedback_at' => 'datetime',
        ];
    }

    /* ---------- Relasi ---------- */

    public function task(): BelongsTo
    {
        return $this->belongsTo(Task::class);
    }

    public function manager(): BelongsTo
    {
        return $this->belongsTo(User::class, 'manager_id');
    }

    /* ---------- Helper ---------- */

    public function hasEmployeeFeedback(): bool
    {
        return filled($this->employee_feedback);
    }

    /**
     * Validasi backend (Masalah 2 & 5 + revisi Bab 7):
     * - Hanya HR / Manager yang boleh menilai karyawan
     * - Tugas wajib berstatus Done (Masalah 2)
     * - Manager hanya boleh menilai bawahannya; HR bebas
     */
    public static function booted(): void
    {
        static::creating(function (ManagerEvaluation $evaluation) {
            // Seeding/tinker lewat console tetap diizinkan
            if (app()->runningInConsole()) {
                return;
            }

            $task = $evaluation->task;
            $user = auth()->user();

            abort_unless($user !== null && ($user->isHr() || $user->isManager()), 403,
                'Hanya HR dan Manager yang dapat melakukan evaluasi.');

            abort_unless($task?->status === 'completed', 422,
                'Evaluasi hanya bisa diisi setelah tugas berstatus Done.');

            abort_unless(
                $user->isHr() || $task->assignedTo?->manager_id === $user->id,
                403,
                'Manager hanya dapat menilai tugas bawahannya sendiri.'
            );
        });
    }
}