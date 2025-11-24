<?php
namespace App\Service;

use App\Core\Database;
use PDO;

class SmsService
{
    private string $apiKey;
    private string $baseUrl;
    private PDO $pdo;

    public function __construct()
    {
        $cfg = include __DIR__ . '/../../config/config.php';
        $this->apiKey = (string)($cfg['sms_api_key'] ?? '');
        $this->baseUrl = rtrim((string)($cfg['sms_api_base'] ?? 'https://api.limosms.com'), '/');
        $this->pdo = Database::connection();
    }

    private function request(string $endpoint, array $payload = [], string $method = 'POST'): array
    {
        $url = $this->baseUrl . '/' . ltrim($endpoint, '/');
        $headers = [
            'Accept: application/json',
            'Content-Type: application/json',
        ];
        if ($this->apiKey !== '') {
            $headers[] = 'Authorization: Bearer ' . $this->apiKey;
        }

        $ch = curl_init();
        $opts = [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_CONNECTTIMEOUT => 10,
            CURLOPT_TIMEOUT => 30,
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_SSL_VERIFYHOST => false,
            CURLOPT_HTTPHEADER => $headers,
        ];

        if (strtoupper($method) === 'GET') {
            if (!empty($payload)) {
                $url .= (str_contains($url, '?') ? '&' : '?') . http_build_query($payload);
            }
            $opts[CURLOPT_URL] = $url;
        } else {
            $opts[CURLOPT_URL] = $url;
            $opts[CURLOPT_CUSTOMREQUEST] = $method;
            $opts[CURLOPT_POSTFIELDS] = json_encode($payload);
        }

        curl_setopt_array($ch, $opts);
        $responseBody = curl_exec($ch);
        $lastError = curl_error($ch);
        $httpCode = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        $decoded = json_decode((string)$responseBody, true);
        $success = ($lastError === '' && $httpCode >= 200 && $httpCode < 300);
        $message = $lastError ?: 'HTTP ' . $httpCode;

        return [
            'success' => $success,
            'code' => $httpCode,
            'message' => $message,
            'data' => $decoded ?? $responseBody,
        ];
    }

    private function log(
        string $direction,
        string $type,
        ?string $phone,
        ?string $message,
        string $status,
        ?string $providerId = null,
        array $meta = [],
        ?int $customerId = null
    ): void {
        try {
            $stmt = $this->pdo->prepare('INSERT INTO sms_logs (direction, sms_type, customer_id, phone, category, message, status, provider_message_id, meta_json, created_at) VALUES (?,?,?,?,?,?,?,?,?,?)');
            $stmt->execute([
                $direction,
                $type,
                $customerId,
                $phone,
                $meta['category'] ?? null,
                $message,
                $status,
                $providerId,
                json_encode($meta),
                date('Y-m-d H:i:s'),
            ]);
        } catch (\Throwable $e) {
            // ignore logging issues
        }
    }

    public function sendStandard(array $phones, string $text, ?string $category = null, ?string $scheduleAt = null, ?int $customerId = null): array
    {
        $payload = [
            'to' => array_values(array_unique($phones)),
            'text' => $text,
        ];
        if ($category) {
            $payload['category'] = $category;
        }
        if ($scheduleAt) {
            $payload['schedule_at'] = $scheduleAt;
        }

        $res = $this->request('/sms/send', $payload, 'POST');
        $providerId = is_array($res['data'] ?? null) ? ($res['data']['message_id'] ?? null) : null;

        foreach ($phones as $phone) {
            $this->log('outbound', 'standard', $phone, $text, $res['success'] ? 'sent' : 'failed', $providerId, ['category' => $category], $customerId);
        }

        return $res;
    }

    public function sendCorrelated(array $messages, ?string $category = null, ?string $scheduleAt = null, ?int $customerId = null): array
    {
        $payload = [
            'messages' => $messages,
        ];
        if ($category) {
            $payload['category'] = $category;
        }
        if ($scheduleAt) {
            $payload['schedule_at'] = $scheduleAt;
        }

        $res = $this->request('/sms/send-correlated', $payload, 'POST');
        $providerId = is_array($res['data'] ?? null) ? ($res['data']['batch_id'] ?? null) : null;

        foreach ($messages as $msg) {
            $this->log('outbound', 'correlated', $msg['to'] ?? null, $msg['text'] ?? null, $res['success'] ? 'sent' : 'failed', $providerId, ['category' => $category, 'correlation_id' => $msg['correlation_id'] ?? null], $customerId);
        }

        return $res;
    }

    public function sendPattern(string $patternCode, string $receptor, array $values, ?string $category = null, ?int $customerId = null): array
    {
        $payload = [
            'pattern_code' => $patternCode,
            'receptor' => $receptor,
            'values' => $values,
        ];
        if ($category) {
            $payload['category'] = $category;
        }

        $res = $this->request('/sms/pattern', $payload, 'POST');
        $providerId = is_array($res['data'] ?? null) ? ($res['data']['message_id'] ?? null) : null;
        $this->log('outbound', 'pattern', $receptor, json_encode($values), $res['success'] ? 'sent' : 'failed', $providerId, ['category' => $category, 'pattern_code' => $patternCode], $customerId);

        return $res;
    }

    public function sendVoiceOtp(string $receptor, string $code, ?string $category = null, ?int $customerId = null): array
    {
        $payload = [
            'receptor' => $receptor,
            'code' => $code,
        ];
        if ($category) {
            $payload['category'] = $category;
        }

        $res = $this->request('/sms/voice-otp', $payload, 'POST');
        $providerId = is_array($res['data'] ?? null) ? ($res['data']['message_id'] ?? null) : null;
        $this->log('outbound', 'voice_otp', $receptor, $code, $res['success'] ? 'sent' : 'failed', $providerId, ['category' => $category], $customerId);

        return $res;
    }

    public function authStatus(): array
    {
        return $this->request('/auth/status', [], 'GET');
    }

    public function fetchInbound(?string $fromDate = null): array
    {
        $payload = [];
        if ($fromDate) {
            $payload['from_date'] = $fromDate;
        }
        $res = $this->request('/sms/inbound', $payload, 'GET');

        if (!empty($res['data']) && is_array($res['data'])) {
            foreach ($res['data'] as $item) {
                $this->log('inbound', 'inbound', $item['from'] ?? null, $item['text'] ?? null, $item['status'] ?? 'received', $item['message_id'] ?? null, $item);
            }
        }

        return $res;
    }

    public function deliveryStatus(string $messageId): array
    {
        $res = $this->request('/sms/delivery-status', ['message_id' => $messageId], 'GET');
        $this->log('outbound', 'status', null, null, $res['success'] ? 'queried' : 'failed', $messageId, ['status_response' => $res['data'] ?? null]);
        return $res;
    }

    public function balance(): array
    {
        return $this->request('/sms/balance', [], 'GET');
    }

    public function tariff(): array
    {
        return $this->request('/sms/tariff', [], 'GET');
    }

    public function cancelScheduled(string $messageId): array
    {
        $res = $this->request('/sms/schedule/cancel', ['message_id' => $messageId], 'POST');
        $this->log('outbound', 'cancel', null, null, $res['success'] ? 'cancelled' : 'failed', $messageId, ['action' => 'cancel_schedule']);
        return $res;
    }
}
