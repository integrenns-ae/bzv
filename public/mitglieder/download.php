<?php
require_once __DIR__ . '/../../library/_init.php';

if (!Auth::check()) {
    header('Location: /mitglieder/login.php');
    exit;
}

$id = (int)($_GET['id'] ?? 0);
if (!$id) { http_response_code(404); exit('Kein Dokument.'); }

$stmt = Database::pdo()->prepare("SELECT file_path, original_name FROM internal_docs WHERE id = ?");
$stmt->execute([$id]);
$doc = $stmt->fetch();
if (!$doc) { http_response_code(404); exit('Nicht gefunden.'); }

$baseDir = realpath(__DIR__ . '/doks');
$abs     = realpath($baseDir . '/' . $doc['file_path']);

if (!$abs || !str_starts_with($abs, $baseDir . '/')) {
    http_response_code(403);
    exit('Zugriff verweigert.');
}

$filename = $doc['original_name'] ?: basename($abs);
header('Content-Type: application/pdf');
header('Content-Length: ' . filesize($abs));
header('Content-Disposition: attachment; filename="' . str_replace('"', '', $filename) . '"');
header('X-Content-Type-Options: nosniff');
readfile($abs);
exit;
