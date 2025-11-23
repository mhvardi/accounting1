<?php
namespace App\Service;

use App\Core\Database;
use PDO;

class NotificationService
{
    private PDO $pdo;

    public function __construct()
    {
        $this->pdo = Database::connection();
    }

    public function create(int $userId, ?int $customerId, string $type, string $severity, string $title, string $body, array $meta = []): void
    {
        $stmt = $this->pdo->prepare('INSERT INTO notifications (user_id, customer_id, type, severity, title, body, meta_json, created_at) VALUES (?,?,?,?,?,?,?,?)');
        $stmt->execute([
            $userId,
            $customerId,
            $type,
            $severity,
            $title,
            $body,
            json_encode($meta),
            date('Y-m-d H:i:s'),
        ]);
    }

    public function markRead(int $notificationId): void
    {
        $stmt = $this->pdo->prepare('UPDATE notifications SET read_at = ? WHERE id = ?');
        $stmt->execute([date('Y-m-d H:i:s'), $notificationId]);
    }

    public function listForUser(int $userId): array
    {
        $stmt = $this->pdo->prepare('SELECT * FROM notifications WHERE user_id = ? ORDER BY created_at DESC');
        $stmt->execute([$userId]);
        return $stmt->fetchAll();
    }
}
