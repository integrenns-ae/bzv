<?php
declare(strict_types=1);

/**
 * PUBLIC Geocode-Endpoint (für Schwarm-Meldeformular).
 * Sehr leichtes Rate-Limit pro Session, damit niemand Nominatim für uns spammt.
 */

require_once __DIR__ . '/../../library/_init.php';
header('Content-Type: application/json; charset=utf-8');

// Rate-Limit: max. 30 Calls/h pro Session
$rl = $_SESSION['_rl_geocode'] ?? ['count' => 0, 'window_start' => time()];
if (time() - $rl['window_start'] > 3600) {
    $rl = ['count' => 0, 'window_start' => time()];
}
if ($rl['count'] >= 30) {
    http_response_code(429);
    echo json_encode(['error' => 'Zu viele Anfragen, bitte später erneut versuchen.']);
    exit;
}
$rl['count']++;
$_SESSION['_rl_geocode'] = $rl;

$street = trim((string)($_POST['street']      ?? $_GET['street']      ?? ''));
$plz    = trim((string)($_POST['postal_code'] ?? $_GET['postal_code'] ?? ''));
$city   = trim((string)($_POST['city']        ?? $_GET['city']        ?? ''));

if ($plz === '' && $city === '' && $street === '') {
    http_response_code(400);
    echo json_encode(['error' => 'Mindestens Straße, PLZ oder Ort erforderlich.']);
    exit;
}

$res = Geocoder::search($street, $plz, $city);
if (!$res) {
    http_response_code(404);
    echo json_encode(['error' => 'Adresse konnte nicht gefunden werden.']);
    exit;
}

echo json_encode($res);
