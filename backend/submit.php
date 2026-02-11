<?php
/**
 * Zuschuss Piloten - Formular-Verarbeitung
 */

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST');
header('Access-Control-Allow-Headers: Content-Type');

require_once 'config.php';

// Nur POST-Anfragen erlauben
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Nur POST-Anfragen erlaubt']);
    exit;
}

// Rate Limiting (einfache Version)
session_start();
$now = time();
if (isset($_SESSION['last_submit']) && ($now - $_SESSION['last_submit']) < 30) {
    http_response_code(429);
    echo json_encode(['success' => false, 'message' => 'Bitte warten Sie 30 Sekunden zwischen Anfragen']);
    exit;
}

// Daten empfangen
$input = json_decode(file_get_contents('php://input'), true);
if (!$input) {
    $input = $_POST;
}

// Validierung
$errors = [];

$name = trim($input['name'] ?? '');
$unternehmen = trim($input['unternehmen'] ?? '');
$email = trim($input['email'] ?? '');
$telefon = trim($input['telefon'] ?? '');
$nachricht = trim($input['nachricht'] ?? '');

if (empty($name)) {
    $errors[] = 'Name ist erforderlich';
}

if (empty($unternehmen)) {
    $errors[] = 'Unternehmen ist erforderlich';
}

if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
    $errors[] = 'Gültige E-Mail-Adresse ist erforderlich';
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
        INSERT INTO anfragen (name, unternehmen, email, telefon, nachricht)
        VALUES (:name, :unternehmen, :email, :telefon, :nachricht)
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
    if (NOTIFY_ENABLED && NOTIFY_EMAIL) {
        $subject = "Neue Anfrage von {$unternehmen} - #{$anfrageId}";
        $body = "
Neue Kontaktanfrage erhalten:

Name: {$name}
Unternehmen: {$unternehmen}
E-Mail: {$email}
Telefon: {$telefon}

Nachricht:
{$nachricht}

---
Zum Admin-Dashboard: " . (isset($_SERVER['HTTPS']) ? 'https' : 'http') . "://" . $_SERVER['HTTP_HOST'] . "/backend/admin/
        ";

        $headers = [
            'From: noreply@zuschuss-piloten.de',
            'Reply-To: ' . $email,
            'Content-Type: text/plain; charset=UTF-8'
        ];

        @mail(NOTIFY_EMAIL, $subject, $body, implode("\r\n", $headers));
    }

    echo json_encode([
        'success' => true,
        'message' => 'Vielen Dank für Ihre Anfrage! Wir melden uns schnellstmöglich bei Ihnen.',
        'id' => $anfrageId
    ]);

} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Ein Fehler ist aufgetreten. Bitte versuchen Sie es später erneut.']);
    error_log("Datenbankfehler: " . $e->getMessage());
}
