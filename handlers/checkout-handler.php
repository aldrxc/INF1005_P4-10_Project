<?php
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/csrf.php';
require_once __DIR__ . '/../includes/sanitize.php';

startSession();
requireLogin();

// Only allow POST requests
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: /checkout.php');
    exit();
}


$submittedToken = $_POST['csrf_token'] ?? '';
$sessionToken = $_SESSION['csrf_token'] ?? '';

if (empty($submittedToken) || !hash_equals($sessionToken, $submittedToken)) {
    $_SESSION['checkout_errors']['general'] = "Security token mismatch. Please try submitting again.";
    header('Location: /checkout.php');
    exit();
}

$userId = getCurrentUserId();
$pdo = getDB();

// sanitise inputs
$shippingName = clean($_POST['shipping_name'] ?? '');
$shippingBlock = clean($_POST['shipping_block'] ?? '');
$shippingStreet = clean($_POST['shipping_street'] ?? '');
$shippingUnit = clean($_POST['shipping_unit'] ?? '');
$shippingPostal = clean($_POST['shipping_postal'] ?? '');
$shippingCountry = clean($_POST['shipping_country'] ?? 'Singapore');


$_SESSION['checkout_old'] = [
    'shipping_name' => $shippingName,
    'shipping_block' => $shippingBlock,
    'shipping_street' => $shippingStreet,
    'shipping_unit' => $shippingUnit,
    'shipping_postal' => $shippingPostal,
    'shipping_country' => $shippingCountry
];


$errors = [];

if (empty($shippingName)) {
    $errors['shipping_name'] = "Full name is required.";
}

if (empty($shippingBlock)) {
    $errors['shipping_block'] = "Block or House number is required.";
}

if (empty($shippingStreet)) {
    $errors['shipping_street'] = "Street name is required.";
}

if (empty($shippingUnit)) {
    $errors['shipping_unit'] = "Unit number is required.";
}

// Strict Singapore Postal Code Check (6 digits)
if (empty($shippingPostal)) {
    $errors['shipping_postal'] = "Postal code is required.";
} elseif (!preg_match('/^[0-9]{6}$/', $shippingPostal)) {
    $errors['shipping_postal'] = "Please enter a valid 6-digit Singapore postal code.";
}

// Strict Country Check
if (strtolower(trim($shippingCountry)) !== 'singapore') {
    $errors['shipping_country'] = "Sorry, we currently only ship within Singapore.";
}

// If there are errors, send them back to the checkout page
if (count($errors) > 0) {
    $_SESSION['checkout_errors'] = $errors;
    header('Location: /checkout.php');
    exit();
}

// Combine the split fields into one string for the database
$shippingAddress = "Blk " . $shippingBlock . " " . $shippingStreet . ", " . $shippingUnit;

try {
    
    $pdo->beginTransaction();


    $stmt = $pdo->prepare("
        SELECT ci.quantity, l.listing_id, l.price, l.status, l.seller_id
        FROM cart_items ci
        JOIN listings l ON ci.listing_id = l.listing_id
        WHERE ci.user_id = ?
    ");
    $stmt->execute([$userId]);
    $cartItems = $stmt->fetchAll();

    if (count($cartItems) == 0) {
        throw new Exception("Your cart is empty.");
    }

    $totalAmount = 0;
    foreach ($cartItems as $item) {
        if ($item['status'] != 'available') {
            throw new Exception("One or more items in your cart are no longer available.");
        }
        $totalAmount = $totalAmount + ($item['price'] * $item['quantity']);
    }


    $insertOrderSql = "INSERT INTO orders (buyer_id, total_amount, shipping_name, shipping_address, shipping_postal, shipping_country) 
                       VALUES (?, ?, ?, ?, ?, ?)";
    $orderStmt = $pdo->prepare($insertOrderSql);
    $orderStmt->execute([
        $userId, 
        $totalAmount, 
        $shippingName, 
        $shippingAddress, 
        $shippingPostal, 
        $shippingCountry
    ]);
    
    // Get the ID of the order we just created
    $orderId = $pdo->lastInsertId();

    // 6. Create the Order Items & Update Listing Status 
    $insertItemSql = "INSERT INTO order_items (order_id, listing_id, seller_id, quantity, price_paid) VALUES (?, ?, ?, ?, ?)";
    $itemStmt = $pdo->prepare($insertItemSql);
    
    $updateListingSql = "UPDATE listings SET status = 'sold' WHERE listing_id = ?";
    $updateListingStmt = $pdo->prepare($updateListingSql);

    foreach ($cartItems as $item) {
        $itemStmt->execute([
            $orderId,
            $item['listing_id'],
            $item['seller_id'],
            $item['quantity'],
            $item['price'] 
        ]);
        
        // Mark the item as sold
        $updateListingStmt->execute([$item['listing_id']]);
    }


    $clearCartSql = "DELETE FROM cart_items WHERE user_id = ?";
    $clearStmt = $pdo->prepare($clearCartSql);
    $clearStmt->execute([$userId]);


    $pdo->commit();


    unset($_SESSION['checkout_old']);

    // 8. Redirect to the confirmation page
    header('Location: /order-confirmation.php?id=' . $orderId);
    exit();

} catch (Exception $e) {
    // Undo all database changes if an error occurs
    $pdo->rollBack();
    
    $_SESSION['checkout_errors']['general'] = "Checkout failed: " . $e->getMessage();
    header('Location: /checkout.php');
    exit();
}
