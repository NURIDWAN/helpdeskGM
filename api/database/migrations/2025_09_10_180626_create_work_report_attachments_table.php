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
        Schema::create('work_report_attachments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('work_report_id')->constrained()->onDelete('cascade');
            $table->string('file_path');
            $table->string('file_type', 100)->nullable();
            $table->timestamps();

            $table->index('work_report_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('work_report_attachments');
    }
};
