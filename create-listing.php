<?php
require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/csrf.php';
require_once __DIR__ . '/includes/sanitize.php';

startSession();
requireLogin();

$pageTitle = 'Create a Listing';
$pdo       = getDB();

$categories = $pdo->query("SELECT category_id, name, slug FROM categories ORDER BY name")->fetchAll();
$genres     = $pdo->query("SELECT genre_id, name FROM genres ORDER BY name")->fetchAll();

$errors = [];
$old    = [];
if (!empty($_SESSION['listing_errors'])) {
    $errors = $_SESSION['listing_errors'];
    $old    = $_SESSION['listing_old'] ?? [];
    unset($_SESSION['listing_errors'], $_SESSION['listing_old']);
}

generateCsrfToken();
require_once __DIR__ . '/includes/header.php';
?>

<div class="container py-4">
    <div class="row justify-content-center">
        <div class="col-12 col-lg-9 col-xl-8">

            <h1 class="h3 fw-bold mb-4">
                <i class="bi bi-plus-circle text-accent me-2" aria-hidden="true"></i>Create a Listing
            </h1>

            <?php if (!empty($errors['general'])): ?>
                <div class="alert alert-danger" role="alert"><?= clean($errors['general']) ?></div>
            <?php endif; ?>

            <form method="POST" action="/handlers/listing-handler.php"
                enctype="multipart/form-data" novalidate id="createListingForm">
                <?= getCsrfField() ?>
                <input type="hidden" name="action" value="create">

                <!-- basic info -->
                <div class="card form-section-card mb-4">
                    <div class="card-body p-4">
                        <h2 class="h5 fw-semibold mb-3 text-hotpink">Basic Information</h2>

                        <!-- title -->
                        <div class="mb-3">
                            <label for="title" class="form-label">Listing Title <span class="text-accent" aria-hidden="true">*</span></label>
                            <input type="text" class="form-control <?= isset($errors['title']) ? 'is-invalid' : '' ?>"
                                id="title" name="title" required maxlength="200"
                                placeholder="e.g. Metallica &quot;Black Album&quot; Vintage Tee — Size L"
                                value="<?= clean($old['title'] ?? '') ?>">
                            <?php if (isset($errors['title'])): ?><div class="invalid-feedback"><?= clean($errors['title']) ?></div><?php endif; ?>
                        </div>

                        <!-- category -->
                        <div class="mb-3">
                            <label for="category_id" class="form-label">Category <span class="text-accent" aria-hidden="true">*</span></label>
                            <select class="form-select <?= isset($errors['category_id']) ? 'is-invalid' : '' ?>"
                                id="category_id" name="category_id" required aria-describedby="categoryHelp">
                                <option value="">Select a category…</option>
                                <?php foreach ($categories as $cat): ?>
                                    <option value="<?= (int)$cat['category_id'] ?>"
                                        data-slug="<?= clean($cat['slug']) ?>"
                                        <?= ((int)($old['category_id'] ?? 0) === (int)$cat['category_id']) ? 'selected' : '' ?>>
                                        <?= clean($cat['name']) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                            <div id="categoryHelp" class="form-text">Choosing "Event Tickets" unlocks ticket-specific fields.</div>
                            <?php if (isset($errors['category_id'])): ?><div class="invalid-feedback"><?= clean($errors['category_id']) ?></div><?php endif; ?>
                        </div>

                        <!-- genre & artist (side by side) -->
                        <div class="row g-3 mb-3">
                            <div class="col-sm-6">
                                <label for="genre_id" class="form-label">Genre</label>
                                <select class="form-select" id="genre_id" name="genre_id" aria-label="Genre">
                                    <option value="">Select genre (optional)</option>
                                    <?php foreach ($genres as $g): ?>
                                        <option value="<?= (int)$g['genre_id'] ?>"
                                            <?= ((int)($old['genre_id'] ?? 0) === (int)$g['genre_id']) ? 'selected' : '' ?>>
                                            <?= clean($g['name']) ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-sm-6">
                                <label for="artist_band" class="form-label">Artist / Band</label>
                                <input type="text" class="form-control"
                                    id="artist_band" name="artist_band" maxlength="150"
                                    placeholder="e.g. Metallica"
                                    value="<?= clean($old['artist_band'] ?? '') ?>">
                            </div>
                        </div>

                        <!-- price -->
                        <div class="mb-3">
                            <label for="price" class="form-label">Price (S$) <span class="text-accent" aria-hidden="true">*</span></label>
                            <div class="input-group">
                                <span class="input-group-text">S$</span>
                                <input type="number" class="form-control <?= isset($errors['price']) ? 'is-invalid' : '' ?>"
                                    id="price" name="price" required min="0.01" step="0.01"
                                    placeholder="0.00"
                                    value="<?= clean($old['price'] ?? '') ?>">
                                <?php if (isset($errors['price'])): ?><div class="invalid-feedback"><?= clean($errors['price']) ?></div><?php endif; ?>
                            </div>
                        </div>

                        <!-- description -->
                        <div class="mb-0">
                            <label for="description" class="form-label">Description <span class="text-accent" aria-hidden="true">*</span></label>
                            <textarea class="form-control <?= isset($errors['description']) ? 'is-invalid' : '' ?>"
                                id="description" name="description" required
                                rows="4" maxlength="1000"
                                placeholder="Describe your item: condition, size, any defects, what's included…"
                                aria-describedby="descCounter"><?= clean($old['description'] ?? '') ?></textarea>
                            <div class="d-flex justify-content-between mt-1">
                                <?php if (isset($errors['description'])): ?><div class="invalid-feedback d-block"><?= clean($errors['description']) ?></div><?php else: ?><div></div><?php endif; ?>
                                <small id="descCounter" class="text-muted"><span id="descCount">0</span>/1000</small>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- merch fields (hidden when ticket selected) -->
                <div class="card form-section-card mb-4" id="merchFields">
                    <div class="card-body p-4">
                        <h2 class="h5 fw-semibold mb-3 text-hotpink">Merch Details</h2>

                        <div class="row g-3">
                            <!-- condition -->
                            <div class="col-sm-6">
                                <label for="condition_type" class="form-label">Condition</label>
                                <select class="form-select" id="condition_type" name="condition_type" aria-label="Item condition">
                                    <option value="">Select condition…</option>
                                    <option value="new" <?= ($old['condition_type'] ?? '') === 'new'      ? 'selected' : '' ?>>New</option>
                                    <option value="like_new" <?= ($old['condition_type'] ?? '') === 'like_new' ? 'selected' : '' ?>>Like New</option>
                                    <option value="good" <?= ($old['condition_type'] ?? '') === 'good'     ? 'selected' : '' ?>>Good</option>
                                    <option value="fair" <?= ($old['condition_type'] ?? '') === 'fair'     ? 'selected' : '' ?>>Fair</option>
                                    <option value="poor" <?= ($old['condition_type'] ?? '') === 'poor'     ? 'selected' : '' ?>>Poor</option>
                                </select>
                            </div>

                            <!-- size (only for apparel - shown/hidden by JS) -->
                            <div class="col-sm-6" id="sizeField" style="display:none">
                                <label for="size" class="form-label">Size</label>
                                <select class="form-select" id="size" name="size" aria-label="Clothing size">
                                    <option value="">Select size…</option>
                                    <?php foreach (['XS', 'S', 'M', 'L', 'XL', 'XXL'] as $sz): ?>
                                        <option value="<?= $sz ?>" <?= ($old['size'] ?? '') === $sz ? 'selected' : '' ?>><?= $sz ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- ticket fields (hidden unless event tickets selected) -->
                <div class="card form-section-card mb-4" id="ticketFields" style="display:none">
                    <div class="card-body p-4">
                        <h2 class="h5 fw-semibold mb-3 text-hotpink">
                            <i class="bi bi-ticket-perforated text-accent me-2" aria-hidden="true"></i>Event / Ticket Details
                        </h2>

                        <div class="row g-3">
                            <div class="col-md-8">
                                <label for="event_name" class="form-label">Event Name <span class="text-accent" aria-hidden="true">*</span></label>
                                <input type="text" class="form-control <?= isset($errors['event_name']) ? 'is-invalid' : '' ?>"
                                    id="event_name" name="event_name" maxlength="200"
                                    placeholder="e.g. Coldplay Music of the Spheres World Tour"
                                    value="<?= clean($old['event_name'] ?? '') ?>">
                                <?php if (isset($errors['event_name'])): ?><div class="invalid-feedback"><?= clean($errors['event_name']) ?></div><?php endif; ?>
                            </div>
                            <div class="col-md-4">
                                <label for="event_date" class="form-label">Event Date <span class="text-accent" aria-hidden="true">*</span></label>
                                <input type="date" class="form-control <?= isset($errors['event_date']) ? 'is-invalid' : '' ?>"
                                    id="event_date" name="event_date"
                                    value="<?= clean($old['event_date'] ?? '') ?>">
                                <?php if (isset($errors['event_date'])): ?><div class="invalid-feedback"><?= clean($errors['event_date']) ?></div><?php endif; ?>
                            </div>
                            <div class="col-md-8">
                                <label for="venue_name" class="form-label">Venue Name <span class="text-accent" aria-hidden="true">*</span></label>
                                <input type="text" class="form-control <?= isset($errors['venue_name']) ? 'is-invalid' : '' ?>"
                                    id="venue_name" name="venue_name" maxlength="200"
                                    placeholder="e.g. Singapore National Stadium"
                                    value="<?= clean($old['venue_name'] ?? '') ?>">
                                <?php if (isset($errors['venue_name'])): ?><div class="invalid-feedback"><?= clean($errors['venue_name']) ?></div><?php endif; ?>
                            </div>
                            <div class="col-md-4">
                                <label for="venue_city" class="form-label">City <span class="text-accent" aria-hidden="true">*</span></label>
                                <input type="text" class="form-control"
                                    id="venue_city" name="venue_city" maxlength="100"
                                    placeholder="e.g. Singapore"
                                    value="<?= clean($old['venue_city'] ?? '') ?>">
                            </div>
                            <div class="col-sm-4">
                                <label for="seat_section" class="form-label">Section</label>
                                <input type="text" class="form-control" id="seat_section" name="seat_section"
                                    maxlength="50" placeholder="e.g. Cat 1 / Floor"
                                    value="<?= clean($old['seat_section'] ?? '') ?>">
                            </div>
                            <div class="col-sm-4">
                                <label for="seat_row" class="form-label">Row</label>
                                <input type="text" class="form-control" id="seat_row" name="seat_row"
                                    maxlength="20" placeholder="e.g. D"
                                    value="<?= clean($old['seat_row'] ?? '') ?>">
                            </div>
                            <div class="col-sm-4">
                                <label for="seat_number" class="form-label">Seat Number(s)</label>
                                <input type="text" class="form-control" id="seat_number" name="seat_number"
                                    maxlength="50" placeholder="e.g. 12, 13"
                                    value="<?= clean($old['seat_number'] ?? '') ?>">
                            </div>
                            <div class="col-sm-4">
                                <label for="quantity" class="form-label">Quantity</label>
                                <input type="number" class="form-control" id="quantity" name="quantity"
                                    min="1" max="99" value="<?= clean($old['quantity'] ?? '1') ?>">
                            </div>
                            <div class="col-sm-8 d-flex align-items-end pb-1">
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" id="is_e_ticket"
                                        name="is_e_ticket" value="1"
                                        <?= !empty($old['is_e_ticket']) ? 'checked' : 'checked' ?>>
                                    <label class="form-check-label" for="is_e_ticket">
                                        This is an e-ticket (digital delivery)
                                    </label>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- image upload -->
                <div class="card form-section-card mb-4">
                    <div class="card-body p-4">
                        <h2 class="h5 fw-semibold mb-1 text-hotpink">Photos</h2>
                        <p class="text-muted small mb-3">Up to 5 images. JPEG, PNG, WebP, GIF. Max 5 MB each.</p>

                        <?php if (isset($errors['images'])): ?>
                            <div class="alert alert-danger py-2 small"><?= clean($errors['images']) ?></div>
                        <?php endif; ?>

                        <!-- drop zone -->
                        <div id="dropZone" class="drop-zone" tabindex="0" role="region"
                            aria-label="Drop images here or click to select files">
                            <i class="bi bi-cloud-arrow-up drop-zone-icon" aria-hidden="true"></i>
                            <p class="mb-1 fw-semibold">Drag &amp; drop images here</p>
                            <p class="text-muted small mb-2">or</p>
                            <label for="images" class="btn btn-sm btn-outline-secondary" style="cursor:pointer">
                                Browse Files
                            </label>
                            <input type="file" id="images" name="images[]"
                                accept="image/jpeg,image/png,image/webp,image/gif"
                                multiple class="d-none"
                                aria-label="Upload listing images">
                        </div>

                        <!-- preview grid -->
                        <div id="imagePreviewGrid" class="image-preview-grid mt-3 d-none">
                            <!-- thumbnails rendered by JS -->
                        </div>
                        <p class="text-muted small mt-1 mb-0" id="imageCountHint" aria-live="polite"></p>
                    </div>
                </div>

                <!-- submit -->
                <div class="d-flex gap-3 justify-content-end">
                    <a href="/browse.php" class="btn btn-outline-secondary">Cancel</a>
                    <button type="submit" class="btn btn-accent px-4 fw-semibold">
                        <i class="bi bi-check-circle me-2" aria-hidden="true"></i>Post Listing
                    </button>
                </div>

            </form>
        </div>
    </div>
</div>

<script src="/assets/js/create-listing.js" defer></script>
<?php require_once __DIR__ . '/includes/footer.php'; ?>