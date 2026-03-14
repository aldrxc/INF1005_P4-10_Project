<?php
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/csrf.php';
require_once __DIR__ . '/../includes/sanitize.php';

startSession();
requireAdmin();

$pageTitle = 'Admin — Listings';
$pdo = getDB();

$listings = $pdo->query("
    SELECT l.listing_id, l.title, l.price, l.status, l.created_at,
           u.username AS seller_username
    FROM listings l
    JOIN users u ON l.seller_id = u.user_id
    ORDER BY l.created_at DESC
")->fetchAll();

generateCsrfToken();
require_once __DIR__ . '/../includes/header.php';
?>

<div class="container py-4">
    <nav aria-label="breadcrumb" class="mb-3">
        <ol class="breadcrumb breadcrumb-dark small">
            <li class="breadcrumb-item"><a href="/admin/">Admin</a></li>
            <li class="breadcrumb-item active">Listings</li>
        </ol>
    </nav>
    <h1 class="mb-4">Listings <span class="badge bg-secondary ms-2"><?= count($listings) ?></span></h1>

    <div class="table-responsive">
        <table class="table table-dark table-hover align-middle">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Title</th>
                    <th>Seller</th>
                    <th>Price</th>
                    <th>Status</th>
                    <th>Created</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($listings as $l): ?>
                <tr>
                    <td class="text-muted"><?= (int)$l['listing_id'] ?></td>
                    <td>
                        <a href="/listing.php?id=<?= (int)$l['listing_id'] ?>" class="text-reset text-decoration-none">
                            <?= clean(mb_strimwidth($l['title'], 0, 50, '…')) ?>
                        </a>
                    </td>
                    <td class="text-muted small">@<?= clean($l['seller_username']) ?></td>
                    <td>S$<?= number_format((float)$l['price'], 2) ?></td>
                    <td>
                        <span class="badge bg-<?= $l['status'] === 'available' ? 'success' : ($l['status'] === 'reserved' ? 'warning' : 'danger') ?>">
                            <?= ucfirst($l['status']) ?>
                        </span>
                    </td>
                    <td class="text-muted small"><?= clean(date('d M Y', strtotime($l['created_at']))) ?></td>
                    <td>
                        <form method="POST" action="/admin/delete-listing-handler.php" class="d-inline">
                            <?= getCsrfField() ?>
                            <input type="hidden" name="listing_id" value="<?= (int)$l['listing_id'] ?>">
                            <button type="submit" class="btn btn-sm btn-outline-danger"
                                    onclick="return confirm('Delete listing #<?= (int)$l['listing_id'] ?>? This cannot be undone.')">
                                <i class="bi bi-trash me-1" aria-hidden="true"></i>Delete
                            </button>
                        </form>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
