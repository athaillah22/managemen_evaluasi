<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('manager_evaluations', function (Blueprint $table) {
            // Hapus kolom employee_feedback jika ada
            if (Schema::hasColumn('manager_evaluations', 'employee_feedback')) {
                $table->dropColumn('employee_feedback');
            }

            // Hapus kolom employee_feedback_at jika ada
            if (Schema::hasColumn('manager_evaluations', 'employee_feedback_at')) {
                $table->dropColumn('employee_feedback_at');
            }
        });
    }

    public function down(): void
    {
        Schema::table('manager_evaluations', function (Blueprint $table) {
            if (!Schema::hasColumn('manager_evaluations', 'employee_feedback')) {
                $table->text('employee_feedback')->nullable();
            }

            if (!Schema::hasColumn('manager_evaluations', 'employee_feedback_at')) {
                $table->timestamp('employee_feedback_at')->nullable();
            }
        });
    }
};