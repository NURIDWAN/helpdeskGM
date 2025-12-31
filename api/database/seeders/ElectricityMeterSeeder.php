<?php

namespace Database\Seeders;

use App\Models\Branch;
use App\Models\ElectricityMeter;
use Illuminate\Database\Seeder;

class ElectricityMeterSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $branches = Branch::all();

        if ($branches->isEmpty()) {
            $this->command->warn('Tidak ada cabang yang ditemukan. Silakan buat cabang terlebih dahulu.');
            return;
        }

        // Template meters yang akan dibuat untuk setiap cabang
        $meterTemplates = [
            [
                'meter_name' => 'Gardu Utama',
                'meter_number' => null, // Will be generated
                'location' => 'Ruang Panel Utama',
                'power_capacity' => 33000, // 33 kVA
                'is_active' => true,
            ],
            [
                'meter_name' => 'Gardu Depan',
                'meter_number' => null,
                'location' => 'Area Depan Toko',
                'power_capacity' => 16500, // 16.5 kVA
                'is_active' => true,
            ],
            [
                'meter_name' => 'Gardu Belakang',
                'meter_number' => null,
                'location' => 'Area Gudang/Belakang',
                'power_capacity' => 11000, // 11 kVA
                'is_active' => true,
            ],
        ];

        foreach ($branches as $index => $branch) {
            // Jumlah meter per cabang bervariasi (1-3)
            $meterCount = ($index % 3) + 1; // 1, 2, atau 3 meter

            for ($i = 0; $i < $meterCount; $i++) {
                $template = $meterTemplates[$i];

                // Generate meter number untuk cabang ini
                $meterNumber = sprintf('%012d', ($branch->id * 1000) + ($i + 1));

                // Cek apakah meter sudah ada
                $existingMeter = ElectricityMeter::where('branch_id', $branch->id)
                    ->where('meter_name', $template['meter_name'])
                    ->first();

                if (!$existingMeter) {
                    ElectricityMeter::create([
                        'branch_id' => $branch->id,
                        'meter_name' => $template['meter_name'],
                        'meter_number' => $meterNumber,
                        'location' => $template['location'],
                        'power_capacity' => $template['power_capacity'],
                        'is_active' => $template['is_active'],
                    ]);

                    $this->command->info("Meter '{$template['meter_name']}' created for branch: {$branch->name}");
                }
            }
        }

        $this->command->info('Electricity meters seeded successfully!');
    }
}
