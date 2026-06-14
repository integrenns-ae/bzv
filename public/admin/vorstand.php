<?php
$pageTitle = 'Vorstand';
$current   = 'vorstand.php';
include __DIR__ . '/_layout.php';

$pdo    = Database::pdo();
$action = $_GET['action'] ?? 'list';
$id     = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$bildBase = __DIR__ . '/../bilder';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    $post = $_POST;
    if (($post['_action'] ?? '') === 'delete') {
        $pdo->prepare("DELETE FROM vorstand WHERE id = ?")->execute([(int)$post['id']]);
        flash('success', 'Eintrag gelöscht.');
        redirect_after_save('/admin/vorstand.php');
    }
    $id    = (int)($post['id'] ?? 0);
    $name  = trim((string)($post['name']  ?? ''));
    $role  = trim((string)($post['role']  ?? ''));
    $email = trim((string)($post['email'] ?? ''));
    $phone = trim((string)($post['phone'] ?? ''));
    $sort  = (int)($post['sort_order'] ?? 0);
    $pub   = !empty($post['is_published']) ? 1 : 0;

    if ($name === '' || $role === '') {
        flash('error', 'Name und Rolle sind Pflicht.');
        header('Location: /admin/vorstand.php?action=edit' . ($id ? '&id=' . $id : ''));
        exit;
    }
    $photoPath = $post['existing_photo'] ?? null;
    if (!empty($_FILES['photo']['name'])) {
        try {
            $photoPath = store_upload(
                $_FILES['photo'], 'vorstand',
                unserialize(UPLOAD_ALLOWED_IMAGE_MIME),
                $bildBase
            );
        } catch (Throwable $e) {
            flash('error', 'Foto-Upload: ' . $e->getMessage());
            header('Location: /admin/vorstand.php?action=edit' . ($id ? '&id=' . $id : ''));
            exit;
        }
    }
    if ($id) {
        $pdo->prepare("UPDATE vorstand SET name=?, role=?, photo_path=?, email=?, phone=?, sort_order=?, is_published=? WHERE id=?")
            ->execute([$name, $role, $photoPath, $email ?: null, $phone ?: null, $sort, $pub, $id]);
        flash('success', 'Eintrag aktualisiert.');
    } else {
        $pdo->prepare("INSERT INTO vorstand (name, role, photo_path, email, phone, sort_order, is_published) VALUES (?,?,?,?,?,?,?)")
            ->execute([$name, $role, $photoPath, $email ?: null, $phone ?: null, $sort, $pub]);
        flash('success', 'Eintrag angelegt.');
    }
    redirect_after_save('/admin/vorstand.php');
}

if ($action === 'edit' || $action === 'new') {
    $row = ['id' => 0, 'name' => '', 'role' => '', 'photo_path' => '', 'email' => '', 'phone' => '', 'sort_order' => 0, 'is_published' => 1];
    if ($id) {
        $st = $pdo->prepare("SELECT * FROM vorstand WHERE id = ?"); $st->execute([$id]);
        $row = $st->fetch() ?: $row;
    }
    ?>
    <h1 class="text-2xl font-bold mb-4"><?= $row['id'] ? 'Vorstandsmitglied bearbeiten' : 'Neues Vorstandsmitglied' ?></h1>
    <form method="post" enctype="multipart/form-data" class="bg-white border border-stone-200 rounded p-5 space-y-4 max-w-xl">
      <?= csrf_field() ?>
      <input type="hidden" name="id" value="<?= (int)$row['id'] ?>">
      <input type="hidden" name="existing_photo" value="<?= h($row['photo_path'] ?? '') ?>">

      <div class="grid sm:grid-cols-2 gap-3">
        <label class="block">
          <span class="text-sm font-semibold">Name *</span>
          <input type="text" name="name" required maxlength="128" value="<?= h($row['name']) ?>"
                 class="w-full border border-stone-300 rounded px-3 py-2 mt-1">
        </label>
        <label class="block">
          <span class="text-sm font-semibold">Rolle *</span>
          <input type="text" name="role" required maxlength="128" value="<?= h($row['role']) ?>"
                 class="w-full border border-stone-300 rounded px-3 py-2 mt-1">
        </label>
      </div>

      <div class="grid sm:grid-cols-2 gap-3">
        <label class="block">
          <span class="text-sm font-semibold">E-Mail</span>
          <input type="email" name="email" maxlength="128" value="<?= h($row['email'] ?? '') ?>"
                 class="w-full border border-stone-300 rounded px-3 py-2 mt-1">
        </label>
        <label class="block">
          <span class="text-sm font-semibold">Telefon</span>
          <input type="tel" name="phone" maxlength="64" value="<?= h($row['phone'] ?? '') ?>"
                 class="w-full border border-stone-300 rounded px-3 py-2 mt-1">
        </label>
      </div>

      <label class="block">
        <span class="text-sm font-semibold">Reihenfolge</span>
        <input type="number" name="sort_order" value="<?= (int)$row['sort_order'] ?>"
               class="border border-stone-300 rounded px-3 py-2 mt-1 w-32">
        <span class="text-xs text-stone-500">kleinere Zahl = weiter oben</span>
      </label>

      <div>
        <span class="text-sm font-semibold">Foto</span>
        <?php if ($row['photo_path']): ?>
          <div class="mt-1"><img src="/bilder/<?= h($row['photo_path']) ?>" class="h-24 rounded-full"></div>
        <?php endif; ?>
        <input type="file" name="photo" accept="image/jpeg,image/png,image/webp" class="block mt-1">
        <?php
          $widgetTargetHidden = 'existing_photo';
          $widgetCategory     = 'vorstand';
          $widgetDefaultQuery = 'imker portrait';
          include __DIR__ . '/_pixabay-widget.php';
        ?>
      </div>

      <label class="flex items-center gap-2">
        <input type="checkbox" name="is_published" value="1" <?= !empty($row['is_published']) ? 'checked' : '' ?>>
        <span class="text-sm">sichtbar</span>
      </label>

      <div class="flex gap-2">
        <button type="submit" class="bg-amber-700 hover:bg-amber-800 text-white font-semibold px-4 py-2 rounded">Speichern</button>
        <a href="/admin/vorstand.php" class="px-4 py-2 rounded border border-stone-300 hover:bg-stone-50">Abbrechen</a>
      </div>
    </form>
    <?php
} else {
    $rows = $pdo->query("SELECT * FROM vorstand ORDER BY sort_order ASC, id ASC")->fetchAll();
    ?>
    <div class="flex justify-between items-baseline mb-4">
      <h1 class="text-2xl font-bold">Vorstand</h1>
      <a href="/admin/vorstand.php?action=new" class="bg-amber-700 hover:bg-amber-800 text-white px-3 py-2 rounded text-sm">+ Neu</a>
    </div>
    <div class="bg-white border border-stone-200 rounded overflow-hidden">
      <table class="w-full text-sm">
        <thead class="bg-stone-100 text-left">
          <tr><th class="px-3 py-2 w-16">Foto</th><th>Name</th><th>Rolle</th><th>Status</th><th></th></tr>
        </thead>
        <tbody>
          <?php foreach ($rows as $r): ?>
            <tr class="border-t">
              <td class="px-3 py-2">
                <?php if ($r['photo_path']): ?>
                  <img src="/bilder/<?= h($r['photo_path']) ?>" class="w-10 h-10 rounded-full object-cover">
                <?php else: ?>
                  <div class="w-10 h-10 rounded-full bg-amber-100 flex items-center justify-center">
                    <img src="/assets/favicon.png" alt="" class="w-6 h-6 object-contain" width="256" height="256">
                  </div>
                <?php endif; ?>
              </td>
              <td><?= h($r['name']) ?></td>
              <td><?= h($r['role']) ?></td>
              <td><?= $r['is_published'] ? '<span class="text-emerald-700">sichtbar</span>' : '<span class="text-stone-400">aus</span>' ?></td>
              <td class="text-right pr-2 whitespace-nowrap">
                <a href="/admin/vorstand.php?action=edit&id=<?= (int)$r['id'] ?>" class="text-amber-700 hover:underline">bearbeiten</a>
                <form method="post" class="inline" onsubmit="return confirm('Eintrag wirklich löschen?');">
                  <?= csrf_field() ?>
                  <input type="hidden" name="_action" value="delete">
                  <input type="hidden" name="id" value="<?= (int)$r['id'] ?>">
                  <button type="submit" class="text-red-700 hover:underline ml-3">löschen</button>
                </form>
              </td>
            </tr>
          <?php endforeach; ?>
          <?php if (!$rows): ?>
            <tr><td colspan="5" class="px-3 py-6 text-center text-stone-400">Noch keine Einträge.</td></tr>
          <?php endif; ?>
        </tbody>
      </table>
    </div>
    <?php
}

include __DIR__ . '/_layout_end.php';
