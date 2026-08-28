<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('ticket_imports', function (Blueprint $table) {
            $table->string('booking_reference')->nullable()->after('raw_pdf_text');
            $table->renameColumn('pnr', 'agency_pnr');
            $table->renameColumn('travel_date', 'departure_date');
            $table->string('travel_class', 50)->nullable()->after('route');
            $table->string('baggage', 50)->nullable()->after('travel_class');
            $table->string('booking_status', 50)->nullable()->after('baggage');
        });
    }

    public function down(): void
    {
        Schema::table('ticket_imports', function (Blueprint $table) {
            $table->dropColumn(['booking_reference', 'travel_class', 'baggage', 'booking_status']);
            $table->renameColumn('agency_pnr', 'pnr');
            $table->renameColumn('departure_date', 'travel_date');
        });
    }
};
