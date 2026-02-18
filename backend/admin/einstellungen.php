<?php
/**
 * Zuschuss Piloten - Admin Einstellungen
 */

require_once 'auth.php';
requireLogin();

$settingsFile = __DIR__ . '/../settings.json';
$settings = json_decode(file_get_contents($settingsFile), true);

$success = '';

// Einstellung speichern
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    if ($_POST['action'] === 'toggle_kundenportal') {
        $settings['kundenportal_aktiv'] = !$settings['kundenportal_aktiv'];
        file_put_contents($settingsFile, json_encode($settings, JSON_PRETTY_PRINT));
        $success = 'Einstellung wurde gespeichert.';
    }
}
?>
<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Einstellungen - Zuschuss Piloten Admin</title>
    <link rel="icon" type="image/svg+xml" href="../../assets/favicon.svg">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://code.iconify.design/iconify-icon/1.0.7/iconify-icon.min.js"></script>
    <style>
        body { font-family: 'Inter', sans-serif; }
        .sidebar { background: linear-gradient(180deg, #0B1120 0%, #1e293b 100%); }
        .toggle-checkbox:checked + .toggle-label {
            background-color: #10b981;
        }
        .toggle-checkbox:checked + .toggle-label .toggle-dot {
            transform: translateX(100%);
        }
    </style>
</head>
<body class="bg-slate-100 min-h-screen">
    <div class="flex">
        <!-- Sidebar -->
        <aside class="sidebar w-64 min-h-screen fixed left-0 top-0 text-white p-6 flex flex-col">
            <div class="flex items-center gap-3 mb-10">
                <div class="w-10 h-10 bg-blue-600 rounded-lg flex items-center justify-center shadow-lg shadow-blue-600/30">
                    <iconify-icon icon="solar:plain-3-bold-duotone" width="24"></iconify-icon>
                </div>
                <div>
                    <span class="block font-bold text-lg tracking-tight">Zuschuss Piloten</span>
                    <span class="block text-[10px] text-slate-400 uppercase tracking-widest">Admin</span>
                </div>
            </div>

            <nav class="flex-1 space-y-2">
                <a href="index.php" class="flex items-center gap-3 px-4 py-3 rounded-xl text-slate-300 hover:bg-white/5 hover:text-white transition-all">
                    <iconify-icon icon="solar:widget-bold" width="20"></iconify-icon>
                    Dashboard
                </a>
                <a href="kunden.php" class="flex items-center gap-3 px-4 py-3 rounded-xl text-slate-300 hover:bg-white/5 hover:text-white transition-all">
                    <iconify-icon icon="solar:users-group-rounded-bold" width="20"></iconify-icon>
                    Kundendaten
                </a>
                <a href="einstellungen.php" class="flex items-center gap-3 px-4 py-3 rounded-xl bg-white/10 text-white font-medium">
                    <iconify-icon icon="solar:settings-bold" width="20"></iconify-icon>
                    Einstellungen
                </a>
            </nav>

            <div class="border-t border-white/10 pt-6 mt-6">
                <a href="logout.php" class="flex items-center gap-2 text-sm text-slate-400 hover:text-white transition-colors">
                    <iconify-icon icon="solar:logout-2-bold" width="18"></iconify-icon>
                    Abmelden
                </a>
            </div>
        </aside>

        <!-- Main Content -->
        <main class="ml-64 flex-1 p-8">
            <div class="mb-8">
                <h1 class="text-2xl font-bold text-slate-900">Einstellungen</h1>
                <p class="text-slate-500">Website-Funktionen verwalten</p>
            </div>

            <?php if ($success): ?>
            <div class="mb-6 p-4 bg-emerald-50 border border-emerald-200 rounded-xl flex items-start gap-3">
                <iconify-icon icon="solar:check-circle-bold" class="text-emerald-500 text-xl flex-shrink-0 mt-0.5"></iconify-icon>
                <span class="text-emerald-700"><?= e($success) ?></span>
            </div>
            <?php endif; ?>

            <!-- Kundenportal Einstellung -->
            <div class="bg-white rounded-2xl border border-slate-200 shadow-sm overflow-hidden">
                <div class="px-6 py-4 border-b border-slate-200">
                    <h2 class="font-semibold text-slate-900">Kundenportal</h2>
                </div>
                <div class="p-6">
                    <div class="flex items-center justify-between">
                        <div class="flex items-center gap-4">
                            <div class="w-12 h-12 <?= $settings['kundenportal_aktiv'] ? 'bg-emerald-100' : 'bg-slate-100' ?> rounded-xl flex items-center justify-center transition-colors">
                                <iconify-icon icon="solar:login-2-bold" width="24" class="<?= $settings['kundenportal_aktiv'] ? 'text-emerald-600' : 'text-slate-400' ?>"></iconify-icon>
                            </div>
                            <div>
                                <h3 class="font-medium text-slate-900">"Kunden Login" im Header</h3>
                                <p class="text-sm text-slate-500">Zeigt oder versteckt den Login-Link für Kunden in der Navigation</p>
                            </div>
                        </div>
                        <form method="POST" class="flex items-center gap-4">
                            <input type="hidden" name="action" value="toggle_kundenportal">

                            <span class="text-sm font-medium <?= $settings['kundenportal_aktiv'] ? 'text-emerald-600' : 'text-slate-400' ?>">
                                <?= $settings['kundenportal_aktiv'] ? 'Aktiviert' : 'Deaktiviert' ?>
                            </span>

                            <button type="submit" class="relative inline-flex h-7 w-14 items-center rounded-full transition-colors focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 <?= $settings['kundenportal_aktiv'] ? 'bg-emerald-500' : 'bg-slate-300' ?>">
                                <span class="inline-block h-5 w-5 transform rounded-full bg-white shadow-lg transition-transform <?= $settings['kundenportal_aktiv'] ? 'translate-x-8' : 'translate-x-1' ?>"></span>
                            </button>
                        </form>
                    </div>

                    <?php if (!$settings['kundenportal_aktiv']): ?>
                    <div class="mt-4 p-4 bg-amber-50 border border-amber-200 rounded-xl flex items-start gap-3">
                        <iconify-icon icon="solar:info-circle-bold" class="text-amber-500 text-xl flex-shrink-0 mt-0.5"></iconify-icon>
                        <p class="text-sm text-amber-800">Das Kundenportal ist deaktiviert. Der "Kunden Login" Link wird nicht mehr im Header angezeigt. Kunden können sich weiterhin über die direkte URL anmelden.</p>
                    </div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Info -->
            <div class="mt-6 p-4 bg-blue-50 border border-blue-200 rounded-xl flex items-start gap-3">
                <iconify-icon icon="solar:info-circle-bold" class="text-blue-500 text-xl flex-shrink-0 mt-0.5"></iconify-icon>
                <div class="text-sm text-blue-800">
                    <p class="font-medium">Direkter Zugang zum Kundenportal:</p>
                    <code class="mt-1 block bg-blue-100 px-2 py-1 rounded text-xs">/backend/kunde/login.php</code>
                </div>
            </div>
        </main>
    </div>
</body>
</html>
