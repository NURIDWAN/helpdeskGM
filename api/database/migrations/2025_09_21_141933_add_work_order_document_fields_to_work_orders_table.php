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
        Schema::table('work_orders', function (Blueprint $table) {
            $table->string('damage_unit')->nullable()->comment('Unit kerusakan');
            $table->string('contact_person')->nullable()->comment('Contact person');
            $table->string('contact_phone')->nullable()->comment('Nomor telepon/HP contact person');
            $table->string('product_type')->nullable()->comment('Jenis produk');
            $table->string('brand')->nullable()->comment('Merk');
            $table->string('model')->nullable()->comment('Tipe');
            $table->string('serial_number')->nullable()->comment('Nomor seri');
            $table->date('purchase_date')->nullable()->comment('Tanggal pembelian');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('work_orders', function (Blueprint $table) {
            $table->dropColumn([
                'damage_unit',
                'contact_person',
                'contact_phone',
                'product_type',
                'brand',
                'model',
                'serial_number',
                'purchase_date'
            ]);
        });
    }
};
