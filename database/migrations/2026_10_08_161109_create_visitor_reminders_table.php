<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('visitor_reminders', function (Blueprint $table) {
            $table->id();
            $table->foreignId('daily_visitor_id')->constrained('daily_visitors')->cascadeOnDelete();
            // Who gets the reminder (usually the meet-with person)
            $table->foreignId('user_id')->constrained('users')->restrictOnDelete();
            $table->date('remind_on');
            $table->string('note', 255)->nullable();
            // Null until the daily job sends the bell notification
            $table->timestamp('notified_at')->nullable();
            // Staff marks reminder as handled
            $table->boolean('is_done')->default(false);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('visitor_reminders');
    }
};
