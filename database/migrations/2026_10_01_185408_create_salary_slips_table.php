<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('salary_slips', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('month', 7); // YYYY-MM
            $table->decimal('monthly_salary', 12, 2)->default(0);
            $table->unsignedSmallInteger('working_days')->default(0);
            $table->decimal('absent_days', 8, 2)->default(0);
            $table->decimal('unpaid_leave_days', 8, 2)->default(0);
            $table->decimal('deduct_days', 8, 2)->default(0);
            $table->decimal('daily_rate', 12, 2)->default(0);
            $table->decimal('deduction', 12, 2)->default(0);
            $table->decimal('net_pay', 12, 2)->default(0);
            $table->foreignId('generated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->unique(['user_id', 'month']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('salary_slips');
    }
};
