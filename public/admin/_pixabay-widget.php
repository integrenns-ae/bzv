<?php
/**
 * Wiederverwendbares Widget für Pixabay-Bildsuche in CRUD-Formularen.
 *
 * Erwartete Variablen (vor include setzen):
 *   $widgetTargetHidden  z.B. 'existing_image' oder 'existing_photo'
 *   $widgetCategory      z.B. 'news', 'vorstand', 'infos' — bestimmt Unterordner unter /bilder/
 *   $widgetDefaultQuery  Vorschlag fürs Suchfeld
 *
 * Voraussetzung: das Formular hat ein <input type="hidden" name="<$widgetTargetHidden>" id="<$widgetTargetHidden>">.
 */
$widgetTargetHidden = $widgetTargetHidden ?? 'existing_image';
$widgetCategory     = $widgetCategory     ?? 'news';
$widgetDefaultQuery = $widgetDefaultQuery ?? 'imker';
$widgetId           = 'pxb-' . bin2hex(random_bytes(3));
?>
<details class="mt-3 border border-stone-200 rounded-lg bg-stone-50 group" id="<?= h($widgetId) ?>">
  <summary class="cursor-pointer select-none px-3 py-2 text-sm font-semibold text-amber-800 hover:bg-amber-50 rounded-lg flex items-center gap-2">
    🔍 Bild aus Pixabay suchen
    <span class="text-xs text-stone-500 font-normal group-open:hidden">— statt Datei hochladen</span>
  </summary>
  <div class="p-3 border-t border-stone-200 space-y-3">
    <div class="flex gap-2">
      <input type="text" placeholder="Suchbegriff: imker, biene, wabe, schwarm…"
             value="<?= h($widgetDefaultQuery) ?>" data-pxb-q
             class="flex-1 border border-stone-300 rounded px-3 py-2 text-sm">
      <button type="button" data-pxb-search
              class="bg-amber-700 hover:bg-amber-800 text-white px-3 py-2 rounded text-sm font-semibold">
        Suchen
      </button>
    </div>
    <div data-pxb-status class="text-xs text-stone-500"></div>
    <div data-pxb-grid class="grid grid-cols-3 sm:grid-cols-4 gap-2"></div>
    <div data-pxb-selected class="hidden mt-2 p-2 bg-emerald-50 border border-emerald-200 rounded text-xs text-emerald-900"></div>
  </div>
</details>

<script>
(function() {
  const root     = document.getElementById(<?= json_encode($widgetId) ?>);
  const qInput   = root.querySelector('[data-pxb-q]');
  const searchBtn= root.querySelector('[data-pxb-search]');
  const grid     = root.querySelector('[data-pxb-grid]');
  const status   = root.querySelector('[data-pxb-status]');
  const selected = root.querySelector('[data-pxb-selected]');
  const hiddenInput = document.getElementsByName(<?= json_encode($widgetTargetHidden) ?>)[0];
  const csrf     = <?= json_encode(csrf_token()) ?>;
  const category = <?= json_encode($widgetCategory) ?>;

  async function search() {
    const q = qInput.value.trim();
    if (!q) return;
    status.textContent = 'Suche…';
    grid.innerHTML = '';
    try {
      const r = await fetch('/admin/api/pixabay-search.php?q=' + encodeURIComponent(q));
      const j = await r.json();
      if (j.error) { status.textContent = '✗ ' + j.error; return; }
      if (!j.hits || !j.hits.length) { status.textContent = 'Keine Treffer.'; return; }
      status.textContent = j.hits.length + ' Treffer (von ' + j.total + ').';
      j.hits.forEach(hit => {
        const tile = document.createElement('button');
        tile.type = 'button';
        tile.className = 'group relative rounded overflow-hidden border-2 border-transparent hover:border-amber-500 focus:border-amber-500 focus:outline-none transition';
        tile.title = (hit.tags || '') + ' — © ' + (hit.user || 'Pixabay');
        tile.innerHTML = '<img src="' + hit.previewURL + '" loading="lazy" class="w-full h-20 object-cover">' +
                        '<div class="absolute inset-x-0 bottom-0 bg-black/60 text-white text-[10px] px-1 py-0.5 truncate opacity-0 group-hover:opacity-100 transition">© ' + (hit.user || 'Pixabay') + '</div>';
        tile.addEventListener('click', () => choose(hit, tile));
        grid.appendChild(tile);
      });
    } catch (e) {
      status.textContent = '✗ Netzwerk-Fehler.';
    }
  }

  async function choose(hit, tile) {
    tile.classList.add('opacity-50');
    status.textContent = 'Lade Bild herunter…';
    const fd = new FormData();
    fd.set('_csrf', csrf);
    fd.set('id', hit.id);
    fd.set('category', category);
    try {
      const r = await fetch('/admin/api/pixabay-fetch.php', { method: 'POST', body: fd, headers: { 'X-Csrf': csrf } });
      const j = await r.json();
      tile.classList.remove('opacity-50');
      if (j.error) { status.textContent = '✗ ' + j.error; return; }
      hiddenInput.value = j.path;
      selected.classList.remove('hidden');
      selected.innerHTML = '✓ Übernommen: <code class="bg-white px-1 py-0.5 rounded">' + j.path + '</code> · ' +
                           '<a href="' + j.pageURL + '" target="_blank" class="underline">© ' + (j.user || 'Pixabay') + '</a><br>' +
                           '<img src="' + j.preview + '" class="mt-2 max-h-32 rounded">';
      status.textContent = 'Übernommen. Beim Speichern wird das Bild fest mit diesem Eintrag verknüpft.';
    } catch (e) {
      tile.classList.remove('opacity-50');
      status.textContent = '✗ Fehler beim Speichern.';
    }
  }

  searchBtn.addEventListener('click', search);
  qInput.addEventListener('keydown', e => { if (e.key === 'Enter') { e.preventDefault(); search(); } });
})();
</script>
