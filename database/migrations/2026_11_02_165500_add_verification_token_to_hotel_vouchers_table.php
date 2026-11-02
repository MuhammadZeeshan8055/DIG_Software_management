<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('hotel_vouchers', function (Blueprint $table) {
            $table->uuid('verification_token')->nullable()->unique()->after('voucher_number');
        });

        DB::table('hotel_vouchers')
            ->whereNull('verification_token')
            ->orderBy('id')
            ->each(function ($row): void {
                DB::table('hotel_vouchers')
                    ->where('id', $row->id)
                    ->update(['verification_token' => (string) Str::uuid()]);
            });
    }

    public function down(): void
    {
        Schema::table('hotel_vouchers', function (Blueprint $table) {
            $table->dropColumn('verification_token');
        });
    }
};
