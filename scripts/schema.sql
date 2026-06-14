-- Bienenzuchtverein Grünberg — DB-Schema (MySQL/MariaDB)
-- Datenbank am Ziel-Host anlegen, dann diese Datei importieren:
--   mariadb --default-character-set=utf8mb4 -u <user> -p <db> < schema.sql
-- Wichtig: --default-character-set=utf8mb4 — sonst Doppel-Encoding bei Umlauten.

SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

-- --- Benutzer (Vorstand mit Admin-Zugang + ein Mitglieder-Account) ---
DROP TABLE IF EXISTS users;
CREATE TABLE users (
    id              INT UNSIGNED NOT NULL AUTO_INCREMENT,
    username        VARCHAR(64) NOT NULL,
    password_hash   VARCHAR(255) NOT NULL,
    display_name    VARCHAR(128) DEFAULT NULL,
    email           VARCHAR(160) DEFAULT NULL,
    note            TEXT DEFAULT NULL,                  -- Begründung bei Selbst-Registrierung
    role            ENUM('admin','member') NOT NULL DEFAULT 'member',
    active          TINYINT(1) NOT NULL DEFAULT 1,       -- 0 = pending/gesperrt, 1 = freigegeben
    created_at      DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    last_login_at   DATETIME DEFAULT NULL,
    PRIMARY KEY (id),
    UNIQUE KEY uk_users_username (username)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --- News / Aktuelles ---
DROP TABLE IF EXISTS news;
CREATE TABLE news (
    id              INT UNSIGNED NOT NULL AUTO_INCREMENT,
    slug            VARCHAR(200) NOT NULL,
    title           VARCHAR(255) NOT NULL,
    published_at    DATE NOT NULL,
    expires_at      DATE DEFAULT NULL,         -- ab diesem Datum wird der Beitrag NICHT mehr angezeigt
    image_path      VARCHAR(255) DEFAULT NULL,
    body            MEDIUMTEXT NOT NULL,
    is_published    TINYINT(1) NOT NULL DEFAULT 1,
    created_at      DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at      DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY uk_news_slug (slug),
    KEY ix_news_published (is_published, published_at),
    KEY ix_news_expires   (is_published, expires_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --- Termine ---
DROP TABLE IF EXISTS termine;
CREATE TABLE termine (
    id              INT UNSIGNED NOT NULL AUTO_INCREMENT,
    starts_at       DATETIME NOT NULL,
    ends_at         DATETIME DEFAULT NULL,
    title           VARCHAR(255) NOT NULL,
    location        VARCHAR(255) DEFAULT NULL,
    description     TEXT,
    is_published    TINYINT(1) NOT NULL DEFAULT 1,
    created_at      DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at      DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    KEY ix_termine_starts (is_published, starts_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --- Vorstand ---
DROP TABLE IF EXISTS vorstand;
CREATE TABLE vorstand (
    id              INT UNSIGNED NOT NULL AUTO_INCREMENT,
    name            VARCHAR(128) NOT NULL,
    role            VARCHAR(128) NOT NULL,
    photo_path      VARCHAR(255) DEFAULT NULL,
    email           VARCHAR(128) DEFAULT NULL,
    phone           VARCHAR(64)  DEFAULT NULL,
    sort_order      INT NOT NULL DEFAULT 0,
    is_published    TINYINT(1) NOT NULL DEFAULT 1,
    PRIMARY KEY (id),
    KEY ix_vorstand_sort (is_published, sort_order)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --- Infos für Imker ---
DROP TABLE IF EXISTS infos;
CREATE TABLE infos (
    id              INT UNSIGNED NOT NULL AUTO_INCREMENT,
    section         VARCHAR(64) NOT NULL,         -- z.B. 'mitgliedschaft', 'recht', 'varroa', 'formulare', 'videos', 'links'
    title           VARCHAR(255) NOT NULL,
    body            TEXT,                          -- darf einfache HTML-Tags enthalten (gefiltert)
    link_url        VARCHAR(500) DEFAULT NULL,
    download_path   VARCHAR(255) DEFAULT NULL,
    sort_order      INT NOT NULL DEFAULT 0,
    is_published    TINYINT(1) NOT NULL DEFAULT 1,
    PRIMARY KEY (id),
    KEY ix_infos_section (section, is_published, sort_order)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --- Externe Links (separat von infos, weil reine Linklisten) ---
DROP TABLE IF EXISTS links;
CREATE TABLE links (
    id              INT UNSIGNED NOT NULL AUTO_INCREMENT,
    section         VARCHAR(64) NOT NULL,
    title           VARCHAR(255) NOT NULL,
    url             VARCHAR(500) NOT NULL,
    sort_order      INT NOT NULL DEFAULT 0,
    PRIMARY KEY (id),
    KEY ix_links_section (section, sort_order)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --- Interne Dokumente (Mitgliederbereich) ---
DROP TABLE IF EXISTS internal_docs;
CREATE TABLE internal_docs (
    id              INT UNSIGNED NOT NULL AUTO_INCREMENT,
    title           VARCHAR(255) NOT NULL,
    description     TEXT,
    file_path       VARCHAR(255) NOT NULL,        -- relativ zu /mitglieder/doks/, zufälliger Name
    original_name   VARCHAR(255) DEFAULT NULL,
    uploaded_at     DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    KEY ix_docs_uploaded (uploaded_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --- Schwarm-Meldungen (Log, optional) ---
DROP TABLE IF EXISTS schwarm_logs;
CREATE TABLE schwarm_logs (
    id              INT UNSIGNED NOT NULL AUTO_INCREMENT,
    reported_at     DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    reporter_name   VARCHAR(128) DEFAULT NULL,
    reporter_phone  VARCHAR(64)  DEFAULT NULL,
    location        VARCHAR(255) DEFAULT NULL,
    lat             DECIMAL(10,7) DEFAULT NULL,
    lng             DECIMAL(10,7) DEFAULT NULL,
    height_m        VARCHAR(32)  DEFAULT NULL,
    message         TEXT,
    mail_sent       TINYINT(1) NOT NULL DEFAULT 0,
    ip              VARCHAR(64)  DEFAULT NULL,
    PRIMARY KEY (id),
    KEY ix_schwarm_reported (reported_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --- Imker (öffentliche Karte; Eintragung nur mit consent_given) ---
DROP TABLE IF EXISTS imker;
CREATE TABLE imker (
    id            INT UNSIGNED NOT NULL AUTO_INCREMENT,
    name          VARCHAR(128) NOT NULL,
    street        VARCHAR(255) DEFAULT NULL,
    postal_code   VARCHAR(10)  DEFAULT NULL,
    city          VARCHAR(128) DEFAULT NULL,
    lat           DECIMAL(10,7) DEFAULT NULL,
    lng           DECIMAL(10,7) DEFAULT NULL,
    phone         VARCHAR(64)  DEFAULT NULL,
    email         VARCHAR(128) DEFAULT NULL,
    sells_honey   TINYINT(1) NOT NULL DEFAULT 0,
    swarm_helper  TINYINT(1) NOT NULL DEFAULT 0,
    description   TEXT,
    consent_given TINYINT(1) NOT NULL DEFAULT 0,
    is_published  TINYINT(1) NOT NULL DEFAULT 1,
    sort_order    INT NOT NULL DEFAULT 0,
    created_at    DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at    DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    KEY ix_imker_pub (is_published, consent_given, lat)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --- Sammelbestellungen (Mitgliederbereich: Futter / Behandlung / Zucht) ---
DROP TABLE IF EXISTS bestellungen;
CREATE TABLE bestellungen (
    id              INT UNSIGNED NOT NULL AUTO_INCREMENT,
    art             ENUM('futter','behandlung','zucht') NOT NULL,
    member_user_id  INT UNSIGNED DEFAULT NULL,         -- Login-User, der bestellt hat
    member_name     VARCHAR(160) NOT NULL,
    member_email    VARCHAR(160) DEFAULT NULL,
    member_phone    VARCHAR(64)  DEFAULT NULL,
    details         MEDIUMTEXT,                         -- JSON: art-spezifische Felder
    summe_eur       DECIMAL(10,2) DEFAULT NULL,
    erledigt        TINYINT(1) NOT NULL DEFAULT 0,       -- vom Vorstand abgehakt
    created_at      DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    ip              VARCHAR(64)  DEFAULT NULL,
    PRIMARY KEY (id),
    KEY ix_best_art (art, created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --- Bildergalerie (für die Startseite) ---
DROP TABLE IF EXISTS gallery;
CREATE TABLE gallery (
    id           INT UNSIGNED NOT NULL AUTO_INCREMENT,
    image_path   VARCHAR(255) NOT NULL,
    alt_text     VARCHAR(255) DEFAULT NULL,
    caption      VARCHAR(255) DEFAULT NULL,
    sort_order   INT NOT NULL DEFAULT 0,
    is_published TINYINT(1) NOT NULL DEFAULT 1,
    PRIMARY KEY (id),
    KEY ix_gallery_sort (is_published, sort_order)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --- Page-Blocks (Inline-editierbare Texte für statische Bereiche) ---
DROP TABLE IF EXISTS page_blocks;
CREATE TABLE page_blocks (
    slug          VARCHAR(100) NOT NULL,
    title         VARCHAR(255) DEFAULT NULL,
    body          MEDIUMTEXT,
    updated_at    DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    updated_by    INT UNSIGNED DEFAULT NULL,
    PRIMARY KEY (slug)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

SET FOREIGN_KEY_CHECKS = 1;
