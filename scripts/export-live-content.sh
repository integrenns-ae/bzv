#!/usr/bin/env bash
#
# scripts/export-live-content.sh
#
# Zieht den aktuellen Inhalt der Live-Test-Site (bienen.integrenns.de) als
# SQL-Seed-Datei herunter und legt ihn in scripts/seed-content-live.sql ab.
#
# So bleibt das Repo der "Stand der Wahrheit" — beim GoLive auf der
# Echtdomain kann diese Datei mit phpMyAdmin importiert werden und alle
# Inhalte sind sofort wieder da.
#
# Voraussetzung: .deploy.env enthält SFTP_* + SITE_URL.
#
# Aufruf:
#   bash scripts/export-live-content.sh

set -euo pipefail

ROOT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
cd "$ROOT_DIR"

if [[ ! -f ".deploy.env" ]]; then
    echo "FEHLER: .deploy.env fehlt." >&2
    exit 1
fi
# shellcheck disable=SC1091
set -a; . ./.deploy.env; set +a

: "${SFTP_HOST:?SFTP_HOST nicht gesetzt}"
: "${SFTP_USER:?SFTP_USER nicht gesetzt}"
: "${SFTP_PASS:?SFTP_PASS nicht gesetzt}"
: "${SFTP_PORT:=22}"
: "${SITE_URL:?SITE_URL nicht gesetzt}"

TMP_PHP=$(mktemp -t bzv-export.XXXXXX.php)
OUT_SQL="$ROOT_DIR/scripts/seed-content-live.sql"
REMOTE_NAME="bzv-export-$(date +%s)-$RANDOM.php"

cleanup() {
    rm -f "$TMP_PHP"
    lftp -u "$SFTP_USER,$SFTP_PASS" -p "$SFTP_PORT" "sftp://$SFTP_HOST" \
         -e "set sftp:auto-confirm yes; cd public; rm $REMOTE_NAME; bye" 2>/dev/null || true
}
trap cleanup EXIT

# Export-Skript zusammenbauen (inline, immer aktueller Stand)
cat > "$TMP_PHP" <<'PHP_EOF'
<?php
declare(strict_types=1);
error_reporting(E_ALL);
ini_set('display_errors', '1');
header('Content-Type: text/plain; charset=utf-8');
while (ob_get_level()) ob_end_flush();

require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../library/Database.php';

$pdo = Database::pdo();
$pdo->exec("SET NAMES utf8mb4");

$tables = [
    'page_blocks' => ['slug', 'title', 'body'],
    'vorstand'    => ['name', 'role', 'photo_path', 'email', 'phone', 'sort_order', 'is_published'],
    'termine'     => ['starts_at', 'ends_at', 'title', 'location', 'description', 'is_published'],
    'news'        => ['slug', 'title', 'published_at', 'expires_at', 'image_path', 'body', 'is_published'],
    'infos'       => ['section', 'title', 'body', 'link_url', 'download_path', 'sort_order', 'is_published'],
    'links'       => ['section', 'title', 'url', 'sort_order'],
    'imker'       => ['name', 'street', 'postal_code', 'city', 'lat', 'lng', 'phone', 'email',
                      'sells_honey', 'swarm_helper', 'description',
                      'consent_given', 'is_published', 'sort_order'],
    'gallery'     => ['image_path', 'alt_text', 'caption', 'sort_order', 'is_published'],
];

$q = function($v) use ($pdo): string {
    if ($v === null) return 'NULL';
    if (is_int($v) || is_float($v)) return (string)$v;
    return $pdo->quote((string)$v);
};

echo "-- Bienenzuchtverein Grünberg — Live-Content-Seed\n";
echo "-- Generiert am " . date('c') . "\n";
echo "-- Import via phpMyAdmin (Zeichensatz utf8mb4) ODER\n";
echo "--   mariadb --default-character-set=utf8mb4 <db> < scripts/seed-content-live.sql\n\n";
echo "SET NAMES utf8mb4;\n";
echo "SET FOREIGN_KEY_CHECKS = 0;\n\n";

foreach ($tables as $table => $cols) {
    echo "-- =============================================\n";
    echo "-- Tabelle: $table\n";
    echo "-- =============================================\n";
    echo "DELETE FROM `$table`;\n";

    $rows = $pdo->query("SELECT * FROM `$table` ORDER BY " .
        ($table === 'page_blocks' ? 'slug' : 'id')
    )->fetchAll(PDO::FETCH_ASSOC);

    if (!$rows) {
        echo "-- (leer)\n\n";
        continue;
    }
    $colList = '`' . implode('`, `', $cols) . '`';
    foreach ($rows as $r) {
        $vals = [];
        foreach ($cols as $c) $vals[] = $q($r[$c] ?? null);
        echo "INSERT INTO `$table` ($colList) VALUES (" . implode(', ', $vals) . ");\n";
    }
    echo "\n";
}

echo "SET FOREIGN_KEY_CHECKS = 1;\n";
PHP_EOF

echo "→ Upload temporärer Exporter ($REMOTE_NAME) …"
lftp -u "$SFTP_USER,$SFTP_PASS" -p "$SFTP_PORT" "sftp://$SFTP_HOST" \
     -e "set sftp:auto-confirm yes; cd public; put $TMP_PHP -o $REMOTE_NAME; bye" >/dev/null

echo "→ Aufruf des Exporters über HTTP …"
sleep 2   # SFTP-Schreibvorgang darf erst auf der Disk landen
set +e
HTTP_CODE=$(curl -s -o "$OUT_SQL.tmp" -w "%{http_code}" -m 90 "$SITE_URL/$REMOTE_NAME")
CURL_RC=$?
set -e
if [[ $CURL_RC -ne 0 || "$HTTP_CODE" != "200" ]]; then
    echo "FEHLER: curl rc=$CURL_RC, HTTP=$HTTP_CODE. URL: $SITE_URL/$REMOTE_NAME" >&2
    [[ -f "$OUT_SQL.tmp" ]] && head -20 "$OUT_SQL.tmp" >&2
    rm -f "$OUT_SQL.tmp"
    exit 1
fi

# Sanity-Check: enthält Statements
if ! grep -q "INSERT INTO" "$OUT_SQL.tmp"; then
    echo "FEHLER: Keine INSERTs im Output gefunden." >&2
    head -30 "$OUT_SQL.tmp" >&2
    rm -f "$OUT_SQL.tmp"
    exit 1
fi

mv "$OUT_SQL.tmp" "$OUT_SQL"

echo ""
echo "✓ Export erfolgreich → $OUT_SQL"
echo "  $(wc -l < "$OUT_SQL") Zeilen / $(wc -c < "$OUT_SQL") Bytes"
awk '/^-- Tabelle:/{tbl=$3; next} /^INSERT INTO/{c[tbl]++} END{for(t in c) printf "  %-15s %d Zeilen\n", t":", c[t]}' "$OUT_SQL"

echo ""
echo "Nun ggf. ins Git committen:"
echo "  git add scripts/seed-content-live.sql && git commit -m 'Live-Inhalte aktualisiert'"
