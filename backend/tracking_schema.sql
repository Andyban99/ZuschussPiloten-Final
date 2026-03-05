-- =====================================================
-- Zuschuss Piloten - Tracking System Database Schema
-- DSGVO-konformes, cookieloses Tracking
-- =====================================================

-- Tabelle für Seitenaufrufe
CREATE TABLE IF NOT EXISTS tracking_pageviews (
    id INT AUTO_INCREMENT PRIMARY KEY,
    session_hash VARCHAR(64) NOT NULL COMMENT 'Anonymer Session-Hash (rotiert täglich)',
    seite VARCHAR(255) NOT NULL COMMENT 'Besuchte Seite (URL-Pfad)',
    referrer VARCHAR(500) DEFAULT NULL COMMENT 'Herkunftsseite',
    referrer_domain VARCHAR(255) DEFAULT NULL COMMENT 'Extrahierte Referrer-Domain',
    geraetetyp ENUM('desktop', 'tablet', 'mobile') DEFAULT 'desktop',
    browser VARCHAR(50) DEFAULT NULL COMMENT 'Browser-Name',
    browser_version VARCHAR(20) DEFAULT NULL,
    os VARCHAR(50) DEFAULT NULL COMMENT 'Betriebssystem',
    os_version VARCHAR(20) DEFAULT NULL,
    bildschirmbreite INT DEFAULT NULL,
    bildschirmhoehe INT DEFAULT NULL,
    sprache VARCHAR(10) DEFAULT NULL COMMENT 'Browser-Sprache',
    erstellt_am DATETIME DEFAULT CURRENT_TIMESTAMP,

    INDEX idx_session (session_hash),
    INDEX idx_seite (seite),
    INDEX idx_datum (erstellt_am),
    INDEX idx_geraetetyp (geraetetyp),
    INDEX idx_referrer_domain (referrer_domain)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Tabelle für Events (Button-Klicks, Scroll-Tiefe, etc.)
CREATE TABLE IF NOT EXISTS tracking_events (
    id INT AUTO_INCREMENT PRIMARY KEY,
    session_hash VARCHAR(64) NOT NULL,
    event_typ VARCHAR(50) NOT NULL COMMENT 'Typ: click, scroll, form_submit, etc.',
    event_name VARCHAR(100) NOT NULL COMMENT 'Name des Events',
    event_kategorie VARCHAR(50) DEFAULT NULL COMMENT 'Kategorie: cta, navigation, contact, etc.',
    event_wert VARCHAR(255) DEFAULT NULL COMMENT 'Zusätzlicher Wert (z.B. Scroll-Tiefe in %)',
    seite VARCHAR(255) NOT NULL COMMENT 'Seite auf der das Event ausgelöst wurde',
    element_text VARCHAR(255) DEFAULT NULL COMMENT 'Text des geklickten Elements',
    element_id VARCHAR(100) DEFAULT NULL COMMENT 'ID des Elements',
    element_klassen VARCHAR(255) DEFAULT NULL COMMENT 'CSS-Klassen des Elements',
    erstellt_am DATETIME DEFAULT CURRENT_TIMESTAMP,

    INDEX idx_session (session_hash),
    INDEX idx_event_typ (event_typ),
    INDEX idx_event_name (event_name),
    INDEX idx_kategorie (event_kategorie),
    INDEX idx_datum (erstellt_am),
    INDEX idx_seite (seite)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Tabelle für aggregierte Tagesstatistiken (für schnelle Dashboard-Abfragen)
CREATE TABLE IF NOT EXISTS tracking_daily_stats (
    id INT AUTO_INCREMENT PRIMARY KEY,
    datum DATE NOT NULL,
    seite VARCHAR(255) NOT NULL,
    besucher_unique INT DEFAULT 0 COMMENT 'Eindeutige Besucher (basierend auf Session-Hash)',
    seitenaufrufe INT DEFAULT 0 COMMENT 'Gesamte Seitenaufrufe',
    desktop_besuche INT DEFAULT 0,
    tablet_besuche INT DEFAULT 0,
    mobile_besuche INT DEFAULT 0,
    avg_scroll_tiefe DECIMAL(5,2) DEFAULT NULL COMMENT 'Durchschnittliche Scroll-Tiefe in %',
    cta_klicks INT DEFAULT 0 COMMENT 'CTA-Button Klicks',
    telefon_klicks INT DEFAULT 0,
    email_klicks INT DEFAULT 0,
    aktualisiert_am DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    UNIQUE KEY unique_datum_seite (datum, seite),
    INDEX idx_datum (datum),
    INDEX idx_seite (seite)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Tabelle für Referrer-Statistiken (aggregiert)
CREATE TABLE IF NOT EXISTS tracking_referrer_stats (
    id INT AUTO_INCREMENT PRIMARY KEY,
    datum DATE NOT NULL,
    referrer_domain VARCHAR(255) NOT NULL,
    besuche INT DEFAULT 0,

    UNIQUE KEY unique_datum_referrer (datum, referrer_domain),
    INDEX idx_datum (datum),
    INDEX idx_referrer (referrer_domain)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Tabelle für Browser-Statistiken (aggregiert)
CREATE TABLE IF NOT EXISTS tracking_browser_stats (
    id INT AUTO_INCREMENT PRIMARY KEY,
    datum DATE NOT NULL,
    browser VARCHAR(50) NOT NULL,
    besuche INT DEFAULT 0,

    UNIQUE KEY unique_datum_browser (datum, browser),
    INDEX idx_datum (datum)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
