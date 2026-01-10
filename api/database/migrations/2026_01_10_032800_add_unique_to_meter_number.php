<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Only process existing data if table exists and has data
        // (this handles fresh migrations vs. upgrades)
        if (Schema::hasTable('electricity_meters')) {
            // First, handle any NULL meter_numbers by generating unique values
            $metersWithNull = DB::table('electricity_meters')->whereNull('meter_number')->get();
            foreach ($metersWithNull as $meter) {
                DB::table('electricity_meters')
                    ->where('id', $meter->id)
                    ->update(['meter_number' => 'PLN-' . str_pad($meter->id, 8, '0', STR_PAD_LEFT)]);
            }

            // Check for duplicates and make them unique by appending suffix
            $duplicates = DB::table('electricity_meters')
                ->selectRaw('meter_number, COUNT(*) as count')
                ->whereNotNull('meter_number')
                ->groupBy('meter_number')
                ->havingRaw('COUNT(*) > 1')
                ->pluck('meter_number');

            foreach ($duplicates as $meterNumber) {
                $meters = DB::table('electricity_meters')->where('meter_number', $meterNumber)->get();
                // Skip the first one, update the rest
                $count = 2;
                foreach ($meters->skip(1) as $meter) {
                    DB::table('electricity_meters')
                        ->where('id', $meter->id)
                        ->update(['meter_number' => $meterNumber . '-' . $count]);
                    $count++;
                }
            }

            // Now add the unique constraint
            Schema::table('electricity_meters', function (Blueprint $table) {
                $table->unique('meter_number');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('electricity_meters')) {
            Schema::table('electricity_meters', function (Blueprint $table) {
                $table->dropUnique(['meter_number']);
            });
        }
    }
};
