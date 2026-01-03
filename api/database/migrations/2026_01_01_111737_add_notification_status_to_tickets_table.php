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
        Schema::table('tickets', function (Blueprint $table) {
            $table->boolean('notif_staff_sent')->nullable()->default(null)->after('completed_at');
            $table->boolean('notif_group_sent')->nullable()->default(null)->after('notif_staff_sent');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('tickets', function (Blueprint $table) {
            $table->dropColumn(['notif_staff_sent', 'notif_group_sent']);
        });
    }
};
