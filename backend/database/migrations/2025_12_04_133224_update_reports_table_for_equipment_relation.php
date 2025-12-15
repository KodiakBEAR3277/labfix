<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('reports', function (Blueprint $table) {
            // Drop old columns
            $table->dropColumn(['lab_location', 'equipment_id']);
            
            // Add new foreign key
            $table->foreignId('equipment_id')->after('assigned_to')->constrained('equipment')->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::table('reports', function (Blueprint $table) {
            $table->dropForeign(['equipment_id']);
            $table->dropColumn('equipment_id');
            
            // Restore old columns
            $table->string('lab_location');
            $table->string('equipment_id')->nullable();
        });
    }
};