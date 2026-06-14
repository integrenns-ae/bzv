<?php
require_once __DIR__ . '/../library/_init.php';

$pdo = Database::pdo();

$gallery = $pdo->query(
    "SELECT id, image_path, alt_text, caption
       FROM gallery
      WHERE is_published = 1
      ORDER BY sort_order ASC, id ASC"
)->fetchAll();

$termineNext = $pdo->query(
    "SELECT id, starts_at, title, location
       FROM termine
      WHERE is_published = 1 AND starts_at >= NOW()
      ORDER BY starts_at ASC
      LIMIT 3"
)->fetchAll();

$newsLatest = $pdo->query(
    "SELECT id, slug, title, published_at, image_path, body
       FROM news
      WHERE is_published = 1
        AND (expires_at IS NULL OR expires_at >= CURDATE())
      ORDER BY published_at DESC, id DESC
      LIMIT 3"
)->fetchAll();

Templates::header('Willkommen', '/');
?>

<!-- Hero: Willkommen + Bildergalerie -->
<section class="relative bg-gradient-to-b from-amber-50/70 via-amber-100/30 to-stone-50 overflow-hidden py-12 md:py-20 border-b border-amber-100/40">
  <div class="absolute inset-0 opacity-[0.03] pointer-events-none bg-[radial-gradient(#d97706_1.5px,transparent_1.5px)] [background-size:24px_24px]"></div>

  <div class="max-w-5xl mx-auto px-4 grid md:grid-cols-12 gap-8 md:gap-10 items-center relative z-10">
    <div class="md:col-span-7 space-y-5">
      <div class="inline-flex items-center gap-2 bg-amber-100/80 border border-amber-200/50 rounded-full pl-1.5 pr-3 py-1 text-xs font-semibold text-honey-800 tracking-wide" data-edit-resource="page_blocks" data-edit-id="home.hero.eyebrow">
        <img src="/assets/favicon.png" alt="" class="w-4 h-4 object-contain" width="256" height="256">
        <span><?= h(block('home.hero.eyebrow', 'Herzlich willkommen')) ?></span>
      </div>
      <h1 class="text-4xl md:text-5xl font-display font-extrabold text-stone-900 leading-tight" data-edit-resource="page_blocks" data-edit-id="home.hero.title">
        <?= h(block('home.hero.title', 'Bienenzuchtverein Grünberg und Umgebung e.V.')) ?>
      </h1>
      <div class="text-stone-700 text-base md:text-lg leading-relaxed max-w-xl prose-bzv [&>p]:my-3" data-edit-resource="page_blocks" data-edit-id="home.welcome.body">
        <?= block_html('home.welcome.body',
            '<p>Schön, dass Sie hier sind! Wir sind eine engagierte Gemeinschaft von Imkerinnen und Imkern '
          . 'in Grünberg und Umgebung – mit Leidenschaft für die Honigbiene und ihren Beitrag zu unserer Natur.</p>'
          . '<p>Bei uns finden Sie Ansprechpartner für Bienenhaltung, Standverkauf, Schwarmrettung und alles, '
          . 'was rund um die Imkerei wichtig ist – egal ob Sie selbst imkern möchten, regionalen Honig suchen '
          . 'oder einen Schwarm in Ihrem Garten entdeckt haben.</p>') ?>
      </div>
      <div class="flex flex-wrap gap-3 pt-1">
        <a href="/mitglied-werden.php"
           class="bg-honey-700 hover:bg-honey-800 text-white px-6 py-3 rounded-xl font-bold transition-all shadow-md hover:shadow-lg active:scale-95">
          Mitglied werden
        </a>
        <a href="/infos.php"
           class="bg-white border border-stone-200 text-stone-700 px-6 py-3 rounded-xl font-bold transition-all hover:bg-stone-50 hover:border-stone-300 shadow-sm active:scale-95">
          Infos für Imker
        </a>
      </div>
    </div>

    <!-- Bildergalerie -->
    <div class="md:col-span-5">
      <?php if ($gallery): ?>
        <div class="relative aspect-[4/3] rounded-2xl overflow-hidden shadow-xl shadow-honey-900/10 bg-white" id="bzv-gallery"
             data-edit-resource="gallery">
          <?php foreach ($gallery as $i => $g): ?>
            <figure class="absolute inset-0 transition-opacity duration-700 <?= $i === 0 ? 'opacity-100' : 'opacity-0' ?>"
                    data-gal-slide>
              <img src="/bilder/<?= h($g['image_path']) ?>"
                   alt="<?= h($g['alt_text'] ?? '') ?>"
                   class="w-full h-full object-cover"
                   loading="<?= $i === 0 ? 'eager' : 'lazy' ?>">
              <?php if (!empty($g['caption'])): ?>
                <figcaption class="absolute bottom-0 inset-x-0 bg-gradient-to-t from-stone-900/70 to-transparent text-white text-sm font-semibold p-3">
                  <?= h($g['caption']) ?>
                </figcaption>
              <?php endif; ?>
            </figure>
          <?php endforeach; ?>
          <?php if (count($gallery) > 1): ?>
            <div class="absolute bottom-3 right-3 flex gap-1.5">
              <?php foreach ($gallery as $i => $g): ?>
                <button type="button" data-gal-dot="<?= $i ?>" aria-label="Bild <?= $i + 1 ?>"
                        class="w-2 h-2 rounded-full bg-white/60 hover:bg-white transition-all <?= $i === 0 ? '!bg-white !w-5' : '' ?>"></button>
              <?php endforeach; ?>
            </div>
          <?php endif; ?>
        </div>
        <?php if (count($gallery) > 1): ?>
          <script>
            (function() {
              const root  = document.getElementById('bzv-gallery');
              const slides = root.querySelectorAll('[data-gal-slide]');
              const dots   = root.querySelectorAll('[data-gal-dot]');
              let i = 0;
              function show(n) {
                slides.forEach((s, k) => s.classList.toggle('opacity-100', k === n));
                slides.forEach((s, k) => s.classList.toggle('opacity-0',   k !== n));
                dots.forEach((d, k) => {
                  d.classList.toggle('!bg-white', k === n);
                  d.classList.toggle('!w-5',      k === n);
                });
                i = n;
              }
              dots.forEach((d, k) => d.addEventListener('click', () => show(k)));
              setInterval(() => show((i + 1) % slides.length), 5000);
            })();
          </script>
        <?php endif; ?>
      <?php else: ?>
        <div class="aspect-[4/3] rounded-2xl bg-amber-50 border border-amber-200/60 flex items-center justify-center text-amber-700 text-sm">
          Noch keine Bilder in der Galerie.
        </div>
      <?php endif; ?>
    </div>
  </div>
</section>

<!-- Schwarm-Notfall (zentral, nur saisonal) -->
<?php if (is_schwarm_saison()): ?>
<section class="max-w-3xl mx-auto px-4 py-12">
  <div class="bg-white border-2 border-amber-500 rounded-2xl shadow-xl shadow-honey-900/5 p-6 md:p-8 relative overflow-hidden">
    <div class="absolute top-0 inset-x-0 h-1.5 bg-gradient-to-r from-amber-400 to-amber-600"></div>
    <div class="flex items-center justify-between">
      <span class="text-xs uppercase tracking-widest text-honey-700 font-extrabold bg-amber-50 px-2.5 py-1 rounded-md">Notfall-Meldung</span>
      <span class="flex h-2 w-2 relative">
        <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-red-400 opacity-75"></span>
        <span class="relative inline-flex rounded-full h-2 w-2 bg-red-500"></span>
      </span>
    </div>
    <h2 class="text-2xl md:text-3xl font-display font-extrabold text-stone-900 mt-3" data-edit-resource="page_blocks" data-edit-id="home.hero.schwarm.title">
      <?= h(block('home.hero.schwarm.title', 'Schwarm entdeckt?')) ?>
    </h2>
    <p class="text-stone-500 mt-2 text-sm md:text-base leading-relaxed" data-edit-resource="page_blocks" data-edit-id="home.schwarm.intro">
      <?= h(block('home.schwarm.intro', 'Wir holen Bienenschwärme kostenlos, sicher und fachgerecht ab.')) ?>
    </p>
    <?php
      $sTel  = block('contact.schwarm.tel', SCHWARM_TEL);
      $sE164 = tel_to_e164($sTel);
    ?>
    <div class="mt-5 flex flex-col sm:flex-row gap-3">
      <a href="tel:<?= h($sE164) ?>"
         class="flex-1 flex items-center justify-center gap-3 bg-amber-500 hover:bg-amber-600 text-stone-950 font-extrabold py-4 px-6 rounded-xl text-lg shadow-md hover:shadow-lg transition-all active:scale-98">
        <span class="text-xl">📞</span> <?= h($sTel) ?>
      </a>
      <a href="https://wa.me/<?= h(ltrim($sE164, '+')) ?>"
         target="_blank" rel="noopener"
         class="flex-1 flex items-center justify-center gap-2 bg-emerald-600 hover:bg-emerald-700 text-white font-bold py-4 px-6 rounded-xl text-lg shadow-md transition-all active:scale-98">
        <span>💬</span> WhatsApp
      </a>
    </div>
    <p class="text-xs text-stone-500 text-center mt-3">
      <a href="/schwarm.php" class="hover:text-honey-700">Mehr Infos zur Schwarmrettung →</a>
    </p>
  </div>
</section>
<?php endif; ?>

<!-- Nächste Termine -->
<section class="max-w-5xl mx-auto px-4 py-16">
  <div class="flex items-baseline justify-between mb-8 border-b border-stone-100 pb-4">
    <h2 class="text-3xl font-display font-extrabold text-stone-900 tracking-tight" data-edit-resource="page_blocks" data-edit-id="home.termine.title">
      <?= h(block('home.termine.title', 'Nächste Termine')) ?>
    </h2>
    <div class="flex items-center gap-3">
      <button type="button" class="bzv-edit-new text-xs bg-amber-700 hover:bg-amber-800 text-white font-bold px-3 py-1.5 rounded-full" data-edit-resource="termine">+ Termin</button>
      <a href="/termine.php" class="text-honey-700 font-bold text-sm hover:text-honey-900 transition-colors flex items-center gap-1">Alle Termine <span class="text-xs">→</span></a>
    </div>
  </div>
  
  <?php if (!$termineNext): ?>
    <div class="bg-white border border-stone-200/60 rounded-2xl p-8 text-center shadow-sm">
      <span class="text-3xl">📅</span>
      <p class="text-stone-500 mt-2 font-medium" data-edit-resource="page_blocks" data-edit-id="home.termine.empty">
        <?= h(block('home.termine.empty', 'Aktuell sind keine kommenden Termine eingetragen.')) ?>
      </p>
    </div>
  <?php else: ?>
    <ul class="grid sm:grid-cols-3 gap-6">
      <?php foreach ($termineNext as $t): 
        $timestamp = strtotime($t['starts_at']);
        $day = date('d', $timestamp);
        $month = format_date_de($t['starts_at'], 'M');
      ?>
        <li class="bg-white border border-stone-200/60 hover:border-honey-200 rounded-2xl p-5 shadow-sm hover:shadow-md transition-all duration-200 flex flex-col justify-between group" data-edit-resource="termine" data-edit-id="<?= (int)$t['id'] ?>">
          <div>
            <div class="flex items-center justify-between">
              <!-- Kalender-Badge -->
              <div class="bg-honey-50 border border-honey-100/60 rounded-xl px-3 py-1 text-center">
                <span class="block text-xl font-display font-extrabold text-honey-800 leading-tight"><?= $day ?></span>
                <span class="block text-[10px] font-bold text-honey-700 uppercase tracking-wider"><?= $month ?></span>
              </div>
              <span class="text-xs font-semibold text-stone-400"><?= h(format_date_de($t['starts_at'], 'H:i')) ?> Uhr</span>
            </div>
            <div class="font-bold text-stone-900 text-lg mt-4 leading-snug group-hover:text-honey-800 transition-colors"><?= h($t['title']) ?></div>
          </div>
          <?php if ($t['location']): ?>
            <div class="text-xs font-semibold text-stone-500 mt-4 flex items-center gap-1.5 bg-stone-50 px-2.5 py-1.5 rounded-lg w-fit">
              <span class="text-stone-400">📍</span> <?= h($t['location']) ?>
            </div>
          <?php endif; ?>
        </li>
      <?php endforeach; ?>
    </ul>
  <?php endif; ?>
</section>

<!-- Aktuelles -->
<section class="bg-white border-y border-stone-200/60 relative py-16">
  <div class="max-w-5xl mx-auto px-4">
    <div class="flex items-baseline justify-between mb-8 border-b border-stone-100 pb-4">
      <h2 class="text-3xl font-display font-extrabold text-stone-900 tracking-tight" data-edit-resource="page_blocks" data-edit-id="home.news.title">
        <?= h(block('home.news.title', 'Aktuelles vom Verein')) ?>
      </h2>
      <div class="flex items-center gap-3">
        <button type="button" class="bzv-edit-new text-xs bg-amber-700 hover:bg-amber-800 text-white font-bold px-3 py-1.5 rounded-full" data-edit-resource="news">+ Beitrag</button>
        <a href="/aktuelles.php" class="text-honey-700 font-bold text-sm hover:text-honey-900 transition-colors flex items-center gap-1">Alle Beiträge <span class="text-xs">→</span></a>
      </div>
    </div>
    
    <?php if (!$newsLatest): ?>
      <div class="bg-stone-50 border border-stone-200/60 rounded-2xl p-8 text-center">
        <span class="text-3xl">📰</span>
        <p class="text-stone-500 mt-2 font-medium" data-edit-resource="page_blocks" data-edit-id="home.news.empty">
          <?= h(block('home.news.empty', 'Noch keine Beiträge vorhanden.')) ?>
        </p>
      </div>
    <?php else: ?>
      <div class="grid md:grid-cols-3 gap-8">
        <?php foreach ($newsLatest as $n):
          $excerpt = mb_substr(trim(strip_tags($n['body'])), 0, 120, 'UTF-8');
          if (mb_strlen($n['body'], 'UTF-8') > 120) $excerpt .= '…';
        ?>
          <a href="/aktuelles-detail.php?slug=<?= h($n['slug']) ?>" class="group block bg-stone-50 border border-stone-200/50 hover:border-honey-200/70 rounded-2xl overflow-hidden hover:shadow-lg transition-all duration-300 flex flex-col h-full" data-edit-resource="news" data-edit-id="<?= (int)$n['id'] ?>">
            <div class="aspect-video w-full overflow-hidden bg-stone-100">
              <?php if ($n['image_path']): ?>
                <img src="/bilder/<?= h($n['image_path']) ?>" alt=""
                     class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-300">
              <?php else: ?>
                <?= stock_picture(stock_fallback_for_news((int)$n['id']), [
                  'class' => 'w-full h-full object-cover group-hover:scale-105 transition-transform duration-300',
                  'alt'   => $n['title'],
                ]) ?>
              <?php endif; ?>
            </div>
            
            <div class="p-5 flex-1 flex flex-col justify-between">
              <div>
                <div class="text-[10px] font-bold text-honey-700 uppercase tracking-widest"><?= h(format_date_de($n['published_at'])) ?></div>
                <div class="font-bold text-stone-900 text-lg mt-2 leading-snug group-hover:text-honey-800 transition-colors"><?= h($n['title']) ?></div>
                <div class="text-stone-500 text-sm mt-2.5 leading-relaxed"><?= h($excerpt) ?></div>
              </div>
              <div class="text-xs font-bold text-honey-700 mt-4 group-hover:text-honey-900 transition-colors flex items-center gap-1">Weiterlesen <span class="transform group-hover:translate-x-0.5 transition-transform">→</span></div>
            </div>
          </a>
        <?php endforeach; ?>
      </div>
    <?php endif; ?>
  </div>
</section>

<!-- Kurzvorstellung -->
<section class="max-w-5xl mx-auto px-4 py-20">
  <div class="bg-gradient-to-r from-amber-50 to-amber-100/20 border border-amber-200/30 rounded-3xl p-8 md:p-12 shadow-sm grid md:grid-cols-12 gap-8 items-center">
    <div class="md:col-span-8">
      <div class="space-y-4 prose-bzv">
        <h2 class="text-3xl font-display font-extrabold text-honey-900 !mt-0" data-edit-resource="page_blocks" data-edit-id="home.about.title">
          <?= h(block('home.about.title', 'Über unseren Verein')) ?>
        </h2>
        <div class="text-stone-700 text-base leading-relaxed [&>p]:my-3" data-edit-resource="page_blocks" data-edit-id="home.about.body">
          <?= block_html('home.about.body', '') ?>
        </div>
      </div>
      <div class="pt-6 flex flex-wrap items-center gap-2">
        <a href="/mitglied-werden.php" class="inline-block bg-honey-800 hover:bg-honey-900 text-white !text-white no-underline text-sm font-bold px-5 py-3 rounded-xl shadow-md shadow-honey-950/10 transition-colors">Jetzt Mitglied werden</a>
        <a href="/termine.php" class="inline-block text-honey-800 hover:text-honey-900 text-sm font-bold px-5 py-3 transition-colors">Unsere Termine ansehen →</a>
      </div>
    </div>
    <div class="md:col-span-4 hidden md:flex items-center justify-center">
      <img src="/assets/logo.svg" alt="<?= h(SITE_NAME) ?>" class="max-h-40 w-auto filter drop-shadow-md select-none">
    </div>
  </div>
</section>

<?php Templates::footer(); ?>
