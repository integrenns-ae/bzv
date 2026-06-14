<?php
require_once __DIR__ . '/../../library/_init.php';

// Mitglieder ODER Admins dürfen rein
if (!Auth::check()) {
    header('Location: /mitglieder/login.php');
    exit;
}

$pdo  = Database::pdo();
$docs = $pdo->query("SELECT id, title, description, original_name, uploaded_at FROM internal_docs ORDER BY uploaded_at DESC")->fetchAll();
$user = Auth::user();

Templates::header('Mitgliederbereich', '/mitglieder/');
?>

<section class="max-w-3xl mx-auto px-4 py-10">
  <div class="flex justify-between items-baseline">
    <h1 class="text-3xl font-bold text-amber-900">Mitgliederbereich</h1>
    <a href="/mitglieder/logout.php" class="text-sm text-stone-500 hover:text-stone-800">Abmelden</a>
  </div>
  <p class="text-stone-600 mt-2">Hallo <?= h($user['display_name']) ?>! Hier findest du interne Dokumente und die Sammelbestellungen.</p>

  <!-- Sammelbestellungen -->
  <h2 class="text-xl font-bold text-amber-900 mt-8">Sammelbestellungen</h2>
  <p class="text-sm text-stone-500 mt-1">Gemeinsam bestellen — bessere Konditionen für alle.</p>
  <div class="grid sm:grid-cols-3 gap-4 mt-4">
    <a href="/mitglieder/bestellung-futter.php"
       class="block bg-white border border-stone-200 rounded-2xl p-5 hover:border-amber-400 hover:shadow-md transition-all">
      <div class="text-3xl">🍯</div>
      <div class="font-bold text-amber-900 mt-2">Futter</div>
      <div class="text-sm text-stone-500 mt-1">Apifonda &amp; Apiinvert</div>
    </a>
    <a href="/mitglieder/bestellung-behandlung.php"
       class="block bg-white border border-stone-200 rounded-2xl p-5 hover:border-amber-400 hover:shadow-md transition-all">
      <div class="text-3xl">🧪</div>
      <div class="font-bold text-amber-900 mt-2">Behandlung</div>
      <div class="text-sm text-stone-500 mt-1">Oxalsäure &amp; Völkermeldung</div>
    </a>
    <a href="/mitglieder/bestellung-zucht.php"
       class="block bg-white border border-stone-200 rounded-2xl p-5 hover:border-amber-400 hover:shadow-md transition-all">
      <div class="text-3xl">👑</div>
      <div class="font-bold text-amber-900 mt-2">Zucht</div>
      <div class="text-sm text-stone-500 mt-1">Königinnen bestellen</div>
    </a>
  </div>

  <h2 class="text-xl font-bold text-amber-900 mt-10">Interne Dokumente</h2>
  <?php if (!$docs): ?>
    <p class="text-stone-500 mt-6">Aktuell sind keine Dokumente eingestellt.</p>
  <?php else: ?>
    <ul class="mt-6 divide-y divide-stone-200 bg-white border border-stone-200 rounded">
      <?php foreach ($docs as $d): ?>
        <li class="p-4 flex items-start gap-4">
          <div class="text-3xl">📄</div>
          <div class="flex-1">
            <div class="font-semibold"><?= h($d['title']) ?></div>
            <?php if ($d['description']): ?>
              <div class="text-sm text-stone-600 mt-1"><?= nl2br(h($d['description'])) ?></div>
            <?php endif; ?>
            <div class="text-xs text-stone-400 mt-1"><?= h(format_date_de($d['uploaded_at'])) ?></div>
          </div>
          <a href="/mitglieder/download.php?id=<?= (int)$d['id'] ?>"
             class="bg-amber-100 hover:bg-amber-200 text-amber-900 px-3 py-2 rounded text-sm font-semibold">
            ⬇ Download
          </a>
        </li>
      <?php endforeach; ?>
    </ul>
  <?php endif; ?>
</section>

<?php Templates::footer(); ?>
