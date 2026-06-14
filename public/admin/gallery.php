<?php
$pageTitle = 'Bildergalerie';
$current   = 'gallery.php';
include __DIR__ . '/_layout.php';

$pdo = Database::pdo();
$action = $_GET['action'] ?? 'list';
$id     = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$bildBase = __DIR__ . '/../bilder';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    $post = $_POST;
    if (($post['_action'] ?? '') === 'delete') {
        $pdo->prepare("DELETE FROM gallery WHERE id = ?")->execute([(int)$post['id']]);
        flash('success', 'Bild gelöscht.');
        redirect_after_save('/admin/gallery.php');
    }
    $id  = (int)($post['id'] ?? 0);
    $alt = trim((string)($post['alt_text'] ?? ''));
    $cap = trim((string)($post['caption']  ?? ''));
    $sort = (int)($post['sort_order'] ?? 0);
    $pub  = !empty($post['is_published']) ? 1 : 0;
    $imagePath = $post['existing_image'] ?? null;
    if (!empty($_FILES['image']['name'])) {
        try {
            $imagePath = store_upload(
                $_FILES['image'], 'galerie',
                unserialize(UPLOAD_ALLOWED_IMAGE_MIME),
                $bildBase
            );
        } catch (Throwable $e) {
            flash('error', 'Bild-Upload: ' . $e->getMessage());
            header('Location: /admin/gallery.php?action=edit' . ($id ? '&id=' . $id : ''));
            exit;
        }
    }
    if (!$imagePath) {
        flash('error', 'Bitte ein Bild auswählen.');
        header('Location: /admin/gallery.php?action=' . ($id ? 'edit&id=' . $id : 'new'));
        exit;
    }
    if ($id) {
        $pdo->prepare("UPDATE gallery SET image_path=?, alt_text=?, caption=?, sort_order=?, is_published=? WHERE id=?")
            ->execute([$imagePath, $alt ?: null, $cap ?: null, $sort, $pub, $id]);
        flash('success', 'Bild aktualisiert.');
    } else {
        $pdo->prepare("INSERT INTO gallery (image_path, alt_text, caption, sort_order, is_published) VALUES (?,?,?,?,?)")
            ->execute([$imagePath, $alt ?: null, $cap ?: null, $sort, $pub]);
        flash('success', 'Bild angelegt.');
    }
    redirect_after_save('/admin/gallery.php');
}

if ($action === 'edit' || $action === 'new') {
    $row = ['id' => 0, 'image_path' => '', 'alt_text' => '', 'caption' => '', 'sort_order' => 0, 'is_published' => 1];
    if ($id) {
        $st = $pdo->prepare("SELECT * FROM gallery WHERE id = ?"); $st->execute([$id]);
        $row = $st->fetch() ?: $row;
    }
    ?>
    <h1 class="text-2xl font-bold mb-4"><?= $row['id'] ? 'Galeriebild bearbeiten' : 'Neues Galeriebild' ?></h1>
    <form method="post" enctype="multipart/form-data" class="bg-white border border-stone-200 rounded p-5 space-y-4 max-w-xl">
      <?= csrf_field() ?>
      <input type="hidden" name="id" value="<?= (int)$row['id'] ?>">
      <input type="hidden" name="existing_image" value="<?= h($row['image_path'] ?? '') ?>">

      <div>
        <span class="text-sm font-semibold">Bild</span>
        <?php if ($row['image_path']): ?>
          <div class="mt-1"><img src="/bilder/<?= h($row['image_path']) ?>" class="max-h-48 rounded"></div>
        <?php endif; ?>
        <input type="file" name="image" accept="image/jpeg,image/png,image/webp" class="block mt-2">
        <?php
          $widgetTargetHidden = 'existing_image';
          $widgetCategory     = 'galerie';
          $widgetDefaultQuery = 'biene imker';
          include __DIR__ . '/_pixabay-widget.php';
        ?>
      </div>

      <label class="block">
        <span class="text-sm font-semibold">Alternativtext (für Screenreader)</span>
        <input type="text" name="alt_text" maxlength="255" value="<?= h($row['alt_text']) ?>"
               class="w-full border border-stone-300 rounded px-3 py-2 mt-1">
      </label>
      <label class="block">
        <span class="text-sm font-semibold">Bildunterschrift (optional, wird auf Bild eingeblendet)</span>
        <input type="text" name="caption" maxlength="255" value="<?= h($row['caption']) ?>"
               class="w-full border border-stone-300 rounded px-3 py-2 mt-1">
      </label>
      <label class="block">
        <span class="text-sm font-semibold">Reihenfolge</span>
        <input type="number" name="sort_order" value="<?= (int)$row['sort_order'] ?>"
               class="border border-stone-300 rounded px-3 py-2 mt-1 w-32">
        <span class="text-xs text-stone-500">kleinere Zahl = weiter vorne</span>
      </label>
      <label class="flex items-center gap-2">
        <input type="checkbox" name="is_published" value="1" <?= !empty($row['is_published']) ? 'checked' : '' ?>>
        <span class="text-sm">sichtbar</span>
      </label>
      <div class="flex gap-2">
        <button type="submit" class="bg-amber-700 hover:bg-amber-800 text-white font-semibold px-4 py-2 rounded">Speichern</button>
        <a href="/admin/gallery.php" class="px-4 py-2 rounded border border-stone-300 hover:bg-stone-50">Abbrechen</a>
      </div>
    </form>
    <?php
} else {
    $rows = $pdo->query("SELECT * FROM gallery ORDER BY sort_order ASC, id ASC")->fetchAll();
    ?>
    <div class="flex justify-between items-baseline mb-4">
      <h1 class="text-2xl font-bold">Bildergalerie (Startseite)</h1>
      <a href="/admin/gallery.php?action=new" class="bg-amber-700 hover:bg-amber-800 text-white px-3 py-2 rounded text-sm">+ Bild</a>
    </div>
    <div class="bg-white border border-stone-200 rounded overflow-hidden">
      <table class="w-full text-sm">
        <thead class="bg-stone-100 text-left">
          <tr><th class="px-3 py-2 w-24">Bild</th><th>Bildunterschrift</th><th>Reihenfolge</th><th>Status</th><th></th></tr>
        </thead>
        <tbody>
          <?php foreach ($rows as $r): ?>
            <tr class="border-t">
              <td class="px-3 py-2">
                <img src="/bilder/<?= h($r['image_path']) ?>" class="w-20 h-14 object-cover rounded">
              </td>
              <td><?= h($r['caption'] ?: '—') ?></td>
              <td><?= (int)$r['sort_order'] ?></td>
              <td><?= $r['is_published'] ? '<span class="text-emerald-700">sichtbar</span>' : '<span class="text-stone-400">aus</span>' ?></td>
              <td class="text-right pr-2 whitespace-nowrap">
                <a href="/admin/gallery.php?action=edit&id=<?= (int)$r['id'] ?>" class="text-amber-700 hover:underline">bearbeiten</a>
                <form method="post" class="inline" onsubmit="return confirm('Bild wirklich löschen?');">
                  <?= csrf_field() ?>
                  <input type="hidden" name="_action" value="delete">
                  <input type="hidden" name="id" value="<?= (int)$r['id'] ?>">
                  <button type="submit" class="text-red-700 hover:underline ml-3">löschen</button>
                </form>
              </td>
            </tr>
          <?php endforeach; ?>
          <?php if (!$rows): ?>
            <tr><td colspan="5" class="px-3 py-6 text-center text-stone-400">Noch keine Bilder.</td></tr>
          <?php endif; ?>
        </tbody>
      </table>
    </div>
    <?php
}

include __DIR__ . '/_layout_end.php';
