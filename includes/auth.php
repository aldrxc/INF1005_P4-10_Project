<?php
// =============================================================
// Authentication Helpers
// =============================================================

/**
 * Start a secure session. Call at the top of every page before output.
 */
function startSession(): void {
    if (session_status() === PHP_SESSION_NONE) {
        ini_set('session.cookie_httponly', '1');
        ini_set('session.cookie_samesite', 'Strict');
        ini_set('session.use_strict_mode', '1');
        // Uncomment on HTTPS (production GCP):
        // ini_set('session.cookie_secure', '1');
        session_start();
    }
}

/**
 * Check if a user is currently logged in.
 */
function isLoggedIn(): bool {
    return isset($_SESSION['user_id']) && !empty($_SESSION['user_id']);
}

/**
 * Redirect to login if not authenticated.
 * Preserves the intended destination in the redirect query param.
 */
function requireLogin(): void {
    if (!isLoggedIn()) {
        $redirect = urlencode($_SERVER['REQUEST_URI'] ?? '/');
        header('Location: /login.php?redirect=' . $redirect);
        exit;
    }
}

/**
 * Return the current user's ID, or null.
 */
function getCurrentUserId(): ?int {
    return isLoggedIn() ? (int)$_SESSION['user_id'] : null;
}

/**
 * Return the current user's username, or null.
 */
function getCurrentUsername(): ?string {
    return $_SESSION['username'] ?? null;
}

/**
 * Store a one-time flash message in the session.
 * Type: 'success' | 'danger' | 'warning' | 'info'
 */
function setFlash(string $message, string $type = 'info'): void {
    $_SESSION['flash'] = ['message' => $message, 'type' => $type];
}

/**
 * Retrieve and clear the flash message. Returns null if none.
 */
function getFlash(): ?array {
    if (isset($_SESSION['flash'])) {
        $flash = $_SESSION['flash'];
        unset($_SESSION['flash']);
        return $flash;
    }
    return null;
}
