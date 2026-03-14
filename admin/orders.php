<?php
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/sanitize.php';

startSession();
requireAdmin();

$pageTitle = 'Admin — Orders';
$pdo = getDB();

$orders = $pdo->query("
    SELECT o.order_id, o.total_amount, o.status, o.created_at,
           u.display_name AS buyer_display,
           COUNT(oi.order_item_id) AS item_count
    FROM orders o
    JOIN users u ON o.buyer_id = u.user_id
    LEFT JOIN order_items oi ON oi.order_id = o.order_id
    GROUP BY o.order_id
    ORDER BY o.created_at DESC
")->fetchAll();

require_once __DIR__ . '/../includes/header.php';
?>

<div class="container py-4">
    <nav aria-label="breadcrumb" class="mb-3">
        <ol class="breadcrumb breadcrumb-dark small">
            <li class="breadcrumb-item"><a href="/admin/">Admin</a></li>
            <li class="breadcrumb-item active">Orders</li>
        </ol>
    </nav>
    <h1 class="mb-4">Orders <span class="badge bg-secondary ms-2"><?= count($orders) ?></span></h1>

    <?php if (empty($orders)): ?>
        <p class="text-muted">No orders yet.</p>
    <?php else: ?>
    <div class="table-responsive">
        <table class="table table-dark table-hover align-middle">
            <thead>
                <tr>
                    <th>Order ID</th>
                    <th>Buyer</th>
                    <th>Total</th>
                    <th>Items</th>
                    <th>Status</th>
                    <th>Date</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($orders as $o): ?>
                <?php
                $statusColor = match($o['status']) {
                    'completed'  => 'success',
                    'shipped'    => 'info',
                    'confirmed'  => 'primary',
                    'cancelled'  => 'danger',
                    default      => 'warning',
                };
                ?>
                <tr>
                    <td class="text-muted">#<?= (int)$o['order_id'] ?></td>
                    <td><?= clean($o['buyer_display']) ?></td>
                    <td>S$<?= number_format((float)$o['total_amount'], 2) ?></td>
                    <td><?= (int)$o['item_count'] ?></td>
                    <td><span class="badge bg-<?= $statusColor ?>"><?= ucfirst($o['status']) ?></span></td>
                    <td class="text-muted small"><?= clean(date('d M Y, H:i', strtotime($o['created_at']))) ?></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <?php endif; ?>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
