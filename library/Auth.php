<?php
declare(strict_types=1);

/**
 * Session-basierte Auth.
 * Rolle 'admin' → Admin-Bereich, Rolle 'member' → Mitgliederbereich.
 */
final class Auth
{
    public static function start(): void
    {
        if (session_status() === PHP_SESSION_ACTIVE) return;
        require_once __DIR__ . '/../config/db.php';
        session_name(SESSION_NAME);
        session_set_cookie_params([
            'lifetime' => 0,
            'path'     => '/',
            'secure'   => !empty($_SERVER['HTTPS']),
            'httponly' => true,
            'samesite' => 'Lax',
        ]);
        session_start();
    }

    public static function login(string $username, string $password): bool
    {
        self::start();
        $stmt = Database::pdo()->prepare(
            'SELECT id, username, password_hash, display_name, role, active
               FROM users WHERE username = ? LIMIT 1'
        );
        $stmt->execute([$username]);
        $u = $stmt->fetch();
        if (!$u || !$u['active'] || !password_verify($password, $u['password_hash'])) {
            return false;
        }
        session_regenerate_id(true);
        $_SESSION['user'] = [
            'id'           => (int)$u['id'],
            'username'     => $u['username'],
            'display_name' => $u['display_name'] ?: $u['username'],
            'role'         => $u['role'],
        ];
        Database::pdo()->prepare('UPDATE users SET last_login_at = NOW() WHERE id = ?')->execute([$u['id']]);
        return true;
    }

    public static function logout(): void
    {
        self::start();
        $_SESSION = [];
        session_destroy();
    }

    public static function user(): ?array
    {
        self::start();
        return $_SESSION['user'] ?? null;
    }

    public static function check(): bool
    {
        return self::user() !== null;
    }

    public static function requireRole(string $role, string $loginUrl): void
    {
        $u = self::user();
        if (!$u || $u['role'] !== $role) {
            $back = $_SERVER['REQUEST_URI'] ?? '/';
            header('Location: ' . $loginUrl . '?next=' . urlencode($back));
            exit;
        }
    }
}
