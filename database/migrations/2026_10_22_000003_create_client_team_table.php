<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('client_team', function (Blueprint $table) {
            $table->id();
            $table->foreignId('client_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('assigned_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->unique(['client_id', 'user_id']);
        });

        // Migrate legacy single-client team links into the multi-client assignment table.
        if (Schema::hasColumn('users', 'client_id')) {
            \Illuminate\Support\Facades\DB::table('users')->whereNotNull('client_id')->where('role', 'team')->get()->each(function ($user) {
                \Illuminate\Support\Facades\DB::table('client_team')->insertOrIgnore([
                    'client_id' => $user->client_id, 'user_id' => $user->id,
                    'created_at' => now(), 'updated_at' => now(),
                ]);
            });
        }
    }

    public function down(): void { Schema::dropIfExists('client_team'); }
};
