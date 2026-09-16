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
        Schema::table('invoices', function (Blueprint $table) {
            $table->uuid('verification_token')->nullable()->unique()->after('invoice_number');
        });

        DB::table('invoices')
            ->whereNull('verification_token')
            ->orderBy('id')
            ->pluck('id')
            ->each(function (int $id): void {
                DB::table('invoices')
                    ->where('id', $id)
                    ->update(['verification_token' => (string) Str::uuid()]);
            });
    }

    public function down(): void
    {
        Schema::table('invoices', function (Blueprint $table) {
            $table->dropColumn('verification_token');
        });
    }
};
