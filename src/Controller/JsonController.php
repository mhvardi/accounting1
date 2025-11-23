<?php
namespace App\Controller;

use App\Core\Auth;
use App\Core\Database;
use PDO;
use PDOException;

class JsonController
{
    private function ensureAuth(): void
    {
        if (!Auth::check()) {
            header('Location: /login');
            exit;
        }
    }

    private function respond(array $payload): void
    {
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
    }

    private function servers(PDO $pdo): array
    {
        return $pdo->query("SELECT * FROM servers ORDER BY id DESC")->fetchAll();
    }

    private function services(PDO $pdo): array
    {
        return $pdo->query("SELECT s.*, c.name AS customer_name, c.id AS customer_id, p.name AS product_name, p.type AS product_type, pc.slug AS category_slug FROM service_instances s LEFT JOIN customers c ON c.id = s.customer_id LEFT JOIN products p ON p.id = s.product_id LEFT JOIN product_categories pc ON pc.id = s.category_id ORDER BY s.id DESC")->fetchAll();
    }

    private function attachServerRelations(array $servers, array $services): array
    {
        $map = [];
        foreach ($servers as $server) {
            $map[$server['id']] = [
                'server' => $server,
                'services' => [],
                'customers' => [],
            ];
        }

        foreach ($services as $service) {
            $meta = json_decode($service['meta_json'] ?? '', true) ?: [];
            $serverId = (int)($meta['panel']['server_id'] ?? 0);
            if ($serverId > 0 && isset($map[$serverId])) {
                $map[$serverId]['services'][] = [
                    'service_id' => (int)$service['id'],
                    'customer_id' => (int)($service['customer_id'] ?? 0),
                    'customer_name' => $service['customer_name'] ?? '',
                    'domain' => $meta['domain'] ?? '',
                    'status' => $service['status'] ?? '',
                ];
                $map[$serverId]['customers'][$service['customer_id']] = $service['customer_name'];
            }
        }

        return $map;
    }

    public function directAdminConfig(): void
    {
        $this->ensureAuth();
        try {
            $pdo = Database::connection();
            $servers = $this->servers($pdo);
            $services = $this->services($pdo);
            $relations = $this->attachServerRelations($servers, $services);
        } catch (PDOException $e) {
            http_response_code(500);
            $this->respond(['error' => 'خطا در بارگذاری اطلاعات سرورها: ' . $e->getMessage()]);
            return;
        }

        $payload = [
            'provider' => 'DirectAdmin',
            'synced_at' => date('c'),
            'servers' => [],
        ];

        foreach ($relations as $map) {
            $srv = $map['server'];
            $payload['servers'][] = [
                'id' => (int)$srv['id'],
                'name' => $srv['name'],
                'hostname' => $srv['hostname'],
                'ip' => $srv['ip'],
                'port' => (int)$srv['port'],
                'ssl' => (bool)$srv['ssl'],
                'module' => $srv['module'],
                'datacenter' => $srv['datacenter'],
                'allocated_ips' => array_values(array_filter(array_map('trim', explode("\n", $srv['allocated_ips'] ?? '')))),
                'nameservers' => array_values(array_filter([$srv['ns1'] ?? '', $srv['ns2'] ?? '', $srv['ns3'] ?? '', $srv['ns4'] ?? '', $srv['ns5'] ?? ''])),
                'customers' => array_values(array_map(static fn($name, $cid) => ['id' => (int)$cid, 'name' => $name], $map['customers'], array_keys($map['customers']))),
                'services' => $map['services'],
            ];
        }

        $this->respond($payload);
    }

    public function directAdminApiMap(): void
    {
        $this->ensureAuth();
        try {
            $pdo = Database::connection();
            $servers = $this->servers($pdo);
            $services = $this->services($pdo);
        } catch (PDOException $e) {
            http_response_code(500);
            $this->respond(['error' => 'خطا در نقشه API: ' . $e->getMessage()]);
            return;
        }

        $paths = [
            '/servers' => ['method' => 'GET', 'description' => 'لیست سرورهای متصل به داشبورد و وضعیت سلامت آنها'],
            '/servers/check' => ['method' => 'GET', 'description' => 'بررسی سلامت یک سرور DirectAdmin بر اساس شناسه'],
            '/services' => ['method' => 'GET', 'description' => 'تمام خدمات شامل دامنه، هاست و سرویس‌های سئو متصل به مشتریان'],
            '/services/update' => ['method' => 'POST', 'description' => 'بروزرسانی دسترسی، دوره، مبالغ و اتصال سرور/دامنه به سرویس'],
        ];

        $payload = [
            'title' => 'DirectAdmin Reseller Map',
            'generated_at' => date('c'),
            'paths' => $paths,
            'servers' => array_map(static function ($srv) {
                return [
                    'id' => (int)$srv['id'],
                    'name' => $srv['name'],
                    'hostname' => $srv['hostname'],
                    'profile' => '/servers',
                ];
            }, $servers),
            'services' => array_map(static function ($service) {
                $meta = json_decode($service['meta_json'] ?? '', true) ?: [];
                return [
                    'id' => (int)$service['id'],
                    'customer' => [
                        'id' => (int)($service['customer_id'] ?? 0),
                        'name' => $service['customer_name'] ?? '',
                        'profile' => '/customers/profile?id=' . (int)($service['customer_id'] ?? 0),
                    ],
                    'domain' => $meta['domain'] ?? '',
                    'server_id' => (int)($meta['panel']['server_id'] ?? 0),
                    'status' => $service['status'],
                ];
            }, $services),
        ];

        $this->respond($payload);
    }

    public function directAdminSwagger(): void
    {
        $this->ensureAuth();
        try {
            $pdo = Database::connection();
            $servers = $this->servers($pdo);
            $services = $this->services($pdo);
        } catch (PDOException $e) {
            http_response_code(500);
            $this->respond(['error' => 'خطا در ساخت Swagger: ' . $e->getMessage()]);
            return;
        }

        $payload = [
            'openapi' => '3.1.0',
            'info' => [
                'title' => 'Accounting Panel - DirectAdmin Connector',
                'version' => '1.0.0',
                'description' => 'اتصال سرورها و دامنه‌ها به پروفایل مشتریان برای مدیریت یکپارچه',
            ],
            'paths' => [
                '/json/directadmin/reseller-config.json' => ['get' => ['summary' => 'پیکربندی سرورهای DirectAdmin و مشتریان متصل']],
                '/json/directadmin/directadmin_reseller_api_map.json' => ['get' => ['summary' => 'نقشه راه API برای سرور و سرویس']],
                '/json/domin/openapi (1).json' => ['get' => ['summary' => 'داکیومنت دامنه‌های مشتریان']],
            ],
            'components' => [
                'servers' => array_map(static function ($srv) {
                    return [
                        'id' => (int)$srv['id'],
                        'name' => $srv['name'],
                        'hostname' => $srv['hostname'],
                        'port' => (int)$srv['port'],
                    ];
                }, $servers),
                'services' => array_map(static function ($service) {
                    $meta = json_decode($service['meta_json'] ?? '', true) ?: [];
                    return [
                        'id' => (int)$service['id'],
                        'domain' => $meta['domain'] ?? '',
                        'customer_name' => $service['customer_name'] ?? '',
                        'server_id' => (int)($meta['panel']['server_id'] ?? 0),
                    ];
                }, $services),
            ],
        ];

        $this->respond($payload);
    }

    public function domainOpenApi(): void
    {
        $this->ensureAuth();
        try {
            $pdo = Database::connection();
            $services = $this->services($pdo);
            $servers = $this->servers($pdo);
            $serverMap = [];
            foreach ($servers as $srv) {
                $serverMap[$srv['id']] = $srv['name'];
            }
        } catch (PDOException $e) {
            http_response_code(500);
            $this->respond(['error' => 'خطا در ساخت دامنه‌ها: ' . $e->getMessage()]);
            return;
        }

        $domains = [];
        foreach ($services as $service) {
            $meta = json_decode($service['meta_json'] ?? '', true) ?: [];
            $type = $service['product_type'] ?: ($meta['product_type'] ?? $service['category_slug'] ?? '');
            if ($type !== 'domain') {
                continue;
            }
            $serverId = (int)($meta['panel']['server_id'] ?? 0);
            $domains[] = [
                'id' => (int)$service['id'],
                'customer' => [
                    'id' => (int)($service['customer_id'] ?? 0),
                    'name' => $service['customer_name'] ?? '',
                    'profile' => '/customers/profile?id=' . (int)($service['customer_id'] ?? 0),
                ],
                'domain' => $meta['domain'] ?? '',
                'dns' => $meta['domain_dns'] ?? [],
                'server' => $serverId ? ['id' => $serverId, 'name' => $serverMap[$serverId] ?? null] : null,
                'billing_cycle' => $service['billing_cycle'] ?? null,
                'next_due_date' => $service['next_due_date'],
            ];
        }

        $payload = [
            'openapi' => '3.1.0',
            'info' => [
                'title' => 'دامنه‌های متصل به مشتریان',
                'version' => '1.0.0',
            ],
            'domains' => $domains,
            'meta' => [
                'count' => count($domains),
                'generated_at' => date('c'),
            ],
        ];

        $this->respond($payload);
    }
}
