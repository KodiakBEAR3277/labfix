<?php

namespace Tests\Feature;

use App\Models\Institution;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class SuperAdminInstitutionCrudTest extends TestCase
{
    use RefreshDatabase;

    private function superadmin(): User
    {
        return User::create([
            'institution_id' => null,
            'first_name'     => 'Super',
            'last_name'      => 'Admin',
            'email'          => 'super@test.edu',
            'role'           => 'superadmin',
            'password'       => Hash::make('password123'),
        ]);
    }

    /** @return array<string, mixed> */
    private function payload(array $overrides = []): array
    {
        return array_merge([
            'name'                       => 'New University',
            'contact_email'              => 'it@new.edu',
            'address'                    => 'Main Campus',
            'plan'                       => 'standard',
            'is_active'                  => true,
            'admin_first_name'           => 'New',
            'admin_last_name'            => 'Admin',
            'admin_email'                => 'new-admin@test.edu',
            'admin_password'             => 'SuperSecret123',
            'admin_password_confirmation' => 'SuperSecret123',
        ], $overrides);
    }

    public function test_superadmin_can_create_an_institution_with_its_first_admin(): void
    {
        $superadmin = $this->superadmin();

        $response = $this->actingAs($superadmin)->post('/superadmin/institutions', $this->payload());

        $response->assertRedirect(route('superadmin.institutions.index'));
        $response->assertSessionHas('success');

        $institution = Institution::where('name', 'New University')->firstOrFail();
        $this->assertSame('standard', $institution->plan);

        $admin = User::where('email', 'new-admin@test.edu')->firstOrFail();
        $this->assertSame('admin', $admin->role);
        $this->assertSame($institution->id, $admin->institution_id);
        $this->assertTrue(Hash::check('SuperSecret123', $admin->password));

        // The institution must be usable out of the box.
        $this->assertSame(6, DB::table('settings')->where('institution_id', $institution->id)->count());
    }

    public function test_admin_email_must_be_unique(): void
    {
        $superadmin = $this->superadmin();

        User::create([
            'institution_id' => null,
            'first_name'     => 'Existing',
            'last_name'      => 'Admin',
            'email'          => 'new-admin@test.edu',
            'role'           => 'admin',
            'password'       => Hash::make('password123'),
        ]);

        $this->actingAs($superadmin)
            ->post('/superadmin/institutions', $this->payload())
            ->assertSessionHasErrors('admin_email');

        $this->assertDatabaseMissing('institutions', ['name' => 'New University']);
    }

    public function test_superadmin_can_update_an_institution(): void
    {
        $superadmin = $this->superadmin();
        $institution = Institution::create([
            'name'      => 'Old University',
            'plan'      => 'free',
            'is_active' => true,
        ]);

        $response = $this->actingAs($superadmin)->put("/superadmin/institutions/{$institution->id}", [
            'name'          => 'Renamed University',
            'contact_email' => 'new@test.edu',
            'address'       => 'New Address',
            'plan'          => 'pro',
            'is_active'     => false,
        ]);

        $response->assertRedirect(route('superadmin.institutions.index'));
        $response->assertSessionHas('success');

        $this->assertDatabaseHas('institutions', [
            'id'            => $institution->id,
            'name'          => 'Renamed University',
            'contact_email' => 'new@test.edu',
            'address'       => 'New Address',
            'plan'          => 'pro',
            'is_active'     => false,
        ]);
    }

    public function test_superadmin_can_delete_an_empty_institution(): void
    {
        $superadmin = $this->superadmin();
        $institution = Institution::create([
            'name'      => 'Empty University',
            'plan'      => 'free',
            'is_active' => true,
        ]);

        $this->actingAs($superadmin)
            ->delete("/superadmin/institutions/{$institution->id}")
            ->assertRedirect(route('superadmin.institutions.index'));

        $this->assertDatabaseMissing('institutions', ['id' => $institution->id]);
    }

    public function test_delete_is_blocked_for_an_institution_with_users(): void
    {
        $superadmin = $this->superadmin();
        $institution = Institution::create([
            'name'      => 'Live University',
            'plan'      => 'free',
            'is_active' => true,
        ]);

        User::create([
            'institution_id' => $institution->id,
            'first_name'     => 'Live',
            'last_name'      => 'Admin',
            'email'          => 'live-admin@test.edu',
            'role'           => 'admin',
            'password'       => Hash::make('password123'),
        ]);

        $response = $this->actingAs($superadmin)
            ->delete("/superadmin/institutions/{$institution->id}");

        $response->assertRedirect(route('superadmin.institutions.index'));
        $response->assertSessionHas('error');

        $this->assertDatabaseHas('institutions', ['id' => $institution->id]);
    }

    public function test_non_superadmin_cannot_manage_institutions(): void
    {
        $institution = Institution::create([
            'name'      => 'Tenant University',
            'plan'      => 'free',
            'is_active' => true,
        ]);
        $admin = User::create([
            'institution_id' => $institution->id,
            'first_name'     => 'Tenant',
            'last_name'      => 'Admin',
            'email'          => 'tenant-admin@test.edu',
            'role'           => 'admin',
            'password'       => Hash::make('password123'),
        ]);

        $this->actingAs($admin)
            ->post('/superadmin/institutions', $this->payload())
            ->assertForbidden();

        $this->actingAs($admin)
            ->put("/superadmin/institutions/{$institution->id}", ['name' => 'Nope'])
            ->assertForbidden();

        $this->actingAs($admin)
            ->delete("/superadmin/institutions/{$institution->id}")
            ->assertForbidden();

        $this->assertSame('Tenant University', $institution->fresh()->name);
    }
}
