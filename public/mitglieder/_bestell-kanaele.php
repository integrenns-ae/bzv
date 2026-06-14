<?php
/**
 * Partial: Bestellkanal-Boxen (E-Mail / Telefon) für die Bestellseiten.
 *
 * Erwartet vor dem Include gesetzt:
 *   $art          string  'futter' | 'behandlung' | 'zucht'
 *   $kanalEmail   bool     E-Mail-Bestellung anbieten?
 *   $kanalTelefon bool     Telefon-Bestellung anbieten?
 */
$_zielMail   = block("bestellung.$art.email", '');
$_telefon    = block("bestellung.$art.telefon", '');
$_kontakt    = block("bestellung.$art.kontakt", '');
$_telLink    = preg_replace('/[^0-9+]/', '', $_telefon);

if (($kanalEmail && $_zielMail !== '') || ($kanalTelefon && $_telefon !== '')):
?>
<div class="mt-6 grid sm:grid-cols-2 gap-4">
  <?php if ($kanalEmail && $_zielMail !== ''): ?>
    <div class="bg-white border border-stone-200 rounded-2xl p-5">
      <div class="font-bold text-amber-900 flex items-center gap-2">📧 Bestellung per E-Mail</div>
      <p class="text-sm text-stone-500 mt-1">Senden Sie Ihre Bestellung an:</p>
      <a href="mailto:<?= h($_zielMail) ?>"
         class="inline-block mt-2 bg-amber-100 hover:bg-amber-200 text-amber-900 font-semibold px-4 py-2 rounded-lg break-all">
        <?= h($_zielMail) ?>
      </a>
    </div>
  <?php endif; ?>

  <?php if ($kanalTelefon && $_telefon !== ''): ?>
    <div class="bg-white border border-stone-200 rounded-2xl p-5">
      <div class="font-bold text-amber-900 flex items-center gap-2">📞 Bestellung per Telefon</div>
      <p class="text-sm text-stone-500 mt-1">
        <?= $_kontakt !== '' ? 'Rufen Sie an bei ' . h($_kontakt) . ':' : 'Rufen Sie uns an:' ?>
      </p>
      <a href="tel:<?= h($_telLink) ?>"
         class="inline-block mt-2 bg-emerald-600 hover:bg-emerald-700 text-white font-bold px-4 py-2 rounded-lg">
        <?= h($_telefon) ?>
      </a>
    </div>
  <?php endif; ?>
</div>
<?php endif; ?>
