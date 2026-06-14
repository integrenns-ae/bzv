<?php
require_once __DIR__ . '/../../library/_init.php';

if (!Auth::check()) {
    header('Location: /mitglieder/login.php');
    exit;
}
$user = Auth::user();
$pdo  = Database::pdo();

$preisStand   = (float)str_replace(',', '.', block('bestellung.zucht.preis_stand',       '20,00'));
$preisBeleg   = (float)str_replace(',', '.', block('bestellung.zucht.preis_belegstelle', '25,00'));
$zuschuss     = (float)str_replace(',', '.', block('bestellung.zucht.zuschuss',          '5,00'));
$zielMail     = block('bestellung.zucht.email', 'wbughahl@gmx.de');

// Bestellkanäle (editierbar via page_blocks) — Zucht: Standard nur E-Mail + Telefon
$art           = 'zucht';
$kanalFormular = flag_block('bestellung.zucht.kanal_formular', false);
$kanalEmail    = flag_block('bestellung.zucht.kanal_email',    true);
$kanalTelefon  = flag_block('bestellung.zucht.kanal_telefon',  true);

$sent  = false;
$error = '';

if ($kanalFormular && $_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        csrf_check();
        $name      = trim((string)($_POST['name']  ?? ''));
        $email     = trim((string)($_POST['email'] ?? ''));
        $phone     = trim((string)($_POST['phone'] ?? ''));
        $anzStand  = max(0, (int)($_POST['anzahl_stand'] ?? 0));
        $anzBeleg  = max(0, (int)($_POST['anzahl_beleg'] ?? 0));
        $bemerkung = trim((string)($_POST['bemerkung'] ?? ''));
        $hp        = trim((string)($_POST['website'] ?? ''));

        if ($hp !== '') {
            $sent = true;
        } elseif ($name === '') {
            $error = 'Bitte geben Sie Ihren Namen an.';
        } elseif (($anzStand + $anzBeleg) < 1) {
            $error = 'Bitte geben Sie mindestens eine Königin an.';
        } else {
            $gesamt   = $anzStand + $anzBeleg;
            $brutto   = $anzStand * $preisStand + $anzBeleg * $preisBeleg;
            $abzug    = $gesamt >= 1 ? $zuschuss : 0.0;   // 5 € Vereinszuschuss / Mitglied / Jahr
            $summe    = round($brutto - $abzug, 2);

            $details = [
                'anzahl_stand'  => $anzStand,
                'preis_stand'   => $preisStand,
                'anzahl_beleg'  => $anzBeleg,
                'preis_beleg'   => $preisBeleg,
                'brutto'        => round($brutto, 2),
                'zuschuss'      => $abzug,
                'bemerkung'     => $bemerkung,
            ];

            $body = "Neue Königinnen-Bestellung (Zucht)\n"
                  . "==================================\n\n"
                  . "Mitglied:  $name\n"
                  . "E-Mail:    " . ($email ?: '(nicht angegeben)') . "\n"
                  . "Telefon:   " . ($phone ?: '(nicht angegeben)') . "\n\n"
                  . sprintf("Standbegattete Königinnen:        %d  x %.2f €\n", $anzStand, $preisStand)
                  . sprintf("Belegstellenbegattete Königinnen: %d  x %.2f €\n", $anzBeleg, $preisBeleg)
                  . sprintf("Zwischensumme:                    %.2f €\n", $brutto)
                  . sprintf("Vereinszuschuss:                 -%.2f €\n", $abzug)
                  . sprintf("Zu zahlen:                        %.2f €\n", $summe)
                  . "\nBemerkung: " . ($bemerkung ?: '(keine)') . "\n"
                  . "\nEingegangen: " . date('d.m.Y H:i');

            $mailOk = Mailer::send($zielMail, 'Königinnen-Bestellung über die Webseite', $body, $email ?: null);

            try {
                $pdo->prepare(
                    "INSERT INTO bestellungen (art, member_user_id, member_name, member_email, member_phone, details, summe_eur, ip)
                     VALUES ('zucht', ?, ?, ?, ?, ?, ?, ?)"
                )->execute([
                    (int)($user['id'] ?? 0) ?: null,
                    $name, $email ?: null, $phone ?: null,
                    json_encode($details, JSON_UNESCAPED_UNICODE),
                    $summe, $_SERVER['REMOTE_ADDR'] ?? null,
                ]);
            } catch (Throwable $e) {
                error_log('bestellungen(zucht) insert failed: ' . $e->getMessage());
            }

            if (!$mailOk) {
                $error = 'Der Mail-Versand ist fehlgeschlagen. Bitte versuchen Sie es später erneut.';
            } else {
                $sent = true;
            }
        }
    } catch (Throwable $e) {
        $error = 'Es ist ein Fehler aufgetreten: ' . $e->getMessage();
    }
}

Templates::header('Königinnen bestellen', '/mitglieder/');
?>

<section class="max-w-2xl mx-auto px-4 py-10">
  <a href="/mitglieder/" class="text-sm text-stone-500 hover:text-stone-800">&larr; Zurück zum Mitgliederbereich</a>

  <h1 class="text-3xl font-bold text-amber-900 mt-3" data-edit-resource="page_blocks" data-edit-id="bestellung.zucht.title">
    👑 <?= h(block('bestellung.zucht.title', 'Königinnen-Bestellung')) ?>
  </h1>

  <div class="prose-bzv text-stone-700 mt-3" data-edit-resource="page_blocks" data-edit-id="bestellung.zucht.intro">
    <?= block_html('bestellung.zucht.intro',
        '<p>Über den Verein könnt ihr standbegattete oder belegstellenbegattete Königinnen beziehen. '
        . 'Der Verein bezuschusst eine Königin pro Mitglied und Jahr.</p>') ?>
  </div>

  <?php $deadline = block('bestellung.zucht.deadline', ''); if ($deadline): ?>
    <p class="mt-3 text-sm font-semibold text-amber-800 bg-amber-50 border border-amber-200 rounded-lg px-3 py-2"
       data-edit-resource="page_blocks" data-edit-id="bestellung.zucht.deadline">
      ⏳ Rückmeldung bitte bis: <?= h($deadline) ?>
    </p>
  <?php endif; ?>

  <?php if (!$sent) include __DIR__ . '/_bestell-kanaele.php'; ?>

  <?php if ($sent): ?>
    <div class="mt-6 bg-emerald-50 border border-emerald-200 text-emerald-950 rounded-2xl p-6 text-center">
      <span class="text-4xl">🎉</span>
      <h2 class="font-bold text-xl mt-2 text-emerald-900">Bestellung eingegangen!</h2>
      <p class="text-sm mt-2">Vielen Dank. Deine Königinnen-Bestellung wurde übermittelt.</p>
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

      <fieldset class="border border-stone-200 rounded-xl p-4 space-y-3">
        <legend class="text-sm font-bold px-2">Anzahl Königinnen</legend>
        <label class="flex items-center justify-between gap-3">
          <span>
            <span class="font-semibold">Standbegattet</span>
            <span class="text-sm text-stone-500">— <?= number_format($preisStand, 2, ',', '.') ?> € / Stück</span>
          </span>
          <input type="number" name="anzahl_stand" id="anz-stand" min="0" step="1"
                 value="<?= h($_POST['anzahl_stand'] ?? '0') ?>"
                 data-preis="<?= h(number_format($preisStand, 2, '.', '')) ?>"
                 class="w-24 border border-stone-300 rounded-lg px-3 py-2 text-right">
        </label>
        <label class="flex items-center justify-between gap-3">
          <span>
            <span class="font-semibold">Belegstellenbegattet</span>
            <span class="text-sm text-stone-500">— <?= number_format($preisBeleg, 2, ',', '.') ?> € / Stück</span>
          </span>
          <input type="number" name="anzahl_beleg" id="anz-beleg" min="0" step="1"
                 value="<?= h($_POST['anzahl_beleg'] ?? '0') ?>"
                 data-preis="<?= h(number_format($preisBeleg, 2, '.', '')) ?>"
                 class="w-24 border border-stone-300 rounded-lg px-3 py-2 text-right">
        </label>
      </fieldset>

      <div class="bg-amber-50 border border-amber-200 rounded-xl px-4 py-3 text-sm space-y-1">
        <div>Zwischensumme: <span id="brutto" class="font-semibold">– €</span></div>
        <div>Vereinszuschuss: <span class="font-semibold text-emerald-700">– <?= number_format($zuschuss, 2, ',', '.') ?> €</span>
          <span class="text-stone-500">(eine Königin / Mitglied / Jahr)</span></div>
        <div class="text-base">Zu zahlen: <span id="summe" class="font-bold text-lg text-amber-900">– €</span></div>
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
        const form   = document.currentScript.previousElementSibling;
        const stand  = form.querySelector('#anz-stand');
        const beleg  = form.querySelector('#anz-beleg');
        const elBrutto = form.querySelector('#brutto');
        const elSumme  = form.querySelector('#summe');
        const zuschuss = <?= json_encode($zuschuss) ?>;
        function calc() {
          const ns = parseInt(stand.value || '0', 10);
          const nb = parseInt(beleg.value || '0', 10);
          const brutto = ns * parseFloat(stand.dataset.preis) + nb * parseFloat(beleg.dataset.preis);
          const abzug  = (ns + nb) >= 1 ? zuschuss : 0;
          if ((ns + nb) > 0) {
            elBrutto.textContent = brutto.toFixed(2).replace('.', ',') + ' €';
            elSumme.textContent  = (brutto - abzug).toFixed(2).replace('.', ',') + ' €';
          } else {
            elBrutto.textContent = '– €';
            elSumme.textContent  = '– €';
          }
        }
        [stand, beleg].forEach(el => el.addEventListener('input', calc));
      })();
    </script>
  <?php endif; ?>
</section>

<?php Templates::footer(); ?>
