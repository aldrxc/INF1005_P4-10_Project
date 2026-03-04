<?php
// AJAX POST: update listing status (available/reserved/sold) — owner only
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/csrf.php';
require_once __DIR__ . '/../includes/sanitize.php';

startSession();

header('Content-Type: application/json');

if (!isLoggedIn()) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Unauthorized.']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed.']);
    exit;
}

validateCsrfToken();

$listingId = sanitizeInt($_POST['listing_id'] ?? '');
$newStatus = sanitizeEnum($_POST['status'] ?? '', ['available', 'reserved', 'sold']);

if (!$listingId || !$newStatus) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Invalid parameters.']);
    exit;
}

$pdo  = getDB();
$stmt = $pdo->prepare("UPDATE listings SET status = ? WHERE listing_id = ? AND seller_id = ?");
$stmt->execute([$newStatus, $listingId, getCurrentUserId()]);

if ($stmt->rowCount() === 0) {
    http_response_code(404);
    echo json_encode(['success' => false, 'message' => 'Listing not found or permission denied.']);
    exit;
}

echo json_encode(['success' => true, 'status' => $newStatus]);
exit;
