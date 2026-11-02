<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('hotel_vouchers', function (Blueprint $table) {
            $table->id();
            $table->string('voucher_number')->unique();
            $table->date('issue_date');
            $table->string('status', 20)->default('draft'); // draft | issued

            $table->string('booking_reference')->nullable();
            $table->string('family_head');
            $table->string('group_number')->nullable();
            $table->string('agent_customer')->nullable();
            $table->string('package_program')->nullable();
            $table->string('country')->default('Pakistan');
            $table->string('visa_type')->default('Umrah Visa');
            $table->text('service_scope')->nullable();

            $table->json('guests')->nullable();
            $table->json('stays')->nullable();
            $table->json('flights')->nullable();
            $table->json('transport')->nullable();

            $table->string('ksa_helpline')->nullable();
            $table->string('pakistan_helpline')->nullable();
            $table->string('prepared_by')->nullable();
            $table->text('special_instructions')->nullable();

            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('hotel_vouchers');
    }
};
