<?php
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/csrf.php';
require_once __DIR__ . '/../includes/sanitize.php';

startSession();
requireAdmin();

$pageTitle = 'Admin — Listings';
$pdo = getDB();

$page    = max(1, (int)($_GET['page'] ?? 1));
$perPage = 25;
$offset  = ($page - 1) * $perPage;

$total      = (int)$pdo->query("SELECT COUNT(*) FROM listings")->fetchColumn();
$totalPages = (int)ceil($total / $perPage);

$stmt = $pdo->prepare("
    SELECT l.listing_id, l.title, l.price, l.status, l.created_at,
           u.username AS seller_username
    FROM listings l
    JOIN users u ON l.seller_id = u.user_id
    ORDER BY l.created_at DESC
    LIMIT ? OFFSET ?
");
$stmt->execute([$perPage, $offset]);
$listings = $stmt->fetchAll();

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
    <h1 class="mb-4">Listings <span class="badge bg-secondary ms-2"><?= $total ?></span></h1>

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
                                <button type="submit" class="btn btn-sm btn-outline-danger btn-confirm"
                                    data-confirm="Delete listing #<?= (int)$l['listing_id'] ?>? This cannot be undone.">
                                    <i class="bi bi-trash me-1" aria-hidden="true"></i>Delete
                                </button>
                            </form>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>

    <?php if ($totalPages > 1): ?>
        <nav class="mt-3" aria-label="Listings pagination">
            <ul class="pagination justify-content-center">
                <?php if ($page > 1): ?>
                    <li class="page-item">
                        <a class="page-link" href="?page=<?= $page - 1 ?>"><i class="bi bi-chevron-left" aria-hidden="true"></i></a>
                    </li>
                <?php endif; ?>
                <?php for ($p = max(1, $page - 2); $p <= min($totalPages, $page + 2); $p++): ?>
                    <li class="page-item <?= $p === $page ? 'active' : '' ?>">
                        <a class="page-link" href="?page=<?= $p ?>"><?= $p ?></a>
                    </li>
                <?php endfor; ?>
                <?php if ($page < $totalPages): ?>
                    <li class="page-item">
                        <a class="page-link" href="?page=<?= $page + 1 ?>"><i class="bi bi-chevron-right" aria-hidden="true"></i></a>
                    </li>
                <?php endif; ?>
            </ul>
        </nav>
    <?php endif; ?>
</div>

<?php ob_start(); ?>
<script>
    $(function() {
        $('.btn-confirm').on('click', function(e) {
            if (!confirm($(this).data('confirm'))) {
                e.preventDefault();
            }
        });
    });
</script>
<?php $extraScripts = ob_get_clean(); ?>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>