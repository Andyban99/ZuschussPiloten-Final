-- Zuschuss Piloten - Kunden-Tabelle Erweiterung 2
-- Geschäftsjahre, Gesellschafter und Arbeitsplätze

ALTER TABLE kunden
-- Geschäftsjahre (JSON für flexible Speicherung)
ADD COLUMN geschaeftsjahre JSON DEFAULT NULL AFTER branchenschluessel,

-- Gesellschafter (JSON-Array mit Name und Beteiligung)
ADD COLUMN gesellschafter JSON DEFAULT NULL AFTER geschaeftsjahre,

-- Vorhandene Dauerarbeitsplätze bei Antragstellung (in VZÄ)
ADD COLUMN arbeitsplaetze_frauen DECIMAL(10,2) DEFAULT NULL AFTER gesellschafter,
ADD COLUMN arbeitsplaetze_maenner DECIMAL(10,2) DEFAULT NULL AFTER arbeitsplaetze_frauen,
ADD COLUMN arbeitsplaetze_ausbildung DECIMAL(10,2) DEFAULT NULL AFTER arbeitsplaetze_maenner,
ADD COLUMN arbeitsplaetze_leiharbeiter DECIMAL(10,2) DEFAULT NULL AFTER arbeitsplaetze_ausbildung,

-- Geplante zusätzliche Arbeitsplätze nach Investition (in VZÄ)
ADD COLUMN geplante_arbeitsplaetze_frauen DECIMAL(10,2) DEFAULT NULL AFTER arbeitsplaetze_leiharbeiter,
ADD COLUMN geplante_arbeitsplaetze_maenner DECIMAL(10,2) DEFAULT NULL AFTER geplante_arbeitsplaetze_frauen,
ADD COLUMN geplante_arbeitsplaetze_ausbildung DECIMAL(10,2) DEFAULT NULL AFTER geplante_arbeitsplaetze_maenner;
