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
            <h2 id="storyHeading" class="section-heading mb-3 text-center">Our Story</h2>
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

            <div class="col">
                <div class="team-card card text-center h-100 p-4 border-0">
                    <div class="team-avatar mx-auto mb-3">
                        <i class="bi bi-code-slash" aria-hidden="true"></i>
                    </div>
                    <h3 class="h6 fw-bold mb-1 text-hotpink">Ho Zhi Jin Ivan</h3>
                    <p class="text-muted small mb-0">Full-Stack Developer</p>
                </div>
            </div>
            <div class="col">
                <div class="team-card card text-center h-100 p-4 border-0">
                    <div class="team-avatar mx-auto mb-3">
                        <i class="bi bi-palette" aria-hidden="true"></i>
                    </div>
                    <h3 class="h6 fw-bold mb-1 text-hotpink">Long Chay Han</h3>
                    <p class="text-muted small mb-0">Backend &amp; Database</p>
                </div>
            </div>
            <div class="col">
                <div class="team-card card text-center h-100 p-4 border-0">
                    <div class="team-avatar mx-auto mb-3">
                        <i class="bi bi-shield-check" aria-hidden="true"></i>
                    </div>
                    <h3 class="h6 fw-bold mb-1 text-hotpink">Wong Zhen Jie</h3>
                    <p class="text-muted small mb-0">Security &amp; Testing</p>
                </div>
            </div>
            <div class="col">
                <div class="team-card card text-center h-100 p-4 border-0">
                    <div class="team-avatar mx-auto mb-3">
                        <i class="bi bi-kanban" aria-hidden="true"></i>
                    </div>
                    <h3 class="h6 fw-bold mb-1 text-hotpink">Tan Sze Loke Aldric</h3>
                    <p class="text-muted small mb-0">Project Manager</p>
                </div>
            </div>
            <div class="col">
                <div class="team-card card text-center h-100 p-4 border-0">
                    <div class="team-avatar mx-auto mb-3">
                        <i class="bi bi-database" aria-hidden="true"></i>
                    </div>
                    <h3 class="h6 fw-bold mb-1 text-hotpink">Low Dong Han</h3>
                    <p class="text-muted small mb-0">Frontend &amp; UI/UX</p>
                </div>
            </div>

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
                <h3 class="h5 fw-bold text-hotpink">Smart Tagging</h3>
                <p class="text-muted small">Tag every listing with genre, artist, and category so buyers find exactly what they're looking for.</p>
            </div>
        </div>
        <div class="col">
            <div class="feature-card card h-100 p-4 border-0 text-center">
                <i class="bi bi-ticket-perforated feature-icon mb-3" aria-hidden="true"></i>
                <h3 class="h5 fw-bold text-hotpink">Ticket Reselling</h3>
                <p class="text-muted small">List event tickets with venue, seat section, event date and quantity — all the info buyers need.</p>
            </div>
        </div>
        <div class="col">
            <div class="feature-card card h-100 p-4 border-0 text-center">
                <i class="bi bi-shield-check feature-icon mb-3" aria-hidden="true"></i>
                <h3 class="h5 fw-bold text-hotpink">Secure Platform</h3>
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
                            Absolutely! When you create a listing, choose "Event Tickets" as the category
                            to unlock ticket-specific fields like event date, venue, and seat details.
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
                    <h3 class="accordion-header" id="faq3Head">
                        <button class="accordion-button collapsed" type="button"
                            data-bs-toggle="collapse" data-bs-target="#faq3"
                            aria-expanded="false" aria-controls="faq3">
                            Is international shipping available for physical merchandise?
                        </button>
                    </h3>
                    <div id="faq3" class="accordion-collapse collapse" aria-labelledby="faq3Head" data-bs-parent="#faqAccordion">
                        <div class="accordion-body text-muted">
                            No unfortunately, currently we only support shipping within Singapore. We recommend meeting locally for ticket exchanges or merch handoffs.
                        </div>
                    </div>
                </div>

                <div class="accordion-item">
                    <h3 class="accordion-header" id="faq5Head">
                        <button class="accordion-button collapsed" type="button"
                            data-bs-toggle="collapse" data-bs-target="#faq5"
                            aria-expanded="false" aria-controls="faq5">
                            How do I contact the support team?
                        </button>
                    </h3>
                    <div id="faq5" class="accordion-collapse collapse" aria-labelledby="faq5Head" data-bs-parent="#faqAccordion">
                        <div class="accordion-body text-muted">
                            Please send us an email at <a class="text-hotpink" href="mailto:support@MusicVault.com">support@musicvault.com</a>.
                            We will do our best to respond to you within 3-5 working days.
                        </div>
                    </div>
                </div>

            </div>
        </div>
    </div>
</section>

<?php require_once __DIR__ . '/includes/footer.php'; ?>