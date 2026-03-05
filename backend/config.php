<?php
/**
 * Zuschuss Piloten - Konfiguration
 * SICHERHEITSHINWEIS: Diese Datei enthält sensible Daten!
 */

// Fehleranzeige in Produktion ausschalten
error_reporting(0);
ini_set('display_errors', 0);

// Datenbank-Konfiguration
define('DB_HOST', 'database-5019531275.webspace-host.com');
define('DB_NAME', 'dbs15265930');
define('DB_USER', 'dbu2285787');
define('DB_PASS', 'Freunde999...');

// Admin-Zugangsdaten
define('ADMIN_USER', 'admin');
define('ADMIN_PASS_HASH', 'dev_mode');

// E-Mail-Benachrichtigung
define('NOTIFY_EMAILS', ['andrew.banoub@zuschusspiloten.de', 'team@zuschusspiloten.de']);
define('NOTIFY_ENABLED', true);

// Sicherheitseinstellungen
define('SESSION_LIFETIME', 3600); // 1 Stunde
define('CSRF_TOKEN_NAME', 'csrf_token');
define('MAX_LOGIN_ATTEMPTS', 5); // Maximale Loginversuche
define('LOGIN_LOCKOUT_TIME', 900); // 15 Minuten Sperre nach zu vielen Versuchen
define('RATE_LIMIT_REQUESTS', 10); // Max Anfragen
define('RATE_LIMIT_WINDOW', 60); // pro Minute

// Erlaubte Domains für CORS
define('ALLOWED_ORIGINS', [
    'https://zuschusspiloten.de',
    'https://www.zuschusspiloten.de',
    'http://localhost:8000',
    'http://localhost:3000'
]);

// Zeitzone
date_default_timezone_set('Europe/Berlin');

// Sichere Session-Konfiguration
function initSecureSession() {
    if (session_status() === PHP_SESSION_NONE) {
        // Sichere Session-Einstellungen
        ini_set('session.cookie_httponly', 1);
        ini_set('session.cookie_secure', isset($_SERVER['HTTPS']) ? 1 : 0);
        ini_set('session.cookie_samesite', 'Strict');
        ini_set('session.use_strict_mode', 1);
        ini_set('session.use_only_cookies', 1);

        session_start();

        // Session-ID regenerieren bei Login (verhindert Session Fixation)
        if (!isset($_SESSION['initiated'])) {
            session_regenerate_id(true);
            $_SESSION['initiated'] = true;
        }
    }
}

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
                    PDO::ATTR_EMULATE_PREPARES => false,
                    PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8mb4 COLLATE utf8mb4_unicode_ci"
                ]
            );
        } catch (PDOException $e) {
            error_log("Datenbankverbindung fehlgeschlagen: " . $e->getMessage());
            die("Ein Fehler ist aufgetreten. Bitte versuchen Sie es später erneut.");
        }
    }

    return $pdo;
}

// CSRF-Token generieren
function generateCSRFToken() {
    initSecureSession();
    if (empty($_SESSION[CSRF_TOKEN_NAME])) {
        $_SESSION[CSRF_TOKEN_NAME] = bin2hex(random_bytes(32));
    }
    return $_SESSION[CSRF_TOKEN_NAME];
}

// CSRF-Token validieren
function validateCSRFToken($token) {
    if (empty($token) || empty($_SESSION[CSRF_TOKEN_NAME])) {
        return false;
    }
    return hash_equals($_SESSION[CSRF_TOKEN_NAME], $token);
}

// CSRF-Token für Formulare ausgeben
function csrfField() {
    return '<input type="hidden" name="' . CSRF_TOKEN_NAME . '" value="' . generateCSRFToken() . '">';
}

// Sichere Ausgabe (XSS-Schutz)
function e($string) {
    if ($string === null) return '';
    return htmlspecialchars($string, ENT_QUOTES | ENT_HTML5, 'UTF-8');
}

// Input sanitization
function sanitizeInput($input, $type = 'string') {
    if ($input === null) return '';

    $input = trim($input);

    switch ($type) {
        case 'email':
            return filter_var($input, FILTER_SANITIZE_EMAIL);
        case 'int':
            return filter_var($input, FILTER_SANITIZE_NUMBER_INT);
        case 'float':
            return filter_var($input, FILTER_SANITIZE_NUMBER_FLOAT, FILTER_FLAG_ALLOW_FRACTION);
        case 'url':
            return filter_var($input, FILTER_SANITIZE_URL);
        case 'string':
        default:
            // Entferne gefährliche Zeichen, behalte aber Umlaute
            $input = strip_tags($input);
            return $input;
    }
}

// Rate Limiting prüfen
function checkRateLimit($identifier, $maxRequests = null, $windowSeconds = null) {
    initSecureSession();

    $maxRequests = $maxRequests ?? RATE_LIMIT_REQUESTS;
    $windowSeconds = $windowSeconds ?? RATE_LIMIT_WINDOW;

    $key = 'rate_limit_' . md5($identifier);
    $now = time();

    if (!isset($_SESSION[$key])) {
        $_SESSION[$key] = ['count' => 0, 'start' => $now];
    }

    // Fenster zurücksetzen wenn abgelaufen
    if ($now - $_SESSION[$key]['start'] > $windowSeconds) {
        $_SESSION[$key] = ['count' => 0, 'start' => $now];
    }

    $_SESSION[$key]['count']++;

    return $_SESSION[$key]['count'] <= $maxRequests;
}

// Brute-Force-Schutz für Login
function checkLoginAttempts($identifier) {
    initSecureSession();

    $key = 'login_attempts_' . md5($identifier);
    $lockKey = 'login_locked_' . md5($identifier);
    $now = time();

    // Prüfen ob gesperrt
    if (isset($_SESSION[$lockKey]) && $_SESSION[$lockKey] > $now) {
        $remaining = $_SESSION[$lockKey] - $now;
        return ['allowed' => false, 'remaining' => $remaining];
    }

    return ['allowed' => true, 'attempts' => $_SESSION[$key] ?? 0];
}

// Login-Versuch registrieren
function recordLoginAttempt($identifier, $success) {
    initSecureSession();

    $key = 'login_attempts_' . md5($identifier);
    $lockKey = 'login_locked_' . md5($identifier);

    if ($success) {
        // Bei Erfolg: Zähler zurücksetzen
        unset($_SESSION[$key]);
        unset($_SESSION[$lockKey]);
    } else {
        // Bei Fehler: Zähler erhöhen
        if (!isset($_SESSION[$key])) {
            $_SESSION[$key] = 0;
        }
        $_SESSION[$key]++;

        // Bei zu vielen Versuchen: Sperren
        if ($_SESSION[$key] >= MAX_LOGIN_ATTEMPTS) {
            $_SESSION[$lockKey] = time() + LOGIN_LOCKOUT_TIME;
            $_SESSION[$key] = 0;
        }
    }
}

// IP-Adresse ermitteln
function getClientIP() {
    $headers = ['HTTP_CF_CONNECTING_IP', 'HTTP_X_FORWARDED_FOR', 'HTTP_X_REAL_IP', 'REMOTE_ADDR'];

    foreach ($headers as $header) {
        if (!empty($_SERVER[$header])) {
            $ip = $_SERVER[$header];
            // Bei X-Forwarded-For kann es mehrere IPs geben
            if (strpos($ip, ',') !== false) {
                $ip = trim(explode(',', $ip)[0]);
            }
            if (filter_var($ip, FILTER_VALIDATE_IP)) {
                return $ip;
            }
        }
    }

    return $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
}

// Security Headers setzen
function setSecurityHeaders() {
    // Nur wenn noch keine Header gesendet wurden
    if (!headers_sent()) {
        header('X-Content-Type-Options: nosniff');
        header('X-Frame-Options: SAMEORIGIN');
        header('X-XSS-Protection: 1; mode=block');
        header('Referrer-Policy: strict-origin-when-cross-origin');
        header('Permissions-Policy: geolocation=(), microphone=(), camera=()');

        // Content Security Policy
        header("Content-Security-Policy: default-src 'self'; script-src 'self' 'unsafe-inline' https://cdn.tailwindcss.com https://code.iconify.design; style-src 'self' 'unsafe-inline' https://fonts.googleapis.com; font-src 'self' https://fonts.gstatic.com; img-src 'self' data: https:; connect-src 'self'");
    }
}

// CORS-Header setzen
function setCORSHeaders() {
    $origin = $_SERVER['HTTP_ORIGIN'] ?? '';

    if (in_array($origin, ALLOWED_ORIGINS)) {
        header("Access-Control-Allow-Origin: $origin");
    }

    header('Access-Control-Allow-Methods: POST, GET, OPTIONS');
    header('Access-Control-Allow-Headers: Content-Type, X-Requested-With');
    header('Access-Control-Max-Age: 86400');

    // Preflight-Anfragen beantworten
    if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
        http_response_code(204);
        exit;
    }
}

// Logging für Sicherheitsvorfälle
function logSecurityEvent($event, $details = []) {
    $logEntry = [
        'timestamp' => date('Y-m-d H:i:s'),
        'event' => $event,
        'ip' => getClientIP(),
        'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? 'unknown',
        'details' => $details
    ];

    error_log('[SECURITY] ' . json_encode($logEntry));
}
