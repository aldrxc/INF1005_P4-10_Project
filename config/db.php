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



/**
 * Returns a singleton PDO instance.
 * Uses real prepared statements (EMULATE_PREPARES = false) for SQL injection protection.
 */
function getDB(): PDO {
    static $pdo = null;
    if ($pdo === null) {
        $db = file_exists(__DIR__ . '/db.local.php')
            ? require __DIR__ . '/db.local.php'
            : ['host'=>'localhost','name'=>'merch_vault','user'=>'root','pass'=>''];
        $dsn = "mysql:host={$db['host']};dbname={$db['name']};charset=utf8mb4";
        $pdo = new PDO($dsn, $db['user'], $db['pass'], [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
        ]);
    }
    return $pdo;
}
