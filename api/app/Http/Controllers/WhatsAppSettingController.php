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
            new Middleware(PermissionMiddleware::using(['whatsapp-setting-list']), only: ['index', 'getTemplates', 'getPlaceholders']),
            new Middleware(PermissionMiddleware::using(['whatsapp-setting-edit']), only: ['updateSettings', 'updateTemplate']),
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
                'delay' => 'sometimes|string',
            ]);

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
}
