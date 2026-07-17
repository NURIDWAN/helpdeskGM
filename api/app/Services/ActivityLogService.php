<?php

namespace App\Services;

use App\Models\ActivityLog;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class ActivityLogService
{
    /**
     * Atribut sensitif yang tidak boleh di-log.
     */
    protected static array $sensitiveAttributes = [
        'password',
        'password_confirmation',
        'remember_token',
        'token',
        'secret',
        'two_factor_secret',
        'two_factor_recovery_codes',
    ];

    /**
     * Log aktivitas umum (CRUD).
     */
    public static function log(
        string $action,
        string $module,
        ?Model $model = null,
        ?string $description = null,
        ?array $oldValues = null,
        ?array $newValues = null,
    ): ?ActivityLog {
        try {
            $request = request();

            $log = ActivityLog::create([
                'user_id' => Auth::id(),
                'action' => $action,
                'module' => $module,
                'model_type' => $model ? get_class($model) : null,
                'model_id' => $model?->getKey(),
                'description' => $description ?? static::generateDescription($action, $module, $model),
                'old_values' => $oldValues ? static::filterSensitive($oldValues) : null,
                'new_values' => $newValues ? static::filterSensitive($newValues) : null,
                'ip_address' => $request?->ip(),
                'user_agent' => $request?->userAgent(),
                'created_at' => now(),
            ]);

            try {
                app(BrowserNotificationService::class)->dispatch($log);
            } catch (\Throwable $e) {
                Log::error('Browser notification dispatch failed: ' . $e->getMessage(), [
                    'activity_log_id' => $log->id,
                ]);
            }

            return $log;
        } catch (\Throwable $e) {
            Log::error('ActivityLog failed: ' . $e->getMessage(), [
                'action' => $action,
                'module' => $module,
            ]);
            return null;
        }
    }

    /**
     * Log aktivitas auth (login, logout, login_failed).
     */
    public static function logAuth(
        string $action,
        ?string $description = null,
        ?int $userId = null,
        array $extra = [],
    ): ?ActivityLog {
        try {
            $request = request();

            $log = ActivityLog::create([
                'user_id' => $userId ?? Auth::id(),
                'action' => $action,
                'module' => 'auth',
                'model_type' => null,
                'model_id' => null,
                'description' => $description ?? "User {$action}",
                'old_values' => null,
                'new_values' => !empty($extra) ? static::filterSensitive($extra) : null,
                'ip_address' => $request?->ip(),
                'user_agent' => $request?->userAgent(),
                'created_at' => now(),
            ]);

            try {
                app(BrowserNotificationService::class)->dispatch($log);
            } catch (\Throwable $e) {
                Log::error('Browser notification dispatch failed: ' . $e->getMessage(), [
                    'activity_log_id' => $log->id,
                ]);
            }

            return $log;
        } catch (\Throwable $e) {
            Log::error('ActivityLog auth failed: ' . $e->getMessage(), [
                'action' => $action,
            ]);
            return null;
        }
    }

    /**
     * Generate deskripsi otomatis berdasarkan aksi dan model.
     */
    protected static function generateDescription(string $action, string $module, ?Model $model): string
    {
        $userName = Auth::user()?->name ?? 'System';
        $moduleName = ucfirst(str_replace('-', ' ', $module));

        $identifier = '';
        if ($model) {
            // Coba ambil atribut yang bisa jadi identifier
            $identifier = $model->getAttribute('name')
                ?? $model->getAttribute('title')
                ?? $model->getAttribute('code')
                ?? $model->getAttribute('email')
                ?? "#{$model->getKey()}";
        }

        return match ($action) {
            'created' => "{$userName} membuat {$moduleName}: {$identifier}",
            'updated' => "{$userName} mengubah {$moduleName}: {$identifier}",
            'deleted' => "{$userName} menghapus {$moduleName}: {$identifier}",
            default => "{$userName} melakukan {$action} pada {$moduleName}",
        };
    }

    /**
     * Filter atribut sensitif dari data.
     */
    protected static function filterSensitive(array $data): array
    {
        foreach (static::$sensitiveAttributes as $attr) {
            if (array_key_exists($attr, $data)) {
                $data[$attr] = '********';
            }
        }

        return $data;
    }
}
