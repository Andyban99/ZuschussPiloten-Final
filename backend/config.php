<?php
/**
 * Zuschuss Piloten - Konfiguration
 */

// Datenbank-Konfiguration
define('DB_HOST', 'localhost');
define('DB_NAME', 'zuschuss_piloten');
define('DB_USER', 'root');
define('DB_PASS', '');

// Admin-Zugangsdaten (bitte ändern!)
define('ADMIN_USER', 'admin');
define('ADMIN_PASS_HASH', password_hash('zuschuss2024!', PASSWORD_DEFAULT)); // Passwort: zuschuss2024!

// E-Mail-Benachrichtigung
define('NOTIFY_EMAIL', 'info@zuschuss-piloten.de');
define('NOTIFY_ENABLED', true);

// Sicherheit
define('SESSION_LIFETIME', 3600); // 1 Stunde
define('CSRF_TOKEN_NAME', 'csrf_token');

// Zeitzone
date_default_timezone_set('Europe/Berlin');

// Datenbankverbindung
function getDB() {
    static $pdo = null;

    if ($pdo === null) {
        try {
            $pdo = new PDO(
                "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4",
                DB_USER,
                DB_PASS,
                [
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                    PDO::ATTR_EMULATE_PREPARES => false
                ]
            );
        } catch (PDOException $e) {
            die("Datenbankverbindung fehlgeschlagen: " . $e->getMessage());
        }
    }

    return $pdo;
}

// CSRF-Token generieren
function generateCSRFToken() {
    if (empty($_SESSION[CSRF_TOKEN_NAME])) {
        $_SESSION[CSRF_TOKEN_NAME] = bin2hex(random_bytes(32));
    }
    return $_SESSION[CSRF_TOKEN_NAME];
}

// CSRF-Token validieren
function validateCSRFToken($token) {
    return isset($_SESSION[CSRF_TOKEN_NAME]) && hash_equals($_SESSION[CSRF_TOKEN_NAME], $token);
}

// Sichere Ausgabe
function e($string) {
    return htmlspecialchars($string, ENT_QUOTES, 'UTF-8');
}
