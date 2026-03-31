<?php
require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/sanitize.php';

startSession();

$pageTitle = 'Browse Listings';
$pdo = getDB();

// --- Allowed filter values (whitelist) ---
$allowedSorts = [
    'newest'     => 'l.created_at DESC',
    'oldest'     => 'l.created_at ASC',
    'price_asc'  => 'l.price ASC',
    'price_desc' => 'l.price DESC',
];
$allowedConditions = ['new', 'like_new', 'good', 'fair', 'poor'];

// --- Read & validate GET params ---
$searchQ    = trim($_GET['q']         ?? '');
$catSlug    = trim($_GET['category']  ?? '');
$genreId    = sanitizeInt($_GET['genre']  ?? '');
$conditions = isset($_GET['condition']) && is_array($_GET['condition'])
              ? array_filter($_GET['condition'], fn($v) => in_array($v, $allowedConditions, true))
              : [];
$minPrice   = sanitizePrice($_GET['min_price'] ?? '');
$maxPrice   = sanitizePrice($_GET['max_price'] ?? '');
$sortKey    = array_key_exists($_GET['sort'] ?? '', $allowedSorts) ? $_GET['sort'] : 'newest';
$page       = max(1, (int)($_GET['page'] ?? 1));
$perPage    = 12;
$offset     = ($page - 1) * $perPage;

// Validate category slug
$categoryRow = null;
if ($catSlug !== '') {
    $stmt = $pdo->prepare("SELECT category_id, name, slug FROM categories WHERE slug = ? LIMIT 1");
    $stmt->execute([$catSlug]);
    $categoryRow = $stmt->fetch();
    if (!$categoryRow) {
        $catSlug = '';
    }
}

// --- Build query ---
$where  = ["l.status = 'available'"];
$params = [];

if ($catSlug && $categoryRow) {
    $where[]  = 'l.category_id = ?';
    $params[] = $categoryRow['category_id'];
}
if ($genreId) {
    $where[]  = 'l.genre_id = ?';
    $params[] = $genreId;
}
if (!empty($conditions)) {
    $placeholders = implode(',', array_fill(0, count($conditions), '?'));
    $where[]  = "l.condition_type IN ($placeholders)";
    $params   = array_merge($params, array_values($conditions));
}
if ($minPrice !== null) {
    $where[]  = 'l.price >= ?';
    $params[] = $minPrice;
}
if ($maxPrice !== null) {
    $where[]  = 'l.price <= ?';
    $params[] = $maxPrice;
}
if ($searchQ !== '') {
    $where[]  = '(l.title LIKE ? OR l.artist_band LIKE ? OR l.description LIKE ?)';
    $like     = '%' . $searchQ . '%';
    $params[] = $like;
    $params[] = $like;
    $params[] = $like;
}

$whereClause = 'WHERE ' . implode(' AND ', $where);
$orderClause = 'ORDER BY ' . $allowedSorts[$sortKey];

// Count total results for pagination
$countStmt = $pdo->prepare("SELECT COUNT(*) FROM listings l $whereClause");
$countStmt->execute($params);
$totalResults = (int)$countStmt->fetchColumn();
$totalPages   = (int)ceil($totalResults / $perPage);

// Fetch listings for current page
$listingParams   = array_merge($params, [$perPage, $offset]);
$listingStmt = $pdo->prepare("
    SELECT l.listing_id, l.title, l.price, l.artist_band, l.condition_type,
           c.name AS category_name, c.slug AS category_slug,
           g.name AS genre_name,
           u.username AS seller_username, u.display_name AS seller_display,
           li.file_path AS primary_image
    FROM listings l
    JOIN categories c ON l.category_id = c.category_id
    LEFT JOIN genres g ON l.genre_id = g.genre_id
    JOIN users u ON l.seller_id = u.user_id
    LEFT JOIN listing_images li ON li.listing_id = l.listing_id AND li.is_primary = 1
    $whereClause
    $orderClause
    LIMIT ? OFFSET ?
");
$listingStmt->execute($listingParams);
$listings = $listingStmt->fetchAll();

// Filter sidebar data
$allCategories = $pdo->query("SELECT category_id, name, slug FROM categories ORDER BY name")->fetchAll();
$allGenres     = $pdo->query("SELECT genre_id, name FROM genres ORDER BY name")->fetchAll();

require_once __DIR__ . '/includes/header.php';
?>

<!-- Page header -->
<section class="page-header py-4" aria-labelledby="browseHeading">
    <div class="container">
        <h1 id="browseHeading" class="h3 fw-bold text-white mb-0">Browse Listings</h1>
        <?php if ($searchQ): ?>
            <p class="text-white-75 small mt-1 mb-0">
                Results for "<strong><?= clean($searchQ) ?></strong>"
                &mdash; <?= $totalResults ?> <?= $totalResults === 1 ? 'item' : 'items' ?>
            </p>
        <?php endif; ?>
    </div>
</section>

<div class="container py-4">
    <div class="row g-4">

        <!-- ===================== FILTER SIDEBAR ===================== -->
        <!-- Mobile: offcanvas trigger -->
        <div class="d-lg-none mb-2">
            <button class="btn btn-outline-secondary btn-sm w-100" type="button"
                    data-bs-toggle="offcanvas" data-bs-target="#filterOffcanvas"
                    aria-controls="filterOffcanvas">
                <i class="bi bi-funnel me-2" aria-hidden="true"></i>Filters
                <?php if (!empty($conditions) || $catSlug || $genreId || $minPrice || $maxPrice): ?>
                    <span class="badge bg-accent ms-1">Active</span>
                <?php endif; ?>
            </button>
        </div>

        <!-- Offcanvas for mobile -->
        <div class="offcanvas offcanvas-start offcanvas-dark" tabindex="-1"
             id="filterOffcanvas" aria-labelledby="filterOffcanvasLabel">
            <div class="offcanvas-header">
                <h2 class="offcanvas-title h5" id="filterOffcanvasLabel">Filters</h2>
                <button type="button" class="btn-close btn-close-white"
                        data-bs-dismiss="offcanvas" aria-label="Close filters"></button>
            </div>
            <div class="offcanvas-body">
                <?php include __DIR__ . '/includes/filter-form.php'; ?>
            </div>
        </div>

        <!-- Desktop sidebar -->
        <aside class="col-lg-3 d-none d-lg-block" aria-label="Listing filters">
            <div class="filter-sidebar sticky-top" style="top:80px">
                <?php include __DIR__ . '/includes/filter-form.php'; ?>
            </div>
        </aside>

        <!-- ===================== RESULTS AREA ===================== -->
        <div class="col-lg-9">

            <!-- Active filter chips + sort -->
            <div class="d-flex flex-wrap align-items-center gap-2 mb-3">
                <span class="text-muted small me-auto">
                    <?= $totalResults ?> <?= $totalResults === 1 ? 'result' : 'results' ?>
                </span>

                <?php if ($searchQ): ?>
                    <a href="<?= clean(strtok($_SERVER['REQUEST_URI'], '?') . '?' . http_build_query(array_merge($_GET, ['q' => '']))) ?>"
                       class="filter-chip badge">
                        "<?= clean(mb_strimwidth($searchQ, 0, 20, '…')) ?>" <i class="bi bi-x" aria-hidden="true"></i>
                    </a>
                <?php endif; ?>
                <?php if ($catSlug && $categoryRow): ?>
                    <a href="?<?= clean(http_build_query(array_merge($_GET, ['category' => '', 'page' => 1]))) ?>"
                       class="filter-chip badge">
                        <?= clean($categoryRow['name']) ?> <i class="bi bi-x" aria-hidden="true"></i>
                    </a>
                <?php endif; ?>
                <?php if ($catSlug || $genreId || !empty($conditions) || $minPrice || $maxPrice || $searchQ): ?>
                    <a href="/browse.php" class="badge bg-danger-subtle text-danger-emphasis text-decoration-none">
                        <i class="bi bi-x-circle me-1" aria-hidden="true"></i>Clear All
                    </a>
                <?php endif; ?>

                <!-- Sort -->
                <form method="GET" id="sortForm" class="ms-auto" aria-label="Sort listings">
                    <?php foreach ($_GET as $k => $v): if ($k === 'sort' || $k === 'page') continue; ?>
                        <?php if (is_array($v)): foreach ($v as $vi): ?>
                            <input type="hidden" name="<?= clean($k) ?>[]" value="<?= clean($vi) ?>">
                        <?php endforeach; else: ?>
                            <input type="hidden" name="<?= clean($k) ?>" value="<?= clean($v) ?>">
                        <?php endif; ?>
                    <?php endforeach; ?>
                    <select name="sort" class="form-select form-select-sm sort-select" onchange="this.form.submit()"
                            aria-label="Sort by">
                        <option value="newest"     <?= $sortKey === 'newest'     ? 'selected' : '' ?>>Newest</option>
                        <option value="oldest"     <?= $sortKey === 'oldest'     ? 'selected' : '' ?>>Oldest</option>
                        <option value="price_asc"  <?= $sortKey === 'price_asc'  ? 'selected' : '' ?>>Price: Low–High</option>
                        <option value="price_desc" <?= $sortKey === 'price_desc' ? 'selected' : '' ?>>Price: High–Low</option>
                    </select>
                </form>
            </div>

            <!-- Listings grid -->
            <?php if (empty($listings)): ?>
                <div class="text-center py-5 text-muted" role="status">
                    <i class="bi bi-search display-4" aria-hidden="true"></i>
                    <p class="mt-3">No listings found matching your filters.</p>
                    <a href="/browse.php" class="btn btn-outline-secondary btn-sm">Clear Filters</a>
                </div>
            <?php else: ?>
                <div class="row row-cols-1 row-cols-sm-2 row-cols-xl-3 g-4" id="listingsGrid">
                    <?php foreach ($listings as $listing): ?>
                        <div class="col">
                            <?php include __DIR__ . '/includes/listing-card.php'; ?>
                        </div>
                    <?php endforeach; ?>
                </div>

                <!-- Pagination -->
                <?php if ($totalPages > 1): ?>
                    <nav class="mt-4" aria-label="Listings pagination">
                        <ul class="pagination justify-content-center">
                            <?php
                            $queryBase = array_merge($_GET, ['page' => 1]);
                            for ($p = 1; $p <= $totalPages; $p++):
                                $queryBase['page'] = $p;
                                $pUrl = '?' . http_build_query($queryBase);
                            ?>
                                <li class="page-item <?= $p === $page ? 'active' : '' ?>">
                                    <a class="page-link"
                                       href="<?= clean($pUrl) ?>"
                                       <?= $p === $page ? 'aria-current="page"' : '' ?>>
                                        <?= $p ?>
                                    </a>
                                </li>
                            <?php endfor; ?>
                        </ul>
                    </nav>
                <?php endif; ?>
            <?php endif; ?>

        </div><!-- /results -->
    </div><!-- /row -->
</div><!-- /container -->

<script src="/assets/js/browse.js" defer></script>
<?php require_once __DIR__ . '/includes/footer.php'; ?>
