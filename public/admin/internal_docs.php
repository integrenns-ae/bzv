<?php
$pageTitle = 'Mitglieder-Doks';
$current   = 'internal_docs.php';
include __DIR__ . '/_layout.php';

$pdo    = Database::pdo();
$action = $_GET['action'] ?? 'list';
$id     = isset($_GET['id']) ? (int)$_GET['id'] : 0;

$docDir = __DIR__ . '/../mitglieder/doks';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    $post = $_POST;
    if (($post['_action'] ?? '') === 'delete') {
        $st = $pdo->prepare("SELECT file_path FROM internal_docs WHERE id = ?");
        $st->execute([(int)$post['id']]);
        if ($f = $st->fetchColumn()) {
            @unlink($docDir . '/' . $f);
        }
        $pdo->prepare("DELETE FROM internal_docs WHERE id = ?")->execute([(int)$post['id']]);
        flash('success', 'Dokument gelöscht.');
        header('Location: /admin/internal_docs.php');
        exit;
    }
    $id          = (int)($post['id'] ?? 0);
    $title       = trim((string)($post['title'] ?? ''));
    $description = trim((string)($post['description'] ?? ''));
    if ($title === '') {
        flash('error', 'Titel ist Pflicht.');
        header('Location: /admin/internal_docs.php?action=' . ($id ? 'edit&id=' . $id : 'new'));
        exit;
    }
    $filePath = null;
    $original = null;
    if (!empty($_FILES['file']['name'])) {
        try {
            $filePath = store_upload(
                $_FILES['file'], '',
                unserialize(UPLOAD_ALLOWED_DOC_MIME),
                $docDir
            );
            $original = $_FILES['file']['name'];
        } catch (Throwable $e) {
            flash('error', 'Upload: ' . $e->getMessage());
            header('Location: /admin/internal_docs.php?action=' . ($id ? 'edit&id=' . $id : 'new'));
            exit;
        }
    }
    if ($id) {
        if ($filePath) {
            // alte Datei löschen
            $st = $pdo->prepare("SELECT file_path FROM internal_docs WHERE id = ?");
            $st->execute([$id]);
            if ($old = $st->fetchColumn()) @unlink($docDir . '/' . $old);
            $pdo->prepare("UPDATE internal_docs SET title=?, description=?, file_path=?, original_name=? WHERE id=?")
                ->execute([$title, $description ?: null, $filePath, $original, $id]);
        } else {
            $pdo->prepare("UPDATE internal_docs SET title=?, description=? WHERE id=?")
                ->execute([$title, $description ?: null, $id]);
        }
        flash('success', 'Aktualisiert.');
    } else {
        if (!$filePath) {
            flash('error', 'Datei ist Pflicht beim Anlegen.');
            header('Location: /admin/internal_docs.php?action=new');
            exit;
        }
        $pdo->prepare("INSERT INTO internal_docs (title, description, file_path, original_name) VALUES (?,?,?,?)")
            ->execute([$title, $description ?: null, $filePath, $original]);
        flash('success', 'Angelegt.');
    }
    header('Location: /admin/internal_docs.php');
    exit;
}

if ($action === 'edit' || $action === 'new') {
    $row = ['id' => 0, 'title' => '', 'description' => '', 'file_path' => '', 'original_name' => ''];
    if ($id) {
        $st = $pdo->prepare("SELECT * FROM internal_docs WHERE id = ?"); $st->execute([$id]);
        $row = $st->fetch() ?: $row;
    }
    ?>
    <h1 class="text-2xl font-bold mb-4"><?= $row['id'] ? 'Dokument bearbeiten' : 'Neues Dokument' ?></h1>
    <form method="post" enctype="multipart/form-data" class="bg-white border border-stone-200 rounded p-5 space-y-4 max-w-xl">
      <?= csrf_field() ?>
      <input type="hidden" name="id" value="<?= (int)$row['id'] ?>">
      <label class="block">
        <span class="text-sm font-semibold">Titel *</span>
        <input type="text" name="title" required maxlength="255" value="<?= h($row['title']) ?>"
               class="w-full border border-stone-300 rounded px-3 py-2 mt-1">
      </label>
      <label class="block">
        <span class="text-sm font-semibold">Beschreibung</span>
        <textarea name="description" rows="3" class="w-full border border-stone-300 rounded px-3 py-2 mt-1"><?= h($row['description'] ?? '') ?></textarea>
      </label>
      <div>
        <span class="text-sm font-semibold">Datei (PDF) <?= $row['id'] ? '— optional, ersetzt vorhandene' : '*' ?></span>
        <?php if ($row['file_path']): ?>
          <div class="text-xs text-stone-500 mt-1">aktuell: <?= h($row['original_name'] ?: $row['file_path']) ?></div>
        <?php endif; ?>
        <input type="file" name="file" accept="application/pdf" <?= $row['id'] ? '' : 'required' ?> class="block mt-1">
      </div>
      <div class="flex gap-2">
        <button type="submit" class="bg-amber-700 hover:bg-amber-800 text-white font-semibold px-4 py-2 rounded">Speichern</button>
        <a href="/admin/internal_docs.php" class="px-4 py-2 rounded border border-stone-300 hover:bg-stone-50">Abbrechen</a>
      </div>
    </form>
    <?php
} else {
    $rows = $pdo->query("SELECT * FROM internal_docs ORDER BY uploaded_at DESC")->fetchAll();
    ?>
    <div class="flex justify-between items-baseline mb-4">
      <h1 class="text-2xl font-bold">Mitglieder-Dokumente</h1>
      <a href="/admin/internal_docs.php?action=new" class="bg-amber-700 hover:bg-amber-800 text-white px-3 py-2 rounded text-sm">+ Neu</a>
    </div>
    <p class="text-sm text-stone-600 mb-3">Diese Dokumente sind nur für eingeloggte Mitglieder zugänglich (Bereich <a href="/mitglieder/" class="underline">/mitglieder/</a>).</p>
    <div class="bg-white border border-stone-200 rounded overflow-hidden">
      <table class="w-full text-sm">
        <thead class="bg-stone-100 text-left">
          <tr><th class="px-3 py-2">Hochgeladen</th><th>Titel</th><th>Datei</th><th></th></tr>
        </thead>
        <tbody>
          <?php foreach ($rows as $r): ?>
            <tr class="border-t">
              <td class="px-3 py-2 whitespace-nowrap"><?= h(format_date_de($r['uploaded_at'])) ?></td>
              <td><?= h($r['title']) ?></td>
              <td class="text-xs text-stone-500"><?= h($r['original_name'] ?: $r['file_path']) ?></td>
              <td class="text-right pr-2 whitespace-nowrap">
                <a href="/admin/internal_docs.php?action=edit&id=<?= (int)$r['id'] ?>" class="text-amber-700 hover:underline">bearbeiten</a>
                <form method="post" class="inline" onsubmit="return confirm('Dokument wirklich löschen?');">
                  <?= csrf_field() ?>
                  <input type="hidden" name="_action" value="delete">
                  <input type="hidden" name="id" value="<?= (int)$r['id'] ?>">
                  <button type="submit" class="text-red-700 hover:underline ml-3">löschen</button>
                </form>
              </td>
            </tr>
          <?php endforeach; ?>
          <?php if (!$rows): ?>
            <tr><td colspan="4" class="px-3 py-6 text-center text-stone-400">Noch keine Dokumente.</td></tr>
          <?php endif; ?>
        </tbody>
      </table>
    </div>
    <?php
}

include __DIR__ . '/_layout_end.php';
