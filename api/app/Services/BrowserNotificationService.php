<?php

namespace App\Services;

use App\Models\ActivityLog;
use App\Models\BrowserPushSubscription;
use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Minishlink\WebPush\Subscription;
use Minishlink\WebPush\WebPush;

class BrowserNotificationService
{
    private const EXCLUDED_MODULES = ['auth'];

    public function vapidPublicKey(): ?string
    {
        return config('services.webpush.public_key');
    }

    public function storeSubscription(User $user, array $payload, ?string $userAgent = null): BrowserPushSubscription
    {
        return BrowserPushSubscription::updateOrCreate(
            ['endpoint' => $payload['endpoint']],
            [
                'user_id' => $user->id,
                'p256dh_key' => data_get($payload, 'keys.p256dh'),
                'auth_token' => data_get($payload, 'keys.auth'),
                'content_encoding' => $payload['contentEncoding'] ?? 'aes128gcm',
                'user_agent' => $userAgent,
                'last_used_at' => now(),
                'revoked_at' => null,
            ],
        );
    }

    public function revokeSubscription(User $user, string $endpoint): void
    {
        BrowserPushSubscription::query()
            ->where('user_id', $user->id)
            ->where('endpoint', $endpoint)
            ->update(['revoked_at' => now()]);
    }

    public function notificationsFor(User $user, ?int $afterId = null, int $limit = 20): array
    {
        $query = ActivityLog::with('user:id,name,email')
            ->whereNotIn('module', self::EXCLUDED_MODULES)
            ->orderByDesc('id')
            ->limit(150);

        if ($afterId) {
            $query->where('id', '>', $afterId);
        }

        return $query->get()
            ->filter(fn (ActivityLog $log) => $this->shouldNotifyUser($user, $log))
            ->take($limit)
            ->sortBy('id')
            ->map(fn (ActivityLog $log) => $this->formatNotification($log, $user))
            ->values()
            ->all();
    }

    public function dispatch(ActivityLog $log): void
    {
        if (in_array($log->module, self::EXCLUDED_MODULES, true)) {
            return;
        }

        if (!$this->hasVapidConfig()) {
            Log::debug('Browser push skipped: VAPID is not configured.');
            return;
        }

        $recipients = $this->recipientsFor($log);
        if ($recipients->isEmpty()) {
            return;
        }

        $webPush = new WebPush([
            'VAPID' => [
                'subject' => config('services.webpush.subject'),
                'publicKey' => config('services.webpush.public_key'),
                'privateKey' => config('services.webpush.private_key'),
            ],
        ], [
            'TTL' => 3600,
            'urgency' => 'normal',
        ], 5);

        $recipientById = $recipients->keyBy('id');

        BrowserPushSubscription::query()
            ->whereNull('revoked_at')
            ->whereIn('user_id', $recipientById->keys())
            ->chunkById(100, function ($subscriptions) use ($webPush, $log, $recipientById) {
                foreach ($subscriptions as $subscription) {
                    $recipient = $recipientById->get($subscription->user_id);
                    if (!$recipient) {
                        continue;
                    }

                    $webPush->queueNotification(
                        Subscription::create([
                            'endpoint' => $subscription->endpoint,
                            'keys' => [
                                'p256dh' => $subscription->p256dh_key,
                                'auth' => $subscription->auth_token,
                            ],
                            'contentEncoding' => $subscription->content_encoding ?: 'aes128gcm',
                        ]),
                        json_encode($this->formatNotification($log, $recipient), JSON_THROW_ON_ERROR),
                    );
                }

                foreach ($webPush->flush() as $report) {
                    BrowserPushSubscription::query()
                        ->where('endpoint', $report->getEndpoint())
                        ->update(['last_used_at' => now()]);

                    if ($report->isSubscriptionExpired()) {
                        BrowserPushSubscription::query()
                            ->where('endpoint', $report->getEndpoint())
                            ->update(['revoked_at' => now()]);
                    }
                }
            });
    }

    public function shouldNotifyUser(User $user, ActivityLog $log): bool
    {
        if ((int) $log->user_id === (int) $user->id) {
            return false;
        }

        if ($user->hasAnyRole(['superadmin', 'admin'])) {
            return true;
        }

        return in_array($user->id, $this->relatedUserIds($log), true);
    }

    private function recipientsFor(ActivityLog $log): Collection
    {
        return User::query()
            ->where('id', '!=', $log->user_id)
            ->where(function ($query) use ($log) {
                $query->role(['superadmin', 'admin']);

                $relatedUserIds = $this->relatedUserIds($log);
                if (!empty($relatedUserIds)) {
                    $query->orWhereIn('id', $relatedUserIds);
                }
            })
            ->get();
    }

    private function relatedUserIds(ActivityLog $log): array
    {
        $ids = [];
        $modelId = (int) $log->model_id;

        switch ($log->model_type) {
            case \App\Models\Ticket::class:
                $ids = $this->ticketUserIds($modelId);
                break;
            case \App\Models\TicketReply::class:
                $reply = DB::table('ticket_replies')->where('id', $modelId)->first();
                $ids = $reply ? array_merge([$reply->user_id], $this->ticketUserIds((int) $reply->ticket_id)) : [];
                break;
            case \App\Models\TicketAttachment::class:
                $attachment = DB::table('ticket_attachments')->where('id', $modelId)->first();
                $ids = $attachment ? $this->ticketUserIds((int) $attachment->ticket_id) : [];
                break;
            case \App\Models\WorkOrder::class:
                $ids = $this->workOrderUserIds($modelId);
                break;
            case \App\Models\WorkReport::class:
                $report = DB::table('work_reports')->where('id', $modelId)->first();
                $ids = $report ? array_merge([$report->user_id], $this->workOrderUserIds((int) $report->work_order_id)) : [];
                break;
            case \App\Models\FormPermintaan::class:
                $form = DB::table('form_permintaan')->where('id', $modelId)->first();
                $ids = $form ? array_merge([$form->user_id], $this->ticketUserIds((int) ($form->ticket_id ?? 0))) : [];
                break;
            case \App\Models\FormPermintaanItem::class:
                $formId = DB::table('form_permintaan_items')->where('id', $modelId)->value('form_permintaan_id');
                $ids = $formId ? $this->formPermintaanUserIds((int) $formId) : [];
                break;
            case \App\Models\FormPermintaanAttachment::class:
                $formId = DB::table('form_permintaan_attachments')->where('id', $modelId)->value('form_permintaan_id');
                $ids = $formId ? $this->formPermintaanUserIds((int) $formId) : [];
                break;
            case \App\Models\DailyRecord::class:
                $record = DB::table('daily_records')->where('id', $modelId)->first();
                $ids = $record ? [$record->user_id] : [];
                break;
            case \App\Models\User::class:
                $ids = [$modelId];
                break;
            default:
                $newUserId = data_get($log->new_values, 'user_id') ?? data_get($log->old_values, 'user_id');
                if ($newUserId) {
                    $ids = [(int) $newUserId];
                }
        }

        return array_values(array_unique(array_filter(array_map('intval', $ids))));
    }

    private function ticketUserIds(int $ticketId): array
    {
        if (!$ticketId) {
            return [];
        }

        $ticket = DB::table('tickets')->where('id', $ticketId)->first();
        if (!$ticket) {
            return [];
        }

        return array_merge(
            [$ticket->user_id],
            DB::table('ticket_staff')->where('ticket_id', $ticketId)->pluck('user_id')->all(),
        );
    }

    private function workOrderUserIds(int $workOrderId): array
    {
        if (!$workOrderId) {
            return [];
        }

        $workOrder = DB::table('work_orders')->where('id', $workOrderId)->first();
        if (!$workOrder) {
            return [];
        }

        return array_merge(
            [$workOrder->assigned_to],
            DB::table('work_order_staff')->where('work_order_id', $workOrderId)->pluck('user_id')->all(),
            $this->ticketUserIds((int) ($workOrder->ticket_id ?? 0)),
        );
    }

    private function formPermintaanUserIds(int $formId): array
    {
        $form = DB::table('form_permintaan')->where('id', $formId)->first();
        if (!$form) {
            return [];
        }

        return array_merge([$form->user_id], $this->ticketUserIds((int) ($form->ticket_id ?? 0)));
    }

    private function formatNotification(ActivityLog $log, ?User $recipient = null): array
    {
        return [
            'id' => $log->id,
            'title' => $this->titleFor($log),
            'body' => $log->description,
            'module' => $log->module,
            'action' => $log->action,
            'url' => $this->urlFor($log, $recipient),
            'tag' => "activity-{$log->id}",
            'created_at' => optional($log->created_at)->toISOString(),
            'actor' => $log->user ? [
                'id' => $log->user->id,
                'name' => $log->user->name,
            ] : null,
        ];
    }

    private function titleFor(ActivityLog $log): string
    {
        $action = match ($log->action) {
            'created' => 'Dibuat',
            'updated' => 'Diperbarui',
            'deleted' => 'Dihapus',
            default => ucfirst(str_replace('_', ' ', $log->action)),
        };

        return "{$action}: " . ucfirst(str_replace('-', ' ', $log->module));
    }

    private function urlFor(ActivityLog $log, ?User $recipient = null): string
    {
        $id = (int) $log->model_id;
        $useAdminRoutes = !$recipient || $recipient->can('system-admin-panel-access');

        if (!$useAdminRoutes) {
            return match ($log->model_type) {
                \App\Models\Ticket::class => $this->ticketUrl($id, false),
                \App\Models\TicketReply::class => $this->ticketReplyUrl($id, false),
                \App\Models\TicketAttachment::class => $this->ticketAttachmentUrl($id, false),
                \App\Models\FormPermintaan::class => "/form-permintaan/{$id}",
                \App\Models\FormPermintaanItem::class => $this->formPermintaanItemUrl($id, false),
                \App\Models\FormPermintaanAttachment::class => $this->formPermintaanAttachmentUrl($id, false),
                \App\Models\DailyRecord::class => "/daily-record/{$id}",
                default => '/',
            };
        }

        return match ($log->model_type) {
            \App\Models\Ticket::class => $this->ticketUrl($id, true),
            \App\Models\TicketReply::class => $this->ticketReplyUrl($id, true),
            \App\Models\TicketAttachment::class => $this->ticketAttachmentUrl($id, true),
            \App\Models\WorkOrder::class => "/admin/work-order/{$id}",
            \App\Models\WorkReport::class => "/admin/work-report/{$id}",
            \App\Models\FormPermintaan::class => "/admin/form-permintaan/{$id}",
            \App\Models\FormPermintaanItem::class => $this->formPermintaanItemUrl($id, true),
            \App\Models\FormPermintaanAttachment::class => $this->formPermintaanAttachmentUrl($id, true),
            \App\Models\DailyRecord::class => "/admin/daily-record/{$id}",
            default => '/admin/dashboard',
        };
    }

    private function ticketUrl(int $ticketId, bool $useAdminRoutes): string
    {
        if ($useAdminRoutes) {
            return "/admin/ticket/{$ticketId}";
        }

        $code = DB::table('tickets')->where('id', $ticketId)->value('code');
        return $code ? '/ticket/' . rawurlencode($code) : '/tickets';
    }

    private function ticketReplyUrl(int $replyId, bool $useAdminRoutes): string
    {
        $ticketId = DB::table('ticket_replies')->where('id', $replyId)->value('ticket_id');
        return $ticketId ? $this->ticketUrl((int) $ticketId, $useAdminRoutes) : ($useAdminRoutes ? '/admin/tickets' : '/tickets');
    }

    private function ticketAttachmentUrl(int $attachmentId, bool $useAdminRoutes): string
    {
        $ticketId = DB::table('ticket_attachments')->where('id', $attachmentId)->value('ticket_id');
        return $ticketId ? $this->ticketUrl((int) $ticketId, $useAdminRoutes) : ($useAdminRoutes ? '/admin/tickets' : '/tickets');
    }

    private function formPermintaanItemUrl(int $itemId, bool $useAdminRoutes): string
    {
        $formId = DB::table('form_permintaan_items')->where('id', $itemId)->value('form_permintaan_id');
        return $this->formPermintaanUrl((int) $formId, $useAdminRoutes);
    }

    private function formPermintaanAttachmentUrl(int $attachmentId, bool $useAdminRoutes): string
    {
        $formId = DB::table('form_permintaan_attachments')->where('id', $attachmentId)->value('form_permintaan_id');
        return $this->formPermintaanUrl((int) $formId, $useAdminRoutes);
    }

    private function formPermintaanUrl(int $formId, bool $useAdminRoutes): string
    {
        if (!$formId) {
            return $useAdminRoutes ? '/admin/form-permintaan' : '/form-permintaan';
        }

        return $useAdminRoutes ? "/admin/form-permintaan/{$formId}" : "/form-permintaan/{$formId}";
    }

    private function hasVapidConfig(): bool
    {
        return filled(config('services.webpush.public_key'))
            && filled(config('services.webpush.private_key'))
            && filled(config('services.webpush.subject'));
    }
}
