<?php
declare(strict_types=1);

/**
 * PUBLIC: Top-2 Schwarmhelfer-Imker zu einem lat/lng-Punkt (Haversine).
 */
require_once __DIR__ . '/../../library/_init.php';
header('Content-Type: application/json; charset=utf-8');

$lat = (float)($_GET['lat'] ?? $_POST['lat'] ?? 0);
$lng = (float)($_GET['lng'] ?? $_POST['lng'] ?? 0);
$limit = max(1, min(5, (int)($_GET['limit'] ?? 2)));

if (!$lat || !$lng) {
    http_response_code(400);
    echo json_encode(['error' => 'lat und lng erforderlich.']);
    exit;
}

$rows = Database::pdo()->query(
    "SELECT id, name, street, postal_code, city, lat AS imker_lat, lng AS imker_lng, phone, email
       FROM imker
      WHERE is_published   = 1
        AND consent_given  = 1
        AND swarm_helper   = 1
        AND lat IS NOT NULL
        AND lng IS NOT NULL"
)->fetchAll();

$scored = [];
foreach ($rows as $r) {
    $d = haversine_km($lat, $lng, (float)$r['imker_lat'], (float)$r['imker_lng']);
    $scored[] = [
        'id'          => (int)$r['id'],
        'name'        => $r['name'],
        'addr'        => trim(($r['street'] ?? '') . ', ' . trim(($r['postal_code'] ?? '') . ' ' . ($r['city'] ?? '')), ', '),
        'phone'       => $r['phone'],
        'email'       => $r['email'],
        'distance_km' => round($d, 2),
    ];
}
usort($scored, fn($a, $b) => $a['distance_km'] <=> $b['distance_km']);

echo json_encode([
    'count'  => count($scored),
    'imker'  => array_slice($scored, 0, $limit),
]);
