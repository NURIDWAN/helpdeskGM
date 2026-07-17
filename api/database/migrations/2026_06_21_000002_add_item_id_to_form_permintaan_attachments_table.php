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
        Schema::table('form_permintaan_attachments', function (Blueprint $table) {
            $table->foreignId('form_permintaan_item_id')
                ->nullable()
                ->after('form_permintaan_id')
                ->constrained('form_permintaan_items')
                ->onDelete('cascade');

            $table->index('form_permintaan_item_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('form_permintaan_attachments', function (Blueprint $table) {
            $table->dropForeign(['form_permintaan_item_id']);
            $table->dropIndex(['form_permintaan_item_id']);
            $table->dropColumn('form_permintaan_item_id');
        });
    }
};
