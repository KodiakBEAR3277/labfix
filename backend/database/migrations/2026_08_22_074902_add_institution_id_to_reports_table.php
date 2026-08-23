<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('reports', function (Blueprint $table) {
            $table->foreignId('institution_id')->nullable()->after('id')->constrained()->restrictOnDelete();

            $table->dropUnique(['ticket_number']);
            $table->unique(['institution_id', 'ticket_number']);
        });
    }

    public function down(): void
    {
        Schema::table('reports', function (Blueprint $table) {
            $table->dropUnique(['institution_id', 'ticket_number']);
            $table->unique('ticket_number');

            $table->dropConstrainedForeignId('institution_id');
        });
    }
};