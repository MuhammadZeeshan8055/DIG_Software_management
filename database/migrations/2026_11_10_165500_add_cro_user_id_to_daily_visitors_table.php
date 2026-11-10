<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('daily_visitors', function (Blueprint $table) {
            $table->foreignId('cro_user_id')
                ->nullable()
                ->after('assigned_to')
                ->constrained('users')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('daily_visitors', function (Blueprint $table) {
            $table->dropConstrainedForeignId('cro_user_id');
        });
    }
};
