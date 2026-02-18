-- Zuschuss Piloten - Kunden-Tabelle Setup
-- Führen Sie dieses SQL in Ihrer Datenbank aus

CREATE TABLE IF NOT EXISTS kunden (
    id INT AUTO_INCREMENT PRIMARY KEY,
    email VARCHAR(255) NOT NULL UNIQUE,
    passwort_hash VARCHAR(255) NOT NULL,
    vorname VARCHAR(100) NOT NULL,
    nachname VARCHAR(100) NOT NULL,
    unternehmen VARCHAR(255) DEFAULT NULL,
    telefon VARCHAR(50) DEFAULT NULL,
    strasse VARCHAR(255) DEFAULT NULL,
    hausnummer VARCHAR(20) DEFAULT NULL,
    plz VARCHAR(10) DEFAULT NULL,
    ort VARCHAR(100) DEFAULT NULL,
    iban VARCHAR(34) DEFAULT NULL,
    bic VARCHAR(11) DEFAULT NULL,
    bank_name VARCHAR(100) DEFAULT NULL,
    aktiv TINYINT(1) DEFAULT 1,
    erstellt_am DATETIME DEFAULT CURRENT_TIMESTAMP,
    aktualisiert_am DATETIME DEFAULT NULL,
    letzter_login DATETIME DEFAULT NULL,
    INDEX idx_email (email),
    INDEX idx_aktiv (aktiv),
    INDEX idx_erstellt_am (erstellt_am)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Optional: Test-Kunde anlegen (Passwort: Test1234)
-- INSERT INTO kunden (email, passwort_hash, vorname, nachname, erstellt_am)
-- VALUES ('test@example.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Max', 'Mustermann', NOW());
