<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Task extends Model
{
    use HasFactory;

    public const ACTIVE_STATUSES = ['pending', 'in_progress', 'waiting_review'];

    public const STATUS_LABELS = [
        'pending'        => 'Menunggu (To Do)',
        'in_progress'    => 'Sedang Dikerjakan',
        'waiting_review' => 'Menunggu Review',
        'completed'      => 'Selesai (Done)',
        'cancelled'      => 'Dibatalkan',
    ];

    /** Alias — dipakai oleh TaskResource (filter/select) Anda */
    public const STATUS_OPTIONS = self::STATUS_LABELS;

    public const PRIORITY_LABELS = [
        'low'    => 'Rendah',
        'medium' => 'Sedang',
        'high'   => 'Tinggi',
        'urgent' => 'Kritis',
    ];

    /** Alias — dipakai oleh TaskResource (filter/select) Anda */
    public const PRIORITY_OPTIONS = self::PRIORITY_LABELS;

    public const PRIORITY_WEIGHTS = ['low' => 1, 'medium' => 2, 'high' => 3, 'urgent' => 4];

    protected $fillable = [
        'title',
        'description',
        'status',
        'priority',
        'due_date',
        'assigned_by',
        'assigned_to_user_id',
    ];

    protected function casts(): array
    {
        return ['due_date' => 'date'];
    }

    /* ---------- Relasi ---------- */

    public function assignedTo(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_to_user_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_by');
    }

    public function managerEvaluations(): HasMany
    {
        return $this->hasMany(ManagerEvaluation::class);
    }

    public function receiverEvaluations(): HasMany
    {
        return $this->hasMany(ReceiverEvaluation::class);
    }

    /* ---------- Helper & kalkulasi beban ---------- */

    public function getPriorityWeight(): int
    {
        return self::PRIORITY_WEIGHTS[$this->priority] ?? 1;
    }

    public function isActive(): bool
    {
        return in_array($this->status, self::ACTIVE_STATUSES, true);
    }

    public function isOverdue(): bool
    {
        return $this->due_date !== null && $this->isActive() && $this->due_date->isPast();
    }

    /** Evaluator resmi = atasan PENERIMA tugas (bukan pembuat tugas) */
    public function getEvaluator(): ?User
    {
        return $this->assignedTo?->manager;
    }
}