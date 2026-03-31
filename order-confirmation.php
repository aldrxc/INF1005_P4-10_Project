<?php
require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/csrf.php';
require_once __DIR__ . '/includes/sanitize.php';

startSession();
requireLogin();


$orderId = 0;
if (isset($_GET['id'])) {
    $orderId = (int)$_GET['id'];
}

if ($orderId === 0) {
    header('Location: /dashboard.php');
    exit();
}

$pdo = getDB();
$stmt = $pdo->prepare("SELECT * FROM orders WHERE order_id = ? AND buyer_id = ? LIMIT 1");
$stmt->execute([$orderId, getCurrentUserId()]);
$order = $stmt->fetch();

if (!$order) {
    setFlash('Order not found.', 'danger');
    header('Location: /dashboard.php');
    exit();
}


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

// check which sellers the buyer has already reviewed for this order
$reviewedStmt = $pdo->prepare("SELECT seller_id FROM reviews WHERE order_id = ? AND reviewer_id = ?");
$reviewedStmt->execute([$orderId, getCurrentUserId()]);
$alreadyReviewed = $reviewedStmt->fetchAll(PDO::FETCH_COLUMN);

// collect distinct sellers in this order
$sellerStmt = $pdo->prepare("
    SELECT DISTINCT oi.seller_id, u.display_name AS seller_display
    FROM order_items oi
    JOIN users u ON u.user_id = oi.seller_id
    WHERE oi.order_id = ?
");
$sellerStmt->execute([$orderId]);
$orderSellers = $sellerStmt->fetchAll();

generateCsrfToken();
$pageTitle = 'Order Confirmed — #' . $orderId;
require_once __DIR__ . '/includes/header.php';
?>

<div class="container py-5">
    <div class="row justify-content-center">
        <div class="col-12 col-lg-8">

            <div class="text-center mb-5">
                <div class="mb-3">
                    <i class="bi bi-check-circle-fill text-success" style="font-size:4rem"></i>
                </div>
                <h1 class="h2 fw-bold">Order Confirmed!</h1>
                <p class="text-muted fs-5">
                    Thank you for your purchase. Your order #<?= $orderId ?> has been placed.
                </p>
                <p class="text-muted small">
                    Placed on <?= clean(date('d M Y, h:i A', strtotime($order['created_at']))) ?>
                </p>
            </div>

            <div class="card border-0 mb-4 order-summary-card">
                <div class="card-body p-4">
                    <h2 class="h5 fw-bold mb-3 text-hotpink">Order #<?= $orderId ?></h2>

                    <ul class="list-group list-group-flush mb-3">
                        <?php foreach ($orderItems as $item): ?>
                            <li class="list-group-item d-flex gap-3 align-items-center order-summary-card">
                                <?php 
                                    // Human-readable image pathing
                                    $imgSrc = '/assets/images/placeholder.png'; 
                                    if ($item['primary_image']) {
                                        $imgSrc = '/' . clean($item['primary_image']);
                                    }
                                ?>
                                <img src="<?= $imgSrc ?>" alt="<?= clean($item['title']) ?>" class="rounded" width="56" height="56" style="object-fit:cover">
                                
                                <div class="flex-grow-1">
                                    <div class="fw-semibold">
                                        <a href="/listing.php?id=<?= (int)$item['listing_id'] ?>" class="text-decoration-none text-reset">
                                            <?= clean($item['title']) ?>
                                        </a>
                                    </div>
                                    <div class="text-muted small">
                                        Sold by <?= clean($item['seller_display']) ?> &bull; Qty: <?= (int)$item['quantity'] ?>
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

                    <h3 class="h6 fw-bold text-muted mb-2">Shipping To</h3>
                    <address class="text-muted small mb-0">
                        <?= clean($order['shipping_name']) ?><br>
                        <?= nl2br(clean($order['shipping_address'])) ?><br>
                        <?= clean($order['shipping_postal']) ?>, <?= clean($order['shipping_country']) ?>
                    </address>
                </div>
            </div>

            <?php
            $unreviewedSellers = array_filter($orderSellers, fn($s) => !in_array((int)$s['seller_id'], array_map('intval', $alreadyReviewed)));
            ?>
            <?php if (!empty($unreviewedSellers)): ?>
                <div class="card mb-4">
                    <div class="card-body p-4">
                        <h3 class="h6 fw-bold mb-3">
                            <i class="bi bi-star me-2 text-accent" aria-hidden="true"></i>Leave a Review
                        </h3>
                        <?php foreach ($unreviewedSellers as $seller): ?>
                            <p class="small text-muted mb-2">Rate your experience with <strong><?= clean($seller['seller_display']) ?></strong>:</p>
                            <form method="POST" action="/handlers/review-handler.php" class="mb-3">
                                <?= getCsrfField() ?>
                                <input type="hidden" name="order_id"  value="<?= $orderId ?>">
                                <input type="hidden" name="seller_id" value="<?= (int)$seller['seller_id'] ?>">
                                <div class="d-flex gap-2 mb-2 align-items-center">
                                    <?php for ($i = 1; $i <= 5; $i++): ?>
                                        <div class="form-check form-check-inline">
                                            <input class="form-check-input visually-hidden" type="radio"
                                                   name="rating" id="star_<?= (int)$seller['seller_id'] ?>_<?= $i ?>"
                                                   value="<?= $i ?>" required>
                                            <label class="form-check-label star-label fs-4"
                                                   for="star_<?= (int)$seller['seller_id'] ?>_<?= $i ?>"
                                                   style="cursor:pointer;color:var(--mv-border)">&#9733;</label>
                                        </div>
                                    <?php endfor; ?>
                                </div>
                                <textarea class="form-control mb-2" name="body" rows="2" maxlength="500"
                                          placeholder="Optional comment…"></textarea>
                                <button type="submit" class="btn btn-sm btn-accent">Submit Review</button>
                            </form>
                        <?php endforeach; ?>
                    </div>
                </div>
            <?php endif; ?>

            <div class="d-flex flex-column flex-sm-row gap-3 justify-content-center">
                <a href="/dashboard.php#purchases" class="btn btn-outline-secondary">
                    <i class="bi bi-receipt me-2"></i>View My Orders
                </a>
                <a href="/browse.php" class="btn btn-accent px-4">
                    <i class="bi bi-search me-2"></i>Keep Browsing
                </a>
            </div>

        </div>
    </div>
</div>

<script>
// highlight stars up to the hovered/selected one
document.querySelectorAll('.star-label').forEach(label => {
    label.addEventListener('mouseenter', () => highlightStars(label, true));
    label.addEventListener('mouseleave', () => highlightStars(label, false));
});
document.querySelectorAll('input[name="rating"]').forEach(radio => {
    radio.addEventListener('change', () => {
        const form = radio.closest('form');
        form.querySelectorAll('.star-label').forEach(l => l.classList.remove('selected'));
        const idx = radio.value;
        form.querySelectorAll('.star-label').forEach((l, i) => {
            if (i < idx) l.classList.add('selected');
        });
    });
});
function highlightStars(label, on) {
    const form = label.closest('form');
    const labels = [...form.querySelectorAll('.star-label')];
    const idx = labels.indexOf(label);
    labels.forEach((l, i) => {
        l.style.color = on && i <= idx ? 'var(--mv-accent)' : '';
    });
}
</script>
<?php require_once __DIR__ . '/includes/footer.php'; ?>
