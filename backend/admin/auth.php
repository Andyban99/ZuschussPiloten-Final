<?php
/**
 * Zuschuss Piloten - Admin Authentifizierung (Gesichert)
 */

require_once __DIR__ . '/../config.php';

// Sichere Session initialisieren
initSecureSession();

// Session-Timeout prüfen
if (isset($_SESSION['admin_login_time'])) {
    if (time() - $_SESSION['admin_login_time'] > SESSION_LIFETIME) {
        // Session abgelaufen
        logSecurityEvent('admin_session_timeout', [
            'user' => $_SESSION['admin_user'] ?? 'unknown'
        ]);
        session_destroy();
        header('Location: login.php?timeout=1');
        exit;
    }
    // Session verlängern bei Aktivität
    $_SESSION['admin_login_time'] = time();
}

// Prüfen ob eingeloggt
function isLoggedIn() {
    return isset($_SESSION['admin_logged_in']) &&
           $_SESSION['admin_logged_in'] === true &&
           isset($_SESSION['admin_user']);
}

// Login erforderlich
function requireLogin() {
    if (!isLoggedIn()) {
        header('Location: login.php');
        exit;
    }

    // IP-Bindung prüfen (optional aber empfohlen)
    if (isset($_SESSION['admin_ip']) && $_SESSION['admin_ip'] !== getClientIP()) {
        logSecurityEvent('admin_ip_mismatch', [
            'user' => $_SESSION['admin_user'] ?? 'unknown',
            'session_ip' => $_SESSION['admin_ip'],
            'current_ip' => getClientIP()
        ]);
        session_destroy();
        header('Location: login.php?security=1');
        exit;
    }
}

// Login durchführen
function doLogin($username, $password) {
    // Input validieren
    $username = sanitizeInput($username, 'string');

    if (empty($username) || empty($password)) {
        return false;
    }

    // Längenprüfung um DoS zu verhindern
    if (strlen($username) > 100 || strlen($password) > 200) {
        return false;
    }

    // Zuerst: Haupt-Admin aus config.php prüfen
    if ($username === ADMIN_USER) {
        // Da password_hash() jedes Mal einen anderen Hash erzeugt,
        // müssen wir den Hash einmalig generieren und speichern.
        // Für die Entwicklung: Direkter Vergleich (NICHT für Produktion empfohlen!)
        // In Produktion sollte ein fester Hash in der Datenbank gespeichert sein.

        // Temporäre Lösung: Hash bei erstem Aufruf generieren
        $storedHash = ADMIN_PASS_HASH;

        // Sicherer Passwort-Vergleich mit bcrypt
        if (password_verify($password, $storedHash)) {
            setAdminSession($username, 'Super Admin');
            return true;
        }
    }

    // Alternativ: Datenbank-Benutzer prüfen
    try {
        $db = getDB();
        $stmt = $db->prepare("
            SELECT id, benutzername, name, passwort_hash
            FROM admin_benutzer
            WHERE benutzername = :user AND aktiv = 1
            LIMIT 1
        ");
        $stmt->execute([':user' => $username]);
        $user = $stmt->fetch();

        if ($user && password_verify($password, $user['passwort_hash'])) {
            setAdminSession($user['benutzername'], $user['name']);

            // Letzten Login aktualisieren
            $db->prepare("UPDATE admin_benutzer SET letzter_login = NOW() WHERE id = :id")
               ->execute([':id' => $user['id']]);

            return true;
        }
    } catch (PDOException $e) {
        error_log("Login-Fehler: " . $e->getMessage());
    }

    return false;
}

// Admin-Session setzen
function setAdminSession($username, $name) {
    // Session-ID regenerieren um Session Fixation zu verhindern
    session_regenerate_id(true);

    $_SESSION['admin_logged_in'] = true;
    $_SESSION['admin_user'] = $username;
    $_SESSION['admin_name'] = $name;
    $_SESSION['admin_login_time'] = time();
    $_SESSION['admin_ip'] = getClientIP();
    $_SESSION['admin_user_agent'] = $_SERVER['HTTP_USER_AGENT'] ?? '';
}

// Logout
function doLogout() {
    $user = $_SESSION['admin_user'] ?? 'unknown';

    logSecurityEvent('admin_logout', ['user' => $user]);

    // Session-Daten löschen
    $_SESSION = [];

    // Session-Cookie löschen
    if (ini_get("session.use_cookies")) {
        $params = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000,
            $params["path"], $params["domain"],
            $params["secure"], $params["httponly"]
        );
    }

    session_destroy();
}

// Admin-Name für Anzeige
function getAdminDisplayName() {
    return $_SESSION['admin_name'] ?? $_SESSION['admin_user'] ?? 'Admin';
}
