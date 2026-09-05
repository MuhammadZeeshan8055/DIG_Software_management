<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * One row per employee per work day (check-in / check-out).
     */
    public function up(): void
    {
        Schema::create('attendance_records', function (Blueprint $table) {
            $table->id();

            // Who punched
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();

            // Calendar day of this shift (Asia/Karachi date)
            $table->date('work_date');

            // Punch times
            $table->dateTime('check_in_at')->nullable();
            $table->dateTime('check_out_at')->nullable();

            // Calculated after check-out
            $table->unsignedInteger('worked_seconds')->default(0);
            $table->unsignedInteger('overtime_seconds')->default(0);

            // red = under hours, green = met/over hours
            $table->string('status_color', 10)->nullable();

            $table->timestamps();

            // One attendance row per user per day
            $table->unique(['user_id', 'work_date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('attendance_records');
    }
};
