<?php
namespace App\Service;

use App\Core\Auth;
use App\Core\Database;
use PDO;
use PDOException;

class DomainService
{
    private DomainResellerClient $client;
    private PDO $pdo;
    private CacheService $cache;
    private NotificationService $notifier;
    private ?int $actorId;

    public function __construct(?int $actorId = null)
    {
        $this->pdo = Database::connection();
        $this->cache = new CacheService();
        $this->notifier = new NotificationService();
        $this->actorId = $actorId ?? (Auth::user()['id'] ?? null);

        $cfg = $this->loadConfig();
        $this->client = new DomainResellerClient($cfg['base_url'], $cfg['api_key']);
    }

    private function loadConfig(): array
    {
        $configFile = __DIR__ . '/../../config/config.php';
        $cfg = file_exists($configFile) ? include $configFile : [];
        return [
            'base_url' => rtrim($cfg['domain_reseller_url'] ?? 'https://reseller.local/api', '/'),
            'api_key'  => $cfg['domain_reseller_api_key'] ?? null,
        ];
    }

    private function logAction(?int $customerId, ?int $serviceId, string $action, array $request, $response, bool $success, string $message): void
    {
        try {
            $stmt = $this->pdo->prepare('INSERT INTO sync_logs (type, customer_id, service_id, action, request_json, response_json, success, message, created_at) VALUES (?,?,?,?,?,?,?,?,?)');
            $stmt->execute([
                'domain',
                $customerId,
                $serviceId,
                $action,
                json_encode($request),
                json_encode($response),
                $success ? 1 : 0,
                $message,
                date('Y-m-d H:i:s'),
            ]);

            $audit = $this->pdo->prepare('INSERT INTO audit_logs (actor_user_id, customer_id, entity_type, entity_id, action, before_json, after_json, request_json, response_json, success, message, created_at) VALUES (?,?,?,?,?,?,?,?,?,?,?,?)');
            $audit->execute([
                $this->actorId,
                $customerId,
                'domain',
                $serviceId,
                $action,
                null,
                null,
                json_encode($request),
                json_encode($response),
                $success ? 1 : 0,
                $message,
                date('Y-m-d H:i:s'),
            ]);
        } catch (PDOException $e) {
            // ignore logging errors
        }
    }

    private function persistDomain(int $customerId, string $domain, array $remoteData): array
    {
        $stmt = $this->pdo->prepare('SELECT * FROM domains WHERE domain_name = ?');
        $stmt->execute([$domain]);
        $existing = $stmt->fetch();

        $status = $remoteData['status'] ?? $remoteData['state'] ?? ($existing['status'] ?? 'pending');
        $expiresAt = null;
        if (!empty($remoteData['expires_at'])) {
            $expiresAt = date('Y-m-d', strtotime($remoteData['expires_at']));
        } elseif (!empty($remoteData['expiry'])) {
            $expiresAt = date('Y-m-d', strtotime($remoteData['expiry']));
        } else {
            $expiresAt = $existing['expires_at'] ?? null;
        }

        $existingNs = $existing && !empty($existing['nameservers_json']) ? (json_decode($existing['nameservers_json'], true) ?: []) : [];
        $existingDns = $existing && !empty($existing['dns_records_json']) ? (json_decode($existing['dns_records_json'], true) ?: null) : null;
        $existingWhois = $existing && !empty($existing['whois_json']) ? (json_decode($existing['whois_json'], true) ?: null) : null;
        if (array_key_exists('nameservers', $remoteData)) {
            $nameservers = $remoteData['nameservers'];
        } elseif (array_key_exists('ns', $remoteData)) {
            $nameservers = $remoteData['ns'];
        } else {
            $nameservers = $existingNs;
        }

        $lockStatus = $remoteData['lock_status'] ?? ($remoteData['locked'] ?? ($existing['lock_status'] ?? 'open'));
        $dnsRecords = array_key_exists('dns_records', $remoteData) ? $remoteData['dns_records'] : $existingDns;
        $whois = array_key_exists('whois', $remoteData) ? $remoteData['whois'] : $existingWhois;
        $remoteId = $remoteData['id'] ?? $remoteData['remote_id'] ?? ($existing['remote_id'] ?? null);

        $payload = [
            'customer_id'    => $customerId ?: ($existing['customer_id'] ?? null),
            'reseller_provider' => $remoteData['provider'] ?? ($existing['reseller_provider'] ?? null),
            'domain_name'    => $domain,
            'status'         => $status,
            'expires_at'     => $expiresAt,
            'auto_renew'     => (int)($remoteData['auto_renew'] ?? ($existing['auto_renew'] ?? 0)),
            'nameservers_json' => json_encode($nameservers),
            'lock_status'    => $lockStatus,
            'dns_records_json' => $dnsRecords ? json_encode($dnsRecords) : ($existing['dns_records_json'] ?? null),
            'whois_json'     => $whois ? json_encode($whois) : ($existing['whois_json'] ?? null),
            'last_sync_at'   => date('Y-m-d H:i:s'),
            'remote_id'      => $remoteId,
            'meta_json'      => json_encode($remoteData),
        ];

        $statusChanged = $existing && ($existing['status'] ?? '') !== $status;
        $expiryChanged = $existing && ($existing['expires_at'] ?? '') !== (string)$expiresAt;

        if ($existing) {
            $sql = 'UPDATE domains SET customer_id=:customer_id, reseller_provider=:reseller_provider, status=:status, expires_at=:expires_at, auto_renew=:auto_renew, nameservers_json=:nameservers_json, lock_status=:lock_status, dns_records_json=:dns_records_json, whois_json=:whois_json, last_sync_at=:last_sync_at, remote_id=:remote_id, meta_json=:meta_json, updated_at=:updated_at WHERE id=:id';
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([
                ...$payload,
                'updated_at' => date('Y-m-d H:i:s'),
                'id' => $existing['id'],
            ]);
            $recordId = (int)$existing['id'];
        } else {
            $sql = 'INSERT INTO domains (customer_id, reseller_provider, domain_name, status, expires_at, auto_renew, nameservers_json, lock_status, dns_records_json, whois_json, last_sync_at, remote_id, meta_json, created_at, updated_at) VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)';
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([
                $payload['customer_id'],
                $payload['reseller_provider'],
                $payload['domain_name'],
                $payload['status'],
                $payload['expires_at'],
                $payload['auto_renew'],
                $payload['nameservers_json'],
                $payload['lock_status'],
                $payload['dns_records_json'],
                $payload['whois_json'],
                $payload['last_sync_at'],
                $payload['remote_id'],
                $payload['meta_json'],
                date('Y-m-d H:i:s'),
                date('Y-m-d H:i:s'),
            ]);
            $recordId = (int)$this->pdo->lastInsertId();
        }

        if ($statusChanged || $expiryChanged) {
            $this->notifyChanges($payload, $statusChanged, $expiryChanged);
        }

        $this->cache->delete('domains_all');
        return ['id' => $recordId, 'status' => $status, 'expires_at' => $expiresAt, 'nameservers' => $nameservers];
    }

    private function notifyChanges(array $payload, bool $statusChanged, bool $expiryChanged): void
    {
        $title = 'بروزرسانی دامنه ' . ($payload['domain_name'] ?? '');
        $bodyParts = [];
        if ($statusChanged) {
            $bodyParts[] = 'وضعیت دامنه تغییر کرد به: ' . ($payload['status'] ?? '');
        }
        if ($expiryChanged && !empty($payload['expires_at'])) {
            $bodyParts[] = 'تاریخ انقضا: ' . $payload['expires_at'];
        }
        $this->notifier->create($this->actorId ?? 0, $payload['customer_id'] ?? null, 'domain', 'info', $title, implode(' - ', $bodyParts), $payload);
    }

    public function perform(array $serviceRow, string $action, array $payload = []): array
    {
        $meta = json_decode($serviceRow['meta_json'] ?? '', true) ?: [];
        $domain = trim($payload['domain'] ?? $meta['domain'] ?? $meta['domain_name'] ?? '');
        if ($domain === '') {
            return ['success' => false, 'message' => 'دامنه در سرویس مشخص نشده است'];
        }

        $response = ['success' => false, 'message' => 'اقدامی انجام نشد'];
        $statusHint = null;
        switch ($action) {
            case 'register':
                $years = (int)($payload['years'] ?? 1);
                $contact = $payload['contact'] ?? ($meta['contact'] ?? []);
                $ns = $payload['nameservers'] ?? ($meta['nameservers'] ?? []);
                $response = $this->client->registerDomain($domain, $years, $contact, $ns);
                $statusHint = 'pending';
                break;
            case 'renew':
                $years = (int)($payload['years'] ?? 1);
                $response = $this->client->renewDomain($domain, $years);
                $statusHint = 'active';
                break;
            case 'transfer':
                $authCode = $payload['auth_code'] ?? $payload['authCode'] ?? '';
                if ($authCode === '') {
                    return ['success' => false, 'message' => 'کد انتقال وارد نشده است'];
                }
                $ns = $payload['nameservers'] ?? ($meta['nameservers'] ?? []);
                $response = $this->client->transferDomain($domain, $authCode, $ns);
                $statusHint = 'transfering';
                break;
            case 'suspend':
                $response = $this->client->suspendDomain($domain);
                $statusHint = 'suspended';
                break;
            case 'unsuspend':
                $response = $this->client->unsuspendDomain($domain);
                $statusHint = 'active';
                break;
            case 'delete':
                $response = $this->client->deleteDomain($domain);
                $statusHint = 'expired';
                break;
            case 'dns_get':
                $response = $this->client->getDNSRecords($domain);
                break;
            case 'dns_set':
                $record = $payload['record'] ?? [];
                $response = $this->client->setDNSRecord($domain, $record);
                break;
            case 'dns_delete':
                $recordId = $payload['record_id'] ?? '';
                $response = $this->client->deleteDNSRecord($domain, $recordId);
                break;
            case 'whois':
                $response = $this->client->whoisLookup($domain);
                break;
            case 'sync':
            default:
                $response = $this->client->getDomainInfo($domain);
                break;
        }

        $data = is_array($response['data'] ?? null) ? $response['data'] : [];
        if ($response['success'] && empty($data) && $statusHint !== null) {
            $data = ['status' => $statusHint];
        }
        if ($response['success'] && !empty($data)) {
            $persisted = $this->persistDomain((int)$serviceRow['customer_id'], $domain, $data);
            $response['persisted'] = $persisted;
        }

        $this->logAction((int)$serviceRow['customer_id'], (int)$serviceRow['id'], $action, $payload, $response, (bool)$response['success'], $response['message'] ?? '');
        return $response;
    }

    public function reconcile(): array
    {
        $list = $this->client->listDomains();
        if (empty($list['success'])) {
            return ['success' => false, 'message' => $list['message'] ?? 'خطا در دریافت لیست دامنه‌ها'];
        }

        $updated = 0;
        $items = is_array($list['data'] ?? null) ? $list['data'] : [];
        foreach ($items as $item) {
            if (!empty($item['domain'] ?? $item['domain_name'])) {
                $domain = $item['domain'] ?? $item['domain_name'];
                $this->persistDomain($item['customer_id'] ?? 0, $domain, $item);
                $updated++;
            }
        }

        return ['success' => true, 'message' => 'همسان‌سازی دامنه‌ها انجام شد', 'updated' => $updated];
    }
}
