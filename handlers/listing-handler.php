<?php
// POST handler — create or update a listing
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/csrf.php';
require_once __DIR__ . '/../includes/sanitize.php';
require_once __DIR__ . '/../includes/upload.php';

startSession();
requireLogin();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: /create-listing.php');
    exit;
}

validateCsrfToken();

$action    = $_POST['action'] ?? 'create'; // 'create' or 'edit'
$listingId = sanitizeInt($_POST['listing_id'] ?? '');
$errors    = [];
$old       = [];

// -----------------------------------------------
// Collect & validate common fields
// -----------------------------------------------
$title       = trim($_POST['title']       ?? '');
$description = trim($_POST['description'] ?? '');
$artist_band = trim($_POST['artist_band'] ?? '');
$categoryId  = sanitizeInt($_POST['category_id'] ?? '');
$genreId     = sanitizeInt($_POST['genre_id']    ?? '') ?: null;
$price       = sanitizePrice($_POST['price']     ?? '');
$condition   = sanitizeEnum(trim($_POST['condition_type'] ?? ''), ['new','like_new','good','fair','poor', '']) ?: null;
$size        = trim($_POST['size'] ?? '') ?: null;

$old = compact('title','description','artist_band','category_id','genre_id','price','condition_type','size');
$old['condition_type'] = $condition;
$old['category_id']    = $categoryId;
$old['genre_id']       = $genreId;

if ($title === '')           $errors['title']       = 'Title is required.';
elseif (strlen($title) > 200) $errors['title']      = 'Title must be 200 characters or fewer.';
if ($description === '')     $errors['description'] = 'Description is required.';
elseif (strlen($description) > 1000) $errors['description'] = 'Description must be 1000 characters or fewer.';
if (!$categoryId)            $errors['category_id'] = 'Please select a category.';
if ($price === null)         $errors['price']       = 'Please enter a valid positive price.';

// Verify category exists
$pdo = getDB();
$catRow = null;
if ($categoryId) {
    $stmt = $pdo->prepare("SELECT category_id, slug FROM categories WHERE category_id = ? LIMIT 1");
    $stmt->execute([$categoryId]);
    $catRow = $stmt->fetch();
    if (!$catRow) $errors['category_id'] = 'Invalid category selected.';
}

$isTicket = $catRow && $catRow['slug'] === 'event-tickets';

// -----------------------------------------------
// Ticket-specific validation
// -----------------------------------------------
$ticketData = [];
if ($isTicket) {
    $eventName    = trim($_POST['event_name']    ?? '');
    $eventDateStr = trim($_POST['event_date']    ?? '');
    $venueName    = trim($_POST['venue_name']    ?? '');
    $venueCity    = trim($_POST['venue_city']    ?? '');
    $seatSection  = trim($_POST['seat_section']  ?? '') ?: null;
    $seatRow      = trim($_POST['seat_row']      ?? '') ?: null;
    $seatNumber   = trim($_POST['seat_number']   ?? '') ?: null;
    $quantity     = max(1, (int)($_POST['quantity'] ?? 1));
    $isETicket    = isset($_POST['is_e_ticket']) ? 1 : 0;

    $old = array_merge($old, compact('event_name','event_date','venue_name','venue_city','seat_section','seat_row','seat_number','quantity','is_e_ticket'));
    $old['event_date'] = $eventDateStr;

    if ($eventName === '')    $errors['event_name'] = 'Event name is required.';
    if ($venueName === '')    $errors['venue_name'] = 'Venue name is required.';
    if ($venueCity === '')    $errors['venue_city'] = 'City is required.';

    $eventDate = null;
    if ($eventDateStr === '') {
        $errors['event_date'] = 'Event date is required.';
    } else {
        $eventDate = sanitizeDate($eventDateStr);
        if (!$eventDate) {
            $errors['event_date'] = 'Please enter a valid date.';
        } elseif ($eventDate < new DateTime('today')) {
            $errors['event_date'] = 'Event date must be in the future.';
        }
    }

    $ticketData = compact('eventName','eventDate','venueName','venueCity','seatSection','seatRow','seatNumber','quantity','isETicket');
}

// -----------------------------------------------
// Ownership check for edits
// -----------------------------------------------
$existingListing = null;
if ($action === 'edit') {
    if (!$listingId) {
        setFlash('Invalid listing.', 'danger');
        header('Location: /dashboard.php');
        exit;
    }
    $stmt = $pdo->prepare("SELECT * FROM listings WHERE listing_id = ? AND seller_id = ? LIMIT 1");
    $stmt->execute([$listingId, getCurrentUserId()]);
    $existingListing = $stmt->fetch();
    if (!$existingListing) {
        setFlash('Listing not found or you do not have permission to edit it.', 'danger');
        header('Location: /dashboard.php');
        exit;
    }
}

// -----------------------------------------------
// Image upload
// -----------------------------------------------
$uploadedPaths = [];
if (!empty($_FILES['images']['name'][0])) {
    $filesArray = reindexFilesArray($_FILES['images']);
    if (count($filesArray) > 5) {
        $errors['images'] = 'You may upload a maximum of 5 images.';
    } else {
        foreach ($filesArray as $file) {
            if ($file['error'] === UPLOAD_ERR_NO_FILE) continue;
            try {
                $uploadedPaths[] = handleImageUpload($file);
            } catch (RuntimeException $e) {
                $errors['images'] = $e->getMessage();
                break;
            }
        }
    }
}

// Require at least one image on create
if ($action === 'create' && empty($uploadedPaths) && !isset($errors['images'])) {
    $errors['images'] = 'Please upload at least one image.';
}

if (!empty($errors)) {
    $_SESSION['listing_errors'] = $errors;
    $_SESSION['listing_old']    = $old;
    $redirect = $action === 'edit' ? "/edit-listing.php?id=$listingId" : '/create-listing.php';
    header('Location: ' . $redirect);
    exit;
}

// -----------------------------------------------
// Persist to database
// -----------------------------------------------
$pdo->beginTransaction();
try {
    if ($action === 'create') {
        $stmt = $pdo->prepare("
            INSERT INTO listings
                (seller_id, category_id, genre_id, title, description, artist_band,
                 price, condition_type, size)
            VALUES
                (:seller_id, :category_id, :genre_id, :title, :description, :artist_band,
                 :price, :condition_type, :size)
        ");
        $stmt->execute([
            ':seller_id'     => getCurrentUserId(),
            ':category_id'   => $categoryId,
            ':genre_id'      => $genreId,
            ':title'         => $title,
            ':description'   => $description,
            ':artist_band'   => $artist_band ?: null,
            ':price'         => $price,
            ':condition_type'=> $isTicket ? null : $condition,
            ':size'          => $isTicket ? null : $size,
        ]);
        $listingId = (int)$pdo->lastInsertId();
    } else {
        $stmt = $pdo->prepare("
            UPDATE listings SET
                category_id = :category_id, genre_id = :genre_id, title = :title,
                description = :description, artist_band = :artist_band, price = :price,
                condition_type = :condition_type, size = :size
            WHERE listing_id = :listing_id AND seller_id = :seller_id
        ");
        $stmt->execute([
            ':category_id'   => $categoryId,
            ':genre_id'      => $genreId,
            ':title'         => $title,
            ':description'   => $description,
            ':artist_band'   => $artist_band ?: null,
            ':price'         => $price,
            ':condition_type'=> $isTicket ? null : $condition,
            ':size'          => $isTicket ? null : $size,
            ':listing_id'    => $listingId,
            ':seller_id'     => getCurrentUserId(),
        ]);
    }

    // Ticket details
    if ($isTicket && $ticketData) {
        if ($action === 'edit') {
            $pdo->prepare("DELETE FROM ticket_details WHERE listing_id = ?")->execute([$listingId]);
        }
        $stmt = $pdo->prepare("
            INSERT INTO ticket_details
                (listing_id, event_name, event_date, venue_name, venue_city,
                 seat_section, seat_row, seat_number, quantity, is_e_ticket)
            VALUES
                (:listing_id, :event_name, :event_date, :venue_name, :venue_city,
                 :seat_section, :seat_row, :seat_number, :quantity, :is_e_ticket)
        ");
        $stmt->execute([
            ':listing_id'  => $listingId,
            ':event_name'  => $ticketData['eventName'],
            ':event_date'  => $ticketData['eventDate']->format('Y-m-d'),
            ':venue_name'  => $ticketData['venueName'],
            ':venue_city'  => $ticketData['venueCity'],
            ':seat_section'=> $ticketData['seatSection'],
            ':seat_row'    => $ticketData['seatRow'],
            ':seat_number' => $ticketData['seatNumber'],
            ':quantity'    => $ticketData['quantity'],
            ':is_e_ticket' => $ticketData['isETicket'],
        ]);
    }

    // Images
    if (!empty($uploadedPaths)) {
        $isPrimary = ($action === 'create') ? 1 : 0; // first image is primary on create only
        foreach ($uploadedPaths as $i => $path) {
            $stmt = $pdo->prepare("
                INSERT INTO listing_images (listing_id, file_path, is_primary, sort_order)
                VALUES (?, ?, ?, ?)
            ");
            $stmt->execute([$listingId, $path, ($i === 0 && $action === 'create') ? 1 : 0, $i]);
        }
    }

    $pdo->commit();
} catch (Exception $e) {
    $pdo->rollBack();
    error_log('Listing save error: ' . $e->getMessage());
    setFlash('An error occurred while saving your listing. Please try again.', 'danger');
    $redirect = $action === 'edit' ? "/edit-listing.php?id=$listingId" : '/create-listing.php';
    header('Location: ' . $redirect);
    exit;
}

$msg = $action === 'create' ? 'Your listing has been posted!' : 'Your listing has been updated.';
setFlash($msg, 'success');
header('Location: /listing.php?id=' . $listingId);
exit;
