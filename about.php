<?php
require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/sanitize.php';

startSession();
$pageTitle = 'About Us';
require_once __DIR__ . '/includes/header.php';
?>

<!-- Page Header -->
<section class="page-header py-5" aria-labelledby="aboutHeading">
    <div class="container text-center">
        <h1 id="aboutHeading" class="display-5 fw-bold text-white">About MerchVault</h1>
        <p class="lead text-white-75 mt-2">The music community's marketplace for merch and tickets.</p>
    </div>
</section>

<!-- Our Story -->
<section class="container py-5 fade-in-section" aria-labelledby="storyHeading">
    <div class="row justify-content-center">
        <div class="col-lg-8">
            <h2 id="storyHeading" class="section-heading mb-3">Our Story</h2>
            <p class="text-muted">
                MerchVault was born out of a simple frustration: finding a good home for concert tees,
                rare vinyls, and unwanted event tickets meant navigating generic marketplaces that had no
                understanding of music culture.
            </p>
            <p class="text-muted">
                We built a dedicated space where fans can buy, sell, and discover music merchandise from
                other fans — with genre tags, artist filters, and ticket-specific listing fields that
                actually make sense.
            </p>
            <p class="text-muted">
                Whether you scored an extra pair of concert tickets, have a vintage band tee you're ready
                to pass on, or are hunting for a rare pressing of your favourite album, MerchVault is your
                community marketplace.
            </p>
        </div>
    </div>
</section>

<!-- Team Section -->
<section class="py-5 team-section fade-in-section" aria-labelledby="teamHeading">
    <div class="container">
        <h2 id="teamHeading" class="section-heading text-center mb-5">Meet the Team</h2>
        <div class="row row-cols-1 row-cols-sm-2 row-cols-lg-5 g-4 justify-content-center">

            <?php
            // Team members — update names/roles as needed
            $team = [
                ['name' => 'Team Member 1', 'role' => 'Full-Stack Developer', 'icon' => 'bi-code-slash'],
                ['name' => 'Team Member 2', 'role' => 'Frontend & UI/UX',     'icon' => 'bi-palette'],
                ['name' => 'Team Member 3', 'role' => 'Backend & Database',   'icon' => 'bi-database'],
                ['name' => 'Team Member 4', 'role' => 'Security & Testing',   'icon' => 'bi-shield-check'],
                ['name' => 'Team Member 5', 'role' => 'Project Manager',      'icon' => 'bi-kanban'],
            ];
            foreach ($team as $member):
            ?>
            <div class="col">
                <div class="team-card card text-center h-100 p-4 border-0">
                    <div class="team-avatar mx-auto mb-3">
                        <i class="bi <?= $member['icon'] ?>" aria-hidden="true"></i>
                    </div>
                    <h3 class="h6 fw-bold mb-1"><?= htmlspecialchars($member['name'], ENT_QUOTES, 'UTF-8') ?></h3>
                    <p class="text-muted small mb-0"><?= htmlspecialchars($member['role'], ENT_QUOTES, 'UTF-8') ?></p>
                </div>
            </div>
            <?php endforeach; ?>

        </div>
        <p class="text-center text-muted small mt-4">
            INF1005 Web Systems &amp; Technologies &mdash; Lab P4, Team 10
        </p>
    </div>
</section>

<!-- Features Highlight -->
<section class="container py-5 fade-in-section" aria-labelledby="featuresHeading">
    <h2 id="featuresHeading" class="section-heading text-center mb-5">What We Offer</h2>
    <div class="row row-cols-1 row-cols-md-3 g-4">
        <div class="col">
            <div class="feature-card card h-100 p-4 border-0 text-center">
                <i class="bi bi-tags feature-icon mb-3" aria-hidden="true"></i>
                <h3 class="h5 fw-bold">Smart Tagging</h3>
                <p class="text-muted small">Tag every listing with genre, artist, and category so buyers find exactly what they're looking for.</p>
            </div>
        </div>
        <div class="col">
            <div class="feature-card card h-100 p-4 border-0 text-center">
                <i class="bi bi-ticket-perforated feature-icon mb-3" aria-hidden="true"></i>
                <h3 class="h5 fw-bold">Ticket Reselling</h3>
                <p class="text-muted small">List event tickets with venue, seat section, event date and quantity — all the info buyers need.</p>
            </div>
        </div>
        <div class="col">
            <div class="feature-card card h-100 p-4 border-0 text-center">
                <i class="bi bi-shield-check feature-icon mb-3" aria-hidden="true"></i>
                <h3 class="h5 fw-bold">Secure Platform</h3>
                <p class="text-muted small">All transactions and data are protected with industry-standard security practices.</p>
            </div>
        </div>
    </div>
</section>

<!-- FAQ -->
<section class="container py-5 pb-5 fade-in-section" aria-labelledby="faqHeading">
    <div class="row justify-content-center">
        <div class="col-lg-8">
            <h2 id="faqHeading" class="section-heading text-center mb-4">Frequently Asked Questions</h2>

            <div class="accordion faq-accordion" id="faqAccordion">

                <div class="accordion-item">
                    <h3 class="accordion-header" id="faq1Head">
                        <button class="accordion-button collapsed" type="button"
                                data-bs-toggle="collapse" data-bs-target="#faq1"
                                aria-expanded="false" aria-controls="faq1">
                            Is MerchVault free to use?
                        </button>
                    </h3>
                    <div id="faq1" class="accordion-collapse collapse" aria-labelledby="faq1Head" data-bs-parent="#faqAccordion">
                        <div class="accordion-body text-muted">
                            Yes — creating an account, browsing, and listing items are completely free.
                        </div>
                    </div>
                </div>

                <div class="accordion-item">
                    <h3 class="accordion-header" id="faq2Head">
                        <button class="accordion-button collapsed" type="button"
                                data-bs-toggle="collapse" data-bs-target="#faq2"
                                aria-expanded="false" aria-controls="faq2">
                            Can I sell both physical merch and event tickets?
                        </button>
                    </h3>
                    <div id="faq2" class="accordion-collapse collapse" aria-labelledby="faq2Head" data-bs-parent="#faqAccordion">
                        <div class="accordion-body text-muted">
                            Absolutely. When you create a listing, choose "Event Tickets" as the category
                            to unlock ticket-specific fields like event date, venue, and seat details.
                        </div>
                    </div>
                </div>

                <div class="accordion-item">
                    <h3 class="accordion-header" id="faq3Head">
                        <button class="accordion-button collapsed" type="button"
                                data-bs-toggle="collapse" data-bs-target="#faq3"
                                aria-expanded="false" aria-controls="faq3">
                            How do I update the status of my listing?
                        </button>
                    </h3>
                    <div id="faq3" class="accordion-collapse collapse" aria-labelledby="faq3Head" data-bs-parent="#faqAccordion">
                        <div class="accordion-body text-muted">
                            Head to your Dashboard → My Listings. Each listing has a status dropdown
                            (Available / Reserved / Sold) you can update instantly.
                        </div>
                    </div>
                </div>

                <div class="accordion-item">
                    <h3 class="accordion-header" id="faq4Head">
                        <button class="accordion-button collapsed" type="button"
                                data-bs-toggle="collapse" data-bs-target="#faq4"
                                aria-expanded="false" aria-controls="faq4">
                            How many photos can I upload per listing?
                        </button>
                    </h3>
                    <div id="faq4" class="accordion-collapse collapse" aria-labelledby="faq4Head" data-bs-parent="#faqAccordion">
                        <div class="accordion-body text-muted">
                            Up to 5 images per listing. Supported formats: JPEG, PNG, WebP, and GIF.
                            Maximum 5 MB per image.
                        </div>
                    </div>
                </div>

                <div class="accordion-item">
                    <h3 class="accordion-header" id="faq5Head">
                        <button class="accordion-button collapsed" type="button"
                                data-bs-toggle="collapse" data-bs-target="#faq5"
                                aria-expanded="false" aria-controls="faq5">
                            How is my data kept secure?
                        </button>
                    </h3>
                    <div id="faq5" class="accordion-collapse collapse" aria-labelledby="faq5Head" data-bs-parent="#faqAccordion">
                        <div class="accordion-body text-muted">
                            Passwords are hashed using bcrypt. All database queries use prepared statements
                            to prevent SQL injection. Every form is protected with CSRF tokens. All user
                            content is sanitised to prevent cross-site scripting (XSS).
                        </div>
                    </div>
                </div>

            </div>
        </div>
    </div>
</section>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
