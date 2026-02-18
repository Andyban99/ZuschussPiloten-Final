<?php
/**
 * Zuschuss Piloten - Kunden Dashboard
 */

require_once 'auth.php';
requireKundeLogin();

$kunde_id = $_SESSION['kunde_id'];
$kunde = getKundeData($kunde_id);

$success = '';
$error = '';

// Willkommensnachricht für neue Registrierungen
if (isset($_GET['welcome'])) {
    $success = 'Willkommen bei Zuschuss Piloten! Bitte füllen Sie Ihre Daten aus.';
}

// Excel-Upload verarbeiten
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'upload_excel') {
    if (!validateCSRFToken($_POST['csrf_token'] ?? '')) {
        $error = 'Ungültige Anfrage. Bitte versuchen Sie es erneut.';
    } elseif (isset($_FILES['excel_file']) && $_FILES['excel_file']['error'] === UPLOAD_ERR_OK) {
        $result = saveKundeExcel($kunde_id, $_FILES['excel_file']);
        if ($result['success']) {
            $success = 'Datei wurde erfolgreich hochgeladen.';
            $kunde = getKundeData($kunde_id);
        } else {
            $error = $result['error'];
        }
    } else {
        $error = 'Bitte wählen Sie eine Datei aus.';
    }
}

// Excel-Datei löschen
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'delete_excel') {
    if (!validateCSRFToken($_POST['csrf_token'] ?? '')) {
        $error = 'Ungültige Anfrage.';
    } else {
        if (deleteKundeExcel($kunde_id)) {
            $success = 'Datei wurde gelöscht.';
            $kunde = getKundeData($kunde_id);
        } else {
            $error = 'Fehler beim Löschen der Datei.';
        }
    }
}

// Formular verarbeiten
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'save') {
    if (!validateCSRFToken($_POST['csrf_token'] ?? '')) {
        $error = 'Ungültige Anfrage. Bitte versuchen Sie es erneut.';
    } else {
        $data = [
            'vorname' => trim($_POST['vorname'] ?? ''),
            'nachname' => trim($_POST['nachname'] ?? ''),
            'strasse' => trim($_POST['strasse'] ?? ''),
            'hausnummer' => trim($_POST['hausnummer'] ?? ''),
            'plz' => trim($_POST['plz'] ?? ''),
            'ort' => trim($_POST['ort'] ?? ''),
            'telefon' => trim($_POST['telefon'] ?? ''),
            'iban' => preg_replace('/\s+/', '', $_POST['iban'] ?? ''),
            'bic' => trim($_POST['bic'] ?? ''),
            'bank_name' => trim($_POST['bank_name'] ?? ''),
            'unternehmen' => trim($_POST['unternehmen'] ?? ''),
            'hat_webseite' => isset($_POST['hat_webseite']) ? 1 : 0,
            'webseite_url' => trim($_POST['webseite_url'] ?? ''),
            'hat_elster_steuernummer' => isset($_POST['hat_elster_steuernummer']) ? 1 : 0,
            'elster_steuernummer' => trim($_POST['elster_steuernummer'] ?? ''),
            'ust_id' => trim($_POST['ust_id'] ?? ''),
            'w_idnr' => trim($_POST['w_idnr'] ?? ''),
            'branchenschluessel' => trim($_POST['branchenschluessel'] ?? '')
        ];

        // Validierung
        if (empty($data['vorname']) || empty($data['nachname'])) {
            $error = 'Vorname und Nachname sind Pflichtfelder.';
        } elseif (!empty($data['iban']) && !preg_match('/^[A-Z]{2}\d{2}[A-Z0-9]{4,30}$/', $data['iban'])) {
            $error = 'Bitte geben Sie eine gültige IBAN ein.';
        } elseif ($data['hat_webseite'] && !empty($data['webseite_url']) && !filter_var($data['webseite_url'], FILTER_VALIDATE_URL)) {
            $error = 'Bitte geben Sie eine gültige Webseiten-URL ein (mit https://).';
        } else {
            if (saveKundeData($kunde_id, $data)) {
                $success = 'Ihre Daten wurden erfolgreich gespeichert.';
                $kunde = getKundeData($kunde_id);
            } else {
                $error = 'Beim Speichern ist ein Fehler aufgetreten.';
            }
        }
    }
}

$csrf_token = generateCSRFToken();

// IBAN formatieren für Anzeige
function formatIBAN($iban) {
    if (empty($iban)) return '';
    return implode(' ', str_split($iban, 4));
}
?>
<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mein Dashboard - Zuschuss Piloten</title>
    <link rel="icon" type="image/svg+xml" href="../../assets/Icon Black White BG.svg">
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
                    <span class="block text-[10px] text-slate-400 uppercase tracking-widest">Kundenportal</span>
                </div>
            </div>

            <nav class="flex-1 space-y-2">
                <a href="dashboard.php" class="flex items-center gap-3 px-4 py-3 rounded-xl bg-white/10 text-white font-medium">
                    <iconify-icon icon="solar:home-2-bold" width="20"></iconify-icon>
                    Dashboard
                </a>
                <a href="dashboard.php#daten" class="flex items-center gap-3 px-4 py-3 rounded-xl text-slate-300 hover:bg-white/5 hover:text-white transition-all">
                    <iconify-icon icon="solar:user-bold" width="20"></iconify-icon>
                    Meine Daten
                </a>
                <a href="dashboard.php#dokumente" class="flex items-center gap-3 px-4 py-3 rounded-xl text-slate-300 hover:bg-white/5 hover:text-white transition-all">
                    <iconify-icon icon="solar:file-bold" width="20"></iconify-icon>
                    Dokumente
                </a>
            </nav>

            <div class="border-t border-white/10 pt-6 mt-6">
                <div class="flex items-center gap-3 mb-4">
                    <div class="w-10 h-10 bg-blue-600/50 rounded-full flex items-center justify-center">
                        <span class="text-white font-semibold">
                            <?= strtoupper(substr($kunde['vorname'] ?? 'K', 0, 1) . substr($kunde['nachname'] ?? 'U', 0, 1)) ?>
                        </span>
                    </div>
                    <div>
                        <span class="block text-sm font-medium"><?= e($_SESSION['kunde_name'] ?: $_SESSION['kunde_email']) ?></span>
                        <span class="block text-xs text-slate-400">Kunde</span>
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
                    <h1 class="text-2xl font-bold text-slate-900">Willkommen, <?= e($kunde['vorname'] ?? 'Kunde') ?>!</h1>
                    <p class="text-slate-500">Verwalten Sie hier Ihre persönlichen Daten</p>
                </div>
                <a href="../../index.html" target="_blank" class="flex items-center gap-2 px-4 py-2.5 bg-slate-200 hover:bg-slate-300 text-slate-700 rounded-xl text-sm font-medium transition-colors">
                    <iconify-icon icon="solar:arrow-left-linear" width="18"></iconify-icon>
                    Zur Website
                </a>
            </div>

            <?php if ($success): ?>
            <div class="mb-6 p-4 bg-emerald-50 border border-emerald-200 rounded-xl flex items-start gap-3">
                <iconify-icon icon="solar:check-circle-bold" class="text-emerald-500 text-xl flex-shrink-0 mt-0.5"></iconify-icon>
                <span class="text-emerald-700"><?= e($success) ?></span>
            </div>
            <?php endif; ?>

            <?php if ($error): ?>
            <div class="mb-6 p-4 bg-red-50 border border-red-200 rounded-xl flex items-start gap-3">
                <iconify-icon icon="solar:danger-triangle-bold" class="text-red-500 text-xl flex-shrink-0 mt-0.5"></iconify-icon>
                <span class="text-red-700"><?= e($error) ?></span>
            </div>
            <?php endif; ?>

            <!-- Daten-Formular -->
            <div id="daten" class="bg-white rounded-2xl border border-slate-200 shadow-sm overflow-hidden mb-8">
                <div class="px-6 py-4 border-b border-slate-200 flex items-center gap-3">
                    <div class="w-10 h-10 bg-blue-100 rounded-xl flex items-center justify-center">
                        <iconify-icon icon="solar:document-bold" class="text-blue-600" width="20"></iconify-icon>
                    </div>
                    <div>
                        <h2 class="font-semibold text-slate-900">Meine Stammdaten</h2>
                        <p class="text-sm text-slate-500">Persönliche Informationen, Steuerdaten und Bankverbindung</p>
                    </div>
                </div>

                <form method="POST" class="p-6">
                    <input type="hidden" name="action" value="save">
                    <input type="hidden" name="csrf_token" value="<?= $csrf_token ?>">

                    <!-- Persönliche Daten -->
                    <div class="mb-8">
                        <h3 class="text-lg font-semibold text-slate-900 mb-4 flex items-center gap-2">
                            <iconify-icon icon="solar:user-bold" class="text-blue-600"></iconify-icon>
                            Persönliche Daten
                        </h3>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <div>
                                <label for="vorname" class="block text-sm font-medium text-slate-700 mb-2">Vorname *</label>
                                <input type="text" id="vorname" name="vorname" required
                                       value="<?= e($kunde['vorname'] ?? '') ?>"
                                       class="w-full px-4 py-3 bg-slate-50 border border-slate-200 rounded-xl text-slate-900 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-all">
                            </div>
                            <div>
                                <label for="nachname" class="block text-sm font-medium text-slate-700 mb-2">Nachname *</label>
                                <input type="text" id="nachname" name="nachname" required
                                       value="<?= e($kunde['nachname'] ?? '') ?>"
                                       class="w-full px-4 py-3 bg-slate-50 border border-slate-200 rounded-xl text-slate-900 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-all">
                            </div>
                            <div>
                                <label for="unternehmen" class="block text-sm font-medium text-slate-700 mb-2">Unternehmen</label>
                                <input type="text" id="unternehmen" name="unternehmen"
                                       value="<?= e($kunde['unternehmen'] ?? '') ?>"
                                       class="w-full px-4 py-3 bg-slate-50 border border-slate-200 rounded-xl text-slate-900 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-all"
                                       placeholder="Firmenname">
                            </div>
                            <div>
                                <label for="telefon" class="block text-sm font-medium text-slate-700 mb-2">Telefon</label>
                                <input type="tel" id="telefon" name="telefon"
                                       value="<?= e($kunde['telefon'] ?? '') ?>"
                                       class="w-full px-4 py-3 bg-slate-50 border border-slate-200 rounded-xl text-slate-900 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-all"
                                       placeholder="+49 123 456789">
                            </div>
                        </div>
                    </div>

                    <!-- Webseite -->
                    <div class="mb-8">
                        <h3 class="text-lg font-semibold text-slate-900 mb-4 flex items-center gap-2">
                            <iconify-icon icon="solar:global-bold" class="text-blue-600"></iconify-icon>
                            Webseite
                        </h3>
                        <div class="space-y-4">
                            <div class="flex items-center gap-4">
                                <label class="flex items-center gap-3 cursor-pointer">
                                    <input type="checkbox" id="hat_webseite" name="hat_webseite" value="1"
                                           <?= ($kunde['hat_webseite'] ?? 0) ? 'checked' : '' ?>
                                           class="w-5 h-5 text-blue-600 border-slate-300 rounded focus:ring-blue-500"
                                           onchange="toggleWebseiteUrl()">
                                    <span class="text-slate-700">Ja, ich habe eine Webseite</span>
                                </label>
                            </div>
                            <div id="webseite_url_container" class="<?= ($kunde['hat_webseite'] ?? 0) ? '' : 'hidden' ?>">
                                <label for="webseite_url" class="block text-sm font-medium text-slate-700 mb-2">Webseiten-URL</label>
                                <input type="url" id="webseite_url" name="webseite_url"
                                       value="<?= e($kunde['webseite_url'] ?? '') ?>"
                                       class="w-full px-4 py-3 bg-slate-50 border border-slate-200 rounded-xl text-slate-900 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-all"
                                       placeholder="https://www.meine-firma.de">
                            </div>
                        </div>
                    </div>

                    <!-- Adresse -->
                    <div class="mb-8">
                        <h3 class="text-lg font-semibold text-slate-900 mb-4 flex items-center gap-2">
                            <iconify-icon icon="solar:home-bold" class="text-blue-600"></iconify-icon>
                            Adresse
                        </h3>
                        <div class="grid grid-cols-1 md:grid-cols-4 gap-6">
                            <div class="md:col-span-3">
                                <label for="strasse" class="block text-sm font-medium text-slate-700 mb-2">Straße</label>
                                <input type="text" id="strasse" name="strasse"
                                       value="<?= e($kunde['strasse'] ?? '') ?>"
                                       class="w-full px-4 py-3 bg-slate-50 border border-slate-200 rounded-xl text-slate-900 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-all"
                                       placeholder="Musterstraße">
                            </div>
                            <div>
                                <label for="hausnummer" class="block text-sm font-medium text-slate-700 mb-2">Hausnr.</label>
                                <input type="text" id="hausnummer" name="hausnummer"
                                       value="<?= e($kunde['hausnummer'] ?? '') ?>"
                                       class="w-full px-4 py-3 bg-slate-50 border border-slate-200 rounded-xl text-slate-900 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-all"
                                       placeholder="123">
                            </div>
                            <div>
                                <label for="plz" class="block text-sm font-medium text-slate-700 mb-2">PLZ</label>
                                <input type="text" id="plz" name="plz" maxlength="5"
                                       value="<?= e($kunde['plz'] ?? '') ?>"
                                       class="w-full px-4 py-3 bg-slate-50 border border-slate-200 rounded-xl text-slate-900 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-all"
                                       placeholder="12345">
                            </div>
                            <div class="md:col-span-3">
                                <label for="ort" class="block text-sm font-medium text-slate-700 mb-2">Ort</label>
                                <input type="text" id="ort" name="ort"
                                       value="<?= e($kunde['ort'] ?? '') ?>"
                                       class="w-full px-4 py-3 bg-slate-50 border border-slate-200 rounded-xl text-slate-900 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-all"
                                       placeholder="Musterstadt">
                            </div>
                        </div>
                    </div>

                    <!-- Steuerdaten -->
                    <div class="mb-8">
                        <h3 class="text-lg font-semibold text-slate-900 mb-4 flex items-center gap-2">
                            <iconify-icon icon="solar:document-text-bold" class="text-blue-600"></iconify-icon>
                            Steuerdaten
                        </h3>

                        <!-- Elster-Steuernummer -->
                        <div class="mb-6">
                            <div class="flex items-center gap-4 mb-4">
                                <label class="flex items-center gap-3 cursor-pointer">
                                    <input type="checkbox" id="hat_elster_steuernummer" name="hat_elster_steuernummer" value="1"
                                           <?= ($kunde['hat_elster_steuernummer'] ?? 0) ? 'checked' : '' ?>
                                           class="w-5 h-5 text-blue-600 border-slate-300 rounded focus:ring-blue-500"
                                           onchange="toggleElsterNummer()">
                                    <span class="text-slate-700">Elster-Steuernummer vorhanden</span>
                                </label>
                            </div>
                            <div id="elster_container" class="<?= ($kunde['hat_elster_steuernummer'] ?? 0) ? '' : 'hidden' ?>">
                                <label for="elster_steuernummer" class="block text-sm font-medium text-slate-700 mb-2">Elster-Steuernummer</label>
                                <input type="text" id="elster_steuernummer" name="elster_steuernummer"
                                       value="<?= e($kunde['elster_steuernummer'] ?? '') ?>"
                                       class="w-full md:w-1/2 px-4 py-3 bg-slate-50 border border-slate-200 rounded-xl text-slate-900 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-all font-mono"
                                       placeholder="123/456/78901">
                            </div>
                        </div>

                        <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                            <div>
                                <label for="ust_id" class="block text-sm font-medium text-slate-700 mb-2">Umsatzsteuer-ID (USt-IdNr)</label>
                                <input type="text" id="ust_id" name="ust_id"
                                       value="<?= e($kunde['ust_id'] ?? '') ?>"
                                       class="w-full px-4 py-3 bg-slate-50 border border-slate-200 rounded-xl text-slate-900 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-all font-mono uppercase"
                                       placeholder="DE123456789">
                            </div>
                            <div>
                                <label for="w_idnr" class="block text-sm font-medium text-slate-700 mb-2">Wirtschafts-ID (W-IdNr.)</label>
                                <input type="text" id="w_idnr" name="w_idnr"
                                       value="<?= e($kunde['w_idnr'] ?? '') ?>"
                                       class="w-full px-4 py-3 bg-slate-50 border border-slate-200 rounded-xl text-slate-900 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-all font-mono uppercase"
                                       placeholder="DE123456789012">
                            </div>
                            <div>
                                <label for="branchenschluessel" class="block text-sm font-medium text-slate-700 mb-2">Branchenschlüssel (WZ-Code)</label>
                                <input type="text" id="branchenschluessel" name="branchenschluessel"
                                       value="<?= e($kunde['branchenschluessel'] ?? '') ?>"
                                       class="w-full px-4 py-3 bg-slate-50 border border-slate-200 rounded-xl text-slate-900 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-all font-mono"
                                       placeholder="62.01">
                            </div>
                        </div>
                        <p class="mt-3 text-sm text-slate-500">
                            <iconify-icon icon="solar:info-circle-linear" class="align-middle mr-1"></iconify-icon>
                            Den Branchenschlüssel (WZ-Code) finden Sie auf Ihrem Handelsregisterauszug oder Gewerbeschein.
                        </p>
                    </div>

                    <!-- Bankdaten -->
                    <div class="mb-8">
                        <h3 class="text-lg font-semibold text-slate-900 mb-4 flex items-center gap-2">
                            <iconify-icon icon="solar:card-bold" class="text-blue-600"></iconify-icon>
                            Bankverbindung
                        </h3>
                        <div class="p-4 bg-amber-50 border border-amber-200 rounded-xl mb-6 flex items-start gap-3">
                            <iconify-icon icon="solar:shield-check-bold" class="text-amber-600 text-xl flex-shrink-0 mt-0.5"></iconify-icon>
                            <p class="text-sm text-amber-800">Ihre Bankdaten werden verschlüsselt gespeichert und nur für die Auszahlung von Fördermitteln verwendet.</p>
                        </div>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <div class="md:col-span-2">
                                <label for="iban" class="block text-sm font-medium text-slate-700 mb-2">IBAN</label>
                                <input type="text" id="iban" name="iban"
                                       value="<?= e(formatIBAN($kunde['iban'] ?? '')) ?>"
                                       class="w-full px-4 py-3 bg-slate-50 border border-slate-200 rounded-xl text-slate-900 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-all font-mono"
                                       placeholder="DE89 3704 0044 0532 0130 00">
                            </div>
                            <div>
                                <label for="bic" class="block text-sm font-medium text-slate-700 mb-2">BIC</label>
                                <input type="text" id="bic" name="bic"
                                       value="<?= e($kunde['bic'] ?? '') ?>"
                                       class="w-full px-4 py-3 bg-slate-50 border border-slate-200 rounded-xl text-slate-900 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-all font-mono uppercase"
                                       placeholder="COBADEFFXXX">
                            </div>
                            <div>
                                <label for="bank_name" class="block text-sm font-medium text-slate-700 mb-2">Bank</label>
                                <input type="text" id="bank_name" name="bank_name"
                                       value="<?= e($kunde['bank_name'] ?? '') ?>"
                                       class="w-full px-4 py-3 bg-slate-50 border border-slate-200 rounded-xl text-slate-900 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-all"
                                       placeholder="Commerzbank">
                            </div>
                        </div>
                    </div>

                    <!-- Submit Button -->
                    <div class="flex justify-end pt-6 border-t border-slate-200">
                        <button type="submit"
                                class="px-8 py-3.5 bg-[#0F172A] hover:bg-blue-900 text-white font-medium rounded-xl transition-all shadow-lg shadow-blue-900/20 flex items-center gap-2">
                            <iconify-icon icon="solar:diskette-bold"></iconify-icon>
                            Daten speichern
                        </button>
                    </div>
                </form>
            </div>

            <!-- Dokumente Section -->
            <div id="dokumente" class="bg-white rounded-2xl border border-slate-200 shadow-sm overflow-hidden">
                <div class="px-6 py-4 border-b border-slate-200 flex items-center gap-3">
                    <div class="w-10 h-10 bg-emerald-100 rounded-xl flex items-center justify-center">
                        <iconify-icon icon="solar:file-bold" class="text-emerald-600" width="20"></iconify-icon>
                    </div>
                    <div>
                        <h2 class="font-semibold text-slate-900">Dokumente</h2>
                        <p class="text-sm text-slate-500">Excel-Vorlage herunterladen und ausgefüllt hochladen</p>
                    </div>
                </div>

                <div class="p-6">
                    <!-- Download Vorlage -->
                    <div class="mb-8">
                        <h3 class="text-lg font-semibold text-slate-900 mb-4 flex items-center gap-2">
                            <iconify-icon icon="solar:download-bold" class="text-emerald-600"></iconify-icon>
                            Excel-Vorlage herunterladen
                        </h3>
                        <p class="text-slate-600 mb-4">Laden Sie unsere Vorlage herunter, füllen Sie diese aus und laden Sie sie anschließend wieder hoch.</p>
                        <a href="download_vorlage.php"
                           class="inline-flex items-center gap-2 px-6 py-3 bg-emerald-600 hover:bg-emerald-700 text-white font-medium rounded-xl transition-all shadow-lg shadow-emerald-600/20">
                            <iconify-icon icon="solar:file-download-bold" width="20"></iconify-icon>
                            Excel-Vorlage herunterladen
                        </a>
                    </div>

                    <!-- Upload Bereich -->
                    <div>
                        <h3 class="text-lg font-semibold text-slate-900 mb-4 flex items-center gap-2">
                            <iconify-icon icon="solar:upload-bold" class="text-blue-600"></iconify-icon>
                            Excel-Datei hochladen
                        </h3>

                        <?php if (!empty($kunde['excel_datei'])): ?>
                        <!-- Hochgeladene Datei anzeigen -->
                        <div class="p-4 bg-blue-50 border border-blue-200 rounded-xl mb-4">
                            <div class="flex items-center justify-between">
                                <div class="flex items-center gap-3">
                                    <div class="w-10 h-10 bg-blue-100 rounded-lg flex items-center justify-center">
                                        <iconify-icon icon="solar:file-check-bold" class="text-blue-600" width="20"></iconify-icon>
                                    </div>
                                    <div>
                                        <p class="font-medium text-slate-900"><?= e($kunde['excel_datei_original']) ?></p>
                                        <p class="text-sm text-slate-500">Hochgeladen am <?= date('d.m.Y \u\m H:i', strtotime($kunde['excel_hochgeladen_am'])) ?> Uhr</p>
                                    </div>
                                </div>
                                <form method="POST" class="inline" onsubmit="return confirm('Datei wirklich löschen?')">
                                    <input type="hidden" name="action" value="delete_excel">
                                    <input type="hidden" name="csrf_token" value="<?= $csrf_token ?>">
                                    <button type="submit" class="p-2 text-red-600 hover:bg-red-100 rounded-lg transition-colors">
                                        <iconify-icon icon="solar:trash-bin-trash-bold" width="20"></iconify-icon>
                                    </button>
                                </form>
                            </div>
                        </div>
                        <?php endif; ?>

                        <form method="POST" enctype="multipart/form-data" class="space-y-4">
                            <input type="hidden" name="action" value="upload_excel">
                            <input type="hidden" name="csrf_token" value="<?= $csrf_token ?>">

                            <div class="border-2 border-dashed border-slate-300 rounded-xl p-8 text-center hover:border-blue-400 transition-colors">
                                <iconify-icon icon="solar:cloud-upload-bold" class="text-slate-400 mb-3" width="48"></iconify-icon>
                                <p class="text-slate-600 mb-2">Datei hierher ziehen oder klicken zum Auswählen</p>
                                <p class="text-sm text-slate-400 mb-4">Erlaubte Formate: .xlsx, .xls, .csv (max. 10 MB)</p>
                                <input type="file" name="excel_file" id="excel_file" accept=".xlsx,.xls,.csv"
                                       class="hidden" onchange="updateFileName(this)">
                                <label for="excel_file"
                                       class="inline-flex items-center gap-2 px-6 py-3 bg-slate-100 hover:bg-slate-200 text-slate-700 font-medium rounded-xl cursor-pointer transition-colors">
                                    <iconify-icon icon="solar:folder-open-bold" width="20"></iconify-icon>
                                    Datei auswählen
                                </label>
                                <p id="selected_file" class="mt-3 text-sm text-blue-600 font-medium hidden"></p>
                            </div>

                            <div class="flex justify-end">
                                <button type="submit"
                                        class="px-6 py-3 bg-blue-600 hover:bg-blue-700 text-white font-medium rounded-xl transition-all shadow-lg shadow-blue-600/20 flex items-center gap-2">
                                    <iconify-icon icon="solar:upload-bold"></iconify-icon>
                                    Datei hochladen
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>

            <!-- Letzte Aktualisierung -->
            <?php if (!empty($kunde['aktualisiert_am'])): ?>
            <p class="text-sm text-slate-400 mt-4 text-right">
                Zuletzt aktualisiert: <?= date('d.m.Y \u\m H:i', strtotime($kunde['aktualisiert_am'])) ?> Uhr
            </p>
            <?php endif; ?>
        </main>
    </div>

    <script>
        // Webseite URL Toggle
        function toggleWebseiteUrl() {
            const checkbox = document.getElementById('hat_webseite');
            const container = document.getElementById('webseite_url_container');
            container.classList.toggle('hidden', !checkbox.checked);
        }

        // Elster Steuernummer Toggle
        function toggleElsterNummer() {
            const checkbox = document.getElementById('hat_elster_steuernummer');
            const container = document.getElementById('elster_container');
            container.classList.toggle('hidden', !checkbox.checked);
        }

        // IBAN Formatierung
        document.getElementById('iban').addEventListener('input', function(e) {
            let value = e.target.value.replace(/\s/g, '').toUpperCase();
            let formatted = value.match(/.{1,4}/g)?.join(' ') || '';
            e.target.value = formatted;
        });

        // BIC und USt-ID Uppercase
        ['bic', 'ust_id', 'w_idnr'].forEach(id => {
            const el = document.getElementById(id);
            if (el) {
                el.addEventListener('input', function(e) {
                    e.target.value = e.target.value.toUpperCase();
                });
            }
        });

        // Dateiname anzeigen
        function updateFileName(input) {
            const fileNameEl = document.getElementById('selected_file');
            if (input.files.length > 0) {
                fileNameEl.textContent = 'Ausgewählt: ' + input.files[0].name;
                fileNameEl.classList.remove('hidden');
            } else {
                fileNameEl.classList.add('hidden');
            }
        }

        // Drag & Drop
        const dropZone = document.querySelector('.border-dashed');
        const fileInput = document.getElementById('excel_file');

        ['dragenter', 'dragover', 'dragleave', 'drop'].forEach(eventName => {
            dropZone.addEventListener(eventName, preventDefaults, false);
        });

        function preventDefaults(e) {
            e.preventDefault();
            e.stopPropagation();
        }

        ['dragenter', 'dragover'].forEach(eventName => {
            dropZone.addEventListener(eventName, () => dropZone.classList.add('border-blue-400', 'bg-blue-50'), false);
        });

        ['dragleave', 'drop'].forEach(eventName => {
            dropZone.addEventListener(eventName, () => dropZone.classList.remove('border-blue-400', 'bg-blue-50'), false);
        });

        dropZone.addEventListener('drop', function(e) {
            const files = e.dataTransfer.files;
            if (files.length > 0) {
                fileInput.files = files;
                updateFileName(fileInput);
            }
        });
    </script>
</body>
</html>
