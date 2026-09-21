<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('invoice_payments', function (Blueprint $table) {
            $table->foreignId('receiving_account_id')
                ->nullable()
                ->after('user_id')
                ->constrained('receiving_accounts')
                ->nullOnDelete();
            $table->string('received_in', 30)->nullable()->after('receiving_account_id');
            $table->string('received_account', 100)->nullable()->after('received_in');
        });
    }

    public function down(): void
    {
        Schema::table('invoice_payments', function (Blueprint $table) {
            $table->dropConstrainedForeignId('receiving_account_id');
            $table->dropColumn(['received_in', 'received_account']);
        });
    }
};
