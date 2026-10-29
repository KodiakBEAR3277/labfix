<?php

namespace Database\Seeders;

use App\Models\Institution;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class InstitutionSeeder extends Seeder
{
    /**
     * Single source of truth for the demo institutions. The slug doubles as
     * the email prefix for each institution's accounts (see UserSeeder), so
     * it is set explicitly rather than derived — Str::slug('S.N.S.U.') would
     * produce 's-n-s-u', not 'snsu'.
     *
     * @var array<int, array<string, string>>
     */
    public const INSTITUTIONS = [
        [
            'name'          => 'Free',
            'slug'          => 'free',
            'plan'          => 'free',
            'contact_email' => 'freeadmin@gmail.com',
            'address'       => 'Free Campus, Main Building',
        ],
        [
            'name'          => 'Standard',
            'slug'          => 'standard',
            'plan'          => 'standard',
            'contact_email' => 'standardadmin@gmail.com',
            'address'       => 'Standard Campus, Main Building',
        ],
        [
            'name'          => 'S.N.S.U.',
            'slug'          => 'snsu',
            'plan'          => 'pro',
            'contact_email' => 'snsuadmin@gmail.com',
            'address'       => 'Surigao del Norte State University, Main Campus',
        ],
        [
            'name'          => 'Enterprise',
            'slug'          => 'enterprise',
            'plan'          => 'enterprise',
            'contact_email' => 'enterpriseadmin@gmail.com',
            'address'       => 'Enterprise Campus, Main Building',
        ],
    ];

    public function run(): void
    {
        foreach (self::INSTITUTIONS as $data) {
            Institution::create($data + ['is_active' => true]);
        }

        // Platform-level superadmin — not tied to any institution. The
        // tickets:auto-close command and the superadmin pages both expect one.
        User::create([
            'institution_id' => null,
            'first_name'     => 'Platform',
            'last_name'      => 'Superadmin',
            'email'          => 'superadmin@gmail.com',
            'role'           => 'superadmin',
            'password'       => Hash::make('password'),
        ]);

        $this->command->info('Institutions and platform superadmin created.');
    }
}
