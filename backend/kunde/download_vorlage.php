<?php
/**
 * Zuschuss Piloten - Excel-Vorlage Download
 */

require_once 'auth.php';
requireKundeLogin();

// CSV-Vorlage erstellen (kann mit Excel geöffnet werden)
$filename = 'Zuschuss_Piloten_Vorlage.csv';

header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment; filename="' . $filename . '"');

// BOM für Excel UTF-8
echo "\xEF\xBB\xBF";

$output = fopen('php://output', 'w');

// Header-Zeile
fputcsv($output, [
    'Investitionsart',
    'Beschreibung',
    'Betrag (EUR)',
    'Geplantes Datum',
    'Lieferant/Anbieter',
    'Anmerkungen'
], ';');

// Beispielzeilen
$beispiele = [
    ['Maschinen & Anlagen', 'Beispiel: CNC-Fräsmaschine', '50000', '2024-06-01', 'Mustermann GmbH', 'Optional'],
    ['Software/IT', 'Beispiel: ERP-System', '15000', '2024-07-01', 'Software AG', ''],
    ['Fahrzeuge', 'Beispiel: Lieferwagen', '35000', '2024-08-01', 'Autohaus XY', ''],
    ['Gebäude/Umbau', 'Beispiel: Hallenerweiterung', '120000', '2024-09-01', 'Baufirma ABC', ''],
    ['Energieeffizienz', 'Beispiel: Solaranlage', '80000', '2024-10-01', 'Solar GmbH', ''],
    ['', '', '', '', '', ''],
    ['', '', '', '', '', ''],
    ['', '', '', '', '', ''],
    ['', '', '', '', '', ''],
    ['', '', '', '', '', ''],
];

foreach ($beispiele as $row) {
    fputcsv($output, $row, ';');
}

// Hinweise am Ende
fputcsv($output, [], ';');
fputcsv($output, ['--- HINWEISE ---'], ';');
fputcsv($output, ['Bitte füllen Sie die Tabelle mit Ihren geplanten Investitionen aus.'], ';');
fputcsv($output, ['Die Beispielzeilen können überschrieben werden.'], ';');
fputcsv($output, ['Speichern Sie die Datei und laden Sie sie in Ihrem Kundenportal hoch.'], ';');

fclose($output);
exit;
