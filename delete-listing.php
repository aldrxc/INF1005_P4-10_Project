<?php
// POST-only: delete a listing (owner + CSRF verified)
require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/csrf.php';
require_once __DIR__ . '/includes/sanitize.php';

startSession();
requireLogin();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: /dashboard.php');
    exit;
}

validateCsrfToken();

$listingId = sanitizeInt($_POST['listing_id'] ?? '');
if (!$listingId) {
    setFlash('Invalid request.', 'danger');
    header('Location: /dashboard.php');
    exit;
}

$pdo  = getDB();
$stmt = $pdo->prepare("SELECT listing_id, seller_id FROM listings WHERE listing_id = ? LIMIT 1");
$stmt->execute([$listingId]);
$listing = $stmt->fetch();

if (!$listing || (int)$listing['seller_id'] !== getCurrentUserId()) {
    setFlash('Listing not found or permission denied.', 'danger');
    header('Location: /dashboard.php');
    exit;
}

// Delete (CASCADE will remove images + ticket_details + cart_items)
$pdo->prepare("DELETE FROM listings WHERE listing_id = ?")->execute([$listingId]);

setFlash('Your listing has been deleted.', 'success');
header('Location: /dashboard.php');
exit;
