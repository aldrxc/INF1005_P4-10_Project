<?php
define('UPLOAD_DIR', __DIR__ . '/../uploads/listings/');
define('UPLOAD_MAX_SIZE', 5 * 1024 * 1024); // 5 MB
define('UPLOAD_ALLOWED_MIME', ['image/jpeg', 'image/png', 'image/webp', 'image/gif']);
define('UPLOAD_ALLOWED_EXT',  ['jpg', 'jpeg', 'png', 'webp', 'gif']);

// throws RuntimeException on validation or move failure
function handleImageUpload(array $file): string
{
    if ($file['error'] !== UPLOAD_ERR_OK) {
        throw new RuntimeException('Upload error code: ' . $file['error']);
    }
    if ($file['size'] > UPLOAD_MAX_SIZE) {
        throw new RuntimeException('File exceeds maximum allowed size of 5MB.');
    }

    // use finfo, not browser-supplied MIME type
    $finfo    = finfo_open(FILEINFO_MIME_TYPE);
    $mimeType = finfo_file($finfo, $file['tmp_name']);
    finfo_close($finfo);

    if (!in_array($mimeType, UPLOAD_ALLOWED_MIME, true)) {
        throw new RuntimeException('Invalid file type. Only JPEG, PNG, WebP, and GIF are allowed.');
    }

    $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    if (!in_array($ext, UPLOAD_ALLOWED_EXT, true)) {
        throw new RuntimeException('Invalid file extension.');
    }

    if (!getimagesize($file['tmp_name'])) {
        throw new RuntimeException('File does not appear to be a valid image.');
    }

    $newFilename = bin2hex(random_bytes(16)) . '.' . $ext;
    $destination = UPLOAD_DIR . $newFilename;

    if (!move_uploaded_file($file['tmp_name'], $destination)) {
        throw new RuntimeException('Failed to move uploaded file.');
    }

    return 'uploads/listings/' . $newFilename;
}

// reindex PHP's parallel-arrays multi-upload format into array-of-arrays
function reindexFilesArray(array $filesInput): array
{
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
