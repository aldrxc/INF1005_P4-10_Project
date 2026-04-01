<?php
// POST handler - add/update/remove cart items (also handles AJAX)
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/csrf.php';
require_once __DIR__ . '/../includes/sanitize.php';

startSession();

$isAjax = !empty($_SERVER['HTTP_X_REQUESTED_WITH']) &&
    strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest';

function jsonResponse(array $data, int $code = 200): void
{
    http_response_code($code);
    header('Content-Type: application/json');
    echo json_encode($data);
    exit;
}

if (!isLoggedIn()) {
    if ($isAjax) jsonResponse(['success' => false, 'message' => 'Please log in.'], 401);
    header('Location: /login.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    if ($isAjax) jsonResponse(['success' => false, 'message' => 'Method not allowed.'], 405);
    header('Location: /cart.php');
    exit;
}

validateCsrfToken();

$cartAction = trim($_POST['cart_action'] ?? '');
$pdo        = getDB();
$userId     = getCurrentUserId();

// add to cart
if ($cartAction === 'add') {
    $listingId = sanitizeInt($_POST['listing_id'] ?? '');
    if (!$listingId) {
        if ($isAjax) jsonResponse(['success' => false, 'message' => 'Invalid listing.'], 400);
        header('Location: /browse.php');
        exit;
    }

    $stmt = $pdo->prepare("SELECT listing_id, status, seller_id FROM listings WHERE listing_id = ? LIMIT 1");
    $stmt->execute([$listingId]);
    $listing = $stmt->fetch();

    if (!$listing || $listing['status'] !== 'available') {
        if ($isAjax) jsonResponse(['success' => false, 'message' => 'This item is no longer available.'], 400);
        setFlash('This item is no longer available.', 'warning');
        header('Location: /listing.php?id=' . $listingId);
        exit;
    }
    if ((int)$listing['seller_id'] === $userId) {
        if ($isAjax) jsonResponse(['success' => false, 'message' => 'You cannot buy your own listing.'], 400);
        setFlash('You cannot add your own listing to your cart.', 'warning');
        header('Location: /listing.php?id=' . $listingId);
        exit;
    }

    $stmt = $pdo->prepare("
        INSERT INTO cart_items (user_id, listing_id, quantity)
        VALUES (?, ?, 1)
        ON DUPLICATE KEY UPDATE quantity = quantity
    ");
    $stmt->execute([$userId, $listingId]);

    $countStmt = $pdo->prepare("SELECT COALESCE(SUM(quantity),0) FROM cart_items WHERE user_id = ?");
    $countStmt->execute([$userId]);
    $cartCount = (int)$countStmt->fetchColumn();

    if ($isAjax) jsonResponse(['success' => true, 'message' => 'Added to cart!', 'cartCount' => $cartCount]);
    setFlash('Item added to cart!', 'success');
    header('Location: /listing.php?id=' . $listingId);
    exit;
}

// update quantity
if ($cartAction === 'update') {
    $cartItemId = sanitizeInt($_POST['cart_item_id'] ?? '');
    $newQty     = max(1, (int)($_POST['quantity'] ?? 1));

    if (!$cartItemId) {
        if ($isAjax) jsonResponse(['success' => false, 'message' => 'Invalid cart item.'], 400);
        header('Location: /cart.php');
        exit;
    }

    $stmt = $pdo->prepare("
        SELECT ci.cart_item_id, l.price
        FROM cart_items ci
        JOIN listings l ON ci.listing_id = l.listing_id
        WHERE ci.cart_item_id = ? AND ci.user_id = ?
        LIMIT 1
    ");
    $stmt->execute([$cartItemId, $userId]);
    $item = $stmt->fetch();

    if (!$item) {
        if ($isAjax) jsonResponse(['success' => false, 'message' => 'Item not found.'], 404);
        header('Location: /cart.php');
        exit;
    }

    $pdo->prepare("UPDATE cart_items SET quantity = ? WHERE cart_item_id = ? AND user_id = ?")
        ->execute([$newQty, $cartItemId, $userId]);

    $lineTotal = (float)$item['price'] * $newQty;

    $totalStmt = $pdo->prepare("
        SELECT COALESCE(SUM(l.price * ci.quantity), 0)
        FROM cart_items ci
        JOIN listings l ON ci.listing_id = l.listing_id
        WHERE ci.user_id = ? AND l.status = 'available'
    ");
    $totalStmt->execute([$userId]);
    $cartTotal = (float)$totalStmt->fetchColumn();

    $countStmt = $pdo->prepare("SELECT COALESCE(SUM(quantity),0) FROM cart_items WHERE user_id = ?");
    $countStmt->execute([$userId]);
    $cartCount = (int)$countStmt->fetchColumn();

    if ($isAjax) jsonResponse([
        'success'   => true,
        'lineTotal' => 'S$' . number_format($lineTotal, 2),
        'cartTotal' => 'S$' . number_format($cartTotal, 2),
        'cartCount' => $cartCount,
    ]);
    header('Location: /cart.php');
    exit;
}

// remove from cart
if ($cartAction === 'remove') {
    $cartItemId = sanitizeInt($_POST['cart_item_id'] ?? '');

    if (!$cartItemId) {
        if ($isAjax) jsonResponse(['success' => false, 'message' => 'Invalid cart item.'], 400);
        header('Location: /cart.php');
        exit;
    }

    $pdo->prepare("DELETE FROM cart_items WHERE cart_item_id = ? AND user_id = ?")
        ->execute([$cartItemId, $userId]);

    $totalStmt = $pdo->prepare("
        SELECT COALESCE(SUM(l.price * ci.quantity), 0)
        FROM cart_items ci
        JOIN listings l ON ci.listing_id = l.listing_id
        WHERE ci.user_id = ? AND l.status = 'available'
    ");
    $totalStmt->execute([$userId]);
    $cartTotal = (float)$totalStmt->fetchColumn();

    $countStmt = $pdo->prepare("SELECT COALESCE(SUM(quantity),0) FROM cart_items WHERE user_id = ?");
    $countStmt->execute([$userId]);
    $cartCount = (int)$countStmt->fetchColumn();

    if ($isAjax) jsonResponse([
        'success'   => true,
        'cartTotal' => 'S$' . number_format($cartTotal, 2),
        'cartCount' => $cartCount,
    ]);
    setFlash('Item removed from cart.', 'info');
    header('Location: /cart.php');
    exit;
}

if ($isAjax) jsonResponse(['success' => false, 'message' => 'Unknown action.'], 400);
header('Location: /cart.php');
exit;
