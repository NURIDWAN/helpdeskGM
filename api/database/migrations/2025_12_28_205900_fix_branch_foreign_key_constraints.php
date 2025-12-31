<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     * 
     * Fix: Add onDelete constraint to branch_id foreign keys
     * so that branches can be deleted without foreign key errors.
     */
    public function up(): void
    {
        // Fix tickets table - add onDelete('set null')
        Schema::table('tickets', function (Blueprint $table) {
            // Drop existing foreign key
            $table->dropForeign(['branch_id']);

            // Re-add with onDelete('set null')
            $table->foreign('branch_id')
                ->references('id')
                ->on('branches')
                ->onDelete('set null');
        });

        // Fix users table - add proper constraint with onDelete('set null')
        Schema::table('users', function (Blueprint $table) {
            // Add foreign key constraint (was missing)
            $table->foreign('branch_id')
                ->references('id')
                ->on('branches')
                ->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Revert tickets table
        Schema::table('tickets', function (Blueprint $table) {
            $table->dropForeign(['branch_id']);

            $table->foreign('branch_id')
                ->references('id')
                ->on('branches');
        });

        // Revert users table
        Schema::table('users', function (Blueprint $table) {
            $table->dropForeign(['branch_id']);
        });
    }
};
