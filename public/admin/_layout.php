<?php
/**
 * Admin-Layout. Eingebunden von admin/* pages.
 * Erwartet: $pageTitle, $current (URL-Pfad relativ zu /admin/)
 * Variable $skipAuth=true setzen für Login-Page.
 *
 * URL-Parameter ?minimal=1: rendert ohne Header/Sidebar/Footer für Modal-Iframe.
 */
require_once __DIR__ . '/../../library/_init.php';

if (empty($skipAuth)) {
    Auth::requireRole('admin', '/admin/login.php');
}
$user = Auth::user();
$pageTitle = $pageTitle ?? 'Admin';
$current   = $current   ?? '';
$minimal   = !empty($_GET['minimal']) || !empty($_POST['minimal']);

function admin_nav(string $href, string $label, string $current): string
{
    $active = ($href === $current) ? ' bg-amber-700 text-white' : ' hover:bg-amber-100';
    return '<a href="/admin/' . h($href) . '" class="block px-3 py-2 rounded' . $active . '">' . h($label) . '</a>';
}
?>
<!DOCTYPE html>
<html lang="de">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title><?= h($pageTitle) ?> · Admin · <?= h(SITE_NAME) ?></title>
  <script src="https://cdn.tailwindcss.com"></script>

  <!-- Trix WYSIWYG-Editor -->
  <link rel="stylesheet" href="https://unpkg.com/trix@2.1.15/dist/trix.css">
  <script src="https://unpkg.com/trix@2.1.15/dist/trix.umd.min.js"></script>
  <style>
    trix-editor {
      min-height: 12rem;
      background: white;
      border: 1px solid #d6d3d1;
      border-radius: 0.375rem;
      padding: 0.75rem 1rem;
      font-size: 0.95rem;
      line-height: 1.6;
    }
    trix-editor:focus {
      outline: 2px solid #d97706;
      outline-offset: -1px;
    }
    trix-toolbar .trix-button-row {
      flex-wrap: wrap;
    }
    trix-toolbar .trix-button-group {
      border-radius: 0.375rem;
    }
    /* Inline-Code-Button verstecken — Bild-Anhang-Button bleibt sichtbar (für Bild-Upload). */
    trix-toolbar .trix-button--icon-code { display: none; }
    /* Eingefügte Bilder im Editor begrenzen */
    trix-editor figure img { max-width: 100%; height: auto; }
  </style>
  <meta name="csrf-token" content="<?= h(csrf_token()) ?>">
</head>
<body class="bg-stone-100 min-h-screen flex flex-col<?= $minimal ? ' admin-minimal' : '' ?>">
<script>
  // Trix-Bild-Upload: hängt an /admin/api/trix-upload.php
  document.addEventListener('trix-attachment-add', function(event) {
    const attachment = event.attachment;
    if (!attachment.file) return;
    const token = document.querySelector('meta[name="csrf-token"]')?.content || '';
    const fd = new FormData();
    fd.append('image', attachment.file);
    fd.append('_csrf', token);
    const xhr = new XMLHttpRequest();
    xhr.open('POST', '/admin/api/trix-upload.php', true);
    xhr.upload.addEventListener('progress', e => {
      if (e.lengthComputable) attachment.setUploadProgress(e.loaded / e.total * 100);
    });
    xhr.addEventListener('load', () => {
      try {
        const r = JSON.parse(xhr.responseText || '{}');
        if (xhr.status === 200 && r.url) {
          attachment.setAttributes({ url: r.url, href: r.url });
        } else {
          alert('Bild-Upload fehlgeschlagen: ' + (r.error || ('HTTP ' + xhr.status)));
          attachment.remove();
        }
      } catch (e) {
        alert('Bild-Upload fehlgeschlagen (Antwort konnte nicht gelesen werden).');
        attachment.remove();
      }
    });
    xhr.addEventListener('error', () => {
      alert('Netzwerkfehler beim Bild-Upload.');
      attachment.remove();
    });
    xhr.send(fd);
  });
  // Bilder im Editor nur erlauben — andere Datei-Typen verwerfen
  document.addEventListener('trix-file-accept', function(event) {
    if (!event.file.type.startsWith('image/')) {
      event.preventDefault();
      alert('Nur Bild-Dateien (JPG/PNG/WebP) können eingefügt werden.');
    }
  });
</script>
<?php if (!$minimal): ?>
<header class="bg-amber-900 text-white">
  <div class="max-w-6xl mx-auto px-4 py-3 flex justify-between items-center">
    <a href="/admin/" class="font-semibold flex items-center gap-2">
      <img src="/assets/favicon.png" alt="" class="w-6 h-6 object-contain shrink-0" width="256" height="256">
      <span>Admin · <?= h(SITE_NAME) ?></span>
    </a>
    <?php if ($user): ?>
      <div class="text-sm flex items-center gap-3">
        <span class="opacity-80"><?= h($user['display_name']) ?></span>
        <a href="/" class="opacity-80 hover:opacity-100 text-xs">↗ zur Webseite</a>
        <a href="/admin/logout.php" class="bg-amber-700 hover:bg-amber-600 px-2 py-1 rounded text-xs">Abmelden</a>
      </div>
    <?php endif; ?>
  </div>
</header>
<?php endif; ?>

<div class="flex-1 max-w-6xl w-full mx-auto p-4 <?= (!$minimal && !empty($user)) ? 'grid md:grid-cols-[200px_1fr] gap-6' : '' ?>">
  <?php if (!$minimal && !empty($user)): ?>
    <aside class="text-sm">
      <nav class="space-y-1">
        <?= admin_nav('index.php',     'Übersicht',     $current) ?>
        <?= admin_nav('termine.php',   'Termine',       $current) ?>
        <?= admin_nav('news.php',      'Aktuelles',     $current) ?>
        <?= admin_nav('gallery.php',   'Bildergalerie', $current) ?>
        <?= admin_nav('vorstand.php',  'Vorstand',      $current) ?>
        <?= admin_nav('imker.php',     'Imker (Karte)', $current) ?>
        <?= admin_nav('bestellungen.php', 'Bestellungen', $current) ?>
        <?= admin_nav('kontaktkanaele.php', 'Kontaktkanäle', $current) ?>
        <?= admin_nav('infos.php',     'Infos',         $current) ?>
        <?= admin_nav('links.php',     'Links',         $current) ?>
        <?= admin_nav('page_blocks.php', 'Texte',       $current) ?>
        <?= admin_nav('internal_docs.php', 'Mitglieder-Doks', $current) ?>
        <?= admin_nav('users.php',     'Benutzer',      $current) ?>
      </nav>
    </aside>
  <?php endif; ?>

  <main>
    <?php
      foreach (['success' => 'bg-emerald-100 text-emerald-900', 'error' => 'bg-red-100 text-red-900'] as $type => $cls) {
          foreach (flash($type) as $msg) {
              echo '<div class="mb-4 px-4 py-3 rounded ' . $cls . '">' . h($msg) . '</div>';
          }
      }
    ?>
