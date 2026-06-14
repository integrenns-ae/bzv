<?php
require_once __DIR__ . '/../library/_init.php';

$pdo = Database::pdo();

$yearFilter = isset($_GET['jahr']) ? (int)$_GET['jahr'] : 0;

$where = 'is_published = 1 AND (expires_at IS NULL OR expires_at >= CURDATE())';
$params = [];
if ($yearFilter) {
    $where .= ' AND YEAR(published_at) = :y';
    $params[':y'] = $yearFilter;
}

$stmt = $pdo->prepare("SELECT id, slug, title, published_at, image_path, body
                         FROM news
                        WHERE $where
                        ORDER BY published_at DESC, id DESC");
$stmt->execute($params);
$items = $stmt->fetchAll();

$years = $pdo->query("SELECT DISTINCT YEAR(published_at) AS y FROM news
                       WHERE is_published = 1
                         AND (expires_at IS NULL OR expires_at >= CURDATE())
                       ORDER BY y DESC")
             ->fetchAll(PDO::FETCH_COLUMN);

Templates::header('Aktuelles', '/aktuelles.php');
?>

<section class="max-w-5xl mx-auto px-4 py-12 md:py-16">
  <!-- Header -->
  <div class="border-b border-stone-200/60 pb-6 mb-10">
    <div class="flex items-start justify-between gap-4">
      <div>
        <div class="text-xs uppercase tracking-widest text-honey-700 font-extrabold mb-1" data-edit-resource="page_blocks" data-edit-id="aktuelles.eyebrow">
          <?= h(block('aktuelles.eyebrow', 'Neuigkeiten')) ?>
        </div>
        <h1 class="text-3xl md:text-4xl font-display font-extrabold text-stone-900 tracking-tight" data-edit-resource="page_blocks" data-edit-id="aktuelles.title">
          <?= h(block('aktuelles.title', 'Aktuelles')) ?>
        </h1>
        <p class="text-stone-500 mt-2 text-base max-w-xl" data-edit-resource="page_blocks" data-edit-id="aktuelles.subtitle">
          <?= h(block('aktuelles.subtitle', 'Berichte aus dem Vereinsleben, Informationen zur Bienenhaltung und Berichte über unsere Aktivitäten.')) ?>
        </p>
      </div>
      <button type="button" class="bzv-edit-new shrink-0 bg-amber-700 hover:bg-amber-800 text-white font-bold px-3 py-1.5 rounded-full text-xs" data-edit-resource="news">+ Beitrag</button>
    </div>

    <!-- Filter -->
    <?php if ($years): ?>
      <div class="mt-8 flex flex-wrap items-center gap-2 text-xs">
        <span class="text-stone-400 font-bold uppercase tracking-wider mr-2">Jahr filtern:</span>
        <a href="/aktuelles.php" 
           class="px-4 py-2 rounded-full font-bold transition-all <?= !$yearFilter ? 'bg-honey-700 text-white shadow-md shadow-honey-700/10' : 'bg-white border border-stone-200 text-stone-600 hover:bg-honey-50 hover:border-honey-200' ?>">
           Alle Jahre
        </a>
        <?php foreach ($years as $y): ?>
          <a href="?jahr=<?= (int)$y ?>"
             class="px-4 py-2 rounded-full font-bold transition-all <?= $yearFilter == $y ? 'bg-honey-700 text-white shadow-md shadow-honey-700/10' : 'bg-white border border-stone-200 text-stone-600 hover:bg-honey-50 hover:border-honey-200' ?>">
            <?= (int)$y ?>
          </a>
        <?php endforeach; ?>
      </div>
    <?php endif; ?>
  </div>

  <!-- News Beiträge -->
  <?php if (!$items): ?>
    <div class="bg-white border border-stone-200/60 rounded-2xl p-12 text-center shadow-sm">
      <span class="text-4xl">📰</span>
      <p class="text-stone-500 mt-3 font-medium" data-edit-resource="page_blocks" data-edit-id="aktuelles.empty">
        <?= h(block('aktuelles.empty', 'Keine Beiträge für diesen Filter vorhanden.')) ?>
      </p>
    </div>
  <?php else: ?>
    <div class="grid md:grid-cols-2 gap-8">
      <?php foreach ($items as $n):
        $excerpt = mb_substr(trim(strip_tags($n['body'])), 0, 180, 'UTF-8');
        if (mb_strlen(strip_tags($n['body']), 'UTF-8') > 180) $excerpt .= '…';
      ?>
        <a href="/aktuelles-detail.php?slug=<?= h($n['slug']) ?>"
           class="group block bg-white border border-stone-200/60 hover:border-honey-200 rounded-2xl overflow-hidden hover:shadow-lg transition-all duration-300 flex flex-col h-full"
           data-edit-resource="news" data-edit-id="<?= (int)$n['id'] ?>">
          <div class="aspect-[16/9] w-full overflow-hidden bg-stone-100 relative">
            <?php if ($n['image_path']): ?>
              <img src="/bilder/<?= h($n['image_path']) ?>" alt=""
                   class="w-full h-full object-cover group-hover:scale-103 transition-transform duration-300">
            <?php else: ?>
              <?= stock_picture(stock_fallback_for_news((int)$n['id']), [
                'class' => 'w-full h-full object-cover group-hover:scale-103 transition-transform duration-300',
                'alt'   => $n['title'],
              ]) ?>
            <?php endif; ?>
            <div class="absolute inset-0 bg-gradient-to-t from-black/20 to-transparent opacity-0 group-hover:opacity-100 transition-opacity"></div>
          </div>
          
          <div class="p-6 flex-1 flex flex-col justify-between">
            <div>
              <div class="text-[10px] font-bold text-honey-700 uppercase tracking-widest"><?= h(format_date_de($n['published_at'])) ?></div>
              <h2 class="font-display font-extrabold text-stone-900 text-xl mt-2 leading-snug group-hover:text-honey-850 transition-colors"><?= h($n['title']) ?></h2>
              <p class="text-stone-500 text-sm mt-3 leading-relaxed"><?= h($excerpt) ?></p>
            </div>
            <div class="text-xs font-bold text-honey-700 mt-5 group-hover:text-honey-900 transition-colors flex items-center gap-1.5 pt-4 border-t border-stone-50">
              Beitrag lesen <span class="transform group-hover:translate-x-0.5 transition-transform">→</span>
            </div>
          </div>
        </a>
      <?php endforeach; ?>
    </div>
  <?php endif; ?>
</section>

<?php Templates::footer(); ?>
