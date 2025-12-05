<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // 1. First create users (required by other seeders)
        $this->call(UserSeeder::class);
        
        // 2. Create labs
        $this->call(LabSeeder::class);
        
        // 3. Create equipment (depends on labs)
        $this->call(EquipmentSeeder::class);
        
        // 4. Create articles (depends on users)
        $this->call(ArticleSeeder::class);
        
        // 5. Create reports (depends on users, labs, and equipment)
        $this->call(ReportSeeder::class);
    }
}