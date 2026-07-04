<?php
require_once __DIR__ . '/../library/_init.php';

$members = Database::pdo()->query(
    "SELECT id, name, role, photo_path, email, phone
       FROM vorstand
      WHERE is_published = 1
      ORDER BY sort_order ASC, id ASC"
)->fetchAll();

Templates::header('Vorstand', '/vorstand.php');
?>

<section class="max-w-5xl mx-auto px-4 py-12 md:py-16">
  <div class="border-b border-stone-200/60 pb-6 mb-10 flex items-end justify-between gap-4">
    <div>
      <div class="text-xs uppercase tracking-widest text-honey-700 font-extrabold mb-1" data-edit-resource="page_blocks" data-edit-id="vorstand.eyebrow">
        <?= h(block('vorstand.eyebrow', 'Ansprechpartner')) ?>
      </div>
      <h1 class="text-3xl md:text-4xl font-display font-extrabold text-stone-900 tracking-tight" data-edit-resource="page_blocks" data-edit-id="vorstand.title">
        <?= h(block('vorstand.title', 'Vorstand')) ?>
      </h1>
      <p class="text-stone-500 mt-2 text-base max-w-xl" data-edit-resource="page_blocks" data-edit-id="vorstand.subtitle">
        <?= h(block('vorstand.subtitle', 'Der Vorstand des Bienenzuchtvereins Grünberg steht Ihnen bei Fragen, Anregungen oder Wünschen gerne zur Seite.')) ?>
      </p>
    </div>
    <button type="button" class="bzv-edit-new shrink-0 bg-amber-700 hover:bg-amber-800 text-white font-bold px-3 py-1.5 rounded-full text-xs" data-edit-resource="vorstand">+ Mitglied</button>
  </div>

  <?php if (!$members): ?>
    <div class="bg-white border border-stone-200/60 rounded-2xl p-8 text-center shadow-sm">
      <span class="text-3xl">👥</span>
      <p class="text-stone-500 mt-2 font-medium" data-edit-resource="page_blocks" data-edit-id="vorstand.empty">
        <?= h(block('vorstand.empty', 'Aktuell sind keine Vorstandsmitglieder eingetragen.')) ?>
      </p>
    </div>
  <?php else: ?>
    <div class="grid sm:grid-cols-2 md:grid-cols-3 gap-8">
      <?php foreach ($members as $m): ?>
        <div class="bg-white border border-stone-200/60 rounded-2xl p-6 text-center hover:border-honey-200 hover:shadow-md transition-all duration-300 flex flex-col justify-between group" data-edit-resource="vorstand" data-edit-id="<?= (int)$m['id'] ?>">
          <div>
            <?php if ($m['photo_path']): ?>
              <div class="relative w-36 h-36 mx-auto rounded-full overflow-hidden border-4 border-amber-50 shadow-inner group-hover:scale-102 transition-transform duration-300">
                <img src="/bilder/<?= h($m['photo_path']) ?>" alt=""
                     class="w-full h-full object-cover">
              </div>
            <?php else: ?>
              <div class="w-36 h-36 rounded-full mx-auto bg-gradient-to-br from-amber-50 to-amber-100/60 border-4 border-amber-50 flex items-center justify-center shadow-inner group-hover:scale-102 transition-transform duration-300">
                <img src="/assets/favicon.png" alt="" class="w-20 h-20 object-contain opacity-80" width="256" height="256">
              </div>
            <?php endif; ?>
            
            <h2 class="font-display font-extrabold text-stone-900 text-xl mt-5 leading-snug"><?= h($m['name']) ?></h2>
            <div class="text-honey-700 font-bold text-sm uppercase tracking-wider mt-1"><?= h($m['role']) ?></div>
          </div>
          
          <?php if ($m['email'] || $m['phone']): ?>
            <div class="mt-6 pt-5 border-t border-stone-100 flex flex-col gap-2">
              <?php if ($m['phone']): ?>
                <a href="tel:<?= h(tel_to_e164($m['phone'])) ?>"
                   class="flex items-center justify-center gap-2 bg-amber-500 hover:bg-amber-600 text-stone-950 font-bold py-2.5 px-4 rounded-xl text-sm shadow-sm transition-all active:scale-98">
                  <span>📞</span> <span><?= h($m['phone']) ?></span>
                </a>
              <?php endif; ?>
              <?php if ($m['email']): ?>
                <a href="mailto:<?= h($m['email']) ?>"
                   class="flex items-center justify-center gap-2 bg-white border border-amber-200 hover:bg-honey-50 text-honey-800 font-semibold py-2.5 px-4 rounded-xl text-sm shadow-sm transition-all active:scale-98">
                  <span>✉️</span> <span class="truncate max-w-[180px]">E-Mail senden</span>
                </a>
              <?php endif; ?>
            </div>
          <?php endif; ?>
        </div>
      <?php endforeach; ?>
    </div>
  <?php endif; ?>

  <?php $kontaktMail = block('vorstand.kontakt.email', SCHWARM_MAIL_TO); ?>
  <div class="mt-12 bg-gradient-to-r from-amber-50 to-amber-100/20 border border-amber-200/40 rounded-2xl p-6 text-center shadow-sm">
    <div class="text-lg font-bold text-honey-900 flex items-center justify-center gap-2"
         data-edit-resource="page_blocks" data-edit-id="vorstand.kontakt.title">
      <span>✉️</span> <?= h(block('vorstand.kontakt.title', 'Allgemeine Anfragen')) ?>
    </div>
    <p class="text-stone-600 text-sm mt-1 max-w-lg mx-auto leading-relaxed"
       data-edit-resource="page_blocks" data-edit-id="vorstand.kontakt.text">
      <?= h(block('vorstand.kontakt.text', 'Haben Sie allgemeine Fragen zum Verein oder der Imkerei? Sie erreichen uns direkt per E-Mail unter:')) ?>
    </p>
    <div data-edit-resource="page_blocks" data-edit-id="vorstand.kontakt.email" class="mt-3">
      <a href="mailto:<?= h($kontaktMail) ?>"
         class="inline-block bg-white border border-amber-200 hover:bg-honey-50 text-honey-800 font-bold px-6 py-2.5 rounded-xl shadow-sm transition-all active:scale-98">
        <?= h($kontaktMail) ?>
      </a>
    </div>
  </div>
</section>

<?php Templates::footer(); ?>
