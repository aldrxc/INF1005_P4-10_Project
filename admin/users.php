<?php
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/csrf.php';
require_once __DIR__ . '/../includes/sanitize.php';

startSession();
requireAdmin();

$pageTitle = 'Admin — Users';
$pdo = getDB();
$myId = getCurrentUserId();

$users = $pdo->query("
    SELECT user_id, display_name, username, joined_at, is_active, role
    FROM users
    ORDER BY joined_at DESC
")->fetchAll();

generateCsrfToken();
require_once __DIR__ . '/../includes/header.php';
?>

<div class="container py-4">
    <nav aria-label="breadcrumb" class="mb-3">
        <ol class="breadcrumb breadcrumb-dark small">
            <li class="breadcrumb-item"><a href="/admin/">Admin</a></li>
            <li class="breadcrumb-item active">Users</li>
        </ol>
    </nav>
    <h1 class="mb-4">Users <span class="badge bg-secondary ms-2"><?= count($users) ?></span></h1>

    <div class="table-responsive">
        <table class="table table-dark table-hover align-middle">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Display Name</th>
                    <th>Username</th>
                    <th>Joined</th>
                    <th>Status</th>
                    <th>Role</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($users as $u): ?>
                <tr>
                    <td class="text-muted"><?= (int)$u['user_id'] ?></td>
                    <td><?= clean($u['display_name']) ?></td>
                    <td class="text-muted">@<?= clean($u['username']) ?></td>
                    <td class="text-muted small"><?= clean(date('d M Y', strtotime($u['joined_at']))) ?></td>
                    <td>
                        <?php if ($u['is_active']): ?>
                            <span class="badge bg-success">Active</span>
                        <?php else: ?>
                            <span class="badge bg-danger">Banned</span>
                        <?php endif; ?>
                    </td>
                    <td>
                        <?php if ($u['role'] === 'admin'): ?>
                            <span class="badge bg-accent">Admin</span>
                        <?php else: ?>
                            <span class="badge bg-secondary">User</span>
                        <?php endif; ?>
                    </td>
                    <td>
                        <?php if ((int)$u['user_id'] === $myId): ?>
                            <span class="text-muted small">You</span>
                        <?php else: ?>
                            <div class="d-flex gap-1 flex-wrap">
                                <!-- ban / unban -->
                                <form method="POST" action="/admin/ban-handler.php" class="d-inline">
                                    <?= getCsrfField() ?>
                                    <input type="hidden" name="user_id" value="<?= (int)$u['user_id'] ?>">
                                    <?php if ($u['is_active']): ?>
                                        <input type="hidden" name="action" value="ban">
                                        <button type="submit" class="btn btn-sm btn-outline-danger"
                                                onclick="return confirm('Ban <?= clean($u['username']) ?>?')">
                                            <i class="bi bi-slash-circle me-1" aria-hidden="true"></i>Ban
                                        </button>
                                    <?php else: ?>
                                        <input type="hidden" name="action" value="unban">
                                        <button type="submit" class="btn btn-sm btn-outline-success">
                                            <i class="bi bi-check-circle me-1" aria-hidden="true"></i>Unban
                                        </button>
                                    <?php endif; ?>
                                </form>

                                <!-- promote / demote -->
                                <form method="POST" action="/admin/role-handler.php" class="d-inline">
                                    <?= getCsrfField() ?>
                                    <input type="hidden" name="user_id" value="<?= (int)$u['user_id'] ?>">
                                    <?php if ($u['role'] === 'admin'): ?>
                                        <input type="hidden" name="action" value="demote">
                                        <button type="submit" class="btn btn-sm btn-outline-warning"
                                                onclick="return confirm('Remove admin from <?= clean($u['username']) ?>?')">
                                            <i class="bi bi-arrow-down-circle me-1" aria-hidden="true"></i>Demote
                                        </button>
                                    <?php else: ?>
                                        <input type="hidden" name="action" value="promote">
                                        <button type="submit" class="btn btn-sm btn-outline-secondary"
                                                onclick="return confirm('Make <?= clean($u['username']) ?> an admin?')">
                                            <i class="bi bi-arrow-up-circle me-1" aria-hidden="true"></i>Promote
                                        </button>
                                    <?php endif; ?>
                                </form>
                            </div>
                        <?php endif; ?>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
