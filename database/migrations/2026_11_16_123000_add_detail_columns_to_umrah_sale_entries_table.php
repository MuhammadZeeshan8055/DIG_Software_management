<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('umrah_sale_entries', function (Blueprint $table) {
            $table->unsignedInteger('total_pax')->nullable()->after('airline');
            $table->decimal('air_ticket_pp', 14, 2)->default(0)->after('total_pax');
            $table->decimal('visa_cost_pp', 14, 2)->default(0)->after('air_ticket_pp');
            $table->decimal('makkah_hotel_pp', 14, 2)->default(0)->after('visa_cost_pp');
            $table->decimal('madinah_hotel_pp', 14, 2)->default(0)->after('makkah_hotel_pp');
            $table->decimal('ziarat_pp', 14, 2)->default(0)->after('madinah_hotel_pp');
            $table->decimal('transport_pp', 14, 2)->default(0)->after('ziarat_pp');
            $table->decimal('other_expense_pp', 14, 2)->default(0)->after('transport_pp');
            $table->decimal('agent_profit_pp', 14, 2)->default(0)->after('other_expense_pp');
        });
    }

    public function down(): void
    {
        Schema::table('umrah_sale_entries', function (Blueprint $table) {
            $table->dropColumn([
                'total_pax',
                'air_ticket_pp',
                'visa_cost_pp',
                'makkah_hotel_pp',
                'madinah_hotel_pp',
                'ziarat_pp',
                'transport_pp',
                'other_expense_pp',
                'agent_profit_pp',
            ]);
        });
    }
};
