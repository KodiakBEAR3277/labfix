<?php

namespace Tests\Feature;

use App\Models\Institution;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Inertia\Testing\AssertableInertia;
use Tests\TestCase;

class AdminUserCreationTest extends TestCase
{
    use RefreshDatabase;

    private function institution(string $plan = 'standard', bool $active = true): Institution
    {
        return Institution::create([
            'name'    => 'Test University',
            'is_active' => $active,
            'plan'    => $plan,
        ]);
    }

    private function user(Institution $institution, string $role, string $email): User
    {
        return User::create([
            'institution_id' => $institution->id,
            'first_name'     => 'Test',
            'last_name'      => ucfirst($role),
            'email'          => $email,
            'role'           => $role,
            'password'       => Hash::make('password123'),
        ]);
    }

    /** @return array<string, mixed> */
    private function validPayload(array $overrides = []): array
    {
        return array_merge([
            'first_name'       => 'Jane',
            'last_name'        => 'Doe',
            'email'            => 'jane@test.edu',
            'role'             => 'it-support',
            'password'         => 'SuperSecret123',
            'phone'            => '09171234567',
            'student_staff_id' => 'EMP-001',
        ], $overrides);
    }

    public function test_admin_can_view_the_users_index(): void
    {
        $institution = $this->institution();
        $admin = $this->user($institution, 'admin', 'admin@test.edu');

        $this->actingAs($admin)
            ->get('/admin/users')
            ->assertOk()
            ->assertInertia(fn (AssertableInertia $page) => $page->component('Admin/Users/Index'));
    }

    public function test_admin_can_view_the_create_form(): void
    {
        $institution = $this->institution();
        $admin = $this->user($institution, 'admin', 'admin@test.edu');

        $this->actingAs($admin)
            ->get('/admin/users/create')
            ->assertOk()
            ->assertInertia(fn (AssertableInertia $page) => $page->component('Admin/Users/Create'));
    }

    public function test_admin_can_create_a_user(): void
    {
        $institution = $this->institution();
        $admin = $this->user($institution, 'admin', 'admin@test.edu');

        $response = $this->actingAs($admin)->post('/admin/users', $this->validPayload());

        $response->assertRedirect(route('admin.users.index'));
        $response->assertSessionHas('success');

        // institution_id is not in the payload — it must be stamped by the
        // BelongsToInstitution trait from the acting admin.
        $this->assertDatabaseHas('users', [
            'email'            => 'jane@test.edu',
            'role'             => 'it-support',
            'institution_id'   => $institution->id,
            'phone'            => '09171234567',
            'student_staff_id' => 'EMP-001',
        ]);

        $created = User::where('email', 'jane@test.edu')->firstOrFail();
        $this->assertNotSame('SuperSecret123', $created->password);
        $this->assertTrue(Hash::check('SuperSecret123', $created->password));
    }

    public function test_created_user_is_scoped_to_the_admins_institution(): void
    {
        $institutionA = $this->institution();
        $institutionB = $this->institution('pro');
        $admin = $this->user($institutionA, 'admin', 'admin@test.edu');
        $this->user($institutionB, 'admin', 'other-admin@test.edu');

        $this->actingAs($admin)
            ->post('/admin/users', $this->validPayload())
            ->assertRedirect(route('admin.users.index'));

        $this->assertDatabaseHas('users', [
            'email'          => 'jane@test.edu',
            'institution_id' => $institutionA->id,
        ]);
        $this->assertDatabaseMissing('users', [
            'email'          => 'jane@test.edu',
            'institution_id' => $institutionB->id,
        ]);
    }

    public function test_duplicate_email_is_rejected(): void
    {
        $institution = $this->institution();
        $admin = $this->user($institution, 'admin', 'admin@test.edu');
        $this->user($institution, 'student', 'jane@test.edu');

        $this->actingAs($admin)
            ->post('/admin/users', $this->validPayload())
            ->assertSessionHasErrors('email');

        $this->assertSame(1, User::where('email', 'jane@test.edu')->count());
    }

    public function test_invalid_role_is_rejected(): void
    {
        $institution = $this->institution();
        $admin = $this->user($institution, 'admin', 'admin@test.edu');

        $this->actingAs($admin)
            ->post('/admin/users', $this->validPayload(['role' => 'superuser']))
            ->assertSessionHasErrors('role');

        $this->assertDatabaseMissing('users', ['email' => 'jane@test.edu']);
    }

    public function test_short_password_is_rejected(): void
    {
        $institution = $this->institution();
        $admin = $this->user($institution, 'admin', 'admin@test.edu');

        $this->actingAs($admin)
            ->post('/admin/users', $this->validPayload(['password' => 'short']))
            ->assertSessionHasErrors('password');

        $this->assertDatabaseMissing('users', ['email' => 'jane@test.edu']);
    }

    public function test_non_admin_cannot_create_users(): void
    {
        $institution = $this->institution();
        $student = $this->user($institution, 'student', 'student@test.edu');

        $this->actingAs($student)
            ->post('/admin/users', $this->validPayload())
            ->assertForbidden();

        $this->assertDatabaseMissing('users', ['email' => 'jane@test.edu']);
    }

    public function test_user_limit_blocks_creation_on_free_plan(): void
    {
        $institution = $this->institution('free'); // max_users = 50
        $admin = $this->user($institution, 'admin', 'admin@test.edu');

        for ($i = 0; $i < 49; $i++) {
            $this->user($institution, 'student', "bulk{$i}@test.edu");
        }

        $this->assertSame(50, $institution->users()->count());
        $this->assertFalse($institution->fresh()->canAddUser());

        $response = $this->actingAs($admin)->post('/admin/users', $this->validPayload());

        $response->assertRedirect(route('admin.users.index'));
        $response->assertSessionHas('error');
        $this->assertDatabaseMissing('users', ['email' => 'jane@test.edu']);
    }
}
