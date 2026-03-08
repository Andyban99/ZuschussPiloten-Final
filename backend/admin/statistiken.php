<?php
/**
 * Zuschuss Piloten - Admin Statistiken
 * Übersicht über Website-Tracking Daten
 */

require_once 'auth.php';
requireLogin();

$db = getDB();

// Zeitraum-Filter
$zeitraum = isset($_GET['zeitraum']) ? intval($_GET['zeitraum']) : 14;
$validZeitraeume = [7, 14, 30, 90];
if (!in_array($zeitraum, $validZeitraeume)) {
    $zeitraum = 14;
}

$startDatum = date('Y-m-d', strtotime("-{$zeitraum} days"));
$heute = date('Y-m-d');

// ========== KPI-Daten laden (echte unique Personen aus pageviews) ==========

// Heute - Echte unique Personen (basierend auf besucher_hash)
$stmtHeute = $db->prepare("
    SELECT
        COUNT(DISTINCT besucher_hash) as personen,
        COUNT(*) as aufrufe
    FROM tracking_pageviews
    WHERE DATE(erstellt_am) = :heute
");
$stmtHeute->execute([':heute' => $heute]);
$kpiHeute = $stmtHeute->fetch();
$kpiHeute['personen'] = $kpiHeute['personen'] ?? 0;
$kpiHeute['aufrufe'] = $kpiHeute['aufrufe'] ?? 0;

// Diese Woche - Echte unique Personen
$wochenStart = date('Y-m-d', strtotime('monday this week'));
$stmtWoche = $db->prepare("
    SELECT
        COUNT(DISTINCT besucher_hash) as personen,
        COUNT(*) as aufrufe
    FROM tracking_pageviews
    WHERE DATE(erstellt_am) >= :start
");
$stmtWoche->execute([':start' => $wochenStart]);
$kpiWoche = $stmtWoche->fetch();
$kpiWoche['personen'] = $kpiWoche['personen'] ?? 0;
$kpiWoche['aufrufe'] = $kpiWoche['aufrufe'] ?? 0;

// Dieser Monat - Echte unique Personen
$monatsStart = date('Y-m-01');
$stmtMonat = $db->prepare("
    SELECT
        COUNT(DISTINCT besucher_hash) as personen,
        COUNT(*) as aufrufe
    FROM tracking_pageviews
    WHERE DATE(erstellt_am) >= :start
");
$stmtMonat->execute([':start' => $monatsStart]);
$kpiMonat = $stmtMonat->fetch();
$kpiMonat['personen'] = $kpiMonat['personen'] ?? 0;
$kpiMonat['aufrufe'] = $kpiMonat['aufrufe'] ?? 0;

// Gesamt im Zeitraum - Echte unique Personen
$stmtGesamt = $db->prepare("
    SELECT
        COUNT(DISTINCT besucher_hash) as personen,
        COUNT(*) as aufrufe
    FROM tracking_pageviews
    WHERE DATE(erstellt_am) >= :start
");
$stmtGesamt->execute([':start' => $startDatum]);
$kpiGesamt = $stmtGesamt->fetch();
$kpiGesamt['personen'] = $kpiGesamt['personen'] ?? 0;
$kpiGesamt['aufrufe'] = $kpiGesamt['aufrufe'] ?? 0;

// Seiten pro Person (Durchschnitt im Zeitraum)
$seitenProBesuch = $kpiGesamt['personen'] > 0
    ? round($kpiGesamt['aufrufe'] / $kpiGesamt['personen'], 1)
    : 0;

// ========== Besucherverlauf (Chart-Daten) - Echte unique Personen pro Tag ==========
$stmtVerlauf = $db->prepare("
    SELECT
        DATE(erstellt_am) as datum,
        COUNT(DISTINCT besucher_hash) as personen,
        COUNT(*) as aufrufe
    FROM tracking_pageviews
    WHERE DATE(erstellt_am) >= :start
    GROUP BY DATE(erstellt_am)
    ORDER BY datum ASC
");
$stmtVerlauf->execute([':start' => $startDatum]);
$verlaufDaten = $stmtVerlauf->fetchAll();

// Alle Tage im Zeitraum füllen (auch ohne Daten)
$chartLabels = [];
$chartPersonen = [];
$chartAufrufe = [];
$verlaufIndex = [];
foreach ($verlaufDaten as $row) {
    $verlaufIndex[$row['datum']] = $row;
}

for ($i = $zeitraum; $i >= 0; $i--) {
    $d = date('Y-m-d', strtotime("-{$i} days"));
    $chartLabels[] = date('d.m.', strtotime($d));
    $chartPersonen[] = isset($verlaufIndex[$d]) ? intval($verlaufIndex[$d]['personen']) : 0;
    $chartAufrufe[] = isset($verlaufIndex[$d]) ? intval($verlaufIndex[$d]['aufrufe']) : 0;
}

// ========== Geräte-Verteilung (echte Personen) ==========
$stmtGeraete = $db->prepare("
    SELECT
        geraetetyp,
        COUNT(DISTINCT besucher_hash) as personen
    FROM tracking_pageviews
    WHERE DATE(erstellt_am) >= :start
    GROUP BY geraetetyp
");
$stmtGeraete->execute([':start' => $startDatum]);
$geraeteRaw = $stmtGeraete->fetchAll();
$geraete = ['desktop' => 0, 'tablet' => 0, 'mobile' => 0];
foreach ($geraeteRaw as $g) {
    $geraete[$g['geraetetyp']] = intval($g['personen']);
}
$geraeteGesamt = $geraete['desktop'] + $geraete['tablet'] + $geraete['mobile'];

// ========== Top-Seiten (echte Personen) ==========
$stmtSeiten = $db->prepare("
    SELECT
        seite,
        COUNT(*) as aufrufe,
        COUNT(DISTINCT besucher_hash) as personen
    FROM tracking_pageviews
    WHERE DATE(erstellt_am) >= :start
    GROUP BY seite
    ORDER BY personen DESC
    LIMIT 10
");
$stmtSeiten->execute([':start' => $startDatum]);
$topSeiten = $stmtSeiten->fetchAll();

// ========== Top-Referrer (echte Personen) ==========
$stmtReferrer = $db->prepare("
    SELECT
        referrer_domain,
        COUNT(DISTINCT besucher_hash) as personen
    FROM tracking_pageviews
    WHERE DATE(erstellt_am) >= :start AND referrer_domain IS NOT NULL
    GROUP BY referrer_domain
    ORDER BY personen DESC
    LIMIT 10
");
$stmtReferrer->execute([':start' => $startDatum]);
$topReferrer = $stmtReferrer->fetchAll();

// ========== Top-Klicks (alle Events inkl. Formulare und Login) ==========
$stmtEvents = $db->prepare("
    SELECT
        event_name,
        event_kategorie,
        COUNT(*) as anzahl
    FROM tracking_events
    WHERE DATE(erstellt_am) >= :start
        AND (event_typ = 'click' OR event_typ = 'submit')
    GROUP BY event_name, event_kategorie
    ORDER BY anzahl DESC
    LIMIT 10
");
$stmtEvents->execute([':start' => $startDatum]);
$topEvents = $stmtEvents->fetchAll();

// Gesamt Formulare im Zeitraum (für KPI)
$stmtFormularGesamt = $db->prepare("
    SELECT COUNT(*) as anzahl
    FROM tracking_events
    WHERE DATE(erstellt_am) >= :start
        AND (event_kategorie = 'formular' OR event_typ = 'submit')
");
$stmtFormularGesamt->execute([':start' => $startDatum]);
$formularGesamt = $stmtFormularGesamt->fetch()['anzahl'] ?? 0;

// Kunden-Login Klicks (für KPI)
$stmtLogin = $db->prepare("
    SELECT COUNT(*) as anzahl
    FROM tracking_events
    WHERE DATE(erstellt_am) >= :start
        AND event_kategorie = 'login'
");
$stmtLogin->execute([':start' => $startDatum]);
$loginKlicks = $stmtLogin->fetch()['anzahl'] ?? 0;

// ========== Durchschnittliche Verweildauer ==========
$stmtVerweildauer = $db->prepare("
    SELECT AVG(CAST(event_wert AS UNSIGNED)) as avg_sekunden
    FROM tracking_events
    WHERE DATE(erstellt_am) >= :start
        AND event_name = 'time_on_page'
        AND event_wert IS NOT NULL
        AND event_wert REGEXP '^[0-9]+$'
");
$stmtVerweildauer->execute([':start' => $startDatum]);
$avgVerweildauer = $stmtVerweildauer->fetch()['avg_sekunden'] ?? 0;
$avgVerweildauerFormatted = $avgVerweildauer > 0
    ? sprintf('%d:%02d', floor($avgVerweildauer / 60), $avgVerweildauer % 60)
    : '0:00';

// ========== Browser-Statistiken (echte Personen) ==========
$stmtBrowser = $db->prepare("
    SELECT
        browser,
        COUNT(DISTINCT besucher_hash) as personen
    FROM tracking_pageviews
    WHERE DATE(erstellt_am) >= :start AND browser IS NOT NULL
    GROUP BY browser
    ORDER BY personen DESC
    LIMIT 5
");
$stmtBrowser->execute([':start' => $startDatum]);
$browserStats = $stmtBrowser->fetchAll();

// ========== Standort-Statistiken (echte Personen) ==========
$stmtStandorte = $db->prepare("
    SELECT
        land,
        region,
        COUNT(DISTINCT besucher_hash) as personen
    FROM tracking_pageviews
    WHERE DATE(erstellt_am) >= :start AND land IS NOT NULL
    GROUP BY land, region
    ORDER BY personen DESC
    LIMIT 10
");
$stmtStandorte->execute([':start' => $startDatum]);
$standortStats = $stmtStandorte->fetchAll();

// Top-Länder (echte Personen aggregiert)
$stmtLaender = $db->prepare("
    SELECT
        land,
        COUNT(DISTINCT besucher_hash) as personen
    FROM tracking_pageviews
    WHERE DATE(erstellt_am) >= :start AND land IS NOT NULL
    GROUP BY land
    ORDER BY personen DESC
    LIMIT 5
");
$stmtLaender->execute([':start' => $startDatum]);
$laenderStats = $stmtLaender->fetchAll();

// Status-Statistiken für Sidebar Badge
$stats = $db->query("SELECT SUM(CASE WHEN status = 'neu' THEN 1 ELSE 0 END) as neu FROM anfragen WHERE status != 'archiviert'")->fetch();
?>
<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Statistiken - Zuschuss Piloten Admin</title>
    <link rel="icon" type="image/svg+xml" href="../../assets/favicon.svg">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://code.iconify.design/iconify-icon/1.0.7/iconify-icon.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js"></script>
    <style>
        body { font-family: 'Inter', sans-serif; }
        .sidebar { background: linear-gradient(180deg, #0B1120 0%, #1e293b 100%); }
        @keyframes spin { from { transform: rotate(0deg); } to { transform: rotate(360deg); } }
        .reload-spin { animation: spin 0.8s linear infinite; }
    </style>
</head>
<body class="bg-slate-100 min-h-screen">
    <div class="flex">
        <!-- Sidebar -->
        <aside class="sidebar w-64 min-h-screen fixed left-0 top-0 text-white p-6 flex flex-col">
            <!-- Logo -->
            <div class="flex items-center gap-3 mb-10">
                <div class="w-10 h-10 bg-blue-600 rounded-lg flex items-center justify-center shadow-lg shadow-blue-600/30">
                    <iconify-icon icon="solar:plain-3-bold-duotone" width="24"></iconify-icon>
                </div>
                <div>
                    <span class="block font-bold text-lg tracking-tight">Zuschuss Piloten</span>
                    <span class="block text-[10px] text-slate-400 uppercase tracking-widest">Admin</span>
                </div>
            </div>

            <!-- Navigation -->
            <nav class="flex-1 space-y-2">
                <a href="index.php" class="flex items-center gap-3 px-4 py-3 rounded-xl text-slate-300 hover:bg-white/5 hover:text-white transition-all">
                    <iconify-icon icon="solar:widget-bold" width="20"></iconify-icon>
                    Dashboard
                </a>
                <a href="index.php?status=neu" class="flex items-center gap-3 px-4 py-3 rounded-xl text-slate-300 hover:bg-white/5 hover:text-white transition-all">
                    <iconify-icon icon="solar:inbox-bold" width="20"></iconify-icon>
                    Neue Anfragen
                    <?php if ($stats['neu'] > 0): ?>
                    <span class="ml-auto bg-blue-500 text-white text-xs font-bold px-2 py-0.5 rounded-full"><?= $stats['neu'] ?></span>
                    <?php endif; ?>
                </a>
                <a href="index.php?status=in_bearbeitung" class="flex items-center gap-3 px-4 py-3 rounded-xl text-slate-300 hover:bg-white/5 hover:text-white transition-all">
                    <iconify-icon icon="solar:clock-circle-bold" width="20"></iconify-icon>
                    In Bearbeitung
                </a>
                <a href="index.php?status=erledigt" class="flex items-center gap-3 px-4 py-3 rounded-xl text-slate-300 hover:bg-white/5 hover:text-white transition-all">
                    <iconify-icon icon="solar:check-circle-bold" width="20"></iconify-icon>
                    Erledigt
                </a>
                <a href="kunden.php" class="flex items-center gap-3 px-4 py-3 rounded-xl text-slate-300 hover:bg-white/5 hover:text-white transition-all">
                    <iconify-icon icon="solar:users-group-rounded-bold" width="20"></iconify-icon>
                    Kundendaten
                </a>
                <a href="statistiken.php" class="flex items-center gap-3 px-4 py-3 rounded-xl bg-white/10 text-white font-medium">
                    <iconify-icon icon="solar:chart-2-bold" width="20"></iconify-icon>
                    Statistiken
                </a>
                <a href="export.php" class="flex items-center gap-3 px-4 py-3 rounded-xl text-slate-300 hover:bg-white/5 hover:text-white transition-all">
                    <iconify-icon icon="solar:download-bold" width="20"></iconify-icon>
                    CSV Export
                </a>
                <a href="einstellungen.php" class="flex items-center gap-3 px-4 py-3 rounded-xl text-slate-300 hover:bg-white/5 hover:text-white transition-all">
                    <iconify-icon icon="solar:settings-bold" width="20"></iconify-icon>
                    Einstellungen
                </a>
            </nav>

            <!-- User -->
            <div class="border-t border-white/10 pt-6 mt-6">
                <div class="flex items-center gap-3 mb-4">
                    <div class="w-10 h-10 bg-slate-700 rounded-full flex items-center justify-center">
                        <iconify-icon icon="solar:user-bold" width="20" class="text-slate-300"></iconify-icon>
                    </div>
                    <div>
                        <span class="block text-sm font-medium"><?= e($_SESSION['admin_name'] ?? $_SESSION['admin_user']) ?></span>
                        <span class="block text-xs text-slate-400">Administrator</span>
                    </div>
                </div>
                <a href="logout.php" class="flex items-center gap-2 text-sm text-slate-400 hover:text-white transition-colors">
                    <iconify-icon icon="solar:logout-2-bold" width="18"></iconify-icon>
                    Abmelden
                </a>
            </div>
        </aside>

        <!-- Main Content -->
        <main class="ml-64 flex-1 p-8">
            <!-- Header -->
            <div class="flex items-center justify-between mb-8">
                <div>
                    <h1 class="text-2xl font-bold text-slate-900">Statistiken</h1>
                    <p class="text-slate-500">Website-Analyse & Besucherdaten</p>
                </div>
                <div class="flex items-center gap-3">
                    <!-- Reload Button -->
                    <button onclick="document.getElementById('reloadIcon').classList.add('reload-spin'); setTimeout(() => window.location.reload(), 300);"
                            class="flex items-center gap-2 px-4 py-2 bg-white border border-slate-200 rounded-xl text-slate-600 hover:bg-slate-50 hover:text-slate-900 transition-all"
                            title="Statistiken neu laden">
                        <iconify-icon icon="solar:refresh-bold" width="18" id="reloadIcon"></iconify-icon>
                        <span class="text-sm font-medium">Aktualisieren</span>
                    </button>
                    <!-- Zeitraum Filter -->
                    <div class="flex bg-white border border-slate-200 rounded-xl p-1">
                        <?php foreach ($validZeitraeume as $z): ?>
                        <a href="?zeitraum=<?= $z ?>"
                           class="px-4 py-2 text-sm rounded-lg transition-all <?= $zeitraum === $z ? 'bg-slate-900 text-white' : 'text-slate-600 hover:bg-slate-100' ?>">
                            <?= $z ?> Tage
                        </a>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>

            <!-- KPI Cards -->
            <div class="grid grid-cols-4 gap-6 mb-8">
                <!-- Heute -->
                <div class="bg-white rounded-2xl p-6 border border-slate-200 shadow-sm">
                    <div class="flex items-center gap-4">
                        <div class="w-12 h-12 bg-blue-100 rounded-xl flex items-center justify-center">
                            <iconify-icon icon="solar:users-group-rounded-bold" width="24" class="text-blue-600"></iconify-icon>
                        </div>
                        <div>
                            <span class="block text-2xl font-bold text-slate-900"><?= number_format($kpiHeute['personen']) ?></span>
                            <span class="block text-sm text-slate-500">Personen heute</span>
                        </div>
                    </div>
                    <div class="mt-3 flex items-center gap-2 text-xs">
                        <span class="text-slate-400"><?= number_format($kpiHeute['aufrufe']) ?> Seitenaufrufe</span>
                        <?php if ($kpiHeute['personen'] > 0): ?>
                        <span class="text-blue-500">(<?= round($kpiHeute['aufrufe'] / $kpiHeute['personen'], 1) ?> pro Person)</span>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Diese Woche -->
                <div class="bg-white rounded-2xl p-6 border border-slate-200 shadow-sm">
                    <div class="flex items-center gap-4">
                        <div class="w-12 h-12 bg-indigo-100 rounded-xl flex items-center justify-center">
                            <iconify-icon icon="solar:users-group-rounded-bold" width="24" class="text-indigo-600"></iconify-icon>
                        </div>
                        <div>
                            <span class="block text-2xl font-bold text-slate-900"><?= number_format($kpiWoche['personen']) ?></span>
                            <span class="block text-sm text-slate-500">Personen diese Woche</span>
                        </div>
                    </div>
                    <div class="mt-3 flex items-center gap-2 text-xs">
                        <span class="text-slate-400"><?= number_format($kpiWoche['aufrufe']) ?> Seitenaufrufe</span>
                        <?php if ($kpiWoche['personen'] > 0): ?>
                        <span class="text-indigo-500">(<?= round($kpiWoche['aufrufe'] / $kpiWoche['personen'], 1) ?> pro Person)</span>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Dieser Monat -->
                <div class="bg-white rounded-2xl p-6 border border-slate-200 shadow-sm">
                    <div class="flex items-center gap-4">
                        <div class="w-12 h-12 bg-emerald-100 rounded-xl flex items-center justify-center">
                            <iconify-icon icon="solar:users-group-rounded-bold" width="24" class="text-emerald-600"></iconify-icon>
                        </div>
                        <div>
                            <span class="block text-2xl font-bold text-slate-900"><?= number_format($kpiMonat['personen']) ?></span>
                            <span class="block text-sm text-slate-500">Personen diesen Monat</span>
                        </div>
                    </div>
                    <div class="mt-3 flex items-center gap-2 text-xs">
                        <span class="text-slate-400"><?= number_format($kpiMonat['aufrufe']) ?> Seitenaufrufe</span>
                        <?php if ($kpiMonat['personen'] > 0): ?>
                        <span class="text-emerald-500">(<?= round($kpiMonat['aufrufe'] / $kpiMonat['personen'], 1) ?> pro Person)</span>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Seiten/Besuch -->
                <div class="bg-white rounded-2xl p-6 border border-slate-200 shadow-sm">
                    <div class="flex items-center gap-4">
                        <div class="w-12 h-12 bg-amber-100 rounded-xl flex items-center justify-center">
                            <iconify-icon icon="solar:documents-bold" width="24" class="text-amber-600"></iconify-icon>
                        </div>
                        <div>
                            <span class="block text-2xl font-bold text-slate-900"><?= number_format($seitenProBesuch, 1) ?></span>
                            <span class="block text-sm text-slate-500">Seiten/Besuch</span>
                        </div>
                    </div>
                    <div class="mt-3 text-xs text-slate-400">
                        Durchschnitt (<?= $zeitraum ?> Tage)
                    </div>
                </div>
            </div>

            <!-- Conversion KPI Cards -->
            <div class="grid grid-cols-4 gap-6 mb-8">
                <!-- Formular-Absendungen -->
                <div class="bg-white rounded-2xl p-6 border border-slate-200 shadow-sm">
                    <div class="flex items-center gap-4">
                        <div class="w-12 h-12 bg-purple-100 rounded-xl flex items-center justify-center">
                            <iconify-icon icon="solar:document-add-bold" width="24" class="text-purple-600"></iconify-icon>
                        </div>
                        <div>
                            <span class="block text-2xl font-bold text-slate-900"><?= number_format($formularGesamt) ?></span>
                            <span class="block text-sm text-slate-500">Formular-Absendungen</span>
                        </div>
                    </div>
                    <div class="mt-3 text-xs text-slate-400">
                        Im gewählten Zeitraum
                    </div>
                </div>

                <!-- Kunden-Login Klicks -->
                <div class="bg-white rounded-2xl p-6 border border-slate-200 shadow-sm">
                    <div class="flex items-center gap-4">
                        <div class="w-12 h-12 bg-cyan-100 rounded-xl flex items-center justify-center">
                            <iconify-icon icon="solar:login-2-bold" width="24" class="text-cyan-600"></iconify-icon>
                        </div>
                        <div>
                            <span class="block text-2xl font-bold text-slate-900"><?= number_format($loginKlicks) ?></span>
                            <span class="block text-sm text-slate-500">Kunden-Login Klicks</span>
                        </div>
                    </div>
                    <div class="mt-3 text-xs text-slate-400">
                        Im gewählten Zeitraum
                    </div>
                </div>

                <!-- Durchschn. Verweildauer -->
                <div class="bg-white rounded-2xl p-6 border border-slate-200 shadow-sm">
                    <div class="flex items-center gap-4">
                        <div class="w-12 h-12 bg-rose-100 rounded-xl flex items-center justify-center">
                            <iconify-icon icon="solar:clock-circle-bold" width="24" class="text-rose-600"></iconify-icon>
                        </div>
                        <div>
                            <span class="block text-2xl font-bold text-slate-900"><?= $avgVerweildauerFormatted ?></span>
                            <span class="block text-sm text-slate-500">Ø Verweildauer</span>
                        </div>
                    </div>
                    <div class="mt-3 text-xs text-slate-400">
                        Minuten:Sekunden
                    </div>
                </div>

                <!-- Conversion Rate -->
                <div class="bg-white rounded-2xl p-6 border border-slate-200 shadow-sm">
                    <div class="flex items-center gap-4">
                        <div class="w-12 h-12 bg-teal-100 rounded-xl flex items-center justify-center">
                            <iconify-icon icon="solar:chart-square-bold" width="24" class="text-teal-600"></iconify-icon>
                        </div>
                        <div>
                            <?php
                            $conversionRate = $kpiGesamt['personen'] > 0 ? ($formularGesamt / $kpiGesamt['personen']) * 100 : 0;
                            ?>
                            <span class="block text-2xl font-bold text-slate-900"><?= number_format($conversionRate, 1) ?>%</span>
                            <span class="block text-sm text-slate-500">Conversion Rate</span>
                        </div>
                    </div>
                    <div class="mt-3 text-xs text-slate-400">
                        Formulare / Besucher
                    </div>
                </div>
            </div>

            <!-- Charts Row -->
            <div class="grid grid-cols-3 gap-6 mb-8">
                <!-- Besucherverlauf Chart -->
                <div class="col-span-2 bg-white rounded-2xl p-6 border border-slate-200 shadow-sm">
                    <h2 class="font-semibold text-slate-900 mb-4">Besucherverlauf</h2>
                    <div class="h-64">
                        <canvas id="besucherChart"></canvas>
                    </div>
                </div>

                <!-- Geräte-Verteilung -->
                <div class="bg-white rounded-2xl p-6 border border-slate-200 shadow-sm">
                    <h2 class="font-semibold text-slate-900 mb-4">Geräte</h2>
                    <div class="h-48 flex items-center justify-center">
                        <canvas id="geraeteChart"></canvas>
                    </div>
                    <div class="mt-4 space-y-2">
                        <?php if ($geraeteGesamt > 0): ?>
                        <div class="flex items-center justify-between text-sm">
                            <span class="flex items-center gap-2">
                                <span class="w-3 h-3 bg-blue-500 rounded-full"></span>
                                Desktop
                            </span>
                            <span class="font-medium"><?= round(($geraete['desktop'] / $geraeteGesamt) * 100) ?>%</span>
                        </div>
                        <div class="flex items-center justify-between text-sm">
                            <span class="flex items-center gap-2">
                                <span class="w-3 h-3 bg-emerald-500 rounded-full"></span>
                                Mobile
                            </span>
                            <span class="font-medium"><?= round(($geraete['mobile'] / $geraeteGesamt) * 100) ?>%</span>
                        </div>
                        <div class="flex items-center justify-between text-sm">
                            <span class="flex items-center gap-2">
                                <span class="w-3 h-3 bg-amber-500 rounded-full"></span>
                                Tablet
                            </span>
                            <span class="font-medium"><?= round(($geraete['tablet'] / $geraeteGesamt) * 100) ?>%</span>
                        </div>
                        <?php else: ?>
                        <p class="text-slate-400 text-center text-sm">Keine Daten vorhanden</p>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <!-- Tables Row -->
            <div class="grid grid-cols-2 gap-6 mb-8">
                <!-- Top Seiten -->
                <div class="bg-white rounded-2xl border border-slate-200 shadow-sm overflow-hidden">
                    <div class="px-6 py-4 border-b border-slate-200">
                        <h2 class="font-semibold text-slate-900">Top Seiten</h2>
                    </div>
                    <?php if (empty($topSeiten)): ?>
                    <div class="p-8 text-center text-slate-400">
                        Keine Daten vorhanden
                    </div>
                    <?php else: ?>
                    <table class="w-full">
                        <thead class="bg-slate-50 text-left text-xs text-slate-500">
                            <tr>
                                <th class="px-6 py-3 font-medium">Seite</th>
                                <th class="px-6 py-3 font-medium text-right">Aufrufe</th>
                                <th class="px-6 py-3 font-medium text-right">Besucher</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            <?php foreach ($topSeiten as $seite): ?>
                            <tr class="hover:bg-slate-50">
                                <td class="px-6 py-3 text-sm">
                                    <span class="text-slate-700 truncate block max-w-xs"><?= e($seite['seite']) ?></span>
                                </td>
                                <td class="px-6 py-3 text-sm text-right font-medium text-slate-900">
                                    <?= number_format($seite['aufrufe']) ?>
                                </td>
                                <td class="px-6 py-3 text-sm text-right text-slate-500">
                                    <?= number_format($seite['personen']) ?>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                    <?php endif; ?>
                </div>

                <!-- Top Referrer -->
                <div class="bg-white rounded-2xl border border-slate-200 shadow-sm overflow-hidden">
                    <div class="px-6 py-4 border-b border-slate-200">
                        <h2 class="font-semibold text-slate-900">Top Referrer</h2>
                    </div>
                    <?php if (empty($topReferrer)): ?>
                    <div class="p-8 text-center text-slate-400">
                        Keine externen Referrer
                    </div>
                    <?php else: ?>
                    <table class="w-full">
                        <thead class="bg-slate-50 text-left text-xs text-slate-500">
                            <tr>
                                <th class="px-6 py-3 font-medium">Quelle</th>
                                <th class="px-6 py-3 font-medium text-right">Personen</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            <?php foreach ($topReferrer as $ref): ?>
                            <tr class="hover:bg-slate-50">
                                <td class="px-6 py-3 text-sm">
                                    <span class="text-slate-700"><?= e($ref['referrer_domain']) ?></span>
                                </td>
                                <td class="px-6 py-3 text-sm text-right font-medium text-slate-900">
                                    <?= number_format($ref['personen']) ?>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Bottom Row -->
            <div class="grid grid-cols-2 gap-6">
                <!-- Top Klicks -->
                <div class="bg-white rounded-2xl border border-slate-200 shadow-sm overflow-hidden">
                    <div class="px-6 py-4 border-b border-slate-200">
                        <h2 class="font-semibold text-slate-900">Top-Klicks</h2>
                    </div>
                    <?php if (empty($topEvents)): ?>
                    <div class="p-8 text-center text-slate-400">
                        Keine Events erfasst
                    </div>
                    <?php else: ?>
                    <table class="w-full">
                        <thead class="bg-slate-50 text-left text-xs text-slate-500">
                            <tr>
                                <th class="px-6 py-3 font-medium">Event</th>
                                <th class="px-6 py-3 font-medium">Kategorie</th>
                                <th class="px-6 py-3 font-medium text-right">Anzahl</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            <?php foreach ($topEvents as $event): ?>
                            <tr class="hover:bg-slate-50">
                                <td class="px-6 py-3 text-sm">
                                    <span class="text-slate-700 truncate block max-w-xs"><?= e($event['event_name']) ?></span>
                                </td>
                                <td class="px-6 py-3 text-sm">
                                    <?php
                                    $kategorieBadge = [
                                        'cta' => 'bg-blue-100 text-blue-700',
                                        'telefon' => 'bg-emerald-100 text-emerald-700',
                                        'email' => 'bg-amber-100 text-amber-700',
                                        'navigation' => 'bg-slate-100 text-slate-600',
                                        'formular' => 'bg-purple-100 text-purple-700',
                                        'login' => 'bg-cyan-100 text-cyan-700'
                                    ];
                                    $badgeClass = $kategorieBadge[$event['event_kategorie']] ?? 'bg-slate-100 text-slate-600';
                                    ?>
                                    <span class="inline-flex px-2 py-0.5 text-xs font-medium rounded <?= $badgeClass ?>">
                                        <?= e($event['event_kategorie'] ?: 'sonstig') ?>
                                    </span>
                                </td>
                                <td class="px-6 py-3 text-sm text-right font-medium text-slate-900">
                                    <?= number_format($event['anzahl']) ?>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                    <?php endif; ?>
                </div>

                <!-- Browser Statistiken -->
                <div class="bg-white rounded-2xl border border-slate-200 shadow-sm overflow-hidden">
                    <div class="px-6 py-4 border-b border-slate-200">
                        <h2 class="font-semibold text-slate-900">Browser</h2>
                    </div>
                    <?php if (empty($browserStats)): ?>
                    <div class="p-8 text-center text-slate-400">
                        Keine Daten vorhanden
                    </div>
                    <?php else: ?>
                    <div class="p-6 space-y-4">
                        <?php
                        $browserGesamt = array_sum(array_column($browserStats, 'personen'));
                        $browserIcons = [
                            'Chrome' => 'logos:chrome',
                            'Firefox' => 'logos:firefox',
                            'Safari' => 'logos:safari',
                            'Edge' => 'logos:microsoft-edge',
                            'Opera' => 'logos:opera'
                        ];
                        foreach ($browserStats as $browser):
                            $prozent = $browserGesamt > 0 ? round(($browser['personen'] / $browserGesamt) * 100) : 0;
                            $icon = $browserIcons[$browser['browser']] ?? 'solar:global-bold';
                        ?>
                        <div>
                            <div class="flex items-center justify-between mb-1">
                                <span class="flex items-center gap-2 text-sm text-slate-700">
                                    <iconify-icon icon="<?= $icon ?>" width="16"></iconify-icon>
                                    <?= e($browser['browser']) ?>
                                </span>
                                <span class="text-sm font-medium text-slate-900"><?= $prozent ?>%</span>
                            </div>
                            <div class="w-full bg-slate-100 rounded-full h-2">
                                <div class="bg-blue-500 h-2 rounded-full transition-all" style="width: <?= $prozent ?>%"></div>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Standort-Statistiken -->
            <div class="grid grid-cols-2 gap-6 mt-6">
                <!-- Länder -->
                <div class="bg-white rounded-2xl border border-slate-200 shadow-sm overflow-hidden">
                    <div class="px-6 py-4 border-b border-slate-200">
                        <h2 class="font-semibold text-slate-900 flex items-center gap-2">
                            <iconify-icon icon="solar:globe-bold" class="text-blue-500" width="20"></iconify-icon>
                            Besucher nach Land
                        </h2>
                    </div>
                    <?php if (empty($laenderStats)): ?>
                    <div class="p-8 text-center text-slate-400">
                        <iconify-icon icon="solar:map-point-bold" width="32" class="mb-2"></iconify-icon>
                        <p>Noch keine Standortdaten</p>
                        <p class="text-xs mt-1">Daten werden ab jetzt erfasst</p>
                    </div>
                    <?php else: ?>
                    <div class="p-6 space-y-4">
                        <?php
                        $laenderGesamt = array_sum(array_column($laenderStats, 'personen'));
                        $landFlags = [
                            'Germany' => '🇩🇪',
                            'Deutschland' => '🇩🇪',
                            'Austria' => '🇦🇹',
                            'Österreich' => '🇦🇹',
                            'Switzerland' => '🇨🇭',
                            'Schweiz' => '🇨🇭',
                            'Netherlands' => '🇳🇱',
                            'Belgium' => '🇧🇪',
                            'France' => '🇫🇷',
                            'United Kingdom' => '🇬🇧',
                            'United States' => '🇺🇸',
                            'Poland' => '🇵🇱',
                            'Italy' => '🇮🇹',
                            'Spain' => '🇪🇸',
                            'Portugal' => '🇵🇹'
                        ];
                        foreach ($laenderStats as $land):
                            $prozent = $laenderGesamt > 0 ? round(($land['personen'] / $laenderGesamt) * 100) : 0;
                            $flag = $landFlags[$land['land']] ?? '🌍';
                        ?>
                        <div>
                            <div class="flex items-center justify-between mb-1">
                                <span class="flex items-center gap-2 text-sm text-slate-700">
                                    <span class="text-lg"><?= $flag ?></span>
                                    <?= e($land['land']) ?>
                                </span>
                                <span class="text-sm">
                                    <span class="font-medium text-slate-900"><?= number_format($land['personen']) ?></span>
                                    <span class="text-slate-400">(<?= $prozent ?>%)</span>
                                </span>
                            </div>
                            <div class="w-full bg-slate-100 rounded-full h-2">
                                <div class="bg-blue-500 h-2 rounded-full transition-all" style="width: <?= $prozent ?>%"></div>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                    <?php endif; ?>
                </div>

                <!-- Regionen (Detail) -->
                <div class="bg-white rounded-2xl border border-slate-200 shadow-sm overflow-hidden">
                    <div class="px-6 py-4 border-b border-slate-200">
                        <h2 class="font-semibold text-slate-900 flex items-center gap-2">
                            <iconify-icon icon="solar:map-point-bold" class="text-emerald-500" width="20"></iconify-icon>
                            Besucher nach Region
                        </h2>
                    </div>
                    <?php if (empty($standortStats)): ?>
                    <div class="p-8 text-center text-slate-400">
                        <iconify-icon icon="solar:map-bold" width="32" class="mb-2"></iconify-icon>
                        <p>Noch keine Standortdaten</p>
                    </div>
                    <?php else: ?>
                    <table class="w-full">
                        <thead class="bg-slate-50 text-left text-xs text-slate-500">
                            <tr>
                                <th class="px-6 py-3 font-medium">Land</th>
                                <th class="px-6 py-3 font-medium">Region</th>
                                <th class="px-6 py-3 font-medium text-right">Personen</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            <?php foreach ($standortStats as $ort): ?>
                            <tr class="hover:bg-slate-50">
                                <td class="px-6 py-3 text-sm text-slate-700"><?= e($ort['land']) ?></td>
                                <td class="px-6 py-3 text-sm text-slate-500"><?= e($ort['region'] ?: '-') ?></td>
                                <td class="px-6 py-3 text-sm text-right font-medium text-slate-900"><?= number_format($ort['personen']) ?></td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                    <?php endif; ?>
                </div>
            </div>

            <!-- DSGVO Hinweis -->
            <div class="mt-8 p-4 bg-slate-50 rounded-xl border border-slate-200 text-sm text-slate-500">
                <iconify-icon icon="solar:shield-check-bold" class="text-emerald-500 mr-2" width="18"></iconify-icon>
                <strong>DSGVO-konform:</strong> Alle Daten werden ohne Cookies und ohne IP-Speicherung erfasst. Session-Hashes rotieren täglich. Standorte werden nur als Land/Region gespeichert (keine genauen Adressen). Daten werden nach 90 Tagen automatisch gelöscht.
            </div>
        </main>
    </div>

    <script>
        // Besucherverlauf Chart
        const besucherCtx = document.getElementById('besucherChart').getContext('2d');
        new Chart(besucherCtx, {
            type: 'line',
            data: {
                labels: <?= json_encode($chartLabels) ?>,
                datasets: [
                    {
                        label: 'Personen (unique)',
                        data: <?= json_encode($chartPersonen) ?>,
                        borderColor: '#3b82f6',
                        backgroundColor: 'rgba(59, 130, 246, 0.1)',
                        fill: true,
                        tension: 0.3,
                        borderWidth: 2
                    },
                    {
                        label: 'Seitenaufrufe',
                        data: <?= json_encode($chartAufrufe) ?>,
                        borderColor: '#10b981',
                        backgroundColor: 'transparent',
                        borderWidth: 2,
                        borderDash: [5, 5],
                        tension: 0.3
                    }
                ]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        position: 'bottom'
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        grid: {
                            color: '#f1f5f9'
                        }
                    },
                    x: {
                        grid: {
                            display: false
                        }
                    }
                }
            }
        });

        // Geräte Chart
        <?php if ($geraeteGesamt > 0): ?>
        const geraeteCtx = document.getElementById('geraeteChart').getContext('2d');
        new Chart(geraeteCtx, {
            type: 'doughnut',
            data: {
                labels: ['Desktop', 'Mobile', 'Tablet'],
                datasets: [{
                    data: [<?= $geraete['desktop'] ?>, <?= $geraete['mobile'] ?>, <?= $geraete['tablet'] ?>],
                    backgroundColor: ['#3b82f6', '#10b981', '#f59e0b'],
                    borderWidth: 0
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                cutout: '70%',
                plugins: {
                    legend: {
                        display: false
                    }
                }
            }
        });
        <?php endif; ?>
    </script>
</body>
</html>
