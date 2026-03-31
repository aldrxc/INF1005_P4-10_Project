<?php
require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/csrf.php';
require_once __DIR__ . '/includes/sanitize.php';

startSession();

$pageTitle = 'Home — Buy & Sell Music Merch';
$pdo = getDB();

// Featured listings: latest 8 available
$stmt = $pdo->prepare("
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
    WHERE l.status = 'available'
    ORDER BY l.created_at DESC
    LIMIT 8
");
$stmt->execute();
$featuredListings = $stmt->fetchAll();

// Stats
$stats = $pdo->query("
    SELECT
        (SELECT COUNT(*) FROM listings WHERE status = 'available') AS total_listings,
        (SELECT COUNT(*) FROM users WHERE is_active = 1) AS total_users,
        (SELECT COUNT(*) FROM categories) AS total_categories
")->fetch();

require_once __DIR__ . '/includes/header.php';
?>

<!-- Hero Section -->
<section class="hero-section" aria-labelledby="heroHeading">
    <div class="hero-overlay"></div>
    <div class="container hero-content text-center">
        <h1 id="heroHeading" class="display-4 fw-bold text-white mb-3">
            Buy &amp; Sell Music Merch
        </h1>
        <p class="lead text-white-75 mb-4 mx-auto" style="max-width:560px;">
            Discover band tees, vinyl records, concert posters, instruments, accessories
            and event tickets — all in one place.
        </p>
        <div class="d-flex flex-column flex-sm-row gap-3 justify-content-center">
            <a href="/browse.php" class="btn btn-accent btn-lg px-4 fw-semibold">
                <i class="bi bi-search me-2" aria-hidden="true"></i>Browse Listings
            </a>
            <?php if (isLoggedIn()): ?>
                <a href="/create-listing.php" class="btn btn-outline-light btn-lg px-4">
                    <i class="bi bi-plus-circle me-2" aria-hidden="true"></i>Sell an Item
                </a>
            <?php else: ?>
                <a href="/register.php" class="btn btn-outline-light btn-lg px-4">
                    <i class="bi bi-person-plus me-2" aria-hidden="true"></i>Start Selling
                </a>
            <?php endif; ?>
        </div>
    </div>
</section>

<!-- Stats Strip -->
<section class="stats-strip py-3" aria-label="Platform statistics">
    <div class="container">
        <div class="row text-center gy-2">
            <div class="col-4">
                <div class="stat-number"><?= number_format((int)$stats['total_listings']) ?></div>
                <div class="stat-label">Active Listings</div>
            </div>
            <div class="col-4">
                <div class="stat-number"><?= number_format((int)$stats['total_users']) ?></div>
                <div class="stat-label">Members</div>
            </div>
            <div class="col-4">
                <div class="stat-number"><?= (int)$stats['total_categories'] ?></div>
                <div class="stat-label">Categories</div>
            </div>
        </div>
    </div>
</section>

<!-- Category Quicklinks -->
<section class="container py-5 fade-in-section" aria-labelledby="categoriesHeading">
    <h2 id="categoriesHeading" class="section-heading text-center mb-4">Shop by Category</h2>
    <div class="row row-cols-2 row-cols-sm-3 row-cols-lg-6 g-3">
        <div class="col">
            <a href="/browse.php?category=band-tees" class="category-card card text-center text-decoration-none h-100 p-3">
                <i class="fa-solid fa-shirt category-icon" aria-hidden="true"></i>
                <div class="category-name mt-2">Band Tees</div>
            </a>
        </div>
        <div class="col">
            <a href="/browse.php?category=vinyl-records" class="category-card card text-center text-decoration-none h-100 p-3">
                <i class="bi bi-disc category-icon" aria-hidden="true"></i>
                <div class="category-name mt-2">Vinyl Records</div>
            </a>
        </div>
        <div class="col">
            <a href="/browse.php?category=concert-posters" class="category-card card text-center text-decoration-none h-100 p-3">
                <i class="bi bi-image category-icon" aria-hidden="true"></i>
                <div class="category-name mt-2">Concert Posters</div>
            </a>
        </div>
        <div class="col">
            <a href="/browse.php?category=instruments" class="category-card card text-center text-decoration-none h-100 p-3">
                <i class="bi bi-music-note-beamed category-icon" aria-hidden="true"></i>
                <div class="category-name mt-2">Instruments</div>
            </a>
        </div>
        <div class="col">
            <a href="/browse.php?category=accessories" class="category-card card text-center text-decoration-none h-100 p-3">
                <i class="bi bi-bag category-icon" aria-hidden="true"></i>
                <div class="category-name mt-2">Accessories</div>
            </a>
        </div>
        <div class="col">
            <a href="/browse.php?category=event-tickets" class="category-card card text-center text-decoration-none h-100 p-3">
                <i class="bi bi-ticket-perforated category-icon" aria-hidden="true"></i>
                <div class="category-name mt-2">Event Tickets</div>
            </a>
        </div>
    </div>
</section>

<!-- Featured Listings -->
<section class="container py-5 fade-in-section" aria-labelledby="featuredHeading">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2 id="featuredHeading" class="section-heading mb-0">Latest Listings</h2>
        <a href="/browse.php" class="btn btn-sm btn-outline-accent">
            View All <i class="bi bi-arrow-right ms-1" aria-hidden="true"></i>
        </a>
    </div>

    <?php if (empty($featuredListings)): ?>
        <div class="text-center py-5 text-muted">
            <i class="bi bi-shop display-4" aria-hidden="true"></i>
            <p class="mt-3">No listings yet. Be the first to sell!</p>
            <a href="<?= isLoggedIn() ? '/create-listing.php' : '/register.php' ?>"
                class="btn btn-accent">
                <?= isLoggedIn() ? 'Create a Listing' : 'Sign Up &amp; Sell' ?>
            </a>
        </div>
    <?php else: ?>
        <div class="row row-cols-1 row-cols-sm-2 row-cols-md-3 row-cols-xl-4 g-4">
            <?php foreach ($featuredListings as $listing): ?>
                <div class="col">
                    <?php include __DIR__ . '/includes/listing-card.php'; ?>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</section>

<!-- How It Works -->
<section class="how-it-works py-5 fade-in-section" aria-labelledby="howHeading">
    <div class="container">
        <h2 id="howHeading" class="section-heading mb-5">Sell and buy every kinda thing on MerchVault</h2>
        <div class="row gy-4 text-center">
            <div class="col-md-4">
                <div class="hiw-step">
                    <div class="hiw-icon mb-3">
                        <i class="bi bi-person-plus" aria-hidden="true"></i>
                    </div>
                    <h3 class="h5 fw-bold">1. Create an Account</h3>
                    <p class="text-muted small">Sign up for free. Every member is both a buyer and a seller.</p>
                </div>
            </div>
            <div class="col-md-4">
                <div class="hiw-step">
                    <div class="hiw-icon mb-3">
                        <i class="bi bi-tags" aria-hidden="true"></i>
                    </div>
                    <h3 class="h5 fw-bold">2. List Your Merch</h3>
                    <p class="text-muted small">Upload photos, add tags for genre and artist, set your price and post.</p>
                </div>
            </div>
            <div class="col-md-4">
                <div class="hiw-step">
                    <div class="hiw-icon mb-3">
                        <i class="bi bi-bag-check" aria-hidden="true"></i>
                    </div>
                    <h3 class="h5 fw-bold">3. Buy &amp; Sell</h3>
                    <p class="text-muted small">Browse the marketplace, add items to your cart, and complete your purchase securely.</p>
                </div>
            </div>
        </div>
        <div class="text-center mt-4">
            <a href="/register.php" class="btn btn-accent btn-lg px-5">
                Get Started — It's Free
            </a>
        </div>
    </div>
</section>

<!-- Review Section (Not Dyanmic maybe in the future -->
<section class="community-section py-5">
    <div class="container">

        <h2 class="section-title mb-5">
            Transact with a trusted local community
        </h2>

        <div class="row g-4">

            <!-- Card -->
            <div class="col-md-6 col-lg-3">
                <div class="testimonial-card">
                    <div class="stars">★★★★★</div>
                    <h3 class="h5">Awesome community</h3>
                    <p>
                        Safe, reliable & easy to use user interface. Overall
                        an awesome community to be in! 😊
                    </p>
                    <span class="username">@md.helmi</span>
                </div>
            </div>

            <!-- Card -->
            <div class="col-md-6 col-lg-3">
                <div class="testimonial-card">
                    <div class="stars">★★★★★</div>
                    <h3 class="h5">Decluttering and bargain finds</h3>
                    <p>
                        Love that we can make the most out of items. Helps me
                        to clear my things without the guilt of throwing and
                        purchase keeping the environment in mind.
                    </p>
                    <span class="username">@chrischross</span>
                </div>
            </div>

            <!-- Card -->
            <div class="col-md-6 col-lg-3">
                <div class="testimonial-card">
                    <div class="stars">★★★★★</div>
                    <h3 class="h5">Great for the earth</h3>
                    <p>
                        Great way to save money and earth resources by buying
                        secondhand; and giving new owners to things that are
                        still working in great condition.
                    </p>
                    <span class="username">@mint_sg</span>
                </div>
            </div>

            <!-- Card -->
            <div class="col-md-6 col-lg-3">
                <div class="testimonial-card">
                    <div class="stars">★★★★★</div>
                    <h3 class="h5">Easy to buy and sell</h3>
                    <p>
                        Easy and convenient platform. It's convenient to buy
                        and sell stuff. Get to chat directly with other users.
                    </p>
                    <span class="username">@joeboxer</span>
                </div>
            </div>

        </div>
    </div>
</section>

<section class="promo-section">
    <div class="container">
        <div class="row align-items-center">

            <!-- Left Side (Placeholder Image) -->
            <div class="col-lg-5 text-center mb-4 mb-lg-0 justify-content-center">
                <!-- <div class="phone-placeholder">
                    Phone / App Preview
                </div> -->
                <div class="phone-mockup"></div>
            </div>

            <!-- Right Side (Text) -->
            <div class="col-lg-7 text-center text-lg-start">

                <h2 class="promo-title">
                    Everyone Wins on MerchVault
                </h2>

                <p class="promo-subtitle">
                    Buy, sell and discover music merch from fans like you.
                    List your items, find rare collectibles and connect with
                    the community in one place.
                </p>

                <div class="promo-buttons d-flex flex-wrap gap-3 align-items-center">

                    <a href="#">
                        <img
                            src="assets/images/apple_store.svg"
                            alt="Download on the App Store"
                            class="store-badge">
                    </a>

                    <a href="#">
                        <img
                            src="assets/images/gplay.svg"
                            alt="Get it on Google Play"
                            class="store-badge">
                    </a>

                </div>

            </div>

        </div>
    </div>
</section>

<?php require_once __DIR__ . '/includes/footer.php'; ?>