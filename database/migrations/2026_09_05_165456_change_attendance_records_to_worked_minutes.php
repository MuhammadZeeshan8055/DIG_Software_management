<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * One duration column only: worked_minutes.
     * Overtime is not stored — compute later from required_hours in settings.
     */
    public function up(): void
    {
        Schema::table('attendance_records', function (Blueprint $table) {
            $table->unsignedInteger('worked_minutes')->default(0)->after('check_out_at');
        });

        // Keep any existing test punches (seconds → whole minutes)
        if (Schema::hasColumn('attendance_records', 'worked_seconds')) {
            DB::table('attendance_records')->orderBy('id')->each(function ($row) {
                DB::table('attendance_records')
                    ->where('id', $row->id)
                    ->update([
                        'worked_minutes' => (int) floor(((int) $row->worked_seconds) / 60),
                    ]);
            });
        }

        Schema::table('attendance_records', function (Blueprint $table) {
            if (Schema::hasColumn('attendance_records', 'worked_seconds')) {
                $table->dropColumn('worked_seconds');
            }
            if (Schema::hasColumn('attendance_records', 'overtime_seconds')) {
                $table->dropColumn('overtime_seconds');
            }
        });
    }

    public function down(): void
    {
        Schema::table('attendance_records', function (Blueprint $table) {
            $table->unsignedInteger('worked_seconds')->default(0)->after('check_out_at');
            $table->unsignedInteger('overtime_seconds')->default(0)->after('worked_seconds');
        });

        DB::table('attendance_records')->orderBy('id')->each(function ($row) {
            DB::table('attendance_records')
                ->where('id', $row->id)
                ->update([
                    'worked_seconds' => ((int) $row->worked_minutes) * 60,
                    'overtime_seconds' => 0,
                ]);
        });

        Schema::table('attendance_records', function (Blueprint $table) {
            $table->dropColumn('worked_minutes');
        });
    }
};
