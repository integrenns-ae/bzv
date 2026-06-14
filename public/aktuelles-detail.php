<?php
require_once __DIR__ . '/../library/_init.php';

$slug = $_GET['slug'] ?? '';
if (!$slug) {
    http_response_code(404);
    Templates::header('Nicht gefunden');
    echo '<section class="max-w-3xl mx-auto px-4 py-16 text-center"><span class="text-5xl">🔍</span><h1 class="text-3xl font-display font-extrabold text-stone-900 mt-4">Beitrag nicht gefunden</h1><p class="text-stone-500 mt-2 mb-6">Der gesuchte Artikel existiert leider nicht.</p><a href="/aktuelles.php" class="bg-honey-700 hover:bg-honey-800 text-white font-bold px-6 py-3 rounded-xl shadow-md transition-all inline-block text-sm">Zur Übersicht</a></section>';
    Templates::footer();
    exit;
}

$stmt = Database::pdo()->prepare(
    "SELECT * FROM news
      WHERE slug = ?
        AND is_published = 1
        AND (expires_at IS NULL OR expires_at >= CURDATE())
      LIMIT 1"
);
$stmt->execute([$slug]);
$post = $stmt->fetch();

if (!$post) {
    http_response_code(404);
    Templates::header('Nicht gefunden');
    echo '<section class="max-w-3xl mx-auto px-4 py-16 text-center"><span class="text-5xl">🔍</span><h1 class="text-3xl font-display font-extrabold text-stone-900 mt-4">Beitrag nicht gefunden</h1><p class="text-stone-500 mt-2 mb-6">Der gesuchte Artikel existiert leider nicht.</p><a href="/aktuelles.php" class="bg-honey-700 hover:bg-honey-800 text-white font-bold px-6 py-3 rounded-xl shadow-md transition-all inline-block text-sm">Zur Übersicht</a></section>';
    Templates::footer();
    exit;
}

Templates::header($post['title'], '/aktuelles.php');
?>

<article class="max-w-3xl mx-auto px-4 py-12 md:py-16" data-edit-resource="news" data-edit-id="<?= (int)$post['id'] ?>">
  <!-- Backlink -->
  <a href="/aktuelles.php" 
     class="inline-flex items-center gap-1.5 text-xs font-bold text-stone-400 hover:text-honey-700 transition-colors uppercase tracking-widest mb-6">
    <span>←</span> Zur Übersicht
  </a>

  <!-- Metadata -->
  <div class="flex items-center gap-2">
    <span class="bg-honey-50 border border-honey-100 text-honey-800 px-2.5 py-1 rounded-md text-[10px] font-bold uppercase tracking-wider">
      Vereinsnews
    </span>
    <span class="text-xs text-stone-400 font-semibold">
      Veröffentlicht am <?= h(format_date_de($post['published_at'])) ?>
    </span>
  </div>
  
  <!-- Title -->
  <h1 class="text-3xl md:text-4xl font-display font-extrabold text-stone-900 mt-4 leading-tight">
    <?= h($post['title']) ?>
  </h1>

  <!-- Hero Image -->
  <div class="mt-8 rounded-2xl overflow-hidden shadow-md bg-stone-100 border border-stone-200/50">
    <?php if ($post['image_path']): ?>
      <img src="/bilder/<?= h($post['image_path']) ?>" alt="" class="w-full h-auto object-cover max-h-[420px]">
    <?php else: ?>
      <?= stock_picture(stock_fallback_for_news((int)$post['id']), [
        'class' => 'w-full h-auto object-cover max-h-[420px]',
        'alt'   => $post['title'],
      ], 1920) ?>
    <?php endif; ?>
  </div>

  <!-- Rich Text Body -->
  <div class="prose-bzv mt-8">
    <?= clean_html($post['body']) ?>
  </div>

  <!-- Bottom Backlink -->
  <div class="mt-12 pt-8 border-t border-stone-200/60 flex justify-between items-center">
    <a href="/aktuelles.php" 
       class="inline-flex items-center gap-2 bg-stone-100 hover:bg-stone-200 text-stone-700 font-bold px-5 py-3 rounded-xl shadow-sm text-sm transition-all active:scale-98">
      <span>←</span> Zurück zur Übersicht
    </a>
    <img src="/assets/favicon.png" alt="" class="w-7 h-7 object-contain opacity-60" width="256" height="256">
  </div>
</article>

<?php Templates::footer(); ?>
