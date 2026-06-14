<?php
declare(strict_types=1);

/**
 * PDO-Singleton für MySQL/MariaDB.
 */
final class Database
{
    private static ?PDO $pdo = null;

    public static function pdo(): PDO
    {
        if (self::$pdo !== null) {
            return self::$pdo;
        }
        require_once __DIR__ . '/../config/db.php';
        // Wenn DB_SOCKET gesetzt (lokal) → unix_socket statt TCP nutzen
        if (defined('DB_SOCKET') && DB_SOCKET) {
            $dsn = sprintf('mysql:unix_socket=%s;dbname=%s;charset=utf8mb4', DB_SOCKET, DB_NAME);
        } else {
            $dsn = sprintf('mysql:host=%s;port=%d;dbname=%s;charset=utf8mb4',
                DB_HOST, DB_PORT, DB_NAME);
        }
        self::$pdo = new PDO($dsn, DB_USER, DB_PASS, [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
            (defined('Pdo\Mysql::ATTR_INIT_COMMAND') ? \Pdo\Mysql::ATTR_INIT_COMMAND : PDO::MYSQL_ATTR_INIT_COMMAND)
                => 'SET NAMES utf8mb4 COLLATE utf8mb4_unicode_ci',
        ]);
        return self::$pdo;
    }
}
