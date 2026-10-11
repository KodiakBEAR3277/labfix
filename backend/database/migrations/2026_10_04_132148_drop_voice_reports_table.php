<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * voice_reports backed the old per-field speech-to-text dictation
     * feature. It never recorded anything in practice — Gboard's own
     * voice typing already covered that use case — and it's being
     * replaced by actual voice-message attachments instead, which reuse
     * the existing reports.attachments column rather than a dedicated
     * table. Safe to drop outright.
     */
    public function up(): void
    {
        Schema::dropIfExists('voice_reports');
    }

    public function down(): void
    {
        Schema::create('voice_reports', function (Blueprint $table) {
            $table->id();
            $table->foreignId('institution_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('report_id')->nullable()->constrained('reports')->nullOnDelete();
            $table->text('transcript');
            $table->string('extracted_title')->nullable();
            $table->text('extracted_description')->nullable();
            $table->string('extracted_category')->nullable();
            $table->timestamps();
        });
    }
};