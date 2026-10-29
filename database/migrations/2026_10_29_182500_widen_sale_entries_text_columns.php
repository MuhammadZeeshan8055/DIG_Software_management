<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('sale_entries', function (Blueprint $table) {
            // Register airlines can be long, e.g. "PIA (PK) - Pakistan International Airlines"
            $table->string('airline', 120)->nullable()->change();
            $table->string('passenger_name', 191)->change();
            $table->string('sector', 120)->nullable()->change();
            $table->string('via', 120)->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('sale_entries', function (Blueprint $table) {
            $table->string('airline', 20)->nullable()->change();
            $table->string('passenger_name')->change();
            $table->string('sector')->nullable()->change();
            $table->string('via')->nullable()->change();
        });
    }
};
