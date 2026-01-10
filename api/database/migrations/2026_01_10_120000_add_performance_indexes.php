<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * 
     * This migration adds indexes to frequently queried columns
     * to improve query performance across the application.
     */
    public function up(): void
    {
        // Add indexes to tickets table
        Schema::table('tickets', function (Blueprint $table) {
            // Index for status filtering (very frequently used)
            $table->index('status', 'idx_tickets_status');
            
            // Index for priority filtering
            $table->index('priority', 'idx_tickets_priority');
            
            // Composite index for branch + status (common query pattern)
            $table->index(['branch_id', 'status'], 'idx_tickets_branch_status');
            
            // Index for created_at (used in date range queries)
            $table->index('created_at', 'idx_tickets_created_at');
        });

        // Add indexes to work_orders table
        Schema::table('work_orders', function (Blueprint $table) {
            // Index for status filtering
            $table->index('status', 'idx_work_orders_status');
            
            // Index for ticket relationship (if not already indexed by FK)
            if (!$this->hasIndex('work_orders', 'ticket_id')) {
                $table->index('ticket_id', 'idx_work_orders_ticket_id');
            }
        });

        // Add indexes to work_reports table
        Schema::table('work_reports', function (Blueprint $table) {
            // Index for user_id (filtering by reporter)
            $table->index('user_id', 'idx_work_reports_user_id');
            
            // Index for branch_id (filtering by branch)
            $table->index('branch_id', 'idx_work_reports_branch_id');
            
            // Index for created_at (date range queries)
            $table->index('created_at', 'idx_work_reports_created_at');
        });

        // Add indexes to daily_records table
        Schema::table('daily_records', function (Blueprint $table) {
            // Index for user_id
            $table->index('user_id', 'idx_daily_records_user_id');
            
            // Index for created_at (date range queries)
            $table->index('created_at', 'idx_daily_records_created_at');
            
            // Composite index for branch + date queries
            $table->index(['branch_id', 'created_at'], 'idx_daily_records_branch_date');
        });

        // Add indexes to utility_readings table
        Schema::table('utility_readings', function (Blueprint $table) {
            // Index for daily_record relationship
            $table->index('daily_record_id', 'idx_utility_readings_daily_record_id');
            
            // Index for category filtering
            $table->index('category', 'idx_utility_readings_category');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('tickets', function (Blueprint $table) {
            $table->dropIndex('idx_tickets_status');
            $table->dropIndex('idx_tickets_priority');
            $table->dropIndex('idx_tickets_branch_status');
            $table->dropIndex('idx_tickets_created_at');
        });

        Schema::table('work_orders', function (Blueprint $table) {
            $table->dropIndex('idx_work_orders_status');
            if ($this->hasIndex('work_orders', 'idx_work_orders_ticket_id')) {
                $table->dropIndex('idx_work_orders_ticket_id');
            }
        });

        Schema::table('work_reports', function (Blueprint $table) {
            $table->dropIndex('idx_work_reports_user_id');
            $table->dropIndex('idx_work_reports_branch_id');
            $table->dropIndex('idx_work_reports_created_at');
        });

        Schema::table('daily_records', function (Blueprint $table) {
            $table->dropIndex('idx_daily_records_user_id');
            $table->dropIndex('idx_daily_records_created_at');
            $table->dropIndex('idx_daily_records_branch_date');
        });

        Schema::table('utility_readings', function (Blueprint $table) {
            $table->dropIndex('idx_utility_readings_daily_record_id');
            $table->dropIndex('idx_utility_readings_category');
        });
    }

    /**
     * Check if an index exists on a table
     */
    private function hasIndex(string $table, string $column): bool
    {
        $indexes = Schema::getIndexes($table);
        
        foreach ($indexes as $index) {
            if (in_array($column, $index['columns'])) {
                return true;
            }
        }
        
        return false;
    }
};
