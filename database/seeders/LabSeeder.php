<?php

namespace Database\Seeders;

use App\Models\Lab;
use Illuminate\Database\Seeder;

class LabSeeder extends Seeder
{
    public function run(): void
    {
        $labs = [
            [
                'name' => 'Computer Lab A',
                'code' => 'EB-202',
                'location' => 'Building 1, 2nd Floor',
                'capacity' => 20,
                'description' => 'General purpose computer laboratory with standard desktop computers',
                'is_active' => true,
            ],
            [
                'name' => 'Computer Lab B',
                'code' => 'EB-203',
                'location' => 'Building 2, 1st Floor',
                'capacity' => 25,
                'description' => 'Large computer laboratory for programming courses',
                'is_active' => true,
            ],
            [
                'name' => 'Computer Lab C',
                'code' => 'EB-204',
                'location' => 'Building 1, 3rd Floor',
                'capacity' => 20,
                'description' => 'Computer laboratory with high-performance workstations',
                'is_active' => true,
            ],
            [
                'name' => 'Networking Lab',
                'code' => 'EB-311',
                'location' => 'Building 3, 3rd Floor',
                'capacity' => 15,
                'description' => 'Specialized lab for multimedia and design work',
                'is_active' => true,
            ],
            [
                'name' => 'Programming Lab',
                'code' => 'EB-306',
                'location' => 'Building 2, 3rd Floor',
                'capacity' => 18,
                'description' => 'Advanced programming laboratory with development tools',
                'is_active' => true,
            ],
            [
                'name' => 'Library Computer Area',
                'code' => 'LRC',
                'location' => 'Library, 2nd Floor',
                'capacity' => 12,
                'description' => 'Public access computers in the library',
                'is_active' => true,
            ],
        ];

        foreach ($labs as $labData) {
            Lab::create($labData);
        }

        $this->command->info('Labs created successfully!');
    }
}