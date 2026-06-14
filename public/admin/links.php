<?php
$pageTitle = 'Links';
$current   = 'links.php';
include __DIR__ . '/_layout.php';

$pdo    = Database::pdo();
$action = $_GET['action'] ?? 'list';
$id     = isset($_GET['id']) ? (int)$_GET['id'] : 0;

$SECTIONS = [
    'mitgliedschaft' => 'Mitgliedschaft',
    'recht'          => 'Recht',
    'varroa'         => 'Bienengesundheit',
    'formulare'      => 'Formulare',
    'videos'         => 'Videos',
    'links'          => 'Allgemein',
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    $post = $_POST;
    if (($post['_action'] ?? '') === 'delete') {
        $pdo->prepare("DELETE FROM links WHERE id = ?")->execute([(int)$post['id']]);
        flash('success', 'Link gelöscht.');
        redirect_after_save('/admin/links.php');
    }
    $id      = (int)($post['id'] ?? 0);
    $section = (string)($post['section'] ?? '');
    $title   = trim((string)($post['title'] ?? ''));
    $url     = trim((string)($post['url']   ?? ''));
    $sort    = (int)($post['sort_order'] ?? 0);
    if (!isset($SECTIONS[$section]) || $title === '' || !filter_var($url, FILTER_VALIDATE_URL)) {
        flash('error', 'Sektion, Titel und gültige URL sind Pflicht.');
        header('Location: /admin/links.php?action=edit' . ($id ? '&id=' . $id : ''));
        exit;
    }
    if ($id) {
        $pdo->prepare("UPDATE links SET section=?, title=?, url=?, sort_order=? WHERE id=?")
            ->execute([$section, $title, $url, $sort, $id]);
        flash('success', 'Aktualisiert.');
    } else {
        $pdo->prepare("INSERT INTO links (section, title, url, sort_order) VALUES (?,?,?,?)")
            ->execute([$section, $title, $url, $sort]);
        flash('success', 'Angelegt.');
    }
    redirect_after_save('/admin/links.php');
}

if ($action === 'edit' || $action === 'new') {
    $row = ['id' => 0, 'section' => array_key_first($SECTIONS), 'title' => '', 'url' => '', 'sort_order' => 0];
    if ($id) {
        $st = $pdo->prepare("SELECT * FROM links WHERE id = ?"); $st->execute([$id]);
        $row = $st->fetch() ?: $row;
    }
    ?>
    <h1 class="text-2xl font-bold mb-4"><?= $row['id'] ? 'Link bearbeiten' : 'Neuer Link' ?></h1>
    <form method="post" class="bg-white border border-stone-200 rounded p-5 space-y-4 max-w-xl">
      <?= csrf_field() ?>
      <input type="hidden" name="id" value="<?= (int)$row['id'] ?>">
      <label class="block">
        <span class="text-sm font-semibold">Sektion *</span>
        <select name="section" class="w-full border border-stone-300 rounded px-3 py-2 mt-1">
          <?php foreach ($SECTIONS as $k => $label): ?>
            <option value="<?= h($k) ?>" <?= $row['section'] === $k ? 'selected' : '' ?>><?= h($label) ?></option>
          <?php endforeach; ?>
        </select>
      </label>
      <label class="block">
        <span class="text-sm font-semibold">Titel *</span>
        <input type="text" name="title" required maxlength="255" value="<?= h($row['title']) ?>"
               class="w-full border border-stone-300 rounded px-3 py-2 mt-1">
      </label>
      <label class="block">
        <span class="text-sm font-semibold">URL *</span>
        <input type="url" name="url" required maxlength="500" value="<?= h($row['url']) ?>"
               class="w-full border border-stone-300 rounded px-3 py-2 mt-1">
      </label>
      <label class="block">
        <span class="text-sm font-semibold">Reihenfolge</span>
        <input type="number" name="sort_order" value="<?= (int)$row['sort_order'] ?>"
               class="w-32 border border-stone-300 rounded px-3 py-2 mt-1">
      </label>
      <div class="flex gap-2">
        <button type="submit" class="bg-amber-700 hover:bg-amber-800 text-white font-semibold px-4 py-2 rounded">Speichern</button>
        <a href="/admin/links.php" class="px-4 py-2 rounded border border-stone-300 hover:bg-stone-50">Abbrechen</a>
      </div>
    </form>
    <?php
} else {
    $rows = $pdo->query("SELECT * FROM links ORDER BY section, sort_order, id")->fetchAll();
    ?>
    <div class="flex justify-between items-baseline mb-4">
      <h1 class="text-2xl font-bold">Externe Links</h1>
      <a href="/admin/links.php?action=new" class="bg-amber-700 hover:bg-amber-800 text-white px-3 py-2 rounded text-sm">+ Neu</a>
    </div>
    <div class="bg-white border border-stone-200 rounded overflow-hidden">
      <table class="w-full text-sm">
        <thead class="bg-stone-100 text-left">
          <tr><th class="px-3 py-2">Sektion</th><th>Titel</th><th>URL</th><th></th></tr>
        </thead>
        <tbody>
          <?php foreach ($rows as $r): ?>
            <tr class="border-t">
              <td class="px-3 py-2"><?= h($SECTIONS[$r['section']] ?? $r['section']) ?></td>
              <td><?= h($r['title']) ?></td>
              <td class="text-xs"><a href="<?= h($r['url']) ?>" target="_blank" class="text-amber-700 hover:underline"><?= h(mb_strimwidth($r['url'], 0, 60, '…')) ?></a></td>
              <td class="text-right pr-2 whitespace-nowrap">
                <a href="/admin/links.php?action=edit&id=<?= (int)$r['id'] ?>" class="text-amber-700 hover:underline">bearbeiten</a>
                <form method="post" class="inline" onsubmit="return confirm('Löschen?');">
                  <?= csrf_field() ?>
                  <input type="hidden" name="_action" value="delete">
                  <input type="hidden" name="id" value="<?= (int)$r['id'] ?>">
                  <button type="submit" class="text-red-700 hover:underline ml-3">löschen</button>
                </form>
              </td>
            </tr>
          <?php endforeach; ?>
          <?php if (!$rows): ?>
            <tr><td colspan="4" class="px-3 py-6 text-center text-stone-400">Noch keine Links.</td></tr>
          <?php endif; ?>
        </tbody>
      </table>
    </div>
    <?php
}

include __DIR__ . '/_layout_end.php';
