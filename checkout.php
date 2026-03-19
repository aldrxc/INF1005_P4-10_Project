<?php
require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/csrf.php';
require_once __DIR__ . '/includes/sanitize.php';

startSession();
requireLogin();

$pageTitle = 'Checkout';
$pdo = getDB();

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

if (count($cartItems) == 0) {
    setFlash('Your cart is empty.', 'warning');
    header('Location: /cart.php');
    exit();
}

$total = 0;
foreach ($cartItems as $item) {
    if ($item['status'] !== 'available') {
        setFlash('Some items in your cart are no longer available. Please remove them before checking out.', 'danger');
        header('Location: /cart.php');
        exit();
    }

    $total = $total + ($item['price'] * $item['quantity']);
}

$errors = [];
$old = [];

if (isset($_SESSION['checkout_errors'])) {
    $errors = $_SESSION['checkout_errors'];
    
    if (isset($_SESSION['checkout_old'])) {
        $old = $_SESSION['checkout_old'];
    }
    
    unset($_SESSION['checkout_errors']);
    unset($_SESSION['checkout_old']);
}

generateCsrfToken();
require_once __DIR__ . '/includes/header.php';
?>

<div class="container py-4">
    <h1 class="h3 fw-bold mb-4">
        <i class="bi bi-bag-check text-accent me-2"></i>Checkout
    </h1>

    <div class="row g-4">
        <div class="col-lg-7">
            <div class="card border-0 mb-4 order-summary-card">
                <div class="card-body p-4 ">
                    <h2 class="h5 fw-bold mb-3 text-white">Shipping Details</h2>

                    <?php if (isset($errors['general'])): ?>
                        <div class="alert alert-danger" role="alert"><?= clean($errors['general']) ?></div>
                    <?php endif; ?>

                    <form method="POST" action="/handlers/checkout-handler.php" id="checkoutForm" onsubmit="return validateCheckout()">
                        <?= getCsrfField() ?>

                        <div class="mb-3">
                            <label for="shipping_name" class="form-label">Full Name <span class="text-accent">*</span></label>
                            <input type="text" class="form-control <?= isset($errors['shipping_name']) ? 'is-invalid' : '' ?>"
                                id="shipping_name" name="shipping_name" required maxlength="150"
                                value="<?= clean($old['shipping_name'] ?? '') ?>">
                            <?php if (isset($errors['shipping_name'])): ?>
                                <div class="invalid-feedback" style="display:block;"><?= clean($errors['shipping_name']) ?></div>
                            <?php endif; ?>
                        </div>

                        <div class="row g-3 mb-3">
                            <div class="col-sm-4">
                                <label for="shipping_block" class="form-label">Block / House No. <span class="text-accent">*</span></label>
                                <input type="text" class="form-control <?= isset($errors['shipping_block']) ? 'is-invalid' : '' ?>"
                                    id="shipping_block" name="shipping_block" required maxlength="10"
                                    value="<?= clean($old['shipping_block'] ?? '') ?>" placeholder="e.g. 123A">
                                <?php if (isset($errors['shipping_block'])): ?>
                                    <div class="invalid-feedback" style="display:block;"><?= clean($errors['shipping_block']) ?></div>
                                <?php endif; ?>
                            </div>
                            
                            <div class="col-sm-8">
                                <label for="shipping_street" class="form-label">Street Name <span class="text-accent">*</span></label>
                                <input type="text" class="form-control <?= isset($errors['shipping_street']) ? 'is-invalid' : '' ?>"
                                    id="shipping_street" name="shipping_street" required maxlength="100"
                                    value="<?= clean($old['shipping_street'] ?? '') ?>" placeholder="e.g. 1 Punggol Coast Road, Singapore 828608">
                                <?php if (isset($errors['shipping_street'])): ?>
                                    <div class="invalid-feedback" style="display:block;"><?= clean($errors['shipping_street']) ?></div>
                                <?php endif; ?>
                            </div>
                        </div>

                        <div class="mb-3">
                            <label for="shipping_unit" class="form-label">Unit Number</label>
                            <input type="text" class="form-control <?= isset($errors['shipping_unit']) ? 'is-invalid' : '' ?>"
                                id="shipping_unit" name="shipping_unit" maxlength="15"
                                value="<?= clean($old['shipping_unit'] ?? '') ?>" placeholder="e.g. #04-56">
                        </div>

                        <div class="row g-3 mb-4">
                            <div class="col-sm-4">
                                <label for="shipping_postal" class="form-label">Postal Code <span class="text-accent">*</span></label>
                                <input type="text" class="form-control <?= isset($errors['shipping_postal']) ? 'is-invalid' : '' ?>"
                                    id="shipping_postal" name="shipping_postal" required maxlength="6"
                                    value="<?= clean($old['shipping_postal'] ?? '') ?>">
                                <div id="postalError" class="invalid-feedback" style="display: none;">Please enter a valid 6-digit Singapore postal code.</div>
                                <?php if (isset($errors['shipping_postal'])): ?>
                                    <div class="invalid-feedback" style="display:block;"><?= clean($errors['shipping_postal']) ?></div>
                                <?php endif; ?>
                            </div>
                            <div class="col-sm-8">
                                <label for="shipping_country" class="form-label">Country <span class="text-accent">*</span></label>
                                <select class="form-select" id="shipping_country" name="shipping_country" required>
                                    <option value="Singapore" selected>Singapore</option>
                                </select>
                                <small class="text-muted" style="font-size: 0.75rem;">We currently only ship within Singapore.</small>
                                <?php if (isset($errors['shipping_country'])): ?>
                                    <div class="invalid-feedback" style="display:block;"><?= clean($errors['shipping_country']) ?></div>
                                <?php endif; ?>
                            </div>
                        </div>

                        <button type="submit" class="btn btn-accent w-100 fw-semibold btn-lg">
                            <i class="bi bi-lock me-2"></i>Place Order — S$<?= number_format($total, 2) ?>
                        </button>
                    </form>
                </div>
            </div>
        </div>

        <div class="col-lg-5">
            <div class="card border-0 order-summary-card">
                <div class="card-body p-4">
                    <h2 class="h5 fw-bold mb-3 text-hotpink">Order Summary</h2>
                    <ul class="list-group list-group-flush order-summary-card">
                        <?php foreach ($cartItems as $item): ?>
                            <li class="list-group-item py-2 d-flex gap-3 align-items-center order-summary-card">
                                <?php 
                                    $imgSrc = '/assets/images/placeholder.png'; 
                                    if ($item['primary_image']) {
                                        $imgSrc = '/' . clean($item['primary_image']);
                                    }
                                ?>
                                <img src="<?= $imgSrc ?>" alt="<?= clean($item['title']) ?>" class="rounded" width="48" height="48" style="object-fit:cover">
                                    
                                <div class="flex-grow-1 min-w-0">
                                    <div class="small fw-semibold text-truncate text-white"><?= clean($item['title']) ?></div>
                                    <div class="text-muted" style="font-size:0.78rem">
                                        Qty: <?= $item['quantity'] ?> &bull; <?= clean($item['seller_display']) ?>
                                    </div>
                                </div>
                                <div class="small fw-semibold text-nowrap text-white">
                                    S$<?= number_format($item['price'] * $item['quantity'], 2) ?>
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

<script>

function validateCheckout() {
    var postalInput = document.getElementById('shipping_postal');
    var postalError = document.getElementById('postalError');
    var postalValue = postalInput.value.trim();
    
    // Check for exactly 6 digits (Singapore format)
    var sgPostalRegex = /^[0-9]{6}$/;
    
    if (!postalValue.match(sgPostalRegex)) {
        postalInput.classList.add('is-invalid');
        postalError.style.display = 'block';
        return false; 
    }
    
    postalInput.classList.remove('is-invalid');
    postalError.style.display = 'none';
    return true; 
}
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
