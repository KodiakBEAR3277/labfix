<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Widen the enum first, so 'superadmin' is a valid value to move
        // existing flagged accounts into.
        DB::statement("ALTER TABLE users MODIFY COLUMN role ENUM('student', 'staff', 'it-support', 'admin', 'superadmin') NOT NULL DEFAULT 'student'");

        // Any account already flagged is_superadmin carries the role itself
        // now, rather than living as a special case layered on top of 'admin'.
        DB::table('users')->where('is_superadmin', true)->update(['role' => 'superadmin']);

        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('is_superadmin');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->boolean('is_superadmin')->default(false)->after('role');
        });

        DB::table('users')->where('role', 'superadmin')->update([
            'role' => 'admin',
            'is_superadmin' => true,
        ]);

        DB::statement("ALTER TABLE users MODIFY COLUMN role ENUM('student', 'staff', 'it-support', 'admin') NOT NULL DEFAULT 'student'");
    }
};