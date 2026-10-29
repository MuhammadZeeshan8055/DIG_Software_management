<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('sale_entries', function (Blueprint $table) {
            // Register columns: Profit (PKR) and Loss (PKR). `profit` remains Net Profit/Loss.
            $table->decimal('gross_profit', 14, 2)->nullable()->after('via');
            $table->decimal('loss', 14, 2)->nullable()->after('gross_profit');
        });
    }

    public function down(): void
    {
        Schema::table('sale_entries', function (Blueprint $table) {
            $table->dropColumn(['gross_profit', 'loss']);
        });
    }
};
