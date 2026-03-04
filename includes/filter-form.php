<?php
// Filter form partial — shared between sidebar and offcanvas.
// Requires: $allCategories, $allGenres, $allowedConditions,
//           $catSlug, $genreId, $conditions, $minPrice, $maxPrice, $searchQ, $sortKey
?>
<form method="GET" action="/browse.php" id="filterForm" aria-label="Filter listings">
    <!-- Search -->
    <div class="mb-3">
        <label for="filterSearch" class="form-label fw-semibold small">Search</label>
        <input type="search" class="form-control form-control-sm"
               id="filterSearch" name="q"
               value="<?= clean($searchQ) ?>"
               placeholder="Title, artist, description…"
               aria-label="Search listings by keyword">
    </div>

    <!-- Category -->
    <div class="mb-3">
        <fieldset>
            <legend class="fw-semibold small mb-2">Category</legend>
            <div class="d-flex flex-column gap-1">
                <div class="form-check">
                    <input class="form-check-input filter-check" type="radio"
                           name="category" id="catAll" value=""
                           <?= $catSlug === '' ? 'checked' : '' ?>>
                    <label class="form-check-label small" for="catAll">All Categories</label>
                </div>
                <?php foreach ($allCategories as $cat): ?>
                    <div class="form-check">
                        <input class="form-check-input filter-check" type="radio"
                               name="category" id="cat<?= (int)$cat['category_id'] ?>"
                               value="<?= clean($cat['slug']) ?>"
                               <?= $catSlug === $cat['slug'] ? 'checked' : '' ?>>
                        <label class="form-check-label small" for="cat<?= (int)$cat['category_id'] ?>">
                            <?= clean($cat['name']) ?>
                        </label>
                    </div>
                <?php endforeach; ?>
            </div>
        </fieldset>
    </div>

    <!-- Genre -->
    <div class="mb-3">
        <label for="filterGenre" class="form-label fw-semibold small">Genre</label>
        <select class="form-select form-select-sm" id="filterGenre" name="genre" aria-label="Filter by genre">
            <option value="">All Genres</option>
            <?php foreach ($allGenres as $g): ?>
                <option value="<?= (int)$g['genre_id'] ?>"
                        <?= $genreId === (int)$g['genre_id'] ? 'selected' : '' ?>>
                    <?= clean($g['name']) ?>
                </option>
            <?php endforeach; ?>
        </select>
    </div>

    <!-- Condition -->
    <div class="mb-3">
        <fieldset>
            <legend class="fw-semibold small mb-2">Condition</legend>
            <?php
            $condLabels = [
                'new'      => 'New',
                'like_new' => 'Like New',
                'good'     => 'Good',
                'fair'     => 'Fair',
                'poor'     => 'Poor',
            ];
            foreach ($condLabels as $val => $label):
            ?>
                <div class="form-check">
                    <input class="form-check-input filter-check" type="checkbox"
                           name="condition[]" id="cond<?= $val ?>"
                           value="<?= $val ?>"
                           <?= in_array($val, $conditions, true) ? 'checked' : '' ?>>
                    <label class="form-check-label small" for="cond<?= $val ?>"><?= $label ?></label>
                </div>
            <?php endforeach; ?>
        </fieldset>
    </div>

    <!-- Price range -->
    <div class="mb-3">
        <fieldset>
            <legend class="fw-semibold small mb-2">Price (S$)</legend>
            <div class="d-flex gap-2 align-items-center">
                <input type="number" class="form-control form-control-sm"
                       name="min_price" id="minPrice"
                       placeholder="Min" min="0" step="0.01"
                       value="<?= $minPrice !== null ? $minPrice : '' ?>"
                       aria-label="Minimum price">
                <span class="text-muted small">–</span>
                <input type="number" class="form-control form-control-sm"
                       name="max_price" id="maxPrice"
                       placeholder="Max" min="0" step="0.01"
                       value="<?= $maxPrice !== null ? $maxPrice : '' ?>"
                       aria-label="Maximum price">
            </div>
        </fieldset>
    </div>

    <!-- Sort (hidden on desktop — separate sort select in results area; shown in offcanvas) -->
    <input type="hidden" name="sort" value="<?= clean($sortKey) ?>">

    <button type="submit" class="btn btn-accent btn-sm w-100 mt-2">
        <i class="bi bi-funnel me-1" aria-hidden="true"></i>Apply Filters
    </button>

    <a href="/browse.php" class="btn btn-link btn-sm w-100 mt-1 text-muted">
        <i class="bi bi-x-circle me-1" aria-hidden="true"></i>Clear All Filters
    </a>
</form>
