<?php

namespace Tests\Feature;

use App\Models\Institution;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

/**
 * The auto-escalate and auto-close rules are a Standard+ feature
 * (Institution::hasAutoRules()). Both scheduled commands must skip
 * institutions whose plan does not include auto rules, so a Free-plan
 * institution never silently gets them from its seeded default settings.
 */
class AutoRulesPlanGateTest extends TestCase
{
    use RefreshDatabase;

    private function institution(string $name, string $plan): Institution
    {
        return Institution::create([
            'name'      => $name,
            'is_active' => true,
            'plan'      => $plan,
        ]);
    }

    private function superadmin(): User
    {
        return User::create([
            'first_name' => 'System',
            'last_name'  => 'Admin',
            'email'      => 'superadmin@test.edu',
            'role'       => 'superadmin',
            'password'   => Hash::make('password'),
        ]);
    }

    public function test_auto_escalate_skips_institutions_without_auto_rules(): void
    {
        $this->institution('Free Uni', 'free');
        $this->institution('Std Uni', 'standard');

        Artisan::call('tickets:auto-escalate');
        $output = Artisan::output();

        // Standard+ institution is processed …
        $this->assertStringContainsString('Std Uni', $output);
        // … the Free-plan institution is skipped entirely.
        $this->assertStringNotContainsString('Free Uni', $output);
    }

    public function test_auto_close_skips_institutions_without_auto_rules(): void
    {
        $this->superadmin();

        $this->institution('Free Uni', 'free');
        $this->institution('Std Uni', 'standard');

        Artisan::call('tickets:auto-close');
        $output = Artisan::output();

        $this->assertStringContainsString('Std Uni', $output);
        $this->assertStringNotContainsString('Free Uni', $output);
    }
}
