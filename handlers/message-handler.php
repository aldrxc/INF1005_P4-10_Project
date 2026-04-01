<?php
// POST handler - send a message
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/csrf.php';
require_once __DIR__ . '/../includes/sanitize.php';

startSession();
requireLogin();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: /messages.php');
    exit;
}

validateCsrfToken();

$senderId    = getCurrentUserId();
$listingId   = sanitizeInt($_POST['listing_id'] ?? '');
$receiverId  = sanitizeInt($_POST['receiver_id'] ?? '');
$body        = trim($_POST['body'] ?? '');

// basic validation
if (!$listingId || !$receiverId || $body === '') {
    setFlash('Message cannot be empty.', 'danger');
    header("Location: /conversation.php?listing_id={$listingId}&with={$receiverId}");
    exit;
}

if (mb_strlen($body) > 1000) {
    setFlash('Message is too long (max 1000 characters).', 'danger');
    header("Location: /conversation.php?listing_id={$listingId}&with={$receiverId}");
    exit;
}

if ($receiverId === $senderId) {
    setFlash('You cannot message yourself.', 'danger');
    header("Location: /listing.php?id={$listingId}");
    exit;
}

$pdo = getDB();

// make sure listing exists
$stmt = $pdo->prepare("SELECT listing_id FROM listings WHERE listing_id = ? LIMIT 1");
$stmt->execute([$listingId]);
if (!$stmt->fetch()) {
    setFlash('Listing not found.', 'danger');
    header('Location: /browse.php');
    exit;
}

// make sure receiver exists
$stmt = $pdo->prepare("SELECT user_id FROM users WHERE user_id = ? LIMIT 1");
$stmt->execute([$receiverId]);
if (!$stmt->fetch()) {
    setFlash('Recipient not found.', 'danger');
    header("Location: /listing.php?id={$listingId}");
    exit;
}

$pdo->prepare("
    INSERT INTO messages (listing_id, sender_id, receiver_id, body)
    VALUES (?, ?, ?, ?)
")->execute([$listingId, $senderId, $receiverId, $body]);

header("Location: /conversation.php?listing_id={$listingId}&with={$receiverId}");
exit;
