<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('electricity_meters', function (Blueprint $table) {
            $table->id();
            $table->foreignId('branch_id')->constrained()->onDelete('cascade');
            $table->string('meter_name');                              // Nama meter (Gardu 1, Gardu 2)
            $table->string('meter_number')->nullable();                // Nomor ID meter PLN
            $table->string('location')->nullable();                    // Lokasi fisik
            $table->decimal('power_capacity', 10, 2)->nullable();      // Daya tersambung (VA/kVA)
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->unique(['branch_id', 'meter_name']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('electricity_meters');
    }
};
