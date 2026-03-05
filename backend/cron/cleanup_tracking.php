<?php
/**
 * Zuschuss Piloten - Tracking Cleanup Cronjob
 * Löscht Tracking-Daten älter als 90 Tage (DSGVO-Konformität)
 *
 * Einrichtung als Cronjob:
 * 0 3 * * * /usr/bin/php /pfad/zu/backend/cron/cleanup_tracking.php
 *
 * (Täglich um 3:00 Uhr)
 */

// CLI-Modus prüfen
if (php_sapi_name() !== 'cli' && !defined('CRON_ALLOWED')) {
    http_response_code(403);
    exit('Zugriff verweigert');
}

require_once __DIR__ . '/../config.php';

// Logging-Funktion
function logMessage($message) {
    $timestamp = date('Y-m-d H:i:s');
    echo "[{$timestamp}] {$message}\n";
    error_log("[Tracking Cleanup] {$message}");
}

try {
    logMessage("Starte Tracking-Cleanup...");

    $db = getDB();

    // Grenzwert: 90 Tage zurück
    $grenzDatum = date('Y-m-d', strtotime('-90 days'));
    logMessage("Lösche Daten vor: {$grenzDatum}");

    // 1. Pageviews löschen
    $stmt = $db->prepare("DELETE FROM tracking_pageviews WHERE DATE(erstellt_am) < :datum");
    $stmt->execute([':datum' => $grenzDatum]);
    $pageviewsGeloescht = $stmt->rowCount();
    logMessage("Pageviews gelöscht: {$pageviewsGeloescht}");

    // 2. Events löschen
    $stmt = $db->prepare("DELETE FROM tracking_events WHERE DATE(erstellt_am) < :datum");
    $stmt->execute([':datum' => $grenzDatum]);
    $eventsGeloescht = $stmt->rowCount();
    logMessage("Events gelöscht: {$eventsGeloescht}");

    // 3. Tagesstatistiken löschen
    $stmt = $db->prepare("DELETE FROM tracking_daily_stats WHERE datum < :datum");
    $stmt->execute([':datum' => $grenzDatum]);
    $statsGeloescht = $stmt->rowCount();
    logMessage("Tagesstatistiken gelöscht: {$statsGeloescht}");

    // 4. Referrer-Statistiken löschen
    $stmt = $db->prepare("DELETE FROM tracking_referrer_stats WHERE datum < :datum");
    $stmt->execute([':datum' => $grenzDatum]);
    $referrerGeloescht = $stmt->rowCount();
    logMessage("Referrer-Statistiken gelöscht: {$referrerGeloescht}");

    // 5. Browser-Statistiken löschen
    $stmt = $db->prepare("DELETE FROM tracking_browser_stats WHERE datum < :datum");
    $stmt->execute([':datum' => $grenzDatum]);
    $browserGeloescht = $stmt->rowCount();
    logMessage("Browser-Statistiken gelöscht: {$browserGeloescht}");

    // Zusammenfassung
    $gesamt = $pageviewsGeloescht + $eventsGeloescht + $statsGeloescht + $referrerGeloescht + $browserGeloescht;
    logMessage("Cleanup abgeschlossen. Insgesamt {$gesamt} Einträge gelöscht.");

    // Optional: Tabellen optimieren (bei großen Datenmengen)
    if ($gesamt > 1000) {
        logMessage("Optimiere Tabellen...");
        $db->exec("OPTIMIZE TABLE tracking_pageviews, tracking_events, tracking_daily_stats, tracking_referrer_stats, tracking_browser_stats");
        logMessage("Tabellen optimiert.");
    }

} catch (PDOException $e) {
    logMessage("FEHLER: " . $e->getMessage());
    exit(1);
}

logMessage("Script beendet.");
exit(0);
