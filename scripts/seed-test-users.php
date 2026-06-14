<?php
declare(strict_types=1);

/**
 * Seedet die drei Test-Accounts: alex (admin), vorstand (admin), mitglied (member).
 * Idempotent — bei wiederholtem Aufruf werden Passwörter zurückgesetzt.
 *
 * Aufruf: php scripts/seed-test-users.php
 */

require_once __DIR__ . '/../library/_init.php';

if (PHP_SAPI !== 'cli') {
    http_response_code(403);
    exit("Nur CLI.\n");
}

$users = [
    ['alex',     'demo1234',      'admin',  'Alex (Haupt-Admin)'],
    ['vorstand', 'vorstand2026',  'admin',  'Vorstand-Test'],
    ['mitglied', 'mitglied2026',  'member', 'Test-Mitglied'],
];

$pdo = Database::pdo();
$stmt = $pdo->prepare(
    "INSERT INTO users (username, password_hash, display_name, role, active)
     VALUES (?,?,?,?,1)
     ON DUPLICATE KEY UPDATE
       password_hash = VALUES(password_hash),
       display_name  = VALUES(display_name),
       role          = VALUES(role),
       active        = 1"
);

echo "Test-Accounts werden angelegt/aktualisiert:\n\n";
printf("  %-12s  %-15s  %-7s  %s\n", 'Username', 'Passwort', 'Rolle', 'Anzeigename');
printf("  %-12s  %-15s  %-7s  %s\n", '--------', '--------', '-----', '-----------');

foreach ($users as [$user, $pass, $role, $display]) {
    $hash = password_hash($pass, PASSWORD_BCRYPT);
    $stmt->execute([$user, $hash, $display, $role]);
    printf("  %-12s  %-15s  %-7s  %s\n", $user, $pass, $role, $display);
}

echo "\nLogin-URLs:\n";
echo "  Admin-Bereich:        /admin/login.php   (alex, vorstand)\n";
echo "  Mitgliederbereich:    /mitglieder/login.php   (mitglied)\n";
echo "\nFertig.\n";
