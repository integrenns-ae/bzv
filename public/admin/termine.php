<?php
$pageTitle = 'Termine';
$current   = 'termine.php';
include __DIR__ . '/_layout.php';

$pdo    = Database::pdo();
$action = $_GET['action'] ?? 'list';
$id     = isset($_GET['id']) ? (int)$_GET['id'] : 0;

// --- POST: speichern / löschen ---
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    $post = $_POST;
    if (($post['_action'] ?? '') === 'delete') {
        $pdo->prepare("DELETE FROM termine WHERE id = ?")->execute([(int)$post['id']]);
        flash('success', 'Termin gelöscht.');
        redirect_after_save('/admin/termine.php');
    }
    $starts = trim((string)($post['starts_at'] ?? ''));
    $ends   = trim((string)($post['ends_at']   ?? ''));
    $title  = trim((string)($post['title']     ?? ''));
    $loc    = trim((string)($post['location']  ?? ''));
    $desc   = trim((string)($post['description'] ?? ''));
    $pub    = !empty($post['is_published']) ? 1 : 0;

    if ($title === '' || $starts === '') {
        flash('error', 'Titel und Beginn sind Pflichtfelder.');
        header('Location: /admin/termine.php?action=edit' . ($id ? '&id=' . $id : ''));
        exit;
    }
    $id = (int)($post['id'] ?? 0);
    if ($id) {
        $pdo->prepare("UPDATE termine SET starts_at=?, ends_at=?, title=?, location=?, description=?, is_published=? WHERE id=?")
            ->execute([$starts, $ends ?: null, $title, $loc ?: null, $desc ?: null, $pub, $id]);
        flash('success', 'Termin aktualisiert.');
    } else {
        $pdo->prepare("INSERT INTO termine (starts_at, ends_at, title, location, description, is_published) VALUES (?,?,?,?,?,?)")
            ->execute([$starts, $ends ?: null, $title, $loc ?: null, $desc ?: null, $pub]);
        flash('success', 'Termin angelegt.');
    }
    redirect_after_save('/admin/termine.php');
}

// --- GET: List oder Edit ---
if ($action === 'edit' || $action === 'new') {
    $row = ['id' => 0, 'starts_at' => '', 'ends_at' => '', 'title' => '', 'location' => '', 'description' => '', 'is_published' => 1];
    if ($id) {
        $st = $pdo->prepare("SELECT * FROM termine WHERE id = ?"); $st->execute([$id]);
        $row = $st->fetch() ?: $row;
    }
    $fmt = static fn($v) => $v ? date('Y-m-d\TH:i', strtotime($v)) : '';
    ?>
    <h1 class="text-2xl font-bold mb-4"><?= $row['id'] ? 'Termin bearbeiten' : 'Neuer Termin' ?></h1>
    <form method="post" class="bg-white border border-stone-200 rounded p-5 space-y-4 max-w-xl">
      <?= csrf_field() ?>
      <input type="hidden" name="id" value="<?= (int)$row['id'] ?>">
      <div class="grid sm:grid-cols-2 gap-3">
        <label class="block">
          <span class="text-sm font-semibold">Beginn *</span>
          <input type="datetime-local" name="starts_at" required value="<?= h($fmt($row['starts_at'])) ?>"
                 class="w-full border border-stone-300 rounded px-3 py-2 mt-1">
        </label>
        <label class="block">
          <span class="text-sm font-semibold">Ende (optional)</span>
          <input type="datetime-local" name="ends_at" value="<?= h($fmt($row['ends_at'])) ?>"
                 class="w-full border border-stone-300 rounded px-3 py-2 mt-1">
        </label>
      </div>
      <label class="block">
        <span class="text-sm font-semibold">Titel *</span>
        <input type="text" name="title" required maxlength="255" value="<?= h($row['title']) ?>"
               class="w-full border border-stone-300 rounded px-3 py-2 mt-1">
      </label>
      <label class="block">
        <span class="text-sm font-semibold">Ort</span>
        <input type="text" name="location" maxlength="255" value="<?= h($row['location'] ?? '') ?>"
               class="w-full border border-stone-300 rounded px-3 py-2 mt-1">
      </label>
      <label class="block">
        <span class="text-sm font-semibold">Beschreibung</span>
        <textarea name="description" rows="4" class="w-full border border-stone-300 rounded px-3 py-2 mt-1"><?= h($row['description'] ?? '') ?></textarea>
      </label>
      <label class="flex items-center gap-2">
        <input type="checkbox" name="is_published" value="1" <?= !empty($row['is_published']) ? 'checked' : '' ?>>
        <span class="text-sm">sichtbar auf der Webseite</span>
      </label>
      <div class="flex gap-2">
        <button type="submit" class="bg-amber-700 hover:bg-amber-800 text-white font-semibold px-4 py-2 rounded">Speichern</button>
        <a href="/admin/termine.php" class="px-4 py-2 rounded border border-stone-300 hover:bg-stone-50">Abbrechen</a>
      </div>
    </form>
    <?php
} else {
    $rows = $pdo->query("SELECT * FROM termine ORDER BY starts_at DESC")->fetchAll();
    ?>
    <div class="flex justify-between items-baseline mb-4">
      <h1 class="text-2xl font-bold">Termine</h1>
      <a href="/admin/termine.php?action=new" class="bg-amber-700 hover:bg-amber-800 text-white px-3 py-2 rounded text-sm">+ Neu</a>
    </div>
    <div class="bg-white border border-stone-200 rounded overflow-hidden">
      <table class="w-full text-sm">
        <thead class="bg-stone-100 text-left">
          <tr><th class="px-3 py-2">Wann</th><th>Titel</th><th>Ort</th><th>Status</th><th></th></tr>
        </thead>
        <tbody>
          <?php foreach ($rows as $r): ?>
            <tr class="border-t">
              <td class="px-3 py-2 whitespace-nowrap"><?= h(format_datetime_de($r['starts_at'])) ?></td>
              <td><?= h($r['title']) ?></td>
              <td><?= h($r['location'] ?? '') ?></td>
              <td><?= $r['is_published'] ? '<span class="text-emerald-700">sichtbar</span>' : '<span class="text-stone-400">ausgeblendet</span>' ?></td>
              <td class="text-right pr-2 whitespace-nowrap">
                <a href="/admin/termine.php?action=edit&id=<?= (int)$r['id'] ?>" class="text-amber-700 hover:underline">bearbeiten</a>
                <form method="post" class="inline" onsubmit="return confirm('Termin wirklich löschen?');">
                  <?= csrf_field() ?>
                  <input type="hidden" name="_action" value="delete">
                  <input type="hidden" name="id" value="<?= (int)$r['id'] ?>">
                  <button type="submit" class="text-red-700 hover:underline ml-3">löschen</button>
                </form>
              </td>
            </tr>
          <?php endforeach; ?>
          <?php if (!$rows): ?>
            <tr><td colspan="5" class="px-3 py-6 text-center text-stone-400">Noch keine Termine.</td></tr>
          <?php endif; ?>
        </tbody>
      </table>
    </div>
    <?php
}

include __DIR__ . '/_layout_end.php';
