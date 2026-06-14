<?php
require_once __DIR__ . '/../library/_init.php';

// Vor dem Header-Versand jeden bisherigen Output verwerfen (BOM, Whitespace, _init-Notices).
while (ob_get_level() > 0) ob_end_clean();

header('Content-Type: text/calendar; charset=utf-8; method=PUBLISH');
header('Content-Disposition: inline; filename="bzv-gruenberg.ics"');
header('Cache-Control: no-cache, must-revalidate');

$events = Database::pdo()->query(
    "SELECT id, starts_at, ends_at, title, location, description, updated_at, created_at
       FROM termine
      WHERE is_published = 1
        AND starts_at >= DATE_SUB(NOW(), INTERVAL 365 DAY)
      ORDER BY starts_at ASC"
)->fetchAll();

function ics_escape(string $s): string
{
    return str_replace(
        ["\\", ";", ",", "\r\n", "\n"],
        ["\\\\", "\\;", "\\,", "\\n", "\\n"],
        $s
    );
}

function ics_dt(string $iso, ?string $tz = 'Europe/Berlin'): string
{
    $dt = new DateTime($iso, new DateTimeZone($tz ?? 'UTC'));
    return $dt->format('Ymd\THis');
}

// RFC 5545: Zeilen >75 Oktette müssen mit CRLF + Space gefaltet werden.
function ics_fold(string $line): string
{
    if (strlen($line) <= 75) return $line;
    $out = '';
    $rest = $line;
    while (strlen($rest) > 75) {
        // Sicher bei UTF-8-Bytegrenzen schneiden
        $chunk = substr($rest, 0, 75);
        // wenn das nächste Byte ein Multibyte-Tail ist, zurückgehen
        while (strlen($chunk) > 0 && (ord(substr($rest, strlen($chunk), 1)) & 0xC0) === 0x80) {
            $chunk = substr($chunk, 0, -1);
        }
        $out .= $chunk . "\r\n ";
        $rest = substr($rest, strlen($chunk));
    }
    return $out . $rest;
}

$lines = [];
$lines[] = 'BEGIN:VCALENDAR';
$lines[] = 'VERSION:2.0';
$lines[] = 'PRODID:-//Bienenzuchtverein Gruenberg//Termine 1.0//DE';
$lines[] = 'CALSCALE:GREGORIAN';
$lines[] = 'METHOD:PUBLISH';
$lines[] = 'X-WR-CALNAME:Bienenzuchtverein Grünberg';
$lines[] = 'X-WR-TIMEZONE:Europe/Berlin';
$lines[] = 'X-WR-CALDESC:Termine des Bienenzuchtvereins Grünberg und Umgebung e.V.';

// VTIMEZONE-Definition — zwingend für Android/Google Calendar mit TZID-Referenz
$lines[] = 'BEGIN:VTIMEZONE';
$lines[] = 'TZID:Europe/Berlin';
$lines[] = 'X-LIC-LOCATION:Europe/Berlin';
$lines[] = 'BEGIN:DAYLIGHT';
$lines[] = 'TZOFFSETFROM:+0100';
$lines[] = 'TZOFFSETTO:+0200';
$lines[] = 'TZNAME:CEST';
$lines[] = 'DTSTART:19700329T020000';
$lines[] = 'RRULE:FREQ=YEARLY;BYMONTH=3;BYDAY=-1SU';
$lines[] = 'END:DAYLIGHT';
$lines[] = 'BEGIN:STANDARD';
$lines[] = 'TZOFFSETFROM:+0200';
$lines[] = 'TZOFFSETTO:+0100';
$lines[] = 'TZNAME:CET';
$lines[] = 'DTSTART:19701025T030000';
$lines[] = 'RRULE:FREQ=YEARLY;BYMONTH=10;BYDAY=-1SU';
$lines[] = 'END:STANDARD';
$lines[] = 'END:VTIMEZONE';

foreach ($events as $e) {
    $start = ics_dt($e['starts_at']);
    $end   = $e['ends_at']
        ? ics_dt($e['ends_at'])
        : ics_dt((new DateTime($e['starts_at']))->modify('+2 hours')->format('Y-m-d H:i:s'));
    $stamp = (new DateTime('now', new DateTimeZone('UTC')))->format('Ymd\THis\Z');
    $modified = !empty($e['updated_at'])
        ? (new DateTime($e['updated_at'], new DateTimeZone('Europe/Berlin')))
            ->setTimezone(new DateTimeZone('UTC'))
            ->format('Ymd\THis\Z')
        : $stamp;
    $uid   = 'termin-' . (int)$e['id'] . '@bienenzuchtverein-gruenberg.de';

    $lines[] = 'BEGIN:VEVENT';
    $lines[] = 'UID:' . $uid;
    $lines[] = 'DTSTAMP:' . $stamp;
    $lines[] = 'LAST-MODIFIED:' . $modified;
    $lines[] = 'SEQUENCE:0';
    $lines[] = 'DTSTART;TZID=Europe/Berlin:' . $start;
    $lines[] = 'DTEND;TZID=Europe/Berlin:'   . $end;
    $lines[] = 'SUMMARY:' . ics_escape($e['title']);
    if (!empty($e['location']))    $lines[] = 'LOCATION:'    . ics_escape($e['location']);
    if (!empty($e['description'])) $lines[] = 'DESCRIPTION:' . ics_escape($e['description']);
    $lines[] = 'STATUS:CONFIRMED';
    $lines[] = 'TRANSP:OPAQUE';
    $lines[] = 'END:VEVENT';
}

$lines[] = 'END:VCALENDAR';

// Line-Folding + CRLF terminator
$out = '';
foreach ($lines as $l) {
    $out .= ics_fold($l) . "\r\n";
}
echo $out;
