-- Zuschuss Piloten - Kunden-Tabelle Erweiterung 3
-- Investitionsgüterliste

ALTER TABLE kunden
ADD COLUMN investitionsgueter JSON DEFAULT NULL AFTER geplante_arbeitsplaetze_ausbildung;
