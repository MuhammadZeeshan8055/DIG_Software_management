<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Step 2: full day vs half day on each leave request.
     *
     * leave_type  = 'full' or 'half'
     * full_days   = how many full days this request uses (0 for half leave)
     * half_days   = how many half days this request uses (0 for full leave)
     * is_paid     = true for now; later unpaid penalties can set false
     */
    public function up(): void
    {
        Schema::table('leave_requests', function (Blueprint $table) {
            $table->string('leave_type', 10)->default('full')->after('to_date');
            $table->unsignedTinyInteger('full_days')->default(1)->after('leave_type');
            $table->unsignedTinyInteger('half_days')->default(0)->after('full_days');
            $table->boolean('is_paid')->default(true)->after('half_days');
        });
    }

    public function down(): void
    {
        Schema::table('leave_requests', function (Blueprint $table) {
            $table->dropColumn(['leave_type', 'full_days', 'half_days', 'is_paid']);
        });
    }
};
