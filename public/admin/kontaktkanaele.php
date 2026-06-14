<?php
$pageTitle = 'Kontaktkanäle';
$current   = 'kontaktkanaele.php';
include __DIR__ . '/_layout.php';

$pdo = Database::pdo();

/**
 * Kuratierte Liste aller editierbaren Kontaktdaten — gruppiert.
 * Wert wird in page_blocks.body gespeichert.
 *
 * Felder pro Eintrag:
 *   slug    => Page-Block-Slug
 *   title   => Beschreibung (wird auch als title in page_blocks gespeichert)
 *   label   => UI-Beschriftung
 *   help    => kurzer Hilfetext unter dem Feld
 *   type    => 'email' | 'tel' | 'text'
 *   default => Vorgabewert bei Erst-Anlage
 */
$groups = [
    'Schwarm-Notfall' => [
        ['slug' => 'contact.schwarm.tel',  'label' => 'Telefonnummer',
         'help' => 'Wird auf Startseite, Schwarm-Seite und Banner verwendet. Beliebige Schreibweise — die tel:- und WhatsApp-Links werden automatisch ins internationale Format umgerechnet.',
         'type' => 'tel',   'title' => 'Schwarm: Telefonnummer (Notruf)',
         'default' => defined('SCHWARM_TEL') ? SCHWARM_TEL : ''],
        ['slug' => 'contact.schwarm.mail', 'label' => 'E-Mail-Empfänger',
         'help' => 'Adresse für Schwarm-Meldungen (derzeit nur als Reserve, da das Online-Formular abgeschaltet ist).',
         'type' => 'email', 'title' => 'Schwarm: E-Mail-Empfänger',
         'default' => defined('SCHWARM_MAIL_TO') ? SCHWARM_MAIL_TO : ''],
    ],
    'Vorstand / Allgemeine Anfragen' => [
        ['slug' => 'contact.vorstand.mail', 'label' => 'E-Mail Vorstand',
         'help' => 'Für allgemeine Anfragen und Beitrittsanträge. Wird auch als Default-Adresse für „Mitglied werden" verwendet.',
         'type' => 'email', 'title' => 'Vorstand: zentrale E-Mail-Adresse',
         'default' => defined('SCHWARM_MAIL_TO') ? SCHWARM_MAIL_TO : ''],
        ['slug' => 'contact.vorstand.tel',  'label' => 'Telefon Vorstand',
         'help' => 'Optional. Wird derzeit nicht direkt angezeigt — Reserve für Telefon-Buttons.',
         'type' => 'tel',   'title' => 'Vorstand: Telefonnummer (allgemein)',
         'default' => ''],
        ['slug' => 'vorstand.kontakt.email','label' => 'Anzeige-Adresse auf der Vorstand-Seite',
         'help' => 'Diese Adresse erscheint im Block „Allgemeine Anfragen" auf /vorstand.php als klickbarer Knopf.',
         'type' => 'email', 'title' => 'Vorstand-Seite: Kontakt-E-Mail-Adresse',
         'default' => defined('SCHWARM_MAIL_TO') ? SCHWARM_MAIL_TO : ''],
    ],
    'Mitgliederbereich' => [
        ['slug' => 'members.notify_email', 'label' => 'Empfänger für neue Registrierungsanfragen',
         'help' => 'Wenn jemand sich auf /mitglieder/registrieren.php anmeldet, geht die Benachrichtigung an diese Adresse.',
         'type' => 'email', 'title' => 'Mitgliederbereich: E-Mail-Empfänger für neue Registrierungsanfragen',
         'default' => defined('SCHWARM_MAIL_TO') ? SCHWARM_MAIL_TO : ''],
    ],
    'Sammelbestellungen' => [
        ['slug' => 'bestellung.futter.email',    'label' => 'Futter — E-Mail',
         'help' => 'Wohin gehen Futter-Bestellungen?',
         'type' => 'email', 'title' => 'Futter: Bestellungen gehen an (E-Mail)',
         'default' => ''],
        ['slug' => 'bestellung.futter.telefon',  'label' => 'Futter — Telefon',
         'help' => 'Optional. Wird nur angezeigt, wenn „Bestellung per Telefon" für Futter aktiviert ist.',
         'type' => 'tel',   'title' => 'Futter: Telefonnummer für Bestellungen',
         'default' => ''],
        ['slug' => 'bestellung.behandlung.email','label' => 'Behandlung — E-Mail',
         'help' => 'Empfänger für Behandlungs-/Völkermeldung.',
         'type' => 'email', 'title' => 'Behandlung: Bestellungen gehen an (E-Mail)',
         'default' => ''],
        ['slug' => 'bestellung.behandlung.telefon','label' => 'Behandlung — Telefon',
         'help' => 'Optional. Wird nur angezeigt, wenn „Bestellung per Telefon" für Behandlung aktiviert ist.',
         'type' => 'tel',   'title' => 'Behandlung: Telefonnummer für Bestellungen',
         'default' => ''],
        ['slug' => 'bestellung.zucht.email',     'label' => 'Zucht — E-Mail',
         'help' => 'Standard: Werner Bugdahl.',
         'type' => 'email', 'title' => 'Zucht: Bestellungen gehen an (E-Mail)',
         'default' => 'wbughahl@gmx.de'],
        ['slug' => 'bestellung.zucht.telefon',   'label' => 'Zucht — Telefon',
         'help' => 'Standard: Werner Bugdahl.',
         'type' => 'tel',   'title' => 'Zucht: Telefonnummer für Bestellungen',
         'default' => '01704028246'],
    ],
];

// Flache Liste aller Slugs für Form-Verarbeitung
$allFields = [];
foreach ($groups as $g) foreach ($g as $f) $allFields[$f['slug']] = $f;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    // PHP wandelt Punkte in input-Namen zu Underscores um → daher fields[<slug>]-Array
    $data = $_POST['fields'] ?? [];
    $upd = $pdo->prepare(
        "INSERT INTO page_blocks (slug, title, body) VALUES (?, ?, ?)
         ON DUPLICATE KEY UPDATE title=VALUES(title), body=VALUES(body)"
    );
    foreach ($allFields as $slug => $f) {
        $val = trim((string)($data[$slug] ?? ''));
        if ($f['type'] === 'email' && $val !== '' && !filter_var($val, FILTER_VALIDATE_EMAIL)) {
            flash('error', "Ungültige E-Mail bei {$f['label']}: $val");
            continue;
        }
        $upd->execute([$slug, $f['title'], $val]);
    }
    flash('success', 'Kontaktdaten gespeichert.');
    redirect_after_save('/admin/kontaktkanaele.php');
}

// Aktuelle Werte laden — direkt aus page_blocks (umgeht block()-Cache pro Request)
$values = [];
$rows = $pdo->query("SELECT slug, body FROM page_blocks")->fetchAll();
foreach ($rows as $r) $values[$r['slug']] = $r['body'];
?>

<h1 class="text-2xl font-bold mb-2">Kontaktkanäle</h1>
<p class="text-sm text-stone-500 mb-6 max-w-2xl">
  Alle Telefonnummern und E-Mail-Adressen, die auf der Webseite und in Mails verwendet werden.
  Änderungen wirken sich sofort auf alle relevanten Seiten aus.
</p>

<form method="post" class="space-y-8 max-w-3xl">
  <?= csrf_field() ?>

  <?php foreach ($groups as $gName => $fields): ?>
    <fieldset class="bg-white border border-stone-200 rounded-2xl p-5 md:p-6">
      <legend class="text-base font-bold text-amber-900 px-2">
        <?= h($gName) ?>
      </legend>
      <div class="space-y-4 mt-2">
        <?php foreach ($fields as $f):
          $val = $values[$f['slug']] ?? $f['default'];
          $icon = $f['type'] === 'tel' ? '📞' : ($f['type'] === 'email' ? '📧' : '✏️');
        ?>
          <div>
            <label class="block">
              <span class="text-sm font-semibold flex items-center gap-1.5"><?= $icon ?> <?= h($f['label']) ?></span>
              <input type="<?= h($f['type']) ?>"
                     name="fields[<?= h($f['slug']) ?>]"
                     value="<?= h($val) ?>"
                     maxlength="200"
                     <?= $f['type'] === 'tel' ? 'pattern="[0-9 +()\\/-]{4,40}"' : '' ?>
                     class="w-full border border-stone-300 rounded-lg px-3 py-2 mt-1 font-mono text-sm">
            </label>
            <?php if (!empty($f['help'])): ?>
              <p class="text-xs text-stone-500 mt-1 leading-relaxed"><?= h($f['help']) ?></p>
            <?php endif; ?>
            <p class="text-[10px] text-stone-400 mt-0.5 font-mono">Block: <?= h($f['slug']) ?></p>
          </div>
        <?php endforeach; ?>
      </div>
    </fieldset>
  <?php endforeach; ?>

  <div class="flex gap-3 sticky bottom-0 bg-stone-100 py-4 -mx-4 px-4 border-t border-stone-200">
    <button type="submit" class="bg-amber-700 hover:bg-amber-800 text-white font-semibold px-6 py-2.5 rounded">
      Alle Änderungen speichern
    </button>
    <a href="/admin/" class="px-4 py-2.5 rounded border border-stone-300 hover:bg-stone-50">Abbrechen</a>
  </div>
</form>

<?php include __DIR__ . '/_layout_end.php'; ?>
