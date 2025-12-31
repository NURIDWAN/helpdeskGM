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
        Schema::create('electricity_readings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('daily_record_id')->constrained()->onDelete('cascade');
            $table->foreignId('electricity_meter_id')->constrained()->onDelete('cascade');
            $table->decimal('meter_value_wbp', 12, 2)->nullable();     // Stand WBP (Waktu Beban Puncak)
            $table->decimal('meter_value_lwbp', 12, 2)->nullable();    // Stand LWBP (Luar Waktu Beban Puncak)
            $table->text('photo_wbp')->nullable();                     // Foto bukti WBP
            $table->text('photo_lwbp')->nullable();                    // Foto bukti LWBP
            $table->timestamps();

            $table->unique(['daily_record_id', 'electricity_meter_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('electricity_readings');
    }
};
