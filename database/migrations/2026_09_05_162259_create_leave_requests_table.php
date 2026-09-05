<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Employee leave requests (approved days = paid / not required work).
     */
    public function up(): void
    {
        Schema::create('leave_requests', function (Blueprint $table) {
            $table->id();

            // Who asked for leave
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();

            // Leave range (inclusive)
            $table->date('from_date');
            $table->date('to_date');

            // Optional note
            $table->string('reason', 255)->nullable();

            // pending | approved | rejected
            $table->string('status', 20)->default('pending');

            // Admin who approved/rejected (nullable until action)
            $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('leave_requests');
    }
};
