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
        // Add telegram_chat_id and telegram_linked_at columns to users table
        Schema::table('users', function (Blueprint $table) {
            $table->string('telegram_chat_id', 100)->nullable()->after('phone_number');
            $table->timestamp('telegram_linked_at')->nullable()->after('telegram_chat_id');
        });

        // Add Telegram settings to whatsapp_settings table
        $settings = [
            ['key' => 'notification_channel', 'value' => 'whatsapp'],
            ['key' => 'telegram_bot_token', 'value' => ''],
            ['key' => 'telegram_chat_id', 'value' => ''],
            ['key' => 'telegram_bot_username', 'value' => ''],
        ];

        foreach ($settings as $setting) {
            DB::table('whatsapp_settings')->insertOrIgnore($setting);
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Remove columns from users table
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['telegram_chat_id', 'telegram_linked_at']);
        });

        // Remove Telegram settings
        DB::table('whatsapp_settings')->whereIn('key', [
            'notification_channel',
            'telegram_bot_token',
            'telegram_chat_id',
            'telegram_bot_username',
        ])->delete();
    }
};
