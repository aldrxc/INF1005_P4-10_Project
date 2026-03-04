<?php
// POST: remove a single image from a listing (owner verified)
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

$imageId   = sanitizeInt($_POST['image_id']   ?? '');
$listingId = sanitizeInt($_POST['listing_id'] ?? '');

if (!$imageId || !$listingId) {
    setFlash('Invalid request.', 'danger');
    header('Location: /dashboard.php');
    exit;
}

$pdo = getDB();

// Verify ownership via listing
$stmt = $pdo->prepare("
    SELECT li.image_id, li.file_path
    FROM listing_images li
    JOIN listings l ON li.listing_id = l.listing_id
    WHERE li.image_id = ? AND li.listing_id = ? AND l.seller_id = ?
    LIMIT 1
");
$stmt->execute([$imageId, $listingId, getCurrentUserId()]);
$img = $stmt->fetch();

if (!$img) {
    setFlash('Image not found or permission denied.', 'danger');
    header('Location: /edit-listing.php?id=' . $listingId);
    exit;
}

// Delete record
$pdo->prepare("DELETE FROM listing_images WHERE image_id = ?")->execute([$imageId]);

// Delete file (best effort)
$filePath = __DIR__ . '/../../' . $img['file_path'];
if (file_exists($filePath)) {
    @unlink($filePath);
}

header('Location: /edit-listing.php?id=' . $listingId);
exit;
