<?php
declare(strict_types=1);

/**
 * Zentrale Initialisierung — jede public/-Page beginnt mit:
 *   require_once __DIR__ . '/../library/_init.php';
 * (admin/, mitglieder/ jeweils '/../../library/_init.php')
 */

// Output-Buffering einschalten — wichtig auf Shared-Hostings ohne output_buffering
// in php.ini (z.B. STRATO). Sonst kann nach Layout-Include kein redirect mehr
// gesetzt werden („Speichern in Admin reagiert nicht").
if (ob_get_level() === 0) {
    ob_start();
}

// Helpers registriert auch den Autoloader für Database, Auth, Mailer, Templates
require_once __DIR__ . '/Helpers.php';

// Konfiguration immer geladen (Konstanten verfügbar)
require_once __DIR__ . '/../config/db.php';

// Session vorbereiten (für CSRF und Flash auch in nicht-eingeloggten Pages)
Auth::start();
