<?php
// reusable listing card partial
// expects $listing array with keys:
//      listing_id, title, price, artist_band, condition_type,
//      category_name, category_slug, genre_name,
//      seller_username, seller_display, primary_image
?>
<article class="listing-card card h-100">
    <a href="/listing.php?id=<?= (int)$listing['listing_id'] ?>"
        class="listing-card-img-wrapper text-decoration-none"
        aria-label="View listing: <?= clean($listing['title']) ?>">
        <img src="<?= $listing['primary_image'] ? '/' . clean($listing['primary_image']) : '/assets/images/placeholder.php' ?>"
            alt="<?= clean($listing['title']) ?>"
            class="listing-card-img"
            loading="lazy">
        <!-- category badge on image -->
        <span class="listing-card-category-badge"><?= clean($listing['category_name']) ?></span>
    </a>

    <div class="card-body d-flex flex-column p-3">
        <!-- title -->
        <h3 class="listing-card-title h6 fw-semibold mb-1">
            <a href="/listing.php?id=<?= (int)$listing['listing_id'] ?>"
                class="text-decoration-none text-reset stretched-link">
                <?= clean($listing['title']) ?>
            </a>
        </h3>

        <!-- artist / genre tags -->
        <div class="listing-card-tags mb-2">
            <?php if ($listing['artist_band']): ?>
                <span class="badge bg-secondary-subtle text-secondary-emphasis">
                    <i class="bi bi-music-note me-1" aria-hidden="true"></i><?= clean($listing['artist_band']) ?>
                </span>
            <?php endif; ?>
            <?php if ($listing['genre_name']): ?>
                <span class="badge bg-dark-subtle text-body-secondary"><?= clean($listing['genre_name']) ?></span>
            <?php endif; ?>
            <?php if ($listing['condition_type'] && $listing['category_slug'] !== 'event-tickets'): ?>
                <?php
                $condLabels = [
                    'new'      => ['New',      'bg-success-subtle text-success-emphasis'],
                    'like_new' => ['Like New', 'bg-success-subtle text-success-emphasis'],
                    'good'     => ['Good',     'bg-info-subtle text-info-emphasis'],
                    'fair'     => ['Fair',     'bg-warning-subtle text-warning-emphasis'],
                    'poor'     => ['Poor',     'bg-danger-subtle text-danger-emphasis'],
                ];
                [$condText, $condClass] = $condLabels[$listing['condition_type']] ?? ['Unknown', 'bg-secondary-subtle text-secondary-emphasis'];
                ?>
                <span class="badge <?= $condClass ?>"><?= $condText ?></span>
            <?php endif; ?>
        </div>

        <!-- price & seller -->
        <div class="mt-auto d-flex justify-content-between align-items-end">
            <div>
                <div class="listing-card-price">S$<?= number_format((float)$listing['price'], 2) ?></div>
                <div class="listing-card-seller small text-muted">
                    by <a href="/profile.php?user=<?= clean($listing['seller_username']) ?>"
                        class="text-muted text-decoration-none">
                        <?= clean($listing['seller_display']) ?>
                    </a>
                </div>
            </div>
        </div>
    </div>
</article>