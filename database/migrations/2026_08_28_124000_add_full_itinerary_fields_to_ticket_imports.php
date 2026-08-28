<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('ticket_imports', function (Blueprint $table) {
            $table->string('agency_name')->nullable()->after('raw_pdf_text');
            $table->string('agency_phone', 30)->nullable()->after('agency_name');
            $table->string('frequent_flyer', 50)->nullable()->after('passenger_name');
            $table->json('flight_segments')->nullable()->after('ticket_number');
        });

        Schema::table('ticket_imports', function (Blueprint $table) {
            $table->dropColumn([
                'airline',
                'flight_number',
                'route',
                'departure_date',
                'travel_class',
                'baggage',
                'booking_status',
            ]);
        });
    }

    public function down(): void
    {
        Schema::table('ticket_imports', function (Blueprint $table) {
            $table->string('airline', 50)->nullable();
            $table->string('flight_number', 20)->nullable();
            $table->string('route', 100)->nullable();
            $table->date('departure_date')->nullable();
            $table->string('travel_class', 50)->nullable();
            $table->string('baggage', 50)->nullable();
            $table->string('booking_status', 50)->nullable();
        });

        Schema::table('ticket_imports', function (Blueprint $table) {
            $table->dropColumn([
                'agency_name',
                'agency_phone',
                'frequent_flyer',
                'flight_segments',
            ]);
        });
    }
};
