<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('manager_evaluations', function (Blueprint $table) {
            if (! Schema::hasColumn('manager_evaluations', 'employee_feedback')) {
                $table->text('employee_feedback')->nullable();      // tanggapan karyawan ke atasan
                $table->timestamp('employee_feedback_at')->nullable();
            }
        });
    }

    public function down(): void
    {
        Schema::table('manager_evaluations', function (Blueprint $table) {
            foreach (['employee_feedback', 'employee_feedback_at'] as $column) {
                if (Schema::hasColumn('manager_evaluations', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};