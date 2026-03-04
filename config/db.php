<?php
// =============================================================
// Database Configuration & PDO Singleton
// =============================================================
//
// LOCAL DEVELOPMENT:
//   Copy config/db.local.php.example → config/db.local.php
//   and edit the credentials there.
//   db.local.php is gitignored and will NEVER be committed.
//
// PRODUCTION (Google Cloud):
//   Set the constants directly in this file, or set them as
//   environment variables and read with getenv().
// =============================================================

// Defaults (work with XAMPP / WAMP out-of-the-box)
define('DB_HOST',    'localhost');
define('DB_NAME',    'merch_vault');
define('DB_USER',    'root');
define('DB_PASS',    '');
define('DB_CHARSET', 'utf8mb4');

// Load local overrides if the file exists.
// db.local.php can redefine the constants above BEFORE they are used.
// (PHP will ignore redefines if the constant is already defined,
//  so db.local.php must be loaded before the constants above are used
//  — we achieve this by using a wrapper approach below.)
$_localConfig = __DIR__ . '/db.local.php';
if (file_exists($_localConfig)) {
    // db.local.php should return an array: ['host'=>…, 'name'=>…, 'user'=>…, 'pass'=>…]
    $_db = require $_localConfig;
} else {
    $_db = [
        'host' => DB_HOST,
        'name' => DB_NAME,
        'user' => DB_USER,
        'pass' => DB_PASS,
    ];
}

// Security headers applied on every request
header('X-Frame-Options: SAMEORIGIN');
header('X-Content-Type-Options: nosniff');
header('Referrer-Policy: strict-origin-when-cross-origin');

/**
 * Returns a singleton PDO instance.
 * Uses real prepared statements (EMULATE_PREPARES = false) for SQL injection protection.
 */
function getDB(): PDO {
    static $pdo = null;
    if ($pdo === null) {
        global $_db;
        $dsn = sprintf(
            'mysql:host=%s;dbname=%s;charset=%s',
            $_db['host'], $_db['name'], DB_CHARSET
        );
        $options = [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false, // real prepared statements
        ];
        try {
            $pdo = new PDO($dsn, $_db['user'], $_db['pass'], $options);
        } catch (PDOException $e) {
            error_log('Database connection failed: ' . $e->getMessage());
            die('A database error occurred. Please try again later.');
        }
    }
    return $pdo;
}
