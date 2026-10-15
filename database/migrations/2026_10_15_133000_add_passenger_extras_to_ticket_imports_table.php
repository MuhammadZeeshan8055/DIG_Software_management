<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('ticket_imports', function (Blueprint $table) {
            $table->json('passenger_extras')->nullable()->after('flight_segments');
        });
    }

    public function down(): void
    {
        Schema::table('ticket_imports', function (Blueprint $table) {
            $table->dropColumn('passenger_extras');
        });
    }
};
