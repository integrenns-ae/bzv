<?php
declare(strict_types=1);

require_once __DIR__ . '/../../../library/_init.php';
Auth::requireRole('admin', '/admin/login.php');

header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'POST only.']);
    exit;
}
// CSRF
$sentToken = $_SERVER['HTTP_X_CSRF'] ?? ($_POST['_csrf'] ?? '');
if (!hash_equals(csrf_token(), (string)$sentToken)) {
    http_response_code(403);
    echo json_encode(['error' => 'CSRF.']);
    exit;
}

$id       = (int)($_POST['id'] ?? 0);
$category = preg_replace('/[^a-z0-9_-]/i', '', (string)($_POST['category'] ?? 'news'));
if (!$id || !$category) {
    http_response_code(400);
    echo json_encode(['error' => 'id und category erforderlich.']);
    exit;
}

// 1) Bild-Detail von Pixabay holen (per ID)
$url = 'https://pixabay.com/api/?' . http_build_query([
    'key' => PIXABAY_KEY,
    'id'  => $id,
]);
$ctx = stream_context_create(['http' => ['timeout' => 10, 'header' => "User-Agent: bzv-gruenberg-admin/1.0\r\n"]]);
$body = @file_get_contents($url, false, $ctx);
$json = $body ? json_decode($body, true) : null;
$hit  = $json['hits'][0] ?? null;
if (!$hit || empty($hit['largeImageURL'])) {
    http_response_code(404);
    echo json_encode(['error' => 'Bild nicht gefunden.']);
    exit;
}

// 2) Bild herunterladen
$tmpFile = tempnam(sys_get_temp_dir(), 'pxb_');
$imgData = @file_get_contents($hit['largeImageURL'], false, $ctx);
if ($imgData === false || strlen($imgData) < 1000) {
    @unlink($tmpFile);
    http_response_code(502);
    echo json_encode(['error' => 'Download fehlgeschlagen.']);
    exit;
}
file_put_contents($tmpFile, $imgData);

// MIME-Check
$finfo = new finfo(FILEINFO_MIME_TYPE);
$mime  = $finfo->file($tmpFile) ?: '';
$ext   = ['image/jpeg' => 'jpg', 'image/png' => 'png', 'image/webp' => 'webp'][$mime] ?? null;
if (!$ext) {
    @unlink($tmpFile);
    http_response_code(415);
    echo json_encode(['error' => 'Unerwarteter MIME: ' . $mime]);
    exit;
}

// 3) Ablage-Pfad: bilder/<category>/YYYY/<random>.<ext>
$relDir  = $category . '/' . date('Y');
$bildBase = __DIR__ . '/../../bilder';
$absDir  = $bildBase . '/' . $relDir;
if (!is_dir($absDir) && !mkdir($absDir, 0755, true) && !is_dir($absDir)) {
    @unlink($tmpFile);
    http_response_code(500);
    echo json_encode(['error' => 'Zielordner nicht anlegbar.']);
    exit;
}
$name    = 'pxb-' . $id . '-' . bin2hex(random_bytes(4)) . '.' . $ext;
$relPath = $relDir . '/' . $name;
$absPath = $absDir . '/' . $name;
rename($tmpFile, $absPath);
@chmod($absPath, 0644);

// 4) Attribution-Log (Pixabay-Lizenz fordert keine Attribution, aber wir loggen für Transparenz)
$logLine = sprintf("%s\t%d\t%s\t%s\t%s\n",
    date('c'), $id,
    str_replace("\t", ' ', (string)($hit['user'] ?? '')),
    str_replace("\t", ' ', (string)($hit['pageURL'] ?? '')),
    $relPath
);
@file_put_contents(__DIR__ . '/../../bilder/pixabay-attribution.tsv', $logLine, FILE_APPEND);

echo json_encode([
    'path'    => $relPath,             // ohne führenden Slash, relativ zu /bilder/
    'preview' => '/bilder/' . $relPath,
    'user'    => $hit['user'] ?? '',
    'pageURL' => $hit['pageURL'] ?? '',
]);
