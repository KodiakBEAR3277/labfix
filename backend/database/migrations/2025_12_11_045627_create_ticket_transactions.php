<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ticket_transactions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('ticket_id')->constrained('reports')->onDelete('cascade');
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->string('action'); // created, status_changed, assigned, priority_changed, updated, deleted, restored
            $table->text('old_value')->nullable();
            $table->text('new_value')->nullable();
            $table->text('description'); // Human-readable description
            $table->timestamp('created_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ticket_transactions');
    }
};