<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('umrah_sale_entries', function (Blueprint $table) {
            $table->id();
            $table->date('sale_date');
            $table->string('booking_id', 80)->nullable();      // Excel: ID (UMR-001)
            $table->string('passenger_name', 191);
            $table->string('passport_no', 80)->nullable();
            $table->string('airline', 120)->nullable();
            $table->decimal('payable', 14, 2)->default(0);     // Cost Per Person
            $table->decimal('received', 14, 2)->default(0);    // Received Amount
            $table->decimal('profit', 14, 2)->nullable();      // Received - Payable
            $table->string('report_month', 7)->index();         // Y-m e.g. 2026-10
            $table->string('original_filename')->nullable();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('umrah_sale_entries');
    }
};
