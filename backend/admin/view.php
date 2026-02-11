<?php
/**
 * Zuschuss Piloten - Anfrage Detailansicht
 */

require_once 'auth.php';
requireLogin();

$db = getDB();
$id = (int)($_GET['id'] ?? 0);

if (!$id) {
    header('Location: index.php');
    exit;
}

// Anfrage laden
$stmt = $db->prepare("SELECT * FROM anfragen WHERE id = :id");
$stmt->execute([':id' => $id]);
$anfrage = $stmt->fetch();

if (!$anfrage) {
    header('Location: index.php');
    exit;
}

// Update verarbeiten
$success = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    try {
        if ($action === 'update_status') {
            $newStatus = $_POST['status'];
            $stmt = $db->prepare("UPDATE anfragen SET status = :status, bearbeitet_von = :user WHERE id = :id");
            $stmt->execute([':status' => $newStatus, ':user' => $_SESSION['admin_user'], ':id' => $id]);
            $anfrage['status'] = $newStatus;
            $success = 'Status aktualisiert';
        }

        if ($action === 'update_notizen') {
            $notizen = trim($_POST['notizen']);
            $stmt = $db->prepare("UPDATE anfragen SET notizen = :notizen WHERE id = :id");
            $stmt->execute([':notizen' => $notizen, ':id' => $id]);
            $anfrage['notizen'] = $notizen;
            $success = 'Notizen gespeichert';
        }

        if ($action === 'update_prioritaet') {
            $prio = $_POST['prioritaet'];
            $stmt = $db->prepare("UPDATE anfragen SET prioritaet = :prio WHERE id = :id");
            $stmt->execute([':prio' => $prio, ':id' => $id]);
            $anfrage['prioritaet'] = $prio;
            $success = 'Priorität aktualisiert';
        }

        if ($action === 'delete') {
            $stmt = $db->prepare("DELETE FROM anfragen WHERE id = :id");
            $stmt->execute([':id' => $id]);
            header('Location: index.php?deleted=1');
            exit;
        }
    } catch (PDOException $e) {
        $error = 'Ein Fehler ist aufgetreten';
    }
}

$statusColors = [
    'neu' => 'bg-blue-100 text-blue-700 border-blue-200',
    'in_bearbeitung' => 'bg-amber-100 text-amber-700 border-amber-200',
    'erledigt' => 'bg-emerald-100 text-emerald-700 border-emerald-200'
];

$statusLabels = [
    'neu' => 'Neu',
    'in_bearbeitung' => 'In Bearbeitung',
    'erledigt' => 'Erledigt'
];
?>
<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Anfrage #<?= $id ?> - Zuschuss Piloten Admin</title>
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
        <!-- Sidebar (gleich wie in index.php) -->
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
                    <iconify-icon icon="solar:arrow-left-linear" width="20"></iconify-icon>
                    Zurück zur Übersicht
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
                    <a href="index.php" class="w-10 h-10 bg-white border border-slate-200 rounded-xl flex items-center justify-center text-slate-500 hover:text-slate-900 hover:border-slate-300 transition-all">
                        <iconify-icon icon="solar:arrow-left-linear" width="20"></iconify-icon>
                    </a>
                    <div>
                        <h1 class="text-2xl font-bold text-slate-900">Anfrage #<?= $id ?></h1>
                        <p class="text-slate-500">Eingegangen am <?= date('d.m.Y \u\m H:i', strtotime($anfrage['erstellt_am'])) ?> Uhr</p>
                    </div>
                </div>
                <span class="inline-flex items-center gap-2 px-4 py-2 text-sm font-medium rounded-xl border <?= $statusColors[$anfrage['status']] ?>">
                    <?= $statusLabels[$anfrage['status']] ?>
                </span>
            </div>

            <?php if ($success): ?>
            <div class="mb-6 p-4 bg-emerald-50 border border-emerald-200 rounded-xl flex items-center gap-3 text-emerald-700">
                <iconify-icon icon="solar:check-circle-bold" width="20"></iconify-icon>
                <span class="text-sm font-medium"><?= e($success) ?></span>
            </div>
            <?php endif; ?>

            <div class="grid grid-cols-3 gap-6">
                <!-- Hauptinhalt -->
                <div class="col-span-2 space-y-6">
                    <!-- Kontaktdaten -->
                    <div class="bg-white rounded-2xl border border-slate-200 shadow-sm p-6">
                        <h2 class="font-semibold text-slate-900 mb-6 flex items-center gap-2">
                            <iconify-icon icon="solar:user-bold" width="20" class="text-slate-400"></iconify-icon>
                            Kontaktdaten
                        </h2>
                        <div class="grid grid-cols-2 gap-6">
                            <div>
                                <label class="block text-xs font-medium text-slate-400 uppercase tracking-wider mb-1">Name</label>
                                <p class="text-slate-900 font-medium text-lg"><?= e($anfrage['name']) ?></p>
                            </div>
                            <div>
                                <label class="block text-xs font-medium text-slate-400 uppercase tracking-wider mb-1">Unternehmen</label>
                                <p class="text-slate-900 font-medium text-lg"><?= e($anfrage['unternehmen']) ?></p>
                            </div>
                            <div>
                                <label class="block text-xs font-medium text-slate-400 uppercase tracking-wider mb-1">E-Mail</label>
                                <a href="mailto:<?= e($anfrage['email']) ?>" class="text-blue-600 hover:text-blue-800 font-medium flex items-center gap-2">
                                    <iconify-icon icon="solar:letter-bold" width="18"></iconify-icon>
                                    <?= e($anfrage['email']) ?>
                                </a>
                            </div>
                            <div>
                                <label class="block text-xs font-medium text-slate-400 uppercase tracking-wider mb-1">Telefon</label>
                                <?php if ($anfrage['telefon']): ?>
                                <a href="tel:<?= e($anfrage['telefon']) ?>" class="text-blue-600 hover:text-blue-800 font-medium flex items-center gap-2">
                                    <iconify-icon icon="solar:phone-bold" width="18"></iconify-icon>
                                    <?= e($anfrage['telefon']) ?>
                                </a>
                                <?php else: ?>
                                <p class="text-slate-400">Nicht angegeben</p>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>

                    <!-- Nachricht -->
                    <div class="bg-white rounded-2xl border border-slate-200 shadow-sm p-6">
                        <h2 class="font-semibold text-slate-900 mb-4 flex items-center gap-2">
                            <iconify-icon icon="solar:document-text-bold" width="20" class="text-slate-400"></iconify-icon>
                            Nachricht
                        </h2>
                        <div class="bg-slate-50 rounded-xl p-4 border border-slate-100">
                            <?php if ($anfrage['nachricht']): ?>
                            <p class="text-slate-700 whitespace-pre-wrap leading-relaxed"><?= e($anfrage['nachricht']) ?></p>
                            <?php else: ?>
                            <p class="text-slate-400 italic">Keine Nachricht angegeben</p>
                            <?php endif; ?>
                        </div>
                    </div>

                    <!-- Notizen -->
                    <div class="bg-white rounded-2xl border border-slate-200 shadow-sm p-6">
                        <h2 class="font-semibold text-slate-900 mb-4 flex items-center gap-2">
                            <iconify-icon icon="solar:notes-bold" width="20" class="text-slate-400"></iconify-icon>
                            Interne Notizen
                        </h2>
                        <form method="POST">
                            <input type="hidden" name="action" value="update_notizen">
                            <textarea name="notizen" rows="4" placeholder="Notizen hinzufügen..."
                                      class="w-full px-4 py-3 bg-slate-50 border border-slate-200 rounded-xl text-slate-700 placeholder-slate-400 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent resize-none"><?= e($anfrage['notizen'] ?? '') ?></textarea>
                            <button type="submit" class="mt-3 px-4 py-2 bg-slate-900 hover:bg-slate-800 text-white text-sm font-medium rounded-lg transition-colors flex items-center gap-2">
                                <iconify-icon icon="solar:diskette-bold" width="16"></iconify-icon>
                                Notizen speichern
                            </button>
                        </form>
                    </div>
                </div>

                <!-- Sidebar -->
                <div class="space-y-6">
                    <!-- Quick Actions -->
                    <div class="bg-white rounded-2xl border border-slate-200 shadow-sm p-6">
                        <h2 class="font-semibold text-slate-900 mb-4">Schnellaktionen</h2>
                        <div class="space-y-3">
                            <a href="mailto:<?= e($anfrage['email']) ?>" class="w-full px-4 py-3 bg-blue-600 hover:bg-blue-700 text-white font-medium rounded-xl transition-colors flex items-center justify-center gap-2">
                                <iconify-icon icon="solar:letter-bold" width="18"></iconify-icon>
                                E-Mail senden
                            </a>
                            <?php if ($anfrage['telefon']): ?>
                            <a href="tel:<?= e($anfrage['telefon']) ?>" class="w-full px-4 py-3 bg-emerald-600 hover:bg-emerald-700 text-white font-medium rounded-xl transition-colors flex items-center justify-center gap-2">
                                <iconify-icon icon="solar:phone-bold" width="18"></iconify-icon>
                                Anrufen
                            </a>
                            <?php endif; ?>
                        </div>
                    </div>

                    <!-- Status ändern -->
                    <div class="bg-white rounded-2xl border border-slate-200 shadow-sm p-6">
                        <h2 class="font-semibold text-slate-900 mb-4">Status</h2>
                        <form method="POST" class="space-y-3">
                            <input type="hidden" name="action" value="update_status">
                            <select name="status" onchange="this.form.submit()"
                                    class="w-full px-4 py-3 bg-slate-50 border border-slate-200 rounded-xl text-slate-700 focus:outline-none focus:ring-2 focus:ring-blue-500 cursor-pointer">
                                <option value="neu" <?= $anfrage['status'] === 'neu' ? 'selected' : '' ?>>Neu</option>
                                <option value="in_bearbeitung" <?= $anfrage['status'] === 'in_bearbeitung' ? 'selected' : '' ?>>In Bearbeitung</option>
                                <option value="erledigt" <?= $anfrage['status'] === 'erledigt' ? 'selected' : '' ?>>Erledigt</option>
                            </select>
                        </form>
                    </div>

                    <!-- Priorität -->
                    <div class="bg-white rounded-2xl border border-slate-200 shadow-sm p-6">
                        <h2 class="font-semibold text-slate-900 mb-4">Priorität</h2>
                        <form method="POST">
                            <input type="hidden" name="action" value="update_prioritaet">
                            <div class="flex gap-2">
                                <button type="submit" name="prioritaet" value="normal"
                                        class="flex-1 px-3 py-2 rounded-lg text-sm font-medium transition-colors <?= $anfrage['prioritaet'] === 'normal' ? 'bg-slate-900 text-white' : 'bg-slate-100 text-slate-600 hover:bg-slate-200' ?>">
                                    Normal
                                </button>
                                <button type="submit" name="prioritaet" value="hoch"
                                        class="flex-1 px-3 py-2 rounded-lg text-sm font-medium transition-colors <?= $anfrage['prioritaet'] === 'hoch' ? 'bg-amber-500 text-white' : 'bg-slate-100 text-slate-600 hover:bg-slate-200' ?>">
                                    Hoch
                                </button>
                                <button type="submit" name="prioritaet" value="dringend"
                                        class="flex-1 px-3 py-2 rounded-lg text-sm font-medium transition-colors <?= $anfrage['prioritaet'] === 'dringend' ? 'bg-red-500 text-white' : 'bg-slate-100 text-slate-600 hover:bg-slate-200' ?>">
                                    Dringend
                                </button>
                            </div>
                        </form>
                    </div>

                    <!-- Meta -->
                    <div class="bg-white rounded-2xl border border-slate-200 shadow-sm p-6">
                        <h2 class="font-semibold text-slate-900 mb-4">Details</h2>
                        <dl class="space-y-3 text-sm">
                            <div class="flex justify-between">
                                <dt class="text-slate-500">Erstellt</dt>
                                <dd class="text-slate-900"><?= date('d.m.Y H:i', strtotime($anfrage['erstellt_am'])) ?></dd>
                            </div>
                            <div class="flex justify-between">
                                <dt class="text-slate-500">Aktualisiert</dt>
                                <dd class="text-slate-900"><?= date('d.m.Y H:i', strtotime($anfrage['aktualisiert_am'])) ?></dd>
                            </div>
                            <?php if ($anfrage['bearbeitet_von']): ?>
                            <div class="flex justify-between">
                                <dt class="text-slate-500">Bearbeitet von</dt>
                                <dd class="text-slate-900"><?= e($anfrage['bearbeitet_von']) ?></dd>
                            </div>
                            <?php endif; ?>
                        </dl>
                    </div>

                    <!-- Löschen -->
                    <div class="bg-red-50 rounded-2xl border border-red-200 p-6">
                        <h2 class="font-semibold text-red-900 mb-2">Gefahrenzone</h2>
                        <p class="text-sm text-red-600 mb-4">Diese Aktion kann nicht rückgängig gemacht werden.</p>
                        <form method="POST" onsubmit="return confirm('Sind Sie sicher, dass Sie diese Anfrage löschen möchten?')">
                            <input type="hidden" name="action" value="delete">
                            <button type="submit" class="w-full px-4 py-2 bg-red-600 hover:bg-red-700 text-white text-sm font-medium rounded-lg transition-colors flex items-center justify-center gap-2">
                                <iconify-icon icon="solar:trash-bin-trash-bold" width="16"></iconify-icon>
                                Anfrage löschen
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        </main>
    </div>
</body>
</html>
