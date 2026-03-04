<?php
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/csrf.php';
require_once __DIR__ . '/../includes/sanitize.php';

startSession();
requireLogin();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: /checkout.php');
    exit;
}

validateCsrfToken();

$shippingName    = trim($_POST['shipping_name']    ?? '');
$shippingAddress = trim($_POST['shipping_address'] ?? '');
$shippingPostal  = trim($_POST['shipping_postal']  ?? '');
$shippingCountry = trim($_POST['shipping_country'] ?? 'Singapore') ?: 'Singapore';
$errors = [];
$old    = compact('shippingName','shippingAddress','shippingPostal','shippingCountry');
$old['shipping_name']    = $shippingName;
$old['shipping_address'] = $shippingAddress;
$old['shipping_postal']  = $shippingPostal;
$old['shipping_country'] = $shippingCountry;

if ($shippingName    === '') $errors['shipping_name']    = 'Full name is required.';
if ($shippingAddress === '') $errors['shipping_address'] = 'Shipping address is required.';
if ($shippingPostal  === '') $errors['shipping_postal']  = 'Postal code is required.';

if (!empty($errors)) {
    $_SESSION['checkout_errors'] = $errors;
    $_SESSION['checkout_old']    = $old;
    header('Location: /checkout.php');
    exit;
}

$pdo    = getDB();
$userId = getCurrentUserId();

// Re-fetch cart (final availability check)
$stmt = $pdo->prepare("
    SELECT ci.cart_item_id, ci.quantity, l.listing_id, l.price, l.status, l.seller_id
    FROM cart_items ci
    JOIN listings l ON ci.listing_id = l.listing_id
    WHERE ci.user_id = ?
");
$stmt->execute([$userId]);
$cartItems = $stmt->fetchAll();

if (empty($cartItems)) {
    setFlash('Your cart is empty.', 'warning');
    header('Location: /cart.php');
    exit;
}

foreach ($cartItems as $item) {
    if ($item['status'] !== 'available') {
        setFlash('Some items are no longer available. Please review your cart.', 'danger');
        header('Location: /cart.php');
        exit;
    }
}

$total = array_sum(array_map(fn($i) => (float)$i['price'] * (int)$i['quantity'], $cartItems));

$pdo->beginTransaction();
try {
    // Create order
    $stmt = $pdo->prepare("
        INSERT INTO orders
            (buyer_id, total_amount, status, shipping_name, shipping_address, shipping_postal, shipping_country)
        VALUES
            (:buyer_id, :total_amount, 'pending', :shipping_name, :shipping_address, :shipping_postal, :shipping_country)
    ");
    $stmt->execute([
        ':buyer_id'         => $userId,
        ':total_amount'     => $total,
        ':shipping_name'    => $shippingName,
        ':shipping_address' => $shippingAddress,
        ':shipping_postal'  => $shippingPostal,
        ':shipping_country' => $shippingCountry,
    ]);
    $orderId = (int)$pdo->lastInsertId();

    // Create order items + mark listings sold
    $itemStmt = $pdo->prepare("
        INSERT INTO order_items (order_id, listing_id, seller_id, price_paid, quantity)
        VALUES (:order_id, :listing_id, :seller_id, :price_paid, :quantity)
    ");
    $markSoldStmt = $pdo->prepare("UPDATE listings SET status = 'sold' WHERE listing_id = ?");

    foreach ($cartItems as $item) {
        $itemStmt->execute([
            ':order_id'   => $orderId,
            ':listing_id' => $item['listing_id'],
            ':seller_id'  => $item['seller_id'],
            ':price_paid' => $item['price'],
            ':quantity'   => $item['quantity'],
        ]);
        $markSoldStmt->execute([$item['listing_id']]);
    }

    // Clear cart
    $pdo->prepare("DELETE FROM cart_items WHERE user_id = ?")->execute([$userId]);

    $pdo->commit();
} catch (Exception $e) {
    $pdo->rollBack();
    error_log('Checkout error: ' . $e->getMessage());
    setFlash('An error occurred during checkout. Please try again.', 'danger');
    header('Location: /checkout.php');
    exit;
}

setFlash('Order placed successfully! Thank you for your purchase.', 'success');
header('Location: /order-confirmation.php?id=' . $orderId);
exit;
