<?php

namespace Database\Seeders;

use App\Enums\UserType;
use App\Models\Branch;
use App\Models\User;
use Illuminate\Database\Seeder;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Get default branch
        $branch = Branch::first();

        // Create superadmin user
        $superadmin = User::firstOrCreate(
            ['email' => 'superadmin@gmail.com'],
            [
                'name' => 'Super Admin',
                'password' => bcrypt('password'),
                'branch_id' => $branch?->id,
                'position' => 'Super Administrator',
                'identity_number' => 'SA001',
                'phone_number' => '081234567890',
                'type' => UserType::INTERNAL->value,
            ]
        );
        $superadmin->syncRoles(['superadmin']);

        // Create admin user
        $admin = User::firstOrCreate(
            ['email' => 'admin@gmail.com'],
            [
                'name' => 'Admin',
                'password' => bcrypt('password'),
                'branch_id' => $branch?->id,
                'position' => 'Administrator',
                'identity_number' => 'AD001',
                'phone_number' => '081234567891',
                'type' => UserType::INTERNAL->value,
            ]
        );
        $admin->syncRoles(['admin']);

        // Create staff user
        $staff = User::firstOrCreate(
            ['email' => 'staff@gmail.com'],
            [
                'name' => 'Staff',
                'password' => bcrypt('password'),
                'branch_id' => $branch?->id,
                'position' => 'Teknisi',
                'identity_number' => 'ST001',
                'phone_number' => '081234567892',
                'type' => UserType::INTERNAL->value,
            ]
        );
        $staff->syncRoles(['staff']);

        // Create regular user
        $user = User::firstOrCreate(
            ['email' => 'user@gmail.com'],
            [
                'name' => 'User',
                'password' => bcrypt('password'),
                'branch_id' => $branch?->id,
                'position' => 'Karyawan',
                'identity_number' => 'US001',
                'phone_number' => '081234567893',
                'type' => UserType::EXTERNAL->value,
            ]
        );
        $user->syncRoles(['user']);
    }
}
