<?php

namespace App\Services;

use App\Models\WaLog;
use App\Models\Setting;
use Illuminate\Support\Facades\Http;

class WhatsAppService
{
    /**
     * Send WhatsApp message with logging and dynamic template formatting.
     */
    public static function send(string $phone, string $message, string $type = 'PENGUMUMAN', ?int $userId = null, ?string $recipientName = null): array
    {
        $cleanPhone = preg_replace('/[^0-9]/', '', $phone);
        if (str_starts_with($cleanPhone, '0')) {
            $cleanPhone = '62' . substr($cleanPhone, 1);
        }

        $baseUrl = rtrim(env('WA_GATEWAY_BASE_URL', 'http://127.0.0.1:3000'), '/');
        $waToken = env('WA_GATEWAY_TOKEN', 'rapiuw-admin-secret-2026');

        $status = 'TERKIRIM';
        $responsePayload = null;

        try {
            $res = Http::timeout(5)->withHeaders([
                'x-admin-token' => $waToken,
                'Authorization' => "Bearer {$waToken}",
            ])->post("{$baseUrl}/api/send", [
                'target' => $cleanPhone,
                'phone' => $cleanPhone,
                'message' => $message,
            ]);

            $responsePayload = $res->body();
            if (!$res->successful()) {
                $status = 'GAGAL';
            }
        } catch (\Exception $e) {
            $status = 'GAGAL';
            $responsePayload = $e->getMessage();
        }

        $log = WaLog::create([
            'recipient_phone' => $cleanPhone,
            'recipient_name' => $recipientName,
            'user_id' => $userId,
            'type' => $type,
            'message' => $message,
            'status' => $status,
            'response_payload' => $responsePayload,
        ]);

        return [
            'success' => $status === 'TERKIRIM',
            'status' => $status,
            'log_id' => $log->id,
        ];
    }

    /**
     * Format dynamic template with variables.
     */
    public static function formatTemplate(string $templateKey, array $vars, string $defaultTemplate): string
    {
        $tpl = Setting::get($templateKey, $defaultTemplate);
        foreach ($vars as $k => $v) {
            $tpl = str_replace('{' . $k . '}', (string) $v, $tpl);
        }
        return $tpl;
    }
}
