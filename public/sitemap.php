<?php
/**
 * Dynamische XML-Sitemap für Suchmaschinen.
 * Erreichbar unter /sitemap.xml (Rewrite in .htaccess).
 *
 * Listet alle öffentlichen, indexierbaren Seiten:
 *   - statische Kernseiten
 *   - veröffentlichte News-Artikel (saubere Slug-URLs)
 * Geschützte Bereiche (/admin, /mitglieder, /api) bleiben außen vor.
 */
require_once __DIR__ . '/../library/_init.php';

$base = rtrim(SITE_URL, '/');

// Statische Seiten mit Priorität + Änderungsfrequenz
$static = [
    ['/',                    '1.0', 'weekly'],
    ['/imker.php',           '0.9', 'weekly'],
    ['/aktuelles.php',       '0.8', 'weekly'],
    ['/termine.php',         '0.8', 'weekly'],
    ['/vorstand.php',        '0.7', 'monthly'],
    ['/infos.php',           '0.7', 'monthly'],
    ['/mitglied-werden.php', '0.7', 'monthly'],
    ['/schwarm.php',         '0.6', 'yearly'],
    ['/bildnachweis.php',    '0.2', 'yearly'],
    ['/impressum.php',       '0.2', 'yearly'],
    ['/datenschutz.php',     '0.2', 'yearly'],
];

// Veröffentlichte News-Artikel
$news = [];
try {
    $news = Database::pdo()->query(
        "SELECT slug, updated_at, published_at
           FROM news
          WHERE is_published = 1
            AND (expires_at IS NULL OR expires_at >= CURDATE())
          ORDER BY published_at DESC"
    )->fetchAll();
} catch (\Throwable $e) {
    $news = [];
}

header('Content-Type: application/xml; charset=UTF-8');
// Vom _init.php gestarteten Output-Buffer verwerfen, damit reines XML ausgeliefert wird
while (ob_get_level() > 0) { ob_end_clean(); }

$esc = static fn(string $s): string => htmlspecialchars($s, ENT_XML1, 'UTF-8');

echo '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
echo '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">' . "\n";

foreach ($static as [$path, $prio, $freq]) {
    echo "  <url>\n";
    echo "    <loc>" . $esc($base . $path) . "</loc>\n";
    echo "    <changefreq>{$freq}</changefreq>\n";
    echo "    <priority>{$prio}</priority>\n";
    echo "  </url>\n";
}

foreach ($news as $n) {
    $lastmod = $n['updated_at'] ?: $n['published_at'];
    echo "  <url>\n";
    echo "    <loc>" . $esc($base . '/aktuelles/' . $n['slug']) . "</loc>\n";
    if ($lastmod) {
        echo "    <lastmod>" . $esc(date('Y-m-d', strtotime((string)$lastmod))) . "</lastmod>\n";
    }
    echo "    <changefreq>monthly</changefreq>\n";
    echo "    <priority>0.6</priority>\n";
    echo "  </url>\n";
}

echo '</urlset>' . "\n";
