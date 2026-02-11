<?php
/**
 * Zuschuss Piloten - Admin Dashboard
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
        SUM(CASE WHEN status = 'neu' THEN 1 ELSE 0 END) as neu,
        SUM(CASE WHEN status = 'in_bearbeitung' THEN 1 ELSE 0 END) as in_bearbeitung,
        SUM(CASE WHEN status = 'erledigt' THEN 1 ELSE 0 END) as erledigt
    FROM anfragen
    WHERE status != 'archiviert'
")->fetch();

// Anfragen laden
$where = ["status != 'archiviert'"];
$params = [];

if ($status !== 'alle') {
    $where[] = "status = :status";
    $params[':status'] = $status;
}

if ($suche) {
    $where[] = "(name LIKE :suche OR unternehmen LIKE :suche OR email LIKE :suche)";
    $params[':suche'] = "%{$suche}%";
}

$sql = "SELECT * FROM anfragen WHERE " . implode(' AND ', $where) . " ORDER BY
    CASE WHEN status = 'neu' THEN 0
         WHEN status = 'in_bearbeitung' THEN 1
         ELSE 2 END,
    CASE WHEN prioritaet = 'dringend' THEN 0
         WHEN prioritaet = 'hoch' THEN 1
         ELSE 2 END,
    erstellt_am DESC";

$stmt = $db->prepare($sql);
$stmt->execute($params);
$anfragen = $stmt->fetchAll();

// Status-Farben
$statusColors = [
    'neu' => 'bg-blue-100 text-blue-700 border-blue-200',
    'in_bearbeitung' => 'bg-amber-100 text-amber-700 border-amber-200',
    'erledigt' => 'bg-emerald-100 text-emerald-700 border-emerald-200',
    'archiviert' => 'bg-slate-100 text-slate-500 border-slate-200'
];

$statusLabels = [
    'neu' => 'Neu',
    'in_bearbeitung' => 'In Bearbeitung',
    'erledigt' => 'Erledigt',
    'archiviert' => 'Archiviert'
];

$prioritaetColors = [
    'normal' => '',
    'hoch' => 'border-l-4 border-l-amber-400',
    'dringend' => 'border-l-4 border-l-red-500'
];
?>
<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - Zuschuss Piloten Admin</title>
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
                <a href="index.php" class="flex items-center gap-3 px-4 py-3 rounded-xl bg-white/10 text-white font-medium">
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
                    <?php if ($stats['in_bearbeitung'] > 0): ?>
                    <span class="ml-auto bg-amber-500 text-white text-xs font-bold px-2 py-0.5 rounded-full"><?= $stats['in_bearbeitung'] ?></span>
                    <?php endif; ?>
                </a>
                <a href="index.php?status=erledigt" class="flex items-center gap-3 px-4 py-3 rounded-xl text-slate-300 hover:bg-white/5 hover:text-white transition-all">
                    <iconify-icon icon="solar:check-circle-bold" width="20"></iconify-icon>
                    Erledigt
                </a>
                <a href="export.php" class="flex items-center gap-3 px-4 py-3 rounded-xl text-slate-300 hover:bg-white/5 hover:text-white transition-all">
                    <iconify-icon icon="solar:download-bold" width="20"></iconify-icon>
                    CSV Export
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
                    <h1 class="text-2xl font-bold text-slate-900">Dashboard</h1>
                    <p class="text-slate-500">Übersicht aller Kontaktanfragen</p>
                </div>
                <div class="flex items-center gap-4">
                    <!-- Suche -->
                    <form method="GET" class="relative">
                        <iconify-icon icon="solar:magnifer-linear" class="absolute left-4 top-1/2 -translate-y-1/2 text-slate-400" width="18"></iconify-icon>
                        <input type="text" name="suche" value="<?= e($suche) ?>" placeholder="Suchen..."
                               class="pl-11 pr-4 py-2.5 bg-white border border-slate-200 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent w-64">
                    </form>
                    <a href="../../index.html" target="_blank" class="flex items-center gap-2 px-4 py-2.5 bg-slate-200 hover:bg-slate-300 text-slate-700 rounded-xl text-sm font-medium transition-colors">
                        <iconify-icon icon="solar:eye-bold" width="18"></iconify-icon>
                        Website ansehen
                    </a>
                </div>
            </div>

            <!-- Stats -->
            <div class="grid grid-cols-4 gap-6 mb-8">
                <div class="bg-white rounded-2xl p-6 border border-slate-200 shadow-sm">
                    <div class="flex items-center gap-4">
                        <div class="w-12 h-12 bg-slate-100 rounded-xl flex items-center justify-center">
                            <iconify-icon icon="solar:inbox-bold" width="24" class="text-slate-600"></iconify-icon>
                        </div>
                        <div>
                            <span class="block text-2xl font-bold text-slate-900"><?= $stats['gesamt'] ?></span>
                            <span class="block text-sm text-slate-500">Gesamt</span>
                        </div>
                    </div>
                </div>
                <div class="bg-white rounded-2xl p-6 border border-slate-200 shadow-sm">
                    <div class="flex items-center gap-4">
                        <div class="w-12 h-12 bg-blue-100 rounded-xl flex items-center justify-center">
                            <iconify-icon icon="solar:bell-bold" width="24" class="text-blue-600"></iconify-icon>
                        </div>
                        <div>
                            <span class="block text-2xl font-bold text-slate-900"><?= $stats['neu'] ?></span>
                            <span class="block text-sm text-slate-500">Neue</span>
                        </div>
                    </div>
                </div>
                <div class="bg-white rounded-2xl p-6 border border-slate-200 shadow-sm">
                    <div class="flex items-center gap-4">
                        <div class="w-12 h-12 bg-amber-100 rounded-xl flex items-center justify-center">
                            <iconify-icon icon="solar:clock-circle-bold" width="24" class="text-amber-600"></iconify-icon>
                        </div>
                        <div>
                            <span class="block text-2xl font-bold text-slate-900"><?= $stats['in_bearbeitung'] ?></span>
                            <span class="block text-sm text-slate-500">In Bearbeitung</span>
                        </div>
                    </div>
                </div>
                <div class="bg-white rounded-2xl p-6 border border-slate-200 shadow-sm">
                    <div class="flex items-center gap-4">
                        <div class="w-12 h-12 bg-emerald-100 rounded-xl flex items-center justify-center">
                            <iconify-icon icon="solar:check-circle-bold" width="24" class="text-emerald-600"></iconify-icon>
                        </div>
                        <div>
                            <span class="block text-2xl font-bold text-slate-900"><?= $stats['erledigt'] ?></span>
                            <span class="block text-sm text-slate-500">Erledigt</span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Anfragen Liste -->
            <div class="bg-white rounded-2xl border border-slate-200 shadow-sm overflow-hidden">
                <div class="px-6 py-4 border-b border-slate-200 flex items-center justify-between">
                    <h2 class="font-semibold text-slate-900">Anfragen</h2>
                    <div class="flex gap-2">
                        <a href="index.php" class="px-3 py-1.5 text-sm rounded-lg <?= $status === 'alle' ? 'bg-slate-900 text-white' : 'text-slate-600 hover:bg-slate-100' ?> transition-colors">Alle</a>
                        <a href="index.php?status=neu" class="px-3 py-1.5 text-sm rounded-lg <?= $status === 'neu' ? 'bg-blue-600 text-white' : 'text-slate-600 hover:bg-slate-100' ?> transition-colors">Neu</a>
                        <a href="index.php?status=in_bearbeitung" class="px-3 py-1.5 text-sm rounded-lg <?= $status === 'in_bearbeitung' ? 'bg-amber-500 text-white' : 'text-slate-600 hover:bg-slate-100' ?> transition-colors">In Bearbeitung</a>
                        <a href="index.php?status=erledigt" class="px-3 py-1.5 text-sm rounded-lg <?= $status === 'erledigt' ? 'bg-emerald-600 text-white' : 'text-slate-600 hover:bg-slate-100' ?> transition-colors">Erledigt</a>
                    </div>
                </div>

                <?php if (empty($anfragen)): ?>
                <div class="p-12 text-center">
                    <iconify-icon icon="solar:inbox-bold" width="48" class="text-slate-300 mb-4"></iconify-icon>
                    <p class="text-slate-500">Keine Anfragen gefunden</p>
                </div>
                <?php else: ?>
                <table class="w-full">
                    <thead class="bg-slate-50 text-left text-sm text-slate-500">
                        <tr>
                            <th class="px-6 py-3 font-medium">#</th>
                            <th class="px-6 py-3 font-medium">Kontakt</th>
                            <th class="px-6 py-3 font-medium">Unternehmen</th>
                            <th class="px-6 py-3 font-medium">Status</th>
                            <th class="px-6 py-3 font-medium">Datum</th>
                            <th class="px-6 py-3 font-medium"></th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        <?php foreach ($anfragen as $anfrage): ?>
                        <tr class="hover:bg-slate-50 transition-colors <?= $prioritaetColors[$anfrage['prioritaet']] ?>">
                            <td class="px-6 py-4 text-sm text-slate-400"><?= $anfrage['id'] ?></td>
                            <td class="px-6 py-4">
                                <div class="font-medium text-slate-900"><?= e($anfrage['name']) ?></div>
                                <div class="text-sm text-slate-500"><?= e($anfrage['email']) ?></div>
                            </td>
                            <td class="px-6 py-4">
                                <div class="text-slate-700"><?= e($anfrage['unternehmen']) ?></div>
                                <?php if ($anfrage['telefon']): ?>
                                <div class="text-sm text-slate-400"><?= e($anfrage['telefon']) ?></div>
                                <?php endif; ?>
                            </td>
                            <td class="px-6 py-4">
                                <span class="inline-flex items-center gap-1.5 px-2.5 py-1 text-xs font-medium rounded-lg border <?= $statusColors[$anfrage['status']] ?>">
                                    <?= $statusLabels[$anfrage['status']] ?>
                                </span>
                            </td>
                            <td class="px-6 py-4 text-sm text-slate-500">
                                <?= date('d.m.Y', strtotime($anfrage['erstellt_am'])) ?>
                                <div class="text-xs text-slate-400"><?= date('H:i', strtotime($anfrage['erstellt_am'])) ?> Uhr</div>
                            </td>
                            <td class="px-6 py-4">
                                <a href="view.php?id=<?= $anfrage['id'] ?>" class="inline-flex items-center gap-1 text-blue-600 hover:text-blue-800 text-sm font-medium transition-colors">
                                    Öffnen
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
