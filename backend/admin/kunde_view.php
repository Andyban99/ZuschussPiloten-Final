<?php
/**
 * Zuschuss Piloten - Admin Kundendetails
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

if (!$kunde) {
    header('Location: kunden.php');
    exit;
}

// Status ändern
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    if ($_POST['action'] === 'toggle_status') {
        $newStatus = $kunde['aktiv'] ? 0 : 1;
        $stmt = $db->prepare("UPDATE kunden SET aktiv = :aktiv WHERE id = :id");
        $stmt->execute([':aktiv' => $newStatus, ':id' => $id]);
        header("Location: kunde_view.php?id={$id}&updated=1");
        exit;
    }
}

// IBAN formatieren
function formatIBAN($iban) {
    if (empty($iban)) return '-';
    return implode(' ', str_split($iban, 4));
}
?>
<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kunde #<?= $id ?> - Zuschuss Piloten Admin</title>
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
                <a href="kunden.php" class="flex items-center gap-3 px-4 py-3 rounded-xl bg-white/10 text-white font-medium">
                    <iconify-icon icon="solar:users-group-rounded-bold" width="20"></iconify-icon>
                    Kundendaten
                </a>
                <a href="einstellungen.php" class="flex items-center gap-3 px-4 py-3 rounded-xl text-slate-300 hover:bg-white/5 hover:text-white transition-all">
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
            <!-- Header -->
            <div class="flex items-center justify-between mb-8">
                <div class="flex items-center gap-4">
                    <a href="kunden.php" class="w-10 h-10 bg-white rounded-xl border border-slate-200 flex items-center justify-center hover:bg-slate-50 transition-colors">
                        <iconify-icon icon="solar:arrow-left-linear" width="20" class="text-slate-600"></iconify-icon>
                    </a>
                    <div>
                        <h1 class="text-2xl font-bold text-slate-900">Kunde #<?= $id ?></h1>
                        <p class="text-slate-500"><?= e($kunde['email']) ?></p>
                    </div>
                </div>
                <div class="flex items-center gap-3">
                    <form method="POST" class="inline">
                        <input type="hidden" name="action" value="toggle_status">
                        <button type="submit" class="px-4 py-2.5 <?= $kunde['aktiv'] ? 'bg-red-100 text-red-700 hover:bg-red-200' : 'bg-emerald-100 text-emerald-700 hover:bg-emerald-200' ?> rounded-xl text-sm font-medium transition-colors flex items-center gap-2">
                            <iconify-icon icon="<?= $kunde['aktiv'] ? 'solar:close-circle-bold' : 'solar:check-circle-bold' ?>" width="18"></iconify-icon>
                            <?= $kunde['aktiv'] ? 'Deaktivieren' : 'Aktivieren' ?>
                        </button>
                    </form>
                </div>
            </div>

            <?php if (isset($_GET['updated'])): ?>
            <div class="mb-6 p-4 bg-emerald-50 border border-emerald-200 rounded-xl flex items-start gap-3">
                <iconify-icon icon="solar:check-circle-bold" class="text-emerald-500 text-xl flex-shrink-0 mt-0.5"></iconify-icon>
                <span class="text-emerald-700">Status wurde erfolgreich geändert.</span>
            </div>
            <?php endif; ?>

            <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                <!-- Hauptinfo -->
                <div class="lg:col-span-2 space-y-6">
                    <!-- Persönliche Daten -->
                    <div class="bg-white rounded-2xl border border-slate-200 shadow-sm overflow-hidden">
                        <div class="px-6 py-4 border-b border-slate-200 flex items-center gap-3">
                            <iconify-icon icon="solar:user-bold" class="text-blue-600" width="20"></iconify-icon>
                            <h2 class="font-semibold text-slate-900">Persönliche Daten</h2>
                        </div>
                        <div class="p-6">
                            <div class="grid grid-cols-2 gap-6">
                                <div>
                                    <span class="text-xs text-slate-500 uppercase tracking-wide">Name</span>
                                    <p class="mt-1 text-slate-900 font-medium"><?= e($kunde['vorname'] . ' ' . $kunde['nachname']) ?></p>
                                </div>
                                <div>
                                    <span class="text-xs text-slate-500 uppercase tracking-wide">E-Mail</span>
                                    <p class="mt-1">
                                        <a href="mailto:<?= e($kunde['email']) ?>" class="text-blue-600 hover:underline"><?= e($kunde['email']) ?></a>
                                    </p>
                                </div>
                                <div>
                                    <span class="text-xs text-slate-500 uppercase tracking-wide">Unternehmen</span>
                                    <p class="mt-1 text-slate-900"><?= e($kunde['unternehmen'] ?: '-') ?></p>
                                </div>
                                <div>
                                    <span class="text-xs text-slate-500 uppercase tracking-wide">Telefon</span>
                                    <p class="mt-1">
                                        <?php if ($kunde['telefon']): ?>
                                        <a href="tel:<?= e($kunde['telefon']) ?>" class="text-blue-600 hover:underline"><?= e($kunde['telefon']) ?></a>
                                        <?php else: ?>
                                        <span class="text-slate-400">-</span>
                                        <?php endif; ?>
                                    </p>
                                </div>
                                <div class="col-span-2">
                                    <span class="text-xs text-slate-500 uppercase tracking-wide">Webseite</span>
                                    <p class="mt-1">
                                        <?php if ($kunde['hat_webseite'] && $kunde['webseite_url']): ?>
                                        <a href="<?= e($kunde['webseite_url']) ?>" target="_blank" class="text-blue-600 hover:underline flex items-center gap-1">
                                            <?= e($kunde['webseite_url']) ?>
                                            <iconify-icon icon="solar:arrow-right-up-linear" width="14"></iconify-icon>
                                        </a>
                                        <?php elseif ($kunde['hat_webseite']): ?>
                                        <span class="text-slate-500">Ja (URL nicht angegeben)</span>
                                        <?php else: ?>
                                        <span class="text-slate-400">Keine Webseite</span>
                                        <?php endif; ?>
                                    </p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Adresse -->
                    <div class="bg-white rounded-2xl border border-slate-200 shadow-sm overflow-hidden">
                        <div class="px-6 py-4 border-b border-slate-200 flex items-center gap-3">
                            <iconify-icon icon="solar:home-bold" class="text-blue-600" width="20"></iconify-icon>
                            <h2 class="font-semibold text-slate-900">Adresse</h2>
                        </div>
                        <div class="p-6">
                            <?php if ($kunde['strasse'] || $kunde['ort']): ?>
                            <p class="text-slate-900">
                                <?php if ($kunde['strasse']): ?>
                                <?= e($kunde['strasse']) ?> <?= e($kunde['hausnummer']) ?><br>
                                <?php endif; ?>
                                <?php if ($kunde['plz'] || $kunde['ort']): ?>
                                <?= e($kunde['plz']) ?> <?= e($kunde['ort']) ?>
                                <?php endif; ?>
                            </p>
                            <?php else: ?>
                            <p class="text-slate-400">Keine Adresse hinterlegt</p>
                            <?php endif; ?>
                        </div>
                    </div>

                    <!-- Steuerdaten -->
                    <div class="bg-white rounded-2xl border border-slate-200 shadow-sm overflow-hidden">
                        <div class="px-6 py-4 border-b border-slate-200 flex items-center gap-3">
                            <iconify-icon icon="solar:document-text-bold" class="text-blue-600" width="20"></iconify-icon>
                            <h2 class="font-semibold text-slate-900">Steuerdaten</h2>
                        </div>
                        <div class="p-6">
                            <div class="grid grid-cols-2 gap-6">
                                <div>
                                    <span class="text-xs text-slate-500 uppercase tracking-wide">Elster-Steuernummer</span>
                                    <p class="mt-1 text-slate-900 font-mono">
                                        <?php if ($kunde['hat_elster_steuernummer'] && $kunde['elster_steuernummer']): ?>
                                        <?= e($kunde['elster_steuernummer']) ?>
                                        <?php elseif ($kunde['hat_elster_steuernummer']): ?>
                                        <span class="text-slate-500">Vorhanden (nicht angegeben)</span>
                                        <?php else: ?>
                                        <span class="text-slate-400">-</span>
                                        <?php endif; ?>
                                    </p>
                                </div>
                                <div>
                                    <span class="text-xs text-slate-500 uppercase tracking-wide">USt-IdNr</span>
                                    <p class="mt-1 text-slate-900 font-mono"><?= e($kunde['ust_id'] ?: '-') ?></p>
                                </div>
                                <div>
                                    <span class="text-xs text-slate-500 uppercase tracking-wide">Wirtschafts-ID (W-IdNr.)</span>
                                    <p class="mt-1 text-slate-900 font-mono"><?= e($kunde['w_idnr'] ?: '-') ?></p>
                                </div>
                                <div>
                                    <span class="text-xs text-slate-500 uppercase tracking-wide">Branchenschlüssel</span>
                                    <p class="mt-1 text-slate-900 font-mono"><?= e($kunde['branchenschluessel'] ?: '-') ?></p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Bankdaten -->
                    <div class="bg-white rounded-2xl border border-slate-200 shadow-sm overflow-hidden">
                        <div class="px-6 py-4 border-b border-slate-200 flex items-center gap-3">
                            <iconify-icon icon="solar:card-bold" class="text-blue-600" width="20"></iconify-icon>
                            <h2 class="font-semibold text-slate-900">Bankverbindung</h2>
                        </div>
                        <div class="p-6">
                            <?php if ($kunde['iban']): ?>
                            <div class="grid grid-cols-2 gap-6">
                                <div class="col-span-2">
                                    <span class="text-xs text-slate-500 uppercase tracking-wide">IBAN</span>
                                    <p class="mt-1 text-slate-900 font-mono text-lg"><?= e(formatIBAN($kunde['iban'])) ?></p>
                                </div>
                                <div>
                                    <span class="text-xs text-slate-500 uppercase tracking-wide">BIC</span>
                                    <p class="mt-1 text-slate-900 font-mono"><?= e($kunde['bic'] ?: '-') ?></p>
                                </div>
                                <div>
                                    <span class="text-xs text-slate-500 uppercase tracking-wide">Bank</span>
                                    <p class="mt-1 text-slate-900"><?= e($kunde['bank_name'] ?: '-') ?></p>
                                </div>
                            </div>
                            <?php else: ?>
                            <p class="text-slate-400">Keine Bankdaten hinterlegt</p>
                            <?php endif; ?>
                        </div>
                    </div>

                    <!-- Hochgeladene Dokumente -->
                    <div class="bg-white rounded-2xl border border-slate-200 shadow-sm overflow-hidden">
                        <div class="px-6 py-4 border-b border-slate-200 flex items-center gap-3">
                            <iconify-icon icon="solar:file-bold" class="text-emerald-600" width="20"></iconify-icon>
                            <h2 class="font-semibold text-slate-900">Hochgeladene Dokumente</h2>
                        </div>
                        <div class="p-6">
                            <?php if ($kunde['excel_datei']): ?>
                            <div class="flex items-center justify-between p-4 bg-slate-50 rounded-xl">
                                <div class="flex items-center gap-3">
                                    <div class="w-10 h-10 bg-emerald-100 rounded-lg flex items-center justify-center">
                                        <iconify-icon icon="solar:file-check-bold" class="text-emerald-600" width="20"></iconify-icon>
                                    </div>
                                    <div>
                                        <p class="font-medium text-slate-900"><?= e($kunde['excel_datei_original']) ?></p>
                                        <p class="text-sm text-slate-500">Hochgeladen am <?= date('d.m.Y \u\m H:i', strtotime($kunde['excel_hochgeladen_am'])) ?> Uhr</p>
                                    </div>
                                </div>
                                <a href="download_kunde_excel.php?id=<?= $id ?>"
                                   class="px-4 py-2 bg-emerald-600 hover:bg-emerald-700 text-white rounded-lg text-sm font-medium transition-colors flex items-center gap-2">
                                    <iconify-icon icon="solar:download-bold" width="16"></iconify-icon>
                                    Download
                                </a>
                            </div>
                            <?php else: ?>
                            <p class="text-slate-400">Keine Dokumente hochgeladen</p>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>

                <!-- Sidebar Info -->
                <div class="space-y-6">
                    <!-- Status -->
                    <div class="bg-white rounded-2xl border border-slate-200 shadow-sm p-6">
                        <h3 class="font-semibold text-slate-900 mb-4">Status</h3>
                        <div class="space-y-4">
                            <div class="flex items-center justify-between">
                                <span class="text-sm text-slate-500">Kontostatus</span>
                                <?php if ($kunde['aktiv']): ?>
                                <span class="inline-flex items-center gap-1.5 px-2.5 py-1 text-xs font-medium rounded-lg bg-emerald-100 text-emerald-700">
                                    <span class="w-1.5 h-1.5 rounded-full bg-emerald-500"></span>
                                    Aktiv
                                </span>
                                <?php else: ?>
                                <span class="inline-flex items-center gap-1.5 px-2.5 py-1 text-xs font-medium rounded-lg bg-red-100 text-red-700">
                                    <span class="w-1.5 h-1.5 rounded-full bg-red-500"></span>
                                    Inaktiv
                                </span>
                                <?php endif; ?>
                            </div>
                            <div class="flex items-center justify-between">
                                <span class="text-sm text-slate-500">Adresse</span>
                                <?php if ($kunde['strasse'] && $kunde['ort']): ?>
                                <iconify-icon icon="solar:check-circle-bold" class="text-emerald-600" width="20"></iconify-icon>
                                <?php else: ?>
                                <iconify-icon icon="solar:clock-circle-bold" class="text-amber-500" width="20"></iconify-icon>
                                <?php endif; ?>
                            </div>
                            <div class="flex items-center justify-between">
                                <span class="text-sm text-slate-500">Bankdaten</span>
                                <?php if ($kunde['iban']): ?>
                                <iconify-icon icon="solar:check-circle-bold" class="text-emerald-600" width="20"></iconify-icon>
                                <?php else: ?>
                                <iconify-icon icon="solar:clock-circle-bold" class="text-amber-500" width="20"></iconify-icon>
                                <?php endif; ?>
                            </div>
                            <div class="flex items-center justify-between">
                                <span class="text-sm text-slate-500">Steuerdaten</span>
                                <?php if ($kunde['ust_id'] || $kunde['elster_steuernummer']): ?>
                                <iconify-icon icon="solar:check-circle-bold" class="text-emerald-600" width="20"></iconify-icon>
                                <?php else: ?>
                                <iconify-icon icon="solar:clock-circle-bold" class="text-amber-500" width="20"></iconify-icon>
                                <?php endif; ?>
                            </div>
                            <div class="flex items-center justify-between">
                                <span class="text-sm text-slate-500">Excel-Datei</span>
                                <?php if ($kunde['excel_datei']): ?>
                                <iconify-icon icon="solar:check-circle-bold" class="text-emerald-600" width="20"></iconify-icon>
                                <?php else: ?>
                                <iconify-icon icon="solar:clock-circle-bold" class="text-amber-500" width="20"></iconify-icon>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>

                    <!-- Zeitstempel -->
                    <div class="bg-white rounded-2xl border border-slate-200 shadow-sm p-6">
                        <h3 class="font-semibold text-slate-900 mb-4">Aktivität</h3>
                        <div class="space-y-4">
                            <div>
                                <span class="text-xs text-slate-500 uppercase tracking-wide">Registriert am</span>
                                <p class="mt-1 text-slate-900"><?= date('d.m.Y \u\m H:i', strtotime($kunde['erstellt_am'])) ?> Uhr</p>
                            </div>
                            <?php if ($kunde['letzter_login']): ?>
                            <div>
                                <span class="text-xs text-slate-500 uppercase tracking-wide">Letzter Login</span>
                                <p class="mt-1 text-slate-900"><?= date('d.m.Y \u\m H:i', strtotime($kunde['letzter_login'])) ?> Uhr</p>
                            </div>
                            <?php endif; ?>
                            <?php if ($kunde['aktualisiert_am']): ?>
                            <div>
                                <span class="text-xs text-slate-500 uppercase tracking-wide">Daten aktualisiert</span>
                                <p class="mt-1 text-slate-900"><?= date('d.m.Y \u\m H:i', strtotime($kunde['aktualisiert_am'])) ?> Uhr</p>
                            </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
        </main>
    </div>
</body>
</html>
