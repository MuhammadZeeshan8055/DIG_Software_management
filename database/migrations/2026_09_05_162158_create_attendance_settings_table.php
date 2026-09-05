<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Office rules (usually 1 row only).
     */
    public function up(): void
    {
        Schema::create('attendance_settings', function (Blueprint $table) {
            $table->id();

            // Official shift window (e.g. 10:00 and 18:00)
            $table->time('office_start');
            $table->time('office_end');

            // How many hours count as a full day (e.g. 8)
            $table->unsignedTinyInteger('required_hours')->default(8);

            // Lunch / break window (optional times)
            $table->time('lunch_start')->nullable();
            $table->time('lunch_end')->nullable();

            // Break length to subtract from worked time (minutes)
            $table->unsignedSmallInteger('break_minutes')->default(60);

            // Up to 3 office IPs (2 offices + 1 optional)
            $table->string('office_ip_1', 45)->nullable();
            $table->string('office_ip_2', 45)->nullable();
            $table->string('office_ip_3', 45)->nullable();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('attendance_settings');
    }
};
