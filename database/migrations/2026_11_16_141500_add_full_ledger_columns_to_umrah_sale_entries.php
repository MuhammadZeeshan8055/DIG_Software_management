<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('umrah_sale_entries', function (Blueprint $table) {
            $table->decimal('total_amount', 14, 2)->default(0)->after('payable');
            $table->decimal('remaining_amount', 14, 2)->default(0)->after('received');
            $table->decimal('loss', 14, 2)->default(0)->after('profit');

            $table->decimal('air_ticket_total', 14, 2)->default(0)->after('agent_profit_pp');
            $table->decimal('visa_total', 14, 2)->default(0)->after('air_ticket_total');
            $table->decimal('makkah_hotel_total', 14, 2)->default(0)->after('visa_total');
            $table->decimal('madinah_hotel_total', 14, 2)->default(0)->after('makkah_hotel_total');
            $table->decimal('ziarat_total', 14, 2)->default(0)->after('madinah_hotel_total');
            $table->decimal('transport_total', 14, 2)->default(0)->after('ziarat_total');
            $table->decimal('other_expense_total', 14, 2)->default(0)->after('transport_total');
            $table->decimal('agent_profit_total', 14, 2)->default(0)->after('other_expense_total');
        });
    }

    public function down(): void
    {
        Schema::table('umrah_sale_entries', function (Blueprint $table) {
            $table->dropColumn([
                'total_amount',
                'remaining_amount',
                'loss',
                'air_ticket_total',
                'visa_total',
                'makkah_hotel_total',
                'madinah_hotel_total',
                'ziarat_total',
                'transport_total',
                'other_expense_total',
                'agent_profit_total',
            ]);
        });
    }
};
