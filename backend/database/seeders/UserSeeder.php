<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        // Create admin
        User::create([
            'first_name' => 'Admin',
            'last_name' => 'User',
            'email' => 'admin@labfix.com',
            'password' => Hash::make('password'),
            'role' => 'admin',
        ]);

        // Create IT support
        User::create([
            'first_name' => 'IT',
            'last_name' => 'Support',
            'email' => 'it@labfix.com',
            'password' => Hash::make('password'),
            'role' => 'it-support',
        ]);

        // Create regular user
        User::create([
            'first_name' => 'John',
            'last_name' => 'Doe',
            'email' => 'user@labfix.com',
            'password' => Hash::make('password'),
            'role' => 'student',
        ]);
    }
}