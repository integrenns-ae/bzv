# Bienenzuchtverein Grünberg — Webseite

Eigenständige Vereins-Webseite mit integriertem CRUD-Admin. Kein externes CMS.

## Stack

- PHP 8.x (vanilla)
- MySQL/MariaDB (`utf8mb4`)
- Server-side rendering + Tailwind via CDN
- Eigener Admin-Bereich (Login + CRUD)
- Mitglieder-Login für interne Dokumente
- Stock-Bild-Pipeline (Wikimedia + Pixabay-Suche im Admin)

## Verzeichnisse

```
config/      DB-Konstanten — echte Credentials in db.local.php (gitignored)
library/     Database, Auth, Templates, Mailer, Helpers
public/      DocRoot
  admin/     CRUD-Pages, geschützt
  mitglieder/ Mitgliederbereich, geschützt
  bilder/    News-Bilder, Vorstand-Fotos, stock/ (Wikimedia), Pixabay-Downloads
  downloads/ öffentliche PDFs
scripts/     schema.sql, seed-demo.sql, create-admin.php, fetch-stock-images.sh
```

## Lokal entwickeln

```bash
# DB aufsetzen
mariadb -e "CREATE DATABASE bzv_gruenberg CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"
mariadb --default-character-set=utf8mb4 bzv_gruenberg < scripts/schema.sql

# Credentials
cp config/db.php config/db.local.php   # echte Werte eintragen

# Erster Admin
php scripts/create-admin.php <user> <starkes-passwort> <anzeigename>

# Demo-Daten (optional, fürs lokale Klickdurch)
mariadb --default-character-set=utf8mb4 bzv_gruenberg < scripts/seed-demo.sql

# Server starten
php -S localhost:8000 -t public
# → http://localhost:8000   (Admin: /admin/login.php)
```

## Stock-Bilder

Wikimedia-Commons-Pool ist bereits eingebunden (`public/bilder/stock/`).
Erneut/erweitert laden:
```bash
./scripts/fetch-stock-images.sh
```
Pixabay-Suche im Admin braucht `PIXABAY_KEY` in `config/db.local.php`.

## Deploy

Hoster-agnostisch — alles was nötig ist:
1. PHP 8.x + MySQL/MariaDB beim Hoster verfügbar
2. `public/` als DocRoot konfigurieren (oder `_bootstrap.php`-Trick wenn DocRoot
   nicht änderbar)
3. DB anlegen + `scripts/schema.sql` importieren
4. `config/db.local.php` mit Live-Credentials
5. `library/` + `config/` außerhalb DocRoot oder per `.htaccess` geschützt
6. Ersten Admin via `php scripts/create-admin.php` anlegen

Vor jedem Deploy: DB-Dump als Backup ziehen.

## Routen

| Pfad | Inhalt |
|---|---|
| `/` | Startseite |
| `/schwarm.php` | Schwarm-Meldeformular |
| `/termine.php` · `/termine.ics` | Termine + iCal-Feed |
| `/aktuelles.php` · `/aktuelles-detail.php?slug=…` | News |
| `/vorstand.php` | Vorstand |
| `/infos.php` | Infos für Imker |
| `/mitglied-werden.php` | Beitrittsformular |
| `/bildnachweis.php` | Bildlizenz-Übersicht |
| `/admin/` | Admin-CRUD |
| `/mitglieder/` | Mitgliederbereich |
