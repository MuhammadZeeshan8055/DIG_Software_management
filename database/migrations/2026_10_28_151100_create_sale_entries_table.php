<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sale_entries', function (Blueprint $table) {
            $table->id();
            $table->date('sale_date');
            $table->string('passenger_name');
            $table->string('sector')->nullable();
            $table->string('airline', 20)->nullable();
            $table->decimal('payable', 14, 2)->default(0);
            $table->decimal('received', 14, 2)->default(0);
            $table->string('via')->nullable();
            $table->decimal('profit', 14, 2)->nullable();
            $table->string('report_month', 7)->index(); // Y-m e.g. 2026-10
            $table->string('original_filename')->nullable();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sale_entries');
    }
};
