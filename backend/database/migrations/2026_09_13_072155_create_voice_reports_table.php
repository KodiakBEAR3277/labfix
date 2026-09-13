<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('voice_reports', function (Blueprint $table) {
            $table->id();
            // No nullable-for-backfill dance needed here, unlike the retrofit
            // migrations — this is a brand-new table, so every row will
            // always have a real institution from the moment it's created.
            $table->foreignId('institution_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            // Nullable: a dictation session may not yet have produced a
            // submitted ticket, or the user may abandon it partway through.
            $table->foreignId('report_id')->nullable()->constrained('reports')->nullOnDelete();
            $table->text('transcript');
            $table->string('extracted_title')->nullable();
            $table->text('extracted_description')->nullable();
            $table->string('extracted_category')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('voice_reports');
    }
};