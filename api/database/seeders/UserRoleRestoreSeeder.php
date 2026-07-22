<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;

class UserRoleRestoreSeeder extends Seeder
{
    /**
     * Restore user-role assignments from SQL backup.
     * Beberapa user punya multiple roles.
     */
    public function run(): void
    {
        $assignments = [
            // superadmin
            'superadmin' => [1, 5, 11, 60],

            // admin
            'admin' => [2, 6, 7, 8, 9, 10, 12, 13, 60],

            // staff
            'staff' => [3, 6, 7, 8, 13, 15, 17, 18, 19, 20, 21, 22, 23, 24, 25, 26, 27, 29, 30, 58, 61, 64, 65],

            // user
            'user' => [4, 23, 31, 32, 33, 34, 35, 36, 37, 38, 39, 40, 41, 42, 43, 44, 45, 46, 47, 48, 49, 50, 51, 52, 53, 54, 55, 56, 57, 59, 62, 63, 66],
        ];

        // Kumpulkan semua user IDs yang punya assignment
        $allUserIds = collect($assignments)->flatten()->unique()->values()->toArray();

        // Reset dulu role semua user yang ada di list ini
        $users = User::whereIn('id', $allUserIds)->get();

        foreach ($users as $user) {
            // Kumpulkan semua role untuk user ini
            $roles = [];
            foreach ($assignments as $role => $userIds) {
                if (in_array($user->id, $userIds)) {
                    $roles[] = $role;
                }
            }
            $user->syncRoles($roles);
        }

        $this->command->info("Restored roles for {$users->count()} users.");

        // User yang tidak ada di list, pastikan minimal punya role 'user'
        $usersWithoutRole = User::whereNotIn('id', $allUserIds)
            ->doesntHave('roles')
            ->get();

        foreach ($usersWithoutRole as $user) {
            $user->assignRole('user');
        }

        if ($usersWithoutRole->count() > 0) {
            $this->command->info("Assigned 'user' role to {$usersWithoutRole->count()} additional users.");
        }
    }
}
