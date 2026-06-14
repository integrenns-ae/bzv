<?php
require_once __DIR__ . '/../library/_init.php';

$pdo = Database::pdo();
$rows = $pdo->query(
    "SELECT id, name, street, postal_code, city, lat, lng, phone, email,
            sells_honey, swarm_helper, description
       FROM imker
      WHERE is_published = 1
        AND consent_given = 1
        AND lat IS NOT NULL
        AND lng IS NOT NULL
      ORDER BY sort_order ASC, id ASC"
)->fetchAll();

// JSON-Payload für die Karte
$mapData = array_map(fn($r) => [
    'id'    => (int)$r['id'],
    'name'  => $r['name'],
    'addr'  => trim(($r['street'] ?? '') . ', ' . trim(($r['postal_code'] ?? '') . ' ' . ($r['city'] ?? '')), ', '),
    'lat'   => (float)$r['lat'],
    'lng'   => (float)$r['lng'],
    'phone' => $r['phone'],
    'email' => $r['email'],
    'honey' => (bool)$r['sells_honey'],
    'swarm' => (bool)$r['swarm_helper'],
    'desc'  => clean_html($r['description'] ?? ''),
], $rows);

Templates::header('Honigkauf in Ihrer Nähe', '/imker.php');
?>

<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css"
      integrity="sha256-p4NxAoJBhIIN+hmNHrzRCf9tD/miZyoHS5obTRR9BMY=" crossorigin="">
<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"
        integrity="sha256-20nQCchB9co0qIjJZRGuk2/Z9VM+kNiyxNV1lvTlZBo=" crossorigin=""></script>

<section class="max-w-5xl mx-auto px-4 py-12 md:py-16">
  <div class="border-b border-stone-200/60 pb-6 mb-8">
    <div class="text-xs uppercase tracking-widest text-honey-700 font-extrabold mb-1" data-edit-resource="page_blocks" data-edit-id="imker.eyebrow">
      <?= h(block('imker.eyebrow', 'Bienenfreunde in der Region')) ?>
    </div>
    <h1 class="text-3xl md:text-4xl font-display font-extrabold text-stone-900 tracking-tight" data-edit-resource="page_blocks" data-edit-id="imker.title">
      <?= h(block('imker.title', 'Imker auf der Karte')) ?>
    </h1>
    <p class="text-stone-500 mt-2 text-base max-w-xl" data-edit-resource="page_blocks" data-edit-id="imker.subtitle">
      <?= h(block('imker.subtitle', '')) ?>
    </p>
  </div>

  <!-- Humorvolle Preis-Erklärung -->
  <div class="bg-honey-50/60 border border-honey-200/60 rounded-2xl p-5 md:p-6 mb-8 flex gap-4 items-start">
    <div class="text-3xl shrink-0">🍯</div>
    <div class="prose-bzv text-stone-700 text-sm md:text-base [&>p]:my-2" data-edit-resource="page_blocks" data-edit-id="imker.honig_preis">
      <?= block_html('imker.honig_preis',
          '<p><strong>Was kostet ein Glas?</strong> Aktuell liegt der Preis für 500 g Vereinshonig bei unseren '
        . 'Imkerinnen und Imkern <strong>zwischen 6 und 7 €</strong>. Das mag erst einmal viel klingen — bedenken '
        . 'Sie aber: für 500 g Honig muss eine Biene rund 40.000-mal ausfliegen, dabei legt das ganze Volk zusammen '
        . 'eine Strecke von etwa 120.000 km zurück. Das ist 3-mal um die Erde. Pro Glas. Ohne Trinkgeld.</p>'
        . '<p>Den genauen Preis und die verfügbaren Sorten erfragen Sie am besten direkt bei dem Imker Ihrer Wahl.</p>') ?>
    </div>
  </div>

  <?php if (!$mapData): ?>
    <div class="bg-stone-50 border border-stone-200/60 rounded-2xl p-8 text-center" data-edit-resource="page_blocks" data-edit-id="imker.empty">
      <img src="/assets/bee.svg" alt="" class="w-12 h-12 mx-auto mb-3 opacity-60">
      <p class="text-stone-600"><?= h(block('imker.empty', 'Aktuell sind noch keine Imker auf der Karte eingetragen.')) ?></p>
    </div>
  <?php else: ?>
    <div id="imker-map" class="w-full h-[500px] rounded-2xl border border-stone-200/60 shadow-sm overflow-hidden"
         style="background: #f5f5f4;"></div>

    <h2 class="text-xl font-display font-extrabold text-stone-900 mt-10 mb-4">Alle Imker auf einen Blick</h2>
    <div class="grid sm:grid-cols-2 gap-4">
      <?php foreach ($rows as $r): ?>
        <article class="bg-white border border-stone-200/60 rounded-2xl p-5 shadow-sm" data-edit-resource="imker" data-edit-id="<?= (int)$r['id'] ?>">
          <h3 class="font-display font-extrabold text-stone-900 text-lg"><?= h($r['name']) ?></h3>
          <?php if ($r['street'] || $r['city']): ?>
            <p class="text-sm text-stone-600 mt-1">📍 <?= h(trim(($r['street'] ?? '') . ', ' . trim(($r['postal_code'] ?? '') . ' ' . ($r['city'] ?? '')), ', ')) ?></p>
          <?php endif; ?>
          <?php if ($r['phone']): ?>
            <p class="text-sm mt-1"><a href="tel:<?= h(preg_replace('/[^0-9+]/', '', $r['phone'])) ?>" class="text-honey-700 hover:underline">📞 <?= h($r['phone']) ?></a></p>
          <?php endif; ?>
          <?php if ($r['email']): ?>
            <p class="text-sm mt-1"><a href="mailto:<?= h($r['email']) ?>" class="text-honey-700 hover:underline">✉️ <?= h($r['email']) ?></a></p>
          <?php endif; ?>
          <?php if ($r['sells_honey'] || $r['swarm_helper']): ?>
            <div class="flex flex-wrap gap-2 mt-3 text-xs">
              <?php if ($r['sells_honey']): ?><span class="bg-amber-100 text-amber-900 px-2.5 py-1 rounded-full font-semibold">🍯 Honig direkt vom Imker</span><?php endif; ?>
              <?php if ($r['swarm_helper']): ?><span class="bg-amber-100 text-amber-900 px-2.5 py-1 rounded-full font-semibold">🐝 Hilft bei Schwarmfängen</span><?php endif; ?>
            </div>
          <?php endif; ?>
          <?php if ($r['description']): ?>
            <div class="prose-bzv text-sm mt-3 text-stone-600">
              <?= clean_html($r['description']) ?>
            </div>
          <?php endif; ?>
        </article>
      <?php endforeach; ?>
    </div>

    <script>
      const imkerData = <?= json_encode($mapData, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>;

      (function() {
        const map = L.map('imker-map', { scrollWheelZoom: false }).setView([50.5926, 8.9543], 11);

        L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
          maxZoom: 19,
          attribution: '© <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a> Mitwirkende',
        }).addTo(map);

        const beeIcon = L.divIcon({
          html: '<img src="/assets/bee.svg" alt="Imker" style="width:32px;height:32px;filter:drop-shadow(0 2px 3px rgba(0,0,0,0.25))">',
          className: '',
          iconSize: [32, 32],
          iconAnchor: [16, 28],
          popupAnchor: [0, -22],
        });

        const bounds = [];
        imkerData.forEach(d => {
          const popup = `
            <div style="min-width:200px;font-family:'Plus Jakarta Sans',sans-serif">
              <div style="font-weight:700;font-size:15px;color:#0f172a">${escapeHtml(d.name)}</div>
              ${d.addr ? `<div style="font-size:12px;color:#57534e;margin-top:4px">📍 ${escapeHtml(d.addr)}</div>` : ''}
              ${d.phone ? `<div style="font-size:13px;margin-top:4px"><a href="tel:${escapeAttr(d.phone)}" style="color:#b45309">📞 ${escapeHtml(d.phone)}</a></div>` : ''}
              ${d.email ? `<div style="font-size:13px;margin-top:2px"><a href="mailto:${escapeAttr(d.email)}" style="color:#b45309">✉️ ${escapeHtml(d.email)}</a></div>` : ''}
              ${(d.honey || d.swarm) ? `<div style="margin-top:8px;display:flex;gap:4px;flex-wrap:wrap">
                  ${d.honey ? '<span style="background:#fef3c7;color:#78350f;padding:2px 8px;border-radius:9999px;font-size:11px;font-weight:600">🍯 Honig</span>' : ''}
                  ${d.swarm ? '<span style="background:#fef3c7;color:#78350f;padding:2px 8px;border-radius:9999px;font-size:11px;font-weight:600">🐝 Schwarmhilfe</span>' : ''}
                </div>` : ''}
              ${d.desc ? `<div style="font-size:13px;margin-top:8px;color:#44403c">${d.desc}</div>` : ''}
            </div>`;
          L.marker([d.lat, d.lng], { icon: beeIcon }).addTo(map).bindPopup(popup);
          bounds.push([d.lat, d.lng]);
        });

        if (bounds.length > 1) {
          map.fitBounds(bounds, { padding: [40, 40], maxZoom: 14 });
        } else if (bounds.length === 1) {
          map.setView(bounds[0], 13);
        }

        function escapeHtml(s) {
          return String(s).replace(/[&<>"']/g, c => ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[c]));
        }
        function escapeAttr(s) {
          return String(s).replace(/[^a-zA-Z0-9+@._-]/g, '');
        }
      })();
    </script>
  <?php endif; ?>
</section>

<?php Templates::footer(); ?>
