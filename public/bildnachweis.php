<?php
require_once __DIR__ . '/../library/_init.php';

/* ----------------------------------------------------------------------
 * 1) Wikimedia-Stock-Bilder aus bilder/stock/CREDITS.tsv
 * -------------------------------------------------------------------- */
$creditsPath = __DIR__ . '/bilder/stock/CREDITS.tsv';
$credits = [];
if (is_file($creditsPath)) {
    $rows = file($creditsPath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    array_shift($rows); // header
    foreach ($rows as $line) {
        $parts = explode("\t", $line);
        if (count($parts) >= 4) {
            $credits[] = [
                'name'        => $parts[0],
                'attribution' => $parts[1],
                'license'     => $parts[2],
                'source'      => $parts[3],
            ];
        }
    }
}

/* ----------------------------------------------------------------------
 * 2) Pixabay-Bilder aus bilder/pixabay-attribution.tsv
 *    Diese Datei wird automatisch von admin/api/pixabay-fetch.php
 *    fortgeschrieben, sobald im Admin ein Pixabay-Bild übernommen wird.
 *    Format je Zeile:  ts \t id \t user \t pageURL \t relPath
 *
 *    Angezeigt werden nur Bilder, die (a) noch als Datei existieren UND
 *    (b) tatsächlich irgendwo eingebunden sind (News-Bild, Vorstand-Foto,
 *    Info-Download oder <img> in einem Seitentext). So bereinigt sich der
 *    Nachweis selbst, wenn ein Bild später ersetzt wird.
 * -------------------------------------------------------------------- */
$pixabayPath = __DIR__ . '/bilder/pixabay-attribution.tsv';
$bildBase    = __DIR__ . '/bilder';
$pixabay     = [];

if (is_file($pixabayPath)) {
    // 2a) Alle aktuell verwendeten Bildpfade einsammeln (ohne /bilder/-Prefix)
    $used = [];
    try {
        $pdo = Database::pdo();
        foreach ($pdo->query("SELECT image_path FROM news WHERE image_path IS NOT NULL")->fetchAll(PDO::FETCH_COLUMN) as $p) {
            $used[ltrim((string)$p, '/')] = true;
        }
        foreach ($pdo->query("SELECT photo_path FROM vorstand WHERE photo_path IS NOT NULL")->fetchAll(PDO::FETCH_COLUMN) as $p) {
            $used[ltrim((string)$p, '/')] = true;
        }
        foreach ($pdo->query("SELECT download_path FROM infos WHERE download_path IS NOT NULL")->fetchAll(PDO::FETCH_COLUMN) as $p) {
            $used[ltrim((string)$p, '/')] = true;
        }
        // <img src="/bilder/…"> in redaktionellen Texten
        foreach ($pdo->query("SELECT body FROM page_blocks WHERE body LIKE '%/bilder/%'")->fetchAll(PDO::FETCH_COLUMN) as $body) {
            if (preg_match_all('#/bilder/([^"\'\s>]+)#', (string)$body, $m)) {
                foreach ($m[1] as $p) $used[$p] = true;
            }
        }
        foreach ($pdo->query("SELECT body FROM news WHERE body LIKE '%/bilder/%'")->fetchAll(PDO::FETCH_COLUMN) as $body) {
            if (preg_match_all('#/bilder/([^"\'\s>]+)#', (string)$body, $m)) {
                foreach ($m[1] as $p) $used[$p] = true;
            }
        }
    } catch (Throwable $e) {
        // DB nicht erreichbar → Pixabay-Sektion bleibt einfach leer
        $used = [];
    }

    // 2b) TSV einlesen, auf relPath deduplizieren (jüngster Eintrag gewinnt)
    $rows = file($pixabayPath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    $byPath = [];
    foreach ($rows as $line) {
        $parts = explode("\t", $line);
        if (count($parts) < 5) continue;
        [$ts, $id, $user, $pageURL, $relPath] = $parts;
        $relPath = ltrim($relPath, '/');
        $byPath[$relPath] = [
            'id'      => (int)$id,
            'user'    => trim($user),
            'pageURL' => trim($pageURL),
            'relPath' => $relPath,
            'ts'      => $ts,
        ];
    }

    // 2c) Nur verwendete + physisch vorhandene Bilder übernehmen
    foreach ($byPath as $relPath => $entry) {
        if (!isset($used[$relPath]))               continue;
        if (!is_file($bildBase . '/' . $relPath))  continue;
        $pixabay[] = $entry;
    }
    // jüngste zuerst
    usort($pixabay, fn($a, $b) => strcmp($b['ts'], $a['ts']));
}

Templates::header('Bildnachweis', '/bildnachweis.php');
?>

<section class="max-w-3xl mx-auto px-4 py-12 md:py-16 prose-bzv">
  <div class="border-b border-stone-200/60 pb-6 mb-8">
    <div class="text-xs uppercase tracking-widest text-honey-700 font-extrabold mb-1" data-edit-resource="page_blocks" data-edit-id="bildnachweis.eyebrow">
      <?= h(block('bildnachweis.eyebrow', 'Rechtliches')) ?>
    </div>
    <h1 class="text-3xl md:text-4xl font-display font-extrabold text-stone-900 tracking-tight" data-edit-resource="page_blocks" data-edit-id="bildnachweis.title">
      <?= h(block('bildnachweis.title', '📷 Bildnachweis')) ?>
    </h1>
    <p class="text-stone-500 mt-2 text-base leading-relaxed" data-edit-resource="page_blocks" data-edit-id="bildnachweis.subtitle">
      <?= h(block('bildnachweis.subtitle', 'Diese Webseite verwendet Fotos aus den Wikimedia Commons.')) ?>
    </p>
  </div>

  <?php if (!$credits && !$pixabay): ?>
    <p>Keine Bilder eingebunden.</p>
  <?php endif; ?>

  <?php if ($credits): ?>
    <h2 class="text-xl font-display font-extrabold text-stone-800 mt-2 mb-3">Fotos aus Wikimedia Commons</h2>
    <div class="overflow-x-auto">
      <table class="w-full text-sm">
        <thead>
          <tr class="border-b border-stone-200 text-left">
            <th class="py-2 pr-3 font-semibold text-stone-700">Vorschau</th>
            <th class="py-2 pr-3 font-semibold text-stone-700">Urheber</th>
            <th class="py-2 pr-3 font-semibold text-stone-700">Lizenz</th>
            <th class="py-2 font-semibold text-stone-700">Quelle</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($credits as $c): ?>
            <tr class="border-b border-stone-100">
              <td class="py-3 pr-3">
                <img src="/bilder/stock/<?= h($c['name']) ?>-480.webp" alt=""
                     loading="lazy" class="w-20 h-14 object-cover rounded-md shadow-sm">
              </td>
              <td class="py-3 pr-3 text-stone-700"><?= h($c['attribution'] ?: '—') ?></td>
              <td class="py-3 pr-3 text-stone-600 whitespace-nowrap"><?= h($c['license']) ?></td>
              <td class="py-3 text-xs">
                <a href="<?= h($c['source']) ?>" target="_blank" rel="noopener"
                   class="text-honey-700 hover:text-honey-900 underline break-all">
                  Wikimedia Commons ↗
                </a>
              </td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  <?php endif; ?>

  <?php if ($pixabay): ?>
    <h2 class="text-xl font-display font-extrabold text-stone-800 mt-10 mb-3">Fotos von Pixabay</h2>
    <p class="text-sm text-stone-500 -mt-1 mb-3">
      Diese Bilder stammen von <a href="https://pixabay.com" target="_blank" rel="noopener"
      class="text-honey-700 underline">Pixabay</a> und stehen unter der
      Pixabay&nbsp;Content&nbsp;License (frei nutzbar, keine Attributionspflicht).
      Wir nennen die Urheberinnen und Urheber dennoch gerne.
    </p>
    <div class="overflow-x-auto">
      <table class="w-full text-sm">
        <thead>
          <tr class="border-b border-stone-200 text-left">
            <th class="py-2 pr-3 font-semibold text-stone-700">Vorschau</th>
            <th class="py-2 pr-3 font-semibold text-stone-700">Urheber</th>
            <th class="py-2 pr-3 font-semibold text-stone-700">Lizenz</th>
            <th class="py-2 font-semibold text-stone-700">Quelle</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($pixabay as $p): ?>
            <tr class="border-b border-stone-100">
              <td class="py-3 pr-3">
                <img src="/bilder/<?= h($p['relPath']) ?>" alt=""
                     loading="lazy" class="w-20 h-14 object-cover rounded-md shadow-sm">
              </td>
              <td class="py-3 pr-3 text-stone-700"><?= h($p['user'] ?: 'Pixabay') ?></td>
              <td class="py-3 pr-3 text-stone-600 whitespace-nowrap">Pixabay Content License</td>
              <td class="py-3 text-xs">
                <?php if ($p['pageURL']): ?>
                  <a href="<?= h($p['pageURL']) ?>" target="_blank" rel="noopener"
                     class="text-honey-700 hover:text-honey-900 underline break-all">
                    Pixabay ↗
                  </a>
                <?php else: ?>
                  <span class="text-stone-400">—</span>
                <?php endif; ?>
              </td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  <?php endif; ?>

  <div data-edit-resource="page_blocks" data-edit-id="bildnachweis.footer">
    <?= block_html('bildnachweis.footer', '<h2>Lizenz-Informationen</h2><p>Die meisten Bilder stehen unter <strong>Creative Commons</strong>-Lizenzen bzw. sind <strong>Public Domain</strong>.</p>') ?>
  </div>
</section>

<?php Templates::footer(); ?>
