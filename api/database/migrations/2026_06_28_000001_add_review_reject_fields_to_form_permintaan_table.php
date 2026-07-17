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
        Schema::table('form_permintaan', function (Blueprint $table) {
            $table->foreignId('reviewed_by')
                ->nullable()
                ->after('confirmed_at')
                ->constrained('users')
                ->onDelete('set null');

            $table->foreignId('rejected_by')
                ->nullable()
                ->after('reviewed_by')
                ->constrained('users')
                ->onDelete('set null');

            $table->text('rejection_reason')
                ->nullable()
                ->after('rejected_by');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('form_permintaan', function (Blueprint $table) {
            $table->dropForeign(['reviewed_by']);
            $table->dropForeign(['rejected_by']);
            $table->dropColumn(['reviewed_by', 'rejected_by', 'rejection_reason']);
        });
    }
};
