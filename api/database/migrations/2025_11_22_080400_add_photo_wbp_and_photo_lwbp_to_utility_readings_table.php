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
            $table->text('photo_wbp')->nullable()->after('photo');
            $table->text('photo_lwbp')->nullable()->after('photo_wbp');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('utility_readings', function (Blueprint $table) {
            $table->dropColumn(['photo_wbp', 'photo_lwbp']);
        });
    }
};
