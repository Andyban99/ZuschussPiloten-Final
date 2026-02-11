<?php
/**
 * Zuschuss Piloten - Admin Authentifizierung
 */

session_start();
require_once '../config.php';

// Session-Timeout prüfen
if (isset($_SESSION['admin_login_time'])) {
    if (time() - $_SESSION['admin_login_time'] > SESSION_LIFETIME) {
        session_destroy();
        header('Location: login.php?timeout=1');
        exit;
    }
    $_SESSION['admin_login_time'] = time();
}

// Prüfen ob eingeloggt
function isLoggedIn() {
    return isset($_SESSION['admin_logged_in']) && $_SESSION['admin_logged_in'] === true;
}

// Login erforderlich
function requireLogin() {
    if (!isLoggedIn()) {
        header('Location: login.php');
        exit;
    }
}

// Login durchführen
function doLogin($username, $password) {
    // Einfache Authentifizierung mit config.php Credentials
    if ($username === ADMIN_USER && password_verify($password, ADMIN_PASS_HASH)) {
        $_SESSION['admin_logged_in'] = true;
        $_SESSION['admin_user'] = $username;
        $_SESSION['admin_login_time'] = time();
        return true;
    }

    // Alternativ: Datenbank-Benutzer prüfen
    try {
        $db = getDB();
        $stmt = $db->prepare("SELECT * FROM admin_benutzer WHERE benutzername = :user AND aktiv = 1");
        $stmt->execute([':user' => $username]);
        $user = $stmt->fetch();

        if ($user && password_verify($password, $user['passwort_hash'])) {
            $_SESSION['admin_logged_in'] = true;
            $_SESSION['admin_user'] = $user['benutzername'];
            $_SESSION['admin_name'] = $user['name'];
            $_SESSION['admin_login_time'] = time();

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

// Logout
function doLogout() {
    session_destroy();
}
