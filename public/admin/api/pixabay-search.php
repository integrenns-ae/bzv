<?php
declare(strict_types=1);

require_once __DIR__ . '/../../../library/_init.php';
Auth::requireRole('admin', '/admin/login.php');

header('Content-Type: application/json; charset=utf-8');

if (!defined('PIXABAY_KEY') || PIXABAY_KEY === '') {
    http_response_code(500);
    echo json_encode(['error' => 'PIXABAY_KEY in config/db.local.php fehlt.']);
    exit;
}

$q = trim((string)($_GET['q'] ?? ''));
if ($q === '') {
    echo json_encode(['hits' => []]);
    exit;
}

$url = 'https://pixabay.com/api/?' . http_build_query([
    'key'           => PIXABAY_KEY,
    'q'             => $q,
    'image_type'    => 'photo',
    'orientation'   => 'horizontal',
    'safesearch'    => 'true',
    'per_page'      => 12,
    'lang'          => 'de',
]);

$ctx = stream_context_create(['http' => ['timeout' => 10, 'header' => "User-Agent: bzv-gruenberg-admin/1.0\r\n"]]);
$body = @file_get_contents($url, false, $ctx);
if ($body === false) {
    http_response_code(502);
    echo json_encode(['error' => 'Pixabay-API nicht erreichbar.']);
    exit;
}

$json = json_decode($body, true);
if (!is_array($json) || !isset($json['hits'])) {
    http_response_code(502);
    echo json_encode(['error' => 'Pixabay-API-Antwort ungültig.']);
    exit;
}

// Nur die Felder zurückgeben, die das Frontend braucht
$hits = array_map(fn($h) => [
    'id'         => (int)$h['id'],
    'previewURL' => $h['previewURL'] ?? '',
    'tags'       => $h['tags'] ?? '',
    'user'       => $h['user'] ?? '',
    'pageURL'    => $h['pageURL'] ?? '',
    'width'      => $h['imageWidth'] ?? 0,
    'height'     => $h['imageHeight'] ?? 0,
], $json['hits']);

echo json_encode(['hits' => $hits, 'total' => (int)($json['totalHits'] ?? 0)]);
