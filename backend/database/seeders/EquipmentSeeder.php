<?php

namespace Database\Seeders;

use App\Models\Equipment;
use App\Models\Institution;
use App\Models\Lab;
use Illuminate\Database\Seeder;

class EquipmentSeeder extends Seeder
{
    public function run(): void
    {
        foreach (Institution::orderBy('id')->get() as $institution) {
            app()->instance('currentInstitutionId', $institution->id);

            $labs = Lab::all();

            if ($labs->isEmpty()) {
                app()->forgetInstance('currentInstitutionId');
                continue;
            }

            foreach ($labs as $lab) {
                // Create equipment based on lab capacity
                for ($i = 1; $i <= $lab->capacity; $i++) {
                    $equipmentCode = 'PC-' . str_pad($i, 2, '0', STR_PAD_LEFT);

                    // A few devices have issues, deterministically, so the
                    // demo has something to triage on every institution.
                    $status = 'operational';
                    if (in_array($i, [3, 12])) {
                        $status = 'has-issue';
                    } elseif ($i === 7) {
                        $status = 'maintenance';
                    }

                    Equipment::create([
                        'lab_id'         => $lab->id,
                        'equipment_code' => $equipmentCode,
                        'type'           => 'computer',
                        'status'         => $status,
                        'notes'          => null,
                    ]);
                }
            }

            app()->forgetInstance('currentInstitutionId');
        }

        $this->command->info('Equipment created for every institution.');
    }
}
