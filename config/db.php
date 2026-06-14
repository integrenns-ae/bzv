<?php
/**
 * Bienenzuchtverein Grünberg – Konfiguration
 *
 * Lokale + produktive DB-Credentials und globale Konstanten.
 *
 * Diese Datei enthält nur Defaults. Echte Credentials gehören in
 * config/db.local.php (gitignored), wird hier am Anfang automatisch geladen.
 */

// Lokale Overrides (echte Credentials, .gitignore'd) ZUERST laden, damit
// db.local.php Konstanten setzen kann; defaults unten greifen nur als Fallback.
if (is_file(__DIR__ . '/db.local.php')) {
    require_once __DIR__ . '/db.local.php';
}

// --- DB --- (Defaults; werden NICHT überschrieben falls bereits in db.local.php gesetzt)
defined('DB_HOST') or define('DB_HOST', 'localhost');
defined('DB_PORT') or define('DB_PORT', 3306);
defined('DB_NAME') or define('DB_NAME', 'bzv_gruenberg');
defined('DB_USER') or define('DB_USER', 'CHANGE_ME');
defined('DB_PASS') or define('DB_PASS', 'CHANGE_ME');

// --- Pixabay ---
defined('PIXABAY_KEY') or define('PIXABAY_KEY', '');  // leer = Widget zeigt Hinweis

// --- Site ---
define('SITE_NAME', 'Bienenzuchtverein Grünberg');
define('SITE_TAGLINE', 'Imkerei aus Leidenschaft');
define('SITE_URL', 'https://www.bienenzuchtverein-gruenberg.de');

// --- Notfall / Schwarm ---
define('SCHWARM_TEL', '017678572306');
define('SCHWARM_TEL_E164', '+4917678572306');           // für tel:- und wa.me-Links
define('SCHWARM_MAIL_TO', 'schwarm@bienenzuchtverein-gruenberg.de'); // wohin geht Meldeformular
// Saison-Fenster (Format: 'MM-DD'). Banner + Notruf-Karten werden nur in diesem
// jährlichen Fenster angezeigt — Menüpunkt und Schwarm-Seite bleiben das Jahr
// über erreichbar.
define('SCHWARM_SAISON_START', '04-01');
define('SCHWARM_SAISON_ENDE',  '07-15');

// --- Mail ---
defined('APP_MAIL_FROM')  or define('APP_MAIL_FROM', 'noreply@bienenzuchtverein-gruenberg.de');
defined('MAIL_TRANSPORT') or define('MAIL_TRANSPORT', 'mail');  // 'mail' | 'log'
// defined('MAIL_LOG_FILE') or define('MAIL_LOG_FILE', __DIR__ . '/../logs/mail.log');

// --- Session ---
define('SESSION_NAME', 'bzv_sid');

// --- Uploads ---
define('UPLOAD_MAX_BYTES', 8 * 1024 * 1024);
define('UPLOAD_ALLOWED_IMAGE_MIME', serialize(['image/jpeg', 'image/png', 'image/webp']));
define('UPLOAD_ALLOWED_DOC_MIME',   serialize(['application/pdf']));

