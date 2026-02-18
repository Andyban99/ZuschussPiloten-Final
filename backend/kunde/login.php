<?php
/**
 * Zuschuss Piloten - Kunden Login & Registrierung
 */

require_once 'auth.php';

// Wenn bereits eingeloggt, zum Dashboard weiterleiten
if (isKundeLoggedIn()) {
    header('Location: dashboard.php');
    exit;
}

$error = '';
$success = '';
$mode = $_GET['mode'] ?? 'login'; // login oder register

// Timeout-Meldung
if (isset($_GET['timeout'])) {
    $error = 'Ihre Sitzung ist abgelaufen. Bitte melden Sie sich erneut an.';
}

// Login verarbeiten
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    if ($_POST['action'] === 'login') {
        $email = trim($_POST['email'] ?? '');
        $password = $_POST['password'] ?? '';

        if (empty($email) || empty($password)) {
            $error = 'Bitte füllen Sie alle Felder aus.';
        } elseif (doKundeLogin($email, $password)) {
            header('Location: dashboard.php');
            exit;
        } else {
            $error = 'Ungültige E-Mail-Adresse oder Passwort.';
        }
    } elseif ($_POST['action'] === 'register') {
        $email = trim($_POST['email'] ?? '');
        $password = $_POST['password'] ?? '';
        $password_confirm = $_POST['password_confirm'] ?? '';
        $vorname = trim($_POST['vorname'] ?? '');
        $nachname = trim($_POST['nachname'] ?? '');

        if (empty($email) || empty($password) || empty($vorname) || empty($nachname)) {
            $error = 'Bitte füllen Sie alle Pflichtfelder aus.';
            $mode = 'register';
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $error = 'Bitte geben Sie eine gültige E-Mail-Adresse ein.';
            $mode = 'register';
        } elseif (strlen($password) < 8) {
            $error = 'Das Passwort muss mindestens 8 Zeichen lang sein.';
            $mode = 'register';
        } elseif ($password !== $password_confirm) {
            $error = 'Die Passwörter stimmen nicht überein.';
            $mode = 'register';
        } else {
            $result = doKundeRegister($email, $password, $vorname, $nachname);
            if ($result['success']) {
                // Direkt einloggen
                doKundeLogin($email, $password);
                header('Location: dashboard.php?welcome=1');
                exit;
            } else {
                $error = $result['error'];
                $mode = 'register';
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kunden Login - Zuschuss Piloten</title>
    <link rel="icon" type="image/svg+xml" href="../../assets/Icon Black White BG.svg">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://code.iconify.design/iconify-icon/1.0.7/iconify-icon.min.js"></script>
    <style>
        body { font-family: 'Inter', sans-serif; }
        .login-bg {
            background: linear-gradient(135deg, #0B1120 0%, #1e3a5f 50%, #0B1120 100%);
        }
        .glass-card {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(20px);
        }
    </style>
</head>
<body class="login-bg min-h-screen flex items-center justify-center p-4">
    <!-- Decorative Elements -->
    <div class="fixed inset-0 overflow-hidden pointer-events-none">
        <div class="absolute top-0 right-0 w-96 h-96 bg-blue-500/20 rounded-full blur-3xl"></div>
        <div class="absolute bottom-0 left-0 w-96 h-96 bg-indigo-500/20 rounded-full blur-3xl"></div>
    </div>

    <div class="w-full max-w-md relative z-10">
        <!-- Logo -->
        <div class="text-center mb-8">
            <a href="../../index.html" class="inline-flex items-center gap-3">
                <img src="../../assets/Icon Black White BG.svg" alt="Logo" class="w-12 h-12">
                <div class="text-left">
                    <span class="block text-white font-bold text-xl">Zuschuss Piloten</span>
                    <span class="block text-blue-300 text-xs uppercase tracking-widest">Kundenportal</span>
                </div>
            </a>
        </div>

        <!-- Login/Register Card -->
        <div class="glass-card rounded-2xl shadow-2xl overflow-hidden">
            <!-- Tabs -->
            <div class="flex border-b border-slate-200">
                <a href="?mode=login"
                   class="flex-1 px-6 py-4 text-center font-medium transition-colors <?= $mode === 'login' ? 'bg-white text-blue-600 border-b-2 border-blue-600' : 'bg-slate-50 text-slate-500 hover:text-slate-700' ?>">
                    <iconify-icon icon="solar:login-2-bold" class="mr-2 align-middle"></iconify-icon>
                    Anmelden
                </a>
                <a href="?mode=register"
                   class="flex-1 px-6 py-4 text-center font-medium transition-colors <?= $mode === 'register' ? 'bg-white text-blue-600 border-b-2 border-blue-600' : 'bg-slate-50 text-slate-500 hover:text-slate-700' ?>">
                    <iconify-icon icon="solar:user-plus-bold" class="mr-2 align-middle"></iconify-icon>
                    Registrieren
                </a>
            </div>

            <div class="p-8">
                <?php if ($error): ?>
                <div class="mb-6 p-4 bg-red-50 border border-red-200 rounded-xl flex items-start gap-3">
                    <iconify-icon icon="solar:danger-triangle-bold" class="text-red-500 text-xl flex-shrink-0 mt-0.5"></iconify-icon>
                    <span class="text-red-700 text-sm"><?= e($error) ?></span>
                </div>
                <?php endif; ?>

                <?php if ($success): ?>
                <div class="mb-6 p-4 bg-emerald-50 border border-emerald-200 rounded-xl flex items-start gap-3">
                    <iconify-icon icon="solar:check-circle-bold" class="text-emerald-500 text-xl flex-shrink-0 mt-0.5"></iconify-icon>
                    <span class="text-emerald-700 text-sm"><?= e($success) ?></span>
                </div>
                <?php endif; ?>

                <?php if ($mode === 'login'): ?>
                <!-- Login Form -->
                <form method="POST" class="space-y-5">
                    <input type="hidden" name="action" value="login">

                    <div>
                        <label for="email" class="block text-sm font-medium text-slate-700 mb-2">E-Mail-Adresse</label>
                        <div class="relative">
                            <iconify-icon icon="solar:letter-bold" class="absolute left-4 top-1/2 -translate-y-1/2 text-slate-400"></iconify-icon>
                            <input type="email" id="email" name="email" required
                                   class="w-full pl-11 pr-4 py-3 bg-slate-50 border border-slate-200 rounded-xl text-slate-900 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-all"
                                   placeholder="ihre@email.de">
                        </div>
                    </div>

                    <div>
                        <label for="password" class="block text-sm font-medium text-slate-700 mb-2">Passwort</label>
                        <div class="relative">
                            <iconify-icon icon="solar:lock-password-bold" class="absolute left-4 top-1/2 -translate-y-1/2 text-slate-400"></iconify-icon>
                            <input type="password" id="password" name="password" required
                                   class="w-full pl-11 pr-4 py-3 bg-slate-50 border border-slate-200 rounded-xl text-slate-900 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-all"
                                   placeholder="Ihr Passwort">
                        </div>
                    </div>

                    <button type="submit"
                            class="w-full py-3.5 bg-[#0F172A] hover:bg-blue-900 text-white font-medium rounded-xl transition-all shadow-lg shadow-blue-900/20 flex items-center justify-center gap-2">
                        <iconify-icon icon="solar:login-2-bold"></iconify-icon>
                        Anmelden
                    </button>
                </form>

                <?php else: ?>
                <!-- Register Form -->
                <form method="POST" class="space-y-5">
                    <input type="hidden" name="action" value="register">

                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label for="vorname" class="block text-sm font-medium text-slate-700 mb-2">Vorname *</label>
                            <input type="text" id="vorname" name="vorname" required
                                   value="<?= e($_POST['vorname'] ?? '') ?>"
                                   class="w-full px-4 py-3 bg-slate-50 border border-slate-200 rounded-xl text-slate-900 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-all"
                                   placeholder="Max">
                        </div>
                        <div>
                            <label for="nachname" class="block text-sm font-medium text-slate-700 mb-2">Nachname *</label>
                            <input type="text" id="nachname" name="nachname" required
                                   value="<?= e($_POST['nachname'] ?? '') ?>"
                                   class="w-full px-4 py-3 bg-slate-50 border border-slate-200 rounded-xl text-slate-900 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-all"
                                   placeholder="Mustermann">
                        </div>
                    </div>

                    <div>
                        <label for="reg_email" class="block text-sm font-medium text-slate-700 mb-2">E-Mail-Adresse *</label>
                        <div class="relative">
                            <iconify-icon icon="solar:letter-bold" class="absolute left-4 top-1/2 -translate-y-1/2 text-slate-400"></iconify-icon>
                            <input type="email" id="reg_email" name="email" required
                                   value="<?= e($_POST['email'] ?? '') ?>"
                                   class="w-full pl-11 pr-4 py-3 bg-slate-50 border border-slate-200 rounded-xl text-slate-900 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-all"
                                   placeholder="ihre@email.de">
                        </div>
                    </div>

                    <div>
                        <label for="reg_password" class="block text-sm font-medium text-slate-700 mb-2">Passwort * <span class="text-slate-400 font-normal">(min. 8 Zeichen)</span></label>
                        <div class="relative">
                            <iconify-icon icon="solar:lock-password-bold" class="absolute left-4 top-1/2 -translate-y-1/2 text-slate-400"></iconify-icon>
                            <input type="password" id="reg_password" name="password" required minlength="8"
                                   class="w-full pl-11 pr-4 py-3 bg-slate-50 border border-slate-200 rounded-xl text-slate-900 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-all"
                                   placeholder="Sicheres Passwort">
                        </div>
                    </div>

                    <div>
                        <label for="password_confirm" class="block text-sm font-medium text-slate-700 mb-2">Passwort bestätigen *</label>
                        <div class="relative">
                            <iconify-icon icon="solar:lock-password-bold" class="absolute left-4 top-1/2 -translate-y-1/2 text-slate-400"></iconify-icon>
                            <input type="password" id="password_confirm" name="password_confirm" required
                                   class="w-full pl-11 pr-4 py-3 bg-slate-50 border border-slate-200 rounded-xl text-slate-900 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-all"
                                   placeholder="Passwort wiederholen">
                        </div>
                    </div>

                    <div class="flex items-start gap-3">
                        <input type="checkbox" id="datenschutz" name="datenschutz" required
                               class="mt-1 w-4 h-4 text-blue-600 border-slate-300 rounded focus:ring-blue-500">
                        <label for="datenschutz" class="text-sm text-slate-600">
                            Ich stimme den <a href="../../datenschutz.html" target="_blank" class="text-blue-600 hover:underline">Datenschutzbestimmungen</a> und
                            <a href="../../agb.html" target="_blank" class="text-blue-600 hover:underline">AGB</a> zu. *
                        </label>
                    </div>

                    <button type="submit"
                            class="w-full py-3.5 bg-[#0F172A] hover:bg-blue-900 text-white font-medium rounded-xl transition-all shadow-lg shadow-blue-900/20 flex items-center justify-center gap-2">
                        <iconify-icon icon="solar:user-plus-bold"></iconify-icon>
                        Konto erstellen
                    </button>
                </form>
                <?php endif; ?>
            </div>
        </div>

        <!-- Back to Website -->
        <div class="text-center mt-6">
            <a href="../../index.html" class="text-blue-300 hover:text-white text-sm transition-colors flex items-center justify-center gap-2">
                <iconify-icon icon="solar:arrow-left-linear"></iconify-icon>
                Zurück zur Website
            </a>
        </div>
    </div>
</body>
</html>
