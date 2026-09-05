<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Store which module features each staff user can view or manage.
     */
    public function up(): void
    {
        Schema::create('user_permissions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('module_key');   // e.g. accounts
            $table->string('feature_key');  // e.g. payments
            $table->string('access');       // view | manage
            $table->timestamps();

            $table->unique(['user_id', 'module_key', 'feature_key']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('user_permissions');
    }
};
