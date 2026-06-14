<?php
$pageTitle = 'Bestellungen';
$current   = 'bestellungen.php';
include __DIR__ . '/_layout.php';
require_once __DIR__ . '/../../library/XlsxWriter.php';

$pdo    = Database::pdo();
$action = $_GET['action'] ?? 'list';
$id     = isset($_GET['id']) ? (int)$_GET['id'] : 0;

// --- XLSX-Export ---
if ($action === 'export' && in_array($_GET['art'] ?? '', ['futter', 'behandlung', 'zucht'], true)) {
    $art = $_GET['art'];
    $st  = $pdo->prepare("SELECT * FROM bestellungen WHERE art = ? ORDER BY created_at ASC");
    $st->execute([$art]);
    $records = $st->fetchAll();

    $headers = [];
    $rows    = [];
    foreach ($records as $r) {
        $d = json_decode((string)$r['details'], true) ?: [];
        $datum = (new DateTime($r['created_at']))->format('d.m.Y H:i');
        if ($art === 'futter') {
            if (!$headers) $headers = ['Datum', 'Name', 'E-Mail', 'Telefon',
                'Sorte', 'Menge (kg)', 'Preis/kg (€)', 'Summe (€)',
                'Bemerkung', 'Erledigt'];
            $rows[] = [
                $datum, $r['member_name'], $r['member_email'], $r['member_phone'],
                $d['sorte'] ?? '', (int)($d['menge_kg'] ?? 0),
                (float)($d['preis_pro_kg'] ?? 0), (float)$r['summe_eur'],
                $d['bemerkung'] ?? '', $r['erledigt'] ? 'ja' : 'nein',
            ];
        } elseif ($art === 'behandlung') {
            if (!$headers) $headers = ['Datum', 'Vorname', 'Nachname', 'Mitglieds-Nr',
                'Straße', 'PLZ', 'Ort', 'E-Mail', 'Telefon',
                'Völker (kommend)', 'Oxal-Einheiten', 'Preis/Einheit (€)', 'Summe (€)',
                'IBAN', 'Kontoinhaber', 'Bestell-OK', 'SEPA-OK', 'Erledigt'];
            $rows[] = [
                $datum, $d['vorname'] ?? '', $d['nachname'] ?? '', $d['mitgliedsnr'] ?? '',
                $d['strasse'] ?? '', $d['plz'] ?? '', $d['ort'] ?? '',
                $r['member_email'], $r['member_phone'],
                (int)($d['voelker_kommend'] ?? 0),
                (int)($d['anzahl_oxal'] ?? 0), (float)($d['preis_oxal'] ?? 0),
                (float)$r['summe_eur'],
                $d['iban'] ?? '', $d['kontoinhaber'] ?? '',
                !empty($d['ok_bestellung']) ? 'ja' : 'nein',
                !empty($d['ok_sepa']) ? 'ja' : 'nein',
                $r['erledigt'] ? 'ja' : 'nein',
            ];
        } else { // zucht
            if (!$headers) $headers = ['Datum', 'Name', 'E-Mail', 'Telefon',
                'Standbegattet', 'Belegstellenbeg.', 'Zwischensumme (€)',
                'Zuschuss (€)', 'Summe (€)', 'Bemerkung', 'Erledigt'];
            $rows[] = [
                $datum, $r['member_name'], $r['member_email'], $r['member_phone'],
                (int)($d['anzahl_stand'] ?? 0), (int)($d['anzahl_beleg'] ?? 0),
                (float)($d['brutto'] ?? 0), (float)($d['zuschuss'] ?? 0),
                (float)$r['summe_eur'],
                $d['bemerkung'] ?? '', $r['erledigt'] ? 'ja' : 'nein',
            ];
        }
    }

    // Wenn leer: trotzdem Header + leere Datei
    if (!$headers) {
        $headers = match ($art) {
            'futter'    => ['Datum', 'Name', 'E-Mail', 'Telefon', 'Sorte', 'Menge (kg)', 'Preis/kg (€)', 'Summe (€)', 'Bemerkung', 'Erledigt'],
            'behandlung'=> ['Datum', 'Vorname', 'Nachname', 'Mitglieds-Nr', 'Straße', 'PLZ', 'Ort', 'E-Mail', 'Telefon', 'Völker (kommend)', 'Oxal-Einheiten', 'Preis/Einheit (€)', 'Summe (€)', 'IBAN', 'Kontoinhaber', 'Bestell-OK', 'SEPA-OK', 'Erledigt'],
            'zucht'     => ['Datum', 'Name', 'E-Mail', 'Telefon', 'Standbegattet', 'Belegstellenbeg.', 'Zwischensumme (€)', 'Zuschuss (€)', 'Summe (€)', 'Bemerkung', 'Erledigt'],
        };
    }

    XlsxWriter::send("bestellungen-{$art}-" . date('Y-m-d') . ".xlsx", $headers, $rows);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    $pid = (int)($_POST['id'] ?? 0);
    $act = $_POST['_action'] ?? '';
    if ($act === 'delete') {
        $pdo->prepare("DELETE FROM bestellungen WHERE id = ?")->execute([$pid]);
        flash('success', 'Bestellung gelöscht.');
    } elseif ($act === 'toggle') {
        $pdo->prepare("UPDATE bestellungen SET erledigt = 1 - erledigt WHERE id = ?")->execute([$pid]);
        flash('success', 'Status aktualisiert.');
    }
    redirect_after_save('/admin/bestellungen.php');
}

// Label-Maps für die JSON-Detailfelder
$labels = [
    'sorte' => 'Futtersorte', 'menge_kg' => 'Menge (kg)', 'preis_pro_kg' => 'Preis je kg',
    'bemerkung' => 'Bemerkung',
    'anzahl_stand' => 'Standbegattet', 'preis_stand' => 'Preis standbeg.',
    'anzahl_beleg' => 'Belegstellenbeg.', 'preis_beleg' => 'Preis belegst.',
    'brutto' => 'Zwischensumme', 'zuschuss' => 'Vereinszuschuss',
    'vorname' => 'Vorname', 'nachname' => 'Nachname', 'mitgliedsnr' => 'Mitgliedsnummer',
    'strasse' => 'Straße', 'plz' => 'PLZ', 'ort' => 'Ort',
    'anzahl_oxal' => 'Oxalsäure-Einheiten', 'preis_oxal' => 'Preis je Einheit',
    'voelker_kommend' => 'Völker kommendes Jahr',
    'iban' => 'IBAN', 'kontoinhaber' => 'Kontoinhaber',
    'ok_bestellung' => 'Einwilligung Bestellung', 'ok_sepa' => 'SEPA-Mandat erteilt',
];
$artLabel = ['futter' => '🍯 Futter', 'behandlung' => '🧪 Behandlung', 'zucht' => '👑 Zucht'];

if ($action === 'detail' && $id) {
    $st = $pdo->prepare("SELECT * FROM bestellungen WHERE id = ?");
    $st->execute([$id]);
    $row = $st->fetch();
    if (!$row) { echo '<p>Nicht gefunden.</p>'; include __DIR__ . '/_layout_end.php'; exit; }
    $details = json_decode((string)$row['details'], true) ?: [];
    ?>
    <a href="/admin/bestellungen.php" class="text-amber-700 hover:underline text-sm">&larr; Zurück zur Liste</a>
    <h1 class="text-2xl font-bold mt-2 mb-4"><?= $artLabel[$row['art']] ?? h($row['art']) ?> — Bestellung #<?= (int)$row['id'] ?></h1>

    <div class="bg-white border border-stone-200 rounded p-5 max-w-2xl space-y-3">
      <div class="grid grid-cols-2 gap-2 text-sm">
        <div class="text-stone-500">Eingegangen</div><div><?= h(format_datetime_de($row['created_at'])) ?></div>
        <div class="text-stone-500">Mitglied</div><div class="font-semibold"><?= h($row['member_name']) ?></div>
        <div class="text-stone-500">E-Mail</div><div><?= $row['member_email'] ? '<a class="text-amber-700 hover:underline" href="mailto:' . h($row['member_email']) . '">' . h($row['member_email']) . '</a>' : '—' ?></div>
        <div class="text-stone-500">Telefon</div><div><?= h($row['member_phone'] ?: '—') ?></div>
        <?php if ($row['summe_eur'] !== null): ?>
          <div class="text-stone-500">Summe</div><div class="font-bold text-amber-900"><?= number_format((float)$row['summe_eur'], 2, ',', '.') ?> €</div>
        <?php endif; ?>
      </div>

      <div class="border-t border-stone-200 pt-3">
        <h2 class="text-sm font-bold uppercase tracking-wide text-stone-500 mb-2">Details</h2>
        <table class="w-full text-sm">
          <?php foreach ($details as $k => $v): ?>
            <tr class="border-b border-stone-100">
              <td class="py-1.5 pr-4 text-stone-500 align-top"><?= h($labels[$k] ?? $k) ?></td>
              <td class="py-1.5 font-medium">
                <?php
                  if (is_bool($v)) echo $v ? '✓ ja' : '✗ nein';
                  elseif (in_array($k, ['preis_pro_kg','preis_stand','preis_beleg','preis_oxal','brutto','zuschuss'], true))
                      echo number_format((float)$v, 2, ',', '.') . ' €';
                  else echo nl2br(h((string)$v));
                ?>
              </td>
            </tr>
          <?php endforeach; ?>
        </table>
      </div>

      <form method="post" class="border-t border-stone-200 pt-3 flex gap-2">
        <?= csrf_field() ?>
        <input type="hidden" name="id" value="<?= (int)$row['id'] ?>">
        <button name="_action" value="toggle"
                class="px-3 py-2 rounded text-sm <?= $row['erledigt'] ? 'bg-stone-200 text-stone-700' : 'bg-emerald-600 text-white hover:bg-emerald-700' ?>">
          <?= $row['erledigt'] ? 'Als offen markieren' : 'Als erledigt markieren' ?>
        </button>
        <button name="_action" value="delete" onclick="return confirm('Bestellung wirklich löschen?');"
                class="px-3 py-2 rounded text-sm bg-red-100 text-red-800 hover:bg-red-200 ml-auto">löschen</button>
      </form>
    </div>
    <?php
} else {
    $filter = in_array($_GET['art'] ?? '', ['futter','behandlung','zucht'], true) ? $_GET['art'] : '';
    $sql = "SELECT id, art, member_name, summe_eur, erledigt, created_at FROM bestellungen";
    $params = [];
    if ($filter) { $sql .= " WHERE art = ?"; $params[] = $filter; }
    $sql .= " ORDER BY erledigt ASC, created_at DESC";
    $st = $pdo->prepare($sql);
    $st->execute($params);
    $rows = $st->fetchAll();
    ?>
    <h1 class="text-2xl font-bold mb-4">Bestellungen</h1>
    <div class="flex flex-wrap justify-between items-center gap-3 mb-4">
      <div class="flex gap-2 text-sm">
        <a href="?" class="px-3 py-1.5 rounded <?= $filter === '' ? 'bg-amber-700 text-white' : 'bg-stone-100 hover:bg-stone-200' ?>">Alle</a>
        <?php foreach ($artLabel as $k => $lbl): ?>
          <a href="?art=<?= $k ?>" class="px-3 py-1.5 rounded <?= $filter === $k ? 'bg-amber-700 text-white' : 'bg-stone-100 hover:bg-stone-200' ?>"><?= $lbl ?></a>
        <?php endforeach; ?>
      </div>
      <div class="flex gap-2 text-xs">
        <span class="text-stone-500 self-center">Excel-Export:</span>
        <?php foreach ($artLabel as $k => $lbl): ?>
          <a href="?action=export&amp;art=<?= $k ?>" class="bg-emerald-600 hover:bg-emerald-700 text-white font-bold px-3 py-1.5 rounded inline-flex items-center gap-1" title="<?= h($lbl) ?> als XLSX herunterladen">
            📊 <?= h($lbl) ?>
          </a>
        <?php endforeach; ?>
      </div>
    </div>

    <div class="bg-white border border-stone-200 rounded overflow-hidden">
      <table class="w-full text-sm">
        <thead class="bg-stone-100 text-left">
          <tr><th class="px-3 py-2">Datum</th><th>Art</th><th>Mitglied</th><th>Summe</th><th>Status</th><th></th></tr>
        </thead>
        <tbody>
          <?php foreach ($rows as $r): ?>
            <tr class="border-t <?= $r['erledigt'] ? 'opacity-50' : '' ?>">
              <td class="px-3 py-2 whitespace-nowrap"><?= h(format_date_de($r['created_at'])) ?></td>
              <td><?= $artLabel[$r['art']] ?? h($r['art']) ?></td>
              <td class="font-medium"><?= h($r['member_name']) ?></td>
              <td><?= $r['summe_eur'] !== null ? number_format((float)$r['summe_eur'], 2, ',', '.') . ' €' : '—' ?></td>
              <td><?= $r['erledigt'] ? '<span class="text-stone-500">erledigt</span>' : '<span class="text-emerald-700 font-semibold">offen</span>' ?></td>
              <td class="text-right pr-2">
                <a href="?action=detail&id=<?= (int)$r['id'] ?>" class="text-amber-700 hover:underline">ansehen</a>
              </td>
            </tr>
          <?php endforeach; ?>
          <?php if (!$rows): ?>
            <tr><td colspan="6" class="px-3 py-6 text-center text-stone-400">Noch keine Bestellungen.</td></tr>
          <?php endif; ?>
        </tbody>
      </table>
    </div>
    <?php
}

include __DIR__ . '/_layout_end.php';
