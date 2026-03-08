<?php
/**
 * Zuschuss Piloten - Admin Excel Download
 */

require_once 'auth.php';
requireLogin();

$db = getDB();
$id = (int)($_GET['id'] ?? 0);

if (!$id) {
    header('Location: kunden.php');
    exit;
}

// Kunde laden
$stmt = $db->prepare("SELECT * FROM kunden WHERE id = :id");
$stmt->execute([':id' => $id]);
$kunde = $stmt->fetch();

if (!$kunde || !$kunde['excel_datei']) {
    header('Location: kunde_view.php?id=' . $id);
    exit;
}

// Sicherheits-Check: Path Traversal verhindern
$baseDir = realpath(__DIR__ . '/../uploads/kunden/' . $id);
if (!$baseDir) {
    logSecurityEvent('path_traversal_attempt', ['id' => $id, 'file' => $kunde['excel_datei']]);
    header('Location: kunde_view.php?id=' . $id . '&error=invalid_path');
    exit;
}

$filePath = realpath($baseDir . '/' . basename($kunde['excel_datei']));

// Prüfen ob Datei innerhalb des erlaubten Verzeichnisses liegt
if (!$filePath || strpos($filePath, $baseDir) !== 0) {
    logSecurityEvent('path_traversal_attempt', ['id' => $id, 'file' => $kunde['excel_datei']]);
    header('Location: kunde_view.php?id=' . $id . '&error=invalid_path');
    exit;
}

if (!file_exists($filePath)) {
    header('Location: kunde_view.php?id=' . $id . '&error=file_not_found');
    exit;
}

// Extension Whitelist prüfen
$allowedExtensions = ['xlsx', 'xls', 'csv'];
$extension = strtolower(pathinfo($kunde['excel_datei_original'], PATHINFO_EXTENSION));
if (!in_array($extension, $allowedExtensions)) {
    logSecurityEvent('invalid_file_extension', ['id' => $id, 'extension' => $extension]);
    header('Location: kunde_view.php?id=' . $id . '&error=invalid_file');
    exit;
}

// Dateigröße limitieren (max 50MB)
$maxSize = 50 * 1024 * 1024;
if (filesize($filePath) > $maxSize) {
    header('Location: kunde_view.php?id=' . $id . '&error=file_too_large');
    exit;
}

// Download
$mimeTypes = [
    'xlsx' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
    'xls' => 'application/vnd.ms-excel',
    'csv' => 'text/csv'
];

$mimeType = $mimeTypes[$extension] ?? 'application/octet-stream';

// Sichere Dateinamen für Header (nur basename verwenden)
$safeFilename = basename($kunde['excel_datei_original']);

header('Content-Type: ' . $mimeType);
header('Content-Disposition: attachment; filename="' . $safeFilename . '"');
header('Content-Length: ' . filesize($filePath));
header('X-Content-Type-Options: nosniff');

readfile($filePath);
exit;
