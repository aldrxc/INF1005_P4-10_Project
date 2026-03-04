<?php
// =============================================================
// Image Upload Handler
// =============================================================

define('UPLOAD_DIR', __DIR__ . '/../uploads/listings/');
define('UPLOAD_MAX_SIZE', 5 * 1024 * 1024); // 5 MB
define('UPLOAD_ALLOWED_MIME', ['image/jpeg', 'image/png', 'image/webp', 'image/gif']);
define('UPLOAD_ALLOWED_EXT',  ['jpg', 'jpeg', 'png', 'webp', 'gif']);

/**
 * Handle a single uploaded image file.
 *
 * @param array $file  A single entry from $_FILES (e.g. $_FILES['images'][0])
 * @return string      The stored file path relative to uploads/listings/ on success
 * @throws RuntimeException on validation or move failure
 */
function handleImageUpload(array $file): string {
    // Check for upload errors
    if ($file['error'] !== UPLOAD_ERR_OK) {
        throw new RuntimeException('Upload error code: ' . $file['error']);
    }

    // Validate file size
    if ($file['size'] > UPLOAD_MAX_SIZE) {
        throw new RuntimeException('File exceeds maximum allowed size of 5MB.');
    }

    // Validate MIME type via finfo (not the browser-supplied type)
    $finfo    = finfo_open(FILEINFO_MIME_TYPE);
    $mimeType = finfo_file($finfo, $file['tmp_name']);
    finfo_close($finfo);

    if (!in_array($mimeType, UPLOAD_ALLOWED_MIME, true)) {
        throw new RuntimeException('Invalid file type. Only JPEG, PNG, WebP, and GIF are allowed.');
    }

    // Validate file extension
    $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    if (!in_array($ext, UPLOAD_ALLOWED_EXT, true)) {
        throw new RuntimeException('Invalid file extension.');
    }

    // Secondary check: verify it is actually a valid image
    if (!getimagesize($file['tmp_name'])) {
        throw new RuntimeException('File does not appear to be a valid image.');
    }

    // Generate a unique filename to prevent path traversal and overwrites
    $newFilename = bin2hex(random_bytes(16)) . '.' . $ext;
    $destination = UPLOAD_DIR . $newFilename;

    if (!move_uploaded_file($file['tmp_name'], $destination)) {
        throw new RuntimeException('Failed to move uploaded file.');
    }

    return 'uploads/listings/' . $newFilename;
}

/**
 * Process multiple uploaded images from a multi-file input.
 * Returns an array of stored file paths.
 * Skips empty slots (no file chosen).
 *
 * @param array  $filesArray  $_FILES['images'] re-indexed as array of individual file arrays
 * @param int    $maxCount    Maximum number of images to accept
 * @return array              Array of stored relative file paths
 */
function handleMultipleUploads(array $filesArray, int $maxCount = 5): array {
    $stored = [];
    $count  = min(count($filesArray), $maxCount);

    for ($i = 0; $i < $count; $i++) {
        $file = $filesArray[$i];
        if ($file['error'] === UPLOAD_ERR_NO_FILE) {
            continue; // Skip empty slots
        }
        $stored[] = handleImageUpload($file);
    }

    return $stored;
}

/**
 * Reindex PHP's $_FILES multi-upload format into a simple array of file arrays.
 * PHP stores multi-upload as parallel arrays; this converts to array-of-arrays.
 *
 * Usage: $files = reindexFilesArray($_FILES['images']);
 */
function reindexFilesArray(array $filesInput): array {
    $result = [];
    if (!isset($filesInput['name']) || !is_array($filesInput['name'])) {
        return $result;
    }
    $count = count($filesInput['name']);
    for ($i = 0; $i < $count; $i++) {
        $result[] = [
            'name'     => $filesInput['name'][$i],
            'type'     => $filesInput['type'][$i],
            'tmp_name' => $filesInput['tmp_name'][$i],
            'error'    => $filesInput['error'][$i],
            'size'     => $filesInput['size'][$i],
        ];
    }
    return $result;
}
