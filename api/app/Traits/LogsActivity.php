<?php

namespace App\Traits;

use App\Services\ActivityLogService;

/**
 * Trait untuk otomatis mencatat aktivitas CRUD pada model Eloquent.
 *
 * Pasang di model: `use LogsActivity;`
 *
 * Opsional override di model:
 * - protected static string $logModule = 'custom-module';
 * - protected static array $logExcept = ['updated_at', 'created_at'];
 */
trait LogsActivity
{
    public static function bootLogsActivity(): void
    {
        static::created(function ($model) {
            $newValues = $model->getLoggableAttributes($model->getAttributes());

            ActivityLogService::log(
                action: 'created',
                module: $model->getLogModule(),
                model: $model,
                oldValues: null,
                newValues: $newValues,
            );
        });

        static::updated(function ($model) {
            $dirty = $model->getDirty();

            // Jangan log jika tidak ada perubahan signifikan
            $excluded = $model->getLogExcept();
            $significantChanges = array_diff_key($dirty, array_flip($excluded));

            if (empty($significantChanges)) {
                return;
            }

            $oldValues = [];
            foreach ($significantChanges as $key => $value) {
                $oldValues[$key] = $model->getOriginal($key);
            }

            ActivityLogService::log(
                action: 'updated',
                module: $model->getLogModule(),
                model: $model,
                oldValues: $oldValues,
                newValues: $significantChanges,
            );
        });

        static::deleted(function ($model) {
            $oldValues = $model->getLoggableAttributes($model->getAttributes());

            ActivityLogService::log(
                action: 'deleted',
                module: $model->getLogModule(),
                model: $model,
                oldValues: $oldValues,
                newValues: null,
            );
        });
    }

    /**
     * Tentukan nama module untuk log.
     * Override property $logModule di model untuk kustomisasi.
     */
    public function getLogModule(): string
    {
        if (property_exists(static::class, 'logModule')) {
            return static::$logModule;
        }

        // Auto-detect: "TicketCategory" -> "ticket-category"
        $className = class_basename(static::class);

        return strtolower(
            trim(preg_replace('/[A-Z]/', '-$0', $className), '-')
        );
    }

    /**
     * Atribut yang tidak perlu di-log.
     * Override property $logExcept di model untuk kustomisasi.
     */
    public function getLogExcept(): array
    {
        $default = ['updated_at', 'created_at', 'remember_token'];

        if (property_exists(static::class, 'logExcept')) {
            return array_merge($default, static::$logExcept);
        }

        return $default;
    }

    /**
     * Filter atribut untuk logging, exclude kolom yang tidak diperlukan.
     */
    public function getLoggableAttributes(array $attributes): array
    {
        $except = $this->getLogExcept();

        return array_diff_key($attributes, array_flip($except));
    }
}
