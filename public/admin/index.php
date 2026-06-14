<?php
$pageTitle = 'Übersicht';
$current   = 'index.php';
include __DIR__ . '/_layout.php';

$pdo = Database::pdo();
$stats = [
    'termine_upcoming' => (int)$pdo->query("SELECT COUNT(*) FROM termine WHERE is_published=1 AND starts_at >= NOW()")->fetchColumn(),
    'news_total'       => (int)$pdo->query("SELECT COUNT(*) FROM news    WHERE is_published=1")->fetchColumn(),
    'vorstand_total'   => (int)$pdo->query("SELECT COUNT(*) FROM vorstand WHERE is_published=1")->fetchColumn(),
    'docs_total'       => (int)$pdo->query("SELECT COUNT(*) FROM internal_docs")->fetchColumn(),
    'schwarm_30d'      => (int)$pdo->query("SELECT COUNT(*) FROM schwarm_logs WHERE reported_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)")->fetchColumn(),
];
$latest = $pdo->query("SELECT reported_at, reporter_name, reporter_phone, location FROM schwarm_logs ORDER BY reported_at DESC LIMIT 5")->fetchAll();
?>
<h1 class="text-2xl font-bold mb-4">Übersicht</h1>

<div class="grid sm:grid-cols-2 lg:grid-cols-4 gap-4">
  <div class="bg-white border border-stone-200 rounded p-4">
    <div class="text-3xl font-bold text-amber-700"><?= $stats['termine_upcoming'] ?></div>
    <div class="text-sm text-stone-600">Termine kommend</div>
    <a href="/admin/termine.php" class="text-xs text-amber-700 hover:underline">verwalten →</a>
  </div>
  <div class="bg-white border border-stone-200 rounded p-4">
    <div class="text-3xl font-bold text-amber-700"><?= $stats['news_total'] ?></div>
    <div class="text-sm text-stone-600">News veröffentlicht</div>
    <a href="/admin/news.php" class="text-xs text-amber-700 hover:underline">verwalten →</a>
  </div>
  <div class="bg-white border border-stone-200 rounded p-4">
    <div class="text-3xl font-bold text-amber-700"><?= $stats['vorstand_total'] ?></div>
    <div class="text-sm text-stone-600">Vorstandsmitglieder</div>
    <a href="/admin/vorstand.php" class="text-xs text-amber-700 hover:underline">verwalten →</a>
  </div>
  <div class="bg-white border border-stone-200 rounded p-4">
    <div class="text-3xl font-bold text-amber-700"><?= $stats['docs_total'] ?></div>
    <div class="text-sm text-stone-600">Mitglieder-Doks</div>
    <a href="/admin/internal_docs.php" class="text-xs text-amber-700 hover:underline">verwalten →</a>
  </div>
</div>

<section class="mt-8 bg-white border border-stone-200 rounded p-4">
  <h2 class="text-lg font-semibold">Letzte Schwarm-Meldungen (30 Tage: <?= $stats['schwarm_30d'] ?>)</h2>
  <?php if (!$latest): ?>
    <p class="text-stone-500 text-sm mt-2">Keine Meldungen.</p>
  <?php else: ?>
    <table class="w-full text-sm mt-3">
      <thead class="text-left text-stone-500 border-b">
        <tr><th class="py-1">Wann</th><th>Name</th><th>Telefon</th><th>Ort</th></tr>
      </thead>
      <tbody>
        <?php foreach ($latest as $r): ?>
          <tr class="border-b">
            <td class="py-1"><?= h(format_datetime_de($r['reported_at'])) ?></td>
            <td><?= h($r['reporter_name'] ?? '') ?></td>
            <td><?= h($r['reporter_phone'] ?? '') ?></td>
            <td><?= h($r['location'] ?? '') ?></td>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  <?php endif; ?>
</section>

<?php include __DIR__ . '/_layout_end.php';
