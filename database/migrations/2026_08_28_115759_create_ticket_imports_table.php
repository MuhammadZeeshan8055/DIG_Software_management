<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ticket_imports', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('original_filename');
            $table->string('pdf_path');
            $table->longText('raw_pdf_text')->nullable();
            $table->string('ticket_number')->nullable();
            $table->string('pnr', 20)->nullable();
            $table->string('passenger_name')->nullable();
            $table->string('airline', 50)->nullable();
            $table->string('flight_number', 20)->nullable();
            $table->string('route', 100)->nullable();
            $table->date('travel_date')->nullable();
            $table->string('status')->default('confirmed');
            $table->timestamp('confirmed_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ticket_imports');
    }
};
