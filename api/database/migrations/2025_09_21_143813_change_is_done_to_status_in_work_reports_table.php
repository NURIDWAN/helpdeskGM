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
        Schema::table('work_reports', function (Blueprint $table) {
            // Add new status column
            $table->string('status')->default('progress')->after('custom_job');
        });

        // Migrate existing data: is_done = true becomes 'completed', false becomes 'progress'
        DB::statement("UPDATE work_reports SET status = CASE WHEN is_done = 1 THEN 'completed' ELSE 'progress' END");

        Schema::table('work_reports', function (Blueprint $table) {
            // Drop the old is_done column
            $table->dropColumn('is_done');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('work_reports', function (Blueprint $table) {
            // Add back is_done column
            $table->boolean('is_done')->default(false)->after('custom_job');
        });

        // Migrate data back: 'completed' becomes true, others become false
        DB::statement("UPDATE work_reports SET is_done = CASE WHEN status = 'completed' THEN 1 ELSE 0 END");

        Schema::table('work_reports', function (Blueprint $table) {
            // Drop the status column
            $table->dropColumn('status');
        });
    }
};
