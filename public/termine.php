<?php
require_once __DIR__ . '/../library/_init.php';

$pdo = Database::pdo();
$upcoming = $pdo->query(
    "SELECT id, starts_at, ends_at, title, location, description
       FROM termine
      WHERE is_published = 1 AND starts_at >= NOW()
      ORDER BY starts_at ASC"
)->fetchAll();

$past = $pdo->query(
    "SELECT id, starts_at, title, location
       FROM termine
      WHERE is_published = 1 AND starts_at < NOW()
      ORDER BY starts_at DESC
      LIMIT 10"
)->fetchAll();

Templates::header('Termine', '/termine.php');
?>

<section class="max-w-5xl mx-auto px-4 py-12 md:py-16">
  <!-- Header -->
  <div class="flex flex-col md:flex-row md:items-center justify-between border-b border-stone-200/60 pb-6 mb-10 gap-4">
    <div>
      <div class="text-xs uppercase tracking-widest text-honey-700 font-extrabold mb-1" data-edit-resource="page_blocks" data-edit-id="termine.eyebrow">
        <?= h(block('termine.eyebrow', 'Vereinskalender')) ?>
      </div>
      <h1 class="text-3xl md:text-4xl font-display font-extrabold text-stone-900 tracking-tight" data-edit-resource="page_blocks" data-edit-id="termine.title">
        <?= h(block('termine.title', 'Termine')) ?>
      </h1>
      <p class="text-stone-500 mt-2 text-base max-w-xl" data-edit-resource="page_blocks" data-edit-id="termine.subtitle">
        <?= h(block('termine.subtitle', 'Hier finden Sie alle aktuellen Treffen, Schulungen, Standschauen und sonstige Termine unseres Vereins.')) ?>
      </p>
    </div>
    <div class="shrink-0 flex items-center gap-3">
      <button type="button" class="bzv-edit-new bg-amber-700 hover:bg-amber-800 text-white font-bold px-3 py-2 rounded-xl text-sm" data-edit-resource="termine">+ Termin</button>
      <a href="<?= h(webcal_url('/termine.ics')) ?>"
         title="Klick öffnet die Kalender-App und fragt nach Abo. Falls dein Browser webcal:// nicht kennt: rechts auf den Download-Link."
         class="inline-flex items-center gap-2 bg-amber-50 border border-amber-200 hover:bg-honey-50 text-honey-800 font-bold px-4 py-3 rounded-xl shadow-sm text-sm transition-all active:scale-98">
        <span>📅</span> Kalender abonnieren
      </a>
      <a href="/termine.ics" download
         title="Termine als .ics-Datei herunterladen (für Outlook-Import o.ä.)"
         class="text-xs text-stone-500 hover:text-honey-700 underline">
        oder Download (.ics)
      </a>
    </div>
  </div>

  <!-- Kommende Termine -->
  <h2 class="text-2xl font-display font-extrabold text-stone-900 mb-6" data-edit-resource="page_blocks" data-edit-id="termine.upcoming.title">
    <?= h(block('termine.upcoming.title', 'Kommende Termine')) ?>
  </h2>
  
  <?php if (!$upcoming): ?>
    <div class="bg-white border border-stone-200/60 rounded-2xl p-8 text-center shadow-sm">
      <span class="text-3xl">📅</span>
      <p class="text-stone-500 mt-2 font-medium" data-edit-resource="page_blocks" data-edit-id="termine.upcoming.empty">
        <?= h(block('termine.upcoming.empty', 'Aktuell sind keine kommenden Termine eingetragen.')) ?>
      </p>
    </div>
  <?php else: ?>
    <div class="space-y-6">
      <?php foreach ($upcoming as $t): 
        $timestamp = strtotime($t['starts_at']);
        $day = date('d', $timestamp);
        $month = format_date_de($t['starts_at'], 'M');
        $weekday = format_date_de($t['starts_at'], 'D');
      ?>
        <div class="bg-white border border-stone-200/60 hover:border-honey-200 rounded-2xl p-6 shadow-sm hover:shadow-md transition-all duration-200 flex flex-col md:flex-row gap-6" data-edit-resource="termine" data-edit-id="<?= (int)$t['id'] ?>">
          <!-- Datum-Badge -->
          <div class="flex md:flex-col items-center justify-center bg-gradient-to-br from-amber-50 to-amber-100/60 border border-amber-100 rounded-xl px-4 py-3 md:w-28 shrink-0 gap-3 md:gap-0">
            <span class="block text-2xl md:text-3xl font-display font-extrabold text-honey-900 leading-none"><?= $day ?></span>
            <span class="block text-xs font-extrabold text-honey-700 uppercase tracking-widest md:mt-1.5"><?= $month ?></span>
            <span class="block text-[10px] font-bold text-stone-400 uppercase tracking-wider md:mt-0.5"><?= $weekday ?></span>
          </div>
          
          <!-- Termin-Details -->
          <div class="flex-1 space-y-3">
            <div class="flex flex-wrap items-center gap-3">
              <span class="bg-stone-100 text-stone-600 px-2.5 py-1 rounded-md text-xs font-bold flex items-center gap-1.5">
                <span>🕒</span> <?= h(format_date_de($t['starts_at'], 'H:i')) ?> Uhr
              </span>
              <?php if ($t['location']): ?>
                <span class="bg-amber-50/50 border border-amber-100/30 text-honey-800 px-2.5 py-1 rounded-md text-xs font-semibold flex items-center gap-1">
                  <span>📍</span> <?= h($t['location']) ?>
                </span>
              <?php endif; ?>
            </div>
            
            <h3 class="font-display font-extrabold text-stone-900 text-xl leading-tight"><?= h($t['title']) ?></h3>
            
            <?php if ($t['description']): ?>
              <p class="text-stone-600 text-sm leading-relaxed whitespace-pre-line"><?= h($t['description']) ?></p>
            <?php endif; ?>
          </div>
        </div>
      <?php endforeach; ?>
    </div>
  <?php endif; ?>

  <!-- Vergangene Termine -->
  <?php if ($past): ?>
    <div class="mt-16 border-t border-stone-200/60 pt-10">
      <h2 class="text-2xl font-display font-extrabold text-stone-900 mb-6" data-edit-resource="page_blocks" data-edit-id="termine.past.title">
        <?= h(block('termine.past.title', 'Rückblick: Vergangene Termine')) ?>
      </h2>
      <div class="bg-white border border-stone-200/60 rounded-2xl overflow-hidden shadow-sm">
        <ul class="divide-y divide-stone-100">
          <?php foreach ($past as $t): ?>
            <li class="px-6 py-4 flex flex-col sm:flex-row sm:items-center justify-between gap-2 hover:bg-stone-50/40 transition-colors">
              <div class="flex items-center gap-4">
                <span class="text-xs font-bold text-stone-400 shrink-0 w-20"><?= h(format_date_de($t['starts_at'], 'd.m.Y')) ?></span>
                <span class="font-semibold text-stone-700 text-sm"><?= h($t['title']) ?></span>
              </div>
              <?php if ($t['location']): ?>
                <span class="text-xs font-semibold text-stone-400 bg-stone-50 px-2.5 py-1 rounded-md w-fit sm:self-center">
                  📍 <?= h($t['location']) ?>
                </span>
              <?php endif; ?>
            </li>
          <?php endforeach; ?>
        </ul>
      </div>
    </div>
  <?php endif; ?>

  <!-- Hinweis -->
  <div class="mt-12 bg-stone-100 border border-stone-200/50 rounded-2xl p-5 text-sm text-stone-500 flex gap-3">
    <span class="text-lg">ℹ️</span>
    <p class="leading-relaxed">
      <strong>Wichtiger Hinweis:</strong> Termine werden teils witterungsbedingt spontan angepasst. Sollten Sie Fragen zu einer Veranstaltung haben, wenden Sie sich bitte direkt an den <a href="/vorstand.php" class="text-honey-700 font-semibold hover:underline">Vorstand</a>.
    </p>
  </div>
</section>

<?php Templates::footer(); ?>
