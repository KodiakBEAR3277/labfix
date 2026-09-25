<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('equipment', function (Blueprint $table) {
            $table->string('brand')->nullable()->after('type');
            $table->string('model')->nullable()->after('brand');
            $table->text('specifications')->nullable()->after('model');
            $table->string('serial_number')->nullable()->after('specifications');
            $table->date('purchase_date')->nullable()->after('serial_number');
            $table->date('warranty_expiry')->nullable()->after('purchase_date');

            $table->index('serial_number');
        });
    }

    public function down(): void
    {
        Schema::table('equipment', function (Blueprint $table) {
            $table->dropIndex(['serial_number']);
            $table->dropColumn([
                'brand',
                'model',
                'specifications',
                'serial_number',
                'purchase_date',
                'warranty_expiry',
            ]);
        });
    }
};