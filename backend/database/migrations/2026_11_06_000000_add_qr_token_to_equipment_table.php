<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('equipment', function (Blueprint $table) {
            $table->uuid('qr_token')->nullable()->unique()->after('equipment_code');
        });

        // Backfill existing equipment. Uses the query builder so the
        // BelongsToInstitution trait and model events do not run mid-migration.
        // The 'GENERAL' placeholder is never scannable, so it gets no token.
        DB::table('equipment')
            ->whereNull('qr_token')
            ->where('equipment_code', '!=', 'GENERAL')
            ->orderBy('id')
            ->chunkById(200, function ($rows) {
                foreach ($rows as $row) {
                    DB::table('equipment')
                        ->where('id', $row->id)
                        ->update(['qr_token' => (string) Str::uuid()]);
                }
            });
    }

    public function down(): void
    {
        Schema::table('equipment', function (Blueprint $table) {
            $table->dropUnique(['qr_token']);
            $table->dropColumn('qr_token');
        });
    }
};
