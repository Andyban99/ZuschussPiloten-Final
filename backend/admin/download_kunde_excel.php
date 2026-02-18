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

$filePath = __DIR__ . '/../uploads/kunden/' . $id . '/' . $kunde['excel_datei'];

if (!file_exists($filePath)) {
    header('Location: kunde_view.php?id=' . $id . '&error=file_not_found');
    exit;
}

// Download
$extension = strtolower(pathinfo($kunde['excel_datei_original'], PATHINFO_EXTENSION));
$mimeTypes = [
    'xlsx' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
    'xls' => 'application/vnd.ms-excel',
    'csv' => 'text/csv'
];

$mimeType = $mimeTypes[$extension] ?? 'application/octet-stream';

header('Content-Type: ' . $mimeType);
header('Content-Disposition: attachment; filename="' . $kunde['excel_datei_original'] . '"');
header('Content-Length: ' . filesize($filePath));

readfile($filePath);
exit;
