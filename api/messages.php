<?php
// JSON polling endpoint — returns new messages after a given timestamp
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/sanitize.php';

startSession();

header('Content-Type: application/json');

if (!isLoggedIn()) {
    echo json_encode(['error' => 'not logged in']);
    exit;
}

$myId      = getCurrentUserId();
$listingId = sanitizeInt($_GET['listing_id'] ?? '');
$withId    = sanitizeInt($_GET['with'] ?? '');
$after     = trim($_GET['after'] ?? '1970-01-01 00:00:00');

// basic sanity check on the timestamp format
if (!preg_match('/^\d{4}-\d{2}-\d{2}[ T]\d{2}:\d{2}:\d{2}/', $after)) {
    $after = '1970-01-01 00:00:00';
}

if (!$listingId || !$withId) {
    echo json_encode([]);
    exit;
}

$pdo = getDB();

// mark incoming messages as read while we're here
$pdo->prepare("
    UPDATE messages SET is_read = 1
    WHERE listing_id = ? AND receiver_id = ? AND sender_id = ?
")->execute([$listingId, $myId, $withId]);

// fetch new messages since $after
$stmt = $pdo->prepare("
    SELECT message_id, sender_id, body, sent_at
    FROM messages
    WHERE listing_id = ?
      AND ((sender_id = ? AND receiver_id = ?) OR (sender_id = ? AND receiver_id = ?))
      AND sent_at > ?
    ORDER BY sent_at ASC
");
$stmt->execute([$listingId, $myId, $withId, $withId, $myId, $after]);
$rows = $stmt->fetchAll();

$result = array_map(fn($r) => [
    'message_id' => (int)$r['message_id'],
    'sender_id'  => (int)$r['sender_id'],
    'body'       => $r['body'],
    'sent_at'    => $r['sent_at'],
    'is_mine'    => (int)$r['sender_id'] === $myId,
], $rows);

echo json_encode($result);
exit;
