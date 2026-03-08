-- =====================================================
-- Zuschuss Piloten - Tracking Schema Update
-- Standort-Tracking (DSGVO-konform: nur Land/Region, keine IP)
-- =====================================================

-- Spalten für Standort in pageviews hinzufügen
ALTER TABLE tracking_pageviews
ADD COLUMN land VARCHAR(100) DEFAULT NULL AFTER sprache,
ADD COLUMN region VARCHAR(100) DEFAULT NULL AFTER land,
ADD COLUMN stadt VARCHAR(100) DEFAULT NULL AFTER region;

-- Index für Standort-Abfragen
ALTER TABLE tracking_pageviews
ADD INDEX idx_land (land),
ADD INDEX idx_region (region);

-- Neue Tabelle für Standort-Statistiken (aggregiert)
CREATE TABLE IF NOT EXISTS tracking_location_stats (
    id INT AUTO_INCREMENT PRIMARY KEY,
    datum DATE NOT NULL,
    land VARCHAR(100) NOT NULL,
    region VARCHAR(100) DEFAULT NULL,
    besuche INT DEFAULT 0,

    UNIQUE KEY unique_datum_land_region (datum, land, region),
    INDEX idx_datum (datum),
    INDEX idx_land (land)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
