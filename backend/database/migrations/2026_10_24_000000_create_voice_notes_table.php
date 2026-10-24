<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Voice notes are short audio recordings attached to a ticket, recorded
     * from the mobile report form. They live in their own table rather than
     * in reports.attachments so the duration is stored alongside the file —
     * the scrubber needs a total to show before the audio metadata loads.
     *
     * institution_id is deliberately part of the model (not only reachable
     * through the report) so the BelongsToInstitution global scope filters
     * these rows on their own, matching every other tenant model.
     */
    public function up(): void
    {
        Schema::create('voice_notes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('institution_id')->constrained()->cascadeOnDelete();
            $table->foreignId('report_id')->constrained('reports')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete(); // who recorded it
            $table->string('path');                  // relative path on the public disk
            $table->string('mime_type')->nullable();
            $table->unsignedSmallInteger('duration_seconds')->nullable();
            $table->unsignedInteger('size_bytes')->nullable();
            $table->timestamps();

            $table->index('report_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('voice_notes');
    }
};
