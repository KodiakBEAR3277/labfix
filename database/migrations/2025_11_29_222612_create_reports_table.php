<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('reports', function (Blueprint $table) {
            $table->id();
            $table->string('ticket_number')->unique(); // e.g., TKT-2025-001
            $table->foreignId('user_id')->constrained()->onDelete('cascade'); // Reporter
            $table->foreignId('assigned_to')->nullable()->constrained('users')->onDelete('set null'); // Assigned IT staff
            
            // Location & Equipment
            $table->string('lab_location'); // e.g., Computer Lab A
            $table->string('equipment_id')->nullable(); // e.g., PC-12
            
            // Issue Details
            $table->enum('category', ['hardware', 'software', 'network', 'other']);
            $table->string('title');
            $table->text('description');
            
            // Status & Priority
            $table->enum('status', ['new', 'assigned', 'in-progress', 'resolved', 'closed'])->default('new');
            $table->enum('priority', ['low', 'medium', 'high'])->default('medium');
            
            // Attachments (we'll store file paths as JSON array)
            $table->json('attachments')->nullable();
            
            // Timestamps
            $table->timestamp('assigned_at')->nullable();
            $table->timestamp('resolved_at')->nullable();
            $table->timestamp('closed_at')->nullable();
            $table->timestamps();
            
            // Indexes for better query performance
            $table->index('user_id');
            $table->index('assigned_to');
            $table->index('status');
            $table->index('priority');
            $table->index('created_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('reports');
    }
};