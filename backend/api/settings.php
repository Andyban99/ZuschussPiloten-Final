<?php
/**
 * Zuschuss Piloten - Settings API
 */

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

$settingsFile = __DIR__ . '/../settings.json';

if (file_exists($settingsFile)) {
    $settings = json_decode(file_get_contents($settingsFile), true);
    echo json_encode([
        'kundenportal_aktiv' => $settings['kundenportal_aktiv'] ?? true
    ]);
} else {
    echo json_encode([
        'kundenportal_aktiv' => true
    ]);
}
