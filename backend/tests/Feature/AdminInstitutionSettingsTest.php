<?php

namespace Tests\Feature;

use App\Models\Institution;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Inertia\Testing\AssertableInertia;
use Tests\TestCase;

class AdminInstitutionSettingsTest extends TestCase
{
    use RefreshDatabase;

    private function institution(string $name = 'Test University'): Institution
    {
        return Institution::create([
            'name'      => $name,
            'is_active' => true,
            'plan'      => 'standard',
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

    public function test_settings_page_exposes_the_institution(): void
    {
        $institution = $this->institution();
        $admin = $this->user($institution, 'admin', 'admin@test.edu');

        $this->actingAs($admin)
            ->get('/admin/settings')
            ->assertOk()
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->component('Admin/Settings')
                ->where('institution.name', 'Test University'));
    }

    public function test_admin_can_rename_their_institution(): void
    {
        $institution = $this->institution();
        $admin = $this->user($institution, 'admin', 'admin@test.edu');

        $response = $this->actingAs($admin)->put('/admin/settings/institution', [
            'name'          => 'Renamed University',
            'contact_email' => 'it@renamed.edu',
            'address'       => 'Main Campus, Building A',
        ]);

        $response->assertRedirect(route('admin.settings'));
        $response->assertSessionHas('success');

        $this->assertDatabaseHas('institutions', [
            'id'            => $institution->id,
            'name'          => 'Renamed University',
            'contact_email' => 'it@renamed.edu',
            'address'       => 'Main Campus, Building A',
        ]);
    }

    public function test_admin_can_only_change_their_own_institution(): void
    {
        $mine = $this->institution('Mine');
        $other = $this->institution('Other');
        $admin = $this->user($mine, 'admin', 'admin@test.edu');

        $this->actingAs($admin)
            ->put('/admin/settings/institution', ['name' => 'Changed'])
            ->assertRedirect(route('admin.settings'));

        $this->assertSame('Changed', $mine->fresh()->name);
        $this->assertSame('Other', $other->fresh()->name);
    }

    public function test_name_is_required(): void
    {
        $institution = $this->institution();
        $admin = $this->user($institution, 'admin', 'admin@test.edu');

        $this->actingAs($admin)
            ->put('/admin/settings/institution', ['name' => ''])
            ->assertSessionHasErrors('name');

        $this->assertSame('Test University', $institution->fresh()->name);
    }

    public function test_non_admin_cannot_update_the_institution(): void
    {
        $institution = $this->institution();
        $student = $this->user($institution, 'student', 'student@test.edu');

        $this->actingAs($student)
            ->put('/admin/settings/institution', ['name' => 'Nope'])
            ->assertForbidden();

        $this->assertSame('Test University', $institution->fresh()->name);
    }
}
