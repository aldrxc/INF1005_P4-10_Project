<?php
require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/sanitize.php';

startSession();

$username = trim($_GET['user'] ?? '');
if ($username === '') {
    header('Location: /browse.php');
    exit;
}

$pdo  = getDB();
$stmt = $pdo->prepare("SELECT * FROM users WHERE username = ? AND is_active = 1 LIMIT 1");
$stmt->execute([$username]);
$profileUser = $stmt->fetch();

if (!$profileUser) {
    http_response_code(404);
    $pageTitle = 'User Not Found';
    require_once __DIR__ . '/includes/header.php';
    echo '<div class="container py-5 text-center"><h1 class="text-muted">User not found.</h1><a href="/browse.php" class="btn btn-accent mt-3">Browse Listings</a></div>';
    require_once __DIR__ . '/includes/footer.php';
    exit;
}

// Listing count
$countStmt = $pdo->prepare("SELECT COUNT(*) FROM listings WHERE seller_id = ? AND status = 'available'");
$countStmt->execute([$profileUser['user_id']]);
$listingCount = (int)$countStmt->fetchColumn();

// Pagination
$page    = max(1, (int)($_GET['page'] ?? 1));
$perPage = 12;
$offset  = ($page - 1) * $perPage;

$totalPages = (int)ceil($listingCount / $perPage);

$listStmt = $pdo->prepare("
    SELECT l.listing_id, l.title, l.price, l.artist_band, l.condition_type,
           c.name AS category_name, c.slug AS category_slug,
           g.name AS genre_name,
           u.username AS seller_username, u.display_name AS seller_display,
           (SELECT file_path FROM listing_images WHERE listing_id = l.listing_id AND is_primary=1 LIMIT 1) AS primary_image
    FROM listings l
    JOIN categories c ON l.category_id = c.category_id
    LEFT JOIN genres g ON l.genre_id = g.genre_id
    JOIN users u ON l.seller_id = u.user_id
    WHERE l.seller_id = ? AND l.status = 'available'
    ORDER BY l.created_at DESC
    LIMIT ? OFFSET ?
");
$listStmt->execute([$profileUser['user_id'], $perPage, $offset]);
$listings = $listStmt->fetchAll();

$pageTitle = clean($profileUser['display_name']) . "'s Profile";
require_once __DIR__ . '/includes/header.php';
?>

<div class="container py-4">

    <!-- Profile header -->
    <div class="profile-header card border-0 mb-4 p-4">
        <div class="d-flex align-items-start gap-4 flex-wrap">
            <!-- Avatar -->
            <div class="flex-shrink-0">
                <?php if ($profileUser['avatar_path']): ?>
                    <img src="/<?= clean($profileUser['avatar_path']) ?>"
                         alt="<?= clean($profileUser['display_name']) ?>'s avatar"
                         class="rounded-circle" width="90" height="90" style="object-fit:cover">
                <?php else: ?>
                    <div class="profile-avatar-placeholder rounded-circle d-flex align-items-center justify-content-center"
                         aria-hidden="true">
                        <?= strtoupper(mb_substr($profileUser['display_name'], 0, 1)) ?>
                    </div>
                <?php endif; ?>
            </div>

            <!-- Info -->
            <div class="flex-grow-1">
                <h1 class="h3 fw-bold mb-0"><?= clean($profileUser['display_name']) ?></h1>
                <div class="text-muted mb-1">@<?= clean($profileUser['username']) ?></div>

                <?php if ($profileUser['bio']): ?>
                    <p class="text-muted small mb-2"><?= clean($profileUser['bio']) ?></p>
                <?php endif; ?>

                <div class="d-flex flex-wrap gap-3 small text-muted">
                    <?php if ($profileUser['location']): ?>
                        <span><i class="bi bi-geo-alt me-1" aria-hidden="true"></i><?= clean($profileUser['location']) ?></span>
                    <?php endif; ?>
                    <span><i class="bi bi-calendar3 me-1" aria-hidden="true"></i>Joined <?= clean(date('M Y', strtotime($profileUser['joined_at']))) ?></span>
                    <span><i class="bi bi-tags me-1" aria-hidden="true"></i><?= $listingCount ?> active listing<?= $listingCount !== 1 ? 's' : '' ?></span>
                </div>
            </div>

            <!-- Own profile edit link -->
            <?php if (isLoggedIn() && getCurrentUserId() === (int)$profileUser['user_id']): ?>
                <a href="/dashboard.php" class="btn btn-sm btn-outline-secondary">
                    <i class="bi bi-speedometer2 me-1" aria-hidden="true"></i>My Dashboard
                </a>
            <?php endif; ?>
        </div>
    </div>

    <!-- Active listings -->
    <h2 class="h5 fw-semibold mb-3">
        <i class="bi bi-tags me-2 text-accent" aria-hidden="true"></i>Active Listings
    </h2>

    <?php if (empty($listings)): ?>
        <div class="text-center py-5 text-muted">
            <i class="bi bi-shop display-4" aria-hidden="true"></i>
            <p class="mt-3">No active listings at the moment.</p>
        </div>
    <?php else: ?>
        <div class="row row-cols-1 row-cols-sm-2 row-cols-md-3 row-cols-xl-4 g-4">
            <?php foreach ($listings as $listing): ?>
                <div class="col">
                    <?php include __DIR__ . '/includes/listing-card.php'; ?>
                </div>
            <?php endforeach; ?>
        </div>

        <!-- Pagination -->
        <?php if ($totalPages > 1): ?>
            <nav class="mt-4" aria-label="Profile listings pagination">
                <ul class="pagination justify-content-center">
                    <?php for ($p = 1; $p <= $totalPages; $p++): ?>
                        <li class="page-item <?= $p === $page ? 'active' : '' ?>">
                            <a class="page-link"
                               href="?user=<?= clean($username) ?>&page=<?= $p ?>"
                               <?= $p === $page ? 'aria-current="page"' : '' ?>>
                                <?= $p ?>
                            </a>
                        </li>
                    <?php endfor; ?>
                </ul>
            </nav>
        <?php endif; ?>
    <?php endif; ?>

</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
