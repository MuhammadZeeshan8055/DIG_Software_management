<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('daily_visitors', function (Blueprint $table) {
            // null | awaiting_review | pending | closed — admin final authority after follow-up
            $table->string('case_status', 20)->nullable()->after('remarks');
            $table->text('case_admin_note')->nullable()->after('case_status');
            $table->foreignId('case_closed_by')->nullable()->after('case_admin_note')
                ->constrained('users')->nullOnDelete();
            $table->timestamp('case_closed_at')->nullable()->after('case_closed_by');
        });
    }

    public function down(): void
    {
        Schema::table('daily_visitors', function (Blueprint $table) {
            $table->dropConstrainedForeignId('case_closed_by');
            $table->dropColumn(['case_status', 'case_admin_note', 'case_closed_at']);
        });
    }
};
