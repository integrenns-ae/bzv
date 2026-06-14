<?php
$pageTitle = 'Benutzer';
$current   = 'users.php';
include __DIR__ . '/_layout.php';

$pdo    = Database::pdo();
$action = $_GET['action'] ?? 'list';
$id     = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$me     = Auth::user();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    $post = $_POST;
    $act  = $post['_action'] ?? '';

    // --- Aktionen aus der Liste ---
    if ($act === 'delete') {
        $targetId = (int)$post['id'];
        if ($targetId === ($me['id'] ?? 0)) {
            flash('error', 'Du kannst dich nicht selbst löschen.');
        } else {
            $pdo->prepare("DELETE FROM users WHERE id = ?")->execute([$targetId]);
            flash('success', 'Benutzer gelöscht.');
        }
        header('Location: /admin/users.php');
        exit;
    }
    if ($act === 'approve') {
        $targetId = (int)$post['id'];
        $st = $pdo->prepare("SELECT id, username, display_name, email FROM users WHERE id = ? AND active = 0");
        $st->execute([$targetId]);
        if ($u = $st->fetch()) {
            $pdo->prepare("UPDATE users SET active = 1 WHERE id = ?")->execute([$targetId]);
            if (!empty($u['email'])) {
                $body = "Hallo " . ($u['display_name'] ?: $u['username']) . ",\n\n"
                      . "dein Mitglieder-Zugang beim Bienenzuchtverein Grünberg wurde vom Vorstand freigeschaltet.\n\n"
                      . "Du kannst dich jetzt einloggen:\n"
                      . SITE_URL . "/mitglieder/login.php\n\n"
                      . "Benutzername: " . $u['username'] . "\n"
                      . "(Passwort wie bei der Registrierung gewählt.)\n\n"
                      . "Viele Grüße\nDein Vorstand";
                @Mailer::send($u['email'], 'Mitglieder-Zugang freigeschaltet', $body);
            }
            flash('success', 'Zugang freigegeben' . (!empty($u['email']) ? ' + Bestätigungs-Mail verschickt' : '') . '.');
        }
        header('Location: /admin/users.php');
        exit;
    }

    // --- Formular Speichern ---
    $id        = (int)($post['id'] ?? 0);
    $username  = strtolower(trim((string)($post['username'] ?? '')));
    $display   = trim((string)($post['display_name'] ?? ''));
    $email     = trim((string)($post['email'] ?? ''));
    $note      = trim((string)($post['note'] ?? ''));
    $role      = ($post['role'] ?? 'member') === 'admin' ? 'admin' : 'member';
    $active    = !empty($post['active']) ? 1 : 0;
    $password  = (string)($post['password'] ?? '');

    if ($username === '' || !preg_match('/^[a-zA-Z0-9._-]{3,64}$/', $username)) {
        flash('error', 'Benutzername min. 3 Zeichen (a-z, 0-9, ., _, -).');
        header('Location: /admin/users.php?action=' . ($id ? 'edit&id=' . $id : 'new'));
        exit;
    }
    if (!$id && strlen($password) < 8) {
        flash('error', 'Passwort min. 8 Zeichen.');
        header('Location: /admin/users.php?action=new');
        exit;
    }
    if ($email !== '' && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        flash('error', 'E-Mail-Adresse ungültig.');
        header('Location: /admin/users.php?action=' . ($id ? 'edit&id=' . $id : 'new'));
        exit;
    }

    try {
        // Bei Edit: alten Stand laden für active-Übergang
        $wasInactive = false;
        if ($id) {
            $prev = $pdo->prepare("SELECT active, email FROM users WHERE id = ?");
            $prev->execute([$id]);
            if ($p = $prev->fetch()) {
                $wasInactive = ((int)$p['active'] === 0 && $active === 1);
            }
        }

        if ($id) {
            if ($password !== '') {
                if (strlen($password) < 8) throw new RuntimeException('Passwort zu kurz.');
                $pdo->prepare("UPDATE users SET username=?, display_name=?, email=?, note=?, role=?, active=?, password_hash=? WHERE id=?")
                    ->execute([$username, $display ?: null, $email ?: null, $note ?: null, $role, $active, password_hash($password, PASSWORD_BCRYPT), $id]);
            } else {
                $pdo->prepare("UPDATE users SET username=?, display_name=?, email=?, note=?, role=?, active=? WHERE id=?")
                    ->execute([$username, $display ?: null, $email ?: null, $note ?: null, $role, $active, $id]);
            }
        } else {
            $pdo->prepare("INSERT INTO users (username, password_hash, display_name, email, note, role, active) VALUES (?,?,?,?,?,?,?)")
                ->execute([$username, password_hash($password, PASSWORD_BCRYPT), $display ?: null, $email ?: null, $note ?: null, $role, $active]);
        }

        // Freigabe-Mail (auch wenn Admin via Bearbeiten-Form freigibt)
        if ($wasInactive && $email !== '') {
            $body = "Hallo " . ($display ?: $username) . ",\n\n"
                  . "dein Mitglieder-Zugang beim Bienenzuchtverein Grünberg wurde vom Vorstand freigeschaltet.\n\n"
                  . "Login: " . SITE_URL . "/mitglieder/login.php\nBenutzername: $username\n\n"
                  . "Viele Grüße\nDein Vorstand";
            @Mailer::send($email, 'Mitglieder-Zugang freigeschaltet', $body);
        }

        flash('success', 'Benutzer gespeichert.');
    } catch (Throwable $e) {
        flash('error', 'Fehler: ' . $e->getMessage());
    }
    header('Location: /admin/users.php');
    exit;
}

if ($action === 'edit' || $action === 'new') {
    $row = ['id' => 0, 'username' => '', 'display_name' => '', 'email' => '', 'note' => '', 'role' => 'member', 'active' => 1];
    if ($id) {
        $st = $pdo->prepare("SELECT id, username, display_name, email, note, role, active FROM users WHERE id = ?"); $st->execute([$id]);
        $row = $st->fetch() ?: $row;
    }
    ?>
    <h1 class="text-2xl font-bold mb-4"><?= $row['id'] ? 'Benutzer bearbeiten' : 'Neuer Benutzer' ?></h1>
    <form method="post" class="bg-white border border-stone-200 rounded p-5 space-y-4 max-w-md">
      <?= csrf_field() ?>
      <input type="hidden" name="id" value="<?= (int)$row['id'] ?>">
      <label class="block">
        <span class="text-sm font-semibold">Benutzername *</span>
        <input type="text" name="username" required pattern="[a-zA-Z0-9._-]{3,64}" value="<?= h($row['username']) ?>"
               class="w-full border border-stone-300 rounded px-3 py-2 mt-1 font-mono">
      </label>
      <label class="block">
        <span class="text-sm font-semibold">Anzeigename</span>
        <input type="text" name="display_name" maxlength="128" value="<?= h($row['display_name'] ?? '') ?>"
               class="w-full border border-stone-300 rounded px-3 py-2 mt-1">
      </label>
      <label class="block">
        <span class="text-sm font-semibold">E-Mail</span>
        <input type="email" name="email" maxlength="160" value="<?= h($row['email'] ?? '') ?>"
               class="w-full border border-stone-300 rounded px-3 py-2 mt-1">
        <span class="text-xs text-stone-500">Für Benachrichtigung bei Freigabe.</span>
      </label>
      <?php if (!empty($row['note'])): ?>
        <label class="block">
          <span class="text-sm font-semibold">Bemerkung aus Registrierung</span>
          <textarea name="note" rows="3" class="w-full border border-stone-300 rounded px-3 py-2 mt-1"><?= h($row['note']) ?></textarea>
        </label>
      <?php else: ?>
        <input type="hidden" name="note" value="">
      <?php endif; ?>
      <label class="block">
        <span class="text-sm font-semibold">Rolle</span>
        <select name="role" class="w-full border border-stone-300 rounded px-3 py-2 mt-1">
          <option value="admin"  <?= $row['role'] === 'admin'  ? 'selected' : '' ?>>Admin (Vorstand)</option>
          <option value="member" <?= $row['role'] === 'member' ? 'selected' : '' ?>>Mitglied</option>
        </select>
      </label>
      <label class="block">
        <span class="text-sm font-semibold">Passwort <?= $row['id'] ? '(leer = unverändert)' : '*' ?></span>
        <input type="password" name="password" minlength="8" <?= $row['id'] ? '' : 'required' ?>
               class="w-full border border-stone-300 rounded px-3 py-2 mt-1">
      </label>
      <label class="flex items-center gap-2">
        <input type="checkbox" name="active" value="1" <?= !empty($row['active']) ? 'checked' : '' ?>>
        <span class="text-sm">aktiv (kann sich einloggen / freigegeben)</span>
      </label>
      <div class="flex gap-2">
        <button type="submit" class="bg-amber-700 hover:bg-amber-800 text-white font-semibold px-4 py-2 rounded">Speichern</button>
        <a href="/admin/users.php" class="px-4 py-2 rounded border border-stone-300 hover:bg-stone-50">Abbrechen</a>
      </div>
    </form>
    <?php
} else {
    $rows = $pdo->query("SELECT id, username, display_name, email, note, role, active, last_login_at FROM users ORDER BY active ASC, role, username")->fetchAll();
    $pendingCount = 0;
    foreach ($rows as $r) if (!$r['active']) $pendingCount++;
    ?>
    <div class="flex justify-between items-baseline mb-4">
      <h1 class="text-2xl font-bold">Benutzer</h1>
      <a href="/admin/users.php?action=new" class="bg-amber-700 hover:bg-amber-800 text-white px-3 py-2 rounded text-sm">+ Neu</a>
    </div>

    <?php if ($pendingCount > 0): ?>
      <div class="bg-amber-50 border border-amber-300 rounded-lg p-3 mb-4 text-sm text-amber-900">
        <strong><?= $pendingCount ?></strong> offene Registrierungsanfrage<?= $pendingCount === 1 ? '' : 'n' ?> warten auf Freigabe.
      </div>
    <?php endif; ?>

    <div class="bg-white border border-stone-200 rounded overflow-hidden">
      <table class="w-full text-sm">
        <thead class="bg-stone-100 text-left">
          <tr><th class="px-3 py-2">Username</th><th>Name</th><th>E-Mail</th><th>Rolle</th><th>Status</th><th>Letzter Login</th><th></th></tr>
        </thead>
        <tbody>
          <?php foreach ($rows as $r): ?>
            <tr class="border-t <?= !$r['active'] ? 'bg-amber-50/50' : '' ?>">
              <td class="px-3 py-2 font-mono"><?= h($r['username']) ?></td>
              <td>
                <?= h($r['display_name'] ?? '') ?>
                <?php if (!empty($r['note'])): ?>
                  <div class="text-xs text-stone-500 mt-0.5 max-w-xs"><?= h(mb_substr($r['note'], 0, 90)) ?><?= mb_strlen($r['note']) > 90 ? '…' : '' ?></div>
                <?php endif; ?>
              </td>
              <td class="text-xs"><?= $r['email'] ? '<a class="text-amber-700 hover:underline" href="mailto:' . h($r['email']) . '">' . h($r['email']) . '</a>' : '<span class="text-stone-300">—</span>' ?></td>
              <td><?= $r['role'] === 'admin' ? 'Admin' : 'Mitglied' ?></td>
              <td>
                <?php if (!$r['active']): ?>
                  <span class="text-amber-700 font-bold">⏳ ausstehend</span>
                <?php else: ?>
                  <span class="text-emerald-700">aktiv</span>
                <?php endif; ?>
              </td>
              <td class="text-xs text-stone-500"><?= h(format_datetime_de($r['last_login_at'])) ?></td>
              <td class="text-right pr-2 whitespace-nowrap">
                <?php if (!$r['active']): ?>
                  <form method="post" class="inline">
                    <?= csrf_field() ?>
                    <input type="hidden" name="_action" value="approve">
                    <input type="hidden" name="id" value="<?= (int)$r['id'] ?>">
                    <button type="submit" class="bg-emerald-600 hover:bg-emerald-700 text-white px-2.5 py-1 rounded text-xs font-bold mr-2">Freigeben</button>
                  </form>
                <?php endif; ?>
                <a href="/admin/users.php?action=edit&id=<?= (int)$r['id'] ?>" class="text-amber-700 hover:underline">bearbeiten</a>
                <?php if ((int)$r['id'] !== ($me['id'] ?? 0)): ?>
                  <form method="post" class="inline" onsubmit="return confirm('Benutzer wirklich löschen?');">
                    <?= csrf_field() ?>
                    <input type="hidden" name="_action" value="delete">
                    <input type="hidden" name="id" value="<?= (int)$r['id'] ?>">
                    <button type="submit" class="text-red-700 hover:underline ml-3">löschen</button>
                  </form>
                <?php endif; ?>
              </td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
    <?php
}

include __DIR__ . '/_layout_end.php';
