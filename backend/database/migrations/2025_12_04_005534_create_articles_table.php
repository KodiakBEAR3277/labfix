<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('articles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('author_id')->constrained('users')->onDelete('cascade');
            
            // Article Content
            $table->string('title');
            $table->string('slug')->unique();
            $table->text('content');
            $table->text('excerpt')->nullable(); // Short summary
            
            // Category & Status
            $table->enum('category', ['hardware', 'software', 'network', 'display', 'peripherals', 'general'])->default('general');
            $table->enum('status', ['draft', 'published'])->default('draft');
            
            // Metrics
            $table->integer('views')->default(0);
            $table->integer('helpful_count')->default(0);
            $table->integer('not_helpful_count')->default(0);
            
            // Timestamps
            $table->timestamp('published_at')->nullable();
            $table->timestamps();
            
            // Indexes
            $table->index('status');
            $table->index('category');
            $table->index('slug');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('articles');
    }
};