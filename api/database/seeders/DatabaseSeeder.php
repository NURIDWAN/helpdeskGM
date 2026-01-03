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
        $this->call([
            PermissionSeeder::class,
            RoleSeeder::class,
            RolePermissionSeeder::class,
            UserSeeder::class,
            WhatsAppTemplateSeeder::class,
            TicketCategorySeeder::class, // Add ticket categories
            DummyDataSeeder::class,
            ElectricityMeterSeeder::class,
            DailyRecordSeeder::class,
        ]);
    }
}
