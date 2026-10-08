<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('daily_visitors', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('contact_no', 40);
            $table->string('purpose', 255);
            $table->foreignId('desk_id')->constrained('desks')->restrictOnDelete();
            $table->foreignId('assigned_to')->constrained('users')->restrictOnDelete();
            $table->foreignId('created_by')->constrained('users')->restrictOnDelete();
            // waiting | please_wait | send_now | completed
            $table->string('status', 20)->default('waiting');
            $table->text('remarks')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('daily_visitors');
    }
};
