<?php
// one-time migration - run once then delete or leave (idempotent)
require_once __DIR__ . '/config/db.php';
$pdo = getDB();

$steps = [];

// add payment_method to orders if missing
$cols = $pdo->query("SHOW COLUMNS FROM orders")->fetchAll(PDO::FETCH_COLUMN);
if (!in_array('payment_method', $cols)) {
    $pdo->exec("ALTER TABLE orders ADD COLUMN payment_method VARCHAR(30) NOT NULL DEFAULT 'unspecified' AFTER shipping_country");
    $steps[] = 'Added payment_method column to orders.';
} else {
    $steps[] = 'payment_method already exists — skipped.';
}

// create reviews table if missing
$tables = $pdo->query("SHOW TABLES")->fetchAll(PDO::FETCH_COLUMN);
if (!in_array('reviews', $tables)) {
    $pdo->exec("
        CREATE TABLE reviews (
            review_id   INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            order_id    INT UNSIGNED NOT NULL,
            reviewer_id INT UNSIGNED NOT NULL,
            seller_id   INT UNSIGNED NOT NULL,
            rating      TINYINT UNSIGNED NOT NULL CHECK (rating BETWEEN 1 AND 5),
            body        TEXT,
            created_at  DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            UNIQUE KEY uq_order_seller (order_id, seller_id),
            KEY idx_seller (seller_id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
    ");
    $steps[] = 'Created reviews table.';
} else {
    $steps[] = 'reviews table already exists — skipped.';
}

echo '<pre>' . implode("\n", $steps) . "\n\nDone.</pre>";
