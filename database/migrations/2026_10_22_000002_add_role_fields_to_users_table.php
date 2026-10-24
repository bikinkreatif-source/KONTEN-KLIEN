<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            if (! Schema::hasColumn('users', 'role')) $table->string('role')->default('team');
            if (! Schema::hasColumn('users', 'division')) $table->string('division')->nullable();
        });
    }
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            if (Schema::hasColumn('users', 'division')) $table->dropColumn('division');
            if (Schema::hasColumn('users', 'role')) $table->dropColumn('role');
        });
    }
};
