<?php
require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/sanitize.php';

startSession();
requireLogin();

$pageTitle = 'My Messages';
$pdo = getDB();
$userId = getCurrentUserId();

// one row per (listing, other-party) conversation with latest message info
$stmt = $pdo->prepare("
    SELECT
        conv.listing_id,
        l.title AS listing_title,
        conv.other_user_id,
        u.display_name AS other_display,
        u.username AS other_username,
        conv.last_at,
        conv.unread_count,
        m_last.body AS last_body
    FROM (
        SELECT
            m.listing_id,
            CASE WHEN m.sender_id = ? THEN m.receiver_id ELSE m.sender_id END AS other_user_id,
            MAX(m.sent_at) AS last_at,
            SUM(CASE WHEN m.receiver_id = ? AND m.is_read = 0 THEN 1 ELSE 0 END) AS unread_count
        FROM messages m
        WHERE m.sender_id = ? OR m.receiver_id = ?
        GROUP BY m.listing_id, CASE WHEN m.sender_id = ? THEN m.receiver_id ELSE m.sender_id END
    ) conv
    JOIN listings l ON l.listing_id = conv.listing_id
    JOIN users u ON u.user_id = conv.other_user_id
    LEFT JOIN messages m_last ON m_last.listing_id = conv.listing_id
        AND m_last.sent_at = conv.last_at
        AND (m_last.sender_id = conv.other_user_id OR m_last.receiver_id = conv.other_user_id)
    ORDER BY conv.last_at DESC
");
$stmt->execute([$userId, $userId, $userId, $userId, $userId]);
$conversations = $stmt->fetchAll();

require_once __DIR__ . '/includes/header.php';
?>

<div class="container py-4">
    <h1 class="mb-4">Messages</h1>

    <?php if (empty($conversations)): ?>
        <div class="text-center py-5">
            <i class="bi bi-chat-dots fs-1 text-muted d-block mb-3" aria-hidden="true"></i>
            <p class="text-muted">No messages yet. Find a listing and message the seller to get started.</p>
            <a href="/browse.php" class="btn btn-accent mt-2">Browse Listings</a>
        </div>
    <?php else: ?>
        <div class="list-group">
            <?php foreach ($conversations as $conv): ?>
            <?php $unread = (int)$conv['unread_count']; ?>
            <a href="/conversation.php?listing_id=<?= (int)$conv['listing_id'] ?>&with=<?= (int)$conv['other_user_id'] ?>"
               class="list-group-item list-group-item-action d-flex justify-content-between align-items-start gap-3 py-3"
               style="background:var(--mv-surface-2); border-color:var(--mv-border); color:var(--mv-text);">
                <div class="flex-grow-1 overflow-hidden">
                    <div class="d-flex justify-content-between align-items-center mb-1">
                        <span class="fw-semibold"><?= clean($conv['other_display']) ?></span>
                        <span class="text-muted small ms-2 text-nowrap">
                            <?= clean(date('d M', strtotime($conv['last_at']))) ?>
                        </span>
                    </div>
                    <div class="text-muted small text-truncate mb-1">
                        <i class="bi bi-tag me-1" aria-hidden="true"></i><?= clean($conv['listing_title']) ?>
                    </div>
                    <div class="text-muted small text-truncate">
                        <?= clean(mb_strimwidth($conv['last_body'] ?? '', 0, 80, '…')) ?>
                    </div>
                </div>
                <?php if ($unread > 0): ?>
                    <span class="badge bg-accent rounded-pill align-self-center"><?= $unread ?></span>
                <?php endif; ?>
            </a>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
