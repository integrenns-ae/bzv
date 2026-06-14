<?php
require_once __DIR__ . '/../library/_init.php';

$sent  = false;
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        csrf_check();
        $name   = trim((string)($_POST['name']    ?? ''));
        $email  = trim((string)($_POST['email']   ?? ''));
        $phone  = trim((string)($_POST['phone']   ?? ''));
        $address = trim((string)($_POST['address'] ?? ''));
        $note   = trim((string)($_POST['note']    ?? ''));
        $hp     = trim((string)($_POST['website'] ?? ''));

        if ($hp !== '') {
            $sent = true;
        } elseif ($name === '' || ($email === '' && $phone === '')) {
            $error = 'Bitte geben Sie Ihren Namen und mindestens eine Kontaktmöglichkeit an.';
        } elseif ($email !== '' && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $error = 'Die eingegebene E-Mail-Adresse ist ungültig.';
        } else {
            $body = "Neue Beitritts-Anfrage\n"
                  . "======================\n\n"
                  . "Name:    $name\n"
                  . "Mail:    " . ($email ?: '(nicht angegeben)') . "\n"
                  . "Tel:     " . ($phone ?: '(nicht angegeben)') . "\n"
                  . "Adresse:\n"  . ($address ?: '(nicht angegeben)') . "\n\n"
                  . "Nachricht:\n" . ($note ?: '(keine)') . "\n\n"
                  . "Eingegangen: " . date('d.m.Y H:i');

            $ok = Mailer::send(
                block('contact.vorstand.mail', SCHWARM_MAIL_TO),
                'Beitrittsanfrage über die Webseite',
                $body,
                $email ?: null
            );
            if (!$ok) {
                $error = 'Der Mail-Versand ist fehlgeschlagen. Bitte verwenden Sie den PDF-Antrag oder rufen Sie uns an.';
            } else {
                $sent = true;
            }
        }
    } catch (Throwable $e) {
        $error = $e->getMessage();
    }
}

Templates::header('Mitglied werden', '/mitglied-werden.php');
?>

<section class="max-w-3xl mx-auto px-4 py-12 md:py-16">
  <!-- Header -->
  <div class="border-b border-stone-200/60 pb-6 mb-10">
    <div class="text-xs uppercase tracking-widest text-honey-700 font-extrabold mb-1" data-edit-resource="page_blocks" data-edit-id="mitglied.eyebrow">
      <?= h(block('mitglied.eyebrow', 'Gemeinschaft')) ?>
    </div>
    <h1 class="text-3xl md:text-4xl font-display font-extrabold text-stone-900 tracking-tight" data-edit-resource="page_blocks" data-edit-id="mitglied.title">
      <?= h(block('mitglied.title', '🤝 Mitglied werden')) ?>
    </h1>
    <p class="text-stone-500 mt-2 text-base leading-relaxed" data-edit-resource="page_blocks" data-edit-id="mitglied.intro">
      <?= h(block('mitglied.intro', 'Schön, dass Sie sich für eine Mitgliedschaft beim Bienenzuchtverein Grünberg interessieren! Ob aktiver Imker oder passiver Förderer — jeder ist herzlich willkommen.')) ?>
    </p>
  </div>

  <!-- Über den Verein -->
  <section class="mb-12">
    <h2 class="text-2xl font-display font-extrabold text-honey-900 mb-3" data-edit-resource="page_blocks" data-edit-id="mitglied.verein.title">
      <?= h(block('mitglied.verein.title', 'Was uns ausmacht')) ?>
    </h2>
    <div class="prose-bzv text-stone-700 leading-relaxed [&>p]:my-3" data-edit-resource="page_blocks" data-edit-id="mitglied.verein.body">
      <?= block_html('mitglied.verein.body', '') ?>
    </div>
  </section>

  <!-- Wer wird gebraucht -->
  <section class="mb-12 bg-gradient-to-br from-amber-50 to-amber-100/30 border border-amber-200/40 rounded-2xl p-6 md:p-8 shadow-sm">
    <h2 class="text-xl md:text-2xl font-display font-extrabold text-honey-900 flex items-center gap-3" data-edit-resource="page_blocks" data-edit-id="mitglied.rollen.title">
      <img src="/assets/bee.svg" alt="" class="w-7 h-7 shrink-0">
      <span><?= h(block('mitglied.rollen.title', 'Auch ohne eigene Bienen herzlich willkommen')) ?></span>
    </h2>
    <div class="prose-bzv mt-4 text-stone-700 leading-relaxed [&>p]:my-3" data-edit-resource="page_blocks" data-edit-id="mitglied.rollen.body">
      <?= block_html('mitglied.rollen.body', '') ?>
    </div>
  </section>


  <!-- Formular -->
  <section id="formular" class="scroll-mt-24 border-t border-stone-200/60 pt-10">
    <?php if ($sent): ?>
      <div class="bg-emerald-50 border border-emerald-200 text-emerald-950 rounded-2xl p-6 md:p-8 text-center shadow-sm">
        <span class="text-4xl">🎉</span>
        <h3 class="font-display font-extrabold text-emerald-900 text-xl mt-3" data-edit-resource="page_blocks" data-edit-id="mitglied.success.title">
          <?= h(block('mitglied.success.title', 'Anfrage erfolgreich übermittelt!')) ?>
        </h3>
        <p class="text-sm mt-2 leading-relaxed text-emerald-800" data-edit-resource="page_blocks" data-edit-id="mitglied.success.body">
          <?= h(block('mitglied.success.body', 'Vielen Dank für Ihr Interesse! Wir haben Ihre Beitrittsanfrage erhalten und werden uns in Kürze mit den entsprechenden Unterlagen bei Ihnen melden.')) ?>
        </p>
      </div>
    <?php else: ?>
      <div class="flex flex-col sm:flex-row sm:items-start sm:justify-between gap-4 mb-6">
        <div>
          <h2 class="text-xl font-display font-extrabold text-stone-900" data-edit-resource="page_blocks" data-edit-id="mitglied.form.title">
            <?= h(block('mitglied.form.title', 'So werden Sie Mitglied')) ?>
          </h2>
          <p class="text-sm text-stone-500 mt-1">
            Senden Sie uns Ihre Kontaktdaten — wir melden uns mit allen Unterlagen.
          </p>
        </div>
        <a href="/downloads/mitgliedsantrag.pdf"
           class="inline-flex items-center gap-2 shrink-0 bg-amber-50 hover:bg-amber-100 border border-amber-200 text-honey-800 font-semibold px-4 py-2 rounded-xl text-xs transition-colors shadow-sm">
          <span>⬇</span> Alternativ: Antrag als PDF
        </a>
      </div>
      
      <?php if ($error): ?>
        <div class="bg-red-50 border border-red-200 text-red-950 p-4 rounded-xl mb-6 text-sm font-semibold flex gap-2">
          <span>⚠️</span> <?= h($error) ?>
        </div>
      <?php endif; ?>

      <form method="post" class="space-y-5 bg-white border border-stone-200/60 rounded-2xl p-6 md:p-8 shadow-sm">
        <?= csrf_field() ?>
        <input type="text" name="website" class="hidden" tabindex="-1" autocomplete="off">

        <div>
          <label class="block text-stone-700 text-xs font-bold uppercase tracking-wider mb-1.5">Ihr Name *</label>
          <input type="text" name="name" required placeholder="Max Mustermann"
                 class="w-full border border-stone-200 rounded-xl px-4 py-3 text-sm focus:border-honey-500 focus:ring-2 focus:ring-honey-200/50 transition-all outline-none">
        </div>

        <div class="grid sm:grid-cols-2 gap-4">
          <div>
            <label class="block text-stone-700 text-xs font-bold uppercase tracking-wider mb-1.5">E-Mail-Adresse</label>
            <input type="email" name="email" placeholder="beispiel@domain.de"
                   class="w-full border border-stone-200 rounded-xl px-4 py-3 text-sm focus:border-honey-500 focus:ring-2 focus:ring-honey-200/50 transition-all outline-none">
          </div>
          <div>
            <label class="block text-stone-700 text-xs font-bold uppercase tracking-wider mb-1.5">Telefonnummer</label>
            <input type="tel" name="phone" placeholder="z.B. 0176 1234567"
                   class="w-full border border-stone-200 rounded-xl px-4 py-3 text-sm focus:border-honey-500 focus:ring-2 focus:ring-honey-200/50 transition-all outline-none">
          </div>
        </div>

        <div>
          <label class="block text-stone-700 text-xs font-bold uppercase tracking-wider mb-1.5">Postanschrift</label>
          <textarea name="address" rows="3" placeholder="Straße, Hausnummer, PLZ & Ort"
                    class="w-full border border-stone-200 rounded-xl px-4 py-3 text-sm focus:border-honey-500 focus:ring-2 focus:ring-honey-200/50 transition-all outline-none"></textarea>
        </div>

        <div>
          <label class="block text-stone-700 text-xs font-bold uppercase tracking-wider mb-1.5">Nachricht / Fragen an den Verein</label>
          <textarea name="note" rows="4" placeholder="Haben Sie bereits Erfahrung in der Imkerei oder möchten Sie als förderndes Mitglied beitreten?"
                    class="w-full border border-stone-200 rounded-xl px-4 py-3 text-sm focus:border-honey-500 focus:ring-2 focus:ring-honey-200/50 transition-all outline-none"></textarea>
        </div>

        <button type="submit"
                class="w-full sm:w-auto bg-honey-700 hover:bg-honey-800 text-white font-bold px-6 py-3.5 rounded-xl shadow-md transition-all active:scale-98">
          Anfrage absenden
        </button>
      </form>
    <?php endif; ?>
  </section>
</section>

<?php Templates::footer(); ?>
