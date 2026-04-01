<?php
// POST handler - admin delete any listing (no ownership check)
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/csrf.php';
require_once __DIR__ . '/../includes/sanitize.php';

startSession();
requireAdmin();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: /admin/listings.php');
    exit;
}

validateCsrfToken();

$listingId = sanitizeInt($_POST['listing_id'] ?? '');
if (!$listingId) {
    setFlash('Invalid listing ID.', 'danger');
    header('Location: /admin/listings.php');
    exit;
}

$pdo = getDB();
$stmt = $pdo->prepare("SELECT listing_id FROM listings WHERE listing_id = ? LIMIT 1");
$stmt->execute([$listingId]);
if (!$stmt->fetch()) {
    setFlash('Listing not found.', 'danger');
    header('Location: /admin/listings.php');
    exit;
}

// cascade handles images, ticket_details, cart_items, messages
$pdo->prepare("DELETE FROM listings WHERE listing_id = ?")->execute([$listingId]);

setFlash('Listing #' . $listingId . ' deleted.', 'success');
header('Location: /admin/listings.php');
exit;
