<?php
declare(strict_types=1);

/**
 * Allgemeine Helpers: Escaping, Slugify, Datumsformat, CSRF-Token,
 * sanftes HTML-Filtering für Body-Felder.
 */

function h(?string $s): string
{
    return htmlspecialchars((string)$s, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

/**
 * URL-tauglichen Slug aus Titel erzeugen.
 */
function slugify(string $s): string
{
    $s = mb_strtolower($s, 'UTF-8');
    $s = strtr($s, [
        'ä' => 'ae', 'ö' => 'oe', 'ü' => 'ue', 'ß' => 'ss',
        'Ä' => 'ae', 'Ö' => 'oe', 'Ü' => 'ue',
    ]);
    $s = preg_replace('/[^a-z0-9]+/u', '-', $s) ?? '';
    return trim($s, '-') ?: 'eintrag';
}

/**
 * Datum für Anzeige formatieren (deutsch).
 * Übersetzt PHP-native Wochentage/Monate (englisch) ins Deutsche.
 *
 * Pattern wie bei date():
 *   D → Mo/Di/Mi/Do/Fr/Sa/So         (Wochentag kurz)
 *   l → Montag/Dienstag/…            (Wochentag lang)
 *   M → Jan/Feb/Mär/Apr/Mai/Jun/…    (Monat kurz)
 *   F → Januar/Februar/März/…        (Monat lang)
 */
function format_date_de(?string $iso, string $pattern = 'd.m.Y'): string
{
    if (!$iso) return '';
    $ts = strtotime($iso);
    if (!$ts) return '';
    $formatted = date($pattern, $ts);

    static $weekdayLong = [
        'Monday'    => 'Montag',     'Tuesday'  => 'Dienstag',
        'Wednesday' => 'Mittwoch',   'Thursday' => 'Donnerstag',
        'Friday'    => 'Freitag',    'Saturday' => 'Samstag',
        'Sunday'    => 'Sonntag',
    ];
    static $weekdayShort = [
        'Mon' => 'Mo', 'Tue' => 'Di', 'Wed' => 'Mi',
        'Thu' => 'Do', 'Fri' => 'Fr', 'Sat' => 'Sa', 'Sun' => 'So',
    ];
    static $monthLong = [
        'January'   => 'Januar',     'February' => 'Februar',
        'March'     => 'März',       'April'    => 'April',
        'May'       => 'Mai',        'June'     => 'Juni',
        'July'      => 'Juli',       'August'   => 'August',
        'September' => 'September',  'October'  => 'Oktober',
        'November'  => 'November',   'December' => 'Dezember',
    ];
    static $monthShort = [
        'Jan' => 'Jan', 'Feb' => 'Feb', 'Mar' => 'Mär', 'Apr' => 'Apr',
        'May' => 'Mai', 'Jun' => 'Jun', 'Jul' => 'Jul', 'Aug' => 'Aug',
        'Sep' => 'Sep', 'Oct' => 'Okt', 'Nov' => 'Nov', 'Dec' => 'Dez',
    ];

    // Reihenfolge: erst die langen (sonst würde 'Mar' in 'March' versehentlich gematcht)
    $formatted = strtr($formatted, $weekdayLong);
    $formatted = strtr($formatted, $monthLong);
    $formatted = strtr($formatted, $weekdayShort);
    $formatted = strtr($formatted, $monthShort);
    return $formatted;
}

function format_datetime_de(?string $iso): string
{
    return format_date_de($iso, 'd.m.Y H:i');
}

/**
 * CSRF: liefert/erzeugt Token für die aktuelle Session.
 */
function csrf_token(): string
{
    if (session_status() !== PHP_SESSION_ACTIVE) {
        session_start();
    }
    if (empty($_SESSION['_csrf'])) {
        $_SESSION['_csrf'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['_csrf'];
}

function csrf_check(): void
{
    $sent = $_POST['_csrf'] ?? '';
    if (!hash_equals(csrf_token(), (string)$sent)) {
        http_response_code(403);
        exit('CSRF-Token ungültig.');
    }
}

function csrf_field(): string
{
    return '<input type="hidden" name="_csrf" value="' . h(csrf_token()) . '">';
}

/**
 * Sehr einfache HTML-Filterung für Body-Felder (News, Infos).
 * Erlaubt: p, br, strong, em, b, i, u, ul, ol, li, a[href,title,target,rel], h2, h3, h4, blockquote.
 * Alles andere wird gestrippt. Links bekommen rel="noopener" wenn target=_blank.
 */
function clean_html(?string $html): string
{
    if (!$html) return '';
    $allowed = '<p><br><strong><em><b><i><u><ul><ol><li><a><h1><h2><h3><h4><blockquote><div><del><pre><code>';
    $clean = strip_tags($html, $allowed);
    $clean = preg_replace_callback('/<a\s+([^>]*)>/i', static function ($m) {
        $attrs = $m[1];
        $href  = '';
        if (preg_match('/href\s*=\s*"([^"]*)"/i', $attrs, $hm)) $href = $hm[1];
        elseif (preg_match("/href\s*=\s*'([^']*)'/i", $attrs, $hm)) $href = $hm[1];
        if (!preg_match('#^(https?:|mailto:|tel:|/)#i', $href)) {
            return '';
        }
        $title  = '';
        if (preg_match('/title\s*=\s*"([^"]*)"/i', $attrs, $tm)) $title = $tm[1];
        $target = (preg_match('/target\s*=\s*"_blank"/i', $attrs)) ? ' target="_blank" rel="noopener noreferrer"' : '';
        return '<a href="' . h($href) . '"' . ($title ? ' title="' . h($title) . '"' : '') . $target . '>';
    }, $clean) ?? '';
    return $clean;
}

/**
 * Plain-Text mit Zeilenumbrüchen → <p>-Absätze.
 * Für Vorstand, der ohne HTML schreiben will.
 */
function text_to_paragraphs(string $text): string
{
    $blocks = preg_split("/(\r?\n){2,}/", trim($text)) ?: [];
    $out = '';
    foreach ($blocks as $b) {
        $b = h($b);
        $b = nl2br($b);
        $out .= '<p>' . $b . '</p>';
    }
    return $out;
}

/**
 * Aktive Nav-Klasse.
 */
function nav_active(string $path, string $current): string
{
    return ($path === $current) ? ' aria-current="page" class="font-semibold text-amber-700"' : '';
}

/**
 * Datei sicher hochladen. Liefert relativen Pfad ab $publicSubdir
 * (z.B. 'news/2026/abc123.jpg') oder wirft Exception.
 *
 * @param array  $file        Eintrag aus $_FILES['name']
 * @param string $publicSubdir Unterordner unter /public/bilder/ oder /public/mitglieder/doks/
 * @param array  $allowedMime Liste erlaubter MIME-Typen
 * @param string $absBaseDir  Absoluter Zielordner
 */
function store_upload(array $file, string $publicSubdir, array $allowedMime, string $absBaseDir): string
{
    if (!isset($file['error']) || $file['error'] !== UPLOAD_ERR_OK) {
        throw new RuntimeException('Upload-Fehler: ' . ($file['error'] ?? '?'));
    }
    if ($file['size'] > UPLOAD_MAX_BYTES) {
        throw new RuntimeException('Datei zu groß.');
    }
    $finfo = new finfo(FILEINFO_MIME_TYPE);
    $mime  = $finfo->file($file['tmp_name']) ?: '';
    if (!in_array($mime, $allowedMime, true)) {
        throw new RuntimeException('Dateityp nicht erlaubt: ' . $mime);
    }
    $extMap = [
        'image/jpeg' => 'jpg', 'image/png' => 'png', 'image/webp' => 'webp',
        'application/pdf' => 'pdf',
    ];
    $ext  = $extMap[$mime] ?? 'bin';
    $name = bin2hex(random_bytes(8)) . '.' . $ext;
    $sub  = trim($publicSubdir, '/');
    $dir  = rtrim($absBaseDir, '/') . ($sub === '' ? '' : '/' . $sub);
    if (!is_dir($dir) && !mkdir($dir, 0755, true) && !is_dir($dir)) {
        throw new RuntimeException('Zielordner nicht anlegbar.');
    }
    if (!move_uploaded_file($file['tmp_name'], $dir . '/' . $name)) {
        throw new RuntimeException('move_uploaded_file fehlgeschlagen.');
    }
    return ($sub === '' ? '' : $sub . '/') . $name;
}

/**
 * Flash-Messages (über Session). Verbrauchen sich beim Lesen.
 */
function flash(string $type, ?string $msg = null): array
{
    if (session_status() !== PHP_SESSION_ACTIVE) {
        session_start();
    }
    if ($msg !== null) {
        $_SESSION['_flash'][$type][] = $msg;
        return [];
    }
    $msgs = $_SESSION['_flash'][$type] ?? [];
    unset($_SESSION['_flash'][$type]);
    return $msgs;
}

/**
 * Schwarm-Saison aktiv?  Nutzt SCHWARM_SAISON_START / SCHWARM_SAISON_ENDE aus
 * config/db.php (Format jeweils 'MM-DD'). Auch jahresübergreifende Fenster
 * (z.B. '11-01' bis '02-28') werden korrekt behandelt.
 */
function is_schwarm_saison(?DateTimeInterface $now = null): bool
{
    require_once __DIR__ . '/../config/db.php';
    $tz   = new DateTimeZone('Europe/Berlin');
    $now  = $now ? DateTime::createFromInterface($now)->setTimezone($tz) : new DateTime('now', $tz);
    $year = (int)$now->format('Y');
    $startMD = defined('SCHWARM_SAISON_START') ? SCHWARM_SAISON_START : '01-01';
    $endMD   = defined('SCHWARM_SAISON_ENDE')  ? SCHWARM_SAISON_ENDE  : '12-31';
    $start = new DateTime("$year-$startMD 00:00:00", $tz);
    $end   = new DateTime("$year-$endMD 23:59:59",   $tz);
    if ($end >= $start) {
        return $now >= $start && $now <= $end;
    }
    // Jahresübergreifend (z.B. Nov–Feb): zwei Fenster prüfen
    $startPrev = (clone $start)->modify('-1 year');
    return ($now >= $start) || ($now <= $end) || ($now >= $startPrev && $now->format('Y') === (string)$year);
}

/**
 * Im Modal-Edit-Modus (?minimal=1) nach erfolgreichem Save mit
 * ?saved=1&minimal=1 zurück, damit der Iframe sein Save-Signal ans Parent
 * senden kann. Sonst normaler Redirect zur Listenseite. Beendet immer mit exit.
 */
function redirect_after_save(string $listUrl): void
{
    $isMinimal = !empty($_GET['minimal']) || !empty($_POST['minimal']);
    if ($isMinimal) {
        // Im Inline-Edit-Modal: KEINE Weiterleitung zur Listenseite (sonst rendert
        // das Iframe kurz den Listen-Layout, was beim parent-Reload als Flackern
        // sichtbar wird). Stattdessen direkt das Save-Signal an den Parent senden
        // und Iframe leer lassen.
        while (ob_get_level() > 0) ob_end_clean();
        header('Content-Type: text/html; charset=utf-8');
        echo '<!DOCTYPE html><html lang="de"><head><meta charset="UTF-8">'
           . '<title>Gespeichert</title></head><body style="margin:0;background:#fff">'
           . '<script>'
           . 'if(window.parent!==window){window.parent.postMessage({type:"bzv-edit-saved"},window.location.origin);}'
           . '</script></body></html>';
        exit;
    }
    header('Location: ' . $listUrl);
    exit;
}

/**
 * Haversine-Distanz in Kilometern zwischen zwei lat/lng-Paaren.
 */
function haversine_km(float $lat1, float $lng1, float $lat2, float $lng2): float
{
    $R = 6371.0;
    $dLat = deg2rad($lat2 - $lat1);
    $dLng = deg2rad($lng2 - $lng1);
    $a = sin($dLat / 2) ** 2 + cos(deg2rad($lat1)) * cos(deg2rad($lat2)) * sin($dLng / 2) ** 2;
    return $R * 2 * atan2(sqrt($a), sqrt(1 - $a));
}

/**
 * webcal://-URL für den iCal-Feed. Klick öffnet die Standard-Kalender-App
 * mit „Abonnieren?"-Dialog statt einfachem Download.
 */
function webcal_url(string $path = '/termine.ics'): string
{
    $host = $_SERVER['HTTP_HOST'] ?? 'bienenzuchtverein-gruenberg.de';
    return 'webcal://' . $host . $path;
}

/**
 * Sektions-Label für infos.php: liefert page_block-Override oder den Default.
 * Beispiel: section_label('recht', 'Recht & Verordnungen')
 */
function section_label(string $key, string $default): string
{
    $b = block("infos.section.$key.label", '');
    return $b !== '' ? $b : $default;
}

/**
 * Liefert den Roh-Text eines page_blocks. Caller ist für Escaping/clean_html verantwortlich.
 * Im Edit-Modus markiert man das umgebende Element zusätzlich mit
 *   data-edit-resource="page_blocks" data-edit-id="<slug>"
 * damit das Inline-Edit-Modal greift.
 */
function block(string $slug, string $default = ''): string
{
    static $cache = [];
    if (!array_key_exists($slug, $cache)) {
        try {
            $stmt = Database::pdo()->prepare("SELECT body FROM page_blocks WHERE slug = ?");
            $stmt->execute([$slug]);
            $cache[$slug] = $stmt->fetchColumn();
        } catch (Throwable $e) {
            $cache[$slug] = false;
        }
    }
    return $cache[$slug] !== false ? (string)$cache[$slug] : $default;
}

/**
 * Convenience: page_blocks-Inhalt mit clean_html als HTML-Block.
 */
function block_html(string $slug, string $default = ''): string
{
    return clean_html(block($slug, $default));
}

/**
 * Telefonnummer in internationales E.164-Format umwandeln (für tel:-Links).
 * Akzeptiert deutsche Schreibweisen ("0176 78572306"), nationale (00 49…) und +49…
 */
function tel_to_e164(string $tel, string $defaultCountryCode = '49'): string
{
    $t = preg_replace('/[^0-9+]/', '', $tel) ?? '';
    if ($t === '') return '';
    if (str_starts_with($t, '+')) return $t;
    if (str_starts_with($t, '00')) return '+' . substr($t, 2);
    if (str_starts_with($t, '0'))  return '+' . $defaultCountryCode . substr($t, 1);
    return '+' . $t;
}

/**
 * page_block als Ja/Nein-Schalter auswerten.
 * Akzeptiert ja/nein, 1/0, an/aus, x, true/false (case-insensitiv).
 */
function flag_block(string $slug, bool $default): bool
{
    $raw = strtolower(trim(block($slug, $default ? 'ja' : 'nein')));
    if (in_array($raw, ['ja', '1', 'an', 'x', 'true', 'yes', 'wahr'], true))   return true;
    if (in_array($raw, ['nein', '0', 'aus', 'false', 'no', 'falsch'], true))   return false;
    return $default;
}

/**
 * Stock-Bild als <picture>-Tag mit srcset rendern.
 *
 * @param string $slug      Basis-Slug aus public/bilder/stock/ (z.B. 'imker-1')
 * @param array  $attrs     z.B. ['class' => '...', 'alt' => '...', 'loading' => 'lazy']
 * @param int    $defaultW  Standard-Größe für src (480 | 960 | 1920)
 */
function stock_picture(string $slug, array $attrs = [], int $defaultW = 960): string
{
    $base = '/bilder/stock/' . $slug;
    $alt  = $attrs['alt'] ?? '';
    unset($attrs['alt']);
    $attrPairs = '';
    foreach ($attrs as $k => $v) {
        $attrPairs .= ' ' . $k . '="' . h((string)$v) . '"';
    }
    $srcsetWebp = "{$base}-480.webp 480w, {$base}-960.webp 960w, {$base}-1920.webp 1920w";
    $srcsetJpg  = "{$base}.jpg";
    return '<picture>'
         . '<source type="image/webp" srcset="' . $srcsetWebp . '" sizes="(max-width: 768px) 100vw, 960px">'
         . '<img src="' . $base . '-' . $defaultW . '.webp" alt="' . h($alt) . '"'
         . ' loading="lazy" decoding="async"' . $attrPairs . '>'
         . '</picture>';
}

/**
 * Stock-Bild-Slug für News ohne eigenes Bild — deterministisch aus DB-ID
 * damit derselbe Eintrag immer dasselbe Fallback bekommt.
 */
function stock_fallback_for_news(int $id): string
{
    $pool = ['biene-bluete-1', 'biene-bluete-2', 'biene-bluete-3', 'wabe-1', 'wabe-2', 'imker-1', 'imker-2', 'honig-1', 'bienenstand-1', 'smoker-1'];
    return $pool[$id % count($pool)];
}

/**
 * Lazy-Init: alle Models laden.
 */
function lib_autoload(string $class): void
{
    $file = __DIR__ . '/' . $class . '.php';
    if (is_file($file)) require_once $file;
}
spl_autoload_register('lib_autoload');
