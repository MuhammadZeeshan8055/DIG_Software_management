<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('visitor_reminders', function (Blueprint $table) {
            // What happened after the reminder follow-up
            $table->string('done_note', 500)->nullable()->after('is_done');
            $table->timestamp('done_at')->nullable()->after('done_note');
        });
    }

    public function down(): void
    {
        Schema::table('visitor_reminders', function (Blueprint $table) {
            $table->dropColumn(['done_note', 'done_at']);
        });
    }
};
