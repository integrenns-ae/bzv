<?php
declare(strict_types=1);

/**
 * Bootstrap-Skript: erster Admin-User.
 *
 * Aufruf (lokal):
 *   php scripts/create-admin.php <username> <passwort> [anzeigename]
 *
 * Aufruf (Live, einmalig per SSH oder Cron):
 *   /usr/bin/php scripts/create-admin.php alex 'starkes-pw' 'Alex'
 *
 * Wenn der User existiert, wird das Passwort aktualisiert (für Wiederherstellung).
 */

require_once __DIR__ . '/../library/_init.php';

if (PHP_SAPI !== 'cli') {
    http_response_code(403);
    exit("Nur CLI.\n");
}
if ($argc < 3) {
    fwrite(STDERR, "Usage: php scripts/create-admin.php <username> <passwort> [anzeigename]\n");
    exit(1);
}
[$_, $user, $pass] = $argv;
$display = $argv[3] ?? $user;

if (strlen($pass) < 8)        { fwrite(STDERR, "Passwort min. 8 Zeichen.\n"); exit(1); }
if (!preg_match('/^[a-zA-Z0-9._-]{3,64}$/', $user)) {
    fwrite(STDERR, "Username 3-64 Zeichen, [a-zA-Z0-9._-].\n"); exit(1);
}

$hash = password_hash($pass, PASSWORD_BCRYPT);
$pdo  = Database::pdo();
$pdo->prepare(
    "INSERT INTO users (username, password_hash, display_name, role, active)
     VALUES (?, ?, ?, 'admin', 1)
     ON DUPLICATE KEY UPDATE password_hash=VALUES(password_hash), display_name=VALUES(display_name), role='admin', active=1"
)->execute([$user, $hash, $display]);

echo "OK — Admin '$user' angelegt/aktualisiert.\n";
