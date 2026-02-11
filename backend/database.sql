-- Zuschuss Piloten - Datenbank-Setup
-- Führe dieses Script in phpMyAdmin oder MySQL CLI aus

CREATE DATABASE IF NOT EXISTS zuschuss_piloten CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

USE zuschuss_piloten;

-- Tabelle für Kontaktanfragen
CREATE TABLE IF NOT EXISTS anfragen (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    unternehmen VARCHAR(255) NOT NULL,
    email VARCHAR(255) NOT NULL,
    telefon VARCHAR(50),
    nachricht TEXT,
    status ENUM('neu', 'in_bearbeitung', 'erledigt', 'archiviert') DEFAULT 'neu',
    notizen TEXT,
    prioritaet ENUM('normal', 'hoch', 'dringend') DEFAULT 'normal',
    erstellt_am DATETIME DEFAULT CURRENT_TIMESTAMP,
    aktualisiert_am DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    bearbeitet_von VARCHAR(100),
    INDEX idx_status (status),
    INDEX idx_erstellt (erstellt_am),
    INDEX idx_email (email)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Tabelle für Admin-Aktivitäten (Logging)
CREATE TABLE IF NOT EXISTS aktivitaeten (
    id INT AUTO_INCREMENT PRIMARY KEY,
    anfrage_id INT,
    aktion VARCHAR(100) NOT NULL,
    details TEXT,
    benutzer VARCHAR(100),
    erstellt_am DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (anfrage_id) REFERENCES anfragen(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Tabelle für Admin-Benutzer (optional, für mehrere Benutzer)
CREATE TABLE IF NOT EXISTS admin_benutzer (
    id INT AUTO_INCREMENT PRIMARY KEY,
    benutzername VARCHAR(50) NOT NULL UNIQUE,
    passwort_hash VARCHAR(255) NOT NULL,
    name VARCHAR(100),
    email VARCHAR(255),
    aktiv BOOLEAN DEFAULT TRUE,
    letzter_login DATETIME,
    erstellt_am DATETIME DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Standard-Admin-Benutzer einfügen
INSERT INTO admin_benutzer (benutzername, passwort_hash, name, email) VALUES
('admin', '$2y$10$YourHashedPasswordHere', 'Administrator', 'admin@zuschuss-piloten.de')
ON DUPLICATE KEY UPDATE benutzername = benutzername;
