-- =====================================================
-- Zuschuss Piloten - Tracking Schema Update 2
-- Stabiler Besucher-Hash für bessere Personenzählung
-- =====================================================

-- Spalte für stabilen Besucher-Hash hinzufügen
ALTER TABLE tracking_pageviews
ADD COLUMN besucher_hash VARCHAR(64) DEFAULT NULL AFTER id;

-- Index für schnelle Abfragen
ALTER TABLE tracking_pageviews
ADD INDEX idx_besucher_hash (besucher_hash);
