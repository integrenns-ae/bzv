<?php
declare(strict_types=1);

/**
 * Page-Layout: Header und Footer.
 * Modernisiertes Design für den Bienenzuchtverein Grünberg.
 */
final class Templates
{
    public static function header(string $pageTitle = '', string $currentPath = '/'): void
    {
        require_once __DIR__ . '/../config/db.php';
        $title = $pageTitle
            ? h($pageTitle) . ' — ' . h(SITE_NAME)
            : h(SITE_NAME) . ' — ' . h(SITE_TAGLINE);
        ?>
<!DOCTYPE html>
<html lang="de" class="scroll-smooth">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= $title ?></title>
    <meta name="description" content="<?= h(SITE_NAME . ' — ' . SITE_TAGLINE) ?>">
    <link rel="icon" type="image/png" sizes="32x32" href="/assets/favicon-32.png">
    <link rel="icon" type="image/png" sizes="16x16" href="/assets/favicon-16.png">
    <link rel="icon" type="image/x-icon" href="/assets/favicon.ico">
    <link rel="apple-touch-icon" sizes="180x180" href="/assets/apple-touch-icon.png">
    <link rel="shortcut icon" href="/assets/favicon.ico">
    
    <!-- Google Fonts: Outfit & Plus Jakarta Sans -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@400;500;600;700;800&family=Plus+Jakarta+Sans:ital,wght@0,300;0,400;0,500;0,600;0,700;1,400&display=swap" rel="stylesheet">
    
    <!-- Tailwind Play CDN -->
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
      tailwind.config = {
        theme: {
          extend: {
            fontFamily: {
              sans: ['"Plus Jakarta Sans"', 'system-ui', '-apple-system', 'Segoe UI', 'sans-serif'],
              display: ['Outfit', 'sans-serif'],
            },
            colors: {
              honey: {
                50: '#fffbeb',
                100: '#fef3c7',
                200: '#fde68a',
                300: '#fcd34d',
                400: '#fbbf24',
                500: '#f59e0b',
                600: '#d97706',
                700: '#b45309',
                800: '#92400e',
                900: '#78350f',
                950: '#451a03',
              },
              forest: {
                50: '#f0fdf4',
                100: '#dcfce7',
                200: '#bbf7d0',
                300: '#86efac',
                400: '#4ade80',
                500: '#22c55e',
                600: '#16a34a',
                700: '#15803d',
                800: '#166534',
                900: '#14532d',
                950: '#052e16',
              }
            }
          }
        }
      }
    </script>
    
    <style>
      /* Globale Übergänge & Design-Raffinesse */
      body {
        text-rendering: optimizeLegibility;
        -webkit-font-smoothing: antialiased;
      }
      
      /* Navigation Styling via CSS-Targeting für sauberes PHP */
      header nav a {
        color: #4b5563; /* gray-600 */
        padding: 0.5rem 0.875rem;
        border-radius: 0.5rem;
        transition: all 0.2s cubic-bezier(0.4, 0, 0.2, 1);
        font-weight: 500;
        font-size: 0.925rem;
        display: inline-flex;
        align-items: center;
        gap: 0.25rem;
      }
      header nav a:hover {
        color: #78350f; /* honey-900 */
        background-color: #fffbeb; /* honey-50 */
      }
      header nav a[aria-current="page"] {
        color: #b45309; /* honey-700 */
        background-color: #fef3c7; /* honey-100 */
        font-weight: 600;
      }

      /* Premium-Prose für CMS-Inhalte (.prose-bzv) — etwas großzügiger gesetzt für Lesbarkeit */
      .prose-bzv {
        font-size: 1.0625rem;   /* ~17px Standard, war 16px */
        line-height: 1.8;
        color: #44403c;
      }
      @media (min-width: 768px) {
        .prose-bzv { font-size: 1.125rem; }  /* 18px auf Tablet+ */
      }
      .prose-bzv h2 {
        font-family: 'Outfit', sans-serif;
        font-size: 1.625rem;
        font-weight: 700;
        margin: 2.5rem 0 1rem;
        color: #78350f;
        line-height: 1.25;
      }
      .prose-bzv h3 {
        font-family: 'Outfit', sans-serif;
        font-size: 1.35rem;
        font-weight: 600;
        margin: 2rem 0 0.75rem;
        color: #92400e;
        line-height: 1.3;
      }
      .prose-bzv p,
      .prose-bzv > div {
        margin: 0 0 1em;
        line-height: 1.8;
        color: #44403c;
      }
      /* echte Leerzeile zwischen aufeinanderfolgenden Absätzen */
      /* (Trix-Editor speichert Absätze als <div>; Standard-Inhalte als <p>) */
      .prose-bzv p + p,
      .prose-bzv > div + div,
      .prose-bzv p + div,
      .prose-bzv > div + p {
        margin-top: 1.8em;
      }
      .prose-bzv ul {
        list-style: none;
        padding-left: 1.75rem;
        margin: 1.5rem 0;
      }
      .prose-bzv ul li {
        position: relative;
        margin: 0.75rem 0;
        line-height: 1.7;
        color: #44403c;
      }
      .prose-bzv ul li::before {
        content: "";
        position: absolute;
        left: -1.6rem;
        top: 0.4rem;
        width: 1rem;
        height: 1rem;
        background-image: url('/assets/bee.svg');
        background-size: contain;
        background-repeat: no-repeat;
        background-position: center;
        transform: rotate(-90deg);
      }
      .prose-bzv ol {
        list-style: decimal;
        padding-left: 1.75rem;
        margin: 1.5rem 0;
      }
      .prose-bzv ol li {
        margin: 0.75rem 0;
        line-height: 1.7;
        color: #44403c;
      }
      .prose-bzv li strong {
        color: #1c1917;
      }
      .prose-bzv a { 
        color: #b45309; 
        text-decoration: underline; 
        text-underline-offset: 4px;
        font-weight: 500;
        transition: color 0.15s ease;
      }
      .prose-bzv a:hover { 
        color: #78350f; 
        text-decoration-color: #78350f;
      }
      .prose-bzv blockquote { 
        border-left: 4px solid #fbbf24; 
        background-color: #fffdf5;
        padding: 1rem 1.25rem; 
        font-style: italic; 
        color: #57534e; 
        margin: 1.5rem 0;
        border-radius: 0 0.5rem 0.5rem 0;
      }
      .prose-bzv img { 
        max-width: 100%; 
        height: auto; 
        border-radius: 0.75rem; 
        margin: 1.5rem 0; 
        box-shadow: 0 4px 6px -1px rgb(0 0 0 / 0.05), 0 2px 4px -2px rgb(0 0 0 / 0.05);
      }
      
      /* Animationen */
      @keyframes pulse-subtle {
        0%, 100% { transform: scale(1); opacity: 1; }
        50% { transform: scale(1.03); opacity: 0.95; }
      }
      .animate-pulse-subtle {
        animation: pulse-subtle 3s infinite ease-in-out;
      }

      /* === Inline-Edit-Modus === */
      body.edit-mode [data-edit-resource] {
        position: relative;
        outline: 2px dashed transparent;
        outline-offset: 4px;
        transition: outline-color 0.15s ease;
        cursor: pointer;
      }
      body.edit-mode [data-edit-resource]:hover {
        outline-color: #f59e0b;
      }
      body.edit-mode [data-edit-resource]::after {
        content: "✎ Bearbeiten";
        position: absolute;
        top: -10px;
        right: -10px;
        background: #b45309;
        color: white;
        font-size: 11px;
        font-weight: 600;
        padding: 2px 8px;
        border-radius: 9999px;
        opacity: 0;
        pointer-events: none;
        transform: translateY(-4px);
        transition: opacity 0.15s ease, transform 0.15s ease;
        z-index: 30;
        font-family: 'Plus Jakarta Sans', sans-serif;
        white-space: nowrap;
      }
      body.edit-mode [data-edit-resource]:hover::after {
        opacity: 1;
        transform: translateY(0);
      }
      /* "Neu anlegen"-Buttons: erscheinen NUR im Edit-Modus */
      .bzv-edit-new { display: none; }
      body.edit-mode .bzv-edit-new { display: inline-flex; }
      /* Modal-Overlay */
      #bzv-edit-modal { display: none; position: fixed; inset: 0; z-index: 100; background: rgba(0,0,0,0.5); }
      #bzv-edit-modal.open { display: flex; align-items: center; justify-content: center; padding: 1rem; }
      #bzv-edit-modal .frame {
        background: white; border-radius: 1rem; width: 100%; max-width: 900px;
        height: 90vh; max-height: 90vh; display: flex; flex-direction: column;
        box-shadow: 0 25px 50px -12px rgba(0,0,0,0.4);
      }
      #bzv-edit-modal header {
        display: flex; align-items: center; justify-content: space-between;
        padding: 0.75rem 1.25rem; border-bottom: 1px solid #e7e5e4;
      }
      #bzv-edit-modal iframe { border: 0; width: 100%; flex: 1; border-radius: 0 0 1rem 1rem; }
      #bzv-edit-modal .close-btn {
        background: #f5f5f4; border: 0; padding: 0.4rem 0.9rem;
        border-radius: 0.5rem; cursor: pointer; font-weight: 600; color: #44403c;
      }
      #bzv-edit-modal .close-btn:hover { background: #e7e5e4; }
      /* Toolbar (sticky am unteren Rand) */
      #bzv-edit-toolbar {
        position: fixed; bottom: 1rem; right: 1rem; z-index: 50;
        background: white; border: 2px solid #f59e0b;
        border-radius: 9999px; padding: 0.5rem 0.5rem 0.5rem 1rem;
        box-shadow: 0 10px 25px -3px rgba(0,0,0,0.15);
        display: flex; align-items: center; gap: 0.75rem;
        font-family: 'Plus Jakarta Sans', sans-serif;
      }
      #bzv-edit-toolbar .label {
        font-size: 13px; font-weight: 600; color: #78350f;
        display: flex; align-items: center; gap: 0.4rem;
      }
      #bzv-edit-toolbar .switch {
        position: relative; width: 44px; height: 24px;
        background: #d6d3d1; border-radius: 9999px;
        cursor: pointer; transition: background 0.2s;
      }
      #bzv-edit-toolbar .switch.on { background: #b45309; }
      #bzv-edit-toolbar .switch::after {
        content: ""; position: absolute; top: 2px; left: 2px;
        width: 20px; height: 20px; background: white; border-radius: 50%;
        transition: transform 0.2s;
      }
      #bzv-edit-toolbar .switch.on::after { transform: translateX(20px); }
    </style>
</head>
<body class="min-h-screen bg-stone-50 text-stone-900 font-sans flex flex-col antialiased">

<!-- Honigkauf-Banner (ganzjährig, ersetzt früheren Schwarm-Notfall-Banner) -->
<a href="/imker.php" class="relative block bg-gradient-to-r from-amber-400 via-honey-400 to-amber-400 text-stone-900 text-center py-2.5 px-4 font-bold tracking-wide shadow-sm hover:brightness-105 transition duration-150 z-50">
  <div class="max-w-5xl mx-auto flex items-center justify-center gap-2 text-sm sm:text-base">
    <span class="text-base">🍯</span>
    <span data-edit-resource="page_blocks" data-edit-id="banner.honig.text">
      <?= h(block('banner.honig.text', 'Regionalen Honig kaufen?')) ?>
    </span>
    <span class="hidden sm:inline font-medium text-stone-900/90" data-edit-resource="page_blocks" data-edit-id="banner.honig.suffix">
      <?= h(block('banner.honig.suffix', 'Direkt vom Imker aus Grünberg und Umgebung →')) ?>
    </span>
  </div>
</a>

<!--
  Header — Nav-Band als kompaktes Rechteck. Logo sitzt im linken Slot mit
  fixer Layout-Höhe (h-12/h-14), das Bild selbst ist deutlich höher und ragt
  unten sichtbar aus dem Band heraus — einfach als Rechteck, keine Wabe.
-->
<header class="sticky top-0 bg-white/90 backdrop-blur-md border-b border-stone-200/80 shadow-sm z-40 transition-all duration-300">
  <div class="max-w-5xl mx-auto px-4 py-3 flex items-center justify-between gap-4">
    <div class="relative shrink-0 h-12 md:h-14 w-40 md:w-48 lg:w-56">
      <a href="/" aria-label="Zur Startseite" class="absolute left-0 top-0 z-10 group">
        <img src="/assets/logo.svg" alt="<?= h(SITE_NAME) ?>"
             class="h-28 md:h-32 lg:h-40 w-auto group-hover:scale-[1.02] transition-transform duration-200 select-none"
             width="1836" height="1336">
      </a>
    </div>
    
    <!-- Desktop-Navigation -->
    <nav class="hidden md:flex items-center gap-1.5">
      <a href="/vorstand.php"<?= nav_active('/vorstand.php', $currentPath) ?>>Vorstand</a>
      <a href="/imker.php"<?= nav_active('/imker.php', $currentPath) ?>>Honigkauf in der Nähe</a>
      <a href="/termine.php"<?= nav_active('/termine.php', $currentPath) ?>>Termine</a>
      <a href="/aktuelles.php"<?= nav_active('/aktuelles.php', $currentPath) ?>>Aktuelles</a>
      <a href="/infos.php"<?= nav_active('/infos.php', $currentPath) ?>>Infos für Imker</a>
      <a href="/schwarm.php" class="ml-2 bg-amber-600 hover:bg-amber-700 text-white font-semibold px-4 py-2 rounded-lg text-sm transition-all shadow-sm hover:shadow shadow-amber-600/10">Schwarm melden</a>
    </nav>
    
    <!-- Mobile-Navigation via HTML5 Details -->
    <details class="md:hidden relative group">
      <summary class="list-none cursor-pointer p-2 rounded-lg hover:bg-stone-100 transition-colors" aria-label="Menü">
        <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 text-stone-700" fill="none" viewBox="0 0 24 24" stroke="currentColor">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16m-7 6h7" />
        </svg>
      </summary>
      <div class="absolute right-0 top-full mt-2 bg-white/95 backdrop-blur-md border border-stone-200 rounded-xl shadow-xl py-2 w-64 z-50 transform origin-top-right transition-all animate-in fade-in slide-in-from-top-2">
        <a href="/vorstand.php" class="flex px-4 py-2.5 hover:bg-honey-50 font-medium text-stone-700 hover:text-honey-900 transition-colors">Vorstand</a>
        <a href="/imker.php" class="flex px-4 py-2.5 hover:bg-honey-50 font-medium text-stone-700 hover:text-honey-900 transition-colors">Honigkauf in der Nähe</a>
        <a href="/termine.php" class="flex px-4 py-2.5 hover:bg-honey-50 font-medium text-stone-700 hover:text-honey-900 transition-colors">Termine</a>
        <a href="/aktuelles.php" class="flex px-4 py-2.5 hover:bg-honey-50 font-medium text-stone-700 hover:text-honey-900 transition-colors">Aktuelles</a>
        <a href="/infos.php" class="flex px-4 py-2.5 hover:bg-honey-50 font-medium text-stone-700 hover:text-honey-900 transition-colors">Infos für Imker</a>
        <a href="/schwarm.php" class="flex items-center gap-2 px-4 py-2.5 hover:bg-amber-500 font-semibold text-amber-800 hover:text-white transition-colors border-t border-b border-stone-100 mt-1"><img src="/assets/bee.svg" alt="" class="w-4 h-4">Schwarm melden</a>
        <a href="/mitglied-werden.php" class="flex px-4 py-2.5 hover:bg-honey-50 font-medium text-stone-700 hover:text-honey-900 transition-colors">Mitglied werden</a>
        <a href="/mitglieder/" class="flex px-4 py-2.5 hover:bg-stone-50 font-medium text-stone-500 hover:text-stone-800 transition-colors border-t border-stone-100">🔒 Mitgliederbereich</a>
        <a href="/admin/login.php" class="flex px-4 py-2.5 hover:bg-stone-50 font-medium text-stone-500 hover:text-stone-800 transition-colors">⚙️ Verwaltung</a>
      </div>
    </details>
  </div>
</header>

<main class="flex-1 pt-14 md:pt-16 lg:pt-24">
<?php
        // Flash-Messages anzeigen
        foreach (['success' => 'bg-emerald-50 border border-emerald-200 text-emerald-900 shadow-sm', 'error' => 'bg-red-50 border border-red-200 text-red-900 shadow-sm'] as $type => $cls) {
            foreach (flash($type) as $msg) {
                echo '<div class="max-w-5xl mx-auto mt-6 px-4"><div class="px-5 py-3.5 rounded-xl flex items-center gap-3 ' . $cls . '"><span class="text-lg">' . ($type === 'success' ? '✅' : '⚠️') . '</span><p class="font-medium text-sm">' . h($msg) . '</p></div></div>';
            }
        }
    }

    public static function footer(): void
    {
        require_once __DIR__ . '/../config/db.php';
        $isAdmin = (Auth::user()['role'] ?? '') === 'admin';
        ?>
</main>

<?php if ($isAdmin): ?>
<!-- Inline-Edit-Toolbar (nur für eingeloggte Admins) -->
<div id="bzv-edit-toolbar" role="region" aria-label="Bearbeitungs-Modus">
  <span class="label">✎ Bearbeiten</span>
  <div class="switch" id="bzv-edit-switch" role="switch" aria-checked="false" tabindex="0"></div>
</div>

<!-- Modal -->
<div id="bzv-edit-modal" role="dialog" aria-modal="true" aria-label="Editor">
  <div class="frame">
    <header>
      <strong style="font-family:'Plus Jakarta Sans',sans-serif;color:#78350f;">Bearbeiten</strong>
      <button type="button" class="close-btn" id="bzv-edit-close">Schließen ✕</button>
    </header>
    <iframe id="bzv-edit-iframe" name="bzv-edit-iframe" src="about:blank" sandbox="allow-same-origin allow-scripts allow-forms"></iframe>
  </div>
</div>

<script>
(function() {
  const body   = document.body;
  const swEl   = document.getElementById('bzv-edit-switch');
  const modal  = document.getElementById('bzv-edit-modal');
  const iframe = document.getElementById('bzv-edit-iframe');
  const closeBtn = document.getElementById('bzv-edit-close');

  function setEditMode(on) {
    body.classList.toggle('edit-mode', on);
    swEl.classList.toggle('on', on);
    swEl.setAttribute('aria-checked', on ? 'true' : 'false');
    localStorage.setItem('bzv-edit-mode', on ? '1' : '0');
  }
  setEditMode(localStorage.getItem('bzv-edit-mode') === '1');
  swEl.addEventListener('click', () => setEditMode(!body.classList.contains('edit-mode')));
  swEl.addEventListener('keydown', (e) => {
    if (e.key === ' ' || e.key === 'Enter') { e.preventDefault(); swEl.click(); }
  });

  // Resource → Admin-URL mapping
  const RESOURCES = {
    termine:     { url: '/admin/termine.php',     action: 'edit',  newUrl: '/admin/termine.php?action=new' },
    news:        { url: '/admin/news.php',        action: 'edit',  newUrl: '/admin/news.php?action=new' },
    vorstand:    { url: '/admin/vorstand.php',    action: 'edit',  newUrl: '/admin/vorstand.php?action=new' },
    imker:       { url: '/admin/imker.php',       action: 'edit',  newUrl: '/admin/imker.php?action=new' },
    infos:       { url: '/admin/infos.php',       action: 'edit',  newUrl: '/admin/infos.php?action=new' },
    links:       { url: '/admin/links.php',       action: 'edit',  newUrl: '/admin/links.php?action=new' },
    page_blocks: { url: '/admin/page_blocks.php', action: 'edit',  param: 'slug' },
  };

  function openModal(resource, id) {
    const cfg = RESOURCES[resource];
    if (!cfg) return;
    let src;
    if (id === '__new__') {
      src = cfg.newUrl + '&minimal=1';
    } else {
      const idParam = cfg.param || 'id';
      src = `${cfg.url}?action=${cfg.action}&${idParam}=${encodeURIComponent(id)}&minimal=1`;
    }
    iframe.src = src;
    modal.classList.add('open');
    document.documentElement.style.overflow = 'hidden';
  }
  function closeModal() {
    modal.classList.remove('open');
    iframe.src = 'about:blank';
    document.documentElement.style.overflow = '';
  }
  closeBtn.addEventListener('click', closeModal);
  modal.addEventListener('click', (e) => { if (e.target === modal) closeModal(); });
  document.addEventListener('keydown', (e) => { if (e.key === 'Escape' && modal.classList.contains('open')) closeModal(); });

  // Klick auf editierbare Bereiche
  document.addEventListener('click', (e) => {
    if (!body.classList.contains('edit-mode')) return;
    // "Neu anlegen"-Buttons
    const newBtn = e.target.closest('.bzv-edit-new');
    if (newBtn) {
      e.preventDefault();
      openModal(newBtn.dataset.editResource, '__new__');
      return;
    }
    // Bearbeiten
    const el = e.target.closest('[data-edit-resource]');
    if (!el) return;
    // Wenn der Klick auf einen Link innerhalb des editable war: Edit gewinnt im Edit-Mode
    e.preventDefault();
    e.stopPropagation();
    openModal(el.dataset.editResource, el.dataset.editId);
  }, true);

  // Save-Signal aus dem Iframe — Reload nur EINMAL, auch wenn mehrere Messages kommen
  let saveHandled = false;
  window.addEventListener('message', (e) => {
    if (e.origin !== window.location.origin) return;
    if (e.data && e.data.type === 'bzv-edit-saved' && !saveHandled) {
      saveHandled = true;
      // iframe zuerst auf about:blank → verhindert weitere Loads/Messages
      iframe.src = 'about:blank';
      closeModal();
      window.location.reload();
    }
  });
})();
</script>
<?php endif; ?>
<?php /* original-footer wird ab hier weitergeführt */ ?>

<footer class="mt-20 bg-stone-900 border-t border-stone-800 text-stone-400 text-sm">
  <div class="max-w-5xl mx-auto px-6 py-12 grid sm:grid-cols-3 gap-10">
    <div class="space-y-4">
      <div class="flex items-center gap-3">
        <img src="/assets/favicon.png" alt="" class="w-10 h-10 object-contain shrink-0" width="256" height="256">
        <span class="font-display font-bold text-white text-lg tracking-wide"><?= h(SITE_NAME) ?></span>
      </div>
      <p class="text-stone-400 leading-relaxed max-w-xs">
        Wir engagieren uns für das Wohl der Bienen, fördern den Naturschutz und stehen Imkern sowie Naturfreunden mit Rat und Tat zur Seite.
      </p>
    </div>
    
    <div>
      <div class="font-display font-semibold text-white text-base tracking-wide mb-4" data-edit-resource="page_blocks" data-edit-id="footer.quicklinks.title">
        <?= h(block('footer.quicklinks.title', 'Schnellzugriff')) ?>
      </div>
      <ul class="space-y-2.5">
        <li><a href="/schwarm.php" class="hover:text-amber-400 transition-colors flex items-center gap-2"><img src="/assets/bee.svg" alt="" class="w-4 h-4">Schwarm melden</a></li>
        <li><a href="/mitglied-werden.php" class="hover:text-amber-400 transition-colors flex items-center gap-2">🤝 Mitglied werden</a></li>
        <li><a href="<?= h(webcal_url('/termine.ics')) ?>" class="hover:text-amber-400 transition-colors flex items-center gap-2">📅 iCal Kalender-Abo</a></li>
        <li><a href="/mitglieder/" class="hover:text-amber-400 transition-colors flex items-center gap-2 border-t border-stone-800/80 pt-2.5 mt-2">🔒 Mitgliederbereich</a></li>
        <li><a href="/admin/login.php" class="hover:text-amber-400 transition-colors flex items-center gap-2">⚙️ Verwaltung</a></li>
      </ul>
    </div>
    
    <div class="space-y-4">
      <div class="font-display font-semibold text-white text-base tracking-wide" data-edit-resource="page_blocks" data-edit-id="footer.kontakt.title">
        <?= h(block('footer.kontakt.title', 'Kontakt zum Vorstand')) ?>
      </div>
      <p class="text-stone-400 leading-relaxed" data-edit-resource="page_blocks" data-edit-id="footer.kontakt.body">
        <?= h(block('footer.kontakt.body', 'Fragen zur Imkerei, Mitgliedschaft oder unserem Verein? Wir freuen uns auf Ihre Nachricht.')) ?>
      </p>
      <a href="/vorstand.php" class="inline-flex items-center gap-2 text-amber-400 hover:text-amber-300 font-bold transition-colors">
        → Zum Vorstand
      </a>
    </div>
  </div>
  
  <div class="border-t border-stone-800/60 bg-stone-950/40">
    <div class="max-w-5xl mx-auto px-6 py-6 flex flex-col sm:flex-row items-center justify-between gap-4 text-xs">
      <span class="text-stone-500">© <?= date('Y') ?> <?= h(SITE_NAME) ?>. Alle Rechte vorbehalten.</span>
      <span class="space-x-4">
        <a href="/impressum.php" class="hover:text-white transition-colors">Impressum</a>
        <a href="/datenschutz.php" class="hover:text-white transition-colors">Datenschutz</a>
        <a href="/bildnachweis.php" class="hover:text-white transition-colors">Bildnachweis</a>
      </span>
    </div>
  </div>
</footer>
</body>
</html>
<?php
    }
}
