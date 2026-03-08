<?php
require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/csrf.php';
require_once __DIR__ . '/includes/sanitize.php';

startSession();
requireLogin();

$pageTitle = 'Checkout';
$pdo       = getDB();

// Fetch cart for summary
$stmt = $pdo->prepare("
    SELECT ci.cart_item_id, ci.quantity, l.listing_id, l.title, l.price, l.status, l.seller_id,
           u.display_name AS seller_display,
           (SELECT file_path FROM listing_images WHERE listing_id = l.listing_id AND is_primary=1 LIMIT 1) AS primary_image
    FROM cart_items ci
    JOIN listings l ON ci.listing_id = l.listing_id
    JOIN users u ON l.seller_id = u.user_id
    WHERE ci.user_id = ?
    ORDER BY ci.added_at DESC
");
$stmt->execute([getCurrentUserId()]);
$cartItems = $stmt->fetchAll();

if (empty($cartItems)) {
    setFlash('Your cart is empty.', 'warning');
    header('Location: /cart.php');
    exit;
}

// Block checkout if any item unavailable
foreach ($cartItems as $item) {
    if ($item['status'] !== 'available') {
        setFlash('Some items in your cart are no longer available. Please remove them before checking out.', 'danger');
        header('Location: /cart.php');
        exit;
    }
}

$total  = array_sum(array_map(fn($i) => (float)$i['price'] * (int)$i['quantity'], $cartItems));
$errors = [];
$old    = [];
if (!empty($_SESSION['checkout_errors'])) {
    $errors = $_SESSION['checkout_errors'];
    $old    = $_SESSION['checkout_old'] ?? [];
    unset($_SESSION['checkout_errors'], $_SESSION['checkout_old']);
}

generateCsrfToken();
require_once __DIR__ . '/includes/header.php';
?>

<div class="container py-4">
    <h1 class="h3 fw-bold mb-4">
        <i class="bi bi-bag-check text-accent me-2" aria-hidden="true"></i>Checkout
    </h1>

    <div class="row g-4">

        <!-- Shipping form -->
        <div class="col-lg-7">
            <div class="card border-0 mb-4 order-summary-card">
                <div class="card-body p-4 ">
                    <h2 class="h5 fw-bold mb-3 text-white">Shipping Details</h2>

                    <?php if (!empty($errors['general'])): ?>
                        <div class="alert alert-danger" role="alert"><?= clean($errors['general']) ?></div>
                    <?php endif; ?>

                    <form method="POST" action="/handlers/checkout-handler.php" novalidate id="checkoutForm">
                        <?= getCsrfField() ?>

                        <div class="mb-3">
                            <label for="shipping_name" class="form-label">Full Name <span class="text-accent" aria-hidden="true">*</span></label>
                            <input type="text" class="form-control <?= isset($errors['shipping_name']) ? 'is-invalid' : '' ?>"
                                id="shipping_name" name="shipping_name" required maxlength="150"
                                value="<?= clean($old['shipping_name'] ?? '') ?>"
                                autocomplete="name">
                            <?php if (isset($errors['shipping_name'])): ?><div class="invalid-feedback"><?= clean($errors['shipping_name']) ?></div><?php endif; ?>
                        </div>

                        <div class="mb-3">
                            <label for="shipping_address" class="form-label">Shipping Address <span class="text-accent" aria-hidden="true">*</span></label>
                            <textarea class="form-control <?= isset($errors['shipping_address']) ? 'is-invalid' : '' ?>"
                                id="shipping_address" name="shipping_address" required rows="3"
                                autocomplete="street-address"><?= clean($old['shipping_address'] ?? '') ?></textarea>
                            <?php if (isset($errors['shipping_address'])): ?><div class="invalid-feedback"><?= clean($errors['shipping_address']) ?></div><?php endif; ?>
                        </div>

                        <div class="row g-3 mb-4">
                            <div class="col-sm-4">
                                <label for="shipping_postal" class="form-label">Postal Code <span class="text-accent" aria-hidden="true">*</span></label>
                                <input type="text" class="form-control <?= isset($errors['shipping_postal']) ? 'is-invalid' : '' ?>"
                                    id="shipping_postal" name="shipping_postal" required maxlength="20"
                                    value="<?= clean($old['shipping_postal'] ?? '') ?>"
                                    autocomplete="postal-code">
                                <?php if (isset($errors['shipping_postal'])): ?><div class="invalid-feedback"><?= clean($errors['shipping_postal']) ?></div><?php endif; ?>
                            </div>
                            <div class="col-sm-8">
                                <label for="shipping_country" class="form-label">Country</label>
                                <input type="text" class="form-control"
                                    id="shipping_country" name="shipping_country"
                                    value="<?= clean($old['shipping_country'] ?? 'Singapore') ?>"
                                    maxlength="100" autocomplete="country-name">
                            </div>
                        </div>

                        <button type="submit" class="btn btn-accent w-100 fw-semibold btn-lg">
                            <i class="bi bi-lock me-2" aria-hidden="true"></i>Place Order
                            — S$<?= number_format($total, 2) ?>
                        </button>
                        <p class="text-muted small text-center mt-2 mb-0">
                            By placing an order you agree to our terms. Payments and delivery to be arranged with the seller.
                        </p>
                    </form>
                </div>
            </div>
        </div>

        <!-- Order summary -->
        <div class="col-lg-5">
            <div class="card border-0 order-summary-card">
                <div class="card-body p-4">
                    <h2 class="h5 fw-bold mb-3 text-hotpink">Order Summary</h2>
                    <ul class="list-group list-group-flush order-summary-card">
                        <?php foreach ($cartItems as $item): ?>
                            <li class="list-group-item py-2 d-flex gap-3 align-items-center order-summary-card">
                                <img src="<?= $item['primary_image'] ? '/' . clean($item['primary_image']) : '/assets/images/placeholder.php' ?>"
                                    alt="<?= clean($item['title']) ?>"
                                    class="rounded" width="48" height="48" style="object-fit:cover">
                                <div class="flex-grow-1 min-w-0">
                                    <div class="small fw-semibold text-truncate text-white"><?= clean($item['title']) ?></div>
                                    <div class="text-muted" style="font-size:0.78rem">
                                        Qty: <?= (int)$item['quantity'] ?> &bull; <?= clean($item['seller_display']) ?>
                                    </div>
                                </div>
                                <div class="small fw-semibold text-nowrap text-white">
                                    S$<?= number_format((float)$item['price'] * (int)$item['quantity'], 2) ?>
                                </div>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                    <hr>
                    <div class="d-flex justify-content-between fw-bold fs-5">
                        <span class="text-white">Total</span>
                        <span class="text-white">S$<?= number_format($total, 2) ?></span>
                    </div>
                </div>
            </div>
        </div>

    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>