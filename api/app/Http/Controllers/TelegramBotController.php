<?php

namespace App\Http\Controllers;

use App\Helpers\ResponseHelper;
use App\Models\User;
use App\Models\WhatsAppSetting;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use Illuminate\Support\Facades\Log;
use Spatie\Permission\Middleware\PermissionMiddleware;

class TelegramBotController extends Controller implements HasMiddleware
{
    public static function middleware()
    {
        return [
            new Middleware(PermissionMiddleware::using(['whatsapp-setting-edit']), only: ['setWebhook', 'getWebhookInfo']),
        ];
    }

    /**
     * Handle incoming webhook from Telegram Bot
     * This is called when users interact with the bot
     */
    public function webhook(Request $request)
    {
        try {
            // Verify Telegram webhook signature via secret_token header
            $secretToken = config('services.telegram.webhook_secret');
            if ($secretToken) {
                $headerToken = $request->header('X-Telegram-Bot-Api-Secret-Token');
                if (!$headerToken || !hash_equals($secretToken, $headerToken)) {
                    Log::warning('Telegram webhook rejected: invalid secret token');
                    return response()->json(['ok' => false], 403);
                }
            }

            $update = $request->all();

            Log::info('Telegram webhook received', ['update' => $update]);

            // Handle /start command with registration token
            if (isset($update['message'])) {
                $message = $update['message'];
                $chatId = $message['chat']['id'];
                $text = $message['text'] ?? '';

                // Check if it's a /start command with token
                if (str_starts_with($text, '/start ')) {
                    $token = trim(str_replace('/start ', '', $text));
                    $this->handleStartCommand($chatId, $token, $message);
                } elseif ($text === '/start') {
                    // Just /start without token
                    $this->sendWelcomeMessage($chatId);
                }
            }

            return response()->json(['ok' => true]);
        } catch (\Exception $e) {
            Log::error('Telegram webhook error', ['error' => $e->getMessage()]);
            return response()->json(['ok' => false, 'error' => $e->getMessage()], 500);
        }
    }

    /**
     * Handle /start command with registration token
     */
    private function handleStartCommand(string $chatId, string $token, array $message): void
    {
        try {
            // Decode the token
            $decoded = $this->decodeToken($token);

            if (!$decoded) {
                $this->sendTelegramMessage($chatId, "Token tidak valid atau sudah kadaluarsa. Silakan generate token baru dari aplikasi.");
                return;
            }

            $userId = $decoded['user_id'];
            $timestamp = $decoded['timestamp'];

            // Check if token is expired (24 hours)
            if (time() - $timestamp > 86400) {
                $this->sendTelegramMessage($chatId, "Token sudah kadaluarsa. Silakan generate token baru dari aplikasi.");
                return;
            }

            // Find user and update telegram_chat_id
            $user = User::find($userId);
            if (!$user) {
                $this->sendTelegramMessage($chatId, "User tidak ditemukan.");
                return;
            }

            // Update user's Telegram chat ID
            $user->telegram_chat_id = (string) $chatId;
            $user->telegram_linked_at = now();
            $user->save();

            // Get Telegram username if available
            $telegramName = $message['from']['first_name'] ?? 'User';
            if (isset($message['from']['username'])) {
                $telegramName .= ' (@' . $message['from']['username'] . ')';
            }

            $this->sendTelegramMessage($chatId, 
                "🎉 *Selamat!* Akun Telegram Anda berhasil dihubungkan.\n\n" .
                "👤 Nama: {$user->name}\n" .
                "📧 Email: {$user->email}\n" .
                "📱 Telegram: {$telegramName}\n\n" .
                "Anda akan menerima notifikasi melalui Telegram."
            );

            Log::info('Telegram account linked', [
                'user_id' => $userId,
                'chat_id' => $chatId
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to handle Telegram start command', ['error' => $e->getMessage()]);
            $this->sendTelegramMessage($chatId, "Terjadi kesalahan. Silakan coba lagi.");
        }
    }

    /**
     * Send welcome message for /start without token
     */
    private function sendWelcomeMessage(string $chatId): void
    {
        $message = "👋 *Selamat datang di Bot Notifikasi Helpdesk!*\n\n" .
            "Untuk menghubungkan akun Telegram Anda dengan akun Helpdesk:\n\n" .
            "1. Login ke aplikasi Helpdesk\n" .
            "2. Buka menu Profil\n" .
            "3. Klik tombol 'Hubungkan Telegram'\n" .
            "4. Ikuti link yang diberikan\n\n" .
            "Jika ada pertanyaan, hubungi administrator.";

        $this->sendTelegramMessage($chatId, $message);
    }

    /**
     * Generate registration token for user
     * Called from API when user wants to connect Telegram
     */
    public function generateToken(Request $request)
    {
        try {
            $user = $request->user();

            if (!$user) {
                return ResponseHelper::jsonResponse(false, 'Unauthorized', null, 401);
            }

            // Generate token
            $token = $this->encodeToken($user->id);

            // Get bot username from settings
            $botUsername = WhatsAppSetting::getValue('telegram_bot_username') ?: 'YourBot';

            $link = "https://t.me/{$botUsername}?start={$token}";

            return ResponseHelper::jsonResponse(true, 'Token generated', [
                'link' => $link,
                'token' => $token,
                'expires_in' => 86400, // 24 hours
            ], 200);
        } catch (\Exception $e) {
            Log::error('Failed to generate Telegram token', ['error' => $e->getMessage()]);
            return ResponseHelper::jsonResponse(false, 'Gagal generate token', null, 500);
        }
    }

    /**
     * Disconnect Telegram from user account
     */
    public function disconnect(Request $request)
    {
        try {
            $user = $request->user();

            if (!$user) {
                return ResponseHelper::jsonResponse(false, 'Unauthorized', null, 401);
            }

            $user->telegram_chat_id = null;
            $user->telegram_linked_at = null;
            $user->save();

            return ResponseHelper::jsonResponse(true, 'Telegram berhasil diputuskan', null, 200);
        } catch (\Exception $e) {
            Log::error('Failed to disconnect Telegram', ['error' => $e->getMessage()]);
            return ResponseHelper::jsonResponse(false, 'Gagal memutuskan Telegram', null, 500);
        }
    }

    /**
     * Get Telegram connection status
     */
    public function status(Request $request)
    {
        try {
            $user = $request->user();

            if (!$user) {
                return ResponseHelper::jsonResponse(false, 'Unauthorized', null, 401);
            }

            return ResponseHelper::jsonResponse(true, 'Status Telegram', [
                'connected' => !empty($user->telegram_chat_id),
                'chat_id' => $user->telegram_chat_id,
                'linked_at' => $user->telegram_linked_at,
            ], 200);
        } catch (\Exception $e) {
            Log::error('Failed to get Telegram status', ['error' => $e->getMessage()]);
            return ResponseHelper::jsonResponse(false, 'Gagal mendapatkan status', null, 500);
        }
    }

    /**
     * Encode token for registration
     */
    private function encodeToken(int $userId): string
    {
        $data = [
            'user_id' => $userId,
            'timestamp' => time(),
            'random' => bin2hex(random_bytes(8)),
        ];

        $payload = json_encode($data);
        $secret = config('app.key');

        // Create signature
        $signature = hash_hmac('sha256', $payload, $secret);

        // Encode payload + signature
        return base64_encode($payload . '.' . $signature);
    }

    /**
     * Decode and verify token
     */
    private function decodeToken(string $token): ?array
    {
        try {
            $decoded = base64_decode($token);
            $parts = explode('.', $decoded);

            if (count($parts) !== 2) {
                return null;
            }

            [$payload, $signature] = $parts;
            $secret = config('app.key');

            // Verify signature
            $expectedSignature = hash_hmac('sha256', $payload, $secret);

            if (!hash_equals($expectedSignature, $signature)) {
                return null;
            }

            return json_decode($payload, true);
        } catch (\Exception $e) {
            return null;
        }
    }

    /**
     * Send message via Telegram Bot API
     */
    private function sendTelegramMessage(string $chatId, string $message): void
    {
        $botToken = WhatsAppSetting::getValue('telegram_bot_token')
            ?: config('services.telegram.bot_token');

        if (!$botToken) {
            Log::warning('Telegram bot token not configured');
            return;
        }

        $url = "https://api.telegram.org/bot{$botToken}/sendMessage";

        $response = \Illuminate\Support\Facades\Http::timeout(30)->post($url, [
            'chat_id' => $chatId,
            'text' => $message,
            'parse_mode' => 'Markdown',
        ]);

        if (!$response->successful()) {
            Log::error('Failed to send Telegram message', [
                'chat_id' => $chatId,
                'response' => $response->body()
            ]);
        }
    }

    /**
     * Set webhook URL (called once during setup)
     */
    public function setWebhook(Request $request)
    {
        try {
            $botToken = WhatsAppSetting::getValue('telegram_bot_token')
                ?: config('services.telegram.bot_token');

            if (!$botToken) {
                return ResponseHelper::jsonResponse(false, 'Bot token tidak dikonfigurasi', null, 400);
            }

            $webhookUrl = $request->input('webhook_url') ?: url('/api/telegram/webhook');

            $url = "https://api.telegram.org/bot{$botToken}/setWebhook";

            $payload = ['url' => $webhookUrl];

            // Include secret_token so Telegram sends X-Telegram-Bot-Api-Secret-Token header
            $webhookSecret = config('services.telegram.webhook_secret');
            if ($webhookSecret) {
                $payload['secret_token'] = $webhookSecret;
            }

            $response = \Illuminate\Support\Facades\Http::timeout(30)->post($url, $payload);

            $result = $response->json();

            if ($result['ok'] ?? false) {
                return ResponseHelper::jsonResponse(true, 'Webhook berhasil diset', [
                    'webhook_url' => $webhookUrl,
                ], 200);
            }

            return ResponseHelper::jsonResponse(false, 'Gagal set webhook: ' . ($result['description'] ?? 'Unknown error'), null, 500);
        } catch (\Exception $e) {
            Log::error('Failed to set Telegram webhook', ['error' => $e->getMessage()]);
            return ResponseHelper::jsonResponse(false, 'Gagal set webhook', null, 500);
        }
    }

    /**
     * Get webhook info
     */
    public function getWebhookInfo()
    {
        try {
            $botToken = WhatsAppSetting::getValue('telegram_bot_token')
                ?: config('services.telegram.bot_token');

            if (!$botToken) {
                return ResponseHelper::jsonResponse(false, 'Bot token tidak dikonfigurasi', null, 400);
            }

            $url = "https://api.telegram.org/bot{$botToken}/getWebhookInfo";

            $response = \Illuminate\Support\Facades\Http::timeout(30)->get($url);
            $result = $response->json();

            return ResponseHelper::jsonResponse(true, 'Webhook info', $result['result'] ?? null, 200);
        } catch (\Exception $e) {
            Log::error('Failed to get Telegram webhook info', ['error' => $e->getMessage()]);
            return ResponseHelper::jsonResponse(false, 'Gagal mendapatkan webhook info', null, 500);
        }
    }
}
