<?php
require_once __DIR__ . '/../library/_init.php';
Templates::header('Datenschutz', '/datenschutz.php');
?>

<section class="max-w-3xl mx-auto px-4 py-12 md:py-16 prose-bzv">
  <div class="border-b border-stone-200/60 pb-6 mb-8">
    <div class="text-xs uppercase tracking-widest text-honey-700 font-extrabold mb-1">Rechtliches</div>
    <h1 class="text-3xl md:text-4xl font-display font-extrabold text-stone-900 tracking-tight">Datenschutzerklärung</h1>
  </div>

  <div data-edit-resource="page_blocks" data-edit-id="datenschutz.body">
    <?= block_html('datenschutz.body', '<p>Bitte im Admin unter „Texte → datenschutz.body" pflegen.</p>') ?>
  </div>
</section>

<?php Templates::footer(); ?>
