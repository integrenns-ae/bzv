<?php
declare(strict_types=1);

/**
 * Bild-Upload-Endpoint für den Trix-Editor im Admin.
 * Erwartet POST mit Datei (Feldname 'image') + CSRF-Token (_csrf).
 * Antwortet JSON: { url } im Erfolgsfall, sonst { error }.
 */
require_once __DIR__ . '/../../../library/_init.php';
Auth::requireRole('admin', '/admin/login.php');

header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Nur POST.']);
    exit;
}

try {
    csrf_check();
} catch (Throwable $e) {
    http_response_code(403);
    echo json_encode(['error' => 'CSRF-Fehler.']);
    exit;
}

if (empty($_FILES['image']) || $_FILES['image']['error'] !== UPLOAD_ERR_OK) {
    http_response_code(400);
    echo json_encode(['error' => 'Kein Bild übergeben oder Upload-Fehler.']);
    exit;
}

try {
    $bildBase = __DIR__ . '/../../bilder';
    $relPath  = store_upload(
        $_FILES['image'],
        'editor',
        unserialize(UPLOAD_ALLOWED_IMAGE_MIME),
        $bildBase
    );
    echo json_encode([
        'url'  => '/bilder/' . $relPath,
        'name' => basename($relPath),
        'size' => (int)$_FILES['image']['size'],
    ]);
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
}
