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
        Schema::create('activity_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('action', 50); // created, updated, deleted, login, logout, login_failed
            $table->string('module', 100); // auth, user, ticket, branch, etc.
            $table->string('model_type')->nullable(); // Eloquent model class (polymorphic)
            $table->unsignedBigInteger('model_id')->nullable(); // ID record yang diubah
            $table->text('description'); // Deskripsi aksi
            $table->json('old_values')->nullable(); // Data sebelum perubahan
            $table->json('new_values')->nullable(); // Data setelah perubahan
            $table->string('ip_address', 45)->nullable(); // IPv4/IPv6
            $table->text('user_agent')->nullable(); // Browser/device info
            $table->timestamp('created_at')->useCurrent();

            // Indexes for filtering
            $table->index('user_id');
            $table->index('action');
            $table->index('module');
            $table->index(['model_type', 'model_id']);
            $table->index('created_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('activity_logs');
    }
};
