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
$sentToken = $_SERVER['HTTP_X_CSRF'] ?? ($_POST['_csrf'] ?? '');
if (!hash_equals(csrf_token(), (string)$sentToken)) {
    http_response_code(403);
    echo json_encode(['error' => 'CSRF.']);
    exit;
}

$street = trim((string)($_POST['street']      ?? ''));
$plz    = trim((string)($_POST['postal_code'] ?? ''));
$city   = trim((string)($_POST['city']        ?? ''));

if ($plz === '' && $city === '') {
    http_response_code(400);
    echo json_encode(['error' => 'Mindestens PLZ oder Ort erforderlich.']);
    exit;
}

$res = Geocoder::search($street, $plz, $city);
if (!$res) {
    http_response_code(404);
    echo json_encode(['error' => 'Adresse konnte nicht gefunden werden. Bitte Koordinaten manuell eintragen.']);
    exit;
}

echo json_encode($res);
