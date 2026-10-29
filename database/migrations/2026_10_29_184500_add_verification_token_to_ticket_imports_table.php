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
        Schema::table('ticket_imports', function (Blueprint $table) {
            $table->uuid('verification_token')->nullable()->unique()->after('id');
        });

        DB::table('ticket_imports')
            ->whereNull('verification_token')
            ->orderBy('id')
            ->chunkById(100, function ($rows): void {
                foreach ($rows as $row) {
                    DB::table('ticket_imports')
                        ->where('id', $row->id)
                        ->update(['verification_token' => (string) Str::uuid()]);
                }
            });
    }

    public function down(): void
    {
        Schema::table('ticket_imports', function (Blueprint $table) {
            $table->dropColumn('verification_token');
        });
    }
};
