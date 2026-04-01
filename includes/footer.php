<?php // footer.php - included at the bottom of every page 
?>
</main><!-- /#main-content -->

<!-- back to top button -->
<button id="backToTop" class="btn btn-accent btn-sm back-to-top" aria-label="Back to top" style="display:none">
    <i class="bi bi-arrow-up" aria-hidden="true"></i>
</button>

<footer class="site-footer mt-5 py-5">
    <div class="container">
        <div class="row gy-4">
            <!-- brand -->
            <div class="col-md-4">
                <a href="/index.php" class="d-flex align-items-center gap-2 text-decoration-none mb-2">
                    <i class="bi bi-music-note-list fs-4 text-accent" aria-hidden="true"></i>
                    <span class="fs-5 fw-bold text-white">MerchVault</span>
                </a>
                <p class="text-muted small">Your go-to marketplace for music merchandise and event tickets. Buy and sell with the community.</p>
            </div>

            <!-- quick links -->
            <div class="col-6 col-md-2 offset-md-1">
                <h2 class="h6 text-hotpink fw-semibold mb-3">Explore</h2>
                <ul class="list-unstyled small">
                    <li><a href="/browse.php" class="footer-link">Browse Listings</a></li>
                    <li><a href="/browse.php?category=event-tickets" class="footer-link">Event Tickets</a></li>
                    <li><a href="/browse.php?category=vinyl-records" class="footer-link">Vinyl Records</a></li>
                    <li><a href="/browse.php?category=band-tees" class="footer-link">Band Tees</a></li>
                </ul>
            </div>

            <div class="col-6 col-md-2">
                <h2 class="h6 text-hotpink fw-semibold mb-3">Account</h2>
                <ul class="list-unstyled small">
                    <?php if (isLoggedIn()): ?>
                        <li><a href="/dashboard.php" class="footer-link">Dashboard</a></li>
                        <li><a href="/create-listing.php" class="footer-link">Sell an Item</a></li>
                        <li><a href="/cart.php" class="footer-link">Cart</a></li>
                    <?php else: ?>
                        <li><a href="/register.php" class="footer-link">Sign Up</a></li>
                        <li><a href="/login.php" class="footer-link">Log In</a></li>
                    <?php endif; ?>
                    <li><a href="/about.php" class="footer-link">About Us</a></li>
                </ul>
            </div>

            <div class="col-md-3">
                <h2 class="h6 text-hotpink fw-semibold mb-3">INF1005 WST</h2>
                <p class="text-muted small mb-1">Lab P4 — Team 10</p>
                <p class="text-muted small">Built with Bootstrap 5, PHP &amp; MySQL</p>
            </div>
        </div>

        <hr class="footer-divider my-4">

        <div class="d-flex flex-column flex-sm-row justify-content-between align-items-center small text-muted">
            <p class="mb-1 mb-sm-0">&copy; <?= date('Y') ?> MerchVault. All rights reserved.</p>
            <p class="mb-0">INF1005 Web Systems &amp; Technologies Project</p>
        </div>
    </div>
</footer>

<!-- bootstrap 5 JS Bundle (includes Popper) -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"
    integrity="sha384-YvpcrYf0tY3lHB60NNkmXc5s9fDVZLESaAA55NDzOxhy9GkcIdslK1eN7N6jIeHz"
    crossorigin="anonymous"></script>

<!-- JS jquery 4.0.0 minified build bundle  -->
<script src="https://code.jquery.com/jquery-4.0.0.min.js"
    integrity="sha256-OaVG6prZf4v69dPg6PhVattBXkcOWQB62pdZ3ORyrao="
    crossorigin="anonymous"></script>

<!-- global JS -->
<script src="/assets/js/main.js"></script>
</body>

</html>