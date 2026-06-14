<?php
$skipAuth = true;
$pageTitle = 'Login';

require_once __DIR__ . '/../../library/_init.php';

$error = '';
$next  = isset($_GET['next']) ? (string)$_GET['next'] : '/admin/';

if (Auth::check() && (Auth::user()['role'] ?? '') === 'admin') {
    header('Location: /admin/');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        csrf_check();
        $u = trim((string)($_POST['username'] ?? ''));
        $p = (string)($_POST['password'] ?? '');
        if (Auth::login($u, $p) && (Auth::user()['role'] ?? '') === 'admin') {
            // next nur akzeptieren, wenn relativ
            $target = (str_starts_with($next, '/admin/') || $next === '/admin/') ? $next : '/admin/';
            header('Location: ' . $target);
            exit;
        }
        $error = 'Anmeldung fehlgeschlagen.';
    } catch (Throwable $e) {
        $error = $e->getMessage();
    }
}

include __DIR__ . '/_layout.php';
?>
<div class="max-w-sm mx-auto bg-white border border-stone-200 rounded p-6 mt-10">
  <h1 class="text-xl font-bold mb-4">Admin-Login</h1>
  <?php if ($error): ?>
    <div class="bg-red-100 text-red-900 p-3 rounded mb-4 text-sm"><?= h($error) ?></div>
  <?php endif; ?>
  <form method="post" class="space-y-4">
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
</div>
<?php include __DIR__ . '/_layout_end.php';
