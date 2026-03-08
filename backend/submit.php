<?php
/**
 * Zuschuss Piloten - Formular-Verarbeitung (Gesichert)
 */

require_once 'config.php';

// Security Headers setzen
setSecurityHeaders();
setCORSHeaders();

header('Content-Type: application/json; charset=utf-8');

// Nur POST-Anfragen erlauben
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Nur POST-Anfragen erlaubt']);
    exit;
}

// Session starten für Rate Limiting
initSecureSession();

// IP-basiertes Rate Limiting
$clientIP = getClientIP();
if (!checkRateLimit($clientIP, 5, 60)) { // Max 5 Anfragen pro Minute pro IP
    http_response_code(429);
    logSecurityEvent('rate_limit_exceeded', ['ip' => $clientIP]);
    echo json_encode(['success' => false, 'message' => 'Zu viele Anfragen. Bitte warten Sie einen Moment.']);
    exit;
}

// Session-basiertes Rate Limiting (zusätzlich)
$now = time();
if (isset($_SESSION['last_submit']) && ($now - $_SESSION['last_submit']) < 30) {
    http_response_code(429);
    echo json_encode(['success' => false, 'message' => 'Bitte warten Sie 30 Sekunden zwischen Anfragen.']);
    exit;
}

// Daten empfangen
$input = json_decode(file_get_contents('php://input'), true);
if (!$input) {
    $input = $_POST;
}

// Honeypot-Feld prüfen (gegen Spam-Bots)
if (!empty($input['website']) || !empty($input['url']) || !empty($input['fax'])) {
    // Bot erkannt - still ablehnen
    logSecurityEvent('honeypot_triggered', ['ip' => $clientIP]);
    echo json_encode(['success' => true, 'message' => 'Vielen Dank für Ihre Anfrage!']);
    exit;
}

// Zeitstempel-Check (Formular zu schnell ausgefüllt = Bot)
if (isset($input['_timestamp'])) {
    $formTime = intval($input['_timestamp']);
    // JavaScript Date.now() sendet Millisekunden - zu Sekunden konvertieren
    if ($formTime > 9999999999) {
        $formTime = intval($formTime / 1000);
    }
    $currentTime = time();
    if ($currentTime - $formTime < 3) { // Weniger als 3 Sekunden = verdächtig
        logSecurityEvent('form_too_fast', ['ip' => $clientIP, 'time' => $currentTime - $formTime]);
        echo json_encode(['success' => true, 'message' => 'Vielen Dank für Ihre Anfrage!']);
        exit;
    }
}

// Validierung und Sanitization
$errors = [];

$name = sanitizeInput($input['name'] ?? '', 'string');
$unternehmen = sanitizeInput($input['unternehmen'] ?? '', 'string');
$email = sanitizeInput($input['email'] ?? '', 'email');
$telefon = sanitizeInput($input['telefon'] ?? '', 'string');
$nachricht = sanitizeInput($input['nachricht'] ?? '', 'string');

// Längenvalidierung
if (empty($name) || strlen($name) < 2 || strlen($name) > 100) {
    $errors[] = 'Name muss zwischen 2 und 100 Zeichen lang sein';
}

if (empty($unternehmen) || strlen($unternehmen) < 2 || strlen($unternehmen) > 200) {
    $errors[] = 'Unternehmen muss zwischen 2 und 200 Zeichen lang sein';
}

if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
    $errors[] = 'Gültige E-Mail-Adresse ist erforderlich';
}

// Telefon validieren (optional, aber wenn angegeben, Format prüfen)
if (!empty($telefon)) {
    $telefon = preg_replace('/[^0-9+\-\s()]/', '', $telefon);
    if (strlen($telefon) > 30) {
        $errors[] = 'Telefonnummer ist zu lang';
    }
}

// Nachricht Längenbegrenzung
if (strlen($nachricht) > 5000) {
    $errors[] = 'Nachricht ist zu lang (max. 5000 Zeichen)';
}

// Verdächtige Inhalte prüfen (SQL Injection, XSS Patterns)
$suspiciousPatterns = [
    '/(\b(SELECT|INSERT|UPDATE|DELETE|DROP|UNION|ALTER)\b)/i',
    '/<script\b[^>]*>/i',
    '/javascript:/i',
    '/on(click|load|error|mouseover)=/i'
];

$allInput = $name . $unternehmen . $email . $telefon . $nachricht;
foreach ($suspiciousPatterns as $pattern) {
    if (preg_match($pattern, $allInput)) {
        logSecurityEvent('suspicious_input', [
            'ip' => $clientIP,
            'pattern' => $pattern,
            'input' => substr($allInput, 0, 200)
        ]);
        $errors[] = 'Ungültige Zeichen in der Eingabe';
        break;
    }
}

if (!empty($errors)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'errors' => $errors]);
    exit;
}

// In Datenbank speichern
try {
    $db = getDB();

    $stmt = $db->prepare("
        INSERT INTO anfragen (name, unternehmen, email, telefon, nachricht, erstellt_am)
        VALUES (:name, :unternehmen, :email, :telefon, :nachricht, NOW())
    ");

    $stmt->execute([
        ':name' => $name,
        ':unternehmen' => $unternehmen,
        ':email' => $email,
        ':telefon' => $telefon,
        ':nachricht' => $nachricht
    ]);

    $anfrageId = $db->lastInsertId();

    // Rate Limit setzen
    $_SESSION['last_submit'] = $now;

    // E-Mail-Benachrichtigung senden
    if (NOTIFY_ENABLED && defined('NOTIFY_EMAILS') && !empty(NOTIFY_EMAILS)) {
        $subject = "Neue Anfrage von " . e($unternehmen) . " - #" . $anfrageId;
        $body = "
════════════════════════════════════════
   NEUE KONTAKTANFRAGE - #{$anfrageId}
════════════════════════════════════════

KONTAKTDATEN
────────────────────────────────────────
Name:           " . e($name) . "
Unternehmen:    " . e($unternehmen) . "
E-Mail:         " . e($email) . "
Telefon:        " . ($telefon ? e($telefon) : 'Nicht angegeben') . "

NACHRICHT
────────────────────────────────────────
" . ($nachricht ? e($nachricht) : 'Keine Nachricht angegeben') . "

────────────────────────────────────────
Eingegangen am: " . date('d.m.Y \u\m H:i') . " Uhr
IP-Adresse: {$clientIP}

→ Zum Admin-Dashboard:
  https://zuschusspiloten.de/backend/admin/view.php?id={$anfrageId}

════════════════════════════════════════
";

        $headers = [
            'From: ZuschussPiloten Webseite <webseite@zuschusspiloten.de>',
            'Reply-To: ' . $email,
            'Content-Type: text/plain; charset=UTF-8',
            'X-Mailer: ZuschussPiloten-Formular'
        ];

        // An alle konfigurierten E-Mail-Adressen senden
        foreach (NOTIFY_EMAILS as $notifyEmail) {
            @mail($notifyEmail, $subject, $body, implode("\r\n", $headers));
        }
    }

    echo json_encode([
        'success' => true,
        'message' => 'Vielen Dank für Ihre Anfrage! Wir melden uns schnellstmöglich bei Ihnen.'
    ]);

} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Ein Fehler ist aufgetreten. Bitte versuchen Sie es später erneut.']);
    error_log("Datenbankfehler: " . $e->getMessage());
}
