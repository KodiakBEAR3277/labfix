<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('articles', function (Blueprint $table) {
            $table->foreignId('institution_id')->nullable()->after('id')->constrained()->cascadeOnDelete();

            $table->dropUnique(['slug']);
            $table->unique(['institution_id', 'slug']);
        });
    }

    public function down(): void
    {
        Schema::table('articles', function (Blueprint $table) {
            $table->dropUnique(['institution_id', 'slug']);
            $table->unique('slug');

            $table->dropConstrainedForeignId('institution_id');
        });
    }
};