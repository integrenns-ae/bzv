# Design-Brief für Antigravity

## Worum geht's

Webseiten-Modernisierung für den **Bienenzuchtverein Grünberg**. Die alte Live-Seite (Joomla, Template `imkerei2`, https://www.bienenzuchtverein-gruenberg.de) ist inhaltlich solide, sieht aber wie 2010 aus. Diese Code-Basis hier ersetzt sie komplett. Backend (CRUD-Admin, Mitgliederbereich, Migration) ist fertig. **Dein Auftrag: die öffentlichen Seiten visuell hochwertig machen.**

## Kontext

- **Verein**: Imker in Grünberg (Hessen). Schulungen, Standschauen, Schwarmrettung, Bienengesundheit.
- **Zielgruppen**:
  1. **Schwarm-Melder** (Nicht-Imker, Notfall, kommen meist vom Handy, oft Senioren) → Notrufnummer muss sofort sichtbar sein
  2. **Interessierte / potenzielle Neumitglieder** → Vereinsprofil, Mitglied-werden-Flow
  3. **Bestehende Mitglieder** → Termine, News, interne Doks
- **Tonalität**: warm, vertrauenswürdig, naturverbunden — nicht corporate, nicht spielerig.

## Was du gestalten sollst (komplette Freiheit)

- Markenfarben, Typografie, Hero-Konzept, Komponenten-Stil, Iconografie, Hover/Animationen
- Layout-Struktur der einzelnen Seiten
- Ob `Tailwind Play CDN` bleibt oder du auf lokalen Tailwind-Build / Custom-CSS umstellst (siehe Constraint unten)
- Mobile-Navigation, Footer-Design, Karten-Layouts für Vorstand/News/Termine
- Bilder/Illustrationen/Icons — wenn du Assets ergänzen willst, leg sie unter `public/assets/` ab

## Constraints (technisch, nicht kreativ)

**Diese Dinge bitte NICHT ändern:**

- **PHP-Routen / Dateinamen** bleiben gleich (`index.php`, `vorstand.php`, `termine.php`, `aktuelles.php`, `aktuelles-detail.php`, `infos.php`, `schwarm.php`, `mitglied-werden.php`, `termine.ics.php`, `impressum.php`, `datenschutz.php`). Sonst brechen die `.htaccess`-Redirects von alten Joomla-URLs.
- **DB-Felder und PHP-Variablen-Namen** in den Templates (`$members`, `$post['image_path']`, `$t['starts_at']` etc.) — die kommen aus DB-Queries. Wenn du HTML umbaust, die Variablen-Referenzen erhalten.
- **`/admin/`** und **`/mitglieder/`** — nicht anfassen. Backend-CRUD und geschützter Bereich, eigene Ästhetik OK aber kein Refactor.
- **`library/Database.php`, `Auth.php`, `Helpers.php`, `Mailer.php`** — Backend-Code, keine Design-Relevanz.
- **`scripts/`** — Migration und Bootstrap, lass weg.
- **Schema `scripts/schema.sql`** — DB-Struktur bleibt.

**Diese Dinge MÜSSEN funktional erhalten bleiben:**

- **Schwarm-Notruf-Block** muss auf jeder Seite prominent sichtbar sein (aktuell oben als Banner-Streifen + im Footer). Form/Ort kannst du ändern, aber **darf nicht unterhalb des ersten Scrolls verschwinden**. Tel-Link `tel:+4917678572306` und WhatsApp-Link `https://wa.me/4917678572306`.
- **Mobile-First** — viele Schwarm-Melder kommen vom Handy, oft draußen, Senioren. Große Klickflächen, kontrastreicher Notruf-Button.
- **CMS-Kompatibilität**: Inhalte wie News-Body kommen als HTML aus der DB (gefiltert durch `clean_html()` in `library/Helpers.php`, erlaubt: `p, br, strong, em, b, i, u, ul, ol, li, a, h2, h3, h4, blockquote`). Diese Tags sollten styled werden — bestehende `.prose-bzv`-Klasse in `library/Templates.php` ist der Hook dafür. Klasse umbenennen ist OK, dann auch in `public/aktuelles-detail.php`, `public/infos.php` etc. mit anpassen.
- **iCal-Feed** (`/termine.ics.php`) liefert `Content-Type: text/calendar` — bitte nicht mit HTML-Layout umhüllen.
- **Footer mit Impressum/Datenschutz-Links** rechtlich Pflicht.

## Tailwind: bleiben oder weg?

Aktuell läuft Tailwind via Play CDN (`<script src="https://cdn.tailwindcss.com">`). Das ist für ein MVP OK, hat aber zwei Schwächen:
- `@apply` funktioniert **nicht** (deshalb sind die `.prose-bzv`-Styles gerade Plain-CSS)
- 300KB JS pro Seitenaufruf

Wenn du auf einen lokalen Tailwind-Build umstellst:
- Build-Output nach `public/assets/app.css`
- Build-Script in einer `package.json` im Repo-Root oder unter `tools/`
- `library/Templates.php` Header anpassen (CDN-Script raus, lokales CSS-Link rein)
- README.md ergänzen, wie der Build läuft
- **Wichtig**: `public/admin/*` und `public/mitglieder/*` nutzen ebenfalls Tailwind via CDN — die müsstest du dann auch umstellen. Wenn dir das zu groß ist, ist CDN-bleiben absolut akzeptabel.

Alternativ: komplett ohne Tailwind, eigenes CSS in `public/assets/`. Auch OK, ist dann mehr Handarbeit.

## Vorhandene Seiten

| Datei | Aktuell |
|---|---|
| `public/index.php` | Hero mit Schwarm-Karte, "Nächste Termine"-Cards, News-Grid, Über-uns-Block |
| `public/vorstand.php` | Karten-Grid mit rundem Foto, Name, Rolle |
| `public/termine.php` | Listen-Layout, iCal-Abonnieren-Button, vergangene Termine als kompakte Liste |
| `public/aktuelles.php` | Card-Grid mit Bild, Jahres-Filter |
| `public/aktuelles-detail.php` | Einzelartikel, `prose-bzv`-Klasse für Body |
| `public/infos.php` | Sektion-Navigation, Karten pro Eintrag, Download/Link-Buttons |
| `public/schwarm.php` | Hero mit Notruf-Box, "Was tun"-Tipps, Meldeformular |
| `public/mitglied-werden.php` | Zwei-Wege-Card (Online vs PDF), Formular |
| `public/impressum.php` / `public/datenschutz.php` | Klassischer Lauftext |

## Live-Preview

```bash
cd /Users/alexanderenns/bienenzuchtverein-gruenberg
php -S localhost:8000 -t public
# → http://localhost:8000
```

DB-abhängige Seiten (Startseite, Vorstand, Termine, Aktuelles, Infos) brauchen MySQL + Schema-Import + Test-Daten. Wenn du nur Design machst und das umständlich ist: **arbeite an den Templates mit Mock-Daten** (z.B. temporär Array statt DB-Query oben in der Page einsetzen) und entferne den Mock am Ende wieder.

Schnellste Variante für reine Design-Arbeit:
- `schwarm.php`, `mitglied-werden.php`, `impressum.php`, `datenschutz.php` brauchen **keine DB** → direkt designbar
- Für die DB-Seiten: am einfachsten Mock-Arrays oben in der Datei während Design-Iteration, am Ende wieder DB-Query

## Übergabe zurück

Wenn fertig: Commit/Push (oder einfach Bescheid sagen). Backend-Audit übernimmt Claude Code — geprüft wird:
- Bleiben alle Dateinamen/Routen
- Funktioniert das CRUD-Backend mit den überarbeiteten Templates (Variablen-Referenzen, Bild-Pfade)
- Funktioniert `.htaccess` weiter
- Erfüllt Schwarm-Notruf-Sichtbarkeit den Constraint
