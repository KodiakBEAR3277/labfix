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
        $this->widenRoleColumn(['student', 'staff', 'it-support', 'admin', 'superadmin']);

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

        $this->widenRoleColumn(['student', 'staff', 'it-support', 'admin']);
    }

    /**
     * Widen the role enum. MySQL/TiDB (production) needs the explicit
     * MODIFY COLUMN; other drivers rebuild the column. SQLite backs an enum
     * with a CHECK constraint, so a plain skip would leave 'superadmin'
     * rejected — the column has to be recreated with the wider value set.
     */
    private function widenRoleColumn(array $values): void
    {
        if (DB::getDriverName() === 'mysql') {
            $list = "'" . implode("', '", $values) . "'";
            DB::statement("ALTER TABLE users MODIFY COLUMN role ENUM({$list}) NOT NULL DEFAULT 'student'");

            return;
        }

        Schema::table('users', function (Blueprint $table) use ($values) {
            $table->enum('role', $values)->default('student')->change();
        });
    }
};