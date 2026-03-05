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

// Geschäftsjahre parsen
$geschaeftsjahre = [];
if (!empty($kunde['geschaeftsjahre'])) {
    $geschaeftsjahre = json_decode($kunde['geschaeftsjahre'], true) ?: [];
}

// Wirtschaftlich berechtigte Personen parsen
$wirtschaftlich_berechtigte = [];
if (!empty($kunde['wirtschaftlich_berechtigte'])) {
    $wirtschaftlich_berechtigte = json_decode($kunde['wirtschaftlich_berechtigte'], true) ?: [];
}

// Abschreibungen parsen
$abschreibungen = [];
if (!empty($kunde['abschreibungen'])) {
    $abschreibungen = json_decode($kunde['abschreibungen'], true) ?: [];
}

// Investitionsgüter parsen
$investitionsgueter = [];
if (!empty($kunde['investitionsgueter'])) {
    $investitionsgueter = json_decode($kunde['investitionsgueter'], true) ?: [];
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
                <a href="statistiken.php" class="flex items-center gap-3 px-4 py-3 rounded-xl text-slate-300 hover:bg-white/5 hover:text-white transition-all">
                    <iconify-icon icon="solar:chart-2-bold" width="20"></iconify-icon>
                    Statistiken
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
                            </div>
                        </div>
                    </div>

                    <!-- Unternehmensdaten -->
                    <div class="bg-white rounded-2xl border border-slate-200 shadow-sm overflow-hidden">
                        <div class="px-6 py-4 border-b border-slate-200 flex items-center gap-3">
                            <iconify-icon icon="solar:buildings-bold" class="text-blue-600" width="20"></iconify-icon>
                            <h2 class="font-semibold text-slate-900">Unternehmensdaten</h2>
                        </div>
                        <div class="p-6">
                            <div class="grid grid-cols-2 gap-6">
                                <div>
                                    <span class="text-xs text-slate-500 uppercase tracking-wide">Unternehmen</span>
                                    <p class="mt-1 text-slate-900 font-medium"><?= e($kunde['unternehmen'] ?: '-') ?></p>
                                </div>
                                <div>
                                    <span class="text-xs text-slate-500 uppercase tracking-wide">Rechtsform</span>
                                    <p class="mt-1 text-slate-900"><?= e($kunde['rechtsform'] ?? '-') ?></p>
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
                                <div>
                                    <span class="text-xs text-slate-500 uppercase tracking-wide">E-Mail (Unternehmen)</span>
                                    <p class="mt-1">
                                        <?php if (!empty($kunde['unternehmen_email'])): ?>
                                        <a href="mailto:<?= e($kunde['unternehmen_email']) ?>" class="text-blue-600 hover:underline"><?= e($kunde['unternehmen_email']) ?></a>
                                        <?php else: ?>
                                        <span class="text-slate-400">-</span>
                                        <?php endif; ?>
                                    </p>
                                </div>
                                <div>
                                    <span class="text-xs text-slate-500 uppercase tracking-wide">Gründungsdatum</span>
                                    <p class="mt-1 text-slate-900"><?= !empty($kunde['gruendungsdatum']) ? date('d.m.Y', strtotime($kunde['gruendungsdatum'])) : '-' ?></p>
                                </div>
                                <div>
                                    <span class="text-xs text-slate-500 uppercase tracking-wide">Branchenschlüssel</span>
                                    <p class="mt-1 text-slate-900 font-mono"><?= e($kunde['branchenschluessel'] ?: '-') ?></p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Adresse -->
                    <div class="bg-white rounded-2xl border border-slate-200 shadow-sm overflow-hidden">
                        <div class="px-6 py-4 border-b border-slate-200 flex items-center gap-3">
                            <iconify-icon icon="solar:home-bold" class="text-blue-600" width="20"></iconify-icon>
                            <h2 class="font-semibold text-slate-900">Unternehmensadresse</h2>
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

                    <!-- Durchführungsort -->
                    <div class="bg-white rounded-2xl border border-slate-200 shadow-sm overflow-hidden">
                        <div class="px-6 py-4 border-b border-slate-200 flex items-center gap-3">
                            <iconify-icon icon="solar:map-point-bold" class="text-blue-600" width="20"></iconify-icon>
                            <h2 class="font-semibold text-slate-900">Durchführungsort des Vorhabens</h2>
                        </div>
                        <div class="p-6">
                            <?php if ($kunde['durchfuehrungsort_gleich_adresse'] ?? 1): ?>
                            <p class="text-slate-600 flex items-center gap-2">
                                <iconify-icon icon="solar:check-circle-bold" class="text-emerald-600" width="18"></iconify-icon>
                                Gleich der Unternehmensadresse
                            </p>
                            <?php elseif ($kunde['durchfuehrungsort_strasse'] || $kunde['durchfuehrungsort_ort']): ?>
                            <p class="text-slate-900">
                                <?php if ($kunde['durchfuehrungsort_strasse']): ?>
                                <?= e($kunde['durchfuehrungsort_strasse']) ?> <?= e($kunde['durchfuehrungsort_hausnummer']) ?><br>
                                <?php endif; ?>
                                <?php if ($kunde['durchfuehrungsort_plz'] || $kunde['durchfuehrungsort_ort']): ?>
                                <?= e($kunde['durchfuehrungsort_plz']) ?> <?= e($kunde['durchfuehrungsort_ort']) ?>
                                <?php endif; ?>
                            </p>
                            <?php else: ?>
                            <p class="text-slate-400">Keine separate Adresse hinterlegt</p>
                            <?php endif; ?>
                        </div>
                    </div>

                    <!-- Wirtschaftlich berechtigte Personen -->
                    <div class="bg-white rounded-2xl border border-slate-200 shadow-sm overflow-hidden">
                        <div class="px-6 py-4 border-b border-slate-200 flex items-center gap-3">
                            <iconify-icon icon="solar:users-group-two-rounded-bold" class="text-blue-600" width="20"></iconify-icon>
                            <h2 class="font-semibold text-slate-900">Wirtschaftlich berechtigte Personen</h2>
                        </div>
                        <div class="p-6">
                            <?php if (!empty($wirtschaftlich_berechtigte)): ?>
                            <div class="overflow-x-auto">
                                <table class="w-full border-collapse text-sm">
                                    <thead>
                                        <tr class="bg-slate-50">
                                            <th class="px-4 py-2 text-left font-semibold text-slate-700 border border-slate-200">Name</th>
                                            <th class="px-4 py-2 text-left font-semibold text-slate-700 border border-slate-200">Steuer-ID</th>
                                            <th class="px-4 py-2 text-left font-semibold text-slate-700 border border-slate-200">Geburtsdatum</th>
                                            <th class="px-4 py-2 text-right font-semibold text-slate-700 border border-slate-200">Anteil</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($wirtschaftlich_berechtigte as $wb): ?>
                                        <?php if (!empty($wb['vorname']) || !empty($wb['nachname']) || !empty($wb['anteil'])): ?>
                                        <tr>
                                            <td class="px-4 py-2 border border-slate-200 font-medium"><?= e(trim(($wb['vorname'] ?? '') . ' ' . ($wb['nachname'] ?? ''))) ?: '-' ?></td>
                                            <td class="px-4 py-2 border border-slate-200 font-mono"><?= e($wb['steuer_id'] ?? '-') ?></td>
                                            <td class="px-4 py-2 border border-slate-200"><?= !empty($wb['geburtsdatum']) ? date('d.m.Y', strtotime($wb['geburtsdatum'])) : '-' ?></td>
                                            <td class="px-4 py-2 border border-slate-200 text-right"><?= e($wb['anteil'] ?? '-') ?> %</td>
                                        </tr>
                                        <?php endif; ?>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                            <?php else: ?>
                            <p class="text-slate-400">Keine wirtschaftlich berechtigten Personen hinterlegt</p>
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
                                    <span class="text-xs text-slate-500 uppercase tracking-wide">Steuernummer</span>
                                    <p class="mt-1 text-slate-900 font-mono"><?= e($kunde['elster_steuernummer'] ?: '-') ?></p>
                                </div>
                                <div>
                                    <span class="text-xs text-slate-500 uppercase tracking-wide">USt-IdNr</span>
                                    <p class="mt-1 text-slate-900 font-mono"><?= e($kunde['ust_id'] ?: '-') ?></p>
                                </div>
                                <div>
                                    <span class="text-xs text-slate-500 uppercase tracking-wide">Wirtschafts-ID (W-IdNr.)</span>
                                    <p class="mt-1 text-slate-900 font-mono"><?= e($kunde['w_idnr'] ?: '-') ?></p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Verdiente Abschreibungen -->
                    <div class="bg-white rounded-2xl border border-slate-200 shadow-sm overflow-hidden">
                        <div class="px-6 py-4 border-b border-slate-200 flex items-center gap-3">
                            <iconify-icon icon="solar:chart-2-bold" class="text-blue-600" width="20"></iconify-icon>
                            <h2 class="font-semibold text-slate-900">Verdiente Abschreibungen</h2>
                        </div>
                        <div class="p-6">
                            <?php if (!empty($abschreibungen)): ?>
                            <div class="overflow-x-auto">
                                <table class="w-full border-collapse text-sm">
                                    <thead>
                                        <tr class="bg-slate-50">
                                            <th class="px-4 py-2 text-left font-semibold text-slate-700 border border-slate-200">Jahr</th>
                                            <th class="px-4 py-2 text-right font-semibold text-slate-700 border border-slate-200">Abschreibungen (EUR)</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach (['2025', '2024', '2023'] as $jahr): ?>
                                        <tr>
                                            <td class="px-4 py-2 border border-slate-200 font-medium"><?= $jahr ?></td>
                                            <td class="px-4 py-2 border border-slate-200 text-right"><?= !empty($abschreibungen[$jahr]) ? number_format((float)$abschreibungen[$jahr], 0, ',', '.') . ' EUR' : '-' ?></td>
                                        </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                            <?php else: ?>
                            <p class="text-slate-400">Keine Abschreibungen hinterlegt</p>
                            <?php endif; ?>
                        </div>
                    </div>

                    <!-- Geschäftsjahre -->
                    <div class="bg-white rounded-2xl border border-slate-200 shadow-sm overflow-hidden">
                        <div class="px-6 py-4 border-b border-slate-200 flex items-center gap-3">
                            <iconify-icon icon="solar:chart-square-bold" class="text-blue-600" width="20"></iconify-icon>
                            <h2 class="font-semibold text-slate-900">Geschäftsjahre</h2>
                        </div>
                        <div class="p-6">
                            <?php if (!empty($geschaeftsjahre)): ?>
                            <div class="overflow-x-auto">
                                <table class="w-full border-collapse text-sm">
                                    <thead>
                                        <tr class="bg-slate-50">
                                            <th class="px-4 py-2 text-left font-semibold text-slate-700 border border-slate-200">Jahr</th>
                                            <th class="px-4 py-2 text-right font-semibold text-slate-700 border border-slate-200">Beschäftigte</th>
                                            <th class="px-4 py-2 text-right font-semibold text-slate-700 border border-slate-200">Umsatz</th>
                                            <th class="px-4 py-2 text-right font-semibold text-slate-700 border border-slate-200">Bilanzsumme</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach (['2025', '2024', '2023'] as $jahr): ?>
                                        <?php $gj = $geschaeftsjahre[$jahr] ?? []; ?>
                                        <tr>
                                            <td class="px-4 py-2 border border-slate-200 font-medium"><?= $jahr ?></td>
                                            <td class="px-4 py-2 border border-slate-200 text-right"><?= e($gj['beschaeftigte'] ?? '-') ?></td>
                                            <td class="px-4 py-2 border border-slate-200 text-right"><?= !empty($gj['umsatz']) ? number_format((float)$gj['umsatz'], 2, ',', '.') . ' EUR' : '-' ?></td>
                                            <td class="px-4 py-2 border border-slate-200 text-right"><?= !empty($gj['bilanzsumme']) ? number_format((float)$gj['bilanzsumme'], 2, ',', '.') . ' EUR' : '-' ?></td>
                                        </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                            <?php else: ?>
                            <p class="text-slate-400">Keine Geschäftsjahre-Daten hinterlegt</p>
                            <?php endif; ?>
                        </div>
                    </div>

                    <!-- Arbeitsplätze -->
                    <div class="bg-white rounded-2xl border border-slate-200 shadow-sm overflow-hidden">
                        <div class="px-6 py-4 border-b border-slate-200 flex items-center gap-3">
                            <iconify-icon icon="solar:user-check-bold" class="text-blue-600" width="20"></iconify-icon>
                            <h2 class="font-semibold text-slate-900">Arbeitsplätze</h2>
                        </div>
                        <div class="p-6">
                            <!-- Vorhandene Arbeitsplätze -->
                            <div class="mb-6">
                                <h4 class="text-sm font-semibold text-slate-700 mb-3">Vorhandene Dauerarbeitsplätze bei Antragstellung (VZÄ)</h4>
                                <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
                                    <div class="p-3 bg-slate-50 rounded-xl text-center">
                                        <span class="block text-xs text-slate-500 mb-1">Frauen</span>
                                        <span class="text-lg font-semibold text-slate-900"><?= e($kunde['arbeitsplaetze_frauen'] ?? '-') ?></span>
                                    </div>
                                    <div class="p-3 bg-slate-50 rounded-xl text-center">
                                        <span class="block text-xs text-slate-500 mb-1">Männer</span>
                                        <span class="text-lg font-semibold text-slate-900"><?= e($kunde['arbeitsplaetze_maenner'] ?? '-') ?></span>
                                    </div>
                                    <div class="p-3 bg-slate-50 rounded-xl text-center">
                                        <span class="block text-xs text-slate-500 mb-1">Ausbildung</span>
                                        <span class="text-lg font-semibold text-slate-900"><?= e($kunde['arbeitsplaetze_ausbildung'] ?? '-') ?></span>
                                    </div>
                                    <div class="p-3 bg-slate-50 rounded-xl text-center">
                                        <span class="block text-xs text-slate-500 mb-1">Leiharbeiter</span>
                                        <span class="text-lg font-semibold text-slate-900"><?= e($kunde['arbeitsplaetze_leiharbeiter'] ?? '-') ?></span>
                                    </div>
                                </div>
                            </div>

                            <!-- Geplante Arbeitsplätze -->
                            <div>
                                <h4 class="text-sm font-semibold text-slate-700 mb-3">Geplante zusätzliche Arbeitsplätze nach Investition (VZÄ)</h4>
                                <div class="grid grid-cols-2 md:grid-cols-3 gap-4">
                                    <div class="p-3 bg-emerald-50 rounded-xl text-center">
                                        <span class="block text-xs text-emerald-600 mb-1">Frauen</span>
                                        <span class="text-lg font-semibold text-emerald-700"><?= e($kunde['geplante_arbeitsplaetze_frauen'] ?? '-') ?></span>
                                    </div>
                                    <div class="p-3 bg-emerald-50 rounded-xl text-center">
                                        <span class="block text-xs text-emerald-600 mb-1">Männer</span>
                                        <span class="text-lg font-semibold text-emerald-700"><?= e($kunde['geplante_arbeitsplaetze_maenner'] ?? '-') ?></span>
                                    </div>
                                    <div class="p-3 bg-emerald-50 rounded-xl text-center">
                                        <span class="block text-xs text-emerald-600 mb-1">Ausbildung</span>
                                        <span class="text-lg font-semibold text-emerald-700"><?= e($kunde['geplante_arbeitsplaetze_ausbildung'] ?? '-') ?></span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Investitionsgüterliste -->
                    <div class="bg-white rounded-2xl border border-slate-200 shadow-sm overflow-hidden">
                        <div class="px-6 py-4 border-b border-slate-200 flex items-center gap-3">
                            <iconify-icon icon="solar:box-bold" class="text-blue-600" width="20"></iconify-icon>
                            <h2 class="font-semibold text-slate-900">Investitionsgüterliste</h2>
                        </div>
                        <div class="p-6">
                            <?php if (!empty($investitionsgueter)): ?>
                            <?php
                            $gesamtWert = 0;
                            foreach ($investitionsgueter as $inv) {
                                if (!empty($inv['wert'])) {
                                    $gesamtWert += (float)$inv['wert'];
                                }
                            }
                            ?>
                            <div class="overflow-x-auto">
                                <table class="w-full border-collapse text-sm">
                                    <thead>
                                        <tr class="bg-slate-50">
                                            <th class="px-4 py-2 text-left font-semibold text-slate-700 border border-slate-200">Bezeichnung</th>
                                            <th class="px-4 py-2 text-right font-semibold text-slate-700 border border-slate-200">Wert</th>
                                            <th class="px-4 py-2 text-center font-semibold text-slate-700 border border-slate-200">Gebraucht</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($investitionsgueter as $inv): ?>
                                        <?php if (!empty($inv['bezeichnung']) || !empty($inv['wert'])): ?>
                                        <tr>
                                            <td class="px-4 py-2 border border-slate-200"><?= e($inv['bezeichnung'] ?: '-') ?></td>
                                            <td class="px-4 py-2 border border-slate-200 text-right"><?= !empty($inv['wert']) ? number_format((float)$inv['wert'], 2, ',', '.') . ' EUR' : '-' ?></td>
                                            <td class="px-4 py-2 border border-slate-200 text-center">
                                                <?php if ($inv['gebraucht'] ?? false): ?>
                                                <span class="inline-flex items-center gap-1 px-2 py-0.5 text-xs font-medium rounded bg-amber-100 text-amber-700">
                                                    <iconify-icon icon="solar:check-circle-bold" width="12"></iconify-icon>
                                                    Ja
                                                </span>
                                                <?php else: ?>
                                                <span class="text-slate-400">-</span>
                                                <?php endif; ?>
                                            </td>
                                        </tr>
                                        <?php endif; ?>
                                        <?php endforeach; ?>
                                    </tbody>
                                    <tfoot>
                                        <tr class="bg-blue-50">
                                            <td class="px-4 py-2 border border-slate-200 font-semibold text-slate-900">Gesamtsumme</td>
                                            <td class="px-4 py-2 border border-slate-200 text-right font-semibold text-blue-700"><?= number_format($gesamtWert, 2, ',', '.') ?> EUR</td>
                                            <td class="px-4 py-2 border border-slate-200"></td>
                                        </tr>
                                    </tfoot>
                                </table>
                            </div>
                            <?php else: ?>
                            <p class="text-slate-400">Keine Investitionsgüter hinterlegt</p>
                            <?php endif; ?>
                        </div>
                    </div>

                    <!-- Webseite & Social Media -->
                    <div class="bg-white rounded-2xl border border-slate-200 shadow-sm overflow-hidden">
                        <div class="px-6 py-4 border-b border-slate-200 flex items-center gap-3">
                            <iconify-icon icon="solar:global-bold" class="text-blue-600" width="20"></iconify-icon>
                            <h2 class="font-semibold text-slate-900">Webseite & Social Media</h2>
                        </div>
                        <div class="p-6">
                            <div class="mb-4">
                                <span class="text-xs text-slate-500 uppercase tracking-wide">Webseite</span>
                                <p class="mt-1">
                                    <?php if (!empty($kunde['webseite_url'])): ?>
                                    <a href="<?= e($kunde['webseite_url']) ?>" target="_blank" class="text-blue-600 hover:underline flex items-center gap-1">
                                        <?= e($kunde['webseite_url']) ?>
                                        <iconify-icon icon="solar:arrow-right-up-linear" width="14"></iconify-icon>
                                    </a>
                                    <?php else: ?>
                                    <span class="text-slate-400">-</span>
                                    <?php endif; ?>
                                </p>
                            </div>
                            <div>
                                <span class="text-xs text-slate-500 uppercase tracking-wide mb-3 block">Social Media Kanäle</span>
                                <div class="flex flex-wrap gap-3">
                                    <?php if (!empty($kunde['social_youtube'])): ?>
                                    <span class="inline-flex items-center gap-2 px-3 py-1.5 bg-red-50 text-red-700 rounded-lg text-sm">
                                        <iconify-icon icon="logos:youtube-icon" width="16"></iconify-icon>
                                        YouTube
                                    </span>
                                    <?php endif; ?>
                                    <?php if (!empty($kunde['social_instagram'])): ?>
                                    <span class="inline-flex items-center gap-2 px-3 py-1.5 bg-pink-50 text-pink-700 rounded-lg text-sm">
                                        <iconify-icon icon="skill-icons:instagram" width="16"></iconify-icon>
                                        Instagram
                                    </span>
                                    <?php endif; ?>
                                    <?php if (!empty($kunde['social_linkedin'])): ?>
                                    <span class="inline-flex items-center gap-2 px-3 py-1.5 bg-blue-50 text-blue-700 rounded-lg text-sm">
                                        <iconify-icon icon="skill-icons:linkedin" width="16"></iconify-icon>
                                        LinkedIn
                                    </span>
                                    <?php endif; ?>
                                    <?php if (!empty($kunde['social_facebook'])): ?>
                                    <span class="inline-flex items-center gap-2 px-3 py-1.5 bg-blue-50 text-blue-700 rounded-lg text-sm">
                                        <iconify-icon icon="logos:facebook" width="16"></iconify-icon>
                                        Facebook
                                    </span>
                                    <?php endif; ?>
                                    <?php if (empty($kunde['social_youtube']) && empty($kunde['social_instagram']) && empty($kunde['social_linkedin']) && empty($kunde['social_facebook'])): ?>
                                    <span class="text-slate-400">Keine Social Media Kanäle angegeben</span>
                                    <?php endif; ?>
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
                                <span class="text-sm text-slate-500">Unternehmensdaten</span>
                                <?php if ($kunde['unternehmen'] && $kunde['rechtsform']): ?>
                                <iconify-icon icon="solar:check-circle-bold" class="text-emerald-600" width="20"></iconify-icon>
                                <?php else: ?>
                                <iconify-icon icon="solar:clock-circle-bold" class="text-amber-500" width="20"></iconify-icon>
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
                                <span class="text-sm text-slate-500">Wirtschaftl. Berechtigte</span>
                                <?php if (!empty($wirtschaftlich_berechtigte)): ?>
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
                            <div class="flex items-center justify-between">
                                <span class="text-sm text-slate-500">Geschäftsjahre</span>
                                <?php if (!empty($geschaeftsjahre)): ?>
                                <iconify-icon icon="solar:check-circle-bold" class="text-emerald-600" width="20"></iconify-icon>
                                <?php else: ?>
                                <iconify-icon icon="solar:clock-circle-bold" class="text-amber-500" width="20"></iconify-icon>
                                <?php endif; ?>
                            </div>
                            <div class="flex items-center justify-between">
                                <span class="text-sm text-slate-500">Abschreibungen</span>
                                <?php if (!empty($abschreibungen)): ?>
                                <iconify-icon icon="solar:check-circle-bold" class="text-emerald-600" width="20"></iconify-icon>
                                <?php else: ?>
                                <iconify-icon icon="solar:clock-circle-bold" class="text-amber-500" width="20"></iconify-icon>
                                <?php endif; ?>
                            </div>
                            <div class="flex items-center justify-between">
                                <span class="text-sm text-slate-500">Arbeitsplätze</span>
                                <?php if ($kunde['arbeitsplaetze_frauen'] !== null || $kunde['arbeitsplaetze_maenner'] !== null): ?>
                                <iconify-icon icon="solar:check-circle-bold" class="text-emerald-600" width="20"></iconify-icon>
                                <?php else: ?>
                                <iconify-icon icon="solar:clock-circle-bold" class="text-amber-500" width="20"></iconify-icon>
                                <?php endif; ?>
                            </div>
                            <div class="flex items-center justify-between">
                                <span class="text-sm text-slate-500">Investitionsgüter</span>
                                <?php if (!empty($investitionsgueter)): ?>
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
