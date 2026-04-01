<?php
require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/csrf.php';
require_once __DIR__ . '/includes/sanitize.php';

startSession();
requireLogin();

$pageTitle = 'Dashboard';
$pdo       = getDB();
$userId    = getCurrentUserId();

// profile info
$stmt = $pdo->prepare("SELECT * FROM users WHERE user_id = ? LIMIT 1");
$stmt->execute([$userId]);
$user = $stmt->fetch();
if (!$user) {
    session_destroy();
    header('Location: /login.php');
    exit;
}

// my listings
$myListings = $pdo->prepare("
    SELECT l.listing_id, l.title, l.price, l.status, l.views, l.created_at,
           c.name AS category_name,
           (SELECT file_path FROM listing_images WHERE listing_id = l.listing_id AND is_primary=1 LIMIT 1) AS primary_image,
           (SELECT COUNT(*) FROM listing_images WHERE listing_id = l.listing_id) AS image_count
    FROM listings l
    JOIN categories c ON l.category_id = c.category_id
    WHERE l.seller_id = ?
    ORDER BY l.created_at DESC
");
$myListings->execute([$userId]);
$myListings = $myListings->fetchAll();

// my purchases
$myOrders = $pdo->prepare("
    SELECT o.order_id, o.total_amount, o.status, o.created_at,
           COUNT(oi.order_item_id) AS item_count
    FROM orders o
    LEFT JOIN order_items oi ON o.order_id = oi.order_id
    WHERE o.buyer_id = ?
    GROUP BY o.order_id
    ORDER BY o.created_at DESC
");
$myOrders->execute([$userId]);
$myOrders = $myOrders->fetchAll();

// my sales
$mySales = $pdo->prepare("
    SELECT oi.order_item_id, oi.price_paid, oi.quantity, oi.order_id,
           l.listing_id, l.title,
           o.created_at, o.status AS order_status,
           u.display_name AS buyer_display,
           (SELECT file_path FROM listing_images WHERE listing_id = l.listing_id AND is_primary=1 LIMIT 1) AS primary_image
    FROM order_items oi
    JOIN listings l ON oi.listing_id = l.listing_id
    JOIN orders o ON oi.order_id = o.order_id
    JOIN users u ON o.buyer_id = u.user_id
    WHERE oi.seller_id = ?
    ORDER BY o.created_at DESC
");
$mySales->execute([$userId]);
$mySales = $mySales->fetchAll();

generateCsrfToken();
require_once __DIR__ . '/includes/header.php';

$statusLabels = [
    'available' => ['Available', 'success'],
    'reserved'  => ['Reserved',  'warning'],
    'sold'      => ['Sold',      'danger'],
];
$orderStatusLabels = [
    'pending'   => ['Pending',   'warning'],
    'confirmed' => ['Confirmed', 'info'],
    'shipped'   => ['Shipped',   'primary'],
    'completed' => ['Completed', 'success'],
    'cancelled' => ['Cancelled', 'danger'],
];
?>

<div class="container py-4">

    <!-- profile card -->
    <div class="dashboard-profile-card card border-0 mb-4 p-4">
        <div class="d-flex align-items-center gap-4 flex-wrap">
            <?php if ($user['avatar_path']): ?>
                <img src="/<?= clean($user['avatar_path']) ?>" alt="Your avatar"
                    class="rounded-circle" width="80" height="80" style="object-fit:cover">
            <?php else: ?>
                <div class="dashboard-avatar-placeholder rounded-circle d-flex align-items-center justify-content-center"
                    aria-hidden="true">
                    <?= strtoupper(mb_substr($user['display_name'], 0, 1)) ?>
                </div>
            <?php endif; ?>
            <div>
                <h1 class="h4 fw-bold mb-0 text-white"><?= clean($user['display_name']) ?></h1>
                <div class="text-muted">@<?= clean($user['username']) ?></div>
                <div class="text-muted small mt-1">
                    <i class="bi bi-calendar3 me-1" aria-hidden="true"></i>
                    Member since <?= clean(date('M Y', strtotime($user['joined_at']))) ?>
                    &nbsp;&bull;&nbsp;
                    <?= count($myListings) ?> listing<?= count($myListings) !== 1 ? 's' : '' ?>
                </div>
            </div>
            <div class="ms-auto d-flex gap-2">
                <a href="/profile.php?user=<?= clean($user['username']) ?>" class="btn btn-sm btn-outline-secondary">
                    <i class="bi bi-person me-1" aria-hidden="true"></i>Public Profile
                </a>
                <a href="/create-listing.php" class="btn btn-sm btn-accent">
                    <i class="bi bi-plus-circle me-1" aria-hidden="true"></i>New Listing
                </a>
            </div>
        </div>
    </div>

    <!-- tabs -->
    <ul class="nav nav-tabs dashboard-tabs mb-4" id="dashTabs" role="tablist">
        <li class="nav-item" role="presentation">
            <button class="nav-link active" id="listingsTab" data-bs-toggle="tab" data-bs-target="#listingsPane"
                type="button" role="tab" aria-controls="listingsPane" aria-selected="true">
                <i class="bi bi-tags me-1" aria-hidden="true"></i>My Listings
                <span class="badge bg-secondary ms-1"><?= count($myListings) ?></span>
            </button>
        </li>
        <li class="nav-item" role="presentation">
            <button class="nav-link" id="purchasesTab" data-bs-toggle="tab" data-bs-target="#purchasesPane"
                type="button" role="tab" aria-controls="purchasesPane" aria-selected="false">
                <i class="bi bi-bag me-1" aria-hidden="true"></i>My Purchases
                <span class="badge bg-secondary ms-1"><?= count($myOrders) ?></span>
            </button>
        </li>
        <li class="nav-item" role="presentation">
            <button class="nav-link" id="salesTab" data-bs-toggle="tab" data-bs-target="#salesPane"
                type="button" role="tab" aria-controls="salesPane" aria-selected="false">
                <i class="bi bi-cash-coin me-1" aria-hidden="true"></i>My Sales
                <span class="badge bg-secondary ms-1"><?= count($mySales) ?></span>
            </button>
        </li>
    </ul>

    <div class="tab-content" id="dashTabContent">

        <!-- my listings tab -->
        <div class="tab-pane fade show active" id="listingsPane" role="tabpanel" aria-labelledby="listingsTab">
            <?php if (empty($myListings)): ?>
                <div class="text-center py-5 text-muted">
                    <i class="bi bi-tags display-4" aria-hidden="true"></i>
                    <p class="mt-3">You haven't listed anything yet.</p>
                    <a href="/create-listing.php" class="btn btn-accent">Create Your First Listing</a>
                </div>
            <?php else: ?>
                <div class="table-responsive">
                    <table class="table table-dark table-hover align-middle">
                        <thead>
                            <tr>
                                <th scope="col">Item</th>
                                <th scope="col">Price</th>
                                <th scope="col">Status</th>
                                <th scope="col" class="d-none d-md-table-cell">Views</th>
                                <th scope="col" class="d-none d-lg-table-cell">Listed</th>
                                <th scope="col">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($myListings as $item):
                                [$statusLabel, $statusColor] = $statusLabels[$item['status']] ?? ['Unknown', 'secondary'];
                            ?>
                                <tr id="listingRow<?= (int)$item['listing_id'] ?>">
                                    <td>
                                        <div class="d-flex align-items-center gap-2">
                                            <img src="<?= $item['primary_image'] ? '/' . clean($item['primary_image']) : '/assets/images/placeholder.php' ?>"
                                                alt="<?= clean($item['title']) ?>"
                                                class="rounded" width="40" height="40" style="object-fit:cover">
                                            <div>
                                                <a href="/listing.php?id=<?= (int)$item['listing_id'] ?>"
                                                    class="fw-semibold text-decoration-none text-reset small">
                                                    <?= clean(mb_strimwidth($item['title'], 0, 50, '…')) ?>
                                                </a>
                                                <div class="text-muted" style="font-size:0.75rem"><?= clean($item['category_name']) ?></div>
                                            </div>
                                        </div>
                                    </td>
                                    <td class="fw-semibold">S$<?= number_format((float)$item['price'], 2) ?></td>
                                    <td>
                                        <!-- inline status toggle -->
                                        <select class="form-select form-select-sm status-toggle"
                                            data-listing-id="<?= (int)$item['listing_id'] ?>"
                                            data-csrf="<?= clean($_SESSION['csrf_token']) ?>"
                                            aria-label="Listing status">
                                            <?php foreach ($statusLabels as $val => [$label, $color]): ?>
                                                <option value="<?= $val ?>" <?= $item['status'] === $val ? 'selected' : '' ?>>
                                                    <?= $label ?>
                                                </option>
                                            <?php endforeach; ?>
                                        </select>
                                    </td>
                                    <td class="text-muted small d-none d-md-table-cell"><?= number_format((int)$item['views']) ?></td>
                                    <td class="text-muted small d-none d-lg-table-cell"><?= clean(date('d M Y', strtotime($item['created_at']))) ?></td>
                                    <td>
                                        <div class="d-flex gap-1">
                                            <a href="/edit-listing.php?id=<?= (int)$item['listing_id'] ?>"
                                                class="btn btn-sm btn-outline-secondary"
                                                aria-label="Edit <?= clean($item['title']) ?>">
                                                <i class="bi bi-pencil" aria-hidden="true"></i>
                                            </a>
                                            <button type="button"
                                                class="btn btn-sm btn-outline-danger delete-listing-btn"
                                                data-listing-id="<?= (int)$item['listing_id'] ?>"
                                                data-title="<?= clean($item['title']) ?>"
                                                aria-label="Delete <?= clean($item['title']) ?>">
                                                <i class="bi bi-trash" aria-hidden="true"></i>
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>

        <!-- my purchases tab -->
        <div class="tab-pane fade" id="purchasesPane" role="tabpanel" aria-labelledby="purchasesTab">
            <?php if (empty($myOrders)): ?>
                <div class="text-center py-5 text-muted">
                    <i class="bi bi-bag display-4" aria-hidden="true"></i>
                    <p class="mt-3">You haven't made any purchases yet.</p>
                    <a href="/browse.php" class="btn btn-accent">Start Shopping</a>
                </div>
            <?php else: ?>
                <div class="table-responsive">
                    <table class="table table-dark table-hover align-middle">
                        <thead>
                            <tr>
                                <th scope="col">Order #</th>
                                <th scope="col">Items</th>
                                <th scope="col">Total</th>
                                <th scope="col">Status</th>
                                <th scope="col">Date</th>
                                <th scope="col">Details</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($myOrders as $order):
                                [$oLabel, $oColor] = $orderStatusLabels[$order['status']] ?? ['Unknown', 'secondary'];
                            ?>
                                <tr>
                                    <td class="fw-semibold">#<?= (int)$order['order_id'] ?></td>
                                    <td class="text-muted small"><?= (int)$order['item_count'] ?> item<?= $order['item_count'] != 1 ? 's' : '' ?></td>
                                    <td class="fw-semibold">S$<?= number_format((float)$order['total_amount'], 2) ?></td>
                                    <td><span class="badge bg-<?= $oColor ?>"><?= $oLabel ?></span></td>
                                    <td class="text-muted small"><?= clean(date('d M Y', strtotime($order['created_at']))) ?></td>
                                    <td>
                                        <a href="/order-confirmation.php?id=<?= (int)$order['order_id'] ?>"
                                            class="btn btn-sm btn-outline-secondary"
                                            aria-label="View order <?= (int)$order['order_id'] ?>">
                                            View
                                        </a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>

        <!-- my sales tab -->
        <div class="tab-pane fade" id="salesPane" role="tabpanel" aria-labelledby="salesTab">
            <?php if (empty($mySales)): ?>
                <div class="text-center py-5 text-muted">
                    <i class="bi bi-cash-coin display-4" aria-hidden="true"></i>
                    <p class="mt-3">No sales yet.</p>
                    <a href="/create-listing.php" class="btn btn-accent">List an Item to Sell</a>
                </div>
            <?php else: ?>
                <div class="table-responsive">
                    <table class="table table-dark table-hover align-middle">
                        <thead>
                            <tr>
                                <th scope="col">Item</th>
                                <th scope="col">Buyer</th>
                                <th scope="col">Revenue</th>
                                <th scope="col">Status</th>
                                <th scope="col">Date</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($mySales as $sale):
                                [$oLabel, $oColor] = $orderStatusLabels[$sale['order_status']] ?? ['Unknown', 'secondary'];
                            ?>
                                <tr>
                                    <td>
                                        <div class="d-flex align-items-center gap-2">
                                            <img src="<?= $sale['primary_image'] ? '/' . clean($sale['primary_image']) : '/assets/images/placeholder.php' ?>"
                                                alt="<?= clean($sale['title']) ?>"
                                                class="rounded" width="36" height="36" style="object-fit:cover">
                                            <a href="/listing.php?id=<?= (int)$sale['listing_id'] ?>"
                                                class="text-decoration-none text-reset small fw-semibold">
                                                <?= clean(mb_strimwidth($sale['title'], 0, 45, '…')) ?>
                                            </a>
                                        </div>
                                    </td>
                                    <td class="text-muted small"><?= clean($sale['buyer_display']) ?></td>
                                    <td class="fw-semibold text-success">
                                        S$<?= number_format((float)$sale['price_paid'] * (int)$sale['quantity'], 2) ?>
                                    </td>
                                    <td><span class="badge bg-<?= $oColor ?>"><?= $oLabel ?></span></td>
                                    <td class="text-muted small"><?= clean(date('d M Y', strtotime($sale['created_at']))) ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>

    </div><!-- /tab-content -->
</div>

<!-- delete confirmation modal -->
<div class="modal fade" id="deleteListingModal" tabindex="-1"
    aria-labelledby="deleteListingModalLabel" aria-hidden="true" role="dialog">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content bg-dark border-secondary">
            <div class="modal-header border-secondary">
                <h2 class="modal-title h5" id="deleteListingModalLabel">Delete Listing</h2>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body text-muted">
                Are you sure you want to delete <strong id="deleteListingTitle" class="text-white"></strong>?
                This action cannot be undone.
            </div>
            <div class="modal-footer border-secondary">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <form id="deleteListingForm" method="POST" action="/delete-listing.php">
                    <?= getCsrfField() ?>
                    <input type="hidden" name="listing_id" id="deleteListingId">
                    <button type="submit" class="btn btn-danger">Delete Listing</button>
                </form>
            </div>
        </div>
    </div>
</div>

<script src="/assets/js/dashboard.js" defer></script>
<?php require_once __DIR__ . '/includes/footer.php'; ?>