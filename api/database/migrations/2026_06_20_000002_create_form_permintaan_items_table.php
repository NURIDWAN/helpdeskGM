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
        Schema::create('form_permintaan_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('form_permintaan_id')->constrained('form_permintaan')->onDelete('cascade');
            $table->string('product_description', 255);
            $table->integer('quantity');
            $table->string('uom', 50);
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index('form_permintaan_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('form_permintaan_items');
    }
};
