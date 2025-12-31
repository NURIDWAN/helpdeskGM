<?php

namespace Database\Seeders;

use App\Models\DailyRecord;
use App\Models\UtilityReading;
use App\Models\Branch;
use App\Models\User;
use App\Enums\UtilityCategory;
use App\Enums\UtilitySubType;
use Illuminate\Database\Seeder;
use Carbon\Carbon;

class DailyRecordSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $branches = Branch::all();
        $users = User::whereHas('roles', function ($q) {
            $q->whereIn('name', ['admin', 'staff']);
        })->get();

        if ($branches->isEmpty()) {
            $this->command->warn('Tidak ada cabang yang ditemukan. Silakan buat cabang terlebih dahulu.');
            return;
        }

        if ($users->isEmpty()) {
            $this->command->warn('Tidak ada user yang ditemukan. Silakan buat user terlebih dahulu.');
            return;
        }

        // Stove types
        $stoveTypes = ['SHINPO', 'RINNAI', 'MODENA', 'YAMAHA', 'COSMOS'];

        // Gas types
        $gasTypes = ['LPG', 'PERTAMINA', 'ELPIJI'];

        // Track meter values per branch untuk konsistensi
        $branchMeterValues = [];

        // Generate data untuk 14 hari terakhir
        $daysToGenerate = 14;

        foreach ($branches as $branch) {
            // Initialize meter values untuk branch ini
            $branchMeterValues[$branch->id] = [
                'gas' => rand(50000, 52000) + (rand(0, 100) / 10), // 50000-52000 dengan 1 desimal
                'water' => [
                    'Belakang Gardu Listrik' => rand(51000, 52000) + (rand(0, 100) / 10),
                ],
                'electricity' => [
                    'Gardu' => rand(7000, 7100) + (rand(0, 100) / 100), // 7000-7100 dengan 2 desimal
                ],
            ];

            // Assign random user untuk branch ini
            $branchUser = $users->random();

            for ($day = $daysToGenerate; $day >= 1; $day--) {
                $date = Carbon::now()->subDays($day);

                // Skip jika sudah ada daily record untuk branch ini di tanggal tersebut
                $existingRecord = DailyRecord::where('branch_id', $branch->id)
                    ->whereDate('created_at', $date->toDateString())
                    ->first();

                if ($existingRecord) {
                    continue;
                }

                // Create daily record
                $dailyRecord = DailyRecord::create([
                    'user_id' => $branchUser->id,
                    'branch_id' => $branch->id,
                    'total_customers' => rand(400, 500),
                    'created_at' => $date->copy()->setTime(rand(7, 9), rand(0, 59), rand(0, 59)),
                    'updated_at' => $date->copy()->setTime(rand(7, 9), rand(0, 59), rand(0, 59)),
                ]);

                // Gas Reading (biasanya 1 per daily record)
                $gasOpening = $branchMeterValues[$branch->id]['gas'];
                $gasUsage = rand(15, 25) + (rand(0, 10) / 10); // 15-25 dengan 1 desimal
                $gasClosing = $gasOpening + $gasUsage;

                UtilityReading::create([
                    'daily_record_id' => $dailyRecord->id,
                    'category' => UtilityCategory::GAS->value,
                    'sub_type' => UtilitySubType::GENERAL->value,
                    'location' => null,
                    'meter_value' => round($gasClosing, 2),
                    'photo' => null,
                    'created_at' => $dailyRecord->created_at,
                    'updated_at' => $dailyRecord->updated_at,
                ]);

                // Update untuk next day
                $branchMeterValues[$branch->id]['gas'] = $gasClosing;

                // Water Reading (biasanya 1 per daily record)
                $waterLocation = 'Belakang Gardu Listrik';
                $waterOpening = $branchMeterValues[$branch->id]['water'][$waterLocation];
                $waterUsage = rand(30, 55) + (rand(0, 10) / 10); // 30-55 dengan 1 desimal
                $waterClosing = $waterOpening + $waterUsage;

                UtilityReading::create([
                    'daily_record_id' => $dailyRecord->id,
                    'category' => UtilityCategory::WATER->value,
                    'sub_type' => UtilitySubType::GENERAL->value,
                    'location' => $waterLocation,
                    'meter_value' => round($waterClosing, 2),
                    'photo' => null,
                    'created_at' => $dailyRecord->created_at,
                    'updated_at' => $dailyRecord->updated_at,
                ]);

                // Update untuk next day
                $branchMeterValues[$branch->id]['water'][$waterLocation] = $waterClosing;

                // Electricity Reading (bisa 1-3 per daily record)
                $electricityCount = rand(1, 3);
                $electricityLocations = ['Gardu', 'Depan Toko', 'Belakang Toko'];

                for ($i = 0; $i < $electricityCount; $i++) {
                    $location = $electricityLocations[$i] ?? 'Gardu';

                    // Untuk lokasi yang sama, gunakan nilai sebelumnya
                    if (!isset($branchMeterValues[$branch->id]['electricity'][$location])) {
                        $branchMeterValues[$branch->id]['electricity'][$location] =
                            $branchMeterValues[$branch->id]['electricity']['Gardu'] + rand(0, 10);
                    }

                    $electricityValue = $branchMeterValues[$branch->id]['electricity'][$location];
                    $electricityIncrement = rand(3, 8) + (rand(0, 100) / 100); // 3-8 dengan 2 desimal
                    $electricityValue += $electricityIncrement;

                    UtilityReading::create([
                        'daily_record_id' => $dailyRecord->id,
                        'category' => UtilityCategory::ELECTRICITY->value,
                        'sub_type' => $i === 0 ? UtilitySubType::GENERAL->value :
                            ($i === 1 ? UtilitySubType::LUBP->value : UtilitySubType::UBP->value),
                        'location' => $location,
                        'meter_value' => round($electricityValue, 2),
                        'photo' => null,
                        'created_at' => $dailyRecord->created_at,
                        'updated_at' => $dailyRecord->updated_at,
                    ]);

                    // Update untuk next day
                    $branchMeterValues[$branch->id]['electricity'][$location] = $electricityValue;
                }
            }

            $this->command->info("Daily records created for branch: {$branch->name}");
        }

        $this->command->info('Daily records and utility readings seeded successfully!');
    }
}

