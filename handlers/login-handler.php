<?php
// POST handler — user login
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/csrf.php';
require_once __DIR__ . '/../includes/sanitize.php';

startSession();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: /login.php');
    exit;
}

validateCsrfToken();

$identifier = trim($_POST['identifier'] ?? '');
$password   = $_POST['password']        ?? '';
$redirect   = $_POST['redirect']        ?? '';

// Basic presence check
if ($identifier === '' || $password === '') {
    $_SESSION['login_error'] = 'Please enter your email/username and password.';
    $_SESSION['login_old']   = $identifier;
    header('Location: /login.php');
    exit;
}

// Find user by email OR username
$pdo  = getDB();
$stmt = $pdo->prepare("
    SELECT user_id, username, display_name, password_hash, is_active, role
    FROM users
    WHERE email = ? OR username = ?
    LIMIT 1
");
$stmt->execute([$identifier, $identifier]);
$user = $stmt->fetch();

// Verify credentials — use generic error to avoid user enumeration
if (!$user || !password_verify($password, $user['password_hash'])) {
    $_SESSION['login_error'] = 'Invalid username/email or password.';
    $_SESSION['login_old']   = $identifier;
    header('Location: /login.php');
    exit;
}

if (!(bool)$user['is_active']) {
    $_SESSION['login_error'] = 'This account has been deactivated. Please contact support.';
    header('Location: /login.php');
    exit;
}

session_regenerate_id(true);
$_SESSION['user_id']      = (int)$user['user_id'];
$_SESSION['username']     = $user['username'];
$_SESSION['display_name'] = $user['display_name'];
$_SESSION['role']         = $user['role'];

setFlash('Welcome back, ' . htmlspecialchars($user['display_name'], ENT_QUOTES, 'UTF-8') . '!', 'success');

// Redirect to intended page or dashboard (validate redirect is internal)
if ($redirect && strpos($redirect, '/') === 0 && strpos($redirect, '//') !== 0) {
    header('Location: ' . $redirect);
} else {
    header('Location: /dashboard.php');
}
exit;
