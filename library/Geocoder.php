<?php
declare(strict_types=1);

/**
 * Schmaler Geocoding-Client (OSM-basiert).
 *
 * Versucht erst Nominatim (offiziell), fällt bei Rate-Limit/Fehler auf
 * Photon (komoot, gleiche OSM-Daten, deutlich tolerantere Limits) zurück.
 *
 * Hintergrund: Shared-Hosting bei STRATO/IONOS teilt sich IP-Range mit anderen
 * Kunden → Nominatim sieht zu viele Requests pro IP und antwortet HTTP 429.
 * Photon nimmt diese Requests weiter an.
 */
final class Geocoder
{
    private const NOMINATIM = 'https://nominatim.openstreetmap.org/search';
    private const PHOTON    = 'https://photon.komoot.io/api/';
    private const UA        = 'bzv-gruenberg/1.0 (admin@bienenzuchtverein-gruenberg.de)';

    /**
     * Liefert ['lat' => float, 'lng' => float, 'display_name' => string] oder null.
     */
    public static function search(string $street, string $postalCode, string $city, string $country = 'Deutschland'): ?array
    {
        $parts = array_filter([trim($street), trim($postalCode), trim($city), trim($country)]);
        if (count($parts) < 2) return null;
        $query = implode(', ', $parts);

        // 1) Nominatim
        $r = self::tryNominatim($query);
        if ($r) return $r;

        // 2) Photon-Fallback
        return self::tryPhoton($query);
    }

    private static function tryNominatim(string $query): ?array
    {
        $url = self::NOMINATIM . '?' . http_build_query([
            'format'         => 'json',
            'limit'          => 1,
            'addressdetails' => 0,
            'q'              => $query,
        ]);
        $body = self::fetch($url);
        if ($body === null) return null;
        $j = json_decode($body, true);
        if (!is_array($j) || !isset($j[0]['lat'], $j[0]['lon'])) return null;
        return [
            'lat'          => (float)$j[0]['lat'],
            'lng'          => (float)$j[0]['lon'],
            'display_name' => (string)($j[0]['display_name'] ?? ''),
        ];
    }

    private static function tryPhoton(string $query): ?array
    {
        $url = self::PHOTON . '?' . http_build_query([
            'q'      => $query,
            'limit'  => 1,
            'lang'   => 'de',
        ]);
        $body = self::fetch($url);
        if ($body === null) return null;
        $j = json_decode($body, true);
        if (!is_array($j) || empty($j['features'][0]['geometry']['coordinates'])) return null;
        $coords = $j['features'][0]['geometry']['coordinates'];   // [lng, lat]
        $props  = $j['features'][0]['properties'] ?? [];
        $name = implode(', ', array_filter([
            ($props['housenumber'] ?? '') ? ($props['street'] ?? '') . ' ' . $props['housenumber'] : ($props['street'] ?? ''),
            ($props['postcode'] ?? '') . ' ' . ($props['city'] ?? $props['name'] ?? ''),
            $props['country'] ?? '',
        ]));
        return [
            'lat'          => (float)$coords[1],
            'lng'          => (float)$coords[0],
            'display_name' => trim($name, ', ') ?: $query,
        ];
    }

    /**
     * HTTP-GET via curl (falls verfügbar), Fallback file_get_contents.
     * Gibt Body bei HTTP 200 zurück, sonst null.
     */
    private static function fetch(string $url): ?string
    {
        if (extension_loaded('curl')) {
            $ch = curl_init($url);
            curl_setopt_array($ch, [
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_USERAGENT      => self::UA,
                CURLOPT_HTTPHEADER     => ['Accept: application/json'],
                CURLOPT_TIMEOUT        => 8,
                CURLOPT_CONNECTTIMEOUT => 5,
                CURLOPT_FOLLOWLOCATION => true,
            ]);
            $body = curl_exec($ch);
            $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);
            if ($body === false || $code !== 200) return null;
            return $body;
        }
        // Fallback
        $ctx = stream_context_create([
            'http' => [
                'timeout' => 8,
                'header'  => "User-Agent: " . self::UA . "\r\nAccept: application/json\r\n",
                'ignore_errors' => true,
            ],
        ]);
        $body = @file_get_contents($url, false, $ctx);
        if ($body === false) return null;
        // HTTP-Status aus $http_response_header parsen
        if (isset($http_response_header[0]) && !preg_match('#\bHTTP/\S+\s+2\d\d\b#', $http_response_header[0])) {
            return null;
        }
        return $body;
    }
}
