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
        // 1. Institutions + the platform superadmin (everything else hangs off
        //    these). UserSeeder then creates each institution's 4 accounts.
        $this->call(InstitutionSeeder::class);
        $this->call(UserSeeder::class);

        // 2. Labs and their equipment (depends on institutions)
        $this->call(LabSeeder::class);
        $this->call(EquipmentSeeder::class);

        // 3. KB articles (depends on users)
        $this->call(ArticleSeeder::class);

        // 4. Reports (depends on users, labs, and equipment)
        $this->call(ReportSeeder::class);

        // 5. Settings — platform keys + each institution's defaults
        $this->call(SettingsSeeder::class);
    }
}
