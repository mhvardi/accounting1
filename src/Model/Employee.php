<?php
namespace App\Model;

use App\Core\Database;

class Employee
{
    public int $id;
    public string $full_name;
    public int $base_salary = 0;
    public int $active = 1;
    public string $compensation_type = 'fixed';
    public string $commission_mode = 'none';
    public string $commission_scope = 'self';
    public int $commission_percent = 0;
    public ?string $commission_config_json = null;
    public ?string $effective_from = null;

    public static function all(): array
    {
        $pdo = Database::connection();
        $stmt = $pdo->query("SELECT * FROM employees ORDER BY id DESC");
        return $stmt->fetchAll();
    }

    public static function find(int $id): ?self
    {
        $pdo = Database::connection();
        $stmt = $pdo->prepare("SELECT * FROM employees WHERE id = ?");
        $stmt->execute([$id]);
        $row = $stmt->fetch();
        if (!$row) {
            return null;
        }
        $e = new self();
        foreach ($row as $k => $v) {
            if (property_exists($e, $k)) {
                $e->$k = $v;
            }
        }
        return $e;
    }

    public function getCommissionConfig(): array
    {
        if (!$this->commission_config_json) return [];
        $data = json_decode($this->commission_config_json, true);
        return is_array($data) ? $data : [];
    }
}
