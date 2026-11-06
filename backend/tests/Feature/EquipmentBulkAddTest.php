<?php

namespace Tests\Feature;

use App\Models\Equipment;
use App\Models\Institution;
use App\Models\Lab;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class EquipmentBulkAddTest extends TestCase
{
    use RefreshDatabase;

    private function institution(string $plan = 'standard'): Institution
    {
        return Institution::create([
            'name'      => 'Test University',
            'is_active' => true,
            'plan'      => $plan,
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

    private function lab(Institution $institution, string $code = 'LAB-A'): Lab
    {
        return Lab::create([
            'institution_id' => $institution->id,
            'name'           => 'Computer Lab A',
            'code'           => $code,
            'capacity'       => 30,
            'is_active'      => true,
        ]);
    }

    private function addEquipment(Lab $lab, string $code): Equipment
    {
        return Equipment::create([
            'institution_id' => $lab->institution_id,
            'lab_id'         => $lab->id,
            'equipment_code' => $code,
            'type'           => 'computer',
            'status'         => 'operational',
        ]);
    }

    public function test_admin_can_bulk_add_equipment(): void
    {
        $institution = $this->institution();
        $admin = $this->user($institution, 'admin', 'admin@test.edu');
        $lab = $this->lab($institution);

        $response = $this->actingAs($admin)->post("/admin/labs/{$lab->id}/equipment/bulk", [
            'codes'  => "PC-01\nPC-02, PC-03",
            'type'   => 'computer',
            'status' => 'operational',
        ]);

        $response->assertRedirect(route('admin.labs.index'));
        $response->assertSessionHas('success');

        foreach (['PC-01', 'PC-02', 'PC-03'] as $code) {
            $this->assertDatabaseHas('equipment', [
                'lab_id'         => $lab->id,
                'equipment_code' => $code,
                'institution_id' => $institution->id,
            ]);
        }

        $created = Equipment::where('equipment_code', 'PC-01')->firstOrFail();
        $this->assertNotNull($created->qr_token);
        $this->assertSame("LABFIX-EQ:{$created->qr_token}", $created->qr_payload);
    }

    public function test_existing_and_repeated_codes_are_skipped(): void
    {
        $institution = $this->institution();
        $admin = $this->user($institution, 'admin', 'admin@test.edu');
        $lab = $this->lab($institution);
        $this->addEquipment($lab, 'PC-01');

        $response = $this->actingAs($admin)->post("/admin/labs/{$lab->id}/equipment/bulk", [
            'codes'  => "PC-01\nPC-02, pc-02\nPC-03",
            'type'   => 'computer',
            'status' => 'operational',
        ]);

        $response->assertRedirect(route('admin.labs.index'));
        $response->assertSessionHas('success');

        $this->assertSame(3, Equipment::where('lab_id', $lab->id)->count());
        $this->assertDatabaseHas('equipment', ['lab_id' => $lab->id, 'equipment_code' => 'PC-02']);
        $this->assertDatabaseHas('equipment', ['lab_id' => $lab->id, 'equipment_code' => 'PC-03']);
    }

    public function test_plan_limit_blocks_the_whole_batch(): void
    {
        $institution = $this->institution('free'); // max_equipment = 30
        $admin = $this->user($institution, 'admin', 'admin@test.edu');
        $lab = $this->lab($institution);

        for ($i = 1; $i <= 28; $i++) {
            $this->addEquipment($lab, 'PC-' . str_pad((string) $i, 2, '0', STR_PAD_LEFT));
        }

        $response = $this->actingAs($admin)->post("/admin/labs/{$lab->id}/equipment/bulk", [
            'codes'  => "PC-29\nPC-30\nPC-31",
            'type'   => 'computer',
            'status' => 'operational',
        ]);

        $response->assertRedirect(route('admin.labs.index'));
        $response->assertSessionHas('error');

        $this->assertSame(28, Equipment::where('lab_id', $lab->id)->count());
        $this->assertDatabaseMissing('equipment', ['lab_id' => $lab->id, 'equipment_code' => 'PC-29']);
    }

    public function test_non_admin_cannot_bulk_add_equipment(): void
    {
        $institution = $this->institution();
        $student = $this->user($institution, 'student', 'student@test.edu');
        $lab = $this->lab($institution);

        $this->actingAs($student)
            ->post("/admin/labs/{$lab->id}/equipment/bulk", [
                'codes'  => 'PC-01',
                'type'   => 'computer',
                'status' => 'operational',
            ])
            ->assertForbidden();

        $this->assertSame(0, Equipment::where('lab_id', $lab->id)->count());
    }

    public function test_cannot_bulk_add_to_another_institutions_lab(): void
    {
        $institutionA = $this->institution();
        $institutionB = $this->institution('pro');
        $admin = $this->user($institutionA, 'admin', 'admin@test.edu');
        $labB = $this->lab($institutionB, 'LAB-B');

        $this->actingAs($admin)
            ->post("/admin/labs/{$labB->id}/equipment/bulk", [
                'codes'  => 'PC-01',
                'type'   => 'computer',
                'status' => 'operational',
            ])
            ->assertNotFound();

        $this->assertDatabaseMissing('equipment', ['lab_id' => $labB->id, 'equipment_code' => 'PC-01']);
    }
}
