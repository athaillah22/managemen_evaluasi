<?php

namespace App\Policies;

use App\Models\Task;
use App\Models\User;

class TaskPolicy
{
    public function viewAny(User $user): bool
    {
        return true; // scoping daftar via getEloquentQuery()
    }

    public function view(User $user, Task $task): bool
    {
        return true; // scoping sudah membatasi
    }

    public function create(User $user): bool
    {
        return true; // semua role boleh buat tugas; assignee dibatasi di form
    }

    public function update(User $user, Task $task): bool
    {
        // Hanya penerima tugas atau HR yang boleh update (mis. status)
        return $user->role === 'hr' || $user->id === $task->assigned_to_user_id;
    }

    public function delete(User $user, Task $task): bool
    {
        return $user->role === 'hr' || $user->id === $task->assigned_by;
    }
}