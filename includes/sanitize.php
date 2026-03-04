<?php
// =============================================================
// Input Sanitization Helpers
// =============================================================

/**
 * Sanitize a string for safe HTML output (XSS protection).
 * Always use this when echoing any user-supplied data.
 */
function clean(string $value): string {
    return htmlspecialchars(trim($value), ENT_QUOTES, 'UTF-8');
}

/**
 * Validate and return an integer, or null if invalid.
 */
function sanitizeInt(mixed $value): ?int {
    $result = filter_var($value, FILTER_VALIDATE_INT);
    return ($result === false) ? null : (int)$result;
}

/**
 * Validate and return a positive float (price), or null if invalid.
 */
function sanitizePrice(mixed $value): ?float {
    $result = filter_var($value, FILTER_VALIDATE_FLOAT);
    if ($result === false || $result <= 0) {
        return null;
    }
    return round((float)$result, 2);
}

/**
 * Validate an email address, or return null if invalid.
 */
function sanitizeEmail(string $value): ?string {
    $result = filter_var(trim($value), FILTER_VALIDATE_EMAIL);
    return ($result === false) ? null : $result;
}

/**
 * Validate a value against an allowed whitelist.
 * Returns the value if in whitelist, or null.
 */
function sanitizeEnum(mixed $value, array $allowed): mixed {
    return in_array($value, $allowed, true) ? $value : null;
}

/**
 * Validate a date string in Y-m-d format.
 * Returns a DateTime object on success, or null.
 */
function sanitizeDate(string $value): ?DateTime {
    $dt = DateTime::createFromFormat('Y-m-d', trim($value));
    if ($dt === false || DateTime::getLastErrors()['warning_count'] > 0) {
        return null;
    }
    return $dt;
}
