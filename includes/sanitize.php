<?php
// sanitize.php — input validation & XSS helpers

// escape for safe HTML output
function clean(?string $value): string {
    return htmlspecialchars(trim($value ?? ''), ENT_QUOTES, 'UTF-8');
}

function sanitizeInt(mixed $value): ?int {
    $result = filter_var($value, FILTER_VALIDATE_INT);
    return ($result === false) ? null : (int)$result;
}

// returns null if value is not a positive number
function sanitizePrice(mixed $value): ?float {
    $result = filter_var($value, FILTER_VALIDATE_FLOAT);
    if ($result === false || $result <= 0) return null;
    return round((float)$result, 2);
}

function sanitizeEmail(?string $value): ?string {
    $result = filter_var(trim($value ?? ''), FILTER_VALIDATE_EMAIL);
    return ($result === false) ? null : $result;
}

// returns $value only if it's in $allowed
function sanitizeEnum(mixed $value, array $allowed): mixed {
    return in_array($value, $allowed, true) ? $value : null;
}

// validates Y-m-d format, returns the date string or null
function sanitizeDate(string $value): ?string {
    $dt = DateTime::createFromFormat('Y-m-d', trim($value));
    return $dt ? $dt->format('Y-m-d') : null;
}
