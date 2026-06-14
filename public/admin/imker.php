<?php
$pageTitle = 'Imker';
$current   = 'imker.php';
include __DIR__ . '/_layout.php';

$pdo    = Database::pdo();
$action = $_GET['action'] ?? 'list';
$id     = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    $post = $_POST;
    if (($post['_action'] ?? '') === 'delete') {
        $pdo->prepare("DELETE FROM imker WHERE id = ?")->execute([(int)$post['id']]);
        flash('success', 'Imker-Eintrag gelöscht.');
        redirect_after_save('/admin/imker.php');
    }
    $id     = (int)($post['id'] ?? 0);
    $name   = trim((string)($post['name']        ?? ''));
    $street = trim((string)($post['street']      ?? ''));
    $plz    = trim((string)($post['postal_code'] ?? ''));
    $city   = trim((string)($post['city']        ?? ''));
    $lat    = trim((string)($post['lat']         ?? ''));
    $lng    = trim((string)($post['lng']         ?? ''));
    $phone  = trim((string)($post['phone']       ?? ''));
    $email  = trim((string)($post['email']       ?? ''));
    $sells  = !empty($post['sells_honey'])   ? 1 : 0;
    $swarm  = !empty($post['swarm_helper'])  ? 1 : 0;
    $desc   = trim((string)($post['description'] ?? ''));
    $cons   = !empty($post['consent_given']) ? 1 : 0;
    $pub    = !empty($post['is_published'])  ? 1 : 0;
    $sort   = (int)($post['sort_order'] ?? 0);

    if ($name === '') {
        flash('error', 'Name ist Pflicht.');
        header('Location: /admin/imker.php?action=edit' . ($id ? '&id=' . $id : ''));
        exit;
    }

    $latVal = $lat !== '' ? (float)str_replace(',', '.', $lat) : null;
    $lngVal = $lng !== '' ? (float)str_replace(',', '.', $lng) : null;

    if ($id) {
        $pdo->prepare("UPDATE imker SET name=?, street=?, postal_code=?, city=?, lat=?, lng=?, phone=?, email=?, sells_honey=?, swarm_helper=?, description=?, consent_given=?, is_published=?, sort_order=? WHERE id=?")
            ->execute([$name, $street ?: null, $plz ?: null, $city ?: null, $latVal, $lngVal, $phone ?: null, $email ?: null, $sells, $swarm, $desc ?: null, $cons, $pub, $sort, $id]);
        flash('success', 'Imker aktualisiert.');
    } else {
        $pdo->prepare("INSERT INTO imker (name, street, postal_code, city, lat, lng, phone, email, sells_honey, swarm_helper, description, consent_given, is_published, sort_order) VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?)")
            ->execute([$name, $street ?: null, $plz ?: null, $city ?: null, $latVal, $lngVal, $phone ?: null, $email ?: null, $sells, $swarm, $desc ?: null, $cons, $pub, $sort]);
        flash('success', 'Imker angelegt.');
    }
    redirect_after_save('/admin/imker.php');
}

if ($action === 'edit' || $action === 'new') {
    $row = ['id'=>0,'name'=>'','street'=>'','postal_code'=>'','city'=>'Grünberg','lat'=>'','lng'=>'','phone'=>'','email'=>'','sells_honey'=>0,'swarm_helper'=>0,'description'=>'','consent_given'=>0,'is_published'=>1,'sort_order'=>0];
    if ($id) {
        $st = $pdo->prepare("SELECT * FROM imker WHERE id = ?"); $st->execute([$id]);
        $row = $st->fetch() ?: $row;
    }
    ?>
    <h1 class="text-2xl font-bold mb-4"><?= $row['id'] ? 'Imker bearbeiten' : 'Neuen Imker anlegen' ?></h1>

    <form method="post" class="bg-white border border-stone-200 rounded p-5 space-y-4 max-w-2xl">
      <?= csrf_field() ?>
      <input type="hidden" name="id" value="<?= (int)$row['id'] ?>">

      <label class="block">
        <span class="text-sm font-semibold">Name *</span>
        <input type="text" name="name" required maxlength="128" value="<?= h($row['name']) ?>"
               class="w-full border border-stone-300 rounded px-3 py-2 mt-1">
      </label>

      <fieldset class="border border-stone-200 rounded p-4">
        <legend class="text-sm font-semibold px-2">Adresse</legend>
        <label class="block">
          <span class="text-xs text-stone-600">Straße + Hausnummer</span>
          <input type="text" id="f-street" name="street" maxlength="255" value="<?= h($row['street'] ?? '') ?>"
                 class="w-full border border-stone-300 rounded px-3 py-2 mt-1">
        </label>
        <div class="grid sm:grid-cols-2 gap-3 mt-3">
          <label class="block">
            <span class="text-xs text-stone-600">PLZ</span>
            <input type="text" id="f-plz" name="postal_code" maxlength="10" value="<?= h($row['postal_code'] ?? '') ?>"
                   class="w-full border border-stone-300 rounded px-3 py-2 mt-1">
          </label>
          <label class="block">
            <span class="text-xs text-stone-600">Ort</span>
            <input type="text" id="f-city" name="city" maxlength="128" value="<?= h($row['city'] ?? '') ?>"
                   class="w-full border border-stone-300 rounded px-3 py-2 mt-1">
          </label>
        </div>
        <div class="grid sm:grid-cols-[1fr_1fr_auto] gap-3 mt-3 items-end">
          <label class="block">
            <span class="text-xs text-stone-600">Breitengrad (Latitude)</span>
            <input type="text" id="f-lat" name="lat" value="<?= h($row['lat'] ?? '') ?>" placeholder="50.5926"
                   class="w-full border border-stone-300 rounded px-3 py-2 mt-1 font-mono text-sm">
          </label>
          <label class="block">
            <span class="text-xs text-stone-600">Längengrad (Longitude)</span>
            <input type="text" id="f-lng" name="lng" value="<?= h($row['lng'] ?? '') ?>" placeholder="8.9543"
                   class="w-full border border-stone-300 rounded px-3 py-2 mt-1 font-mono text-sm">
          </label>
          <button type="button" id="btn-geocode"
                  class="bg-amber-700 hover:bg-amber-800 text-white font-semibold px-3 py-2 rounded text-sm whitespace-nowrap">
            📍 Aus Adresse holen
          </button>
        </div>
        <p id="geocode-status" class="text-xs text-stone-500 mt-2">
          Die Adresse wird an OpenStreetMap (Nominatim) übermittelt, um Koordinaten zu ermitteln.
        </p>
      </fieldset>

      <div class="grid sm:grid-cols-2 gap-3">
        <label class="block">
          <span class="text-sm font-semibold">Telefon</span>
          <input type="tel" name="phone" maxlength="64" value="<?= h($row['phone'] ?? '') ?>"
                 class="w-full border border-stone-300 rounded px-3 py-2 mt-1">
        </label>
        <label class="block">
          <span class="text-sm font-semibold">E-Mail</span>
          <input type="email" name="email" maxlength="128" value="<?= h($row['email'] ?? '') ?>"
                 class="w-full border border-stone-300 rounded px-3 py-2 mt-1">
        </label>
      </div>

      <div class="grid sm:grid-cols-2 gap-3">
        <label class="flex items-center gap-2">
          <input type="checkbox" name="sells_honey" value="1" <?= !empty($row['sells_honey']) ? 'checked' : '' ?>>
          <span class="text-sm">🍯 Verkauft Honig direkt</span>
        </label>
        <label class="flex items-center gap-2">
          <input type="checkbox" name="swarm_helper" value="1" <?= !empty($row['swarm_helper']) ? 'checked' : '' ?>>
          <span class="text-sm flex items-center gap-1">
            <img src="/assets/favicon.png" alt="" class="w-4 h-4 object-contain inline-block" width="256" height="256">
            Unterstützt bei Schwarmfängen
          </span>
        </label>
      </div>

      <div>
        <label for="imker-desc" class="text-sm font-semibold">Kurzbeschreibung</label>
        <input id="imker-desc-input" type="hidden" name="description" value="<?= h($row['description'] ?? '') ?>">
        <trix-editor input="imker-desc-input" id="imker-desc" class="mt-1"></trix-editor>
      </div>

      <fieldset class="border border-amber-300 bg-amber-50 rounded p-4">
        <legend class="text-sm font-semibold px-2">Veröffentlichung</legend>
        <label class="flex items-start gap-2">
          <input type="checkbox" name="consent_given" value="1" <?= !empty($row['consent_given']) ? 'checked' : '' ?> class="mt-1">
          <span class="text-sm">
            <strong>Einwilligung zur öffentlichen Anzeige liegt vor.</strong><br>
            <span class="text-xs text-stone-600">
              Nur wenn diese Checkbox gesetzt ist, erscheint der Imker auf der Karte.
              Stellen Sie sicher, dass eine schriftliche oder mündliche Einwilligung des Imkers vorliegt.
            </span>
          </span>
        </label>
        <label class="flex items-center gap-2 mt-3">
          <input type="checkbox" name="is_published" value="1" <?= !empty($row['is_published']) ? 'checked' : '' ?>>
          <span class="text-sm">sichtbar (techn. Schalter — auch ohne Einwilligung erstmal anlegen können)</span>
        </label>
        <label class="block mt-3">
          <span class="text-xs text-stone-600">Reihenfolge</span>
          <input type="number" name="sort_order" value="<?= (int)$row['sort_order'] ?>"
                 class="w-32 border border-stone-300 rounded px-3 py-2 mt-1">
        </label>
      </fieldset>

      <div class="flex gap-2">
        <button type="submit" class="bg-amber-700 hover:bg-amber-800 text-white font-semibold px-4 py-2 rounded">Speichern</button>
        <a href="/admin/imker.php" class="px-4 py-2 rounded border border-stone-300 hover:bg-stone-50">Abbrechen</a>
      </div>
    </form>

    <script>
    (function() {
      const csrf = <?= json_encode(csrf_token()) ?>;
      const btn = document.getElementById('btn-geocode');
      btn.addEventListener('click', async () => {
        const street = document.getElementById('f-street').value.trim();
        const plz    = document.getElementById('f-plz').value.trim();
        const city   = document.getElementById('f-city').value.trim();
        const status = document.getElementById('geocode-status');
        status.textContent = 'Suche…';
        status.className = 'text-xs text-stone-500 mt-2';
        const fd = new FormData();
        fd.set('_csrf', csrf);
        fd.set('street', street);
        fd.set('postal_code', plz);
        fd.set('city', city);
        try {
          const r = await fetch('/admin/api/geocode.php', { method: 'POST', body: fd, headers: { 'X-Csrf': csrf } });
          const j = await r.json();
          if (j.error) { status.textContent = '✗ ' + j.error; status.className = 'text-xs text-red-700 mt-2'; return; }
          document.getElementById('f-lat').value = j.lat.toFixed(7);
          document.getElementById('f-lng').value = j.lng.toFixed(7);
          status.textContent = '✓ Gefunden: ' + j.display_name;
          status.className = 'text-xs text-emerald-700 mt-2';
        } catch (e) {
          status.textContent = '✗ Netzwerkfehler.';
          status.className = 'text-xs text-red-700 mt-2';
        }
      });
    })();
    </script>
    <?php
} else {
    $rows = $pdo->query("SELECT id, name, postal_code, city, sells_honey, swarm_helper, consent_given, is_published, lat, lng FROM imker ORDER BY sort_order ASC, id ASC")->fetchAll();
    ?>
    <div class="flex justify-between items-baseline mb-4">
      <h1 class="text-2xl font-bold">Imker</h1>
      <a href="/admin/imker.php?action=new" class="bg-amber-700 hover:bg-amber-800 text-white px-3 py-2 rounded text-sm">+ Neuer Imker</a>
    </div>
    <p class="text-sm text-stone-600 mb-3">
      Eintragung ist freiwillig. Imker erscheinen nur auf der Karte, wenn Einwilligung gegeben wurde
      <strong>und</strong> Koordinaten vorliegen <strong>und</strong> der techn. Schalter „sichtbar" an ist.
    </p>
    <div class="bg-white border border-stone-200 rounded overflow-hidden">
      <table class="w-full text-sm">
        <thead class="bg-stone-100 text-left">
          <tr><th class="px-3 py-2">Name</th><th>Ort</th><th>Eigenschaften</th><th>Status</th><th></th></tr>
        </thead>
        <tbody>
          <?php foreach ($rows as $r):
            $missing = [];
            if (!$r['consent_given']) $missing[] = 'Einwilligung fehlt';
            if (!$r['lat'] || !$r['lng']) $missing[] = 'keine Koordinaten';
            if (!$r['is_published']) $missing[] = 'ausgeblendet';
          ?>
            <tr class="border-t">
              <td class="px-3 py-2"><?= h($r['name']) ?></td>
              <td class="text-stone-600"><?= h(trim(($r['postal_code'] ?? '') . ' ' . ($r['city'] ?? ''))) ?></td>
              <td class="text-xs">
                <?php if ($r['sells_honey']):  ?><span class="bg-amber-100 text-amber-900 px-2 py-0.5 rounded mr-1">🍯 Honig</span><?php endif; ?>
                <?php if ($r['swarm_helper']): ?><span class="bg-amber-100 text-amber-900 px-2 py-0.5 rounded inline-flex items-center gap-1"><img src="/assets/favicon.png" alt="" class="w-3 h-3 object-contain" width="256" height="256">Schwarm</span><?php endif; ?>
              </td>
              <td>
                <?php if (!$missing): ?>
                  <span class="text-emerald-700 font-semibold">auf der Karte</span>
                <?php else: ?>
                  <span class="text-stone-500 text-xs"><?= h(implode(' · ', $missing)) ?></span>
                <?php endif; ?>
              </td>
              <td class="text-right pr-2 whitespace-nowrap">
                <a href="/admin/imker.php?action=edit&id=<?= (int)$r['id'] ?>" class="text-amber-700 hover:underline">bearbeiten</a>
                <form method="post" class="inline" onsubmit="return confirm('Imker wirklich löschen?');">
                  <?= csrf_field() ?>
                  <input type="hidden" name="_action" value="delete">
                  <input type="hidden" name="id" value="<?= (int)$r['id'] ?>">
                  <button type="submit" class="text-red-700 hover:underline ml-3">löschen</button>
                </form>
              </td>
            </tr>
          <?php endforeach; ?>
          <?php if (!$rows): ?>
            <tr><td colspan="5" class="px-3 py-6 text-center text-stone-400">Noch keine Imker erfasst.</td></tr>
          <?php endif; ?>
        </tbody>
      </table>
    </div>
    <?php
}

include __DIR__ . '/_layout_end.php';
