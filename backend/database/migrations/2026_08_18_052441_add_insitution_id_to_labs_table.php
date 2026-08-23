<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('labs', function (Blueprint $table) {
            $table->foreignId('institution_id')->nullable()->after('id')->constrained()->cascadeOnDelete();

            $table->dropUnique(['code']);
            $table->unique(['institution_id', 'code']);
        });
    }

    public function down(): void
    {
        Schema::table('labs', function (Blueprint $table) {
            $table->dropUnique(['institution_id', 'code']);
            $table->unique('code');

            $table->dropConstrainedForeignId('institution_id');
        });
    }
};