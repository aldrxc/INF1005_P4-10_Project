<?php
// one-time admin setup — only works when there are zero admin accounts
// once an admin exists this page locks itself and redirects away

require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/csrf.php';
require_once __DIR__ . '/includes/sanitize.php';

startSession();

$pdo = getDB();

// lock the page the moment any admin account exists
$adminCount = (int)$pdo->query("SELECT COUNT(*) FROM users WHERE role = 'admin'")->fetchColumn();
if ($adminCount > 0) {
    header('Location: /index.php');
    exit;
}

$error   = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    validateCsrfToken();

    $username = trim($_POST['username'] ?? '');

    $stmt = $pdo->prepare("SELECT user_id, display_name FROM users WHERE username = ? AND is_active = 1 LIMIT 1");
    $stmt->execute([$username]);
    $target = $stmt->fetch();

    if (!$target) {
        $error = 'No active account found with that username.';
    } else {
        $pdo->prepare("UPDATE users SET role = 'admin' WHERE user_id = ?")->execute([$target['user_id']]);
        $success = clean($target['display_name']) . ' is now an admin. This setup page is now permanently locked.';
    }
}

generateCsrfToken();
$pageTitle = 'First-Time Setup';
require_once __DIR__ . '/includes/header.php';
?>

<div class="container py-5" style="max-width: 480px">

    <div class="text-center mb-4">
        <i class="bi bi-shield-lock display-4 text-accent" aria-hidden="true"></i>
        <h1 class="h3 fw-bold mt-3">First-Time Setup</h1>
        <p class="text-muted small">
            No admin account exists yet. Enter an existing username to promote it.
            This page will lock itself once done.
        </p>
    </div>

    <?php if ($error): ?>
        <div class="alert alert-danger"><?= $error ?></div>
    <?php endif; ?>

    <?php if ($success): ?>
        <div class="alert alert-success">
            <?= $success ?>
            <div class="mt-2">
                <a href="/admin/" class="btn btn-sm btn-accent">Go to Admin Panel</a>
            </div>
        </div>
    <?php else: ?>
        <div class="card border-0 p-4" style="background:var(--mv-surface-2)">
            <form method="POST">
                <?= getCsrfField() ?>
                <div class="mb-3">
                    <label for="username" class="form-label fw-semibold">Username to promote</label>
                    <input type="text" class="form-control" id="username" name="username"
                           placeholder="e.g. ivan" required autofocus>
                    <div class="form-text text-muted">The account must already exist and be active.</div>
                </div>
                <button type="submit" class="btn btn-accent w-100">
                    <i class="bi bi-shield-check me-1" aria-hidden="true"></i>Make Admin
                </button>
            </form>
        </div>
    <?php endif; ?>

</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
