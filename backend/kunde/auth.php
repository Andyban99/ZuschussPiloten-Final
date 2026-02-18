<?php
/**
 * Zuschuss Piloten - Kunden Authentifizierung
 */

session_start();
require_once '../config.php';

// Session-Timeout prüfen
if (isset($_SESSION['kunde_login_time'])) {
    if (time() - $_SESSION['kunde_login_time'] > SESSION_LIFETIME) {
        session_destroy();
        header('Location: login.php?timeout=1');
        exit;
    }
    $_SESSION['kunde_login_time'] = time();
}

// Prüfen ob eingeloggt
function isKundeLoggedIn() {
    return isset($_SESSION['kunde_logged_in']) && $_SESSION['kunde_logged_in'] === true;
}

// Login erforderlich
function requireKundeLogin() {
    if (!isKundeLoggedIn()) {
        header('Location: login.php');
        exit;
    }
}

// Login durchführen
function doKundeLogin($email, $password) {
    try {
        $db = getDB();
        $stmt = $db->prepare("SELECT * FROM kunden WHERE email = :email AND aktiv = 1");
        $stmt->execute([':email' => $email]);
        $kunde = $stmt->fetch();

        if ($kunde && password_verify($password, $kunde['passwort_hash'])) {
            $_SESSION['kunde_logged_in'] = true;
            $_SESSION['kunde_id'] = $kunde['id'];
            $_SESSION['kunde_email'] = $kunde['email'];
            $_SESSION['kunde_name'] = trim($kunde['vorname'] . ' ' . $kunde['nachname']);
            $_SESSION['kunde_login_time'] = time();

            // Letzten Login aktualisieren
            $db->prepare("UPDATE kunden SET letzter_login = NOW() WHERE id = :id")
               ->execute([':id' => $kunde['id']]);

            return true;
        }
    } catch (PDOException $e) {
        error_log("Kunden-Login-Fehler: " . $e->getMessage());
    }

    return false;
}

// Registrierung durchführen
function doKundeRegister($email, $password, $vorname, $nachname) {
    try {
        $db = getDB();

        // Prüfen ob E-Mail bereits existiert
        $stmt = $db->prepare("SELECT id FROM kunden WHERE email = :email");
        $stmt->execute([':email' => $email]);
        if ($stmt->fetch()) {
            return ['success' => false, 'error' => 'Diese E-Mail-Adresse ist bereits registriert.'];
        }

        // Neuen Kunden anlegen
        $passwort_hash = password_hash($password, PASSWORD_DEFAULT);
        $stmt = $db->prepare("
            INSERT INTO kunden (email, passwort_hash, vorname, nachname, erstellt_am, aktiv)
            VALUES (:email, :passwort_hash, :vorname, :nachname, NOW(), 1)
        ");
        $stmt->execute([
            ':email' => $email,
            ':passwort_hash' => $passwort_hash,
            ':vorname' => $vorname,
            ':nachname' => $nachname
        ]);

        return ['success' => true, 'kunde_id' => $db->lastInsertId()];
    } catch (PDOException $e) {
        error_log("Kunden-Registrierung-Fehler: " . $e->getMessage());
        return ['success' => false, 'error' => 'Ein Fehler ist aufgetreten. Bitte versuchen Sie es später erneut.'];
    }
}

// Logout
function doKundeLogout() {
    session_destroy();
}

// Kundendaten laden
function getKundeData($kunde_id) {
    try {
        $db = getDB();
        $stmt = $db->prepare("SELECT * FROM kunden WHERE id = :id");
        $stmt->execute([':id' => $kunde_id]);
        return $stmt->fetch();
    } catch (PDOException $e) {
        error_log("Kundendaten-Fehler: " . $e->getMessage());
        return null;
    }
}

// Kundendaten speichern
function saveKundeData($kunde_id, $data) {
    try {
        $db = getDB();
        $stmt = $db->prepare("
            UPDATE kunden SET
                vorname = :vorname,
                nachname = :nachname,
                strasse = :strasse,
                hausnummer = :hausnummer,
                plz = :plz,
                ort = :ort,
                telefon = :telefon,
                iban = :iban,
                bic = :bic,
                bank_name = :bank_name,
                unternehmen = :unternehmen,
                hat_webseite = :hat_webseite,
                webseite_url = :webseite_url,
                hat_elster_steuernummer = :hat_elster_steuernummer,
                elster_steuernummer = :elster_steuernummer,
                ust_id = :ust_id,
                w_idnr = :w_idnr,
                branchenschluessel = :branchenschluessel,
                aktualisiert_am = NOW()
            WHERE id = :id
        ");
        $stmt->execute([
            ':vorname' => $data['vorname'],
            ':nachname' => $data['nachname'],
            ':strasse' => $data['strasse'] ?? '',
            ':hausnummer' => $data['hausnummer'] ?? '',
            ':plz' => $data['plz'] ?? '',
            ':ort' => $data['ort'] ?? '',
            ':telefon' => $data['telefon'] ?? '',
            ':iban' => $data['iban'] ?? '',
            ':bic' => $data['bic'] ?? '',
            ':bank_name' => $data['bank_name'] ?? '',
            ':unternehmen' => $data['unternehmen'] ?? '',
            ':hat_webseite' => $data['hat_webseite'] ?? 0,
            ':webseite_url' => $data['webseite_url'] ?? '',
            ':hat_elster_steuernummer' => $data['hat_elster_steuernummer'] ?? 0,
            ':elster_steuernummer' => $data['elster_steuernummer'] ?? '',
            ':ust_id' => $data['ust_id'] ?? '',
            ':w_idnr' => $data['w_idnr'] ?? '',
            ':branchenschluessel' => $data['branchenschluessel'] ?? '',
            ':id' => $kunde_id
        ]);
        return true;
    } catch (PDOException $e) {
        error_log("Kundendaten-Speichern-Fehler: " . $e->getMessage());
        return false;
    }
}

// Excel-Datei speichern
function saveKundeExcel($kunde_id, $file) {
    try {
        // Upload-Verzeichnis
        $uploadDir = __DIR__ . '/../uploads/kunden/' . $kunde_id . '/';
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0755, true);
        }

        // Dateiname generieren
        $originalName = $file['name'];
        $extension = strtolower(pathinfo($originalName, PATHINFO_EXTENSION));

        // Nur Excel-Dateien erlauben
        if (!in_array($extension, ['xlsx', 'xls', 'csv'])) {
            return ['success' => false, 'error' => 'Nur Excel-Dateien (.xlsx, .xls) oder CSV-Dateien sind erlaubt.'];
        }

        $newFileName = 'daten_' . date('Y-m-d_H-i-s') . '.' . $extension;
        $targetPath = $uploadDir . $newFileName;

        // Datei verschieben
        if (move_uploaded_file($file['tmp_name'], $targetPath)) {
            // In Datenbank speichern
            $db = getDB();
            $stmt = $db->prepare("
                UPDATE kunden SET
                    excel_datei = :datei,
                    excel_datei_original = :original,
                    excel_hochgeladen_am = NOW()
                WHERE id = :id
            ");
            $stmt->execute([
                ':datei' => $newFileName,
                ':original' => $originalName,
                ':id' => $kunde_id
            ]);
            return ['success' => true];
        } else {
            return ['success' => false, 'error' => 'Fehler beim Hochladen der Datei.'];
        }
    } catch (Exception $e) {
        error_log("Excel-Upload-Fehler: " . $e->getMessage());
        return ['success' => false, 'error' => 'Ein Fehler ist aufgetreten.'];
    }
}

// Excel-Datei löschen
function deleteKundeExcel($kunde_id) {
    try {
        $db = getDB();
        $kunde = getKundeData($kunde_id);

        if ($kunde && $kunde['excel_datei']) {
            $filePath = __DIR__ . '/../uploads/kunden/' . $kunde_id . '/' . $kunde['excel_datei'];
            if (file_exists($filePath)) {
                unlink($filePath);
            }

            $stmt = $db->prepare("
                UPDATE kunden SET
                    excel_datei = NULL,
                    excel_datei_original = NULL,
                    excel_hochgeladen_am = NULL
                WHERE id = :id
            ");
            $stmt->execute([':id' => $kunde_id]);
        }
        return true;
    } catch (Exception $e) {
        error_log("Excel-Löschen-Fehler: " . $e->getMessage());
        return false;
    }
}
