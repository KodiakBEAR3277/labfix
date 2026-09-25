<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('institution_requests', function (Blueprint $table) {
            $table->id();
            $table->string('institution_name');
            $table->string('institution_contact_email')->nullable();
            $table->string('plan')->nullable();
            $table->string('first_name');
            $table->string('last_name');
            $table->string('email');
            $table->string('password'); // hashed at submission time
            $table->string('status')->default('pending'); // pending | approved | rejected
            $table->timestamp('reviewed_at')->nullable();
            $table->foreignId('reviewed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->text('rejection_reason')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('institution_requests');
    }
};