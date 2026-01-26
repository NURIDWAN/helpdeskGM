<?php

namespace App\Http\Controllers;

use App\Helpers\ResponseHelper;
use App\Models\User;
use App\Models\WhatsAppSetting;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class TelegramLoginWidgetController extends Controller
{
    /**
     * Verify Telegram Login Widget data and link to user account
     * 
     * Telegram sends these fields:
     * - id: Telegram user ID (chat_id)
     * - first_name, last_name, username: User info
     * - photo_url: Profile photo
     * - auth_date: Unix timestamp when auth was done
     * - hash: HMAC-SHA256 signature for verification
     */
    public function verifyAndLink(Request $request)
    {
        try {
            $user = $request->user();

            if (!$user) {
                return ResponseHelper::jsonResponse(false, 'Unauthorized', null, 401);
            }

            $request->validate([
                'id' => 'required|string',
                'first_name' => 'nullable|string',
                'last_name' => 'nullable|string',
                'username' => 'nullable|string',
                'photo_url' => 'nullable|string',
                'auth_date' => 'required|integer',
                'hash' => 'required|string',
            ]);

            $telegramData = $request->only([
                'id', 'first_name', 'last_name', 'username', 'photo_url', 'auth_date', 'hash'
            ]);

            // Verify the hash
            if (!$this->verifyTelegramHash($telegramData)) {
                Log::warning('Telegram widget login hash verification failed', [
                    'user_id' => $user->id,
                    'telegram_data' => $telegramData
                ]);
                return ResponseHelper::jsonResponse(false, 'Verifikasi Telegram gagal. Data tidak valid.', null, 400);
            }

            // Check if auth_date is not too old (max 24 hours)
            $authDate = (int) $telegramData['auth_date'];
            if (time() - $authDate > 86400) {
                return ResponseHelper::jsonResponse(false, 'Login Telegram sudah kadaluarsa. Silakan coba lagi.', null, 400);
            }

            // Check if this Telegram ID is already linked to another user
            $existingUser = User::where('telegram_chat_id', $telegramData['id'])
                ->where('id', '!=', $user->id)
                ->first();

            if ($existingUser) {
                return ResponseHelper::jsonResponse(
                    false, 
                    'Akun Telegram ini sudah terhubung dengan user lain.', 
                    null, 
                    400
                );
            }

            // Link Telegram to user
            $user->telegram_chat_id = (string) $telegramData['id'];
            $user->telegram_linked_at = now();
            $user->save();

            // Build Telegram username display
            $telegramName = $telegramData['first_name'] ?? 'User';
            if (!empty($telegramData['last_name'])) {
                $telegramName .= ' ' . $telegramData['last_name'];
            }
            if (!empty($telegramData['username'])) {
                $telegramName .= ' (@' . $telegramData['username'] . ')';
            }

            Log::info('Telegram account linked via widget', [
                'user_id' => $user->id,
                'telegram_id' => $telegramData['id'],
                'telegram_username' => $telegramData['username'] ?? null
            ]);

            // Send welcome message to user via Telegram
            $this->sendWelcomeMessage($telegramData['id'], $user->name);

            return ResponseHelper::jsonResponse(true, 'Telegram berhasil dihubungkan', [
                'connected' => true,
                'chat_id' => $telegramData['id'],
                'telegram_name' => $telegramName,
                'linked_at' => now()->toISOString(),
            ], 200);
        } catch (\Exception $e) {
            Log::error('Failed to verify Telegram widget login', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return ResponseHelper::jsonResponse(false, 'Gagal menghubungkan Telegram', null, 500);
        }
    }

    /**
     * Verify Telegram hash using HMAC-SHA256
     * 
     * @see https://core.telegram.org/widgets/login#checking-authorization
     */
    private function verifyTelegramHash(array $data): bool
    {
        $botToken = WhatsAppSetting::getValue('telegram_bot_token')
            ?: config('services.telegram.bot_token');

        if (!$botToken) {
            Log::warning('Telegram bot token not configured for widget verification');
            return false;
        }

        $hash = $data['hash'] ?? null;
        if (!$hash) {
            return false;
        }

        // Remove hash from data and sort alphabetically
        unset($data['hash']);
        ksort($data);

        // Build data-check-string
        $dataCheckArr = [];
        foreach ($data as $key => $value) {
            if ($value !== null && $value !== '') {
                $dataCheckArr[] = $key . '=' . $value;
            }
        }
        $dataCheckString = implode("\n", $dataCheckArr);

        // Create secret key: SHA256 hash of bot token
        $secretKey = hash('sha256', $botToken, true);

        // Calculate HMAC-SHA256
        $calculatedHash = hash_hmac('sha256', $dataCheckString, $secretKey);

        return hash_equals($calculatedHash, $hash);
    }

    /**
     * Send welcome message to user via Telegram
     */
    private function sendWelcomeMessage(string $chatId, string $userName): void
    {
        try {
            $botToken = WhatsAppSetting::getValue('telegram_bot_token')
                ?: config('services.telegram.bot_token');

            if (!$botToken) {
                return;
            }

            $message = "🎉 *Selamat, {$userName}!*\n\n" .
                "Akun Telegram Anda berhasil dihubungkan dengan sistem Helpdesk.\n\n" .
                "Anda akan menerima notifikasi tentang:\n" .
                "✅ Tiket baru yang ditugaskan\n" .
                "✅ Update status tiket\n" .
                "✅ Balasan tiket\n" .
                "✅ Surat Perintah Kerja (SPK)\n\n" .
                "Terima kasih telah menggunakan layanan kami!";

            $url = "https://api.telegram.org/bot{$botToken}/sendMessage";

            \Illuminate\Support\Facades\Http::timeout(30)->post($url, [
                'chat_id' => $chatId,
                'text' => $message,
                'parse_mode' => 'Markdown',
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to send Telegram welcome message', [
                'chat_id' => $chatId,
                'error' => $e->getMessage()
            ]);
        }
    }

    /**
     * Get bot info for widget configuration
     */
    public function getBotInfo()
    {
        try {
            $botUsername = WhatsAppSetting::getValue('telegram_bot_username');

            if (!$botUsername) {
                return ResponseHelper::jsonResponse(false, 'Bot username belum dikonfigurasi', null, 400);
            }

            return ResponseHelper::jsonResponse(true, 'Bot info', [
                'bot_username' => $botUsername,
            ], 200);
        } catch (\Exception $e) {
            Log::error('Failed to get bot info', ['error' => $e->getMessage()]);
            return ResponseHelper::jsonResponse(false, 'Gagal mendapatkan info bot', null, 500);
        }
    }
}
