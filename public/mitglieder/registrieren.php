<?php
require_once __DIR__ . '/../../library/_init.php';

if (Auth::check()) {
    header('Location: /mitglieder/');
    exit;
}

$pdo   = Database::pdo();
$sent  = false;
$error = '';
$values = ['display_name' => '', 'username' => '', 'email' => '', 'note' => ''];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        csrf_check();
        $display = trim((string)($_POST['display_name'] ?? ''));
        $user    = strtolower(trim((string)($_POST['username'] ?? '')));
        $email   = trim((string)($_POST['email']    ?? ''));
        $pw      = (string)($_POST['password']      ?? '');
        $pw2     = (string)($_POST['password2']     ?? '');
        $note    = trim((string)($_POST['note']     ?? ''));
        $hp      = trim((string)($_POST['website']  ?? ''));
        $values  = compact('display_name', 'username', 'email', 'note');
        $values['display_name'] = $display;
        $values['username']     = $user;

        if ($hp !== '') {
            $sent = true;   // Honeypot: still erfolgreich tun
        } elseif ($display === '') {
            $error = 'Bitte gib deinen vollständigen Namen an.';
        } elseif (!preg_match('/^[a-z0-9._-]{3,64}$/', $user)) {
            $error = 'Benutzername: 3-64 Zeichen, nur a-z, 0-9, Punkt, Unterstrich, Bindestrich.';
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $error = 'Bitte eine gültige E-Mail-Adresse angeben.';
        } elseif (strlen($pw) < 8) {
            $error = 'Passwort: mindestens 8 Zeichen.';
        } elseif ($pw !== $pw2) {
            $error = 'Die Passwörter stimmen nicht überein.';
        } else {
            // Prüfen, ob Username schon vergeben
            $st = $pdo->prepare("SELECT id FROM users WHERE username = ?");
            $st->execute([$user]);
            if ($st->fetch()) {
                $error = 'Dieser Benutzername ist bereits vergeben. Bitte einen anderen wählen.';
            } else {
                $pdo->prepare(
                    "INSERT INTO users (username, password_hash, display_name, email, note, role, active)
                     VALUES (?, ?, ?, ?, ?, 'member', 0)"
                )->execute([$user, password_hash($pw, PASSWORD_BCRYPT), $display, $email, $note ?: null]);
                $newId = (int)$pdo->lastInsertId();

                // Benachrichtigung an Vorstand
                $adminMail = block('members.notify_email', SCHWARM_MAIL_TO);
                $body = "Neue Mitglieder-Registrierung\n"
                      . "=============================\n\n"
                      . "Name:          $display\n"
                      . "Benutzername:  $user\n"
                      . "E-Mail:        $email\n"
                      . ($note !== '' ? "\nBegründung:\n$note\n" : '')
                      . "\nFreigabe im Admin: " . SITE_URL . "/admin/users.php?action=edit&id=$newId\n"
                      . "Oder direkt freigeben: " . SITE_URL . "/admin/users.php (auf 'Freigeben' klicken)\n";
                @Mailer::send($adminMail, 'Neue Mitglieder-Registrierung', $body, $email);
                $sent = true;
            }
        }
    } catch (Throwable $e) {
        $error = 'Es ist ein Fehler aufgetreten: ' . $e->getMessage();
    }
}

Templates::header('Registrieren', '/mitglieder/');
?>

<section class="max-w-md mx-auto px-4 py-10">
  <a href="/mitglieder/login.php" class="text-sm text-stone-500 hover:text-stone-800">&larr; Zurück zum Login</a>

  <h1 class="text-2xl font-bold text-amber-900 mt-3" data-edit-resource="page_blocks" data-edit-id="members.register.title">
    <?= h(block('members.register.title', 'Mitglieder-Zugang beantragen')) ?>
  </h1>

  <div class="prose-bzv text-sm text-stone-700 mt-2 [&>p]:my-2" data-edit-resource="page_blocks" data-edit-id="members.register.intro">
    <?= block_html('members.register.intro',
        '<p>Vereinsmitglieder können sich hier selbst registrieren. Der Vorstand prüft den Antrag und schaltet '
      . 'den Zugang frei – das kann ein paar Tage dauern. Du bekommst eine Bestätigung per E-Mail.</p>') ?>
  </div>

  <?php if ($sent): ?>
    <div class="mt-6 bg-emerald-50 border border-emerald-200 rounded-2xl p-6 text-center">
      <span class="text-4xl">📬</span>
      <h2 class="font-bold text-xl mt-2 text-emerald-900">Antrag eingegangen!</h2>
      <p class="text-sm mt-2 text-emerald-800">
        Der Vorstand wurde benachrichtigt. Sobald dein Zugang freigeschaltet ist, kannst du dich einloggen.
      </p>
      <a href="/mitglieder/login.php" class="inline-block mt-4 bg-emerald-600 hover:bg-emerald-700 text-white font-semibold px-4 py-2 rounded-lg">Zum Login</a>
    </div>
  <?php else: ?>
    <?php if ($error): ?>
      <div class="bg-red-100 text-red-900 p-3 rounded mt-4 text-sm"><?= h($error) ?></div>
    <?php endif; ?>

    <form method="post" class="space-y-4 mt-6 bg-white border border-stone-200 rounded p-5">
      <?= csrf_field() ?>
      <input type="text" name="website" class="hidden" tabindex="-1" autocomplete="off" aria-hidden="true">

      <label class="block">
        <span class="text-sm font-semibold">Vor- und Nachname *</span>
        <input type="text" name="display_name" required maxlength="128" autofocus
               value="<?= h($values['display_name']) ?>"
               class="w-full border border-stone-300 rounded px-3 py-2 mt-1">
      </label>
      <label class="block">
        <span class="text-sm font-semibold">Benutzername (wird zum Anmelden verwendet) *</span>
        <input type="text" name="username" required pattern="[a-z0-9._-]{3,64}" maxlength="64"
               value="<?= h($values['username']) ?>"
               class="w-full border border-stone-300 rounded px-3 py-2 mt-1 font-mono">
        <span class="text-xs text-stone-500">Kleinbuchstaben, Zahlen, Punkt, Unterstrich, Bindestrich.</span>
      </label>
      <label class="block">
        <span class="text-sm font-semibold">E-Mail *</span>
        <input type="email" name="email" required maxlength="160"
               value="<?= h($values['email']) ?>"
               class="w-full border border-stone-300 rounded px-3 py-2 mt-1">
      </label>
      <div class="grid grid-cols-2 gap-3">
        <label class="block">
          <span class="text-sm font-semibold">Passwort *</span>
          <input type="password" name="password" required minlength="8"
                 class="w-full border border-stone-300 rounded px-3 py-2 mt-1">
        </label>
        <label class="block">
          <span class="text-sm font-semibold">Wiederholen *</span>
          <input type="password" name="password2" required minlength="8"
                 class="w-full border border-stone-300 rounded px-3 py-2 mt-1">
        </label>
      </div>
      <label class="block">
        <span class="text-sm font-semibold">Bemerkung <span class="font-normal text-stone-500">(optional)</span></span>
        <textarea name="note" rows="3" maxlength="500"
                  placeholder="z.B. seit wann beim Verein, Wohnort, Besonderheiten…"
                  class="w-full border border-stone-300 rounded px-3 py-2 mt-1"><?= h($values['note']) ?></textarea>
      </label>

      <button type="submit" class="w-full bg-amber-700 hover:bg-amber-800 text-white font-semibold py-2.5 rounded">
        Antrag absenden
      </button>
    </form>
  <?php endif; ?>
</section>
<?php Templates::footer(); ?>
