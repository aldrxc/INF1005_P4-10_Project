<?php
// Local config: copy db.local.php.example → db.local.php (gitignored)
// db.local.php should return ['host'=>…, 'name'=>…, 'user'=>…, 'pass'=>…]

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
