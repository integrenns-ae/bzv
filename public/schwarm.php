<?php
require_once __DIR__ . '/../library/_init.php';

Templates::header('Schwarm melden', '/schwarm.php');
?>

<section class="max-w-3xl mx-auto px-4 py-12 md:py-16">
  <!-- Header -->
  <div class="border-b border-stone-200/60 pb-6 mb-10">
    <div class="text-xs uppercase tracking-widest text-honey-700 font-extrabold mb-1" data-edit-resource="page_blocks" data-edit-id="schwarm.eyebrow">
      <?= h(block('schwarm.eyebrow', 'Schwarmrettung')) ?>
    </div>
    <h1 class="text-3xl md:text-4xl font-display font-extrabold text-stone-900 tracking-tight flex items-start gap-3" data-edit-resource="page_blocks" data-edit-id="schwarm.title">
      <img src="/assets/bee.svg" alt="" class="w-8 h-8 md:w-10 md:h-10 mt-1 shrink-0">
      <span><?= h(block('schwarm.title', 'Bienenschwarm entdeckt? Keine Panik – wir helfen.')) ?></span>
    </h1>
    <p class="text-stone-500 mt-2 text-base leading-relaxed" data-edit-resource="page_blocks" data-edit-id="schwarm.intro">
      <?= h(block('schwarm.intro', '')) ?>
    </p>
  </div>

  <!-- Direktkontakt-Box -->
  <div class="bg-white border-2 border-amber-500 rounded-2xl shadow-xl shadow-honey-900/5 p-6 md:p-8 relative overflow-hidden mb-10">
    <div class="absolute top-0 inset-x-0 h-1.5 bg-gradient-to-r from-amber-400 to-amber-600"></div>
    <div class="flex items-center gap-2 text-xs uppercase tracking-widest text-honey-700 font-extrabold bg-amber-50 px-3 py-1 rounded-md w-fit" data-edit-resource="page_blocks" data-edit-id="schwarm.kontakt.eyebrow">
      <?= h(block('schwarm.kontakt.eyebrow', '⚡ Schnellster Weg')) ?>
    </div>

    <h2 class="text-xl md:text-2xl font-display font-extrabold text-stone-900 mt-4" data-edit-resource="page_blocks" data-edit-id="schwarm.kontakt.title">
      <?= h(block('schwarm.kontakt.title', 'Telefonischer Notruf')) ?>
    </h2>
    <p class="text-stone-500 text-sm mt-1 leading-relaxed" data-edit-resource="page_blocks" data-edit-id="schwarm.kontakt.body">
      <?= h(block('schwarm.kontakt.body', 'Per Anruf oder WhatsApp erreichen Sie uns am schnellsten. Bitte halten Sie nach Möglichkeit den Standort und ein Foto bereit.')) ?>
    </p>

    <?php
      $sTel  = block('contact.schwarm.tel', SCHWARM_TEL);
      $sE164 = tel_to_e164($sTel);
    ?>
    <div class="mt-6 flex flex-col sm:flex-row gap-4 items-stretch sm:items-center">
      <a href="tel:<?= h($sE164) ?>"
         class="flex-1 flex items-center justify-center gap-3 bg-amber-500 hover:bg-amber-600 text-stone-950 font-extrabold py-4 px-6 rounded-xl text-xl shadow-md transition-all active:scale-98">
        <span>📞</span> <?= h($sTel) ?>
      </a>
      <a href="https://wa.me/<?= h(ltrim($sE164, '+')) ?>"
         target="_blank" rel="noopener"
         class="flex-1 flex items-center justify-center gap-2 bg-emerald-600 hover:bg-emerald-700 text-white font-bold py-4 px-6 rounded-xl text-lg shadow-md transition-all active:scale-98">
        <span>💬</span> WhatsApp öffnen
      </a>
    </div>
  </div>

  <!-- Beruhigender Hinweis -->
  <div class="bg-amber-50/50 border border-amber-200/50 rounded-2xl p-6 md:p-8 mb-6">
    <h2 class="text-xl font-display font-extrabold text-honey-900 flex items-center gap-2" data-edit-resource="page_blocks" data-edit-id="schwarm.calm.title">
      <span>🛡️</span> <?= h(block('schwarm.calm.title', 'Bitte beachten Sie')) ?>
    </h2>
    <div class="prose-bzv mt-4 text-sm md:text-base [&>p]:my-3" data-edit-resource="page_blocks" data-edit-id="schwarm.calm.body">
      <?= block_html('schwarm.calm.body', '') ?>
    </div>
  </div>

  <!-- Hinweis zum fehlenden Online-Formular -->
  <p class="text-xs text-stone-500 text-center leading-relaxed" data-edit-resource="page_blocks" data-edit-id="schwarm.no_form_note">
    <?= h(block('schwarm.no_form_note',
        'Wir nehmen Schwarm-Meldungen bewusst nur persönlich – telefonisch oder per WhatsApp – entgegen. So können wir direkt klären, wo der Schwarm hängt und wie wir am schnellsten hinkommen.')) ?>
  </p>
</section>

<?php Templates::footer(); ?>
