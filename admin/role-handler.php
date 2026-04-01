<?php
// promote or demote a user's role - admins only, cant change your own role

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

$targetId = (int)($_POST['user_id'] ?? 0);
$action   = $_POST['action'] ?? '';
$myId     = getCurrentUserId();

if ($targetId === $myId) {
    setFlash('You cannot change your own role.', 'warning');
    header('Location: /admin/users.php');
    exit;
}

if (!in_array($action, ['promote', 'demote'], true)) {
    header('Location: /admin/users.php');
    exit;
}

$pdo = getDB();

// make sure target user exists
$stmt = $pdo->prepare("SELECT display_name FROM users WHERE user_id = ? LIMIT 1");
$stmt->execute([$targetId]);
$target = $stmt->fetch();

if (!$target) {
    setFlash('User not found.', 'danger');
    header('Location: /admin/users.php');
    exit;
}

$newRole = $action === 'promote' ? 'admin' : 'user';
$pdo->prepare("UPDATE users SET role = ? WHERE user_id = ?")->execute([$newRole, $targetId]);

$label = $action === 'promote' ? 'promoted to admin' : 'demoted to user';
setFlash(clean($target['display_name']) . ' has been ' . $label . '.', 'success');
header('Location: /admin/users.php');
exit;
