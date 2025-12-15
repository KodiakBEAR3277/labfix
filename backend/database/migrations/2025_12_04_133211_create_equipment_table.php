<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('equipment', function (Blueprint $table) {
            $table->id();
            $table->foreignId('lab_id')->constrained()->onDelete('cascade');
            $table->string('equipment_code'); // e.g., "PC-01"
            $table->enum('type', ['computer', 'printer', 'projector', 'other'])->default('computer');
            $table->enum('status', ['operational', 'has-issue', 'maintenance', 'retired'])->default('operational');
            $table->text('notes')->nullable();
            $table->timestamps();
            
            // Unique constraint: Each lab can have unique equipment codes
            $table->unique(['lab_id', 'equipment_code']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('equipment');
    }
};