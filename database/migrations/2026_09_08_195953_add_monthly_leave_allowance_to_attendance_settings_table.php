<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Company-wide monthly leave allowance (same for all staff).
     * Example: 2 full days + 2 half days per month.
     */
    public function up(): void
    {
        Schema::table('attendance_settings', function (Blueprint $table) {
            $table->unsignedTinyInteger('monthly_full_days')->default(0)->after('office_ip_3');
            $table->unsignedTinyInteger('monthly_half_days')->default(0)->after('monthly_full_days');
        });
    }

    public function down(): void
    {
        Schema::table('attendance_settings', function (Blueprint $table) {
            $table->dropColumn(['monthly_full_days', 'monthly_half_days']);
        });
    }
};
