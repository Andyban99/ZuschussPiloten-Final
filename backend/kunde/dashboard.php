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
            'vorname' => $kunde['vorname'] ?? '',
            'nachname' => $kunde['nachname'] ?? '',
            'strasse' => trim($_POST['strasse'] ?? ''),
            'hausnummer' => trim($_POST['hausnummer'] ?? ''),
            'plz' => trim($_POST['plz'] ?? ''),
            'ort' => trim($_POST['ort'] ?? ''),
            'telefon' => trim($_POST['telefon'] ?? ''),
            'iban' => preg_replace('/\s+/', '', $_POST['iban'] ?? ''),
            'bic' => trim($_POST['bic'] ?? ''),
            'bank_name' => trim($_POST['bank_name'] ?? ''),
            // Unternehmensdaten
            'unternehmen' => trim($_POST['unternehmen'] ?? ''),
            'unternehmen_email' => trim($_POST['unternehmen_email'] ?? ''),
            'rechtsform' => trim($_POST['rechtsform'] ?? ''),
            'gruendungsdatum' => trim($_POST['gruendungsdatum'] ?? ''),
            // Webseite und Social Media (Checkboxen)
            'webseite_url' => trim($_POST['webseite_url'] ?? ''),
            'social_youtube' => isset($_POST['social_youtube']) ? '1' : '',
            'social_instagram' => isset($_POST['social_instagram']) ? '1' : '',
            'social_linkedin' => isset($_POST['social_linkedin']) ? '1' : '',
            'social_facebook' => isset($_POST['social_facebook']) ? '1' : '',
            // Elster-Steuernummer direkt
            'elster_steuernummer' => trim($_POST['elster_steuernummer'] ?? ''),
            'ust_id' => trim($_POST['ust_id'] ?? ''),
            'w_idnr' => trim($_POST['w_idnr'] ?? ''),
            'branchenschluessel' => trim($_POST['branchenschluessel'] ?? ''),
            // Geschäftsjahre als JSON
            'geschaeftsjahre' => json_encode([
                '2025' => [
                    'beschaeftigte' => trim($_POST['gj_2025_beschaeftigte'] ?? ''),
                    'umsatz' => trim($_POST['gj_2025_umsatz'] ?? ''),
                    'bilanzsumme' => trim($_POST['gj_2025_bilanzsumme'] ?? '')
                ],
                '2024' => [
                    'beschaeftigte' => trim($_POST['gj_2024_beschaeftigte'] ?? ''),
                    'umsatz' => trim($_POST['gj_2024_umsatz'] ?? ''),
                    'bilanzsumme' => trim($_POST['gj_2024_bilanzsumme'] ?? '')
                ],
                '2023' => [
                    'beschaeftigte' => trim($_POST['gj_2023_beschaeftigte'] ?? ''),
                    'umsatz' => trim($_POST['gj_2023_umsatz'] ?? ''),
                    'bilanzsumme' => trim($_POST['gj_2023_bilanzsumme'] ?? '')
                ]
            ]),
            // Wirtschaftlich berechtigte Personen als JSON
            'wirtschaftlich_berechtigte' => json_encode(array_filter(array_map(function($i) {
                $vorname = trim($_POST['wb_vorname'][$i] ?? '');
                $nachname = trim($_POST['wb_nachname'][$i] ?? '');
                $steuer_id = trim($_POST['wb_steuer_id'][$i] ?? '');
                $geburtsdatum = trim($_POST['wb_geburtsdatum'][$i] ?? '');
                $anteil = trim($_POST['wb_anteil'][$i] ?? '');
                if (!empty($vorname) || !empty($nachname) || !empty($steuer_id) || !empty($geburtsdatum) || !empty($anteil)) {
                    return [
                        'vorname' => $vorname,
                        'nachname' => $nachname,
                        'steuer_id' => $steuer_id,
                        'geburtsdatum' => $geburtsdatum,
                        'anteil' => $anteil
                    ];
                }
                return null;
            }, array_keys($_POST['wb_vorname'] ?? [])))),
            // Gesellschafter als JSON
            'gesellschafter' => json_encode(array_filter(array_map(function($i) {
                $name = trim($_POST['gesellschafter_name'][$i] ?? '');
                $beteiligung = trim($_POST['gesellschafter_beteiligung'][$i] ?? '');
                if (!empty($name) || !empty($beteiligung)) {
                    return ['name' => $name, 'beteiligung' => $beteiligung];
                }
                return null;
            }, array_keys($_POST['gesellschafter_name'] ?? [])))),
            // Durchführungsort
            'durchfuehrungsort_gleich_adresse' => isset($_POST['durchfuehrungsort_gleich_adresse']) ? 1 : 0,
            'durchfuehrungsort_strasse' => trim($_POST['durchfuehrungsort_strasse'] ?? ''),
            'durchfuehrungsort_hausnummer' => trim($_POST['durchfuehrungsort_hausnummer'] ?? ''),
            'durchfuehrungsort_plz' => trim($_POST['durchfuehrungsort_plz'] ?? ''),
            'durchfuehrungsort_ort' => trim($_POST['durchfuehrungsort_ort'] ?? ''),
            // Abschreibungen als JSON
            'abschreibungen' => json_encode([
                '2025' => trim($_POST['abschreibung_2025'] ?? ''),
                '2024' => trim($_POST['abschreibung_2024'] ?? ''),
                '2023' => trim($_POST['abschreibung_2023'] ?? '')
            ]),
            // Arbeitsplätze bei Antragstellung (VZÄ)
            'arbeitsplaetze_frauen' => trim($_POST['arbeitsplaetze_frauen'] ?? ''),
            'arbeitsplaetze_maenner' => trim($_POST['arbeitsplaetze_maenner'] ?? ''),
            'arbeitsplaetze_ausbildung' => trim($_POST['arbeitsplaetze_ausbildung'] ?? ''),
            'arbeitsplaetze_leiharbeiter' => trim($_POST['arbeitsplaetze_leiharbeiter'] ?? ''),
            // Geplante zusätzliche Arbeitsplätze (VZÄ)
            'geplante_arbeitsplaetze_frauen' => trim($_POST['geplante_arbeitsplaetze_frauen'] ?? ''),
            'geplante_arbeitsplaetze_maenner' => trim($_POST['geplante_arbeitsplaetze_maenner'] ?? ''),
            'geplante_arbeitsplaetze_ausbildung' => trim($_POST['geplante_arbeitsplaetze_ausbildung'] ?? ''),
            // Investitionsgüterliste als JSON
            'investitionsgueter' => json_encode(array_filter(array_map(function($i) {
                $bezeichnung = trim($_POST['inv_bezeichnung'][$i] ?? '');
                $wert = trim($_POST['inv_wert'][$i] ?? '');
                $gebraucht = isset($_POST['inv_gebraucht']) && in_array($i, $_POST['inv_gebraucht']);
                if (!empty($bezeichnung) || !empty($wert)) {
                    return ['bezeichnung' => $bezeichnung, 'wert' => $wert, 'gebraucht' => $gebraucht];
                }
                return null;
            }, array_keys($_POST['inv_bezeichnung'] ?? []))))
        ];

        // Validierung
        if (!empty($data['iban']) && !preg_match('/^[A-Z]{2}\d{2}[A-Z0-9]{4,30}$/', $data['iban'])) {
            $error = 'Bitte geben Sie eine gültige IBAN ein.';
        } elseif (!empty($data['webseite_url']) && !filter_var($data['webseite_url'], FILTER_VALIDATE_URL)) {
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
// Mindestens eine leere Zeile für neue Einträge
if (empty($wirtschaftlich_berechtigte)) {
    $wirtschaftlich_berechtigte = [['vorname' => '', 'nachname' => '', 'steuer_id' => '', 'geburtsdatum' => '', 'anteil' => '']];
}

// Gesellschafter parsen
$gesellschafter = [];
if (!empty($kunde['gesellschafter'])) {
    $gesellschafter = json_decode($kunde['gesellschafter'], true) ?: [];
}
// Mindestens eine leere Zeile für neue Einträge
if (empty($gesellschafter)) {
    $gesellschafter = [['name' => '', 'beteiligung' => '']];
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
// Mindestens 5 leere Zeilen für neue Einträge
while (count($investitionsgueter) < 5) {
    $investitionsgueter[] = ['bezeichnung' => '', 'wert' => '', 'gebraucht' => false];
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
                <a href="dashboard.php#unternehmen" class="flex items-center gap-3 px-4 py-3 rounded-xl text-slate-300 hover:bg-white/5 hover:text-white transition-all">
                    <iconify-icon icon="solar:buildings-bold" width="20"></iconify-icon>
                    Unternehmensdaten
                </a>
                <a href="dashboard.php#wirtschaftlich" class="flex items-center gap-3 px-4 py-3 rounded-xl text-slate-300 hover:bg-white/5 hover:text-white transition-all">
                    <iconify-icon icon="solar:users-group-two-rounded-bold" width="20"></iconify-icon>
                    Wirtschaftl. Berechtigte
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
                    <p class="text-slate-500">Verwalten Sie hier Ihre Unternehmensdaten</p>
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
                        <p class="text-sm text-slate-500">Unternehmensdaten und Bankverbindung</p>
                    </div>
                </div>

                <form method="POST" class="p-6">
                    <input type="hidden" name="action" value="save">
                    <input type="hidden" name="csrf_token" value="<?= $csrf_token ?>">

                    <!-- Unternehmensdaten Section -->
                    <div id="unternehmen" class="mb-8">
                        <h3 class="text-lg font-semibold text-slate-900 mb-4 flex items-center gap-2">
                            <iconify-icon icon="solar:buildings-bold" class="text-blue-600"></iconify-icon>
                            Unternehmensdaten
                        </h3>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
                            <div>
                                <label for="unternehmen" class="block text-sm font-medium text-slate-700 mb-2">Name des Unternehmens</label>
                                <input type="text" id="unternehmen" name="unternehmen"
                                       value="<?= e($kunde['unternehmen'] ?? '') ?>"
                                       class="w-full px-4 py-3 bg-slate-50 border border-slate-200 rounded-xl text-slate-900 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-all"
                                       placeholder="Firmenname">
                            </div>
                            <div>
                                <label for="rechtsform" class="block text-sm font-medium text-slate-700 mb-2">Rechtsform</label>
                                <select id="rechtsform" name="rechtsform"
                                        class="w-full px-4 py-3 bg-slate-50 border border-slate-200 rounded-xl text-slate-900 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-all">
                                    <option value="">Bitte wählen...</option>
                                    <option value="Einzelunternehmen" <?= ($kunde['rechtsform'] ?? '') === 'Einzelunternehmen' ? 'selected' : '' ?>>Einzelunternehmen</option>
                                    <option value="GbR" <?= ($kunde['rechtsform'] ?? '') === 'GbR' ? 'selected' : '' ?>>GbR - Gesellschaft bürgerlichen Rechts</option>
                                    <option value="OHG" <?= ($kunde['rechtsform'] ?? '') === 'OHG' ? 'selected' : '' ?>>OHG - Offene Handelsgesellschaft</option>
                                    <option value="KG" <?= ($kunde['rechtsform'] ?? '') === 'KG' ? 'selected' : '' ?>>KG - Kommanditgesellschaft</option>
                                    <option value="GmbH" <?= ($kunde['rechtsform'] ?? '') === 'GmbH' ? 'selected' : '' ?>>GmbH - Gesellschaft mit beschränkter Haftung</option>
                                    <option value="UG" <?= ($kunde['rechtsform'] ?? '') === 'UG' ? 'selected' : '' ?>>UG (haftungsbeschränkt)</option>
                                    <option value="AG" <?= ($kunde['rechtsform'] ?? '') === 'AG' ? 'selected' : '' ?>>AG - Aktiengesellschaft</option>
                                    <option value="GmbH & Co. KG" <?= ($kunde['rechtsform'] ?? '') === 'GmbH & Co. KG' ? 'selected' : '' ?>>GmbH & Co. KG</option>
                                    <option value="eG" <?= ($kunde['rechtsform'] ?? '') === 'eG' ? 'selected' : '' ?>>eG - eingetragene Genossenschaft</option>
                                    <option value="Freiberufler" <?= ($kunde['rechtsform'] ?? '') === 'Freiberufler' ? 'selected' : '' ?>>Freiberufler</option>
                                    <option value="Sonstige" <?= ($kunde['rechtsform'] ?? '') === 'Sonstige' ? 'selected' : '' ?>>Sonstige</option>
                                </select>
                            </div>
                            <div>
                                <label for="telefon" class="block text-sm font-medium text-slate-700 mb-2">Telefon</label>
                                <input type="tel" id="telefon" name="telefon"
                                       value="<?= e($kunde['telefon'] ?? '') ?>"
                                       class="w-full px-4 py-3 bg-slate-50 border border-slate-200 rounded-xl text-slate-900 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-all"
                                       placeholder="+49 123 456789">
                            </div>
                            <div>
                                <label for="unternehmen_email" class="block text-sm font-medium text-slate-700 mb-2">E-Mail (Unternehmen)</label>
                                <input type="email" id="unternehmen_email" name="unternehmen_email"
                                       value="<?= e($kunde['unternehmen_email'] ?? '') ?>"
                                       class="w-full px-4 py-3 bg-slate-50 border border-slate-200 rounded-xl text-slate-900 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-all"
                                       placeholder="info@firma.de">
                            </div>
                            <div>
                                <label for="gruendungsdatum" class="block text-sm font-medium text-slate-700 mb-2">Gründungsdatum</label>
                                <input type="date" id="gruendungsdatum" name="gruendungsdatum"
                                       value="<?= e($kunde['gruendungsdatum'] ?? '') ?>"
                                       class="w-full px-4 py-3 bg-slate-50 border border-slate-200 rounded-xl text-slate-900 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-all">
                            </div>
                            <div>
                                <label for="branchenschluessel" class="block text-sm font-medium text-slate-700 mb-2">Branchenschlüssel (WZ-Code)</label>
                                <input type="text" id="branchenschluessel" name="branchenschluessel"
                                       value="<?= e($kunde['branchenschluessel'] ?? '') ?>"
                                       class="w-full px-4 py-3 bg-slate-50 border border-slate-200 rounded-xl text-slate-900 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-all font-mono"
                                       placeholder="62.01">
                            </div>
                        </div>

                        <!-- Unternehmensadresse -->
                        <h4 class="text-md font-medium text-slate-700 mb-3">Unternehmensadresse</h4>
                        <div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-6">
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

                        <!-- Webseite -->
                        <h4 class="text-md font-medium text-slate-700 mb-3">Webseite</h4>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
                            <div class="md:col-span-2">
                                <label for="webseite_url" class="block text-sm font-medium text-slate-700 mb-2">Webseiten-URL</label>
                                <input type="url" id="webseite_url" name="webseite_url"
                                       value="<?= e($kunde['webseite_url'] ?? '') ?>"
                                       class="w-full px-4 py-3 bg-slate-50 border border-slate-200 rounded-xl text-slate-900 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-all"
                                       placeholder="https://www.meine-firma.de">
                            </div>
                        </div>

                        <!-- Social Media Kanäle -->
                        <h4 class="text-md font-medium text-slate-700 mb-3">Social Media Kanäle vorhanden?</h4>
                        <div class="flex flex-wrap gap-6 mb-6">
                            <label class="flex items-center gap-3 cursor-pointer">
                                <input type="checkbox" name="social_youtube" value="1"
                                       <?= !empty($kunde['social_youtube']) ? 'checked' : '' ?>
                                       class="w-5 h-5 text-blue-600 border-slate-300 rounded focus:ring-blue-500">
                                <span class="flex items-center gap-2 text-slate-700">
                                    <iconify-icon icon="logos:youtube-icon" width="20"></iconify-icon>
                                    YouTube
                                </span>
                            </label>
                            <label class="flex items-center gap-3 cursor-pointer">
                                <input type="checkbox" name="social_instagram" value="1"
                                       <?= !empty($kunde['social_instagram']) ? 'checked' : '' ?>
                                       class="w-5 h-5 text-blue-600 border-slate-300 rounded focus:ring-blue-500">
                                <span class="flex items-center gap-2 text-slate-700">
                                    <iconify-icon icon="skill-icons:instagram" width="20"></iconify-icon>
                                    Instagram
                                </span>
                            </label>
                            <label class="flex items-center gap-3 cursor-pointer">
                                <input type="checkbox" name="social_linkedin" value="1"
                                       <?= !empty($kunde['social_linkedin']) ? 'checked' : '' ?>
                                       class="w-5 h-5 text-blue-600 border-slate-300 rounded focus:ring-blue-500">
                                <span class="flex items-center gap-2 text-slate-700">
                                    <iconify-icon icon="skill-icons:linkedin" width="20"></iconify-icon>
                                    LinkedIn
                                </span>
                            </label>
                            <label class="flex items-center gap-3 cursor-pointer">
                                <input type="checkbox" name="social_facebook" value="1"
                                       <?= !empty($kunde['social_facebook']) ? 'checked' : '' ?>
                                       class="w-5 h-5 text-blue-600 border-slate-300 rounded focus:ring-blue-500">
                                <span class="flex items-center gap-2 text-slate-700">
                                    <iconify-icon icon="logos:facebook" width="20"></iconify-icon>
                                    Facebook
                                </span>
                            </label>
                        </div>

                        <!-- Bankverbindung -->
                        <h4 class="text-md font-medium text-slate-700 mb-3 flex items-center gap-2">
                            <iconify-icon icon="solar:card-bold" class="text-blue-600"></iconify-icon>
                            Bankverbindung
                        </h4>
                        <div class="p-4 bg-amber-50 border border-amber-200 rounded-xl mb-4 flex items-start gap-3">
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

                    <!-- Durchführungsort des Vorhabens -->
                    <div class="mb-8">
                        <h3 class="text-lg font-semibold text-slate-900 mb-4 flex items-center gap-2">
                            <iconify-icon icon="solar:map-point-bold" class="text-blue-600"></iconify-icon>
                            Durchführungsort des Vorhabens
                        </h3>
                        <div class="mb-4">
                            <label class="flex items-center gap-3 cursor-pointer">
                                <input type="checkbox" id="durchfuehrungsort_gleich_adresse" name="durchfuehrungsort_gleich_adresse" value="1"
                                       <?= ($kunde['durchfuehrungsort_gleich_adresse'] ?? 1) ? 'checked' : '' ?>
                                       class="w-5 h-5 text-blue-600 border-slate-300 rounded focus:ring-blue-500"
                                       onchange="toggleDurchfuehrungsort()">
                                <span class="text-slate-700">Durchführungsort ist gleich der Unternehmensadresse</span>
                            </label>
                        </div>
                        <div id="durchfuehrungsort_container" class="<?= ($kunde['durchfuehrungsort_gleich_adresse'] ?? 1) ? 'hidden' : '' ?>">
                            <div class="grid grid-cols-1 md:grid-cols-4 gap-6">
                                <div class="md:col-span-3">
                                    <label for="durchfuehrungsort_strasse" class="block text-sm font-medium text-slate-700 mb-2">Straße</label>
                                    <input type="text" id="durchfuehrungsort_strasse" name="durchfuehrungsort_strasse"
                                           value="<?= e($kunde['durchfuehrungsort_strasse'] ?? '') ?>"
                                           class="w-full px-4 py-3 bg-slate-50 border border-slate-200 rounded-xl text-slate-900 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-all"
                                           placeholder="Musterstraße">
                                </div>
                                <div>
                                    <label for="durchfuehrungsort_hausnummer" class="block text-sm font-medium text-slate-700 mb-2">Hausnr.</label>
                                    <input type="text" id="durchfuehrungsort_hausnummer" name="durchfuehrungsort_hausnummer"
                                           value="<?= e($kunde['durchfuehrungsort_hausnummer'] ?? '') ?>"
                                           class="w-full px-4 py-3 bg-slate-50 border border-slate-200 rounded-xl text-slate-900 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-all"
                                           placeholder="123">
                                </div>
                                <div>
                                    <label for="durchfuehrungsort_plz" class="block text-sm font-medium text-slate-700 mb-2">PLZ</label>
                                    <input type="text" id="durchfuehrungsort_plz" name="durchfuehrungsort_plz" maxlength="5"
                                           value="<?= e($kunde['durchfuehrungsort_plz'] ?? '') ?>"
                                           class="w-full px-4 py-3 bg-slate-50 border border-slate-200 rounded-xl text-slate-900 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-all"
                                           placeholder="12345">
                                </div>
                                <div class="md:col-span-3">
                                    <label for="durchfuehrungsort_ort" class="block text-sm font-medium text-slate-700 mb-2">Ort</label>
                                    <input type="text" id="durchfuehrungsort_ort" name="durchfuehrungsort_ort"
                                           value="<?= e($kunde['durchfuehrungsort_ort'] ?? '') ?>"
                                           class="w-full px-4 py-3 bg-slate-50 border border-slate-200 rounded-xl text-slate-900 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-all"
                                           placeholder="Musterstadt">
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Wirtschaftlich berechtigte Personen -->
                    <div id="wirtschaftlich" class="mb-8 pt-4 -mt-4">
                        <h3 class="text-lg font-semibold text-slate-900 mb-4 flex items-center gap-2">
                            <iconify-icon icon="solar:users-group-two-rounded-bold" class="text-blue-600"></iconify-icon>
                            Wirtschaftlich berechtigte Personen
                        </h3>
                        <p class="text-sm text-slate-500 mb-4">
                            <iconify-icon icon="solar:info-circle-linear" class="align-middle mr-1"></iconify-icon>
                            Personen mit mehr als 25% Anteil am Unternehmen.
                        </p>
                        <div id="wb-container" class="space-y-4">
                            <?php foreach ($wirtschaftlich_berechtigte as $index => $wb): ?>
                            <div class="wb-row bg-slate-50 rounded-xl p-4 border border-slate-200">
                                <div class="grid grid-cols-1 md:grid-cols-6 gap-4">
                                    <div>
                                        <label class="block text-sm font-medium text-slate-700 mb-2">Vorname</label>
                                        <input type="text" name="wb_vorname[]"
                                               value="<?= e($wb['vorname'] ?? '') ?>"
                                               class="w-full px-3 py-2.5 bg-white border border-slate-200 rounded-lg text-slate-900 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                                               placeholder="Max">
                                    </div>
                                    <div>
                                        <label class="block text-sm font-medium text-slate-700 mb-2">Nachname</label>
                                        <input type="text" name="wb_nachname[]"
                                               value="<?= e($wb['nachname'] ?? '') ?>"
                                               class="w-full px-3 py-2.5 bg-white border border-slate-200 rounded-lg text-slate-900 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                                               placeholder="Mustermann">
                                    </div>
                                    <div>
                                        <label class="block text-sm font-medium text-slate-700 mb-2">Steuer-ID (persönlich)</label>
                                        <input type="text" name="wb_steuer_id[]"
                                               value="<?= e($wb['steuer_id'] ?? '') ?>"
                                               class="w-full px-3 py-2.5 bg-white border border-slate-200 rounded-lg text-slate-900 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent font-mono"
                                               placeholder="12 345 678 901">
                                    </div>
                                    <div>
                                        <label class="block text-sm font-medium text-slate-700 mb-2">Geburtsdatum</label>
                                        <input type="date" name="wb_geburtsdatum[]"
                                               value="<?= e($wb['geburtsdatum'] ?? '') ?>"
                                               class="w-full px-3 py-2.5 bg-white border border-slate-200 rounded-lg text-slate-900 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                                    </div>
                                    <div>
                                        <label class="block text-sm font-medium text-slate-700 mb-2">Anteil %</label>
                                        <div class="relative">
                                            <input type="number" name="wb_anteil[]" min="0" max="100" step="0.01"
                                                   value="<?= e($wb['anteil'] ?? '') ?>"
                                                   class="w-full px-3 py-2.5 bg-white border border-slate-200 rounded-lg text-slate-900 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent pr-8"
                                                   placeholder="50">
                                            <span class="absolute right-3 top-1/2 -translate-y-1/2 text-slate-500">%</span>
                                        </div>
                                    </div>
                                    <div class="flex items-end">
                                        <button type="button" onclick="removeWBRow(this)" class="p-2.5 text-red-500 hover:bg-red-100 rounded-lg transition-colors <?= $index === 0 ? 'invisible' : '' ?>">
                                            <iconify-icon icon="solar:trash-bin-trash-bold" width="20"></iconify-icon>
                                        </button>
                                    </div>
                                </div>
                            </div>
                            <?php endforeach; ?>
                        </div>
                        <button type="button" onclick="addWBRow()"
                                class="mt-4 flex items-center gap-2 px-4 py-2.5 bg-blue-50 hover:bg-blue-100 text-blue-600 font-medium rounded-xl transition-colors">
                            <iconify-icon icon="solar:add-circle-bold" width="20"></iconify-icon>
                            Person hinzufügen
                        </button>

                        <!-- Gesellschafter -->
                        <div class="mt-8 pt-6 border-t border-slate-200">
                            <h4 class="text-md font-semibold text-slate-800 mb-3 flex items-center gap-2">
                                <iconify-icon icon="solar:users-group-rounded-bold" class="text-blue-500"></iconify-icon>
                                Gesellschafter
                            </h4>
                            <p class="text-sm text-slate-500 mb-4">
                                <iconify-icon icon="solar:info-circle-linear" class="align-middle mr-1"></iconify-icon>
                                Alle Gesellschafter des Unternehmens mit ihrem Beteiligungsanteil.
                            </p>
                            <div id="gesellschafter-container" class="space-y-3">
                                <?php foreach ($gesellschafter as $index => $gs): ?>
                                <div class="gesellschafter-row bg-slate-50 rounded-xl p-4 border border-slate-200">
                                    <div class="grid grid-cols-1 md:grid-cols-12 gap-4 items-end">
                                        <div class="md:col-span-7">
                                            <label class="block text-sm font-medium text-slate-700 mb-2">Name des Gesellschafters</label>
                                            <input type="text" name="gesellschafter_name[]"
                                                   value="<?= e($gs['name'] ?? '') ?>"
                                                   class="w-full px-3 py-2.5 bg-white border border-slate-200 rounded-lg text-slate-900 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                                                   placeholder="Max Mustermann">
                                        </div>
                                        <div class="md:col-span-4">
                                            <label class="block text-sm font-medium text-slate-700 mb-2">Beteiligung</label>
                                            <div class="relative">
                                                <input type="number" name="gesellschafter_beteiligung[]" min="0" max="100" step="0.01"
                                                       value="<?= e($gs['beteiligung'] ?? '') ?>"
                                                       class="w-full px-3 py-2.5 bg-white border border-slate-200 rounded-lg text-slate-900 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent pr-8"
                                                       placeholder="50">
                                                <span class="absolute right-3 top-1/2 -translate-y-1/2 text-slate-500">%</span>
                                            </div>
                                        </div>
                                        <div class="md:col-span-1 flex items-end justify-center">
                                            <button type="button" onclick="removeGesellschafter(this)" class="p-2.5 text-red-500 hover:bg-red-100 rounded-lg transition-colors <?= $index === 0 ? 'invisible' : '' ?>">
                                                <iconify-icon icon="solar:trash-bin-trash-bold" width="20"></iconify-icon>
                                            </button>
                                        </div>
                                    </div>
                                </div>
                                <?php endforeach; ?>
                            </div>
                            <button type="button" onclick="addGesellschafter()"
                                    class="mt-4 flex items-center gap-2 px-4 py-2.5 bg-blue-50 hover:bg-blue-100 text-blue-600 font-medium rounded-xl transition-colors">
                                <iconify-icon icon="solar:add-circle-bold" width="20"></iconify-icon>
                                Gesellschafter hinzufügen
                            </button>
                        </div>
                    </div>

                    <!-- Steuerdaten -->
                    <div class="mb-8">
                        <h3 class="text-lg font-semibold text-slate-900 mb-4 flex items-center gap-2">
                            <iconify-icon icon="solar:document-text-bold" class="text-blue-600"></iconify-icon>
                            Steuerdaten
                        </h3>

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
                            <div>
                                <label for="elster_steuernummer" class="block text-sm font-medium text-slate-700 mb-2">Steuernummer</label>
                                <input type="text" id="elster_steuernummer" name="elster_steuernummer"
                                       value="<?= e($kunde['elster_steuernummer'] ?? '') ?>"
                                       class="w-full px-4 py-3 bg-slate-50 border border-slate-200 rounded-xl text-slate-900 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-all font-mono"
                                       placeholder="123/456/78901">
                            </div>
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
                        </div>
                    </div>

                    <!-- Verdiente Abschreibungen -->
                    <div class="mb-8">
                        <h3 class="text-lg font-semibold text-slate-900 mb-4 flex items-center gap-2">
                            <iconify-icon icon="solar:chart-2-bold" class="text-blue-600"></iconify-icon>
                            Verdiente Abschreibungen
                        </h3>
                        <p class="text-sm text-slate-500 mb-4">
                            <iconify-icon icon="solar:info-circle-linear" class="align-middle mr-1"></iconify-icon>
                            In den letzten drei Geschäftsjahren vor Antragstellung in vollen Euro (ohne Berücksichtigung von Sonderabschreibungen).
                        </p>
                        <div class="overflow-x-auto">
                            <table class="w-full border-collapse">
                                <thead>
                                    <tr class="bg-slate-100">
                                        <th class="px-4 py-3 text-left text-sm font-semibold text-slate-700 border border-slate-200" style="width: 40%">Jahr</th>
                                        <th class="px-4 py-3 text-left text-sm font-semibold text-slate-700 border border-slate-200">Abschreibungen (EUR)</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach (['2025', '2024', '2023'] as $jahr): ?>
                                    <tr>
                                        <td class="px-4 py-3 border border-slate-200 font-medium text-slate-700"><?= $jahr ?></td>
                                        <td class="px-2 py-2 border border-slate-200">
                                            <input type="number" name="abschreibung_<?= $jahr ?>" min="0" step="1"
                                                   value="<?= e($abschreibungen[$jahr] ?? '') ?>"
                                                   class="w-full px-3 py-2 bg-slate-50 border border-slate-200 rounded-lg text-slate-900 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                                                   placeholder="z.B. 25000">
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <!-- Geschäftsjahre -->
                    <div class="mb-8">
                        <h3 class="text-lg font-semibold text-slate-900 mb-4 flex items-center gap-2">
                            <iconify-icon icon="solar:chart-square-bold" class="text-blue-600"></iconify-icon>
                            Geschäftsjahre (letzte 3 Jahre)
                        </h3>
                        <div class="overflow-x-auto">
                            <table class="w-full border-collapse">
                                <thead>
                                    <tr class="bg-slate-100">
                                        <th class="px-4 py-3 text-left text-sm font-semibold text-slate-700 border border-slate-200">Jahr</th>
                                        <th class="px-4 py-3 text-left text-sm font-semibold text-slate-700 border border-slate-200">Anzahl Beschäftigte</th>
                                        <th class="px-4 py-3 text-left text-sm font-semibold text-slate-700 border border-slate-200">Umsatz (EUR)</th>
                                        <th class="px-4 py-3 text-left text-sm font-semibold text-slate-700 border border-slate-200">Bilanzsumme (EUR)</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach (['2025', '2024', '2023'] as $jahr): ?>
                                    <tr>
                                        <td class="px-4 py-3 border border-slate-200 font-medium text-slate-700"><?= $jahr ?></td>
                                        <td class="px-2 py-2 border border-slate-200">
                                            <input type="number" name="gj_<?= $jahr ?>_beschaeftigte" min="0" step="1"
                                                   value="<?= e($geschaeftsjahre[$jahr]['beschaeftigte'] ?? '') ?>"
                                                   class="w-full px-3 py-2 bg-slate-50 border border-slate-200 rounded-lg text-slate-900 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                                                   placeholder="z.B. 10">
                                        </td>
                                        <td class="px-2 py-2 border border-slate-200">
                                            <input type="number" name="gj_<?= $jahr ?>_umsatz" min="0" step="0.01"
                                                   value="<?= e($geschaeftsjahre[$jahr]['umsatz'] ?? '') ?>"
                                                   class="w-full px-3 py-2 bg-slate-50 border border-slate-200 rounded-lg text-slate-900 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                                                   placeholder="z.B. 500000">
                                        </td>
                                        <td class="px-2 py-2 border border-slate-200">
                                            <input type="number" name="gj_<?= $jahr ?>_bilanzsumme" min="0" step="0.01"
                                                   value="<?= e($geschaeftsjahre[$jahr]['bilanzsumme'] ?? '') ?>"
                                                   class="w-full px-3 py-2 bg-slate-50 border border-slate-200 rounded-lg text-slate-900 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                                                   placeholder="z.B. 250000">
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <!-- Arbeitsplätze bei Antragstellung -->
                    <div class="mb-8">
                        <h3 class="text-lg font-semibold text-slate-900 mb-4 flex items-center gap-2">
                            <iconify-icon icon="solar:user-check-bold" class="text-blue-600"></iconify-icon>
                            Vorhandene Dauerarbeitsplätze bei Antragstellung
                        </h3>
                        <p class="text-sm text-slate-500 mb-4">
                            <iconify-icon icon="solar:info-circle-linear" class="align-middle mr-1"></iconify-icon>
                            Angabe in Vollzeitäquivalenten (VZÄ): 1 = 40 Std./Woche, 0.5 = 20 Std./Woche
                        </p>
                        <div class="grid grid-cols-1 md:grid-cols-4 gap-6">
                            <div>
                                <label for="arbeitsplaetze_frauen" class="block text-sm font-medium text-slate-700 mb-2">Frauen (VZÄ)</label>
                                <input type="number" id="arbeitsplaetze_frauen" name="arbeitsplaetze_frauen" min="0" step="0.01"
                                       value="<?= e($kunde['arbeitsplaetze_frauen'] ?? '') ?>"
                                       class="w-full px-4 py-3 bg-slate-50 border border-slate-200 rounded-xl text-slate-900 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                                       placeholder="z.B. 2.5">
                            </div>
                            <div>
                                <label for="arbeitsplaetze_maenner" class="block text-sm font-medium text-slate-700 mb-2">Männer (VZÄ)</label>
                                <input type="number" id="arbeitsplaetze_maenner" name="arbeitsplaetze_maenner" min="0" step="0.01"
                                       value="<?= e($kunde['arbeitsplaetze_maenner'] ?? '') ?>"
                                       class="w-full px-4 py-3 bg-slate-50 border border-slate-200 rounded-xl text-slate-900 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                                       placeholder="z.B. 3.0">
                            </div>
                            <div>
                                <label for="arbeitsplaetze_ausbildung" class="block text-sm font-medium text-slate-700 mb-2">Ausbildungsplätze (VZÄ)</label>
                                <input type="number" id="arbeitsplaetze_ausbildung" name="arbeitsplaetze_ausbildung" min="0" step="0.01"
                                       value="<?= e($kunde['arbeitsplaetze_ausbildung'] ?? '') ?>"
                                       class="w-full px-4 py-3 bg-slate-50 border border-slate-200 rounded-xl text-slate-900 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                                       placeholder="z.B. 1.0">
                            </div>
                            <div>
                                <label for="arbeitsplaetze_leiharbeiter" class="block text-sm font-medium text-slate-700 mb-2">Leiharbeiter (VZÄ)</label>
                                <input type="number" id="arbeitsplaetze_leiharbeiter" name="arbeitsplaetze_leiharbeiter" min="0" step="0.01"
                                       value="<?= e($kunde['arbeitsplaetze_leiharbeiter'] ?? '') ?>"
                                       class="w-full px-4 py-3 bg-slate-50 border border-slate-200 rounded-xl text-slate-900 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                                       placeholder="z.B. 0.5">
                            </div>
                        </div>
                    </div>

                    <!-- Geplante zusätzliche Arbeitsplätze -->
                    <div class="mb-8">
                        <h3 class="text-lg font-semibold text-slate-900 mb-4 flex items-center gap-2">
                            <iconify-icon icon="solar:user-plus-bold" class="text-blue-600"></iconify-icon>
                            Geplante zusätzliche Arbeitsplätze nach Abschluss der Investition
                        </h3>
                        <p class="text-sm text-slate-500 mb-4">
                            <iconify-icon icon="solar:info-circle-linear" class="align-middle mr-1"></iconify-icon>
                            Angabe in Vollzeitäquivalenten (VZÄ): 1 = 40 Std./Woche, 0.5 = 20 Std./Woche
                        </p>
                        <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                            <div>
                                <label for="geplante_arbeitsplaetze_frauen" class="block text-sm font-medium text-slate-700 mb-2">Frauen (VZÄ)</label>
                                <input type="number" id="geplante_arbeitsplaetze_frauen" name="geplante_arbeitsplaetze_frauen" min="0" step="0.01"
                                       value="<?= e($kunde['geplante_arbeitsplaetze_frauen'] ?? '') ?>"
                                       class="w-full px-4 py-3 bg-slate-50 border border-slate-200 rounded-xl text-slate-900 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                                       placeholder="z.B. 1.0">
                            </div>
                            <div>
                                <label for="geplante_arbeitsplaetze_maenner" class="block text-sm font-medium text-slate-700 mb-2">Männer (VZÄ)</label>
                                <input type="number" id="geplante_arbeitsplaetze_maenner" name="geplante_arbeitsplaetze_maenner" min="0" step="0.01"
                                       value="<?= e($kunde['geplante_arbeitsplaetze_maenner'] ?? '') ?>"
                                       class="w-full px-4 py-3 bg-slate-50 border border-slate-200 rounded-xl text-slate-900 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                                       placeholder="z.B. 2.0">
                            </div>
                            <div>
                                <label for="geplante_arbeitsplaetze_ausbildung" class="block text-sm font-medium text-slate-700 mb-2">Ausbildungsplätze (VZÄ)</label>
                                <input type="number" id="geplante_arbeitsplaetze_ausbildung" name="geplante_arbeitsplaetze_ausbildung" min="0" step="0.01"
                                       value="<?= e($kunde['geplante_arbeitsplaetze_ausbildung'] ?? '') ?>"
                                       class="w-full px-4 py-3 bg-slate-50 border border-slate-200 rounded-xl text-slate-900 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                                       placeholder="z.B. 1.0">
                            </div>
                        </div>
                    </div>

                    <!-- Investitionsgüterliste -->
                    <div class="mb-8">
                        <h3 class="text-lg font-semibold text-slate-900 mb-4 flex items-center gap-2">
                            <iconify-icon icon="solar:box-bold" class="text-blue-600"></iconify-icon>
                            Investitionsgüterliste
                        </h3>
                        <p class="text-sm text-slate-500 mb-4">
                            <iconify-icon icon="solar:info-circle-linear" class="align-middle mr-1"></iconify-icon>
                            Tragen Sie hier alle geplanten Investitionen ein. Markieren Sie gebrauchte Wirtschaftsgüter.
                        </p>
                        <div class="overflow-x-auto">
                            <table class="w-full border-collapse" id="investitionsgueter-table">
                                <thead>
                                    <tr class="bg-slate-100">
                                        <th class="px-4 py-3 text-left text-sm font-semibold text-slate-700 border border-slate-200" style="width: 50%">Bezeichnung des Wirtschaftsgutes / der Leistung bzw. der Baumaßnahme</th>
                                        <th class="px-4 py-3 text-left text-sm font-semibold text-slate-700 border border-slate-200" style="width: 25%">Wert (EUR)</th>
                                        <th class="px-4 py-3 text-center text-sm font-semibold text-slate-700 border border-slate-200" style="width: 15%">Gebraucht</th>
                                        <th class="px-4 py-3 text-center text-sm font-semibold text-slate-700 border border-slate-200" style="width: 10%"></th>
                                    </tr>
                                </thead>
                                <tbody id="investitionsgueter-body">
                                    <?php foreach ($investitionsgueter as $index => $inv): ?>
                                    <tr class="investitionsgueter-row">
                                        <td class="px-2 py-2 border border-slate-200">
                                            <input type="text" name="inv_bezeichnung[]"
                                                   value="<?= e($inv['bezeichnung'] ?? '') ?>"
                                                   class="w-full px-3 py-2 bg-slate-50 border border-slate-200 rounded-lg text-slate-900 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                                                   placeholder="z.B. CNC-Fräsmaschine">
                                        </td>
                                        <td class="px-2 py-2 border border-slate-200">
                                            <input type="number" name="inv_wert[]" min="0" step="0.01"
                                                   value="<?= e($inv['wert'] ?? '') ?>"
                                                   class="w-full px-3 py-2 bg-slate-50 border border-slate-200 rounded-lg text-slate-900 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                                                   placeholder="z.B. 50000">
                                        </td>
                                        <td class="px-2 py-2 border border-slate-200 text-center">
                                            <input type="checkbox" name="inv_gebraucht[]" value="<?= $index ?>"
                                                   <?= ($inv['gebraucht'] ?? false) ? 'checked' : '' ?>
                                                   class="w-5 h-5 text-blue-600 border-slate-300 rounded focus:ring-blue-500">
                                        </td>
                                        <td class="px-2 py-2 border border-slate-200 text-center">
                                            <button type="button" onclick="removeInvestitionsgut(this)" class="p-2 text-red-500 hover:bg-red-100 rounded-lg transition-colors <?= $index < 1 ? 'invisible' : '' ?>">
                                                <iconify-icon icon="solar:trash-bin-trash-bold" width="18"></iconify-icon>
                                            </button>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                        <button type="button" onclick="addInvestitionsgut()"
                                class="mt-4 flex items-center gap-2 px-4 py-2.5 bg-blue-50 hover:bg-blue-100 text-blue-600 font-medium rounded-xl transition-colors">
                            <iconify-icon icon="solar:add-circle-bold" width="20"></iconify-icon>
                            Zeile hinzufügen
                        </button>
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
        // Durchführungsort Toggle
        function toggleDurchfuehrungsort() {
            const checkbox = document.getElementById('durchfuehrungsort_gleich_adresse');
            const container = document.getElementById('durchfuehrungsort_container');
            container.classList.toggle('hidden', checkbox.checked);
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

        // Wirtschaftlich berechtigte Personen hinzufügen
        function addWBRow() {
            const container = document.getElementById('wb-container');
            const newRow = document.createElement('div');
            newRow.className = 'wb-row bg-slate-50 rounded-xl p-4 border border-slate-200';
            newRow.innerHTML = `
                <div class="grid grid-cols-1 md:grid-cols-6 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-slate-700 mb-2">Vorname</label>
                        <input type="text" name="wb_vorname[]"
                               class="w-full px-3 py-2.5 bg-white border border-slate-200 rounded-lg text-slate-900 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                               placeholder="Max">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-slate-700 mb-2">Nachname</label>
                        <input type="text" name="wb_nachname[]"
                               class="w-full px-3 py-2.5 bg-white border border-slate-200 rounded-lg text-slate-900 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                               placeholder="Mustermann">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-slate-700 mb-2">Steuer-ID (persönlich)</label>
                        <input type="text" name="wb_steuer_id[]"
                               class="w-full px-3 py-2.5 bg-white border border-slate-200 rounded-lg text-slate-900 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent font-mono"
                               placeholder="12 345 678 901">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-slate-700 mb-2">Geburtsdatum</label>
                        <input type="date" name="wb_geburtsdatum[]"
                               class="w-full px-3 py-2.5 bg-white border border-slate-200 rounded-lg text-slate-900 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-slate-700 mb-2">Anteil %</label>
                        <div class="relative">
                            <input type="number" name="wb_anteil[]" min="0" max="100" step="0.01"
                                   class="w-full px-3 py-2.5 bg-white border border-slate-200 rounded-lg text-slate-900 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent pr-8"
                                   placeholder="50">
                            <span class="absolute right-3 top-1/2 -translate-y-1/2 text-slate-500">%</span>
                        </div>
                    </div>
                    <div class="flex items-end">
                        <button type="button" onclick="removeWBRow(this)" class="p-2.5 text-red-500 hover:bg-red-100 rounded-lg transition-colors">
                            <iconify-icon icon="solar:trash-bin-trash-bold" width="20"></iconify-icon>
                        </button>
                    </div>
                </div>
            `;
            container.appendChild(newRow);
            updateWBDeleteButtons();
        }

        // Wirtschaftlich berechtigte Person entfernen
        function removeWBRow(button) {
            const row = button.closest('.wb-row');
            row.remove();
            updateWBDeleteButtons();
        }

        // Delete-Buttons für WB aktualisieren
        function updateWBDeleteButtons() {
            const rows = document.querySelectorAll('.wb-row');
            rows.forEach((row, index) => {
                const deleteBtn = row.querySelector('button[onclick*="removeWBRow"]');
                if (deleteBtn) {
                    if (rows.length === 1) {
                        deleteBtn.classList.add('invisible');
                    } else {
                        deleteBtn.classList.remove('invisible');
                    }
                }
            });
        }

        // Gesellschafter hinzufügen
        function addGesellschafter() {
            const container = document.getElementById('gesellschafter-container');
            const newRow = document.createElement('div');
            newRow.className = 'gesellschafter-row bg-slate-50 rounded-xl p-4 border border-slate-200';
            newRow.innerHTML = `
                <div class="grid grid-cols-1 md:grid-cols-12 gap-4 items-end">
                    <div class="md:col-span-7">
                        <label class="block text-sm font-medium text-slate-700 mb-2">Name des Gesellschafters</label>
                        <input type="text" name="gesellschafter_name[]"
                               class="w-full px-3 py-2.5 bg-white border border-slate-200 rounded-lg text-slate-900 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                               placeholder="Max Mustermann">
                    </div>
                    <div class="md:col-span-4">
                        <label class="block text-sm font-medium text-slate-700 mb-2">Beteiligung</label>
                        <div class="relative">
                            <input type="number" name="gesellschafter_beteiligung[]" min="0" max="100" step="0.01"
                                   class="w-full px-3 py-2.5 bg-white border border-slate-200 rounded-lg text-slate-900 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent pr-8"
                                   placeholder="50">
                            <span class="absolute right-3 top-1/2 -translate-y-1/2 text-slate-500">%</span>
                        </div>
                    </div>
                    <div class="md:col-span-1 flex items-end justify-center">
                        <button type="button" onclick="removeGesellschafter(this)" class="p-2.5 text-red-500 hover:bg-red-100 rounded-lg transition-colors">
                            <iconify-icon icon="solar:trash-bin-trash-bold" width="20"></iconify-icon>
                        </button>
                    </div>
                </div>
            `;
            container.appendChild(newRow);
            updateGesellschafterDeleteButtons();
        }

        // Gesellschafter entfernen
        function removeGesellschafter(button) {
            const row = button.closest('.gesellschafter-row');
            row.remove();
            updateGesellschafterDeleteButtons();
        }

        // Delete-Buttons für Gesellschafter aktualisieren
        function updateGesellschafterDeleteButtons() {
            const rows = document.querySelectorAll('.gesellschafter-row');
            rows.forEach((row, index) => {
                const deleteBtn = row.querySelector('button[onclick*="removeGesellschafter"]');
                if (deleteBtn) {
                    if (rows.length === 1) {
                        deleteBtn.classList.add('invisible');
                    } else {
                        deleteBtn.classList.remove('invisible');
                    }
                }
            });
        }

        // Investitionsgut hinzufügen
        function addInvestitionsgut() {
            const tbody = document.getElementById('investitionsgueter-body');
            const rowCount = tbody.querySelectorAll('.investitionsgueter-row').length;
            const newRow = document.createElement('tr');
            newRow.className = 'investitionsgueter-row';
            newRow.innerHTML = `
                <td class="px-2 py-2 border border-slate-200">
                    <input type="text" name="inv_bezeichnung[]"
                           class="w-full px-3 py-2 bg-slate-50 border border-slate-200 rounded-lg text-slate-900 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                           placeholder="z.B. CNC-Fräsmaschine">
                </td>
                <td class="px-2 py-2 border border-slate-200">
                    <input type="number" name="inv_wert[]" min="0" step="0.01"
                           class="w-full px-3 py-2 bg-slate-50 border border-slate-200 rounded-lg text-slate-900 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                           placeholder="z.B. 50000">
                </td>
                <td class="px-2 py-2 border border-slate-200 text-center">
                    <input type="checkbox" name="inv_gebraucht[]" value="${rowCount}"
                           class="w-5 h-5 text-blue-600 border-slate-300 rounded focus:ring-blue-500">
                </td>
                <td class="px-2 py-2 border border-slate-200 text-center">
                    <button type="button" onclick="removeInvestitionsgut(this)" class="p-2 text-red-500 hover:bg-red-100 rounded-lg transition-colors">
                        <iconify-icon icon="solar:trash-bin-trash-bold" width="18"></iconify-icon>
                    </button>
                </td>
            `;
            tbody.appendChild(newRow);
            updateInvestitionsgueterButtons();
        }

        // Investitionsgut entfernen
        function removeInvestitionsgut(button) {
            const row = button.closest('.investitionsgueter-row');
            row.remove();
            updateInvestitionsgueterButtons();
            updateInvestitionsgueterIndices();
        }

        // Delete-Buttons für Investitionsgüter aktualisieren
        function updateInvestitionsgueterButtons() {
            const rows = document.querySelectorAll('.investitionsgueter-row');
            rows.forEach((row, index) => {
                const deleteBtn = row.querySelector('button[onclick*="removeInvestitionsgut"]');
                if (deleteBtn) {
                    if (rows.length === 1) {
                        deleteBtn.classList.add('invisible');
                    } else {
                        deleteBtn.classList.remove('invisible');
                    }
                }
            });
        }

        // Checkbox-Indices aktualisieren nach Löschen
        function updateInvestitionsgueterIndices() {
            const rows = document.querySelectorAll('.investitionsgueter-row');
            rows.forEach((row, index) => {
                const checkbox = row.querySelector('input[type="checkbox"]');
                if (checkbox) {
                    checkbox.value = index;
                }
            });
        }
    </script>
</body>
</html>
