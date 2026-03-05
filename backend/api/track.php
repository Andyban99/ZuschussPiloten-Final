<?php
/**
 * Zuschuss Piloten - Tracking API Endpoint
 * DSGVO-konformes, cookieloses Tracking
 *
 * Empfängt Tracking-Daten via POST (JSON)
 * Keine IP-Speicherung, anonymer Session-Hash
 */

require_once __DIR__ . '/../config.php';

// CORS und Security Headers
setCORSHeaders();
header('Content-Type: application/json; charset=utf-8');

// Nur POST-Anfragen erlauben
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed']);
    exit;
}

// Rate Limiting (30 Requests pro Minute)
$rateLimitKey = 'tracking_' . substr(hash('sha256', getClientIP() . date('Y-m-d')), 0, 16);
if (!checkRateLimit($rateLimitKey, 30, 60)) {
    http_response_code(429);
    echo json_encode(['error' => 'Too many requests']);
    exit;
}

// JSON-Daten lesen
$rawInput = file_get_contents('php://input');
$data = json_decode($rawInput, true);

if (json_last_error() !== JSON_ERROR_NONE || !is_array($data)) {
    http_response_code(400);
    echo json_encode(['error' => 'Invalid JSON']);
    exit;
}

// Pflichtfelder prüfen
$type = $data['type'] ?? '';
if (!in_array($type, ['pageview', 'event'])) {
    http_response_code(400);
    echo json_encode(['error' => 'Invalid type']);
    exit;
}

try {
    $db = getDB();

    // Anonymen Session-Hash generieren (ohne IP-Speicherung!)
    // Basiert auf: User-Agent + Bildschirmgröße + Sprache + Tagesdatum
    // Rotiert täglich für zusätzliche Anonymität
    $sessionData = implode('|', [
        $_SERVER['HTTP_USER_AGENT'] ?? 'unknown',
        $data['screen_width'] ?? '0',
        $data['screen_height'] ?? '0',
        $data['language'] ?? 'de',
        date('Y-m-d') // Tägliche Rotation
    ]);
    $sessionHash = hash('sha256', $sessionData);

    // Gerätetyp, Browser und OS erkennen
    $userAgent = $_SERVER['HTTP_USER_AGENT'] ?? '';
    $deviceInfo = parseUserAgent($userAgent);

    if ($type === 'pageview') {
        // Seitenaufruf speichern
        $stmt = $db->prepare("
            INSERT INTO tracking_pageviews
            (session_hash, seite, referrer, referrer_domain, geraetetyp, browser, browser_version,
             os, os_version, bildschirmbreite, bildschirmhoehe, sprache)
            VALUES
            (:session_hash, :seite, :referrer, :referrer_domain, :geraetetyp, :browser, :browser_version,
             :os, :os_version, :bildschirmbreite, :bildschirmhoehe, :sprache)
        ");

        $seite = sanitizeInput(substr($data['page'] ?? '/', 0, 255));
        $referrer = sanitizeInput(substr($data['referrer'] ?? '', 0, 500));
        $referrerDomain = extractDomain($referrer);

        $stmt->execute([
            ':session_hash' => $sessionHash,
            ':seite' => $seite,
            ':referrer' => $referrer ?: null,
            ':referrer_domain' => $referrerDomain,
            ':geraetetyp' => $deviceInfo['device'],
            ':browser' => $deviceInfo['browser'],
            ':browser_version' => $deviceInfo['browser_version'],
            ':os' => $deviceInfo['os'],
            ':os_version' => $deviceInfo['os_version'],
            ':bildschirmbreite' => intval($data['screen_width'] ?? 0) ?: null,
            ':bildschirmhoehe' => intval($data['screen_height'] ?? 0) ?: null,
            ':sprache' => sanitizeInput(substr($data['language'] ?? '', 0, 10)) ?: null
        ]);

        // Tagesstatistiken aktualisieren
        updateDailyStats($db, $seite, $sessionHash, $deviceInfo['device'], $referrerDomain, $deviceInfo['browser']);

    } elseif ($type === 'event') {
        // Event speichern
        $stmt = $db->prepare("
            INSERT INTO tracking_events
            (session_hash, event_typ, event_name, event_kategorie, event_wert, seite,
             element_text, element_id, element_klassen)
            VALUES
            (:session_hash, :event_typ, :event_name, :event_kategorie, :event_wert, :seite,
             :element_text, :element_id, :element_klassen)
        ");

        $eventTyp = sanitizeInput(substr($data['event_type'] ?? 'click', 0, 50));
        $eventName = sanitizeInput(substr($data['event_name'] ?? '', 0, 100));
        $eventKategorie = sanitizeInput(substr($data['category'] ?? '', 0, 50));
        $seite = sanitizeInput(substr($data['page'] ?? '/', 0, 255));

        $stmt->execute([
            ':session_hash' => $sessionHash,
            ':event_typ' => $eventTyp,
            ':event_name' => $eventName,
            ':event_kategorie' => $eventKategorie ?: null,
            ':event_wert' => sanitizeInput(substr($data['value'] ?? '', 0, 255)) ?: null,
            ':seite' => $seite,
            ':element_text' => sanitizeInput(substr($data['element_text'] ?? '', 0, 255)) ?: null,
            ':element_id' => sanitizeInput(substr($data['element_id'] ?? '', 0, 100)) ?: null,
            ':element_klassen' => sanitizeInput(substr($data['element_classes'] ?? '', 0, 255)) ?: null
        ]);

        // Spezielle Events in Tagesstatistiken
        if ($eventKategorie === 'cta') {
            updateEventStats($db, $seite, 'cta_klicks');
        } elseif ($eventKategorie === 'telefon') {
            updateEventStats($db, $seite, 'telefon_klicks');
        } elseif ($eventKategorie === 'email') {
            updateEventStats($db, $seite, 'email_klicks');
        } elseif ($eventTyp === 'scroll' && $eventName === 'scroll_depth') {
            updateScrollStats($db, $seite, floatval($data['value'] ?? 0));
        }
    }

    echo json_encode(['success' => true]);

} catch (PDOException $e) {
    error_log("Tracking Error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['error' => 'Server error']);
}

/**
 * User-Agent parsen für Gerätetyp, Browser und OS
 */
function parseUserAgent($ua) {
    $result = [
        'device' => 'desktop',
        'browser' => 'Unbekannt',
        'browser_version' => null,
        'os' => 'Unbekannt',
        'os_version' => null
    ];

    // Gerätetyp erkennen
    if (preg_match('/Mobile|Android.*Mobile|iPhone|iPod|BlackBerry|IEMobile|Opera Mini/i', $ua)) {
        $result['device'] = 'mobile';
    } elseif (preg_match('/iPad|Android(?!.*Mobile)|Tablet/i', $ua)) {
        $result['device'] = 'tablet';
    }

    // Browser erkennen
    if (preg_match('/Firefox\/(\d+(\.\d+)?)/i', $ua, $m)) {
        $result['browser'] = 'Firefox';
        $result['browser_version'] = $m[1];
    } elseif (preg_match('/Edg\/(\d+(\.\d+)?)/i', $ua, $m)) {
        $result['browser'] = 'Edge';
        $result['browser_version'] = $m[1];
    } elseif (preg_match('/OPR\/(\d+(\.\d+)?)/i', $ua, $m)) {
        $result['browser'] = 'Opera';
        $result['browser_version'] = $m[1];
    } elseif (preg_match('/Chrome\/(\d+(\.\d+)?)/i', $ua, $m)) {
        $result['browser'] = 'Chrome';
        $result['browser_version'] = $m[1];
    } elseif (preg_match('/Safari\/(\d+(\.\d+)?)/i', $ua, $m) && !preg_match('/Chrome/i', $ua)) {
        $result['browser'] = 'Safari';
        if (preg_match('/Version\/(\d+(\.\d+)?)/i', $ua, $v)) {
            $result['browser_version'] = $v[1];
        }
    } elseif (preg_match('/MSIE (\d+(\.\d+)?)|Trident.*rv:(\d+(\.\d+)?)/i', $ua, $m)) {
        $result['browser'] = 'Internet Explorer';
        $result['browser_version'] = $m[1] ?? $m[3];
    }

    // Betriebssystem erkennen
    if (preg_match('/Windows NT (\d+\.\d+)/i', $ua, $m)) {
        $result['os'] = 'Windows';
        $winVersions = ['10.0' => '10/11', '6.3' => '8.1', '6.2' => '8', '6.1' => '7'];
        $result['os_version'] = $winVersions[$m[1]] ?? $m[1];
    } elseif (preg_match('/Mac OS X (\d+[._]\d+)/i', $ua, $m)) {
        $result['os'] = 'macOS';
        $result['os_version'] = str_replace('_', '.', $m[1]);
    } elseif (preg_match('/iPhone OS (\d+[._]\d+)/i', $ua, $m)) {
        $result['os'] = 'iOS';
        $result['os_version'] = str_replace('_', '.', $m[1]);
    } elseif (preg_match('/Android (\d+(\.\d+)?)/i', $ua, $m)) {
        $result['os'] = 'Android';
        $result['os_version'] = $m[1];
    } elseif (preg_match('/Linux/i', $ua)) {
        $result['os'] = 'Linux';
    }

    return $result;
}

/**
 * Domain aus URL extrahieren
 */
function extractDomain($url) {
    if (empty($url)) return null;

    $parsed = parse_url($url);
    $host = $parsed['host'] ?? '';

    if (empty($host)) return null;

    // www. entfernen
    $host = preg_replace('/^www\./i', '', $host);

    // Eigene Domain ignorieren
    if (in_array($host, ['zuschusspiloten.de', 'localhost'])) {
        return null;
    }

    return $host ?: null;
}

/**
 * Tagesstatistiken aktualisieren
 */
function updateDailyStats($db, $seite, $sessionHash, $device, $referrerDomain, $browser) {
    $today = date('Y-m-d');

    // Prüfen ob dieser Session-Hash heute schon gezählt wurde
    $stmt = $db->prepare("
        SELECT COUNT(*) FROM tracking_pageviews
        WHERE session_hash = :hash AND seite = :seite AND DATE(erstellt_am) = :today
    ");
    $stmt->execute([':hash' => $sessionHash, ':seite' => $seite, ':today' => $today]);
    $isNewVisitor = $stmt->fetchColumn() <= 1;

    // Tagesstatistik aktualisieren (INSERT ON DUPLICATE KEY UPDATE)
    $deviceColumn = $device . '_besuche';

    $sql = "
        INSERT INTO tracking_daily_stats
        (datum, seite, besucher_unique, seitenaufrufe, {$deviceColumn})
        VALUES (:datum, :seite, :unique, 1, 1)
        ON DUPLICATE KEY UPDATE
        besucher_unique = besucher_unique + :unique_update,
        seitenaufrufe = seitenaufrufe + 1,
        {$deviceColumn} = {$deviceColumn} + 1
    ";

    $stmt = $db->prepare($sql);
    $stmt->execute([
        ':datum' => $today,
        ':seite' => $seite,
        ':unique' => $isNewVisitor ? 1 : 0,
        ':unique_update' => $isNewVisitor ? 1 : 0
    ]);

    // Referrer-Statistik aktualisieren
    if ($referrerDomain) {
        $stmt = $db->prepare("
            INSERT INTO tracking_referrer_stats (datum, referrer_domain, besuche)
            VALUES (:datum, :domain, 1)
            ON DUPLICATE KEY UPDATE besuche = besuche + 1
        ");
        $stmt->execute([':datum' => $today, ':domain' => $referrerDomain]);
    }

    // Browser-Statistik aktualisieren
    if ($browser && $browser !== 'Unbekannt') {
        $stmt = $db->prepare("
            INSERT INTO tracking_browser_stats (datum, browser, besuche)
            VALUES (:datum, :browser, 1)
            ON DUPLICATE KEY UPDATE besuche = besuche + 1
        ");
        $stmt->execute([':datum' => $today, ':browser' => $browser]);
    }
}

/**
 * Event-Statistiken aktualisieren
 */
function updateEventStats($db, $seite, $column) {
    $today = date('Y-m-d');

    $sql = "
        INSERT INTO tracking_daily_stats (datum, seite, {$column})
        VALUES (:datum, :seite, 1)
        ON DUPLICATE KEY UPDATE {$column} = {$column} + 1
    ";

    $stmt = $db->prepare($sql);
    $stmt->execute([':datum' => $today, ':seite' => $seite]);
}

/**
 * Scroll-Tiefe aktualisieren
 */
function updateScrollStats($db, $seite, $depth) {
    $today = date('Y-m-d');

    // Durchschnitt berechnen ist komplex, hier vereinfacht: letzter Wert
    $stmt = $db->prepare("
        INSERT INTO tracking_daily_stats (datum, seite, avg_scroll_tiefe)
        VALUES (:datum, :seite, :depth)
        ON DUPLICATE KEY UPDATE avg_scroll_tiefe =
            CASE
                WHEN avg_scroll_tiefe IS NULL THEN :depth_update
                ELSE (avg_scroll_tiefe + :depth_update2) / 2
            END
    ");
    $stmt->execute([
        ':datum' => $today,
        ':seite' => $seite,
        ':depth' => $depth,
        ':depth_update' => $depth,
        ':depth_update2' => $depth
    ]);
}
