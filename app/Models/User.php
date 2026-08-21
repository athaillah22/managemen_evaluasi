<?php

namespace App\Models;

use Filament\Models\Contracts\FilamentUser;
use Filament\Panel;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable implements FilamentUser
{
    use HasFactory, Notifiable;

    protected $fillable = [
        'name',
        'email',
        'password',
        'role',
        'is_active',
        'manager_id',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'is_active' => 'boolean',
        ];
    }

    // Agar hanya user aktif yang bisa login ke panel Filament
    public function canAccessPanel(Panel $panel): bool
    {
        return $this->is_active;
    }

    public function manager(): BelongsTo
    {
        return $this->belongsTo(User::class, 'manager_id');
    }

    public function subordinates(): HasMany
    {
        return $this->hasMany(User::class, 'manager_id');
    }

    public function tasksAssigned(): HasMany
    {
        return $this->hasMany(Task::class, 'assigned_to_user_id');
    }

    public function tasksCreated(): HasMany
    {
        return $this->hasMany(Task::class, 'assigned_by');
    }

    public function isHr(): bool
    {
        return $this->role === 'hr';
    }

    public function isManager(): bool
    {
        return $this->role === 'manager';
    }

    public function isEmployee(): bool
    {
        return $this->role === 'employee';
    }

    // User yang boleh di-assign oleh user ini (sesuai Bab 7 dokumen Anda)
    public function getAssignableUsers()
    {
        return match ($this->role) {
            'hr' => static::query()
                ->where('is_active', true)
                ->pluck('name', 'id'),

            'manager' => static::query()
                ->where('is_active', true)
                ->where(fn ($q) => $q->where('manager_id', $this->id)->orWhere('id', $this->id))
                ->pluck('name', 'id'),

            default => static::query()
                ->where('role', 'employee')
                ->where('is_active', true)
                ->pluck('name', 'id'),
        };
    }

    // ===== TAMBAHAN UNTUK KOMPATIBILITAS DENGAN WorkloadService =====

    /**
     * Alias relasi - WorkloadService butuh nama ini
     */
    public function assignedTasks(): HasMany
    {
        return $this->tasksAssigned();
    }

    /**
     * Alias relasi untuk tugas yang dibuat user ini
     */
    public function createdTasks(): HasMany
    {
        return $this->tasksCreated();
    }

    /**
     * Bolehkah user ini memberi tugas ke $target? (Bab 7)
     */
    public function canAssignTo(User $target): bool
    {
        return match ($this->role) {
            'hr' => true,
            'manager' => $target->manager_id === $this->id || $target->id === $this->id,
            default => $target->role === 'employee',
        };
    }

    /**
     * Tugas aktif (belum selesai/dibatalkan)
     */
    public function getActiveTasks()
    {
        return $this->assignedTasks()
            ->whereIn('status', ['pending', 'in_progress', 'waiting_review'])
            ->get();
    }

    /**
     * Weighted Sum: W = Σ bobot prioritas
     */
    public function getWorkloadScore(): float
    {
        return (float) $this->getActiveTasks()
            ->sum(fn ($task) => $task->getPriorityWeight());
    }

    /**
     * Z-Score terhadap tim (dihitung via WorkloadService)
     */
    public function getZScore(): float
    {
        $data = app(\App\Services\WorkloadService::class)->forUser($this);
        
        return (float) ($data['employees']->firstWhere('id', $this->id)['z_score'] ?? 0);
    }

    /**
     * Status beban kerja berdasarkan ambang W (Bab 6.2)
     */
    public function getWorkloadStatus(): string
    {
        $w = $this->getWorkloadScore();
        
        return $w <= 10 ? 'Aman' : ($w <= 20 ? 'Waspada' : 'Berisiko');
    }
}