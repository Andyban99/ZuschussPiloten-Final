<?php
/**
 * Zuschuss Piloten - Admin Login
 */

require_once 'auth.php';

$error = '';

// Bereits eingeloggt?
if (isLoggedIn()) {
    header('Location: index.php');
    exit;
}

// Login-Versuch
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';

    if (doLogin($username, $password)) {
        header('Location: index.php');
        exit;
    } else {
        $error = 'Ungültige Anmeldedaten';
    }
}

$timeout = isset($_GET['timeout']);
?>
<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Login - Zuschuss Piloten</title>
    <link rel="icon" type="image/svg+xml" href="../../assets/favicon.svg">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://code.iconify.design/iconify-icon/1.0.7/iconify-icon.min.js"></script>
    <style>
        body { font-family: 'Inter', sans-serif; }
        .glass-panel {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(20px);
        }
    </style>
</head>
<body class="min-h-screen bg-gradient-to-br from-[#0B1120] via-[#1e3a5f] to-[#0B1120] flex items-center justify-center p-6">
    <!-- Background Effects -->
    <div class="fixed inset-0 overflow-hidden pointer-events-none">
        <div class="absolute top-0 right-0 w-[600px] h-[600px] bg-blue-500/20 rounded-full blur-[120px] -translate-y-1/2 translate-x-1/3"></div>
        <div class="absolute bottom-0 left-0 w-[400px] h-[400px] bg-indigo-500/20 rounded-full blur-[100px] translate-y-1/3 -translate-x-1/4"></div>
    </div>

    <div class="w-full max-w-md relative z-10">
        <!-- Logo -->
        <div class="text-center mb-8">
            <div class="inline-flex items-center gap-3 mb-4">
                <div class="w-12 h-12 bg-[#0B1120] rounded-xl flex items-center justify-center text-white shadow-lg shadow-blue-900/30">
                    <iconify-icon icon="solar:plain-3-bold-duotone" width="28"></iconify-icon>
                </div>
                <div class="text-left">
                    <span class="block text-white font-bold text-xl tracking-tight">Zuschuss Piloten</span>
                    <span class="block text-xs text-blue-300 uppercase tracking-widest">Admin Dashboard</span>
                </div>
            </div>
        </div>

        <!-- Login Card -->
        <div class="glass-panel rounded-2xl shadow-2xl p-8 border border-white/20">
            <h1 class="text-2xl font-bold text-slate-900 mb-2">Willkommen zurück</h1>
            <p class="text-slate-500 mb-8">Melden Sie sich an, um fortzufahren</p>

            <?php if ($error): ?>
            <div class="mb-6 p-4 bg-red-50 border border-red-200 rounded-xl flex items-center gap-3 text-red-700">
                <iconify-icon icon="solar:danger-triangle-bold" width="20"></iconify-icon>
                <span class="text-sm font-medium"><?= e($error) ?></span>
            </div>
            <?php endif; ?>

            <?php if ($timeout): ?>
            <div class="mb-6 p-4 bg-amber-50 border border-amber-200 rounded-xl flex items-center gap-3 text-amber-700">
                <iconify-icon icon="solar:clock-circle-bold" width="20"></iconify-icon>
                <span class="text-sm font-medium">Ihre Sitzung ist abgelaufen. Bitte melden Sie sich erneut an.</span>
            </div>
            <?php endif; ?>

            <form method="POST" class="space-y-5">
                <div>
                    <label for="username" class="block text-sm font-semibold text-slate-700 mb-2">Benutzername</label>
                    <div class="relative">
                        <iconify-icon icon="solar:user-bold" class="absolute left-4 top-1/2 -translate-y-1/2 text-slate-400" width="20"></iconify-icon>
                        <input type="text" id="username" name="username" required autofocus
                               class="w-full pl-12 pr-4 py-3.5 bg-slate-50 border border-slate-200 rounded-xl text-slate-900 placeholder-slate-400 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-all"
                               placeholder="admin">
                    </div>
                </div>

                <div>
                    <label for="password" class="block text-sm font-semibold text-slate-700 mb-2">Passwort</label>
                    <div class="relative">
                        <iconify-icon icon="solar:lock-password-bold" class="absolute left-4 top-1/2 -translate-y-1/2 text-slate-400" width="20"></iconify-icon>
                        <input type="password" id="password" name="password" required
                               class="w-full pl-12 pr-4 py-3.5 bg-slate-50 border border-slate-200 rounded-xl text-slate-900 placeholder-slate-400 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-all"
                               placeholder="••••••••">
                    </div>
                </div>

                <button type="submit" class="w-full py-4 bg-blue-600 hover:bg-blue-700 text-white font-semibold rounded-xl shadow-lg shadow-blue-600/30 transition-all flex items-center justify-center gap-2 group">
                    <span>Anmelden</span>
                    <iconify-icon icon="solar:arrow-right-linear" width="20" class="group-hover:translate-x-1 transition-transform"></iconify-icon>
                </button>
            </form>
        </div>

        <p class="text-center text-slate-400 text-sm mt-6">
            <iconify-icon icon="solar:shield-check-bold" class="mr-1"></iconify-icon>
            Sichere Verbindung
        </p>
    </div>
</body>
</html>
