<?php

namespace Tests\Feature;

use App\Models\Institution;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Inertia\Testing\AssertableInertia;
use Tests\TestCase;

class SuperAdminUserManagementTest extends TestCase
{
    use RefreshDatabase;

    private function institution(string $name = 'Test University', string $plan = 'standard', bool $active = true): Institution
    {
        return Institution::create([
            'name'      => $name,
            'is_active' => $active,
            'plan'      => $plan,
        ]);
    }

    private function superadmin(string $email = 'superadmin@test.edu'): User
    {
        return User::create([
            'institution_id' => null,
            'first_name'     => 'Platform',
            'last_name'      => 'Superadmin',
            'email'          => $email,
            'role'           => 'superadmin',
            'password'       => Hash::make('password123'),
        ]);
    }

    private function user(?Institution $institution, string $role, string $email): User
    {
        return User::create([
            'institution_id' => $institution?->id,
            'first_name'     => 'Test',
            'last_name'      => 'Account',
            'email'          => $email,
            'role'           => $role,
            'password'       => Hash::make('password123'),
        ]);
    }

    /** @return array<string, mixed> */
    private function payload(array $overrides = []): array
    {
        return array_merge([
            'first_name'          => 'Renamed',
            'last_name'           => 'Person',
            'email'               => 'renamed@test.edu',
            'role'                => 'it-support',
            'phone'               => '09171234567',
            'student_staff_id'    => 'EMP-001',
            'is_active'           => true,
            'email_notifications' => true,
            'can_submit_tickets'  => true,
        ], $overrides);
    }

    public function test_superadmin_can_list_users_across_all_institutions(): void
    {
        $alpha = $this->institution('Alpha');
        $beta  = $this->institution('Beta', 'pro');
        $sa    = $this->superadmin();
        $this->user($alpha, 'admin', 'alpha-admin@test.edu');
        $this->user($beta, 'admin', 'beta-admin@test.edu');

        $this->actingAs($sa)
            ->get('/superadmin/users')
            ->assertOk()
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->component('SuperAdmin/Users/Index')
                // superadmin + one admin from each institution
                ->has('users.data', 3)
            );
    }

    public function test_superadmin_can_update_another_users_details_and_role(): void
    {
        $institution = $this->institution();
        $sa   = $this->superadmin();
        $user = $this->user($institution, 'student', 'student@test.edu');

        $this->actingAs($sa)
            ->put("/superadmin/users/{$user->id}", $this->payload(['is_active' => false]))
            ->assertRedirect(route('superadmin.users.index'))
            ->assertSessionHas('success');

        $this->assertDatabaseHas('users', [
            'id'         => $user->id,
            'first_name' => 'Renamed',
            'last_name'  => 'Person',
            'email'      => 'renamed@test.edu',
            'role'       => 'it-support',
            'is_active'  => false,
        ]);
    }

    public function test_superadmin_can_change_their_own_password(): void
    {
        $sa = $this->superadmin();

        $this->actingAs($sa)
            ->put("/superadmin/users/{$sa->id}", [
                'first_name'            => 'Platform',
                'last_name'             => 'Superadmin',
                'email'                 => $sa->email,
                'password'              => 'BrandNewSecret123',
                'password_confirmation' => 'BrandNewSecret123',
            ])
            ->assertRedirect(route('superadmin.users.index'));

        $fresh = User::withoutGlobalScope('institution')->findOrFail($sa->id);
        $this->assertTrue(Hash::check('BrandNewSecret123', $fresh->password));
    }

    public function test_superadmin_cannot_change_their_own_role_or_deactivate_themselves(): void
    {
        $sa = $this->superadmin();

        $this->actingAs($sa)
            ->put("/superadmin/users/{$sa->id}", [
                'first_name' => 'Platform',
                'last_name'  => 'Superadmin',
                'email'      => $sa->email,
                'role'       => 'student',
                'is_active'  => false,
            ])
            ->assertRedirect(route('superadmin.users.index'));

        $fresh = User::withoutGlobalScope('institution')->findOrFail($sa->id);
        $this->assertSame('superadmin', $fresh->role);
        $this->assertTrue($fresh->is_active);
    }

    public function test_duplicate_email_is_rejected(): void
    {
        $institution = $this->institution();
        $sa    = $this->superadmin();
        $this->user($institution, 'student', 'taken@test.edu');
        $other = $this->user($institution, 'staff', 'other@test.edu');

        $this->actingAs($sa)
            ->put("/superadmin/users/{$other->id}", $this->payload(['email' => 'taken@test.edu']))
            ->assertSessionHasErrors('email');

        $this->assertSame('other@test.edu', $other->fresh()->email);
    }

    public function test_non_superadmin_cannot_access_superadmin_users(): void
    {
        $institution = $this->institution();
        $admin = $this->user($institution, 'admin', 'admin@test.edu');

        $this->actingAs($admin)
            ->get('/superadmin/users')
            ->assertForbidden();
    }

    public function test_seeded_superadmin_does_not_use_the_old_default_password(): void
    {
        // Force the random-password path regardless of any local env override.
        config(['labfix.superadmin_password' => null]);

        $this->seed(\Database\Seeders\InstitutionSeeder::class);

        $sa = User::withoutGlobalScope('institution')->where('role', 'superadmin')->firstOrFail();

        $this->assertFalse(Hash::check('password', $sa->password));
    }
}
