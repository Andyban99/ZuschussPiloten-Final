<?php
/**
 * Zuschuss Piloten - Admin Kundenverwaltung
 */

require_once 'auth.php';
requireLogin();

$db = getDB();

// Filter
$status = $_GET['status'] ?? 'alle';
$suche = trim($_GET['suche'] ?? '');

// Statistiken
$stats = $db->query("
    SELECT
        COUNT(*) as gesamt,
        SUM(CASE WHEN aktiv = 1 THEN 1 ELSE 0 END) as aktiv,
        SUM(CASE WHEN aktiv = 0 THEN 1 ELSE 0 END) as inaktiv,
        SUM(CASE WHEN letzter_login >= DATE_SUB(NOW(), INTERVAL 30 DAY) THEN 1 ELSE 0 END) as aktiv_30_tage
    FROM kunden
")->fetch();

// Kunden laden
$where = ["1=1"];
$params = [];

if ($status === 'aktiv') {
    $where[] = "aktiv = 1";
} elseif ($status === 'inaktiv') {
    $where[] = "aktiv = 0";
}

if ($suche) {
    $where[] = "(vorname LIKE :suche OR nachname LIKE :suche OR email LIKE :suche OR unternehmen LIKE :suche)";
    $params[':suche'] = "%{$suche}%";
}

$sql = "SELECT * FROM kunden WHERE " . implode(' AND ', $where) . " ORDER BY erstellt_am DESC";

$stmt = $db->prepare($sql);
$stmt->execute($params);
$kunden = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kundenverwaltung - Zuschuss Piloten Admin</title>
    <link rel="icon" type="image/svg+xml" href="../../assets/favicon.svg">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://code.iconify.design/iconify-icon/1.0.7/iconify-icon.min.js"></script>
    <style>
        body { font-family: 'Inter', sans-serif; }
        .sidebar { background: linear-gradient(180deg, #0B1120 0%, #1e293b 100%); }
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
                    Anfragen
                </a>
                <a href="kunden.php" class="flex items-center gap-3 px-4 py-3 rounded-xl bg-white/10 text-white font-medium">
                    <iconify-icon icon="solar:users-group-rounded-bold" width="20"></iconify-icon>
                    Kundendaten
                    <?php if ($stats['gesamt'] > 0): ?>
                    <span class="ml-auto bg-emerald-500 text-white text-xs font-bold px-2 py-0.5 rounded-full"><?= $stats['gesamt'] ?></span>
                    <?php endif; ?>
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
                    <h1 class="text-2xl font-bold text-slate-900">Kundenverwaltung</h1>
                    <p class="text-slate-500">Übersicht aller registrierten Kunden</p>
                </div>
                <div class="flex items-center gap-4">
                    <!-- Suche -->
                    <form method="GET" class="relative">
                        <iconify-icon icon="solar:magnifer-linear" class="absolute left-4 top-1/2 -translate-y-1/2 text-slate-400" width="18"></iconify-icon>
                        <input type="text" name="suche" value="<?= e($suche) ?>" placeholder="Kunde suchen..."
                               class="pl-11 pr-4 py-2.5 bg-white border border-slate-200 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent w-64">
                    </form>
                </div>
            </div>

            <!-- Stats -->
            <div class="grid grid-cols-4 gap-6 mb-8">
                <div class="bg-white rounded-2xl p-6 border border-slate-200 shadow-sm">
                    <div class="flex items-center gap-4">
                        <div class="w-12 h-12 bg-slate-100 rounded-xl flex items-center justify-center">
                            <iconify-icon icon="solar:users-group-rounded-bold" width="24" class="text-slate-600"></iconify-icon>
                        </div>
                        <div>
                            <span class="block text-2xl font-bold text-slate-900"><?= $stats['gesamt'] ?></span>
                            <span class="block text-sm text-slate-500">Gesamt</span>
                        </div>
                    </div>
                </div>
                <div class="bg-white rounded-2xl p-6 border border-slate-200 shadow-sm">
                    <div class="flex items-center gap-4">
                        <div class="w-12 h-12 bg-emerald-100 rounded-xl flex items-center justify-center">
                            <iconify-icon icon="solar:check-circle-bold" width="24" class="text-emerald-600"></iconify-icon>
                        </div>
                        <div>
                            <span class="block text-2xl font-bold text-slate-900"><?= $stats['aktiv'] ?></span>
                            <span class="block text-sm text-slate-500">Aktiv</span>
                        </div>
                    </div>
                </div>
                <div class="bg-white rounded-2xl p-6 border border-slate-200 shadow-sm">
                    <div class="flex items-center gap-4">
                        <div class="w-12 h-12 bg-amber-100 rounded-xl flex items-center justify-center">
                            <iconify-icon icon="solar:clock-circle-bold" width="24" class="text-amber-600"></iconify-icon>
                        </div>
                        <div>
                            <span class="block text-2xl font-bold text-slate-900"><?= $stats['aktiv_30_tage'] ?></span>
                            <span class="block text-sm text-slate-500">Aktiv (30 Tage)</span>
                        </div>
                    </div>
                </div>
                <div class="bg-white rounded-2xl p-6 border border-slate-200 shadow-sm">
                    <div class="flex items-center gap-4">
                        <div class="w-12 h-12 bg-red-100 rounded-xl flex items-center justify-center">
                            <iconify-icon icon="solar:close-circle-bold" width="24" class="text-red-600"></iconify-icon>
                        </div>
                        <div>
                            <span class="block text-2xl font-bold text-slate-900"><?= $stats['inaktiv'] ?></span>
                            <span class="block text-sm text-slate-500">Inaktiv</span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Kunden Liste -->
            <div class="bg-white rounded-2xl border border-slate-200 shadow-sm overflow-hidden">
                <div class="px-6 py-4 border-b border-slate-200 flex items-center justify-between">
                    <h2 class="font-semibold text-slate-900">Registrierte Kunden</h2>
                    <div class="flex gap-2">
                        <a href="kunden.php" class="px-3 py-1.5 text-sm rounded-lg <?= $status === 'alle' ? 'bg-slate-900 text-white' : 'text-slate-600 hover:bg-slate-100' ?> transition-colors">Alle</a>
                        <a href="kunden.php?status=aktiv" class="px-3 py-1.5 text-sm rounded-lg <?= $status === 'aktiv' ? 'bg-emerald-600 text-white' : 'text-slate-600 hover:bg-slate-100' ?> transition-colors">Aktiv</a>
                        <a href="kunden.php?status=inaktiv" class="px-3 py-1.5 text-sm rounded-lg <?= $status === 'inaktiv' ? 'bg-red-600 text-white' : 'text-slate-600 hover:bg-slate-100' ?> transition-colors">Inaktiv</a>
                    </div>
                </div>

                <?php if (empty($kunden)): ?>
                <div class="p-12 text-center">
                    <iconify-icon icon="solar:users-group-rounded-bold" width="48" class="text-slate-300 mb-4"></iconify-icon>
                    <p class="text-slate-500">Noch keine Kunden registriert</p>
                </div>
                <?php else: ?>
                <table class="w-full">
                    <thead class="bg-slate-50 text-left text-sm text-slate-500">
                        <tr>
                            <th class="px-6 py-3 font-medium">#</th>
                            <th class="px-6 py-3 font-medium">Kunde</th>
                            <th class="px-6 py-3 font-medium">Unternehmen</th>
                            <th class="px-6 py-3 font-medium">Kontakt</th>
                            <th class="px-6 py-3 font-medium">Status</th>
                            <th class="px-6 py-3 font-medium">Registriert</th>
                            <th class="px-6 py-3 font-medium"></th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        <?php foreach ($kunden as $kunde): ?>
                        <tr class="hover:bg-slate-50 transition-colors">
                            <td class="px-6 py-4 text-sm text-slate-400"><?= $kunde['id'] ?></td>
                            <td class="px-6 py-4">
                                <div class="flex items-center gap-3">
                                    <div class="w-10 h-10 bg-blue-100 rounded-full flex items-center justify-center text-blue-600 font-semibold">
                                        <?= strtoupper(substr($kunde['vorname'], 0, 1) . substr($kunde['nachname'], 0, 1)) ?>
                                    </div>
                                    <div>
                                        <div class="font-medium text-slate-900"><?= e($kunde['vorname'] . ' ' . $kunde['nachname']) ?></div>
                                        <div class="text-sm text-slate-500"><?= e($kunde['email']) ?></div>
                                    </div>
                                </div>
                            </td>
                            <td class="px-6 py-4 text-slate-700">
                                <?= e($kunde['unternehmen'] ?: '-') ?>
                            </td>
                            <td class="px-6 py-4">
                                <?php if ($kunde['telefon']): ?>
                                <div class="text-sm text-slate-700"><?= e($kunde['telefon']) ?></div>
                                <?php endif; ?>
                                <?php if ($kunde['ort']): ?>
                                <div class="text-xs text-slate-400"><?= e($kunde['plz'] . ' ' . $kunde['ort']) ?></div>
                                <?php else: ?>
                                <span class="text-slate-400">-</span>
                                <?php endif; ?>
                            </td>
                            <td class="px-6 py-4">
                                <?php if ($kunde['aktiv']): ?>
                                <span class="inline-flex items-center gap-1.5 px-2.5 py-1 text-xs font-medium rounded-lg border bg-emerald-100 text-emerald-700 border-emerald-200">
                                    Aktiv
                                </span>
                                <?php else: ?>
                                <span class="inline-flex items-center gap-1.5 px-2.5 py-1 text-xs font-medium rounded-lg border bg-red-100 text-red-700 border-red-200">
                                    Inaktiv
                                </span>
                                <?php endif; ?>
                            </td>
                            <td class="px-6 py-4 text-sm text-slate-500">
                                <?= date('d.m.Y', strtotime($kunde['erstellt_am'])) ?>
                                <?php if ($kunde['letzter_login']): ?>
                                <div class="text-xs text-slate-400">Login: <?= date('d.m.y', strtotime($kunde['letzter_login'])) ?></div>
                                <?php endif; ?>
                            </td>
                            <td class="px-6 py-4">
                                <a href="kunde_view.php?id=<?= $kunde['id'] ?>" class="inline-flex items-center gap-1 text-blue-600 hover:text-blue-800 text-sm font-medium transition-colors">
                                    Details
                                    <iconify-icon icon="solar:arrow-right-linear" width="16"></iconify-icon>
                                </a>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
                <?php endif; ?>
            </div>
        </main>
    </div>
</body>
</html>
