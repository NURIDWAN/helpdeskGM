<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     * 
     * Menyederhanakan tabel electricity_readings dari 2 kolom (WBP + LWBP) 
     * menjadi 1 kolom (meter_value dan photo).
     */
    public function up(): void
    {
        Schema::table('electricity_readings', function (Blueprint $table) {
            // Rename WBP columns to simplified names
            $table->renameColumn('meter_value_wbp', 'meter_value');
            $table->renameColumn('photo_wbp', 'photo');
        });

        Schema::table('electricity_readings', function (Blueprint $table) {
            // Drop LWBP columns
            $table->dropColumn(['meter_value_lwbp', 'photo_lwbp']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('electricity_readings', function (Blueprint $table) {
            // Restore original column names
            $table->renameColumn('meter_value', 'meter_value_wbp');
            $table->renameColumn('photo', 'photo_wbp');
        });

        Schema::table('electricity_readings', function (Blueprint $table) {
            // Add back LWBP columns
            $table->decimal('meter_value_lwbp', 12, 2)->nullable()->after('meter_value_wbp');
            $table->text('photo_lwbp')->nullable()->after('photo_wbp');
        });
    }
};
