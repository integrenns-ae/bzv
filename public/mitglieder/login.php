<?php
require_once __DIR__ . '/../../library/_init.php';

if (Auth::check()) {
    header('Location: /mitglieder/');
    exit;
}

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        csrf_check();
        $u = trim((string)($_POST['username'] ?? ''));
        $p = (string)($_POST['password'] ?? '');
        if (Auth::login($u, $p)) {
            header('Location: /mitglieder/');
            exit;
        }
        $error = 'Anmeldung fehlgeschlagen.';
    } catch (Throwable $e) {
        $error = $e->getMessage();
    }
}

Templates::header('Mitglieder-Login', '/mitglieder/');
?>
<section class="max-w-sm mx-auto px-4 py-10">
  <h1 class="text-2xl font-bold text-amber-900">Mitglieder-Login</h1>
  <p class="text-sm text-stone-600 mt-2">Zugangsdaten erhalten Mitglieder nach Freigabe durch den Vorstand.</p>

  <?php if ($error): ?>
    <div class="bg-red-100 text-red-900 p-3 rounded mt-4 text-sm"><?= h($error) ?></div>
  <?php endif; ?>

  <form method="post" class="space-y-4 mt-6 bg-white border border-stone-200 rounded p-5">
    <?= csrf_field() ?>
    <div>
      <label class="block text-sm font-semibold mb-1">Benutzername</label>
      <input type="text" name="username" required autofocus
             class="w-full border border-stone-300 rounded px-3 py-2">
    </div>
    <div>
      <label class="block text-sm font-semibold mb-1">Passwort</label>
      <input type="password" name="password" required
             class="w-full border border-stone-300 rounded px-3 py-2">
    </div>
    <button type="submit" class="w-full bg-amber-700 hover:bg-amber-800 text-white font-semibold py-2 rounded">
      Anmelden
    </button>
  </form>

  <p class="text-sm text-stone-600 mt-6 text-center">
    Noch kein Zugang? <a href="/mitglieder/registrieren.php" class="text-amber-700 hover:underline font-semibold">Jetzt registrieren</a>
    — der Vorstand schaltet dich frei.
  </p>
</section>
<?php Templates::footer(); ?>
