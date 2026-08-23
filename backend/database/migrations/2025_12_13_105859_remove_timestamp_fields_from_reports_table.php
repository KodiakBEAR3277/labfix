<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Made defensive: on a database built fresh from the current set of
     * migration files, these columns were never created in the first
     * place, so there's nothing to drop — this becomes a safe no-op
     * instead of failing.
     */
    public function up(): void
    {
        Schema::table('reports', function (Blueprint $table) {
            $columns = array_filter(
                ['assigned_at', 'resolved_at', 'closed_at'],
                fn ($column) => Schema::hasColumn('reports', $column)
            );

            if (!empty($columns)) {
                $table->dropColumn($columns);
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('reports', function (Blueprint $table) {
            if (!Schema::hasColumn('reports', 'assigned_at')) {
                $table->timestamp('assigned_at')->nullable();
            }
            if (!Schema::hasColumn('reports', 'resolved_at')) {
                $table->timestamp('resolved_at')->nullable();
            }
            if (!Schema::hasColumn('reports', 'closed_at')) {
                $table->timestamp('closed_at')->nullable();
            }
        });
    }
};