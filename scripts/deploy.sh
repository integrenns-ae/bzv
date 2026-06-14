#!/usr/bin/env bash
#
# scripts/deploy.sh — BZV Grünberg → STRATO (SFTP, lftp mirror)
#
# Liest Zugangsdaten aus ../.deploy.env (gitignored) und syncht das Projekt
# inkrementell. Excludes verhindern, dass Müll/Secrets hochgeladen werden.
#
# Aufruf:
#   bash scripts/deploy.sh             # normal deployen
#   bash scripts/deploy.sh --dry-run   # nur anzeigen, was passieren würde
#   bash scripts/deploy.sh --first     # Erst-Deploy: zeigt zusätzlich Hinweise
#
# Voraussetzung: lftp (brew install lftp)

set -euo pipefail

ROOT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
cd "$ROOT_DIR"

# --- Args ---
DRY_RUN=""
FIRST=""
for arg in "$@"; do
    case "$arg" in
        --dry-run) DRY_RUN="--dry-run" ;;
        --first)   FIRST="1" ;;
        *) echo "Unbekanntes Argument: $arg" >&2; exit 2 ;;
    esac
done

# --- Credentials laden ---
if [[ ! -f ".deploy.env" ]]; then
    echo "FEHLER: .deploy.env fehlt. Lege sie nach dem Muster in DEPLOY.md an." >&2
    exit 1
fi
# shellcheck disable=SC1091
set -a; . ./.deploy.env; set +a

: "${SFTP_HOST:?SFTP_HOST nicht gesetzt}"
: "${SFTP_USER:?SFTP_USER nicht gesetzt}"
: "${SFTP_PASS:?SFTP_PASS nicht gesetzt}"
: "${SFTP_PORT:=22}"
: "${REMOTE_ROOT:=/}"

# --- lftp-Kommandos ---
# - Spiegelt das gesamte Projekt nach REMOTE_ROOT.
# - --reverse  = local → remote
# - --parallel = mehrere Dateien gleichzeitig hochladen (8 ist STRATO-freundlich)
# - --only-newer = überspringt unveränderte Dateien (mtime-Vergleich)
# - --exclude-glob = Patterns, die NICHT hochgeladen werden
#
# KEIN --delete: Auf dem Server entstehen durch den Admin laufend neue Dateien
# (hochgeladene Fotos, Pixabay-Bilder, PDFs). --delete würde all das beim
# nächsten Deploy löschen, weil es lokal nicht existiert. Stattdessen bleiben
# verwaiste Dateien stehen — das ist harmlos, Datenverlust dagegen nicht.
#
# WICHTIG: config/db.local.php wird ausgeschlossen — die Datei wird einmalig
# direkt auf dem Server angelegt (siehe DEPLOY.md). So überschreibt der Deploy
# die Live-Credentials nie versehentlich mit Dev-Credentials.
#
# Ebenfalls ausgeschlossen: Admin-Upload-Bereiche (uploads/, mitglieder/doks/)
# und pixabay-attribution.tsv — diese gehören dem Server, nicht dem Repo.

LFTP_SCRIPT=$(cat <<'EOF'
set sftp:auto-confirm yes
set ssl:verify-certificate no
set net:max-retries 2
set net:timeout 20

mirror --reverse \
       --only-newer \
       --parallel=8 \
       --verbose \
       --exclude-glob .git \
       --exclude-glob .git/ \
       --exclude-glob .gitignore \
       --exclude-glob .DS_Store \
       --exclude-glob .deploy.env \
       --exclude-glob backups/ \
       --exclude-glob node_modules/ \
       --exclude-glob vendor/ \
       --exclude-glob .vscode/ \
       --exclude-glob .idea/ \
       --exclude-glob '*.sql.gz' \
       --exclude-glob '*.tar.gz' \
       --exclude-glob 'config/db.local.php' \
       --exclude-glob 'config/db.local.php.example' \
       --exclude-glob 'scripts/deploy.sh' \
       --exclude-glob 'logs/' \
       --exclude-glob 'README.md' \
       --exclude-glob 'DEPLOY.md' \
       --exclude-glob 'DESIGN_BRIEF.md' \
       --exclude-glob 'AG_KICKOFF.txt' \
       --exclude-glob 'pixabay-attribution.tsv' \
       --exclude-glob 'uploads/' \
       --exclude-glob 'doks/' \
       __LOCAL__ __REMOTE__
bye
EOF
)

# Platzhalter ersetzen + Dry-Run anhängen falls gewünscht
LFTP_SCRIPT="${LFTP_SCRIPT/__LOCAL__/$ROOT_DIR}"
LFTP_SCRIPT="${LFTP_SCRIPT/__REMOTE__/$REMOTE_ROOT}"
if [[ -n "$DRY_RUN" ]]; then
    LFTP_SCRIPT="${LFTP_SCRIPT/mirror --reverse/mirror --dry-run --reverse}"
fi

echo "=== BZV Deploy → $SFTP_USER@$SFTP_HOST:$REMOTE_ROOT ==="
if [[ -n "$DRY_RUN" ]]; then
    echo "  (DRY-RUN — keine Änderungen am Server)"
fi
echo "Lokal: $ROOT_DIR"
echo "Beginne in 2 Sekunden, Abbruch mit Ctrl-C…"
sleep 2

echo "$LFTP_SCRIPT" | lftp -u "$SFTP_USER,$SFTP_PASS" -p "$SFTP_PORT" "sftp://$SFTP_HOST"

echo ""
echo "=== Deploy abgeschlossen ==="

if [[ -n "$FIRST" ]]; then
    cat <<'HINT'

Erst-Deploy fertig. Nächste manuelle Schritte:

  1) In STRATO phpMyAdmin: scripts/schema.sql importieren
     (Datenbank vorher leer; Import mit Charset utf8mb4)

  2) Auf dem Server config/db.local.php anlegen
     (Vorlage: config/db.local.php.example) — mit echten STRATO-DB-Credentials

  3) Admin-User anlegen via temporär hochgeladenem create-admin.php:
     im Browser: https://<deine-subdomain>/scripts-create-admin?…
     oder besser per phpMyAdmin direkt einen User-INSERT.

  4) Smoke-Tests in DEPLOY.md durchspielen.

HINT
fi
