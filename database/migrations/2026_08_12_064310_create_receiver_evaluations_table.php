<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('receiver_evaluations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('task_id')->constrained('tasks')->cascadeOnDelete();
            $table->foreignId('receiver_id')->constrained('users')->cascadeOnDelete();
            $table->unsignedTinyInteger('clarity_score'); // 1-5 kejelasan instruksi
            $table->unsignedTinyInteger('difficulty_score'); // 1-5 tingkat kesulitan
            $table->text('note')->nullable();
            $table->timestamps();

            $table->unique(['task_id', 'receiver_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('receiver_evaluations');
    }
};