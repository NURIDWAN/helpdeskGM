<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Check if there are any work orders without a ticket
        $orphanedWorkOrders = DB::table('work_orders')->whereNull('ticket_id')->count();

        if ($orphanedWorkOrders > 0) {
            throw new \RuntimeException(
                "Cannot make ticket_id required: Found {$orphanedWorkOrders} work order(s) without a ticket. " .
                "Please assign tickets to these work orders first, or delete them."
            );
        }

        Schema::table('work_orders', function (Blueprint $table) {
            // Drop the old foreign key constraint (which has SET NULL on delete)
            $table->dropForeign(['ticket_id']);
        });

        Schema::table('work_orders', function (Blueprint $table) {
            // Change column to NOT NULL
            $table->unsignedBigInteger('ticket_id')->nullable(false)->change();
        });

        Schema::table('work_orders', function (Blueprint $table) {
            // Add new foreign key constraint with RESTRICT on delete
            // This prevents deletion of tickets that have work orders
            $table->foreign('ticket_id')
                ->references('id')
                ->on('tickets')
                ->onDelete('restrict');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('work_orders', function (Blueprint $table) {
            // Drop the restrict constraint
            $table->dropForeign(['ticket_id']);
        });

        Schema::table('work_orders', function (Blueprint $table) {
            // Change column back to nullable
            $table->unsignedBigInteger('ticket_id')->nullable()->change();
        });

        Schema::table('work_orders', function (Blueprint $table) {
            // Restore original foreign key with SET NULL on delete
            $table->foreign('ticket_id')
                ->references('id')
                ->on('tickets')
                ->onDelete('set null');
        });
    }
};
