-- Zuschuss Piloten - Kunden-Tabelle Erweiterung 4
-- Unternehmensdaten, wirtschaftlich berechtigte Personen, Durchführungsort, Abschreibungen, Social Media

ALTER TABLE kunden
-- Unternehmensdaten
ADD COLUMN unternehmen_email VARCHAR(255) DEFAULT NULL AFTER unternehmen,
ADD COLUMN rechtsform VARCHAR(100) DEFAULT NULL AFTER unternehmen_email,
ADD COLUMN gruendungsdatum DATE DEFAULT NULL AFTER rechtsform,

-- Wirtschaftlich berechtigte Personen (ersetzt Gesellschafter - JSON)
ADD COLUMN wirtschaftlich_berechtigte JSON DEFAULT NULL AFTER gesellschafter,

-- Durchführungsort des Vorhabens
ADD COLUMN durchfuehrungsort_gleich_adresse TINYINT(1) DEFAULT 1 AFTER wirtschaftlich_berechtigte,
ADD COLUMN durchfuehrungsort_strasse VARCHAR(255) DEFAULT NULL AFTER durchfuehrungsort_gleich_adresse,
ADD COLUMN durchfuehrungsort_hausnummer VARCHAR(20) DEFAULT NULL AFTER durchfuehrungsort_strasse,
ADD COLUMN durchfuehrungsort_plz VARCHAR(10) DEFAULT NULL AFTER durchfuehrungsort_hausnummer,
ADD COLUMN durchfuehrungsort_ort VARCHAR(100) DEFAULT NULL AFTER durchfuehrungsort_plz,

-- Verdiente Abschreibungen (JSON für 3 Jahre)
ADD COLUMN abschreibungen JSON DEFAULT NULL AFTER durchfuehrungsort_ort,

-- Social Media
ADD COLUMN social_youtube VARCHAR(255) DEFAULT NULL AFTER webseite_url,
ADD COLUMN social_instagram VARCHAR(255) DEFAULT NULL AFTER social_youtube,
ADD COLUMN social_linkedin VARCHAR(255) DEFAULT NULL AFTER social_instagram,
ADD COLUMN social_facebook VARCHAR(255) DEFAULT NULL AFTER social_linkedin;
