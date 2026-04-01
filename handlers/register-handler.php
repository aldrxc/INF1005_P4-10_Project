<?php
// POST handler - user registration
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/csrf.php';
require_once __DIR__ . '/../includes/sanitize.php';

startSession();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: /register.php');
    exit;
}

validateCsrfToken();

$errors = [];
$old    = [];

$username         = trim($_POST['username']         ?? '');
$email            = trim($_POST['email']            ?? '');
$display_name     = trim($_POST['display_name']     ?? '');
$password         = $_POST['password']              ?? '';
$confirm_password = $_POST['confirm_password']      ?? '';

$old = compact('username', 'email', 'display_name');

// username: 3–50 chars, letters/numbers/underscores only
if ($username === '') {
    $errors['username'] = 'Username is required.';
} elseif (strlen($username) < 3 || strlen($username) > 50) {
    $errors['username'] = 'Username must be between 3 and 50 characters.';
} elseif (!preg_match('/^[A-Za-z0-9_]+$/', $username)) {
    $errors['username'] = 'Username may only contain letters, numbers, and underscores.';
}

if ($email === '') {
    $errors['email'] = 'Email address is required.';
} elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    $errors['email'] = 'Please enter a valid email address.';
} elseif (strlen($email) > 255) {
    $errors['email'] = 'Email address is too long.';
}

if ($display_name === '') {
    $errors['display_name'] = 'Display name is required.';
} elseif (strlen($display_name) > 100) {
    $errors['display_name'] = 'Display name must be 100 characters or fewer.';
}

if ($password === '') {
    $errors['password'] = 'Password is required.';
} elseif (strlen($password) < 8) {
    $errors['password'] = 'Password must be at least 8 characters.';
}

if ($confirm_password === '') {
    $errors['confirm_password'] = 'Please confirm your password.';
} elseif ($password !== $confirm_password) {
    $errors['confirm_password'] = 'Passwords do not match.';
}

if (!empty($errors)) {
    $_SESSION['register_errors'] = $errors;
    $_SESSION['register_old']    = $old;
    header('Location: /register.php');
    exit;
}

$pdo = getDB();

$stmt = $pdo->prepare("SELECT user_id FROM users WHERE username = ? LIMIT 1");
$stmt->execute([$username]);
if ($stmt->fetch()) {
    $errors['username'] = 'This username is already taken.';
}

$stmt = $pdo->prepare("SELECT user_id FROM users WHERE email = ? LIMIT 1");
$stmt->execute([$email]);
if ($stmt->fetch()) {
    $errors['email'] = 'An account with this email already exists.';
}

if (!empty($errors)) {
    $_SESSION['register_errors'] = $errors;
    $_SESSION['register_old']    = $old;
    header('Location: /register.php');
    exit;
}

$hash = password_hash($password, PASSWORD_BCRYPT);

$stmt = $pdo->prepare("
    INSERT INTO users (username, email, password_hash, display_name)
    VALUES (:username, :email, :password_hash, :display_name)
");
$stmt->execute([
    ':username'      => $username,
    ':email'         => $email,
    ':password_hash' => $hash,
    ':display_name'  => $display_name,
]);

$userId = (int)$pdo->lastInsertId();

session_regenerate_id(true);
$_SESSION['user_id']      = $userId;
$_SESSION['username']     = $username;
$_SESSION['display_name'] = $display_name;
$_SESSION['role']         = 'user';

setFlash('Welcome to MerchVault, ' . htmlspecialchars($display_name, ENT_QUOTES, 'UTF-8') . '! Your account is ready.', 'success');
header('Location: /dashboard.php');
exit;
