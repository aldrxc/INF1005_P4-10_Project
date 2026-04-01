<?php
require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/csrf.php';
require_once __DIR__ . '/includes/sanitize.php';

startSession();
requireLogin();

$myId      = getCurrentUserId();
$listingId = sanitizeInt($_GET['listing_id'] ?? '');
$withId    = sanitizeInt($_GET['with'] ?? '');

if (!$listingId || !$withId || $withId === $myId) {
    header('Location: /messages.php');
    exit;
}

$pdo = getDB();

// grab listing info (thumbnail + title)
$stmt = $pdo->prepare("
    SELECT l.listing_id, l.title, l.seller_id,
           (SELECT file_path FROM listing_images WHERE listing_id = l.listing_id AND is_primary=1 LIMIT 1) AS thumb
    FROM listings l
    WHERE l.listing_id = ?
    LIMIT 1
");
$stmt->execute([$listingId]);
$listing = $stmt->fetch();

if (!$listing) {
    setFlash('Listing not found.', 'danger');
    header('Location: /messages.php');
    exit;
}

// grab other user
$stmt = $pdo->prepare("SELECT user_id, display_name, username FROM users WHERE user_id = ? LIMIT 1");
$stmt->execute([$withId]);
$other = $stmt->fetch();

if (!$other) {
    setFlash('User not found.', 'danger');
    header('Location: /messages.php');
    exit;
}

// mark incoming messages in this conversation as read
$pdo->prepare("
    UPDATE messages SET is_read = 1
    WHERE listing_id = ? AND receiver_id = ? AND sender_id = ?
")->execute([$listingId, $myId, $withId]);

// fetch all messages in this conversation
$stmt = $pdo->prepare("
    SELECT message_id, sender_id, body, sent_at
    FROM messages
    WHERE listing_id = ?
      AND ((sender_id = ? AND receiver_id = ?) OR (sender_id = ? AND receiver_id = ?))
    ORDER BY sent_at ASC
");
$stmt->execute([$listingId, $myId, $withId, $withId, $myId]);
$messages = $stmt->fetchAll();

$pageTitle = 'Chat with ' . htmlspecialchars($other['display_name'], ENT_QUOTES, 'UTF-8');
$lastSentAt = !empty($messages) ? end($messages)['sent_at'] : '1970-01-01 00:00:00';
generateCsrfToken();
require_once __DIR__ . '/includes/header.php';
?>

<div class="container py-4" style="max-width:720px">

    <!-- listing context bar -->
    <div class="card mb-3 p-3 d-flex flex-row align-items-center gap-3">
        <?php if ($listing['thumb']): ?>
            <img src="/<?= clean($listing['thumb']) ?>" alt="" width="52" height="52"
                class="rounded" style="object-fit:cover; flex-shrink:0">
        <?php endif; ?>
        <div class="flex-grow-1 overflow-hidden">
            <div class="text-muted small">Conversation about</div>
            <a href="/listing.php?id=<?= (int)$listingId ?>" class="fw-semibold text-reset text-decoration-none text-truncate d-block">
                <?= clean($listing['title']) ?>
            </a>
        </div>
        <a href="/messages.php" class="btn btn-sm btn-outline-secondary text-nowrap">
            <i class="bi bi-arrow-left me-1" aria-hidden="true"></i>Inbox
        </a>
    </div>

    <h1 class="visually-hidden"><?= clean($pageTitle) ?></h1>
    <h2 class="h5 mb-3">
        <i class="bi bi-chat-dots me-2 text-accent" aria-hidden="true"></i>
        <?= clean($other['display_name']) ?>
    </h2>

    <!-- chat window -->
    <div class="chat-window mb-3" id="chatWindow">
        <?php if (empty($messages)): ?>
            <p class="text-muted text-center small my-auto">No messages yet. Say hello!</p>
        <?php endif; ?>
        <?php foreach ($messages as $msg): ?>
            <?php $mine = (int)$msg['sender_id'] === $myId; ?>
            <div class="d-flex flex-column <?= $mine ? 'align-items-end' : 'align-items-start' ?>">
                <div class="chat-bubble <?= $mine ? 'mine' : 'theirs' ?>">
                    <?= clean($msg['body']) ?>
                </div>
                <div class="message-time <?= $mine ? 'text-end' : '' ?>">
                    <?= clean(date('d M, H:i', strtotime($msg['sent_at']))) ?>
                </div>
            </div>
        <?php endforeach; ?>
    </div>

    <!-- send form -->
    <form method="POST" action="/handlers/message-handler.php" id="msgForm">
        <?= getCsrfField() ?>
        <input type="hidden" name="listing_id" value="<?= (int)$listingId ?>">
        <input type="hidden" name="receiver_id" value="<?= (int)$withId ?>">
        <div class="input-group">
            <textarea class="form-control" name="body" id="msgBody"
                rows="2" maxlength="1000"
                placeholder="Write a message…"
                aria-label="Message body" required></textarea>
            <button type="submit" class="btn btn-accent">
                <i class="bi bi-send" aria-hidden="true"></i>
            </button>
        </div>
        <div class="text-muted small mt-1 text-end">
            <span id="charCount">0</span>/1000
        </div>
    </form>
</div>

<script>
    // scroll chat to bottom on load
    const chatWindow = document.getElementById('chatWindow');

    function scrollBottom() {
        chatWindow.scrollTop = chatWindow.scrollHeight;
    }
    scrollBottom();

    // char counter
    const body = document.getElementById('msgBody');
    const counter = document.getElementById('charCount');
    body.addEventListener('input', () => counter.textContent = body.value.length);

    // submit on ctrl+enter / cmd+enter
    body.addEventListener('keydown', e => {
        if ((e.ctrlKey || e.metaKey) && e.key === 'Enter') {
            document.getElementById('msgForm').submit();
        }
    });

    // poll for new messages every 5 seconds
    let lastAt = <?= json_encode($lastSentAt) ?>;
    const myId = <?= $myId ?>;

    async function pollMessages() {
        try {
            const url = `/api/messages.php?listing_id=<?= (int)$listingId ?>&with=<?= (int)$withId ?>&after=` + encodeURIComponent(lastAt);
            const res = await fetch(url);
            const data = await res.json();
            if (!Array.isArray(data) || data.length === 0) return;

            data.forEach(msg => {
                const wrap = document.createElement('div');
                wrap.className = 'd-flex flex-column ' + (msg.is_mine ? 'align-items-end' : 'align-items-start');
                const bubble = document.createElement('div');
                bubble.className = 'chat-bubble ' + (msg.is_mine ? 'mine' : 'theirs');
                bubble.textContent = msg.body;
                const time = document.createElement('div');
                time.className = 'message-time' + (msg.is_mine ? ' text-end' : '');
                time.textContent = msg.sent_at;
                wrap.appendChild(bubble);
                wrap.appendChild(time);
                chatWindow.appendChild(wrap);
                lastAt = msg.sent_at;
            });
            scrollBottom();
        } catch (_) {}
    }
    setInterval(pollMessages, 15000);
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>