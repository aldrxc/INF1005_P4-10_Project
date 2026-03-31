<?php
require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/csrf.php';
require_once __DIR__ . '/includes/sanitize.php';

startSession();
requireLogin();

$pageTitle = 'Shopping Cart';
$pdo       = getDB();

$stmt = $pdo->prepare("
    SELECT ci.cart_item_id, ci.quantity, ci.listing_id,
           l.title, l.price, l.status, l.seller_id,
           u.display_name AS seller_display, u.username AS seller_username,
           (SELECT file_path FROM listing_images
            WHERE listing_id = l.listing_id AND is_primary = 1
            LIMIT 1) AS primary_image
    FROM cart_items ci
    JOIN listings l ON ci.listing_id = l.listing_id
    JOIN users u ON l.seller_id = u.user_id
    WHERE ci.user_id = ?
    ORDER BY ci.added_at DESC
");
$stmt->execute([getCurrentUserId()]);
$cartItems  = $stmt->fetchAll();

$total         = 0;
$hasUnavailable = false;
foreach ($cartItems as $item) {
    if ($item['status'] === 'available') {
        $total += (float)$item['price'] * (int)$item['quantity'];
    } else {
        $hasUnavailable = true;
    }
}

generateCsrfToken();
require_once __DIR__ . '/includes/header.php';
?>

<div class="container py-4">
    <h1 class="h3 fw-bold mb-4">
        <i class="bi bi-cart3 text-accent me-2" aria-hidden="true"></i>Shopping Cart
        <span class="badge bg-secondary ms-2 fs-6"><?= count($cartItems) ?></span>
    </h1>

    <?php if (empty($cartItems)): ?>
        <div class="text-center py-5 text-muted">
            <i class="bi bi-cart-x display-4" aria-hidden="true"></i>
            <p class="mt-3 fs-5">Your cart is empty.</p>
            <a href="/browse.php" class="btn btn-accent mt-2">Browse Listings</a>
        </div>
    <?php else: ?>
        <?php if ($hasUnavailable): ?>
            <div class="alert alert-warning" role="alert">
                <i class="bi bi-exclamation-triangle me-2" aria-hidden="true"></i>
                Some items in your cart are no longer available. Please remove them before checking out.
            </div>
        <?php endif; ?>

        <div class="row g-4">
            <!-- Cart items -->
            <div class="col-lg-8">
                <div class="card border-0">
                    <ul class="list-group list-group-flush" id="cartItemsList" aria-label="Cart items">
                        <?php foreach ($cartItems as $item):
                            $available = $item['status'] === 'available';
                            $lineTotal = (float)$item['price'] * (int)$item['quantity'];
                        ?>
                            <li class="list-group-item cart-item py-3 order-summary-card <?= !$available ? 'opacity-50' : '' ?>"
                                id="cartItem<?= (int)$item['cart_item_id'] ?>"
                                data-price="<?= (float)$item['price'] ?>">

                                <div class="d-flex gap-3 align-items-start">
                                    <!-- Thumbnail -->
                                    <a href="/listing.php?id=<?= (int)$item['listing_id'] ?>" tabindex="-1" aria-hidden="true">
                                        <img src="<?= $item['primary_image'] ? '/' . clean($item['primary_image']) : '/assets/images/placeholder.php' ?>"
                                            alt="<?= clean($item['title']) ?>"
                                            class="rounded cart-thumb"
                                            width="80" height="80" style="object-fit:cover">
                                    </a>

                                    <!-- Details -->
                                    <div class="flex-grow-1 min-w-0">
                                        <a href="/listing.php?id=<?= (int)$item['listing_id'] ?>"
                                            class="fw-semibold text-decoration-none text-reset">
                                            <?= clean($item['title']) ?>
                                        </a>
                                        <div class="text-muted small">
                                            by <a href="/profile.php?user=<?= clean($item['seller_username']) ?>"
                                                class="text-muted text-decoration-none">
                                                <?= clean($item['seller_display']) ?>
                                            </a>
                                        </div>

                                        <?php if (!$available): ?>
                                            <span class="badge bg-danger mt-1">
                                                <?= ucfirst($item['status']) ?> — No longer available
                                            </span>
                                        <?php else: ?>
                                            <div class="d-flex align-items-center gap-2 mt-2">
                                                <!-- Qty stepper -->
                                                <div class="input-group input-group-sm qty-stepper" style="width:110px">
                                                    <button class="btn btn-outline-secondary qty-btn" type="button"
                                                        data-action="minus" data-item-id="<?= (int)$item['cart_item_id'] ?>"
                                                        data-listing-id="<?= (int)$item['listing_id'] ?>"
                                                        aria-label="Decrease quantity">−</button>
                                                    <input type="number" class="form-control text-center qty-input"
                                                        value="<?= (int)$item['quantity'] ?>"
                                                        min="1" max="99"
                                                        data-item-id="<?= (int)$item['cart_item_id'] ?>"
                                                        aria-label="Quantity">
                                                    <button class="btn btn-outline-secondary qty-btn" type="button"
                                                        data-action="plus" data-item-id="<?= (int)$item['cart_item_id'] ?>"
                                                        data-listing-id="<?= (int)$item['listing_id'] ?>"
                                                        aria-label="Increase quantity">+</button>
                                                </div>
                                            </div>
                                        <?php endif; ?>
                                    </div>

                                    <!-- Price & remove -->
                                    <div class="text-end d-flex flex-column align-items-end gap-2">
                                        <div class="cart-item-total fw-bold"
                                            id="itemTotal<?= (int)$item['cart_item_id'] ?>">
                                            S$<?= number_format($lineTotal, 2) ?>
                                        </div>
                                        <button type="button"
                                            class="btn btn-sm btn-link text-danger p-0 remove-item-btn"
                                            data-item-id="<?= (int)$item['cart_item_id'] ?>"
                                            data-csrf="<?= clean($_SESSION['csrf_token']) ?>"
                                            aria-label="Remove <?= clean($item['title']) ?> from cart">
                                            <i class="bi bi-trash" aria-hidden="true"></i> Remove
                                        </button>
                                    </div>
                                </div>

                            </li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            </div>

            <!-- Order summary -->
            <div class="col-lg-4">
                <div class="card border-0 order-summary-card sticky-top" style="top:80px">
                    <div class="card-body p-4">
                        <h2 class="h5 fw-bold mb-3 text-hotpink">Order Summary</h2>

                        <div class="d-flex justify-content-between mb-2 text-muted small">
                            <span>Subtotal (<?= count($cartItems) ?> items)</span>
                            <span id="cartSubtotal">S$<?= number_format($total, 2) ?></span>
                        </div>
                        <div class="d-flex justify-content-between mb-3 text-muted small">
                            <span>Shipping</span>
                            <span>Arranged with seller</span>
                        </div>
                        <hr>
                        <div class="d-flex justify-content-between fw-bold fs-5 mb-4">
                            <span class="text-white">Total</span>
                            <span class="text-white" id="cartTotal">S$<?= number_format($total, 2) ?></span>
                        </div>

                        <a href="/checkout.php"
                            class="btn btn-accent w-100 fw-semibold <?= ($hasUnavailable || $total == 0) ? 'disabled' : '' ?>"
                            <?= ($hasUnavailable || $total == 0) ? 'aria-disabled="true"' : '' ?>>
                            Proceed to Checkout
                            <i class="bi bi-arrow-right ms-2" aria-hidden="true"></i>
                        </a>

                        <a href="/browse.php" class="btn btn-link w-100 mt-2 text-muted small">
                            <i class="bi bi-arrow-left me-1" aria-hidden="true"></i>Continue Shopping
                        </a>
                    </div>
                </div>
            </div>
        </div>
    <?php endif; ?>
</div>

<script>
    // Pass CSRF token to cart.js
    const CSRF_TOKEN = <?= json_encode($_SESSION['csrf_token'] ?? '') ?>;
</script>
<script src="/assets/js/cart.js" defer></script>
<?php require_once __DIR__ . '/includes/footer.php'; ?>