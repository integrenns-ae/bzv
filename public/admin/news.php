<?php
$pageTitle = 'Aktuelles';
$current   = 'news.php';
include __DIR__ . '/_layout.php';

$pdo    = Database::pdo();
$action = $_GET['action'] ?? 'list';
$id     = isset($_GET['id']) ? (int)$_GET['id'] : 0;

$bildBase = __DIR__ . '/../bilder';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    $post = $_POST;
    if (($post['_action'] ?? '') === 'delete') {
        $pdo->prepare("DELETE FROM news WHERE id = ?")->execute([(int)$post['id']]);
        flash('success', 'Beitrag gelöscht.');
        redirect_after_save('/admin/news.php');
    }
    $title = trim((string)($post['title'] ?? ''));
    $date  = trim((string)($post['published_at'] ?? ''));
    $expires = trim((string)($post['expires_at'] ?? ''));
    $body  = trim((string)($post['body']  ?? ''));
    $pub   = !empty($post['is_published']) ? 1 : 0;
    $id    = (int)($post['id'] ?? 0);

    if ($title === '' || $date === '') {
        flash('error', 'Titel und Datum sind Pflicht.');
        header('Location: /admin/news.php?action=edit' . ($id ? '&id=' . $id : ''));
        exit;
    }

    // Slug eindeutig machen
    $base = slugify($title);
    $slug = $base;
    $i = 2;
    $check = $pdo->prepare("SELECT id FROM news WHERE slug = ? AND id <> ?");
    while (true) {
        $check->execute([$slug, $id]);
        if (!$check->fetch()) break;
        $slug = $base . '-' . $i++;
    }

    $imagePath = $post['existing_image'] ?? null;
    if (!empty($_FILES['image']['name'])) {
        try {
            $imagePath = store_upload(
                $_FILES['image'],
                'news/' . date('Y', strtotime($date)),
                unserialize(UPLOAD_ALLOWED_IMAGE_MIME),
                $bildBase
            );
        } catch (Throwable $e) {
            flash('error', 'Bild-Upload: ' . $e->getMessage());
            header('Location: /admin/news.php?action=edit' . ($id ? '&id=' . $id : ''));
            exit;
        }
    }

    if ($id) {
        $pdo->prepare("UPDATE news SET slug=?, title=?, published_at=?, expires_at=?, image_path=?, body=?, is_published=? WHERE id=?")
            ->execute([$slug, $title, $date, $expires ?: null, $imagePath, $body, $pub, $id]);
        flash('success', 'Beitrag aktualisiert.');
    } else {
        $pdo->prepare("INSERT INTO news (slug, title, published_at, expires_at, image_path, body, is_published) VALUES (?,?,?,?,?,?,?)")
            ->execute([$slug, $title, $date, $expires ?: null, $imagePath, $body, $pub]);
        flash('success', 'Beitrag angelegt.');
    }
    redirect_after_save('/admin/news.php');
}

if ($action === 'edit' || $action === 'new') {
    $row = ['id' => 0, 'title' => '', 'published_at' => date('Y-m-d'), 'expires_at' => '', 'image_path' => '', 'body' => '', 'is_published' => 1];
    if ($id) {
        $st = $pdo->prepare("SELECT * FROM news WHERE id = ?"); $st->execute([$id]);
        $row = $st->fetch() ?: $row;
    }
    ?>
    <h1 class="text-2xl font-bold mb-4"><?= $row['id'] ? 'Beitrag bearbeiten' : 'Neuer Beitrag' ?></h1>
    <form method="post" enctype="multipart/form-data" class="bg-white border border-stone-200 rounded p-5 space-y-4 max-w-2xl">
      <?= csrf_field() ?>
      <input type="hidden" name="id" value="<?= (int)$row['id'] ?>">
      <input type="hidden" name="existing_image" value="<?= h($row['image_path'] ?? '') ?>">
      <label class="block">
        <span class="text-sm font-semibold">Titel *</span>
        <input type="text" name="title" required maxlength="255" value="<?= h($row['title']) ?>"
               class="w-full border border-stone-300 rounded px-3 py-2 mt-1">
      </label>
      <div class="grid sm:grid-cols-2 gap-3">
        <label class="block">
          <span class="text-sm font-semibold">Datum *</span>
          <input type="date" name="published_at" required
                 value="<?= h(date('Y-m-d', strtotime($row['published_at'] ?: 'today'))) ?>"
                 class="w-full border border-stone-300 rounded px-3 py-2 mt-1">
          <p class="text-xs text-stone-500 mt-1">Datum, das beim Beitrag angezeigt wird.</p>
        </label>
        <label class="block">
          <span class="text-sm font-semibold">Anzeige bis (optional)</span>
          <input type="date" name="expires_at"
                 value="<?= h($row['expires_at'] ? date('Y-m-d', strtotime($row['expires_at'])) : '') ?>"
                 class="w-full border border-stone-300 rounded px-3 py-2 mt-1">
          <p class="text-xs text-stone-500 mt-1">
            Ab diesem Datum wird der Beitrag automatisch <strong>nicht mehr</strong> auf der Webseite angezeigt. Leer lassen = dauerhaft sichtbar.
          </p>
        </label>
      </div>
      <div>
        <span class="text-sm font-semibold">Titelbild</span>
        <?php if ($row['image_path']): ?>
          <div class="mt-1">
            <img src="/bilder/<?= h($row['image_path']) ?>" alt="" class="max-h-32 rounded">
            <p class="text-xs text-stone-500 mt-1">vorhanden — zum Ersetzen neue Datei wählen</p>
          </div>
        <?php endif; ?>
        <input type="file" name="image" accept="image/jpeg,image/png,image/webp" class="block mt-1">
        <?php
          $widgetTargetHidden = 'existing_image';
          $widgetCategory     = 'news';
          $widgetDefaultQuery = trim($row['title']) ?: 'imker biene';
          include __DIR__ . '/_pixabay-widget.php';
        ?>
      </div>
      <div>
        <label for="news-body" class="text-sm font-semibold">Inhalt</label>
        <input id="news-body-input" type="hidden" name="body" value="<?= h($row['body']) ?>">
        <trix-editor input="news-body-input" id="news-body" class="mt-1"></trix-editor>
        <p class="text-xs text-stone-500 mt-1">Mit der Symbolleiste oben fett/kursiv setzen, Überschriften und Listen anlegen, Links einfügen.</p>
      </div>
      <label class="flex items-center gap-2">
        <input type="checkbox" name="is_published" value="1" <?= !empty($row['is_published']) ? 'checked' : '' ?>>
        <span class="text-sm">sichtbar</span>
      </label>
      <div class="flex gap-2">
        <button type="submit" class="bg-amber-700 hover:bg-amber-800 text-white font-semibold px-4 py-2 rounded">Speichern</button>
        <a href="/admin/news.php" class="px-4 py-2 rounded border border-stone-300 hover:bg-stone-50">Abbrechen</a>
      </div>
    </form>
    <?php
} else {
    $rows = $pdo->query("SELECT id, slug, title, published_at, expires_at, is_published FROM news ORDER BY published_at DESC, id DESC")->fetchAll();
    ?>
    <div class="flex justify-between items-baseline mb-4">
      <h1 class="text-2xl font-bold">Aktuelles</h1>
      <a href="/admin/news.php?action=new" class="bg-amber-700 hover:bg-amber-800 text-white px-3 py-2 rounded text-sm">+ Neu</a>
    </div>
    <div class="bg-white border border-stone-200 rounded overflow-hidden">
      <table class="w-full text-sm">
        <thead class="bg-stone-100 text-left">
          <tr><th class="px-3 py-2">Datum</th><th>Titel</th><th>Slug</th><th>Status</th><th></th></tr>
        </thead>
        <tbody>
          <?php foreach ($rows as $r): ?>
            <tr class="border-t">
              <td class="px-3 py-2 whitespace-nowrap"><?= h(format_date_de($r['published_at'])) ?></td>
              <td><?= h($r['title']) ?></td>
              <td class="text-stone-500 text-xs"><?= h($r['slug']) ?></td>
              <td>
                <?php
                  $expired = $r['expires_at'] && strtotime($r['expires_at']) < strtotime('today');
                  if (!$r['is_published']) {
                      echo '<span class="text-stone-400">aus</span>';
                  } elseif ($expired) {
                      echo '<span class="text-red-700" title="Anzeige bis ' . h(format_date_de($r['expires_at'])) . '">abgelaufen</span>';
                  } elseif ($r['expires_at']) {
                      echo '<span class="text-emerald-700">sichtbar</span> <span class="text-stone-400 text-xs">bis ' . h(format_date_de($r['expires_at'])) . '</span>';
                  } else {
                      echo '<span class="text-emerald-700">sichtbar</span>';
                  }
                ?>
              </td>
              <td class="text-right pr-2 whitespace-nowrap">
                <a href="/aktuelles-detail.php?slug=<?= h($r['slug']) ?>" target="_blank" class="text-stone-500 hover:underline">↗</a>
                <a href="/admin/news.php?action=edit&id=<?= (int)$r['id'] ?>" class="text-amber-700 hover:underline ml-3">bearbeiten</a>
                <form method="post" class="inline" onsubmit="return confirm('Beitrag wirklich löschen?');">
                  <?= csrf_field() ?>
                  <input type="hidden" name="_action" value="delete">
                  <input type="hidden" name="id" value="<?= (int)$r['id'] ?>">
                  <button type="submit" class="text-red-700 hover:underline ml-3">löschen</button>
                </form>
              </td>
            </tr>
          <?php endforeach; ?>
          <?php if (!$rows): ?>
            <tr><td colspan="5" class="px-3 py-6 text-center text-stone-400">Noch keine Beiträge.</td></tr>
          <?php endif; ?>
        </tbody>
      </table>
    </div>
    <?php
}

include __DIR__ . '/_layout_end.php';
