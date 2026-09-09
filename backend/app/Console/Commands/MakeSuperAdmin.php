<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rules\Password;

class MakeSuperAdmin extends Command
{
    protected $signature = 'make:superadmin';

    protected $description = 'Create a platform-level superadmin account, not tied to any single institution';

    public function handle()
    {
        if (User::withoutGlobalScope('institution')->where('role', 'superadmin')->exists()) {
            if (!$this->confirm('A superadmin account already exists. Create another one anyway?')) {
                return 0;
            }
        }

        $firstName = $this->ask('First name');
        $lastName  = $this->ask('Last name');
        $email     = $this->ask('Email');
        $password  = $this->secret('Password');

        $validator = Validator::make(
            compact('firstName', 'lastName', 'email', 'password'),
            [
                'firstName' => ['required', 'string', 'max:255'],
                'lastName'  => ['required', 'string', 'max:255'],
                'email'     => ['required', 'string', 'email', 'max:255', 'unique:users,email'],
                'password'  => ['required', Password::defaults()],
            ]
        );

        if ($validator->fails()) {
            foreach ($validator->errors()->all() as $error) {
                $this->error($error);
            }
            return 1;
        }

        User::create([
            'institution_id' => null,
            'first_name'     => $firstName,
            'last_name'      => $lastName,
            'email'          => $email,
            'role'           => 'superadmin',
            'password'       => Hash::make($password),
        ]);

        $this->info("Superadmin account created for {$email}.");

        return 0;
    }
}