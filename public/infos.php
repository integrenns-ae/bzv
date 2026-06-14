<?php
require_once __DIR__ . '/../library/_init.php';

$pdo = Database::pdo();

$sections = [
    'mitgliedschaft' => 'Mitgliedschaft & Beitritt',
    'recht'          => 'Recht & Verordnungen',
    'varroa'         => 'Bienengesundheit & Varroa',
    'formulare'      => 'Formulare & Vorlagen',
    'videos'         => 'Videos',
    'links'          => 'Weiterführende Links',
];

$infos = [];
$stmt = $pdo->query("SELECT id, section, title, body, link_url, download_path
                       FROM infos
                      WHERE is_published = 1
                      ORDER BY section, sort_order ASC, id ASC");
foreach ($stmt as $row) {
    $infos[$row['section']][] = $row;
}
$links = [];
$stmt = $pdo->query("SELECT id, section, title, url FROM links ORDER BY section, sort_order ASC, id ASC");
foreach ($stmt as $row) {
    $links[$row['section']][] = $row;
}

Templates::header('Infos für Imker', '/infos.php');
?>

<section class="max-w-5xl mx-auto px-4 py-12 md:py-16">
  <!-- Header -->
  <div class="border-b border-stone-200/60 pb-6 mb-8">
    <div class="text-xs uppercase tracking-widest text-honey-700 font-extrabold mb-1" data-edit-resource="page_blocks" data-edit-id="infos.eyebrow">
      <?= h(block('infos.eyebrow', 'Bibliothek')) ?>
    </div>
    <h1 class="text-3xl md:text-4xl font-display font-extrabold text-stone-900 tracking-tight" data-edit-resource="page_blocks" data-edit-id="infos.title">
      <?= h(block('infos.title', 'Infos für Imker')) ?>
    </h1>
    <p class="text-stone-500 mt-2 text-base max-w-xl" data-edit-resource="page_blocks" data-edit-id="infos.subtitle">
      <?= h(block('infos.subtitle', 'Praxis-Hinweise, Leitfäden, Formulare und nützliche Verweise rund um die Bienenhaltung und Imkerei.')) ?>
    </p>

    <!-- Sub-Nav -->
    <nav class="mt-8 flex flex-wrap gap-2 text-xs">
      <?php foreach ($sections as $key => $label): ?>
        <a href="#<?= h($key) ?>"
           class="bg-white border border-stone-200 text-stone-600 hover:bg-honey-50 hover:border-honey-200 px-4 py-2.5 rounded-full font-bold transition-all shadow-sm">
          <?= h(section_label($key, $label)) ?>
        </a>
      <?php endforeach; ?>
    </nav>
  </div>

  <!-- Sections -->
  <div class="space-y-16">
    <?php foreach ($sections as $key => $label): ?>
      <section id="<?= h($key) ?>" class="scroll-mt-24">
        <!-- Section Header -->
        <div class="flex items-center gap-3 border-b border-amber-100 pb-3 mb-6">
          <img src="/assets/favicon.png" alt="" class="w-6 h-6 object-contain shrink-0" width="256" height="256">
          <h2 class="text-2xl font-display font-extrabold text-stone-900" data-edit-resource="page_blocks" data-edit-id="infos.section.<?= h($key) ?>.label">
            <?= h(section_label($key, $label)) ?>
          </h2>
        </div>

        <?php
          // Infos + externe Links zu einer einheitlichen Liste verschmelzen,
          // damit beide Typen optisch identisch gerendert werden.
          $items = $infos[$key] ?? [];
          foreach ($items as &$i) { $i['_resource'] = 'infos'; }
          unset($i);
          foreach ($links[$key] ?? [] as $l) {
            $items[] = [
              'id'            => $l['id'],
              'title'         => $l['title'],
              'body'          => null,
              'link_url'      => $l['url'],
              'download_path' => null,
              '_resource'     => 'links',
            ];
          }
        ?>
        <?php if ($items): ?>
          <div class="grid gap-6">
            <?php foreach ($items as $i): ?>
              <article class="bg-white border border-stone-200/60 rounded-2xl p-6 shadow-sm hover:border-honey-200 hover:shadow-md transition-all duration-250"
                       data-edit-resource="<?= h($i['_resource']) ?>" data-edit-id="<?= (int)$i['id'] ?>">
                <h3 class="font-display font-extrabold text-stone-900 text-lg md:text-xl leading-tight"><?= h($i['title']) ?></h3>

                <?php if (!empty($i['body'])): ?>
                  <div class="prose-bzv mt-3 text-stone-600 text-sm md:text-base">
                    <?= clean_html($i['body']) ?>
                  </div>
                <?php endif; ?>

                <?php if (!empty($i['download_path']) || !empty($i['link_url'])): ?>
                  <div class="<?= !empty($i['body']) ? 'mt-5 pt-4 border-t border-stone-50' : 'mt-4' ?> flex flex-wrap gap-3">
                    <?php if (!empty($i['download_path'])): ?>
                      <a href="/downloads/<?= h($i['download_path']) ?>"
                         class="inline-flex items-center gap-1.5 bg-amber-50 hover:bg-amber-100 text-honey-800 font-bold px-4 py-2 rounded-xl text-xs transition-colors shadow-sm">
                         <span>⬇</span> Download (PDF)
                      </a>
                    <?php endif; ?>
                    <?php if (!empty($i['link_url'])): ?>
                      <a href="<?= h($i['link_url']) ?>" target="_blank" rel="noopener"
                         class="inline-flex items-center gap-1 bg-white border border-stone-200 hover:bg-stone-50 text-stone-700 font-bold px-4 py-2 rounded-xl text-xs transition-colors shadow-sm">
                         Mehr erfahren ↗
                      </a>
                    <?php endif; ?>
                  </div>
                <?php endif; ?>
              </article>
            <?php endforeach; ?>
          </div>
        <?php else: ?>
          <div class="bg-stone-50 border border-stone-200/40 rounded-2xl p-6 text-center text-sm text-stone-400 font-medium italic" data-edit-resource="page_blocks" data-edit-id="infos.empty.section">
            <?= h(block('infos.empty.section', 'Aktuell keine Einträge in dieser Kategorie.')) ?>
          </div>
        <?php endif; ?>
      </section>
    <?php endforeach; ?>
  </div>
</section>

<?php Templates::footer(); ?>
