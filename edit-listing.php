<?php
require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/csrf.php';
require_once __DIR__ . '/includes/sanitize.php';

startSession();
requireLogin();

$listingId = sanitizeInt($_GET['id'] ?? '');
if (!$listingId) {
    header('Location: /dashboard.php');
    exit;
}

$pdo = getDB();

// Fetch listing (must belong to current user)
$stmt = $pdo->prepare("
    SELECT l.*, c.slug AS category_slug
    FROM listings l
    JOIN categories c ON l.category_id = c.category_id
    WHERE l.listing_id = ? AND l.seller_id = ?
    LIMIT 1
");
$stmt->execute([$listingId, getCurrentUserId()]);
$listing = $stmt->fetch();

if (!$listing) {
    setFlash('Listing not found or you do not have permission to edit it.', 'danger');
    header('Location: /dashboard.php');
    exit;
}

// Ticket details
$ticketDetails = null;
if ($listing['category_slug'] === 'event-tickets') {
    $tStmt = $pdo->prepare("SELECT * FROM ticket_details WHERE listing_id = ? LIMIT 1");
    $tStmt->execute([$listingId]);
    $ticketDetails = $tStmt->fetch();
}

// Existing images
$imgStmt = $pdo->prepare("SELECT * FROM listing_images WHERE listing_id = ? ORDER BY is_primary DESC, sort_order ASC");
$imgStmt->execute([$listingId]);
$existingImages = $imgStmt->fetchAll();

$categories = $pdo->query("SELECT category_id, name, slug FROM categories ORDER BY name")->fetchAll();
$genres     = $pdo->query("SELECT genre_id, name FROM genres ORDER BY name")->fetchAll();

$errors = [];
$old    = array_merge($listing, $ticketDetails ?? []);
if (!empty($_SESSION['listing_errors'])) {
    $errors = $_SESSION['listing_errors'];
    $old    = array_merge($old, $_SESSION['listing_old'] ?? []);
    unset($_SESSION['listing_errors'], $_SESSION['listing_old']);
}

$pageTitle = 'Edit Listing';
generateCsrfToken();
require_once __DIR__ . '/includes/header.php';
?>

<div class="container py-4">
    <div class="row justify-content-center">
        <div class="col-12 col-lg-9 col-xl-8">

            <div class="d-flex align-items-center gap-3 mb-4">
                <h1 class="h3 fw-bold mb-0">
                    <i class="bi bi-pencil text-accent me-2" aria-hidden="true"></i>Edit Listing
                </h1>
                <a href="/listing.php?id=<?= (int)$listingId ?>" class="btn btn-sm btn-outline-secondary ms-auto">
                    View Listing
                </a>
            </div>

            <?php if (!empty($errors['general'])): ?>
                <div class="alert alert-danger" role="alert"><?= clean($errors['general']) ?></div>
            <?php endif; ?>

            <!-- Existing images management -->
            <?php if (!empty($existingImages)): ?>
                <div class="card form-section-card mb-4">
                    <div class="card-body p-4">
                        <h2 class="h5 fw-semibold mb-3">Current Photos</h2>
                        <div class="d-flex flex-wrap gap-3" role="list" aria-label="Current listing images">
                            <?php foreach ($existingImages as $img): ?>
                                <div class="existing-img-item position-relative" role="listitem">
                                    <img src="/<?= clean($img['file_path']) ?>"
                                         alt="Listing image"
                                         class="rounded" width="100" height="100"
                                         style="object-fit:cover"
                                         loading="lazy">
                                    <?php if ($img['is_primary']): ?>
                                        <span class="badge bg-accent position-absolute top-0 start-0 m-1" style="font-size:0.6rem">Primary</span>
                                    <?php endif; ?>
                                    <form method="POST" action="/handlers/delete-image-handler.php"
                                          class="position-absolute top-0 end-0 m-1">
                                        <?= getCsrfField() ?>
                                        <input type="hidden" name="image_id" value="<?= (int)$img['image_id'] ?>">
                                        <input type="hidden" name="listing_id" value="<?= (int)$listingId ?>">
                                        <button type="submit" class="btn btn-danger btn-sm p-0" style="width:22px;height:22px;font-size:0.7rem"
                                                aria-label="Remove this image"
                                                onclick="return confirm('Remove this image?')">
                                            <i class="bi bi-x" aria-hidden="true"></i>
                                        </button>
                                    </form>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>
            <?php endif; ?>

            <form method="POST" action="/handlers/listing-handler.php"
                  enctype="multipart/form-data" novalidate id="createListingForm">
                <?= getCsrfField() ?>
                <input type="hidden" name="action" value="edit">
                <input type="hidden" name="listing_id" value="<?= (int)$listingId ?>">

                <!-- Basic Info card (same structure as create-listing.php) -->
                <div class="card form-section-card mb-4">
                    <div class="card-body p-4">
                        <h2 class="h5 fw-semibold mb-3">Basic Information</h2>

                        <div class="mb-3">
                            <label for="title" class="form-label">Title <span class="text-accent" aria-hidden="true">*</span></label>
                            <input type="text" class="form-control <?= isset($errors['title']) ? 'is-invalid' : '' ?>"
                                   id="title" name="title" required maxlength="200"
                                   value="<?= clean($old['title'] ?? '') ?>">
                            <?php if (isset($errors['title'])): ?><div class="invalid-feedback"><?= clean($errors['title']) ?></div><?php endif; ?>
                        </div>

                        <div class="mb-3">
                            <label for="category_id" class="form-label">Category <span class="text-accent" aria-hidden="true">*</span></label>
                            <select class="form-select" id="category_id" name="category_id" required>
                                <option value="">Select…</option>
                                <?php foreach ($categories as $cat): ?>
                                    <option value="<?= (int)$cat['category_id'] ?>"
                                            data-slug="<?= clean($cat['slug']) ?>"
                                            <?= ((int)($old['category_id'] ?? 0) === (int)$cat['category_id']) ? 'selected' : '' ?>>
                                        <?= clean($cat['name']) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="row g-3 mb-3">
                            <div class="col-sm-6">
                                <label for="genre_id" class="form-label">Genre</label>
                                <select class="form-select" id="genre_id" name="genre_id">
                                    <option value="">Any genre</option>
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
                                <input type="text" class="form-control" id="artist_band" name="artist_band"
                                       maxlength="150" value="<?= clean($old['artist_band'] ?? '') ?>">
                            </div>
                        </div>

                        <div class="mb-3">
                            <label for="price" class="form-label">Price (S$) <span class="text-accent" aria-hidden="true">*</span></label>
                            <div class="input-group">
                                <span class="input-group-text">S$</span>
                                <input type="number" class="form-control" id="price" name="price"
                                       required min="0.01" step="0.01"
                                       value="<?= clean($old['price'] ?? '') ?>">
                            </div>
                        </div>

                        <div class="mb-0">
                            <label for="description" class="form-label">Description <span class="text-accent" aria-hidden="true">*</span></label>
                            <textarea class="form-control" id="description" name="description"
                                      required rows="4" maxlength="1000"
                                      aria-describedby="descCounter"><?= clean($old['description'] ?? '') ?></textarea>
                            <div class="text-end mt-1">
                                <small id="descCounter" class="text-muted"><span id="descCount">0</span>/1000</small>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Merch fields -->
                <div class="card form-section-card mb-4" id="merchFields">
                    <div class="card-body p-4">
                        <h2 class="h5 fw-semibold mb-3">Merch Details</h2>
                        <div class="row g-3">
                            <div class="col-sm-6">
                                <label for="condition_type" class="form-label">Condition</label>
                                <select class="form-select" id="condition_type" name="condition_type">
                                    <option value="">Select…</option>
                                    <?php foreach (['new'=>'New','like_new'=>'Like New','good'=>'Good','fair'=>'Fair','poor'=>'Poor'] as $v => $l): ?>
                                        <option value="<?= $v ?>" <?= ($old['condition_type'] ?? '') === $v ? 'selected' : '' ?>><?= $l ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-sm-6" id="sizeField" style="display:none">
                                <label for="size" class="form-label">Size</label>
                                <select class="form-select" id="size" name="size">
                                    <option value="">Select…</option>
                                    <?php foreach (['XS','S','M','L','XL','XXL'] as $sz): ?>
                                        <option value="<?= $sz ?>" <?= ($old['size'] ?? '') === $sz ? 'selected' : '' ?>><?= $sz ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Ticket fields -->
                <div class="card form-section-card mb-4" id="ticketFields" style="display:none">
                    <div class="card-body p-4">
                        <h2 class="h5 fw-semibold mb-3">
                            <i class="bi bi-ticket-perforated text-accent me-2" aria-hidden="true"></i>Event / Ticket Details
                        </h2>
                        <div class="row g-3">
                            <div class="col-md-8">
                                <label for="event_name" class="form-label">Event Name <span class="text-accent" aria-hidden="true">*</span></label>
                                <input type="text" class="form-control" id="event_name" name="event_name"
                                       maxlength="200" value="<?= clean($old['event_name'] ?? '') ?>">
                            </div>
                            <div class="col-md-4">
                                <label for="event_date" class="form-label">Event Date <span class="text-accent" aria-hidden="true">*</span></label>
                                <input type="date" class="form-control" id="event_date" name="event_date"
                                       value="<?= clean($old['event_date'] ?? '') ?>">
                            </div>
                            <div class="col-md-8">
                                <label for="venue_name" class="form-label">Venue <span class="text-accent" aria-hidden="true">*</span></label>
                                <input type="text" class="form-control" id="venue_name" name="venue_name"
                                       maxlength="200" value="<?= clean($old['venue_name'] ?? '') ?>">
                            </div>
                            <div class="col-md-4">
                                <label for="venue_city" class="form-label">City <span class="text-accent" aria-hidden="true">*</span></label>
                                <input type="text" class="form-control" id="venue_city" name="venue_city"
                                       maxlength="100" value="<?= clean($old['venue_city'] ?? '') ?>">
                            </div>
                            <div class="col-sm-4">
                                <label for="seat_section" class="form-label">Section</label>
                                <input type="text" class="form-control" id="seat_section" name="seat_section"
                                       maxlength="50" value="<?= clean($old['seat_section'] ?? '') ?>">
                            </div>
                            <div class="col-sm-4">
                                <label for="seat_row" class="form-label">Row</label>
                                <input type="text" class="form-control" id="seat_row" name="seat_row"
                                       maxlength="20" value="<?= clean($old['seat_row'] ?? '') ?>">
                            </div>
                            <div class="col-sm-4">
                                <label for="seat_number" class="form-label">Seat(s)</label>
                                <input type="text" class="form-control" id="seat_number" name="seat_number"
                                       maxlength="50" value="<?= clean($old['seat_number'] ?? '') ?>">
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
                                           <?= !empty($old['is_e_ticket']) ? 'checked' : '' ?>>
                                    <label class="form-check-label" for="is_e_ticket">E-ticket (digital)</label>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Add more images -->
                <div class="card form-section-card mb-4">
                    <div class="card-body p-4">
                        <h2 class="h5 fw-semibold mb-1">Add More Photos</h2>
                        <p class="text-muted small mb-3">Optional — new images will be added to the listing.</p>
                        <div id="dropZone" class="drop-zone">
                            <i class="bi bi-cloud-arrow-up drop-zone-icon" aria-hidden="true"></i>
                            <p class="mb-1">Drag &amp; drop or <label for="images" class="text-accent" style="cursor:pointer">browse</label></p>
                            <input type="file" id="images" name="images[]"
                                   accept="image/jpeg,image/png,image/webp,image/gif"
                                   multiple class="d-none" aria-label="Upload additional images">
                        </div>
                        <div id="imagePreviewGrid" class="image-preview-grid mt-3 d-none"></div>
                    </div>
                </div>

                <div class="d-flex gap-3 justify-content-end">
                    <a href="/listing.php?id=<?= (int)$listingId ?>" class="btn btn-outline-secondary">Cancel</a>
                    <button type="submit" class="btn btn-accent px-4 fw-semibold">
                        <i class="bi bi-check-circle me-2" aria-hidden="true"></i>Save Changes
                    </button>
                </div>
            </form>

        </div>
    </div>
</div>

<script src="/assets/js/create-listing.js" defer></script>
<?php require_once __DIR__ . '/includes/footer.php'; ?>
