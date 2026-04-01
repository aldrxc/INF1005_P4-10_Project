<?php
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/csrf.php';
require_once __DIR__ . '/../includes/sanitize.php';

startSession();
requireLogin();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: /dashboard.php');
    exit;
}

validateCsrfToken();

$orderId  = sanitizeInt($_POST['order_id']  ?? '');
$sellerId = sanitizeInt($_POST['seller_id'] ?? '');
$rating   = (int)($_POST['rating'] ?? 0);
$body     = trim($_POST['body'] ?? '');
$myId     = getCurrentUserId();

if (!$orderId || !$sellerId || $rating < 1 || $rating > 5) {
    setFlash('Invalid review submission.', 'danger');
    header('Location: /dashboard.php');
    exit;
}

$pdo = getDB();

// confirm order belongs to this buyer and includes this seller
$stmt = $pdo->prepare("
    SELECT 1 FROM orders o
    JOIN order_items oi ON oi.order_id = o.order_id
    WHERE o.order_id = ? AND o.buyer_id = ? AND oi.seller_id = ?
    LIMIT 1
");
$stmt->execute([$orderId, $myId, $sellerId]);
if (!$stmt->fetch()) {
    setFlash('You cannot review this order.', 'danger');
    header('Location: /dashboard.php');
    exit;
}

// one review per (order, seller) - ignore duplicate silently
$stmt = $pdo->prepare("
    INSERT IGNORE INTO reviews (order_id, reviewer_id, seller_id, rating, body)
    VALUES (?, ?, ?, ?, ?)
");
$stmt->execute([$orderId, $myId, $sellerId, $rating, $body ?: null]);

setFlash('Your review has been submitted. Thanks!', 'success');
header('Location: /order-confirmation.php?id=' . $orderId);
exit;
