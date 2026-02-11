<?php
/**
 * Zuschuss Piloten - CSV Export
 */

require_once 'auth.php';
requireLogin();

$db = getDB();

// Alle Anfragen laden
$anfragen = $db->query("
    SELECT id, name, unternehmen, email, telefon, nachricht, status, prioritaet, notizen, erstellt_am, aktualisiert_am
    FROM anfragen
    ORDER BY erstellt_am DESC
")->fetchAll();

// CSV Header
header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment; filename="anfragen_' . date('Y-m-d_H-i') . '.csv"');

// BOM für Excel UTF-8
echo "\xEF\xBB\xBF";

$output = fopen('php://output', 'w');

// Header-Zeile
fputcsv($output, [
    'ID',
    'Name',
    'Unternehmen',
    'E-Mail',
    'Telefon',
    'Nachricht',
    'Status',
    'Priorität',
    'Notizen',
    'Erstellt am',
    'Aktualisiert am'
], ';');

// Daten
foreach ($anfragen as $row) {
    fputcsv($output, [
        $row['id'],
        $row['name'],
        $row['unternehmen'],
        $row['email'],
        $row['telefon'],
        $row['nachricht'],
        $row['status'],
        $row['prioritaet'],
        $row['notizen'],
        $row['erstellt_am'],
        $row['aktualisiert_am']
    ], ';');
}

fclose($output);
