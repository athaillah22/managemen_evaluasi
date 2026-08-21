<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('manager_evaluations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('task_id')->constrained('tasks')->cascadeOnDelete();
            $table->foreignId('manager_id')->constrained('users')->cascadeOnDelete();
            $table->unsignedTinyInteger('score'); // 1-5
            $table->text('note')->nullable();
            $table->timestamps();

            $table->unique(['task_id', 'manager_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('manager_evaluations');
    }
};