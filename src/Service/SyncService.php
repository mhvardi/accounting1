<?php
namespace App\Service;

use App\Core\Database;
use PDO;

class SyncService
{
    private PDO $pdo;

    public function __construct()
    {
        $this->pdo = Database::connection();
    }

    public function syncCustomerHosting(int $customerId): void
    {
        // placeholder: iterate hosting_accounts for customer
        $stmt = $this->pdo->prepare('SELECT * FROM hosting_accounts WHERE customer_id = ?');
        $stmt->execute([$customerId]);
        foreach ($stmt->fetchAll() as $hosting) {
            $this->syncHostingAccount((int)$hosting['id']);
        }
    }

    public function syncHostingAccount(int $hostingId): void
    {
        $stmt = $this->pdo->prepare('SELECT h.*, s.* AS server_data FROM hosting_accounts h LEFT JOIN servers s ON s.id = h.server_id WHERE h.id = ?');
        $stmt->execute([$hostingId]);
        $row = $stmt->fetch();
        if (!$row) {
            return;
        }
        $client = new DirectAdminClient($row);
        $client->userUsage($row['da_username']);
    }

    public function syncCustomerDomains(int $customerId): void
    {
        $stmt = $this->pdo->prepare('SELECT * FROM domains WHERE customer_id = ?');
        $stmt->execute([$customerId]);
        foreach ($stmt->fetchAll() as $domain) {
            $this->syncDomain((int)$domain['id']);
        }
    }

    public function syncDomain(int $domainId): void
    {
        $stmt = $this->pdo->prepare('SELECT * FROM domains WHERE id = ?');
        $stmt->execute([$domainId]);
        $domain = $stmt->fetch();
        if (!$domain) {
            return;
        }
        // generic sync placeholder - would pull from domain reseller
    }

    public function pullUnsyncedDomains(): array
    {
        $stmt = $this->pdo->query("SELECT * FROM domains WHERE customer_id IS NULL");
        return $stmt->fetchAll();
    }
}
