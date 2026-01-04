<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('projects', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('path')->unique();
            $table->string('icon')->default('📁');
            $table->foreignId('default_tool_id')->nullable()->constrained('tools')->nullOnDelete();
            $table->foreignId('default_ai_cli_id')->nullable()->constrained('tools')->nullOnDelete();
            $table->enum('source', ['claude_scan', 'directory_scan', 'manual'])->default('manual');
            $table->boolean('is_favorite')->default(false);
            $table->boolean('is_archived')->default(false);
            $table->string('git_remote_url')->nullable();
            $table->timestamp('last_opened_at')->nullable();
            $table->timestamps();

            $table->index(['is_archived', 'is_favorite']);
            $table->index('last_opened_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('projects');
    }
};
