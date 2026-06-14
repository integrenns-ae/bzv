<?php
$pageTitle = 'Texte';
$current   = 'page_blocks.php';
include __DIR__ . '/_layout.php';

$pdo    = Database::pdo();
$action = $_GET['action'] ?? 'list';
$slug   = (string)($_GET['slug'] ?? '');
$me     = Auth::user();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    $post = $_POST;
    $slug = (string)($post['slug'] ?? '');
    $title = trim((string)($post['title'] ?? ''));
    $body  = trim((string)($post['body']  ?? ''));
    if ($slug === '' || !preg_match('/^[a-z0-9._-]{2,100}$/', $slug)) {
        flash('error', 'Slug muss [a-z0-9._-], 2–100 Zeichen sein.');
        redirect_after_save('/admin/page_blocks.php');
    }
    $pdo->prepare("INSERT INTO page_blocks (slug, title, body, updated_by) VALUES (?,?,?,?)
                   ON DUPLICATE KEY UPDATE title=VALUES(title), body=VALUES(body), updated_by=VALUES(updated_by)")
        ->execute([$slug, $title ?: null, $body, (int)($me['id'] ?? 0) ?: null]);
    flash('success', 'Text gespeichert.');
    redirect_after_save('/admin/page_blocks.php');
}

if ($action === 'edit') {
    $st = $pdo->prepare("SELECT * FROM page_blocks WHERE slug = ?"); $st->execute([$slug]);
    $row = $st->fetch();
    if (!$row) {
        echo '<p>Block nicht gefunden.</p>';
        include __DIR__ . '/_layout_end.php';
        exit;
    }
    ?>
    <h1 class="text-2xl font-bold mb-1">Text bearbeiten</h1>
    <p class="text-sm text-stone-500 mb-4"><?= h($row['title'] ?? $row['slug']) ?> · <code class="text-xs"><?= h($row['slug']) ?></code></p>

    <form method="post" class="bg-white border border-stone-200 rounded p-5 space-y-4 max-w-2xl">
      <?= csrf_field() ?>
      <input type="hidden" name="slug"  value="<?= h($row['slug']) ?>">
      <input type="hidden" name="title" value="<?= h($row['title'] ?? '') ?>">
      <?php
        // Rich-Editor (Trix) nur für Lauftext-Blocks (body/intro/helper/footer/subtitle),
        // sonst einfacher einzeiliger Input.
        $isRich = (bool)preg_match('/\.(body|intro|helper|footer|subtitle)$/', $row['slug']);
        $isMultiline = $isRich || str_contains((string)($row['body'] ?? ''), "\n");
      ?>
      <?php if ($isRich): ?>
        <div>
          <label class="text-sm font-semibold" for="pb-body">Text-Inhalt</label>
          <input id="pb-body-input" type="hidden" name="body" value="<?= h($row['body'] ?? '') ?>">
          <trix-editor input="pb-body-input" id="pb-body" class="mt-1"></trix-editor>
          <p class="text-xs text-stone-500 mt-1">Mit der Symbolleiste fett/kursiv, Überschriften, Listen und Links einfügen.</p>
        </div>
      <?php elseif ($isMultiline): ?>
        <label class="block">
          <span class="text-sm font-semibold">Text-Inhalt</span>
          <textarea name="body" rows="4" class="w-full border border-stone-300 rounded px-3 py-2 mt-1"><?= h($row['body'] ?? '') ?></textarea>
          <p class="text-xs text-stone-500 mt-1">Einfacher Text — Absätze durch Leerzeile trennen.</p>
        </label>
      <?php else: ?>
        <label class="block">
          <span class="text-sm font-semibold">Text-Inhalt</span>
          <input type="text" name="body" maxlength="500" value="<?= h($row['body'] ?? '') ?>"
                 class="w-full border border-stone-300 rounded px-3 py-2 mt-1">
          <p class="text-xs text-stone-500 mt-1">Kurzer Text (z.B. Überschrift, Eyebrow).</p>
        </label>
      <?php endif; ?>
      <div class="flex gap-2">
        <button type="submit" class="bg-amber-700 hover:bg-amber-800 text-white font-semibold px-4 py-2 rounded">Speichern</button>
        <?php if (empty($_GET['minimal'])): ?>
          <a href="/admin/page_blocks.php" class="px-4 py-2 rounded border border-stone-300 hover:bg-stone-50">Abbrechen</a>
        <?php endif; ?>
      </div>
    </form>
    <?php
} else {
    $rows = $pdo->query("SELECT slug, title, LEFT(body, 100) AS preview, updated_at FROM page_blocks ORDER BY slug")->fetchAll();
    ?>
    <h1 class="text-2xl font-bold mb-4">Texte (Page-Blocks)</h1>
    <p class="text-sm text-stone-600 mb-4">Statische Texte auf der Webseite (Hero-Slogan, Über-uns-Sektion, Schwarm-Tipps usw.). Diese erscheinen auf den öffentlichen Seiten und können hier oder direkt im Edit-Modus bearbeitet werden.</p>
    <div class="bg-white border border-stone-200 rounded overflow-hidden">
      <table class="w-full text-sm">
        <thead class="bg-stone-100 text-left">
          <tr><th class="px-3 py-2">Slug</th><th>Bezeichnung</th><th>Vorschau</th><th>Geändert</th><th></th></tr>
        </thead>
        <tbody>
          <?php foreach ($rows as $r): ?>
            <tr class="border-t">
              <td class="px-3 py-2"><code class="text-xs"><?= h($r['slug']) ?></code></td>
              <td><?= h($r['title'] ?? '') ?></td>
              <td class="text-xs text-stone-500"><?= h(mb_strimwidth($r['preview'] ?? '', 0, 70, '…')) ?></td>
              <td class="text-xs text-stone-400"><?= h(format_date_de($r['updated_at'])) ?></td>
              <td class="text-right pr-2 whitespace-nowrap">
                <a href="/admin/page_blocks.php?action=edit&slug=<?= h($r['slug']) ?>" class="text-amber-700 hover:underline">bearbeiten</a>
              </td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
    <?php
}

include __DIR__ . '/_layout_end.php';
