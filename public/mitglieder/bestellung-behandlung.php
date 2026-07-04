<?php
require_once __DIR__ . '/../../library/_init.php';

if (!Auth::check()) {
    header('Location: /mitglieder/login.php');
    exit;
}
$user = Auth::user();
$pdo  = Database::pdo();

$preisOxal = (float)str_replace(',', '.', block('bestellung.behandlung.preis_oxal', '12,00'));
$zielMail  = block('bestellung.behandlung.email', 'info@bienenzuchtverein-gruenberg.de');

// Bestellkanäle (editierbar via page_blocks)
$art           = 'behandlung';
$kanalFormular = flag_block('bestellung.behandlung.kanal_formular', true);
$kanalEmail    = flag_block('bestellung.behandlung.kanal_email',    true);
$kanalTelefon  = flag_block('bestellung.behandlung.kanal_telefon',  false);

$sent  = false;
$error = '';

if ($kanalFormular && $_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        csrf_check();
        $vorname  = trim((string)($_POST['vorname']  ?? ''));
        $nachname = trim((string)($_POST['nachname'] ?? ''));
        $mnr      = trim((string)($_POST['mitgliedsnr'] ?? ''));
        $email    = trim((string)($_POST['email'] ?? ''));
        $phone    = trim((string)($_POST['phone'] ?? ''));
        $strasse  = trim((string)($_POST['strasse'] ?? ''));
        $plz      = trim((string)($_POST['plz'] ?? ''));
        $ort      = trim((string)($_POST['ort'] ?? ''));
        $anzOxal  = max(0, (int)($_POST['anzahl_oxal'] ?? 0));
        $voelker  = max(0, (int)($_POST['voelker'] ?? 0));
        $iban     = strtoupper(trim((string)($_POST['iban'] ?? '')));
        $kontoinh = trim((string)($_POST['kontoinhaber'] ?? ''));
        $okBestell= !empty($_POST['ok_bestellung']);
        $okSepa   = !empty($_POST['ok_sepa']);
        $hp       = trim((string)($_POST['website'] ?? ''));

        $ibanClean = preg_replace('/\s+/', '', $iban);

        if ($hp !== '') {
            $sent = true;
        } elseif ($vorname === '' || $nachname === '') {
            $error = 'Bitte Vor- und Nachnamen angeben.';
        } elseif ($voelker < 1) {
            $error = 'Bitte die beabsichtigte Völkerzahl für das kommende Jahr angeben.';
        } elseif ($anzOxal > 0 && ($strasse === '' || $plz === '' || $ort === '')) {
            $error = 'Für eine Oxalsäure-Bestellung bitte die vollständige Anschrift angeben.';
        } elseif ($anzOxal > 0 && $ibanClean === '') {
            $error = 'Für die Abbuchung wird Ihre IBAN benötigt.';
        } elseif ($anzOxal > 0 && (!$okBestell || !$okSepa)) {
            $error = 'Für eine Oxalsäure-Bestellung müssen beide Einwilligungen bestätigt werden.';
        } else {
            $summe = round($anzOxal * $preisOxal, 2);
            $name  = trim("$vorname $nachname");

            $details = [
                'vorname'      => $vorname,
                'nachname'     => $nachname,
                'mitgliedsnr'  => $mnr,
                'strasse'      => $strasse,
                'plz'          => $plz,
                'ort'          => $ort,
                'anzahl_oxal'  => $anzOxal,
                'preis_oxal'   => $preisOxal,
                'voelker_kommend' => $voelker,
                'iban'         => $ibanClean,
                'kontoinhaber' => $kontoinh ?: $name,
                'ok_bestellung'=> $okBestell,
                'ok_sepa'      => $okSepa,
            ];

            $body = "Behandlung & Völkermeldung\n"
                  . "==========================\n\n"
                  . "Mitglied:        $name\n"
                  . "Mitgliedsnummer: " . ($mnr ?: '(nicht angegeben)') . "\n"
                  . "Anschrift:       " . trim("$strasse, $plz $ort", ', ') . "\n"
                  . "E-Mail:          " . ($email ?: '(nicht angegeben)') . "\n"
                  . "Telefon:         " . ($phone ?: '(nicht angegeben)') . "\n\n"
                  . "Völkerzahl kommendes Jahr: $voelker\n\n"
                  . "--- Oxalsäure-Bestellung ---\n"
                  . sprintf("Einheiten:  %d  x %.2f €\n", $anzOxal, $preisOxal)
                  . sprintf("Summe:      %.2f €  (wird abgebucht)\n", $summe)
                  . ($anzOxal > 0
                        ? "IBAN:        $ibanClean\n"
                        . "Kontoinhaber:" . ($kontoinh ?: $name) . "\n"
                        . "Einwilligung Bestellung: " . ($okBestell ? 'JA' : 'NEIN') . "\n"
                        . "SEPA-Mandat erteilt:     " . ($okSepa ? 'JA' : 'NEIN') . "\n"
                        : "(keine Oxalsäure bestellt — reine Völkermeldung)\n")
                  . "\nEingegangen: " . date('d.m.Y H:i');

            $mailOk = Mailer::send($zielMail, 'Behandlung/Völkermeldung über die Webseite', $body, $email ?: null);

            try {
                $pdo->prepare(
                    "INSERT INTO bestellungen (art, member_user_id, member_name, member_email, member_phone, details, summe_eur, ip)
                     VALUES ('behandlung', ?, ?, ?, ?, ?, ?, ?)"
                )->execute([
                    (int)($user['id'] ?? 0) ?: null,
                    $name, $email ?: null, $phone ?: null,
                    json_encode($details, JSON_UNESCAPED_UNICODE),
                    $summe, $_SERVER['REMOTE_ADDR'] ?? null,
                ]);
            } catch (Throwable $e) {
                error_log('bestellungen(behandlung) insert failed: ' . $e->getMessage());
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

Templates::header('Behandlung & Völkermeldung', '/mitglieder/');
?>

<section class="max-w-2xl mx-auto px-4 py-10">
  <a href="/mitglieder/" class="text-sm text-stone-500 hover:text-stone-800">&larr; Zurück zum Mitgliederbereich</a>

  <h1 class="text-3xl font-bold text-amber-900 mt-3" data-edit-resource="page_blocks" data-edit-id="bestellung.behandlung.title">
    🧪 <?= h(block('bestellung.behandlung.title', 'Behandlung & Völkermeldung')) ?>
  </h1>

  <div class="prose-bzv text-stone-700 mt-3" data-edit-resource="page_blocks" data-edit-id="bestellung.behandlung.intro">
    <?= block_html('bestellung.behandlung.intro',
        '<p>Bitte meldet hier eure beabsichtigte Völkerzahl für das kommende Jahr und – falls gewünscht – '
        . 'eure Oxalsäure-Bestellung. Die Sammelbestellung wird vom Vorstand durchgeführt.</p>') ?>
  </div>

  <?php $deadline = block('bestellung.behandlung.deadline', ''); if ($deadline): ?>
    <p class="mt-3 text-sm font-semibold text-amber-800 bg-amber-50 border border-amber-200 rounded-lg px-3 py-2"
       data-edit-resource="page_blocks" data-edit-id="bestellung.behandlung.deadline">
      ⏳ Rückmeldung bitte bis: <?= h($deadline) ?>
    </p>
  <?php endif; ?>

  <?php if (!$sent) include __DIR__ . '/_bestell-kanaele.php'; ?>

  <?php if ($sent): ?>
    <div class="mt-6 bg-emerald-50 border border-emerald-200 text-emerald-950 rounded-2xl p-6 text-center">
      <span class="text-4xl">🎉</span>
      <h2 class="font-bold text-xl mt-2 text-emerald-900">Meldung eingegangen!</h2>
      <p class="text-sm mt-2">Vielen Dank. Deine Behandlung/Völkermeldung wurde an den Vorstand übermittelt.</p>
      <a href="/mitglieder/" class="inline-block mt-4 bg-emerald-600 hover:bg-emerald-700 text-white font-semibold px-4 py-2 rounded-lg">Fertig</a>
    </div>
  <?php elseif (!$kanalFormular): ?>
    <?php if (!$kanalEmail && !$kanalTelefon): ?>
      <p class="mt-6 text-stone-500">Für diesen Bereich ist aktuell keine Meldung möglich.</p>
    <?php endif; ?>
  <?php else: ?>
    <p class="mt-6 text-sm text-stone-500">… oder direkt über das Formular melden:</p>
    <?php if ($error): ?>
      <div class="mt-6 bg-red-50 border border-red-200 text-red-800 rounded-lg p-3 text-sm"><?= h($error) ?></div>
    <?php endif; ?>

    <form method="post" class="mt-6 bg-white border border-stone-200 rounded-2xl p-5 md:p-6 space-y-6">
      <?= csrf_field() ?>
      <input type="text" name="website" class="hidden" tabindex="-1" autocomplete="off" aria-hidden="true">

      <!-- Stammdaten -->
      <div class="space-y-3">
        <h2 class="text-sm font-bold uppercase tracking-wide text-stone-500">Mitglied</h2>
        <div class="grid sm:grid-cols-2 gap-3">
          <label class="block">
            <span class="text-sm font-semibold">Vorname *</span>
            <input type="text" name="vorname" required maxlength="80"
                   value="<?= h($_POST['vorname'] ?? '') ?>"
                   class="w-full border border-stone-300 rounded-lg px-3 py-2 mt-1">
          </label>
          <label class="block">
            <span class="text-sm font-semibold">Nachname *</span>
            <input type="text" name="nachname" required maxlength="80"
                   value="<?= h($_POST['nachname'] ?? '') ?>"
                   class="w-full border border-stone-300 rounded-lg px-3 py-2 mt-1">
          </label>
        </div>
        <div class="grid sm:grid-cols-2 gap-3">
          <label class="block">
            <span class="text-sm font-semibold">Mitgliedsnummer <span class="text-stone-400 font-normal">(optional)</span></span>
            <input type="text" name="mitgliedsnr" maxlength="32"
                   value="<?= h($_POST['mitgliedsnr'] ?? '') ?>"
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
        <label class="block">
          <span class="text-sm font-semibold">Straße & Hausnummer</span>
          <input type="text" name="strasse" maxlength="160"
                 value="<?= h($_POST['strasse'] ?? '') ?>"
                 class="w-full border border-stone-300 rounded-lg px-3 py-2 mt-1">
        </label>
        <div class="grid grid-cols-3 gap-3">
          <label class="block">
            <span class="text-sm font-semibold">PLZ</span>
            <input type="text" name="plz" maxlength="10"
                   value="<?= h($_POST['plz'] ?? '') ?>"
                   class="w-full border border-stone-300 rounded-lg px-3 py-2 mt-1">
          </label>
          <label class="block col-span-2">
            <span class="text-sm font-semibold">Ort</span>
            <input type="text" name="ort" maxlength="120"
                   value="<?= h($_POST['ort'] ?? '') ?>"
                   class="w-full border border-stone-300 rounded-lg px-3 py-2 mt-1">
          </label>
        </div>
      </div>

      <!-- Völkermeldung -->
      <div class="space-y-3 border-t border-stone-200 pt-5">
        <h2 class="text-sm font-bold uppercase tracking-wide text-stone-500">Völkermeldung</h2>
        <label class="block">
          <span class="text-sm font-semibold">Beabsichtigte Völkerzahl für das kommende Jahr *</span>
          <input type="number" name="voelker" min="0" step="1" required
                 value="<?= h($_POST['voelker'] ?? '') ?>"
                 class="w-32 border border-stone-300 rounded-lg px-3 py-2 mt-1">
        </label>
      </div>

      <!-- Oxalsäure -->
      <div class="space-y-3 border-t border-stone-200 pt-5">
        <h2 class="text-sm font-bold uppercase tracking-wide text-stone-500">Oxalsäure-Bestellung <span class="font-normal normal-case text-stone-400">(optional)</span></h2>

        <div class="bg-amber-50 border border-amber-200 rounded-xl px-4 py-3 text-sm text-amber-900" data-edit-resource="page_blocks" data-edit-id="bestellung.preis_hinweis">
          <strong>Hinweis zu den Preisen:</strong> <?= h(block('bestellung.preis_hinweis',
              'Die aktuellen Preise werden mit der jeweiligen Bestellaufforderung per E-Mail bekannt gegeben. Bitte tragt hier nur die gewünschte Menge ein — die Abrechnung erfolgt später durch den Vorstand.')) ?>
        </div>

        <label class="flex items-center justify-between gap-3">
          <span class="font-semibold">Anzahl Oxalsäure-Einheiten</span>
          <input type="number" name="anzahl_oxal" min="0" step="1"
                 value="<?= h($_POST['anzahl_oxal'] ?? '0') ?>"
                 class="w-24 border border-stone-300 rounded-lg px-3 py-2 text-right">
        </label>

        <!-- IBAN nur relevant bei Bestellung -->
        <div id="sepa-block" class="space-y-3">
          <label class="block">
            <span class="text-sm font-semibold">IBAN</span>
            <input type="text" name="iban" maxlength="40" placeholder="DE.. .. .. .. .. .."
                   value="<?= h($_POST['iban'] ?? '') ?>"
                   class="w-full border border-stone-300 rounded-lg px-3 py-2 mt-1 font-mono">
          </label>
          <label class="block">
            <span class="text-sm font-semibold">Kontoinhaber <span class="text-stone-400 font-normal">(falls abweichend)</span></span>
            <input type="text" name="kontoinhaber" maxlength="160"
                   value="<?= h($_POST['kontoinhaber'] ?? '') ?>"
                   class="w-full border border-stone-300 rounded-lg px-3 py-2 mt-1">
          </label>

          <div class="bg-stone-50 border border-stone-200 rounded-xl p-4 text-sm text-stone-700 space-y-3">
            <div class="prose-bzv" data-edit-resource="page_blocks" data-edit-id="bestellung.behandlung.sepa.body">
              <?= block_html('bestellung.behandlung.sepa.body',
                  '<p>Mit der Bestellung beauftrage ich den Vorstand des Bienenzuchtvereins Grünberg, '
                  . 'die Oxalsäure in meinem Namen mitzubestellen. Ich ermächtige den Verein, den fälligen '
                  . 'Betrag mittels SEPA-Lastschrift von meinem oben angegebenen Konto einzuziehen. '
                  . 'Zugleich weise ich mein Kreditinstitut an, die Lastschrift einzulösen.</p>') ?>
            </div>
            <label class="flex items-start gap-2">
              <input type="checkbox" name="ok_bestellung" value="1" class="mt-1" <?= !empty($_POST['ok_bestellung']) ? 'checked' : '' ?>>
              <span>Ich beauftrage den Vorstand, die Oxalsäure in meinem Namen mitzubestellen.</span>
            </label>
            <label class="flex items-start gap-2">
              <input type="checkbox" name="ok_sepa" value="1" class="mt-1" <?= !empty($_POST['ok_sepa']) ? 'checked' : '' ?>>
              <span>Ich erteile dem Bienenzuchtverein Grünberg das SEPA-Lastschriftmandat für den oben genannten Betrag.</span>
            </label>
          </div>
        </div>
      </div>

      <button type="submit" class="w-full bg-amber-700 hover:bg-amber-800 text-white font-bold py-3 rounded-xl">
        Absenden
      </button>
    </form>

  <?php endif; ?>
</section>

<?php Templates::footer(); ?>
