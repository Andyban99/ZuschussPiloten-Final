-- Zuschuss Piloten - Kunden-Tabelle Erweiterung
-- Führen Sie dieses SQL in Ihrer Datenbank aus

ALTER TABLE kunden
ADD COLUMN hat_webseite TINYINT(1) DEFAULT 0 AFTER bank_name,
ADD COLUMN webseite_url VARCHAR(255) DEFAULT NULL AFTER hat_webseite,
ADD COLUMN hat_elster_steuernummer TINYINT(1) DEFAULT 0 AFTER webseite_url,
ADD COLUMN elster_steuernummer VARCHAR(50) DEFAULT NULL AFTER hat_elster_steuernummer,
ADD COLUMN ust_id VARCHAR(20) DEFAULT NULL AFTER elster_steuernummer,
ADD COLUMN w_idnr VARCHAR(20) DEFAULT NULL AFTER ust_id,
ADD COLUMN branchenschluessel VARCHAR(20) DEFAULT NULL AFTER w_idnr,
ADD COLUMN excel_datei VARCHAR(255) DEFAULT NULL AFTER branchenschluessel,
ADD COLUMN excel_datei_original VARCHAR(255) DEFAULT NULL AFTER excel_datei,
ADD COLUMN excel_hochgeladen_am DATETIME DEFAULT NULL AFTER excel_datei_original;
