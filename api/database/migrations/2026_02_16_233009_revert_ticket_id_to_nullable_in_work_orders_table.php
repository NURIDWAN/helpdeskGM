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
            // Drop the RESTRICT foreign key constraint
            $table->dropForeign(['ticket_id']);
        });

        Schema::table('work_orders', function (Blueprint $table) {
            // Change ticket_id back to nullable
            $table->unsignedBigInteger('ticket_id')->nullable()->change();
        });

        Schema::table('work_orders', function (Blueprint $table) {
            // Re-add foreign key with SET NULL on delete
            $table->foreign('ticket_id')
                ->references('id')
                ->on('tickets')
                ->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('work_orders', function (Blueprint $table) {
            // Drop the SET NULL constraint
            $table->dropForeign(['ticket_id']);
        });

        Schema::table('work_orders', function (Blueprint $table) {
            // Change ticket_id back to required
            $table->unsignedBigInteger('ticket_id')->nullable(false)->change();
        });

        Schema::table('work_orders', function (Blueprint $table) {
            // Re-add foreign key with RESTRICT on delete
            $table->foreign('ticket_id')
                ->references('id')
                ->on('tickets')
                ->onDelete('restrict');
        });
    }
};
