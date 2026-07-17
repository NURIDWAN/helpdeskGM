<?php

namespace App\Http\Controllers;

use App\Helpers\ResponseHelper;
use App\Services\BrowserNotificationService;
use Illuminate\Http\Request;

class BrowserNotificationController extends Controller
{
    public function __construct(private BrowserNotificationService $browserNotificationService)
    {
    }

    public function index(Request $request)
    {
        $validated = $request->validate([
            'after_id' => ['nullable', 'integer', 'min:0'],
            'limit' => ['nullable', 'integer', 'min:1', 'max:50'],
        ]);

        $notifications = $this->browserNotificationService->notificationsFor(
            $request->user(),
            $validated['after_id'] ?? null,
            $validated['limit'] ?? 20,
        );

        return ResponseHelper::jsonResponse(true, 'Notifikasi berhasil diambil', $notifications, 200);
    }

    public function vapidPublicKey()
    {
        return ResponseHelper::jsonResponse(true, 'VAPID public key berhasil diambil', [
            'public_key' => $this->browserNotificationService->vapidPublicKey(),
        ], 200);
    }

    public function subscribe(Request $request)
    {
        $validated = $request->validate([
            'endpoint' => ['required', 'string'],
            'expirationTime' => ['nullable'],
            'keys' => ['required', 'array'],
            'keys.p256dh' => ['required', 'string'],
            'keys.auth' => ['required', 'string'],
            'contentEncoding' => ['nullable', 'string', 'in:aesgcm,aes128gcm'],
        ]);

        $subscription = $this->browserNotificationService->storeSubscription(
            $request->user(),
            $validated,
            $request->userAgent(),
        );

        return ResponseHelper::jsonResponse(true, 'Browser notification berhasil diaktifkan', [
            'id' => $subscription->id,
        ], 200);
    }

    public function unsubscribe(Request $request)
    {
        $validated = $request->validate([
            'endpoint' => ['required', 'string'],
        ]);

        $this->browserNotificationService->revokeSubscription($request->user(), $validated['endpoint']);

        return ResponseHelper::jsonResponse(true, 'Browser notification berhasil dinonaktifkan', null, 200);
    }
}
