<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('clients', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->date('start_date');
            $table->date('end_date');
            $table->unsignedInteger('quota_single')->default(0);
            $table->unsignedInteger('quota_carousel')->default(0);
            $table->unsignedInteger('quota_reels')->default(0);
            $table->unsignedInteger('quota_story')->default(0);
            $table->text('pillars')->nullable();
            $table->text('information')->nullable();
            $table->string('instagram')->nullable();
            $table->string('tiktok')->nullable();
            $table->string('facebook')->nullable();
            $table->string('drive_url')->nullable();
            $table->string('logo_path')->nullable();
            $table->string('public_token', 48)->unique();
            $table->timestamps();
        });

        Schema::table('users', function (Blueprint $table) {
            $table->foreignId('client_id')->nullable()->after('division')->constrained()->nullOnDelete();
        });

        Schema::create('contents', function (Blueprint $table) {
            $table->id();
            $table->foreignId('client_id')->constrained()->cascadeOnDelete();
            $table->foreignId('assignee_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('title');
            $table->string('type');
            $table->date('publish_date');
            $table->time('publish_time')->nullable();
            $table->string('status')->default('Ide');
            $table->string('pillar')->nullable();
            $table->string('funnel')->nullable();
            $table->text('brief')->nullable();
            $table->text('caption')->nullable();
            $table->text('reference_url')->nullable();
            $table->text('drive_url')->nullable();
            $table->text('next_step')->nullable();
            $table->text('checklist')->nullable();
            $table->timestamps();
            $table->index(['client_id', 'publish_date']);
        });

        Schema::create('content_revisions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('content_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('action');
            $table->text('comment')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('content_revisions');
        Schema::dropIfExists('contents');
        Schema::table('users', fn (Blueprint $table) => $table->dropConstrainedForeignId('client_id'));
        Schema::dropIfExists('clients');
    }
};
