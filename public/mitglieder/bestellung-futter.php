<?php
require_once __DIR__ . '/../../library/_init.php';

if (!Auth::check()) {
    header('Location: /mitglieder/login.php');
    exit;
}
$user = Auth::user();
$pdo  = Database::pdo();

// Editierbare Preise (Komma -> Punkt)
$preisApifonda  = (float)str_replace(',', '.', block('bestellung.futter.preis_apifonda',  '1,26'));
$preisApiinvert = (float)str_replace(',', '.', block('bestellung.futter.preis_apiinvert', '1,02'));
$zielMail       = block('bestellung.futter.email', 'info@bienenzuchtverein-gruenberg.de');

// Bestellkanäle (editierbar via page_blocks)
$art           = 'futter';
$kanalFormular = flag_block('bestellung.futter.kanal_formular', true);
$kanalEmail    = flag_block('bestellung.futter.kanal_email',    true);
$kanalTelefon  = flag_block('bestellung.futter.kanal_telefon',  false);

$sent  = false;
$error = '';

if ($kanalFormular && $_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        csrf_check();
        $name   = trim((string)($_POST['name']   ?? ''));
        $email  = trim((string)($_POST['email']  ?? ''));
        $phone  = trim((string)($_POST['phone']  ?? ''));
        $sorte  = ($_POST['sorte'] ?? '') === 'apiinvert' ? 'apiinvert' : 'apifonda';
        $menge  = max(0, (int)($_POST['menge'] ?? 0));
        $bemerkung = trim((string)($_POST['bemerkung'] ?? ''));
        $hp     = trim((string)($_POST['website'] ?? ''));

        if ($hp !== '') {                       // Honeypot
            $sent = true;
        } elseif ($name === '') {
            $error = 'Bitte geben Sie Ihren Namen an.';
        } elseif ($menge < 1) {
            $error = 'Bitte geben Sie eine Menge in Kilogramm an.';
        } else {
            $preisProKg = $sorte === 'apiinvert' ? $preisApiinvert : $preisApifonda;
            $sorteLabel = $sorte === 'apiinvert' ? 'Apiinvert (flüssig)' : 'Apifonda (Teig)';
            $summe = round($menge * $preisProKg, 2);

            $details = [
                'sorte'        => $sorteLabel,
                'menge_kg'     => $menge,
                'preis_pro_kg' => $preisProKg,
                'bemerkung'    => $bemerkung,
            ];

            $body = "Neue Futter-Sammelbestellung\n"
                  . "============================\n\n"
                  . "Mitglied:  $name\n"
                  . "E-Mail:    " . ($email ?: '(nicht angegeben)') . "\n"
                  . "Telefon:   " . ($phone ?: '(nicht angegeben)') . "\n\n"
                  . "Sorte:     $sorteLabel\n"
                  . "Menge:     $menge kg\n"
                  . sprintf("Preis/kg:  %.2f €\n", $preisProKg)
                  . sprintf("Summe:     %.2f €\n", $summe)
                  . "\nBemerkung: " . ($bemerkung ?: '(keine)') . "\n"
                  . "\nEingegangen: " . date('d.m.Y H:i');

            $mailOk = Mailer::send($zielMail, 'Futter-Bestellung über die Webseite', $body, $email ?: null);

            try {
                $pdo->prepare(
                    "INSERT INTO bestellungen (art, member_user_id, member_name, member_email, member_phone, details, summe_eur, ip)
                     VALUES ('futter', ?, ?, ?, ?, ?, ?, ?)"
                )->execute([
                    (int)($user['id'] ?? 0) ?: null,
                    $name, $email ?: null, $phone ?: null,
                    json_encode($details, JSON_UNESCAPED_UNICODE),
                    $summe, $_SERVER['REMOTE_ADDR'] ?? null,
                ]);
            } catch (Throwable $e) {
                error_log('bestellungen(futter) insert failed: ' . $e->getMessage());
            }

            if (!$mailOk) {
                $error = 'Der Mail-Versand ist fehlgeschlagen. Bitte versuchen Sie es später erneut.';
            } else {
                $sent = true;
                $sentSumme = $summe;
            }
        }
    } catch (Throwable $e) {
        $error = 'Es ist ein Fehler aufgetreten: ' . $e->getMessage();
    }
}

Templates::header('Futter bestellen', '/mitglieder/');
?>

<section class="max-w-2xl mx-auto px-4 py-10">
  <a href="/mitglieder/" class="text-sm text-stone-500 hover:text-stone-800">&larr; Zurück zum Mitgliederbereich</a>

  <h1 class="text-3xl font-bold text-amber-900 mt-3" data-edit-resource="page_blocks" data-edit-id="bestellung.futter.title">
    🍯 <?= h(block('bestellung.futter.title', 'Futter-Sammelbestellung')) ?>
  </h1>

  <div class="prose-bzv text-stone-700 mt-3" data-edit-resource="page_blocks" data-edit-id="bestellung.futter.intro">
    <?= block_html('bestellung.futter.intro',
        '<p>Hier könnt ihr euch an der gemeinsamen Futter-Bestellung beteiligen. '
        . 'Durch die Sammelbestellung sparen wir Versandkosten und bekommen bessere Konditionen.</p>') ?>
  </div>

  <?php $deadline = block('bestellung.futter.deadline', ''); if ($deadline): ?>
    <p class="mt-3 text-sm font-semibold text-amber-800 bg-amber-50 border border-amber-200 rounded-lg px-3 py-2"
       data-edit-resource="page_blocks" data-edit-id="bestellung.futter.deadline">
      ⏳ Rückmeldung bitte bis: <?= h($deadline) ?>
    </p>
  <?php endif; ?>

  <?php if (!$sent) include __DIR__ . '/_bestell-kanaele.php'; ?>

  <?php if ($sent): ?>
    <div class="mt-6 bg-emerald-50 border border-emerald-200 text-emerald-950 rounded-2xl p-6 text-center">
      <span class="text-4xl">🎉</span>
      <h2 class="font-bold text-xl mt-2 text-emerald-900">Bestellung eingegangen!</h2>
      <p class="text-sm mt-2">Vielen Dank. Deine Futter-Bestellung wurde an den Vorstand übermittelt.</p>
      <a href="/mitglieder/" class="inline-block mt-4 bg-emerald-600 hover:bg-emerald-700 text-white font-semibold px-4 py-2 rounded-lg">Fertig</a>
    </div>
  <?php elseif (!$kanalFormular): ?>
    <?php if (!$kanalEmail && !$kanalTelefon): ?>
      <p class="mt-6 text-stone-500">Für diesen Bereich ist aktuell keine Bestellung möglich.</p>
    <?php endif; ?>
  <?php else: ?>
    <p class="mt-6 text-sm text-stone-500">… oder direkt über das Formular bestellen:</p>
    <?php if ($error): ?>
      <div class="mt-6 bg-red-50 border border-red-200 text-red-800 rounded-lg p-3 text-sm"><?= h($error) ?></div>
    <?php endif; ?>

    <form method="post" class="mt-6 bg-white border border-stone-200 rounded-2xl p-5 md:p-6 space-y-5">
      <?= csrf_field() ?>
      <input type="text" name="website" class="hidden" tabindex="-1" autocomplete="off" aria-hidden="true">

      <div class="grid sm:grid-cols-2 gap-3">
        <label class="block">
          <span class="text-sm font-semibold">Name *</span>
          <input type="text" name="name" required maxlength="160"
                 value="<?= h($_POST['name'] ?? ($user['display_name'] ?? '')) ?>"
                 class="w-full border border-stone-300 rounded-lg px-3 py-2 mt-1">
        </label>
        <label class="block">
          <span class="text-sm font-semibold">Telefon</span>
          <input type="tel" name="phone" maxlength="64"
                 value="<?= h($_POST['phone'] ?? '') ?>"
                 class="w-full border border-stone-300 rounded-lg px-3 py-2 mt-1">
        </label>
      </div>
      <label class="block">
        <span class="text-sm font-semibold">E-Mail (für Rückfragen)</span>
        <input type="email" name="email" maxlength="160"
               value="<?= h($_POST['email'] ?? '') ?>"
               class="w-full border border-stone-300 rounded-lg px-3 py-2 mt-1">
      </label>

      <?php $sorteSel = $_POST['sorte'] ?? 'apifonda'; ?>
      <fieldset class="border border-stone-200 rounded-xl p-4">
        <legend class="text-sm font-bold px-2">Futtersorte</legend>
        <label class="flex items-start gap-3 cursor-pointer py-1">
          <input type="radio" name="sorte" value="apifonda" <?= $sorteSel === 'apifonda' ? 'checked' : '' ?> class="mt-1" data-preis="<?= h(number_format($preisApifonda, 2, '.', '')) ?>">
          <span>
            <span class="font-semibold">Apifonda (Teig)</span><br>
            <span class="text-sm text-stone-500"><?= number_format($preisApifonda, 2, ',', '.') ?> € pro kg</span>
          </span>
        </label>
        <label class="flex items-start gap-3 cursor-pointer py-1">
          <input type="radio" name="sorte" value="apiinvert" <?= $sorteSel === 'apiinvert' ? 'checked' : '' ?> class="mt-1" data-preis="<?= h(number_format($preisApiinvert, 2, '.', '')) ?>">
          <span>
            <span class="font-semibold">Apiinvert (flüssig)</span><br>
            <span class="text-sm text-stone-500"><?= number_format($preisApiinvert, 2, ',', '.') ?> € pro kg</span>
          </span>
        </label>
      </fieldset>

      <label class="block">
        <span class="text-sm font-semibold">Menge in Kilogramm *</span>
        <input type="number" name="menge" id="menge" min="1" step="1" required
               value="<?= h($_POST['menge'] ?? '') ?>"
               class="w-40 border border-stone-300 rounded-lg px-3 py-2 mt-1">
      </label>

      <div class="bg-amber-50 border border-amber-200 rounded-xl px-4 py-3 text-sm">
        Voraussichtliche Summe:
        <span id="summe" class="font-bold text-lg text-amber-900">– €</span>
        <span class="text-stone-500">(verbindliche Abrechnung durch den Vorstand)</span>
      </div>

      <label class="block">
        <span class="text-sm font-semibold">Bemerkung</span>
        <textarea name="bemerkung" rows="2" maxlength="500"
                  class="w-full border border-stone-300 rounded-lg px-3 py-2 mt-1"><?= h($_POST['bemerkung'] ?? '') ?></textarea>
      </label>

      <button type="submit" class="w-full bg-amber-700 hover:bg-amber-800 text-white font-bold py-3 rounded-xl">
        Bestellung absenden
      </button>
    </form>

    <script>
      (function() {
        const form  = document.currentScript.previousElementSibling;
        const menge = form.querySelector('#menge');
        const summe = form.querySelector('#summe');
        function calc() {
          const sel = form.querySelector('input[name=sorte]:checked');
          const preis = parseFloat(sel.dataset.preis);
          const kg = parseInt(menge.value || '0', 10);
          if (kg > 0) summe.textContent = (kg * preis).toFixed(2).replace('.', ',') + ' €';
          else summe.textContent = '– €';
        }
        form.querySelectorAll('input[name=sorte]').forEach(r => r.addEventListener('change', calc));
        menge.addEventListener('input', calc);
      })();
    </script>
  <?php endif; ?>
</section>

<?php Templates::footer(); ?>
