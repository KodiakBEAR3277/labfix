<?php

namespace Database\Seeders;

use App\Models\Lab;
use App\Models\Equipment;
use Illuminate\Database\Seeder;

class EquipmentSeeder extends Seeder
{
    public function run(): void
    {
        $labs = Lab::all();

        if ($labs->isEmpty()) {
            $this->command->warn('No labs found. Please run LabSeeder first.');
            return;
        }

        foreach ($labs as $lab) {
            // Create equipment based on lab capacity
            for ($i = 1; $i <= $lab->capacity; $i++) {
                $equipmentCode = 'PC-' . str_pad($i, 2, '0', STR_PAD_LEFT);
                
                // Randomly set some equipment to have issues for testing
                $status = 'operational';
                
                // Lab A: PC-03 and PC-12 have issues
                if ($lab->code === 'LAB-A' && in_array($i, [3, 12])) {
                    $status = 'has-issue';
                }
                
                // Lab B: PC-05 and PC-13 have issues, PC-07 under maintenance
                if ($lab->code === 'LAB-B') {
                    if (in_array($i, [5, 13])) {
                        $status = 'has-issue';
                    } elseif ($i === 7) {
                        $status = 'maintenance';
                    }
                }

                Equipment::create([
                    'lab_id' => $lab->id,
                    'equipment_code' => $equipmentCode,
                    'type' => 'computer',
                    'status' => $status,
                    'notes' => null,
                ]);
            }
        }

        $this->command->info('Equipment created successfully!');
    }
}