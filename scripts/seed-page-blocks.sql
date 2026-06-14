-- Erweiterte page_blocks für die Inline-Edit-Phase II.
-- Wichtig: mit --default-character-set=utf8mb4 importieren, sonst Umlaut-Bug.
-- Idempotent via ON DUPLICATE KEY UPDATE — überschreibt vorhandene Defaults.
SET NAMES utf8mb4;
USE bzv_gruenberg;

INSERT INTO page_blocks (slug, title, body) VALUES
  -- Startseite
  ('home.hero.schwarm.title', 'Startseite: Notfall-Karten-Titel', 'Schwarm entdeckt?'),
  ('home.termine.title',  'Startseite: Termine-Sektionstitel', 'Nächste Termine'),
  ('home.termine.empty',  'Startseite: Termine leer-Hinweis', 'Aktuell sind keine kommenden Termine eingetragen.'),
  ('home.news.title',     'Startseite: Aktuelles-Sektionstitel', 'Aktuelles vom Verein'),
  ('home.news.empty',     'Startseite: News leer-Hinweis', 'Noch keine Beiträge vorhanden.'),

  -- Vorstand
  ('vorstand.eyebrow',  'Vorstand-Seite: Eyebrow', 'Ansprechpartner'),
  ('vorstand.title',    'Vorstand-Seite: Hauptüberschrift', 'Vorstand'),
  ('vorstand.subtitle', 'Vorstand-Seite: Untertitel',
    'Der Vorstand des Bienenzuchtvereins Grünberg steht Ihnen bei Fragen, Anregungen oder Wünschen gerne zur Seite.'),
  ('vorstand.empty',    'Vorstand-Seite: leer-Hinweis',
    'Aktuell sind keine Vorstandsmitglieder eingetragen.'),

  -- Termine
  ('termine.eyebrow',         'Termine-Seite: Eyebrow', 'Vereinskalender'),
  ('termine.title',           'Termine-Seite: Hauptüberschrift', 'Termine'),
  ('termine.subtitle',        'Termine-Seite: Untertitel',
    'Hier finden Sie alle aktuellen Treffen, Schulungen, Standschauen und sonstige Termine unseres Vereins.'),
  ('termine.upcoming.title',  'Termine-Seite: Kommende-Termine-Überschrift', 'Kommende Termine'),
  ('termine.upcoming.empty',  'Termine-Seite: Kommende leer-Hinweis',
    'Aktuell sind keine kommenden Termine eingetragen.'),
  ('termine.past.title',      'Termine-Seite: Vergangene-Überschrift', 'Rückblick: Vergangene Termine'),

  -- Aktuelles
  ('aktuelles.eyebrow',  'Aktuelles-Seite: Eyebrow', 'Neuigkeiten'),
  ('aktuelles.title',    'Aktuelles-Seite: Hauptüberschrift', 'Aktuelles'),
  ('aktuelles.subtitle', 'Aktuelles-Seite: Untertitel',
    'Berichte aus dem Vereinsleben, Informationen zur Bienenhaltung und Berichte über unsere Aktivitäten.'),
  ('aktuelles.empty',    'Aktuelles-Seite: leer-Hinweis',
    'Keine Beiträge für diesen Filter vorhanden.'),

  -- Infos
  ('infos.eyebrow',  'Infos-Seite: Eyebrow', 'Bibliothek'),
  ('infos.title',    'Infos-Seite: Hauptüberschrift', 'Infos für Imker'),
  ('infos.subtitle', 'Infos-Seite: Untertitel',
    'Praxis-Hinweise, Leitfäden, Formulare und nützliche Verweise rund um die Bienenhaltung und Imkerei.'),
  ('infos.empty.section', 'Infos-Seite: leerer-Sektion-Hinweis',
    'Aktuell keine Einträge in dieser Kategorie.'),
  ('infos.section.mitgliedschaft.label', 'Infos-Sektion-Label: mitgliedschaft', 'Mitgliedschaft & Beitritt'),
  ('infos.section.recht.label',          'Infos-Sektion-Label: recht',          'Recht & Verordnungen'),
  ('infos.section.varroa.label',         'Infos-Sektion-Label: varroa',         'Bienengesundheit & Varroa'),
  ('infos.section.formulare.label',      'Infos-Sektion-Label: formulare',      'Formulare & Vorlagen'),
  ('infos.section.videos.label',         'Infos-Sektion-Label: videos',         'Videos'),
  ('infos.section.links.label',          'Infos-Sektion-Label: links',          'Weiterführende Links'),

  -- Imker-Karte
  ('imker.eyebrow',  'Imker-Karte: Eyebrow', 'Bienenfreunde in der Region'),
  ('imker.title',    'Imker-Karte: Hauptüberschrift', 'Imker auf der Karte'),
  ('imker.subtitle', 'Imker-Karte: Untertitel',
    'Hier finden Sie unsere Mitgliedsimker rund um Grünberg. Honigverkauf direkt am Bienenstand, Beratung für Einsteiger und Schwarm-Unterstützung — auf Anfrage.'),
  ('imker.empty',    'Imker-Karte: leer-Hinweis',
    'Aktuell sind noch keine Imker auf der Karte eingetragen. Sind Sie Imker und möchten hier erscheinen? Sprechen Sie den Vorstand an.'),

  -- Mitglied werden
  ('mitglied.eyebrow',       'Mitglied-werden: Eyebrow', 'Gemeinschaft'),
  ('mitglied.title',         'Mitglied-werden: Hauptüberschrift', '🤝 Mitglied werden'),
  ('mitglied.intro',         'Mitglied-werden: Einleitungstext',
    'Schön, dass Sie sich für eine Mitgliedschaft beim Bienenzuchtverein Grünberg interessieren! Ob aktiver Imker oder passiver Förderer — jeder ist herzlich willkommen.'),
  ('mitglied.verein.title',  'Mitglied werden: Vereinsvorstellung-Überschrift',
    'Was uns ausmacht'),
  ('mitglied.verein.body',   'Mitglied werden: Vereinsvorstellung-Text (HTML)',
    '<p>Der <strong>Bienenzuchtverein Grünberg</strong> blickt auf eine traditionsreiche Geschichte zurück und widmet sich leidenschaftlich dem Schutz von Honigbienen, Wildbienen und unseren Ökosystemen. Wir verbinden gelebte Imkertradition mit fundiertem Fachwissen.</p><p>Unsere Mitglieder treffen sich regelmäßig zu <strong>Monatsversammlungen, Standschauen und Schulungen</strong>. Wir bieten Einsteigern eine fundierte Begleitung in die Imkerei und erfahrenen Imkern fachliche Weiterbildung, einen lebendigen Erfahrungsaustausch und Unterstützung bei Bienengesundheit und Schwarmrettung.</p>'),
  ('mitglied.rollen.title',  'Mitglied werden: Rollen-Hervorhebung Überschrift',
    'Auch ohne eigene Bienen herzlich willkommen'),
  ('mitglied.rollen.body',   'Mitglied werden: Rollen-Beschreibung (HTML mit Liste)',
    '<p>Ein lebendiger Verein lebt von <strong>vielen Händen</strong> — nicht nur von Imkerinnen und Imkern. Auch wenn Sie keinen eigenen Bienenstand betreiben, freuen wir uns sehr über Ihre Unterstützung. Besonders brauchen wir:</p><ul><li><strong>Standverkäuferinnen und Standverkäufer</strong> für unsere Auftritte auf Veranstaltungen wie „Grünberg auf der Rolle" und anderen regionalen Festen</li><li><strong>Kassiererinnen und Kassierer sowie Organisationstalente</strong>, die uns bei der Vereinsführung und Buchhaltung unter die Arme greifen</li><li><strong>Helferinnen und Helfer bei Vereins-Events</strong>, von der Standschau bis zur Honigverkostung</li><li><strong>Fördermitglieder</strong>, die unsere Arbeit ideell und mit ihrem Jahresbeitrag unterstützen — ohne aktive Mitarbeit</li></ul><p>Sprechen Sie uns gerne an: jede Form von Engagement ist willkommen.</p>'),
  ('mitglied.form.title',    'Mitglied-werden: Form-Überschrift', 'So werden Sie Mitglied'),
  ('mitglied.success.title', 'Mitglied-werden: Erfolgs-Titel', 'Anfrage erfolgreich übermittelt!'),
  ('mitglied.success.body',  'Mitglied-werden: Erfolgs-Text',
    'Vielen Dank für Ihr Interesse! Wir haben Ihre Beitrittsanfrage erhalten und werden uns in Kürze mit den entsprechenden Unterlagen bei Ihnen melden.'),

  -- Schwarm (zusätzlich zu bereits existierenden schwarm.*-Blocks)
  ('schwarm.kontakt.eyebrow', 'Schwarm: Direkt-Kontakt Eyebrow', '⚡ Schnellster Weg'),
  ('schwarm.kontakt.title',   'Schwarm: Direkt-Kontakt Titel', 'Telefonischer Notruf'),
  ('schwarm.kontakt.body',    'Schwarm: Direkt-Kontakt Text',
    'Per Anruf oder WhatsApp erreichen Sie uns am schnellsten. Bitte halten Sie nach Möglichkeit den Standort und ein Foto bereit.'),
  ('schwarm.form.title',      'Schwarm: Form-Überschrift', 'Meldung per Online-Formular'),

  -- Templates (Banner + Footer)
  ('banner.text',           'Banner: Notfall-Text (vor Tel-Nummer)', 'Bienenschwarm gefunden?'),
  ('banner.suffix',         'Banner: Suffix (nach Tel-Nummer)', '· Kostenlose Abholung & Rettung →'),
  ('footer.quicklinks.title', 'Footer: Schnellzugriff-Titel', 'Schnellzugriff'),
  ('footer.schwarm.title',  'Footer: Notruf-Titel', 'Bienenschwarm-Notruf'),
  ('footer.schwarm.body',   'Footer: Notruf-Text',
    'Haben Sie einen wilden Bienenschwarm entdeckt? Wir holen ihn kostenlos, sicher und fachgerecht ab.'),
  ('footer.kontakt.title',  'Footer: Außerhalb-Saison-Titel', 'Kontakt zum Vorstand'),
  ('footer.kontakt.body',   'Footer: Außerhalb-Saison-Text',
    'Fragen zur Imkerei, Mitgliedschaft oder unserem Verein? Wir freuen uns auf Ihre Nachricht.'),

  -- Rechtliche Seiten: kompletter Lauftext-Block (HTML erlaubt: p, h2, h3, ul, ol, li, strong, em, a, br)
  ('impressum.body', 'Impressum: Lauftext',
    '<h2>Angaben gemäß § 5 TMG</h2><p><strong>Bienenzuchtverein Grünberg</strong><br>[Vereinsanschrift hier eintragen]<br>35305 Grünberg</p><h2>Vertreten durch</h2><p>1. Vorsitzender: Samuel Krutzky</p><h2>Vereinsregister</h2><p>Registergericht und -nummer ergänzen</p><h2>Haftungsausschluss</h2><p>Trotz sorgfältiger inhaltlicher Kontrolle übernehmen wir keine Haftung für die Inhalte externer Links. Für den Inhalt der verlinkten Seiten sind ausschließlich deren Betreiber verantwortlich.</p>'),

  ('datenschutz.body', 'Datenschutz: Lauftext',
    '<h2>Verantwortlicher</h2><p>Bienenzuchtverein Grünberg, vertreten durch den Vorstand<br>[Vereinsanschrift]</p><h2>Erhobene Daten</h2><p>Beim Aufruf dieser Webseite werden vom Hosting-Provider technisch notwendige Server-Logs gespeichert (IP-Adresse, Datum, Uhrzeit, abgerufene Datei). Diese werden ausschließlich zur Gewährleistung des sicheren und stabilen Betriebs der Webseite erhoben und verarbeitet.</p><h2>Formulare</h2><p>Bei Nutzung des Schwarm-Meldeformulars oder des Beitritts-Formulars werden die von Ihnen eingegebenen Daten ausschließlich zur Bearbeitung Ihres Anliegens per E-Mail an den Vereinsvorstand übermittelt und zur Bearbeitung temporär oder dauerhaft im Vereinssystem verarbeitet. Eine Weitergabe an unbefugte Dritte erfolgt nicht.</p><h2>Cookies</h2><p>Wir verwenden ausschließlich technisch notwendige Session-Cookies für den geschützten Login-Bereich. Es werden zu keinem Zeitpunkt Tracking-, Analyse- oder Marketing-Cookies eingesetzt.</p><h2>Externe Ressourcen</h2><p>Diese Webseite lädt das CSS-Framework <em>Tailwind</em> über ein öffentliches CDN. Beim Laden der Seite wird eine technische Verbindung zum Server des CDN-Anbieters aufgebaut. Externe YouTube-Videos auf unserer Informationsseite werden erst nach Ihrer ausdrücklichen Bestätigung (Klick) geladen.</p><h2>Ihre Rechte</h2><p>Sie haben jederzeit das gesetzliche Recht auf kostenfreie Auskunft, Berichtigung, Sperrung oder Löschung Ihrer gespeicherten personenbezogenen Daten. Wenden Sie sich hierzu vertrauensvoll an den Vorstand.</p>'),

  ('bildnachweis.eyebrow',  'Bildnachweis: Eyebrow', 'Rechtliches'),
  ('bildnachweis.title',    'Bildnachweis: Hauptüberschrift', '📷 Bildnachweis'),
  ('bildnachweis.subtitle', 'Bildnachweis: Untertitel',
    'Diese Webseite verwendet Fotos aus den Wikimedia Commons. Wir danken den Fotografen für ihre Arbeit und veröffentlichen unter Beibehaltung der Lizenzbedingungen.'),
  ('bildnachweis.footer',   'Bildnachweis: Lizenz-Hinweis am Ende',
    '<h2>Lizenz-Informationen</h2><p>Die meisten Bilder stehen unter <strong>Creative Commons</strong>-Lizenzen (CC BY-SA 3.0 / 4.0) bzw. sind <strong>Public Domain</strong>. Bei der Weiterverwendung sind jeweils Urheber, Lizenz und Quelle anzugeben — eine Verlinkung auf die Wikimedia-Commons-Datei erfüllt diese Anforderung.</p>')

ON DUPLICATE KEY UPDATE
  title = VALUES(title),
  body  = VALUES(body);

SELECT COUNT(*) AS total_blocks FROM page_blocks;
