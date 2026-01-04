<?php

namespace Database\Seeders;

use App\Models\Branch;
use Illuminate\Database\Seeder;

class BranchSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        Branch::firstOrCreate(
            ['code' => 'HDQT'],
            [
                'name' => 'Kantor Pusat',
                'address' => 'Jl. Jenderal Sudirman No. 1, Jakarta Pusat',
            ]
        );
    }
}
