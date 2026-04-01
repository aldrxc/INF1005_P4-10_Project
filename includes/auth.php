<?php

function startSession(): void
{
    if (session_status() === PHP_SESSION_NONE) {
        ini_set('session.cookie_httponly', '1');
        ini_set('session.cookie_samesite', 'Strict');
        ini_set('session.use_strict_mode', '1');
        // ini_set('session.cookie_secure', '1'); // enable on https
        session_start();
    }
}

function isLoggedIn(): bool
{
    return isset($_SESSION['user_id']) && !empty($_SESSION['user_id']);
}

// redirects to login, preserving intended destination
function requireLogin(): void
{
    if (!isLoggedIn()) {
        $redirect = urlencode($_SERVER['REQUEST_URI'] ?? '/');
        header('Location: /login.php?redirect=' . $redirect);
        exit;
    }
}

function getCurrentUserId(): ?int
{
    return isLoggedIn() ? (int)$_SESSION['user_id'] : null;
}

function getCurrentUsername(): ?string
{
    return $_SESSION['username'] ?? null;
}

// type: 'success' | 'danger' | 'warning' | 'info'
function setFlash(string $message, string $type = 'info'): void
{
    $_SESSION['flash'] = ['message' => $message, 'type' => $type];
}

function getFlash(): ?array
{
    if (isset($_SESSION['flash'])) {
        $flash = $_SESSION['flash'];
        unset($_SESSION['flash']);
        return $flash;
    }
    return null;
}

function isAdmin(): bool
{
    return isLoggedIn() && ($_SESSION['role'] ?? '') === 'admin';
}

function requireAdmin(): void
{
    requireLogin();
    if (!isAdmin()) {
        header('Location: /index.php');
        exit;
    }
}
