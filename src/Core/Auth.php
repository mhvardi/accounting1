<?php
namespace App\Core;

class Auth
{
    public static function check(): bool
    {
        return !empty($_SESSION['user']);
    }

    public static function user(): ?array
    {
        return $_SESSION['user'] ?? null;
    }

    public static function attempt(string $identifier, string $password): bool
    {
        $identifier = trim($identifier);
        if ($identifier === '' || $password === '') {
            return false;
        }

        try {
            $pdo = Database::connection();
            $stmt = $pdo->prepare(
                "SELECT id, email, username, phone, password_hash, role FROM users WHERE email = :id OR username = :id OR phone = :id LIMIT 1"
            );
            $stmt->execute([':id' => $identifier]);
            $user = $stmt->fetch();
            if (!$user) {
                return false;
            }

            $hash = (string)$user['password_hash'];
            $isValid = false;

            if (strlen($hash) >= 60 && password_verify($password, $hash)) {
                $isValid = true;
            } elseif (strlen($hash) === 64 && hash_equals($hash, hash('sha256', $password))) {
                $isValid = true;
            }

            if ($isValid) {
                $_SESSION['user'] = [
                    'id'       => $user['id'],
                    'email'    => $user['email'],
                    'username' => $user['username'],
                    'phone'    => $user['phone'],
                    'role'     => $user['role'],
                ];
                return true;
            }
        } catch (\Throwable $e) {
            return false;
        }

        return false;
    }

    public static function logout(): void
    {
        $_SESSION = [];
        session_destroy();
    }
}
