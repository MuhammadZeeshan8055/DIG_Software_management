<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('desks', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('key', 50)->unique();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        // Default desks for the office
        DB::table('desks')->insert([
            [
                'name' => 'Umrah Desk',
                'key' => 'umrah',
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'Ticketing',
                'key' => 'ticketing',
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'Study & Visa',
                'key' => 'study',
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'Travel & Tourism',
                'key' => 'travel',
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'Accounts',
                'key' => 'accounts',
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);
    }

    public function down(): void
    {
        Schema::dropIfExists('desks');
    }
};
