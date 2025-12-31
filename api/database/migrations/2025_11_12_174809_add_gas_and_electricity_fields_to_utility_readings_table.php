<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('utility_readings', function (Blueprint $table) {
            // Fields for Gas category
            $table->string('stove_type')->nullable()->after('sub_type');
            $table->string('gas_type')->nullable()->after('stove_type');
            
            // Fields for Electricity category (WBP and LWBP)
            $table->decimal('meter_value_wbp', 10, 2)->nullable()->after('meter_value');
            $table->decimal('meter_value_lwbp', 10, 2)->nullable()->after('meter_value_wbp');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('utility_readings', function (Blueprint $table) {
            $table->dropColumn(['stove_type', 'gas_type', 'meter_value_wbp', 'meter_value_lwbp']);
        });
    }
};
