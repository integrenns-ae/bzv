<?php
$pageTitle = 'Infos für Imker';
$current   = 'infos.php';
include __DIR__ . '/_layout.php';

$pdo    = Database::pdo();
$action = $_GET['action'] ?? 'list';
$id     = isset($_GET['id']) ? (int)$_GET['id'] : 0;

$SECTIONS = [
    'mitgliedschaft' => 'Mitgliedschaft & Beitritt',
    'recht'          => 'Recht & Verordnungen',
    'varroa'         => 'Bienengesundheit & Varroa',
    'formulare'      => 'Formulare & Vorlagen',
    'videos'         => 'Videos',
    'links'          => 'Weiterführende Links',
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    $post = $_POST;
    if (($post['_action'] ?? '') === 'delete') {
        $pdo->prepare("DELETE FROM infos WHERE id = ?")->execute([(int)$post['id']]);
        flash('success', 'Eintrag gelöscht.');
        redirect_after_save('/admin/infos.php');
    }
    $id      = (int)($post['id'] ?? 0);
    $section = (string)($post['section'] ?? '');
    $title   = trim((string)($post['title']   ?? ''));
    $body    = trim((string)($post['body']    ?? ''));
    $link    = trim((string)($post['link_url'] ?? ''));
    $sort    = (int)($post['sort_order'] ?? 0);
    $pub     = !empty($post['is_published']) ? 1 : 0;
    $download = $post['existing_download'] ?? null;

    if (!isset($SECTIONS[$section]) || $title === '') {
        flash('error', 'Sektion und Titel sind Pflicht.');
        header('Location: /admin/infos.php?action=edit' . ($id ? '&id=' . $id : ''));
        exit;
    }
    if (!empty($_FILES['download']['name'])) {
        try {
            $download = store_upload(
                $_FILES['download'], $section,
                unserialize(UPLOAD_ALLOWED_DOC_MIME),
                __DIR__ . '/../downloads'
            );
        } catch (Throwable $e) {
            flash('error', 'Upload: ' . $e->getMessage());
            header('Location: /admin/infos.php?action=edit' . ($id ? '&id=' . $id : ''));
            exit;
        }
    }
    if ($id) {
        $pdo->prepare("UPDATE infos SET section=?, title=?, body=?, link_url=?, download_path=?, sort_order=?, is_published=? WHERE id=?")
            ->execute([$section, $title, $body, $link ?: null, $download, $sort, $pub, $id]);
        flash('success', 'Aktualisiert.');
    } else {
        $pdo->prepare("INSERT INTO infos (section, title, body, link_url, download_path, sort_order, is_published) VALUES (?,?,?,?,?,?,?)")
            ->execute([$section, $title, $body, $link ?: null, $download, $sort, $pub]);
        flash('success', 'Angelegt.');
    }
    redirect_after_save('/admin/infos.php');
}

if ($action === 'edit' || $action === 'new') {
    $row = ['id' => 0, 'section' => array_key_first($SECTIONS), 'title' => '', 'body' => '', 'link_url' => '', 'download_path' => '', 'sort_order' => 0, 'is_published' => 1];
    if ($id) {
        $st = $pdo->prepare("SELECT * FROM infos WHERE id = ?"); $st->execute([$id]);
        $row = $st->fetch() ?: $row;
    }
    ?>
    <h1 class="text-2xl font-bold mb-4"><?= $row['id'] ? 'Info bearbeiten' : 'Neue Info' ?></h1>
    <form method="post" enctype="multipart/form-data" class="bg-white border border-stone-200 rounded p-5 space-y-4 max-w-2xl">
      <?= csrf_field() ?>
      <input type="hidden" name="id" value="<?= (int)$row['id'] ?>">
      <input type="hidden" name="existing_download" value="<?= h($row['download_path'] ?? '') ?>">
      <div class="grid sm:grid-cols-2 gap-3">
        <label class="block">
          <span class="text-sm font-semibold">Sektion *</span>
          <select name="section" class="w-full border border-stone-300 rounded px-3 py-2 mt-1">
            <?php foreach ($SECTIONS as $k => $label): ?>
              <option value="<?= h($k) ?>" <?= $row['section'] === $k ? 'selected' : '' ?>><?= h($label) ?></option>
            <?php endforeach; ?>
          </select>
        </label>
        <label class="block">
          <span class="text-sm font-semibold">Reihenfolge</span>
          <input type="number" name="sort_order" value="<?= (int)$row['sort_order'] ?>"
                 class="w-32 border border-stone-300 rounded px-3 py-2 mt-1">
        </label>
      </div>
      <label class="block">
        <span class="text-sm font-semibold">Titel *</span>
        <input type="text" name="title" required maxlength="255" value="<?= h($row['title']) ?>"
               class="w-full border border-stone-300 rounded px-3 py-2 mt-1">
      </label>
      <div>
        <label for="info-body" class="text-sm font-semibold">Beschreibung</label>
        <input id="info-body-input" type="hidden" name="body" value="<?= h($row['body'] ?? '') ?>">
        <trix-editor input="info-body-input" id="info-body" class="mt-1"></trix-editor>
      </div>
      <label class="block">
        <span class="text-sm font-semibold">Externer Link (optional)</span>
        <input type="url" name="link_url" maxlength="500" value="<?= h($row['link_url'] ?? '') ?>"
               class="w-full border border-stone-300 rounded px-3 py-2 mt-1">
      </label>
      <div>
        <span class="text-sm font-semibold">Download (PDF, optional)</span>
        <?php if ($row['download_path']): ?>
          <div class="text-sm mt-1">aktuell: <a class="text-amber-700 underline" href="/downloads/<?= h($row['download_path']) ?>" target="_blank"><?= h($row['download_path']) ?></a></div>
        <?php endif; ?>
        <input type="file" name="download" accept="application/pdf" class="block mt-1">
      </div>
      <label class="flex items-center gap-2">
        <input type="checkbox" name="is_published" value="1" <?= !empty($row['is_published']) ? 'checked' : '' ?>>
        <span class="text-sm">sichtbar</span>
      </label>
      <div class="flex gap-2">
        <button type="submit" class="bg-amber-700 hover:bg-amber-800 text-white font-semibold px-4 py-2 rounded">Speichern</button>
        <a href="/admin/infos.php" class="px-4 py-2 rounded border border-stone-300 hover:bg-stone-50">Abbrechen</a>
      </div>
    </form>
    <?php
} else {
    $rows = $pdo->query("SELECT * FROM infos ORDER BY section, sort_order, id")->fetchAll();
    ?>
    <div class="flex justify-between items-baseline mb-4">
      <h1 class="text-2xl font-bold">Infos für Imker</h1>
      <a href="/admin/infos.php?action=new" class="bg-amber-700 hover:bg-amber-800 text-white px-3 py-2 rounded text-sm">+ Neu</a>
    </div>
    <?php foreach ($SECTIONS as $sk => $sLabel):
      $inSec = array_filter($rows, fn($r) => $r['section'] === $sk);
    ?>
      <h2 class="font-semibold text-amber-900 mt-6 mb-2"><?= h($sLabel) ?></h2>
      <?php if (!$inSec): ?>
        <p class="text-stone-400 text-sm">—</p>
      <?php else: ?>
        <div class="bg-white border border-stone-200 rounded overflow-hidden">
          <table class="w-full text-sm">
            <thead class="bg-stone-100 text-left">
              <tr><th class="px-3 py-2 w-12">#</th><th>Titel</th><th>Link/Download</th><th>Status</th><th></th></tr>
            </thead>
            <tbody>
            <?php foreach ($inSec as $r): ?>
              <tr class="border-t">
                <td class="px-3 py-2"><?= (int)$r['sort_order'] ?></td>
                <td><?= h($r['title']) ?></td>
                <td class="text-xs text-stone-500">
                  <?php if ($r['link_url']):     ?>🔗 <?= h(mb_strimwidth($r['link_url'], 0, 50, '…')) ?><br><?php endif; ?>
                  <?php if ($r['download_path']):?>📄 <?= h($r['download_path']) ?><?php endif; ?>
                </td>
                <td><?= $r['is_published'] ? '<span class="text-emerald-700">an</span>' : '<span class="text-stone-400">aus</span>' ?></td>
                <td class="text-right pr-2 whitespace-nowrap">
                  <a href="/admin/infos.php?action=edit&id=<?= (int)$r['id'] ?>" class="text-amber-700 hover:underline">bearbeiten</a>
                  <form method="post" class="inline" onsubmit="return confirm('Löschen?');">
                    <?= csrf_field() ?>
                    <input type="hidden" name="_action" value="delete">
                    <input type="hidden" name="id" value="<?= (int)$r['id'] ?>">
                    <button type="submit" class="text-red-700 hover:underline ml-3">löschen</button>
                  </form>
                </td>
              </tr>
            <?php endforeach; ?>
            </tbody>
          </table>
        </div>
      <?php endif; ?>
    <?php endforeach; ?>
    <?php
}

include __DIR__ . '/_layout_end.php';
