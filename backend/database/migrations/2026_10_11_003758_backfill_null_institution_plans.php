<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    // Institutions created before plans were enforced have a null plan.
    // They've been behaving as Free via the model's fallback; this just
    // makes the stored value say so, so the superadmin list is accurate.
    public function up(): void
    {
        DB::table('institutions')->whereNull('plan')->update(['plan' => 'free']);
    }

    public function down(): void
    {
        // Intentionally empty — we can't know which rows were originally null.
    }
};