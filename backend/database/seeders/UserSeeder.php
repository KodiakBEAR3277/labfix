<?php

namespace Database\Seeders;

use App\Models\Institution;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    /**
     * Role => email suffix. Students read as "...user" so an account is
     * <institution>@gmail.com style, e.g. standarduser@gmail.com.
     *
     * @var array<string, string>
     */
    private const ROLE_SUFFIX = [
        'admin'      => 'admin',
        'it-support' => 'it',
        'staff'      => 'staff',
        'student'    => 'user',
    ];

    /**
     * @var array<string, array{0: string, 1: string}>
     */
    private const PEOPLE = [
        'admin'      => ['Ana', 'Reyes'],
        'it-support' => ['Ivan', 'Torres'],
        'staff'      => ['Sara', 'Cruz'],
        'student'    => ['Juan', 'Dela Cruz'],
    ];

    public function run(): void
    {
        foreach (Institution::orderBy('id')->get() as $institution) {
            foreach (self::ROLE_SUFFIX as $role => $suffix) {
                [$firstName, $lastName] = self::PEOPLE[$role];

                User::create([
                    'institution_id' => $institution->id,
                    'first_name'     => $firstName,
                    'last_name'      => $lastName,
                    'email'          => $institution->slug . $suffix . '@gmail.com',
                    'role'           => $role,
                    'password'       => Hash::make('password'),
                ]);
            }
        }

        $this->command->info('Institution accounts created.');
    }
}
