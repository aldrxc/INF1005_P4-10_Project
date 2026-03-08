<?php
require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/sanitize.php';

startSession();
requireLogin();

$orderId = sanitizeInt($_GET['id'] ?? '');
if (!$orderId) {
    header('Location: /dashboard.php');
    exit;
}

$pdo  = getDB();
$stmt = $pdo->prepare("SELECT * FROM orders WHERE order_id = ? AND buyer_id = ? LIMIT 1");
$stmt->execute([$orderId, getCurrentUserId()]);
$order = $stmt->fetch();

if (!$order) {
    setFlash('Order not found.', 'danger');
    header('Location: /dashboard.php');
    exit;
}

// Order items
$stmt = $pdo->prepare("
    SELECT oi.*, l.title, l.listing_id,
           u.display_name AS seller_display,
           (SELECT file_path FROM listing_images WHERE listing_id = l.listing_id AND is_primary=1 LIMIT 1) AS primary_image
    FROM order_items oi
    JOIN listings l ON oi.listing_id = l.listing_id
    JOIN users u ON oi.seller_id = u.user_id
    WHERE oi.order_id = ?
");
$stmt->execute([$orderId]);
$orderItems = $stmt->fetchAll();

$pageTitle = 'Order Confirmed — #' . $orderId;
require_once __DIR__ . '/includes/header.php';
?>

<div class="container py-5">
    <div class="row justify-content-center">
        <div class="col-12 col-lg-8">

            <!-- Success banner -->
            <div class="text-center mb-5">
                <div class="confirmation-icon mb-3" aria-hidden="true">
                    <i class="bi bi-check-circle-fill text-success" style="font-size:4rem"></i>
                </div>
                <h1 class="h2 fw-bold">Order Confirmed!</h1>
                <p class="text-muted fs-5">
                    Thank you for your purchase. Your order #<?= (int)$orderId ?> has been placed.
                </p>
                <p class="text-muted small">
                    Placed on <?= clean(date('D, d M Y \a\t h:i A', strtotime($order['created_at']))) ?>
                </p>
            </div>

            <!-- Order details card -->
            <div class="card border-0 mb-4 order-summary-card">
                <div class="card-body p-4">
                    <h2 class="h5 fw-bold mb-3 text-hotpink">Order #<?= (int)$orderId ?></h2>

                    <ul class="list-group list-group-flush mb-3">
                        <?php foreach ($orderItems as $item): ?>
                            <li class="list-group-item d-flex gap-3 align-items-center order-summary-card">
                                <img src="<?= $item['primary_image'] ? '/' . clean($item['primary_image']) : '/assets/images/placeholder.php' ?>"
                                    alt="<?= clean($item['title']) ?>"
                                    class="rounded" width="56" height="56" style="object-fit:cover">
                                <div class="flex-grow-1">
                                    <div class="fw-semibold">
                                        <a href="/listing.php?id=<?= (int)$item['listing_id'] ?>"
                                            class="text-decoration-none text-reset">
                                            <?= clean($item['title']) ?>
                                        </a>
                                    </div>
                                    <div class="text-muted small">
                                        Sold by <?= clean($item['seller_display']) ?> &bull;
                                        Qty: <?= (int)$item['quantity'] ?>
                                    </div>
                                </div>
                                <div class="fw-bold text-white">S$<?= number_format((float)$item['price_paid'], 2) ?></div>
                            </li>
                        <?php endforeach; ?>
                    </ul>

                    <div class="d-flex justify-content-between fw-bold fs-5 mb-4">
                        <span class="text-white">Total Paid</span>
                        <span class="text-white">S$<?= number_format((float)$order['total_amount'], 2) ?></span>
                    </div>

                    <!-- Shipping info -->
                    <h3 class="h6 fw-bold text-muted mb-2">Shipping To</h3>
                    <address class="text-muted small mb-0">
                        <?= clean($order['shipping_name']) ?><br>
                        <?= nl2br(clean($order['shipping_address'])) ?><br>
                        <?= clean($order['shipping_postal']) ?>, <?= clean($order['shipping_country']) ?>
                    </address>
                </div>
            </div>

            <!-- Actions -->
            <div class="d-flex flex-column flex-sm-row gap-3 justify-content-center">
                <a href="/dashboard.php#purchases" class="btn btn-outline-secondary">
                    <i class="bi bi-receipt me-2" aria-hidden="true"></i>View My Orders
                </a>
                <a href="/browse.php" class="btn btn-accent px-4">
                    <i class="bi bi-search me-2" aria-hidden="true"></i>Keep Browsing
                </a>
            </div>

        </div>
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>