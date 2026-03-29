<?php
// header.php — included at the top of every page
// Expects: $pageTitle (string), session already started
if (!isset($pageTitle)) {
    $pageTitle = 'MerchVault';
}
$flash = getFlash();
$cartCount = 0;
$unreadMessages = 0;
if (isLoggedIn()) {
    try {
        $stmt = getDB()->prepare("SELECT COALESCE(SUM(quantity),0) AS cnt FROM cart_items WHERE user_id = ?");
        $stmt->execute([getCurrentUserId()]);
        $cartCount = (int)$stmt->fetchColumn();
    } catch (Exception $e) {
        $cartCount = 0;
    }
    try {
        $stmt = getDB()->prepare("SELECT COUNT(*) FROM messages WHERE receiver_id = ? AND is_read = 0");
        $stmt->execute([getCurrentUserId()]);
        $unreadMessages = (int)$stmt->fetchColumn();
    } catch (Exception $e) {}
}
$currentPath = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
function navActive(string $path): string
{
    global $currentPath;
    return ($currentPath === $path || strpos($currentPath, $path) === 0 && $path !== '/') ? 'active' : '';
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="MerchVault — Buy and sell music merchandise and event tickets">
    <title><?= htmlspecialchars($pageTitle, ENT_QUOTES, 'UTF-8') ?> | MerchVault</title>

    <!-- Bootstrap 5 CSS -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css"
        integrity="sha384-QWTKZyjpPEjISv5WaRU9OFeRpok6YctnYmDr5pNlyT2bRjXh0JMhjY6hW+ALEwIH"
        crossorigin="anonymous">
    <!-- Bootstrap Icons -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
    <!-- Custom CSS -->
    <link rel="stylesheet" href="/assets/css/style.css">
</head>

<body>

    <!-- Navigation -->
    <nav class="navbar navbar-expand-lg navbar-dark sticky-top" id="mainNav" aria-label="Main navigation">
        <div class="container">

            <!-- Brand -->
            <a class="navbar-brand d-flex align-items-center gap-2" href="/index.php">
                <i class="bi bi-music-note-list fs-4" aria-hidden="true"></i>
                <span class="fw-bold">MerchVault</span>
            </a>

            <!-- Right side: mobile quick actions + toggle -->
            <div class="nav-mobile-tools d-flex d-lg-none align-items-center gap-2 ms-auto">
                <?php if (isLoggedIn()): ?>
                    <a class="nav-icon-link position-relative" href="/cart.php" aria-label="Shopping cart">
                        <i class="bi bi-cart3 fs-5" aria-hidden="true"></i>
                        <span class="cart-badge badge rounded-pill bg-accent"
                            id="cartBadgeMobile"
                            <?= $cartCount === 0 ? 'style="display:none"' : '' ?>>
                            <?= $cartCount ?>
                        </span>
                    </a>

                    <a class="btn btn-accent btn-sm nav-sell-btn" href="/create-listing.php">
                        <i class="bi bi-plus-circle" aria-hidden="true"></i>
                        <span class="d-none d-sm-inline ms-1">Sell</span>
                    </a>

                    <div class="dropdown">
                        <a class="nav-icon-link" href="#" role="button"
                            data-bs-toggle="dropdown" aria-expanded="false"
                            aria-label="User menu">
                            <i class="bi bi-person-circle fs-5" aria-hidden="true"></i>
                        </a>
                        <ul class="dropdown-menu dropdown-menu-end dropdown-menu-dark">
                            <li>
                                <a class="dropdown-item" href="/dashboard.php">
                                    <i class="bi bi-speedometer2 me-2" aria-hidden="true"></i>Dashboard
                                </a>
                            </li>
                            <li>
                                <a class="dropdown-item" href="/profile.php?user=<?= clean($_SESSION['username'] ?? '') ?>">
                                    <i class="bi bi-person me-2" aria-hidden="true"></i>My Profile
                                </a>
                            </li>
                            <li>
                                <hr class="dropdown-divider">
                            </li>
                            <li>
                                <a class="dropdown-item text-danger" href="/logout.php">
                                    <i class="bi bi-box-arrow-right me-2" aria-hidden="true"></i>Log Out
                                </a>
                            </li>
                        </ul>
                    </div>
                <?php else: ?>
                    <a class="nav-link px-2" href="/login.php">Log In</a>
                    <a class="btn btn-accent btn-sm" href="/create-listing.php">
                        <i class="bi bi-plus-circle me-1" aria-hidden="true"></i>Sell
                    </a>
                <?php endif; ?>

                <!-- Mobile toggle -->
                <button class="navbar-toggler border-0 shadow-none" type="button"
                    data-bs-toggle="collapse" data-bs-target="#navbarMain"
                    aria-controls="navbarMain" aria-expanded="false" aria-label="Toggle navigation">
                    <span class="navbar-toggler-icon"></span>
                </button>
            </div>

            <!-- Desktop toggle -->
            <button class="navbar-toggler border-0 shadow-none d-none" type="button"
                data-bs-toggle="collapse" data-bs-target="#navbarMain"
                aria-controls="navbarMain" aria-expanded="false" aria-label="Toggle navigation">
                <span class="navbar-toggler-icon"></span>
            </button>

            <!-- Collapsible content -->
            <div class="collapse navbar-collapse" id="navbarMain">

                <!-- Left nav links -->
                <ul class="navbar-nav nav-primary mb-3 mb-lg-0">
                    <!-- <li class="nav-item">
                        <a class="nav-link <?= navActive('/index.php') ?>" href="/index.php">Home</a>
                    </li> -->
                    <li class="nav-item">
                        <a class="nav-link <?= navActive('/browse.php') ?>" href="/browse.php">Listings</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link <?= navActive('/about.php') ?>" href="/about.php">About</a>
                    </li>
                    <?php if (isAdmin()): ?>
                    <li class="nav-item">
                        <a class="nav-link admin-link <?= navActive('/admin/') ?>" href="/admin/">Admin</a>
                    </li>
                    <?php endif; ?>
                </ul>

                <div class="nav-right ms-lg-auto d-lg-flex align-items-lg-center justify-content-lg-end">
                    <!-- Search -->
                    <form class="nav-search me-lg-3 mb-3 mb-lg-0" role="search" method="GET" action="/browse.php">
                        <div class="input-group input-group-sm">
                            <input class="form-control" type="search" name="q"
                                placeholder="Search merch, artists, events..."
                                aria-label="Search listings"
                                value="<?= clean($_GET['q'] ?? '') ?>">
                            <button class="btn btn-accent" type="submit" aria-label="Submit search">
                                <i class="bi bi-search" aria-hidden="true"></i>
                            </button>
                        </div>
                    </form>

                    <?php if (isLoggedIn()): ?>
                        <!-- Desktop only actions -->
                        <div class="nav-actions d-none d-lg-flex align-items-center gap-2 flex-wrap">
                            <a class="nav-icon-link position-relative" href="/messages.php" aria-label="Messages">
                                <i class="bi bi-chat-dots fs-5" aria-hidden="true"></i>
                                <?php if ($unreadMessages > 0): ?>
                                <span class="cart-badge badge rounded-pill bg-accent" id="msgBadge">
                                    <?= $unreadMessages ?>
                                </span>
                                <?php endif; ?>
                            </a>
                            <a class="nav-icon-link position-relative" href="/cart.php" aria-label="Shopping cart">
                                <i class="bi bi-cart3 fs-5" aria-hidden="true"></i>
                                <span class="cart-badge badge rounded-pill bg-accent"
                                    id="cartBadge"
                                    <?= $cartCount === 0 ? 'style="display:none"' : '' ?>>
                                    <?= $cartCount ?>
                                </span>
                            </a>

                            <a class="btn btn-accent btn-sm" href="/create-listing.php">
                                <i class="bi bi-plus-circle me-1" aria-hidden="true"></i>Sell
                            </a>

                            <div class="dropdown">
                                <a class="nav-link dropdown-toggle nav-user-toggle px-2" href="#" role="button"
                                    data-bs-toggle="dropdown" aria-expanded="false"
                                    aria-label="User menu">
                                    <i class="bi bi-person-circle fs-5" aria-hidden="true"></i>
                                    <span class="d-none d-xl-inline ms-1">
                                        <?= clean($_SESSION['display_name'] ?? $_SESSION['username'] ?? 'Account') ?>
                                    </span>
                                </a>
                                <ul class="dropdown-menu dropdown-menu-end dropdown-menu-dark">
                                    <li>
                                        <a class="dropdown-item" href="/dashboard.php">
                                            <i class="bi bi-speedometer2 me-2" aria-hidden="true"></i>Dashboard
                                        </a>
                                    </li>
                                    <li>
                                        <a class="dropdown-item" href="/profile.php?user=<?= clean($_SESSION['username'] ?? '') ?>">
                                            <i class="bi bi-person me-2" aria-hidden="true"></i>My Profile
                                        </a>
                                    </li>
                                    <li>
                                        <hr class="dropdown-divider">
                                    </li>
                                    <li>
                                        <a class="dropdown-item text-danger" href="/logout.php">
                                            <i class="bi bi-box-arrow-right me-2" aria-hidden="true"></i>Log Out
                                        </a>
                                    </li>
                                </ul>
                            </div>
                        </div>
                    <?php else: ?>
                        <!-- Desktop only guest actions -->
                        <div class="nav-actions d-none d-lg-flex align-items-center gap-2 flex-wrap">
                            <a class="nav-link px-2" href="/login.php">Login</a>
                            <a class="btn btn-accent btn-sm nav-sell-btn" href="/create-listing.php">
                                <i class="bi bi-plus-circle" aria-hidden="true"></i>
                                <span class="d-none d-sm-inline ms-1">Sell</span>
                            </a>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </nav>

    <!-- Flash message -->
    <?php if ($flash): ?>
        <div class="container mt-3" role="alert" aria-live="polite">
            <div class="alert alert-<?= htmlspecialchars($flash['type'], ENT_QUOTES, 'UTF-8') ?> alert-dismissible fade show flash-message" role="alert">
                <?= htmlspecialchars($flash['message'], ENT_QUOTES, 'UTF-8') ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close alert"></button>
            </div>
        </div>
    <?php endif; ?>

    <!-- Main content wrapper -->
    <main id="main-content">