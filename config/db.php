<?php
// Local config: copy db.local.php.example → db.local.php (gitignored)
// db.local.php should return ['host'=>…, 'name'=>…, 'user'=>…, 'pass'=>…]

// hide errors from users in production — log them instead
ini_set('display_errors', '0');
ini_set('log_errors', '1');
error_reporting(E_ALL);

function getDB(): PDO {
    static $pdo = null;
    if ($pdo === null) {
        $db = file_exists(__DIR__ . '/db.local.php')
            ? require __DIR__ . '/db.local.php'
            : ['host'=>'localhost','name'=>'merch_vault','user'=>'root','pass'=>''];
        $dsn = "mysql:host={$db['host']};dbname={$db['name']};charset=utf8mb4";
        try {
            $pdo = new PDO($dsn, $db['user'], $db['pass'], [
                PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES   => false,
            ]);
            $pdo->exec("SET SESSION sql_mode = (SELECT REPLACE(@@sql_mode, 'ONLY_FULL_GROUP_BY', ''))");
        } catch (PDOException $e) {
            error_log('Database connection failed: ' . $e->getMessage());
            http_response_code(503);
            echo '<!doctype html><html><head><title>Maintenance</title></head><body style="font-family:sans-serif;text-align:center;padding:4rem;background:#0d0d0d;color:#f1f5f9">
                <h1 style="color:#f03ea1">We\'ll be right back</h1>
                <p>MerchVault is temporarily unavailable. Please try again in a few minutes.</p>
            </body></html>';
            exit;
        }
    }
    return $pdo;
}
