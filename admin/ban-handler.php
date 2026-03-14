<?php
// POST handler — ban or unban a user
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/csrf.php';
require_once __DIR__ . '/../includes/sanitize.php';

startSession();
requireAdmin();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: /admin/users.php');
    exit;
}

validateCsrfToken();

$targetId = sanitizeInt($_POST['user_id'] ?? '');
$action   = sanitizeEnum($_POST['action'] ?? '', ['ban', 'unban']);

if (!$targetId || !$action) {
    setFlash('Invalid request.', 'danger');
    header('Location: /admin/users.php');
    exit;
}

// can't ban yourself
if ($targetId === getCurrentUserId()) {
    setFlash('You cannot ban your own account.', 'danger');
    header('Location: /admin/users.php');
    exit;
}

$isActive = $action === 'unban' ? 1 : 0;

$pdo = getDB();
$pdo->prepare("UPDATE users SET is_active = ? WHERE user_id = ?")->execute([$isActive, $targetId]);

setFlash('User has been ' . ($action === 'ban' ? 'banned' : 'unbanned') . '.', 'success');
header('Location: /admin/users.php');
exit;
