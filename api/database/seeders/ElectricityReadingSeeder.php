<?php

namespace Database\Seeders;

use App\Models\Branch;
use App\Models\DailyRecord;
use App\Models\ElectricityMeter;
use App\Models\ElectricityReading;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Seeder;

class ElectricityReadingSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $branches = Branch::with([
            'electricityMeters' => function ($q) {
                $q->where('is_active', true)->orderBy('meter_name');
            }
        ])->get();

        if ($branches->isEmpty()) {
            $this->command->warn('Tidak ada cabang yang ditemukan. Silakan buat cabang terlebih dahulu.');
            return;
        }

        $users = User::whereHas('roles', function ($q) {
            $q->whereIn('name', ['admin', 'staff']);
        })->get();

        if ($users->isEmpty()) {
            $this->command->warn('Tidak ada user yang ditemukan. Silakan buat user terlebih dahulu.');
            return;
        }

        // Track meter values per branch dan meter untuk konsistensi
        $meterValues = [];

        // Generate data untuk 14 hari terakhir
        $daysToGenerate = 14;

        foreach ($branches as $branch) {
            $meters = $branch->electricityMeters;

            if ($meters->isEmpty()) {
                $this->command->warn("Tidak ada meter listrik untuk cabang: {$branch->name}. Jalankan ElectricityMeterSeeder terlebih dahulu.");
                continue;
            }

            // Initialize meter values untuk branch ini
            foreach ($meters as $meter) {
                $meterValues[$meter->id] = [
                    'wbp' => rand(50000, 55000) + (rand(0, 100) / 100), // 50000-55000 dengan 2 desimal
                    'lwbp' => rand(80000, 85000) + (rand(0, 100) / 100), // 80000-85000 dengan 2 desimal
                ];
            }

            // Assign random user untuk branch ini
            $branchUser = $users->random();

            for ($day = $daysToGenerate; $day >= 1; $day--) {
                $date = Carbon::now()->subDays($day);

                // Cari atau buat daily record untuk branch ini di tanggal tersebut
                $dailyRecord = DailyRecord::where('branch_id', $branch->id)
                    ->whereDate('created_at', $date->toDateString())
                    ->first();

                if (!$dailyRecord) {
                    $dailyRecord = DailyRecord::create([
                        'user_id' => $branchUser->id,
                        'branch_id' => $branch->id,
                        'total_customers' => rand(400, 500),
                        'created_at' => $date->copy()->setTime(rand(7, 9), rand(0, 59), rand(0, 59)),
                        'updated_at' => $date->copy()->setTime(rand(7, 9), rand(0, 59), rand(0, 59)),
                    ]);
                }

                // Create electricity readings untuk setiap meter
                foreach ($meters as $meter) {
                    // Skip jika sudah ada reading untuk daily record dan meter ini
                    $existingReading = ElectricityReading::where('daily_record_id', $dailyRecord->id)
                        ->where('electricity_meter_id', $meter->id)
                        ->first();

                    if ($existingReading) {
                        continue;
                    }

                    // Calculate new values (increment from previous day)
                    $wbpIncrement = rand(30, 80) + (rand(0, 100) / 100); // 30-80 kWh per hari untuk WBP
                    $lwbpIncrement = rand(50, 120) + (rand(0, 100) / 100); // 50-120 kWh per hari untuk LWBP

                    $wbpValue = round($meterValues[$meter->id]['wbp'] + $wbpIncrement, 2);
                    $lwbpValue = round($meterValues[$meter->id]['lwbp'] + $lwbpIncrement, 2);

                    ElectricityReading::create([
                        'daily_record_id' => $dailyRecord->id,
                        'electricity_meter_id' => $meter->id,
                        'meter_value_wbp' => $wbpValue,
                        'meter_value_lwbp' => $lwbpValue,
                        'photo_wbp' => null,
                        'photo_lwbp' => null,
                        'created_at' => $dailyRecord->created_at,
                        'updated_at' => $dailyRecord->updated_at,
                    ]);

                    // Update tracked values for next day
                    $meterValues[$meter->id]['wbp'] = $wbpValue;
                    $meterValues[$meter->id]['lwbp'] = $lwbpValue;
                }
            }

            $this->command->info("Electricity readings created for branch: {$branch->name}");
        }

        $this->command->info('Electricity readings seeded successfully!');
    }
}
