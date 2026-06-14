-- Demo-Daten für lokale Vorschau
-- Wichtig: mit --default-character-set=utf8mb4 importieren, sonst
-- werden Umlaute doppelt encodiert (MariaDB-Client-Default ist oft latin1).
SET NAMES utf8mb4;
USE bzv_gruenberg;

-- Termine (Mix kommend + vergangen)
INSERT INTO termine (starts_at, title, location, description, is_published) VALUES
  (DATE_ADD(NOW(), INTERVAL 14 DAY), 'Monatsversammlung Juni', 'Bürgerhaus Grünberg', 'Aktuelle Themen und Erfahrungsaustausch unter Imkern.', 1),
  (DATE_ADD(NOW(), INTERVAL 28 DAY), 'Standschau bei Familie Krutzky', 'Lehrbienenstand am Wald', 'Praktische Demonstration der Völkerführung im Sommer.', 1),
  (DATE_ADD(NOW(), INTERVAL 60 DAY), 'Fachvortrag: Varroa-Behandlung mit Oxalsäure', 'Bürgerhaus Grünberg', 'Referent: Dr. Beil, Bieneninstitut Kirchhain', 1),
  (DATE_SUB(NOW(), INTERVAL 30 DAY), 'Frühjahrs-Standschau', 'Lehrbienenstand', 'Auswinterung und Frühjahrsentwicklung', 1),
  (DATE_SUB(NOW(), INTERVAL 90 DAY), 'Jahreshauptversammlung 2026', 'Bürgerhaus Grünberg', 'Vorstandswahl und Rechenschaftsbericht', 1);

-- Vorstand (Live-Daten der Seite)
INSERT INTO vorstand (name, role, sort_order, is_published) VALUES
  ('Samuel Krutzky',  '1. Vorsitzender', 10, 1),
  ('Ferdi Diedam',    '2. Vorsitzender', 20, 1),
  ('Stephanie Kratz', 'Kassenwartin',    30, 1),
  ('Karl Trüller',    'Schriftführer',   40, 1),
  ('Helge Euler',     'Beisitzer',       50, 1),
  ('Frank Schmied',   'Beisitzer',       60, 1),
  ('Tobias Schmitt',  'Beisitzer',       70, 1),
  ('Daniela Weber',   'Beisitzerin',     80, 1),
  ('Annemarie Zimmer','Beisitzerin',     90, 1);

-- News
INSERT INTO news (slug, title, published_at, body, is_published) VALUES
  ('honigtag-2024-ehrung',
   'Grünberger Imker und Bienenzuchtverein auf Honigtag geehrt',
   '2024-12-20',
   '<p>Auf dem hessischen <strong>Honigtag 2024</strong> wurden mehrere Mitglieder unseres Vereins für die hervorragende Qualität ihres Honigs ausgezeichnet. Besonders hervorzuheben sind die Bewertungen im Premium-Bereich, die zeigen, wie viel Fachwissen und Engagement in unserer Imkerschaft steckt.</p><h2>Die Ausgezeichneten</h2><p>Die genaue Liste der Preisträger sowie weitere Eindrücke vom Tag findet ihr in der nächsten Vereinszeitschrift.</p><blockquote>Honig, der in Hessen ausgezeichnet wird, kommt aus Grünberg. Ein schöner Lohn für die viele Arbeit am Bienenstand.</blockquote>',
   1),
  ('praxis-imkerversammlung-juli-2024',
   'Praxis bei Imkerversammlung im Fokus',
   '2024-07-22',
   '<p>Bei der Juli-Versammlung lag der Fokus diesmal ganz auf der Praxis. Gemeinsam wurden mehrere Völker am Lehrbienenstand kontrolliert, dabei besonders auf <strong>Varroa-Befall</strong> und Königinnen-Entwicklung geachtet.</p><h2>Themen des Abends</h2><ul><li>Sommerbehandlung mit Ameisensäure</li><li>Erkennen schwacher Völker</li><li>Honigernte und Schleuderung</li></ul><p>Vielen Dank an alle, die mit angepackt haben!</p>',
   1),
  ('afb-sperrbezirk-lich-aufhebung',
   'AFB Sperrbezirk bei Lich wird am 28. März aufgehoben',
   '2024-03-26',
   '<p>Der Sperrbezirk wegen Amerikanischer Faulbrut bei Lich wird zum <strong>28. März</strong> wieder aufgehoben. Alle relevanten Bienenstände wurden mehrfach beprobt und sind frei.</p><p>Mehr Informationen beim <a href="https://veterinaeramt-giessen.de" target="_blank">Veterinäramt Gießen</a>.</p>',
   1);

-- Infos
INSERT INTO infos (section, title, body, link_url, sort_order, is_published) VALUES
  ('mitgliedschaft', 'Mitgliedschaft beantragen',
   '<p>Schicke uns einfach das <strong>Mitgliedsformular</strong> ausgefüllt zurück oder nutze unser Online-Formular. Der Jahresbeitrag beträgt 25 €, bei aktiver Imkertätigkeit zusätzlich der Landesverbands-Beitrag.</p>',
   NULL, 10, 1),
  ('varroa', 'Oxalsäure-Träufelbehandlung',
   '<p>Die Träufelbehandlung mit Oxalsäure im brutfreien Zustand ist die wirksamste Winterbehandlung gegen Varroa. <strong>Wichtig:</strong> nur bei Temperaturen unter 5°C anwenden, zwischen November und Januar.</p><ul><li>3,5 % Oxalsäure-Dihydrat-Lösung</li><li>5 ml pro besetzter Wabengasse</li><li>Schutzhandschuhe und Brille tragen</li></ul>',
   NULL, 10, 1),
  ('varroa', 'Puderzuckermethode zur Befallskontrolle',
   '<p>Die Puderzuckermethode ermöglicht eine schonende Bestimmung des Varroa-Befalls ohne Verluste. Bei Befall über 3 % muss behandelt werden.</p>',
   'https://www.youtube.com/watch?v=demo', 20, 1),
  ('recht', 'Honigverordnung 2024',
   '<p>Die aktuelle <strong>Honigverordnung</strong> regelt Zusammensetzung, Kennzeichnung und Vermarktung von Honig in Deutschland. Alle Imker, die Honig verkaufen, müssen die Verordnung kennen und einhalten.</p>',
   'https://www.gesetze-im-internet.de/honigv_2004/', 10, 1),
  ('formulare', 'Bienenstandschild',
   '<p>Pflicht für jeden Bienenstand: gut sichtbares Schild mit Name und Kontakt des Imkers. Vorlage zum Selbstausdrucken.</p>',
   NULL, 10, 1);

-- Externe Links
INSERT INTO links (section, title, url, sort_order) VALUES
  ('links', 'Deutscher Imkerbund (D.I.B.)',         'https://deutscherimkerbund.de',  10),
  ('links', 'Hessischer Imkerverband',              'https://hessischer-imkerverband.de', 20),
  ('links', 'LLH Bieneninstitut Kirchhain',         'https://llh.hessen.de/bildung/landesbetrieb-bieneninstitut-kirchhain/', 30),
  ('videos', 'Imkerei-Praxis-Videos (YouTube)',     'https://www.youtube.com/results?search_query=imkerei+praxis', 10),
  ('recht', 'Bienenseuchen-Verordnung (BienSeuchV)','https://www.gesetze-im-internet.de/bienseuchv/', 20);
