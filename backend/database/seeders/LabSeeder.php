<?php

namespace Database\Seeders;

use App\Models\Institution;
use App\Models\Lab;
use Illuminate\Database\Seeder;

class LabSeeder extends Seeder
{
    /**
     * Labs per institution slug. Counts stay within each plan's max_labs
     * (Free = 1, Standard = 5, Pro/Enterprise = unlimited).
     *
     * @var array<string, array<int, array<string, mixed>>>
     */
    private const LABS = [
        'free' => [
            ['name' => 'Computer Lab A', 'code' => 'LAB-A', 'location' => 'Main Building, 1st Floor', 'capacity' => 20, 'description' => 'General purpose computer laboratory'],
        ],
        'standard' => [
            ['name' => 'Computer Lab A', 'code' => 'LAB-A', 'location' => 'Main Building, 1st Floor', 'capacity' => 20, 'description' => 'General purpose computer laboratory'],
            ['name' => 'Computer Lab B', 'code' => 'LAB-B', 'location' => 'Main Building, 2nd Floor', 'capacity' => 25, 'description' => 'Large computer laboratory for programming courses'],
        ],
        'snsu' => [
            ['name' => 'Computer Lab A', 'code' => 'LAB-A', 'location' => 'IT Building, 1st Floor', 'capacity' => 20, 'description' => 'General purpose computer laboratory'],
            ['name' => 'Computer Lab B', 'code' => 'LAB-B', 'location' => 'IT Building, 2nd Floor', 'capacity' => 20, 'description' => 'Programming laboratory with development tools'],
            ['name' => 'Networking Lab', 'code' => 'LAB-C', 'location' => 'IT Building, 3rd Floor', 'capacity' => 24, 'description' => 'Specialized networking laboratory'],
        ],
        'enterprise' => [
            ['name' => 'Computer Lab A', 'code' => 'LAB-A', 'location' => 'Campus North, 1st Floor', 'capacity' => 20, 'description' => 'General purpose computer laboratory'],
            ['name' => 'Programming Lab', 'code' => 'LAB-B', 'location' => 'Campus North, 2nd Floor', 'capacity' => 24, 'description' => 'Advanced programming laboratory'],
            ['name' => 'Library Computer Area', 'code' => 'LAB-C', 'location' => 'Library, 2nd Floor', 'capacity' => 28, 'description' => 'Public access computers in the library'],
        ],
    ];

    public function run(): void
    {
        foreach (Institution::orderBy('id')->get() as $institution) {
            app()->instance('currentInstitutionId', $institution->id);

            foreach (self::LABS[$institution->slug] ?? [] as $lab) {
                Lab::create($lab + ['is_active' => true]);
            }

            app()->forgetInstance('currentInstitutionId');
        }

        $this->command->info('Labs created for every institution.');
    }
}
