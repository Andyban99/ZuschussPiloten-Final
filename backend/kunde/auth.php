<?php
/**
 * Zuschuss Piloten - Kunden Authentifizierung (Gesichert)
 */

require_once __DIR__ . '/../config.php';

// Sichere Session initialisieren
initSecureSession();

// Session-Timeout prüfen
if (isset($_SESSION['kunde_login_time'])) {
    if (time() - $_SESSION['kunde_login_time'] > SESSION_LIFETIME) {
        logSecurityEvent('kunde_session_timeout', [
            'user' => $_SESSION['kunde_email'] ?? 'unknown'
        ]);
        session_destroy();
        header('Location: login.php?timeout=1');
        exit;
    }
    $_SESSION['kunde_login_time'] = time();
}

// Prüfen ob eingeloggt
function isKundeLoggedIn() {
    return isset($_SESSION['kunde_logged_in']) &&
           $_SESSION['kunde_logged_in'] === true &&
           isset($_SESSION['kunde_id']);
}

// Login erforderlich
function requireKundeLogin() {
    if (!isKundeLoggedIn()) {
        header('Location: login.php');
        exit;
    }

    // IP-Bindung prüfen
    if (isset($_SESSION['kunde_ip']) && $_SESSION['kunde_ip'] !== getClientIP()) {
        logSecurityEvent('kunde_ip_mismatch', [
            'user' => $_SESSION['kunde_email'] ?? 'unknown',
            'session_ip' => $_SESSION['kunde_ip'],
            'current_ip' => getClientIP()
        ]);
        session_destroy();
        header('Location: login.php?security=1');
        exit;
    }
}

// Login durchführen
function doKundeLogin($email, $password) {
    $email = sanitizeInput($email, 'email');

    if (empty($email) || empty($password)) {
        return false;
    }

    if (strlen($email) > 255 || strlen($password) > 200) {
        return false;
    }

    try {
        $db = getDB();
        $stmt = $db->prepare("SELECT * FROM kunden WHERE email = :email AND aktiv = 1 LIMIT 1");
        $stmt->execute([':email' => $email]);
        $kunde = $stmt->fetch();

        if ($kunde && password_verify($password, $kunde['passwort_hash'])) {
            session_regenerate_id(true);

            $_SESSION['kunde_logged_in'] = true;
            $_SESSION['kunde_id'] = $kunde['id'];
            $_SESSION['kunde_email'] = $kunde['email'];
            $_SESSION['kunde_name'] = trim($kunde['vorname'] . ' ' . $kunde['nachname']);
            $_SESSION['kunde_login_time'] = time();
            $_SESSION['kunde_ip'] = getClientIP();

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
    $email = sanitizeInput($email, 'email');
    $vorname = sanitizeInput($vorname, 'string');
    $nachname = sanitizeInput($nachname, 'string');

    // Validierung
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        return ['success' => false, 'error' => 'Ungültige E-Mail-Adresse.'];
    }

    if (strlen($email) > 255 || strlen($vorname) > 100 || strlen($nachname) > 100) {
        return ['success' => false, 'error' => 'Eingaben sind zu lang.'];
    }

    if (strlen($password) < 8 || strlen($password) > 200) {
        return ['success' => false, 'error' => 'Passwort muss zwischen 8 und 200 Zeichen sein.'];
    }

    try {
        $db = getDB();

        // Prüfen ob E-Mail bereits existiert
        $stmt = $db->prepare("SELECT id FROM kunden WHERE email = :email LIMIT 1");
        $stmt->execute([':email' => $email]);
        if ($stmt->fetch()) {
            return ['success' => false, 'error' => 'Diese E-Mail-Adresse ist bereits registriert.'];
        }

        // Sicheren Password-Hash erstellen
        $passwort_hash = password_hash($password, PASSWORD_DEFAULT, ['cost' => 12]);

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
    $user = $_SESSION['kunde_email'] ?? 'unknown';
    logSecurityEvent('kunde_logout', ['user' => $user]);

    $_SESSION = [];

    if (ini_get("session.use_cookies")) {
        $params = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000,
            $params["path"], $params["domain"],
            $params["secure"], $params["httponly"]
        );
    }

    session_destroy();
}

// Kundendaten laden
function getKundeData($kunde_id) {
    $kunde_id = intval($kunde_id);
    if ($kunde_id <= 0) return null;

    try {
        $db = getDB();
        $stmt = $db->prepare("SELECT * FROM kunden WHERE id = :id LIMIT 1");
        $stmt->execute([':id' => $kunde_id]);
        return $stmt->fetch();
    } catch (PDOException $e) {
        error_log("Kundendaten-Fehler: " . $e->getMessage());
        return null;
    }
}

// Kundendaten speichern
function saveKundeData($kunde_id, $data) {
    $kunde_id = intval($kunde_id);
    if ($kunde_id <= 0) return false;

    // Alle Daten sanitizen
    $sanitizedData = [];
    foreach ($data as $key => $value) {
        if (is_string($value)) {
            $sanitizedData[$key] = sanitizeInput($value, 'string');
        } else {
            $sanitizedData[$key] = $value;
        }
    }

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
                unternehmen_email = :unternehmen_email,
                rechtsform = :rechtsform,
                gruendungsdatum = :gruendungsdatum,
                webseite_url = :webseite_url,
                social_youtube = :social_youtube,
                social_instagram = :social_instagram,
                social_linkedin = :social_linkedin,
                social_facebook = :social_facebook,
                elster_steuernummer = :elster_steuernummer,
                ust_id = :ust_id,
                w_idnr = :w_idnr,
                branchenschluessel = :branchenschluessel,
                geschaeftsjahre = :geschaeftsjahre,
                wirtschaftlich_berechtigte = :wirtschaftlich_berechtigte,
                gesellschafter = :gesellschafter,
                durchfuehrungsort_gleich_adresse = :durchfuehrungsort_gleich_adresse,
                durchfuehrungsort_strasse = :durchfuehrungsort_strasse,
                durchfuehrungsort_hausnummer = :durchfuehrungsort_hausnummer,
                durchfuehrungsort_plz = :durchfuehrungsort_plz,
                durchfuehrungsort_ort = :durchfuehrungsort_ort,
                abschreibungen = :abschreibungen,
                arbeitsplaetze_frauen = :arbeitsplaetze_frauen,
                arbeitsplaetze_maenner = :arbeitsplaetze_maenner,
                arbeitsplaetze_ausbildung = :arbeitsplaetze_ausbildung,
                arbeitsplaetze_leiharbeiter = :arbeitsplaetze_leiharbeiter,
                geplante_arbeitsplaetze_frauen = :geplante_arbeitsplaetze_frauen,
                geplante_arbeitsplaetze_maenner = :geplante_arbeitsplaetze_maenner,
                geplante_arbeitsplaetze_ausbildung = :geplante_arbeitsplaetze_ausbildung,
                investitionsgueter = :investitionsgueter,
                aktualisiert_am = NOW()
            WHERE id = :id
        ");

        $stmt->execute([
            ':vorname' => $sanitizedData['vorname'] ?? '',
            ':nachname' => $sanitizedData['nachname'] ?? '',
            ':strasse' => $sanitizedData['strasse'] ?? '',
            ':hausnummer' => $sanitizedData['hausnummer'] ?? '',
            ':plz' => $sanitizedData['plz'] ?? '',
            ':ort' => $sanitizedData['ort'] ?? '',
            ':telefon' => $sanitizedData['telefon'] ?? '',
            ':iban' => $sanitizedData['iban'] ?? '',
            ':bic' => $sanitizedData['bic'] ?? '',
            ':bank_name' => $sanitizedData['bank_name'] ?? '',
            ':unternehmen' => $sanitizedData['unternehmen'] ?? '',
            ':unternehmen_email' => $sanitizedData['unternehmen_email'] ?? '',
            ':rechtsform' => $sanitizedData['rechtsform'] ?? '',
            ':gruendungsdatum' => !empty($sanitizedData['gruendungsdatum']) ? $sanitizedData['gruendungsdatum'] : null,
            ':webseite_url' => $sanitizedData['webseite_url'] ?? '',
            ':social_youtube' => $sanitizedData['social_youtube'] ?? '',
            ':social_instagram' => $sanitizedData['social_instagram'] ?? '',
            ':social_linkedin' => $sanitizedData['social_linkedin'] ?? '',
            ':social_facebook' => $sanitizedData['social_facebook'] ?? '',
            ':elster_steuernummer' => $sanitizedData['elster_steuernummer'] ?? '',
            ':ust_id' => $sanitizedData['ust_id'] ?? '',
            ':w_idnr' => $sanitizedData['w_idnr'] ?? '',
            ':branchenschluessel' => $sanitizedData['branchenschluessel'] ?? '',
            ':geschaeftsjahre' => $sanitizedData['geschaeftsjahre'] ?? null,
            ':wirtschaftlich_berechtigte' => $sanitizedData['wirtschaftlich_berechtigte'] ?? null,
            ':gesellschafter' => $sanitizedData['gesellschafter'] ?? null,
            ':durchfuehrungsort_gleich_adresse' => $sanitizedData['durchfuehrungsort_gleich_adresse'] ?? 1,
            ':durchfuehrungsort_strasse' => $sanitizedData['durchfuehrungsort_strasse'] ?? '',
            ':durchfuehrungsort_hausnummer' => $sanitizedData['durchfuehrungsort_hausnummer'] ?? '',
            ':durchfuehrungsort_plz' => $sanitizedData['durchfuehrungsort_plz'] ?? '',
            ':durchfuehrungsort_ort' => $sanitizedData['durchfuehrungsort_ort'] ?? '',
            ':abschreibungen' => $sanitizedData['abschreibungen'] ?? null,
            ':arbeitsplaetze_frauen' => isset($sanitizedData['arbeitsplaetze_frauen']) && $sanitizedData['arbeitsplaetze_frauen'] !== '' ? intval($sanitizedData['arbeitsplaetze_frauen']) : null,
            ':arbeitsplaetze_maenner' => isset($sanitizedData['arbeitsplaetze_maenner']) && $sanitizedData['arbeitsplaetze_maenner'] !== '' ? intval($sanitizedData['arbeitsplaetze_maenner']) : null,
            ':arbeitsplaetze_ausbildung' => isset($sanitizedData['arbeitsplaetze_ausbildung']) && $sanitizedData['arbeitsplaetze_ausbildung'] !== '' ? intval($sanitizedData['arbeitsplaetze_ausbildung']) : null,
            ':arbeitsplaetze_leiharbeiter' => isset($sanitizedData['arbeitsplaetze_leiharbeiter']) && $sanitizedData['arbeitsplaetze_leiharbeiter'] !== '' ? intval($sanitizedData['arbeitsplaetze_leiharbeiter']) : null,
            ':geplante_arbeitsplaetze_frauen' => isset($sanitizedData['geplante_arbeitsplaetze_frauen']) && $sanitizedData['geplante_arbeitsplaetze_frauen'] !== '' ? intval($sanitizedData['geplante_arbeitsplaetze_frauen']) : null,
            ':geplante_arbeitsplaetze_maenner' => isset($sanitizedData['geplante_arbeitsplaetze_maenner']) && $sanitizedData['geplante_arbeitsplaetze_maenner'] !== '' ? intval($sanitizedData['geplante_arbeitsplaetze_maenner']) : null,
            ':geplante_arbeitsplaetze_ausbildung' => isset($sanitizedData['geplante_arbeitsplaetze_ausbildung']) && $sanitizedData['geplante_arbeitsplaetze_ausbildung'] !== '' ? intval($sanitizedData['geplante_arbeitsplaetze_ausbildung']) : null,
            ':investitionsgueter' => $sanitizedData['investitionsgueter'] ?? null,
            ':id' => $kunde_id
        ]);
        return true;
    } catch (PDOException $e) {
        error_log("Kundendaten-Speichern-Fehler: " . $e->getMessage());
        return false;
    }
}

// Sicherer Excel-Datei Upload
function saveKundeExcel($kunde_id, $file) {
    $kunde_id = intval($kunde_id);
    if ($kunde_id <= 0) {
        return ['success' => false, 'error' => 'Ungültige Kunden-ID.'];
    }

    // Prüfen ob Datei-Upload erfolgreich
    if (!isset($file['error']) || $file['error'] !== UPLOAD_ERR_OK) {
        $uploadErrors = [
            UPLOAD_ERR_INI_SIZE => 'Die Datei ist zu groß.',
            UPLOAD_ERR_FORM_SIZE => 'Die Datei ist zu groß.',
            UPLOAD_ERR_PARTIAL => 'Die Datei wurde nur teilweise hochgeladen.',
            UPLOAD_ERR_NO_FILE => 'Keine Datei ausgewählt.',
            UPLOAD_ERR_NO_TMP_DIR => 'Server-Fehler beim Upload.',
            UPLOAD_ERR_CANT_WRITE => 'Server-Fehler beim Speichern.',
            UPLOAD_ERR_EXTENSION => 'Upload durch Server blockiert.'
        ];
        $errorMsg = $uploadErrors[$file['error']] ?? 'Unbekannter Upload-Fehler.';
        return ['success' => false, 'error' => $errorMsg];
    }

    try {
        $originalName = basename($file['name']);
        $extension = strtolower(pathinfo($originalName, PATHINFO_EXTENSION));

        // Erlaubte Dateitypen
        $allowedExtensions = ['xlsx', 'xls', 'csv'];
        $allowedMimeTypes = [
            'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'application/vnd.ms-excel',
            'text/csv',
            'application/csv',
            'text/plain'
        ];

        // Extension prüfen
        if (!in_array($extension, $allowedExtensions)) {
            logSecurityEvent('invalid_file_upload', [
                'kunde_id' => $kunde_id,
                'extension' => $extension,
                'ip' => getClientIP()
            ]);
            return ['success' => false, 'error' => 'Nur Excel-Dateien (.xlsx, .xls) oder CSV-Dateien sind erlaubt.'];
        }

        // MIME-Type prüfen
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mimeType = finfo_file($finfo, $file['tmp_name']);
        finfo_close($finfo);

        if (!in_array($mimeType, $allowedMimeTypes)) {
            logSecurityEvent('invalid_file_mime', [
                'kunde_id' => $kunde_id,
                'mime' => $mimeType,
                'ip' => getClientIP()
            ]);
            return ['success' => false, 'error' => 'Ungültiger Dateityp.'];
        }

        // Dateigröße prüfen (max 10MB)
        $maxSize = 10 * 1024 * 1024;
        if ($file['size'] > $maxSize) {
            return ['success' => false, 'error' => 'Die Datei ist zu groß (max. 10MB).'];
        }

        // Dateinamen sanitizen (keine Sonderzeichen)
        $safeOriginalName = preg_replace('/[^a-zA-Z0-9._-]/', '_', $originalName);

        // Upload-Verzeichnis
        $uploadDir = __DIR__ . '/../uploads/kunden/' . $kunde_id . '/';
        if (!is_dir($uploadDir)) {
            if (!mkdir($uploadDir, 0755, true)) {
                return ['success' => false, 'error' => 'Server-Fehler: Verzeichnis konnte nicht erstellt werden.'];
            }
        }

        // .htaccess zum Schutz des Upload-Verzeichnisses
        $htaccessPath = __DIR__ . '/../uploads/.htaccess';
        if (!file_exists($htaccessPath)) {
            file_put_contents($htaccessPath, "# Prevent PHP execution\n<FilesMatch \"\\.php$\">\n    Order allow,deny\n    Deny from all\n</FilesMatch>\n\n# Prevent direct access\nOptions -Indexes\n");
        }

        // Eindeutigen Dateinamen generieren
        $newFileName = 'daten_' . date('Y-m-d_H-i-s') . '_' . bin2hex(random_bytes(4)) . '.' . $extension;
        $targetPath = $uploadDir . $newFileName;

        // Datei verschieben
        if (move_uploaded_file($file['tmp_name'], $targetPath)) {
            // Dateiberechtigungen setzen
            chmod($targetPath, 0644);

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
                ':original' => $safeOriginalName,
                ':id' => $kunde_id
            ]);

            logSecurityEvent('file_upload_success', [
                'kunde_id' => $kunde_id,
                'file' => $newFileName,
                'ip' => getClientIP()
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
    $kunde_id = intval($kunde_id);
    if ($kunde_id <= 0) return false;

    try {
        $db = getDB();
        $kunde = getKundeData($kunde_id);

        if ($kunde && !empty($kunde['excel_datei'])) {
            $filePath = __DIR__ . '/../uploads/kunden/' . $kunde_id . '/' . basename($kunde['excel_datei']);
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
