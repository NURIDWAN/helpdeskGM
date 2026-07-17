<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // 1. Tambahkan kolom date sebagai nullable dulu (agar tidak error pada data existing)
        Schema::table('daily_records', function (Blueprint $table) {
            $table->date('date')->nullable()->after('user_id');
        });

        // 2. Backfill: isi kolom date dari created_at untuk semua record yang sudah ada
        DB::statement('UPDATE daily_records SET date = DATE(created_at) WHERE date IS NULL');

        // 3. Ubah kolom menjadi NOT NULL setelah backfill
        Schema::table('daily_records', function (Blueprint $table) {
            $table->date('date')->nullable(false)->change();
        });

        // 4. Tambahkan unique constraint
        Schema::table('daily_records', function (Blueprint $table) {
            $table->unique(['branch_id', 'date']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('daily_records', function (Blueprint $table) {
            $table->dropUnique(['branch_id', 'date']);
            $table->dropColumn('date');
        });
    }
};
