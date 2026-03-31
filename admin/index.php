<?php
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/csrf.php';
require_once __DIR__ . '/../includes/sanitize.php';

startSession();
requireAdmin();

$pageTitle = 'Admin Panel';
$pdo = getDB();

// stat counts
$totalUsers    = (int)$pdo->query("SELECT COUNT(*) FROM users")->fetchColumn();
$totalListings = (int)$pdo->query("SELECT COUNT(*) FROM listings")->fetchColumn();
$totalOrders   = (int)$pdo->query("SELECT COUNT(*) FROM orders")->fetchColumn();
$flagged       = 0; // no reports table yet

require_once __DIR__ . '/../includes/header.php';
?>

<div class="container py-4">
    <h1 class="mb-1">Admin Panel</h1>
    <p class="text-muted mb-4">Site overview and management tools</p>

    <!-- stat cards -->
    <div class="row g-3 mb-5">
        <div class="col-6 col-md-3">
            <div class="card h-100 text-center p-3">
                <div class="fs-1 fw-bold text-accent"><?= number_format($totalUsers) ?></div>
                <div class="text-muted small">Total Users</div>
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div class="card h-100 text-center p-3">
                <div class="fs-1 fw-bold text-accent"><?= number_format($totalListings) ?></div>
                <div class="text-muted small">Total Listings</div>
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div class="card h-100 text-center p-3">
                <div class="fs-1 fw-bold text-accent"><?= number_format($totalOrders) ?></div>
                <div class="text-muted small">Total Orders</div>
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div class="card h-100 text-center p-3">
                <div class="fs-1 fw-bold" style="color:var(--mv-text-muted);opacity:0.4"><?= $flagged ?></div>
                <div class="text-muted small">Flagged</div>
            </div>
        </div>
    </div>

    <!-- quick links -->
    <h2 class="h5 mb-3">Manage</h2>
    <div class="row g-3">
        <div class="col-md-4">
            <a href="/admin/users.php" class="card admin-manage-card p-4 text-decoration-none d-block">
                <i class="bi bi-people fs-3 text-accent mb-2 d-block" aria-hidden="true"></i>
                <div class="fw-semibold">Users</div>
                <div class="small" style="color:var(--mv-text);opacity:0.55">View, ban, and manage accounts</div>
            </a>
        </div>
        <div class="col-md-4">
            <a href="/admin/listings.php" class="card admin-manage-card p-4 text-decoration-none d-block">
                <i class="bi bi-tags fs-3 text-accent mb-2 d-block" aria-hidden="true"></i>
                <div class="fw-semibold">Listings</div>
                <div class="small" style="color:var(--mv-text);opacity:0.55">Browse and delete any listing</div>
            </a>
        </div>
        <div class="col-md-4">
            <a href="/admin/orders.php" class="card admin-manage-card p-4 text-decoration-none d-block">
                <i class="bi bi-bag-check fs-3 text-accent mb-2 d-block" aria-hidden="true"></i>
                <div class="fw-semibold">Orders</div>
                <div class="small" style="color:var(--mv-text);opacity:0.55">Read-only order history</div>
            </a>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
