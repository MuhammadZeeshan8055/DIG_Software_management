<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('desk_user', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('desk_id')->constrained('desks')->cascadeOnDelete();
            $table->timestamps();

            $table->unique(['user_id', 'desk_id']);
        });

        $rows = DB::table('users')
            ->whereNotNull('desk_id')
            ->get(['id', 'desk_id']);

        $now = now();

        foreach ($rows as $row) {
            DB::table('desk_user')->insert([
                'user_id' => $row->id,
                'desk_id' => $row->desk_id,
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }

        Schema::table('users', function (Blueprint $table) {
            $table->dropConstrainedForeignId('desk_id');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->foreignId('desk_id')
                ->nullable()
                ->after('monthly_salary')
                ->constrained('desks')
                ->nullOnDelete();
        });

        $rows = DB::table('desk_user')
            ->orderBy('id')
            ->get(['user_id', 'desk_id']);

        foreach ($rows as $row) {
            DB::table('users')
                ->where('id', $row->user_id)
                ->whereNull('desk_id')
                ->update(['desk_id' => $row->desk_id]);
        }

        Schema::dropIfExists('desk_user');
    }
};
