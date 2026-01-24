<?php

namespace App\Http\Controllers;

use App\Helpers\ResponseHelper;
use App\Models\WhatsAppSetting;
use App\Models\WhatsAppTemplate;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use Spatie\Permission\Middleware\PermissionMiddleware;

class WhatsAppSettingController extends Controller implements HasMiddleware
{
    public static function middleware()
    {
        return [
            new Middleware(PermissionMiddleware::using(['whatsapp-setting-list']), only: ['index', 'getTemplates', 'getPlaceholders', 'getNotificationSettings']),
            new Middleware(PermissionMiddleware::using(['whatsapp-setting-edit']), only: ['updateSettings', 'updateTemplate', 'updateNotificationSettings']),
        ];
    }

    /**
     * Get all WhatsApp settings
     */
    public function index()
    {
        try {
            $settings = WhatsAppSetting::getAllSettings();

            // Add defaults if not set
            $defaults = [
                'enabled' => 'true',
                'token' => config('services.whatsapp.token', ''),
                'group_id' => '',
                'delay' => '2',
            ];

            foreach ($defaults as $key => $value) {
                if (!isset($settings[$key])) {
                    $settings[$key] = $value;
                }
            }

            return ResponseHelper::jsonResponse(true, 'Settings berhasil diambil', $settings, 200);
        } catch (\Throwable $e) {
            return ResponseHelper::jsonResponse(false, 'Terjadi kesalahan', null, 500);
        }
    }

    /**
     * Update WhatsApp settings
     */
    public function updateSettings(Request $request)
    {
        try {
            $validated = $request->validate([
                'enabled' => 'sometimes|string',
                'token' => 'sometimes|string|nullable',
                'group_id' => 'sometimes|string|nullable',
                'delay' => 'sometimes|numeric|min:1|max:60',
            ]);

            // Convert delay to string before saving (database stores as text)
            if (isset($validated['delay'])) {
                $validated['delay'] = (string) $validated['delay'];
            }

            foreach ($validated as $key => $value) {
                WhatsAppSetting::setValue($key, $value);
            }

            return ResponseHelper::jsonResponse(true, 'Settings berhasil diperbarui', null, 200);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return ResponseHelper::jsonResponse(false, 'Validasi gagal', $e->errors(), 422);
        } catch (\Throwable $e) {
            return ResponseHelper::jsonResponse(false, $e->getMessage(), null, 500);
        }
    }

    /**
     * Get all WhatsApp templates
     */
    public function getTemplates()
    {
        try {
            $templates = WhatsAppTemplate::all();

            return ResponseHelper::jsonResponse(true, 'Templates berhasil diambil', $templates, 200);
        } catch (\Throwable $e) {
            return ResponseHelper::jsonResponse(false, 'Terjadi kesalahan', null, 500);
        }
    }

    /**
     * Update a WhatsApp template
     */
    public function updateTemplate(Request $request, $id)
    {
        try {
            $template = WhatsAppTemplate::findOrFail($id);

            $validated = $request->validate([
                'name' => 'sometimes|string|max:100',
                'content' => 'sometimes|string',
                'is_active' => 'sometimes|boolean',
                'send_to_group' => 'sometimes|boolean',
            ]);

            $template->update($validated);

            return ResponseHelper::jsonResponse(true, 'Template berhasil diperbarui', $template, 200);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return ResponseHelper::jsonResponse(false, 'Template tidak ditemukan', null, 404);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return ResponseHelper::jsonResponse(false, 'Validasi gagal', $e->errors(), 422);
        } catch (\Throwable $e) {
            return ResponseHelper::jsonResponse(false, $e->getMessage(), null, 500);
        }
    }

    /**
     * Get available placeholders for a template type
     */
    public function getPlaceholders($type)
    {
        try {
            $placeholders = WhatsAppTemplate::getPlaceholders($type);

            return ResponseHelper::jsonResponse(true, 'Placeholders berhasil diambil', $placeholders, 200);
        } catch (\Throwable $e) {
            return ResponseHelper::jsonResponse(false, 'Terjadi kesalahan', null, 500);
        }
    }

    /**
     * Test send WhatsApp message
     */
    public function testSend(Request $request)
    {
        try {
            $validated = $request->validate([
                'phone' => 'required|string',
                'message' => 'required|string',
            ]);

            $token = WhatsAppSetting::getValue('token', config('services.whatsapp.token'));
            $delay = WhatsAppSetting::getValue('delay', '2');

            if (empty($token)) {
                return ResponseHelper::jsonResponse(false, 'Token WhatsApp belum dikonfigurasi', null, 400);
            }

            $response = \Illuminate\Support\Facades\Http::timeout(30)
                ->withHeaders([
                    'Authorization' => $token,
                    'Content-Type' => 'application/x-www-form-urlencoded',
                ])
                ->asForm()
                ->post('https://api.fonnte.com/send', [
                    'target' => $validated['phone'],
                    'message' => $validated['message'],
                    'delay' => $delay,
                    'countryCode' => '62',
                ]);

            $responseData = $response->json();

            if ($response->successful() && isset($responseData['status']) && $responseData['status'] === true) {
                return ResponseHelper::jsonResponse(true, 'Pesan berhasil dikirim', $responseData, 200);
            } else {
                return ResponseHelper::jsonResponse(false, 'Gagal mengirim pesan: ' . ($responseData['message'] ?? 'Unknown error'), $responseData, 400);
            }
        } catch (\Throwable $e) {
            return ResponseHelper::jsonResponse(false, $e->getMessage(), null, 500);
        }
    }

    /**
     * Test send WhatsApp message to Group
     */
    public function testSendGroup(Request $request)
    {
        try {
            $validated = $request->validate([
                'message' => 'sometimes|string',
            ]);

            $token = WhatsAppSetting::getValue('token', config('services.whatsapp.token'));
            $groupId = WhatsAppSetting::getValue('group_id', config('services.whatsapp.group_id'));
            $delay = WhatsAppSetting::getValue('delay', '2');

            if (empty($token)) {
                return ResponseHelper::jsonResponse(false, 'Token WhatsApp belum dikonfigurasi', null, 400);
            }

            if (empty($groupId)) {
                return ResponseHelper::jsonResponse(false, 'Group ID WhatsApp belum dikonfigurasi', null, 400);
            }

            $message = $validated['message'] ?? '🔔 *Test Message* 🔔' . PHP_EOL .
                'Ini adalah pesan test dari sistem GA Maintenance.' . PHP_EOL .
                'Waktu: ' . now()->format('d/m/Y H:i:s');

            \Illuminate\Support\Facades\Log::info('Testing WhatsApp Group Send', [
                'group_id' => $groupId,
                'message_length' => strlen($message),
            ]);

            $response = \Illuminate\Support\Facades\Http::timeout(30)
                ->withHeaders([
                    'Authorization' => $token,
                    'Content-Type' => 'application/x-www-form-urlencoded',
                ])
                ->asForm()
                ->post('https://api.fonnte.com/send', [
                    'target' => $groupId,
                    'message' => $message,
                    'delay' => $delay,
                ]);

            $responseData = $response->json();

            \Illuminate\Support\Facades\Log::info('WhatsApp Group Send Response', [
                'status' => $response->status(),
                'response' => $responseData,
            ]);

            if ($response->successful() && isset($responseData['status']) && $responseData['status'] === true) {
                return ResponseHelper::jsonResponse(true, 'Pesan berhasil dikirim ke grup', [
                    'group_id' => $groupId,
                    'response' => $responseData,
                ], 200);
            } else {
                return ResponseHelper::jsonResponse(false, 'Gagal mengirim pesan ke grup: ' . ($responseData['reason'] ?? $responseData['message'] ?? 'Unknown error'), [
                    'group_id' => $groupId,
                    'response' => $responseData,
                ], 400);
            }
        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::error('WhatsApp Group Send Error', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            return ResponseHelper::jsonResponse(false, $e->getMessage(), null, 500);
        }
    }

    /**
     * Get notification settings (channel, WhatsApp, Telegram)
     */
    public function getNotificationSettings()
    {
        try {
            $settings = [
                'notification_channel' => WhatsAppSetting::getValue('notification_channel') ?: 'whatsapp',
                // WhatsApp settings
                'whatsapp_enabled' => WhatsAppSetting::getValue('enabled') ?: 'true',
                'whatsapp_token' => WhatsAppSetting::getValue('token') ?: '',
                'whatsapp_group_id' => WhatsAppSetting::getValue('group_id') ?: '',
                'whatsapp_delay' => WhatsAppSetting::getValue('delay') ?: '2',
                // Telegram settings
                'telegram_bot_token' => WhatsAppSetting::getValue('telegram_bot_token') ?: '',
                'telegram_chat_id' => WhatsAppSetting::getValue('telegram_chat_id') ?: '',
                'telegram_bot_username' => WhatsAppSetting::getValue('telegram_bot_username') ?: '',
            ];

            return ResponseHelper::jsonResponse(true, 'Notification settings berhasil diambil', $settings, 200);
        } catch (\Throwable $e) {
            return ResponseHelper::jsonResponse(false, 'Terjadi kesalahan', null, 500);
        }
    }

    /**
     * Update notification settings (channel switching + Telegram config)
     */
    public function updateNotificationSettings(Request $request)
    {
        try {
            $validated = $request->validate([
                'notification_channel' => 'sometimes|string|in:whatsapp,telegram',
                'telegram_bot_token' => 'sometimes|string|nullable',
                'telegram_chat_id' => 'sometimes|string|nullable',
                'telegram_bot_username' => 'sometimes|string|nullable',
            ]);

            foreach ($validated as $key => $value) {
                WhatsAppSetting::setValue($key, $value);
            }

            return ResponseHelper::jsonResponse(true, 'Notification settings berhasil diperbarui', null, 200);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return ResponseHelper::jsonResponse(false, 'Validasi gagal', $e->errors(), 422);
        } catch (\Throwable $e) {
            return ResponseHelper::jsonResponse(false, $e->getMessage(), null, 500);
        }
    }

    /**
     * Test send Telegram message to individual
     */
    public function testTelegramSend(Request $request)
    {
        try {
            $validated = $request->validate([
                'chat_id' => 'required|string',
                'message' => 'required|string',
            ]);

            $botToken = WhatsAppSetting::getValue('telegram_bot_token') ?: config('services.telegram.bot_token');

            if (empty($botToken)) {
                return ResponseHelper::jsonResponse(false, 'Bot Token Telegram belum dikonfigurasi', null, 400);
            }

            $url = "https://api.telegram.org/bot{$botToken}/sendMessage";

            $response = \Illuminate\Support\Facades\Http::timeout(30)->post($url, [
                'chat_id' => $validated['chat_id'],
                'text' => $validated['message'],
                'parse_mode' => 'Markdown',
            ]);

            $responseData = $response->json();

            if ($response->successful() && ($responseData['ok'] ?? false)) {
                return ResponseHelper::jsonResponse(true, 'Pesan berhasil dikirim via Telegram', $responseData, 200);
            } else {
                return ResponseHelper::jsonResponse(false, 'Gagal mengirim pesan: ' . ($responseData['description'] ?? 'Unknown error'), $responseData, 400);
            }
        } catch (\Throwable $e) {
            return ResponseHelper::jsonResponse(false, $e->getMessage(), null, 500);
        }
    }

    /**
     * Test send Telegram message to Group/Channel
     */
    public function testTelegramSendGroup(Request $request)
    {
        try {
            $validated = $request->validate([
                'message' => 'sometimes|string',
            ]);

            $botToken = WhatsAppSetting::getValue('telegram_bot_token') ?: config('services.telegram.bot_token');
            $chatId = WhatsAppSetting::getValue('telegram_chat_id') ?: config('services.telegram.chat_id');

            if (empty($botToken)) {
                return ResponseHelper::jsonResponse(false, 'Bot Token Telegram belum dikonfigurasi', null, 400);
            }

            if (empty($chatId)) {
                return ResponseHelper::jsonResponse(false, 'Chat ID Telegram belum dikonfigurasi', null, 400);
            }

            $message = $validated['message'] ?? "🔔 *Test Message* 🔔\n" .
                "Ini adalah pesan test dari sistem GA Maintenance.\n" .
                "Waktu: " . now()->format('d/m/Y H:i:s');

            $url = "https://api.telegram.org/bot{$botToken}/sendMessage";

            $response = \Illuminate\Support\Facades\Http::timeout(30)->post($url, [
                'chat_id' => $chatId,
                'text' => $message,
                'parse_mode' => 'Markdown',
            ]);

            $responseData = $response->json();

            if ($response->successful() && ($responseData['ok'] ?? false)) {
                return ResponseHelper::jsonResponse(true, 'Pesan berhasil dikirim ke grup Telegram', [
                    'chat_id' => $chatId,
                    'response' => $responseData,
                ], 200);
            } else {
                return ResponseHelper::jsonResponse(false, 'Gagal mengirim pesan: ' . ($responseData['description'] ?? 'Unknown error'), [
                    'chat_id' => $chatId,
                    'response' => $responseData,
                ], 400);
            }
        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::error('Telegram Group Send Error', [
                'error' => $e->getMessage(),
            ]);
            return ResponseHelper::jsonResponse(false, $e->getMessage(), null, 500);
        }
    }
}
