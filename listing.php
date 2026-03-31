<?php
require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/csrf.php';
require_once __DIR__ . '/includes/sanitize.php';

startSession();

$listingId = sanitizeInt($_GET['id'] ?? '');
if (!$listingId) {
    header('Location: /browse.php');
    exit;
}

$pdo = getDB();

$stmt = $pdo->prepare("
    SELECT l.*, c.name AS category_name, c.slug AS category_slug,
           g.name AS genre_name,
           u.username AS seller_username, u.display_name AS seller_display,
           u.bio AS seller_bio, u.avatar_path AS seller_avatar,
           u.joined_at AS seller_joined
    FROM listings l
    JOIN categories c ON l.category_id = c.category_id
    LEFT JOIN genres g ON l.genre_id = g.genre_id
    JOIN users u ON l.seller_id = u.user_id
    WHERE l.listing_id = ?
    LIMIT 1
");
$stmt->execute([$listingId]);
$listing = $stmt->fetch();

if (!$listing) {
    http_response_code(404);
    $pageTitle = 'Listing Not Found';
    require_once __DIR__ . '/includes/header.php';
    echo '<div class="container py-5 text-center"><h1 class="text-muted">Listing not found.</h1><a href="/browse.php" class="btn btn-accent mt-3">Browse Listings</a></div>';
    require_once __DIR__ . '/includes/footer.php';
    exit;
}

$isOwner = isLoggedIn() && (int)$listing['seller_id'] === getCurrentUserId();

// Increment view counter
if (!$isOwner) {
    $pdo->prepare("UPDATE listings SET views = views + 1 WHERE listing_id = ?")->execute([$listingId]);
}

$imgStmt = $pdo->prepare("
    SELECT image_id, file_path, is_primary
    FROM listing_images
    WHERE listing_id = ?
    ORDER BY is_primary DESC, sort_order ASC
");
$imgStmt->execute([$listingId]);
$images = $imgStmt->fetchAll();

// Ticket details (if applicable)
$ticketDetails = null;
if ($listing['category_slug'] === 'event-tickets') {
    $tStmt = $pdo->prepare("SELECT * FROM ticket_details WHERE listing_id = ? LIMIT 1");
    $tStmt->execute([$listingId]);
    $ticketDetails = $tStmt->fetch();
}

// related listings
$relStmt = $pdo->prepare("
    SELECT l.listing_id, l.title, l.price, l.artist_band, l.condition_type,
           c.name AS category_name, c.slug AS category_slug,
           g.name AS genre_name,
           u.username AS seller_username, u.display_name AS seller_display,
           (SELECT file_path FROM listing_images
            WHERE listing_id = l.listing_id AND is_primary = 1
            LIMIT 1) AS primary_image
    FROM listings l
    JOIN categories c ON l.category_id = c.category_id
    LEFT JOIN genres g ON l.genre_id = g.genre_id
    JOIN users u ON l.seller_id = u.user_id
    WHERE l.category_id = ? AND l.listing_id != ? AND l.status = 'available'
    ORDER BY l.created_at DESC
    LIMIT 4
");
$relStmt->execute([$listing['category_id'], $listingId]);
$related = $relStmt->fetchAll();

$ratingStmt = $pdo->prepare("SELECT ROUND(AVG(rating),1) AS avg_rating, COUNT(*) AS review_count FROM reviews WHERE seller_id = ?");
$ratingStmt->execute([$listing['seller_id']]);
$sellerRating = $ratingStmt->fetch();

$inCart = false;
if (isLoggedIn()) {
    $cStmt = $pdo->prepare("SELECT 1 FROM cart_items WHERE user_id = ? AND listing_id = ? LIMIT 1");
    $cStmt->execute([getCurrentUserId(), $listingId]);
    $inCart = (bool)$cStmt->fetch();
}

$pageTitle = $listing['title'];
generateCsrfToken();
require_once __DIR__ . '/includes/header.php';

$condLabels = [
    'new'      => ['New',      'success'],
    'like_new' => ['Like New', 'success'],
    'good'     => ['Good',     'info'],
    'fair'     => ['Fair',     'warning'],
    'poor'     => ['Poor',     'danger'],
];
?>

<div class="container py-4">

    <!-- Breadcrumb -->
    <nav aria-label="Breadcrumb" class="mb-3">
        <ol class="breadcrumb breadcrumb-dark small">
            <li class="breadcrumb-item"><a href="/index.php">Home</a></li>
            <li class="breadcrumb-item"><a href="/browse.php">Browse</a></li>
            <li class="breadcrumb-item">
                <a href="/browse.php?category=<?= clean($listing['category_slug']) ?>">
                    <?= clean($listing['category_name']) ?>
                </a>
            </li>
            <li class="breadcrumb-item active" aria-current="page"><?= clean(mb_strimwidth($listing['title'], 0, 40, '…')) ?></li>
        </ol>
    </nav>

    <div class="row g-4">

        <div class="col-lg-6">
            <div class="listing-gallery" id="listingGallery">
                <div class="gallery-main mb-2">
                    <?php $mainImg = !empty($images) ? $images[0]['file_path'] : null; ?>
                    <img src="<?= $mainImg ? '/' . clean($mainImg) : '/assets/images/placeholder.php' ?>"
                        alt="<?= clean($listing['title']) ?>"
                        class="gallery-main-img img-fluid rounded"
                        id="galleryMainImg">
                </div>

                <!-- Thumbnails -->
                <?php if (count($images) > 1): ?>
                    <div class="gallery-thumbs d-flex gap-2 flex-wrap" role="list" aria-label="Additional images">
                        <?php foreach ($images as $i => $img): ?>
                            <button type="button"
                                class="gallery-thumb <?= $i === 0 ? 'active' : '' ?>"
                                data-src="/<?= clean($img['file_path']) ?>"
                                aria-label="View image <?= $i + 1 ?>"
                                role="listitem">
                                <img src="/<?= clean($img['file_path']) ?>"
                                    alt="Image <?= $i + 1 ?> of <?= clean($listing['title']) ?>"
                                    loading="lazy">
                            </button>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <div class="col-lg-6">
            <div class="listing-details">

                <?php if ($listing['status'] !== 'available'): ?>
                    <span class="badge bg-<?= $listing['status'] === 'reserved' ? 'warning' : 'danger' ?> mb-2">
                        <?= ucfirst($listing['status']) ?>
                    </span>
                <?php endif; ?>

                <h1 class="listing-title h3 fw-bold mb-1"><?= clean($listing['title']) ?></h1>

                <div class="listing-price my-3">S$<?= number_format((float)$listing['price'], 2) ?></div>

                <div class="d-flex flex-wrap gap-2 mb-3">
                    <span class="badge bg-secondary-subtle text-secondary-emphasis">
                        <i class="bi bi-folder me-1" aria-hidden="true"></i><?= clean($listing['category_name']) ?>
                    </span>
                    <?php if ($listing['genre_name']): ?>
                        <span class="badge bg-dark-subtle text-body-secondary">
                            <i class="bi bi-music-note me-1" aria-hidden="true"></i><?= clean($listing['genre_name']) ?>
                        </span>
                    <?php endif; ?>
                    <?php if ($listing['artist_band']): ?>
                        <span class="badge bg-accent-subtle text-accent-emphasis">
                            <i class="bi bi-person-music me-1" aria-hidden="true"></i><?= clean($listing['artist_band']) ?>
                        </span>
                    <?php endif; ?>
                    <?php if ($listing['condition_type'] && $listing['category_slug'] !== 'event-tickets'): ?>
                        <?php [$condText, $condColor] = $condLabels[$listing['condition_type']] ?? ['Unknown', 'secondary']; ?>
                        <span class="badge bg-<?= $condColor ?>-subtle text-<?= $condColor ?>-emphasis">
                            <?= $condText ?> condition
                        </span>
                    <?php endif; ?>
                    <?php if ($listing['size']): ?>
                        <span class="badge bg-dark-subtle text-body-secondary">Size: <?= clean($listing['size']) ?></span>
                    <?php endif; ?>
                </div>

                <!-- Ticket details block -->
                <?php if ($ticketDetails): ?>
                    <div class="ticket-details-card card mb-3 p-3">
                        <h2 class="h6 fw-bold mb-2">
                            <i class="bi bi-ticket-perforated text-accent me-2" aria-hidden="true"></i>Ticket Details
                        </h2>
                        <dl class="row small mb-0">
                            <dt class="col-5 text-muted">Event</dt>
                            <dd class="col-7 text-white"><?= clean($ticketDetails['event_name']) ?></dd>
                            <dt class="col-5 text-muted">Date</dt>
                            <dd class="col-7 text-white" id="eventDate" data-date="<?= clean($ticketDetails['event_date']) ?>">
                                <?= clean(date('D, d M Y', strtotime($ticketDetails['event_date']))) ?>
                            </dd>
                            <dt class="col-5 text-muted">Venue</dt>
                            <dd class="col-7 text-white"><?= clean($ticketDetails['venue_name']) ?>, <?= clean($ticketDetails['venue_city']) ?></dd>
                            <?php if ($ticketDetails['seat_section']): ?>
                                <dt class="col-5 text-muted">Section</dt>
                                <dd class="col-7 text-white"><?= clean($ticketDetails['seat_section']) ?></dd>
                            <?php endif; ?>
                            <?php if ($ticketDetails['seat_row']): ?>
                                <dt class="col-5 text-muted">Row</dt>
                                <dd class="col-7 text-white"><?= clean($ticketDetails['seat_row']) ?></dd>
                            <?php endif; ?>
                            <?php if ($ticketDetails['seat_number']): ?>
                                <dt class="col-5 text-muted">Seat(s)</dt>
                                <dd class="col-7 text-white"><?= clean($ticketDetails['seat_number']) ?></dd>
                            <?php endif; ?>
                            <dt class="col-5 text-muted">Quantity</dt>
                            <dd class="col-7 text-white"><?= (int)$ticketDetails['quantity'] ?></dd>
                            <dt class="col-5 text-muted">Type</dt>
                            <dd class="col-7 text-white"><?= $ticketDetails['is_e_ticket'] ? 'E-Ticket' : 'Physical Ticket' ?></dd>
                        </dl>
                        <!-- Countdown -->
                        <div id="ticketCountdown" class="mt-2 text-muted small"></div>
                    </div>
                <?php endif; ?>

                <div class="listing-description mb-3">
                    <h2 class="h6 fw-bold mb-1">Description</h2>
                    <p class="text-muted" style="white-space:pre-line"><?= clean($listing['description']) ?></p>
                </div>

                <p class="text-muted small mb-3">
                    <i class="bi bi-eye me-1" aria-hidden="true"></i>
                    <?= number_format((int)$listing['views']) ?> view<?= $listing['views'] !== '1' ? 's' : '' ?>
                    &nbsp;&bull;&nbsp;
                    Listed <?= clean(date('d M Y', strtotime($listing['created_at']))) ?>
                </p>

                <div class="d-flex flex-wrap gap-2 mb-4">
                    <?php if ($isOwner): ?>
                        <a href="/edit-listing.php?id=<?= (int)$listingId ?>" class="btn btn-outline-secondary">
                            <i class="bi bi-pencil me-1" aria-hidden="true"></i>Edit Listing
                        </a>
                        <form method="POST" action="/delete-listing.php" class="d-inline"
                            onsubmit="return confirm('Delete this listing? This cannot be undone.')">
                            <?= getCsrfField() ?>
                            <input type="hidden" name="listing_id" value="<?= (int)$listingId ?>">
                            <button type="submit" class="btn btn-outline-danger">
                                <i class="bi bi-trash me-1" aria-hidden="true"></i>Delete
                            </button>
                        </form>
                    <?php elseif ($listing['status'] === 'available'): ?>
                        <?php if (isLoggedIn()): ?>
                            <button type="button" id="addToCartBtn"
                                class="btn btn-accent px-4"
                                data-listing-id="<?= (int)$listingId ?>"
                                data-csrf="<?= clean($_SESSION['csrf_token']) ?>"
                                <?= $inCart ? 'disabled' : '' ?>>
                                <i class="bi bi-cart-plus me-2" aria-hidden="true"></i>
                                <?= $inCart ? 'In Cart' : 'Add to Cart' ?>
                            </button>
                        <?php else: ?>
                            <a href="/login.php?redirect=<?= urlencode('/listing.php?id=' . $listingId) ?>"
                                class="btn btn-accent px-4">
                                <i class="bi bi-cart-plus me-2" aria-hidden="true"></i>Log In to Buy
                            </a>
                        <?php endif; ?>
                    <?php else: ?>
                        <button class="btn btn-secondary" disabled aria-disabled="true">
                            <?= $listing['status'] === 'reserved' ? 'Reserved' : 'Sold' ?>
                        </button>
                    <?php endif; ?>

                    <?php if (!$isOwner && isLoggedIn()): ?>
                        <a href="/conversation.php?listing_id=<?= (int)$listingId ?>&with=<?= (int)$listing['seller_id'] ?>"
                           class="btn btn-outline-secondary">
                            <i class="bi bi-chat-dots me-1" aria-hidden="true"></i>Message Seller
                        </a>
                    <?php endif; ?>

                    <button type="button" id="shareBtn" class="btn btn-outline-secondary"
                        aria-label="Copy listing link to clipboard">
                        <i class="bi bi-share me-1" aria-hidden="true"></i>Share
                    </button>
                </div>

                <!-- Seller card -->
                <div class="seller-card card p-3">
                    <h2 class="h6 fw-bold mb-2">
                        <i class="bi bi-person-circle me-2 text-accent" aria-hidden="true"></i>Seller
                    </h2>
                    <div class="d-flex align-items-center gap-3">
                        <?php if ($listing['seller_avatar']): ?>
                            <img src="/<?= clean($listing['seller_avatar']) ?>"
                                alt="<?= clean($listing['seller_display']) ?>"
                                class="rounded-circle" width="48" height="48" style="object-fit:cover">
                        <?php else: ?>
                            <div class="seller-avatar-placeholder rounded-circle d-flex align-items-center justify-content-center"
                                aria-hidden="true">
                                <?= strtoupper(mb_substr($listing['seller_display'], 0, 1)) ?>
                            </div>
                        <?php endif; ?>
                        <div>
                            <div class="fw-semibold">
                                <a href="/profile.php?user=<?= clean($listing['seller_username']) ?>"
                                    class="text-decoration-none text-reset">
                                    <?= clean($listing['seller_display']) ?>
                                </a>
                            </div>
                            <div class="text-muted small">
                                @<?= clean($listing['seller_username']) ?> &bull;
                                Member since <?= date('M Y', strtotime($listing['seller_joined'])) ?>
                            </div>
                            <?php if ((int)$sellerRating['review_count'] > 0): ?>
                                <div class="small mt-1">
                                    <span class="text-accent"><?= str_repeat('★', (int)round($sellerRating['avg_rating'])) ?><?= str_repeat('☆', 5 - (int)round($sellerRating['avg_rating'])) ?></span>
                                    <span class="text-muted ms-1"><?= $sellerRating['avg_rating'] ?> / 5 (<?= (int)$sellerRating['review_count'] ?> review<?= $sellerRating['review_count'] != 1 ? 's' : '' ?>)</span>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>

            </div><!-- /listing-details -->
        </div><!-- /col -->
    </div><!-- /row -->

    <!-- Related listings -->
    <?php if (!empty($related)): ?>
        <section class="mt-5" aria-labelledby="relatedHeading">
            <h2 id="relatedHeading" class="section-heading mb-4">More in <?= clean($listing['category_name']) ?></h2>
            <div class="row row-cols-1 row-cols-sm-2 row-cols-md-4 g-4">
                <?php foreach ($related as $listing): ?>
                    <div class="col">
                        <?php include __DIR__ . '/includes/listing-card.php'; ?>
                    </div>
                <?php endforeach; ?>
            </div>
        </section>
    <?php endif; ?>

</div><!-- /container -->

<script src="/assets/js/listing.js" defer></script>
<?php require_once __DIR__ . '/includes/footer.php'; ?>