# Deployment auf STRATO

Subdomain läuft bei STRATO Hosting Basic. SFTP-User ist gejailt in
`/htdocs/bzv` — aus seiner Sicht ist das `/`. Die Subdomain zeigt
auf `/htdocs/bzv/public`, deshalb sind `library/`, `config/` und
`scripts/` nicht direkt aus dem Web erreichbar (zusätzlich sperren
`.htaccess`-Dateien diese Ordner als Defense-in-Depth).

## Verzeichnis-Layout

```
/htdocs/bzv/                ← SFTP-Root (= "/" aus SFTP-Sicht)
├── public/                 ← Subdomain-DocRoot (öffentlich)
├── library/                ← PHP-Klassen, gesperrt via .htaccess
├── config/
│   ├── db.php              ← Defaults, hochgeladen
│   ├── db.local.php        ← STRATO-Credentials, NUR auf Server (nicht im Repo)
│   └── .htaccess           ← Require all denied
├── scripts/                ← schema.sql etc., gesperrt
└── logs/                   ← Mail-Log etc., gesperrt + writable
```

## Voraussetzungen lokal

```bash
brew install lftp        # SFTP-Mirror-Tool
```

## Erst-Deploy (einmalig)

### 1) `.deploy.env` füllen

`.deploy.env` im Repo-Root (gitignored). Vorlage:

```bash
SFTP_HOST="59543682.ssh.w1.strato.hosting"
SFTP_PORT="22"
SFTP_USER="stu347672247"
SFTP_PASS="..."
REMOTE_ROOT="/"
SITE_URL="https://bzv.deine-domain.de"
```

### 2) DB im STRATO-Panel anlegen

- Typ: **MariaDB 11.8**
- Kommentar: `bzv_gruenberg`
- Passwort sicher vergeben + notieren
- STRATO zeigt anschließend:
  - DB-Host (z.B. `rdbms.strato.de`)
  - DB-Name (`DBxxxxxxx`)
  - DB-User (meist identisch mit DB-Name)

### 3) Schema + Inhalte importieren (phpMyAdmin)

Im STRATO-Panel → Datenbank-Verwaltung → phpMyAdmin öffnen, Datenbank wählen
und der Reihe nach importieren (Zeichensatz immer **utf8mb4**):

1. `scripts/schema.sql` — DDL: alle Tabellen anlegen
2. `scripts/seed-content-live.sql` — die echten Inhalte vom Verein
   (9 Vorstand, 13 Termine, 26 News, 23 Infos, 1 Link, 61 Page-Blocks).
   Diese Datei wird per `bash scripts/export-live-content.sh` regeneriert
   (zieht den aktuellen Stand von der STRATO-Test-Subdomain).

> Wenn nur ein Schema-Reset ohne Inhalte gewünscht ist (z.B. neue Hosting-Umgebung
> zum Testen), kann `seed-content-live.sql` einfach übersprungen werden — dann
> bleibt die Seite leer und Vorstand pflegt im Admin neu.

### 4) Code hochladen

```bash
bash scripts/deploy.sh --first
```

Das Skript spiegelt das Projekt nach `/htdocs/bzv/` und excludiert:
`config/db.local.php`, `.git/`, `backups/`, `logs/`, `node_modules/`,
`vendor/`, `.deploy.env`, `*.DS_Store`, `*.sql.gz`, `*.tar.gz` und
`scripts/deploy.sh` selbst.

### 5) `config/db.local.php` auf dem Server anlegen

`config/db.local.php.example` als Vorlage nehmen, lokal kopieren,
STRATO-DB-Credentials einsetzen, dann **manuell per FTP** in
`/htdocs/bzv/config/db.local.php` hochladen.

> Diese Datei wird beim Deploy nicht überschrieben (in deploy.sh
> ausgeschlossen). Single Source of Truth für Live-Credentials.

### 6) Admin-User anlegen

Bequemster Weg über phpMyAdmin → Tab `users` → INSERT mit fertigem
Passwort-Hash. Hash lokal erzeugen:

```bash
php -r 'echo password_hash("DEIN_PASSWORT", PASSWORD_BCRYPT) . "\n";'
```

Dann in phpMyAdmin:

```sql
INSERT INTO users (username, password_hash, display_name, role, active)
VALUES ('vorstand', '$2y$10$…', 'Vorstand', 'admin', 1);
```

### 7) Smoke-Tests

```bash
SITE_URL="https://bzv.deine-domain.de"

# Startseite lädt
curl -sI "$SITE_URL/" | head -1            # → HTTP/2 200

# Schwarm-Seite + Endpoints
curl -sI "$SITE_URL/schwarm.php" | head -1
curl -s "$SITE_URL/api/nearest-imker.php?lat=50.59&lng=8.96" | head -100

# Sensible Pfade NICHT erreichbar
curl -sI "$SITE_URL/../config/db.local.php" | head -1   # → 403/404
curl -sI "$SITE_URL/library/Database.php" | head -1     # sollte nicht klappen
curl -sI "$SITE_URL/../library/Database.php" | head -1  # sollte nicht klappen
```

Im Browser:
- `/` → Startseite
- `/schwarm.php` → Karte lädt, GPS-Button da
- `/admin/login.php` → Login mit dem Vorstand-User → Dashboard

## Routinedeploy

Sobald der Erst-Deploy steht:

```bash
bash scripts/deploy.sh             # inkrementell
bash scripts/deploy.sh --dry-run   # vorher prüfen, was geändert wird
```

`--only-newer` lädt nur geänderte Dateien hoch. **Kein `--delete`** —
Admin-Uploads auf dem Server (Fotos, Pixabay-Bilder, PDFs) bleiben
dadurch unangetastet.

### Admin-Uploads beim Domain-Umzug mitnehmen

Diese Dateien entstehen nur auf dem Server und sind **nicht im Repo**:

- `public/bilder/<kategorie>/<jahr>/pxb-*.jpg` — Pixabay-Bilder
- `public/bilder/<kategorie>/*` — im Admin hochgeladene Fotos
- `public/bilder/pixabay-attribution.tsv` — Pixabay-Bildnachweis-Log
- `public/uploads/`, `public/mitglieder/doks/` — sonstige Uploads

Beim Umzug auf die Echtdomain **per SFTP vom alten Webspace
herunterladen und auf den neuen hochladen** — sonst fehlen die im
Admin gepflegten Bilder. (`scripts/seed-content-live.sql` enthält nur
die DB-Zeilen, nicht die Bilddateien selbst.)

## Stolperfallen

- **Subdomain zeigt auf `/bzv` statt `/bzv/public`**: dann sind
  library/, config/, scripts/ öffentlich (zwar mit `.htaccess`
  gesperrt, aber suboptimal). → im Domain-Manager korrigieren.
- **PHP-Version < 8.1**: Code nutzt `declare(strict_types=1)` und
  `match`-Expressions. Im STRATO-Panel → „PHP-Version" → 8.1+
  einstellen.
- **`mail()` schweigt**: STRATO-Sendmail ist da, aber Absender muss
  zur Domain passen. `APP_MAIL_FROM` ggf. in `db.local.php` auf
  eine Adresse `@deine-strato-domain.de` setzen.
- **Doppelte UTF-8-Encoding**: tritt nur beim CLI-Import mit
  falschem Charset auf — bei phpMyAdmin in Schritt 3 explizit
  utf8mb4 wählen.

## Rollback

`backups/` enthält pre-deploy-Snapshots (manuell anlegen):

```bash
# Vor riskanten Deploys
bash scripts/deploy.sh --dry-run > backups/dryrun-$(date +%F).log
```

Im Notfall: alten Stand per SFTP zurückspielen (lokal hat man
Git-History → einfach Checkout + Deploy).
