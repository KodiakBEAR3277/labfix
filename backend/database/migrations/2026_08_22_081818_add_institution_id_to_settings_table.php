<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('settings', function (Blueprint $table) {
            $table->foreignId('institution_id')->nullable()->after('id')->constrained()->cascadeOnDelete();

            $table->dropUnique(['key']);
            $table->unique(['institution_id', 'key']);
        });
    }

    public function down(): void
    {
        Schema::table('settings', function (Blueprint $table) {
            $table->dropUnique(['institution_id', 'key']);
            $table->unique('key');

            $table->dropConstrainedForeignId('institution_id');
        });
    }
};