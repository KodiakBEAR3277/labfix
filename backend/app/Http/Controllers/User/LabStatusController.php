<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use App\Models\Lab;
use Inertia\Inertia;

class LabStatusController extends Controller
{
    public function index()
    {
        // We replicate the logic from your Blade file here
        $labs = Lab::where('is_active', true)
            ->with('equipment')
            ->get()
            ->map(fn($lab) => [
                'id'       => $lab->id,
                'name'     => $lab->name,
                'code'     => $lab->code,
                'location' => $lab->location,
                'capacity' => $lab->capacity,
                'equipment' => $lab->equipment->map(fn($eq) => [
                    'id'             => $eq->id,
                    'equipment_code' => $eq->equipment_code,
                    'type'           => $eq->type,
                    'status'         => $eq->status,
                    'notes'          => $eq->notes,
                ]),
            ]);

        return Inertia::render('User/LabStatus', [
            'labs' => $labs
        ]);
    }
}