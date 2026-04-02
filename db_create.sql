-- MerchVault — full schema creation
-- Run this to create a fresh database from scratch.
-- Safe to re-run: drops existing tables first (in FK-safe order).

SET FOREIGN_KEY_CHECKS = 0;

DROP TABLE IF EXISTS reviews;
DROP TABLE IF EXISTS messages;
DROP TABLE IF EXISTS order_items;
DROP TABLE IF EXISTS orders;
DROP TABLE IF EXISTS cart_items;
DROP TABLE IF EXISTS ticket_details;
DROP TABLE IF EXISTS listing_images;
DROP TABLE IF EXISTS listings;
DROP TABLE IF EXISTS genres;
DROP TABLE IF EXISTS categories;
DROP TABLE IF EXISTS users;

SET FOREIGN_KEY_CHECKS = 1;

-- users
CREATE TABLE users (
    user_id       INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    username      VARCHAR(50)  NOT NULL UNIQUE,
    email         VARCHAR(150) NOT NULL UNIQUE,
    password_hash VARCHAR(255) NOT NULL,
    display_name  VARCHAR(100) NOT NULL,
    bio           TEXT,
    avatar_path   VARCHAR(255),
    location      VARCHAR(100),
    role          ENUM('user','admin') NOT NULL DEFAULT 'user',
    is_active     TINYINT(1)   NOT NULL DEFAULT 1,
    joined_at     DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- categories
CREATE TABLE categories (
    category_id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name        VARCHAR(100) NOT NULL,
    slug        VARCHAR(100) NOT NULL UNIQUE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- genres
CREATE TABLE genres (
    genre_id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name     VARCHAR(100) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- listings
CREATE TABLE listings (
    listing_id     INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    seller_id      INT UNSIGNED NOT NULL,
    category_id    INT UNSIGNED NOT NULL,
    genre_id       INT UNSIGNED,
    title          VARCHAR(200) NOT NULL,
    description    TEXT,
    artist_band    VARCHAR(150),
    price          DECIMAL(10,2) NOT NULL,
    condition_type ENUM('new','like_new','good','fair','poor') NOT NULL,
    size           VARCHAR(20),
    status         ENUM('available','reserved','sold') NOT NULL DEFAULT 'available',
    views          INT UNSIGNED NOT NULL DEFAULT 0,
    created_at     DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (seller_id)   REFERENCES users(user_id)      ON DELETE CASCADE,
    FOREIGN KEY (category_id) REFERENCES categories(category_id),
    FOREIGN KEY (genre_id)    REFERENCES genres(genre_id)    ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- listing_images
CREATE TABLE listing_images (
    image_id   INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    listing_id INT UNSIGNED NOT NULL,
    file_path  VARCHAR(255) NOT NULL,
    is_primary TINYINT(1)   NOT NULL DEFAULT 0,
    sort_order TINYINT UNSIGNED NOT NULL DEFAULT 0,
    FOREIGN KEY (listing_id) REFERENCES listings(listing_id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ticket_details (extra info for ticket-category listings)
CREATE TABLE ticket_details (
    ticket_details_id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    listing_id        INT UNSIGNED NOT NULL UNIQUE,
    event_name        VARCHAR(200) NOT NULL,
    event_date        DATE,
    venue_name        VARCHAR(200),
    venue_city        VARCHAR(100),
    seat_section      VARCHAR(50),
    seat_row          VARCHAR(20),
    seat_number       VARCHAR(20),
    quantity          SMALLINT UNSIGNED NOT NULL DEFAULT 1,
    is_e_ticket       TINYINT(1) NOT NULL DEFAULT 0,
    FOREIGN KEY (listing_id) REFERENCES listings(listing_id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- cart_items
CREATE TABLE cart_items (
    cart_item_id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id      INT UNSIGNED NOT NULL,
    listing_id   INT UNSIGNED NOT NULL,
    quantity     SMALLINT UNSIGNED NOT NULL DEFAULT 1,
    added_at     DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY uq_user_listing (user_id, listing_id),
    FOREIGN KEY (user_id)    REFERENCES users(user_id)       ON DELETE CASCADE,
    FOREIGN KEY (listing_id) REFERENCES listings(listing_id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- orders
CREATE TABLE orders (
    order_id         INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    buyer_id         INT UNSIGNED NOT NULL,
    total_amount     DECIMAL(10,2) NOT NULL,
    shipping_name    VARCHAR(150)  NOT NULL,
    shipping_address VARCHAR(300)  NOT NULL,
    shipping_postal  VARCHAR(10)   NOT NULL,
    shipping_country VARCHAR(100)  NOT NULL DEFAULT 'Singapore',
    payment_method   VARCHAR(30)   NOT NULL DEFAULT 'unspecified',
    status           ENUM('pending','confirmed','shipped','completed','cancelled') NOT NULL DEFAULT 'pending',
    created_at       DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (buyer_id) REFERENCES users(user_id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- order_items
CREATE TABLE order_items (
    order_item_id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    order_id      INT UNSIGNED NOT NULL,
    listing_id    INT UNSIGNED NOT NULL,
    seller_id     INT UNSIGNED NOT NULL,
    quantity      SMALLINT UNSIGNED NOT NULL DEFAULT 1,
    price_paid    DECIMAL(10,2) NOT NULL,
    FOREIGN KEY (order_id)   REFERENCES orders(order_id)     ON DELETE CASCADE,
    FOREIGN KEY (listing_id) REFERENCES listings(listing_id) ON DELETE CASCADE,
    FOREIGN KEY (seller_id)  REFERENCES users(user_id)       ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- messages
CREATE TABLE messages (
    message_id  INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    listing_id  INT UNSIGNED NOT NULL,
    sender_id   INT UNSIGNED NOT NULL,
    receiver_id INT UNSIGNED NOT NULL,
    body        TEXT NOT NULL,
    is_read     TINYINT(1) NOT NULL DEFAULT 0,
    sent_at     DATETIME   NOT NULL DEFAULT CURRENT_TIMESTAMP,
    KEY idx_conversation (listing_id, sender_id, receiver_id),
    KEY idx_receiver_unread (receiver_id, is_read),
    FOREIGN KEY (listing_id)  REFERENCES listings(listing_id) ON DELETE CASCADE,
    FOREIGN KEY (sender_id)   REFERENCES users(user_id)       ON DELETE CASCADE,
    FOREIGN KEY (receiver_id) REFERENCES users(user_id)       ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- reviews
CREATE TABLE reviews (
    review_id   INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    order_id    INT UNSIGNED NOT NULL,
    reviewer_id INT UNSIGNED NOT NULL,
    seller_id   INT UNSIGNED NOT NULL,
    rating      TINYINT UNSIGNED NOT NULL CHECK (rating BETWEEN 1 AND 5),
    body        TEXT,
    created_at  DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY uq_order_seller (order_id, seller_id),
    KEY idx_seller (seller_id),
    FOREIGN KEY (order_id)    REFERENCES orders(order_id) ON DELETE CASCADE,
    FOREIGN KEY (reviewer_id) REFERENCES users(user_id)  ON DELETE CASCADE,
    FOREIGN KEY (seller_id)   REFERENCES users(user_id)  ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- seed categories
INSERT INTO categories (name, slug) VALUES
    ('Merchandise', 'merchandise'),
    ('Vinyl & Music', 'vinyl-music'),
    ('Concert Tickets', 'concert-tickets'),
    ('Apparel', 'apparel'),
    ('Collectibles', 'collectibles'),
    ('Accessories', 'accessories');

-- seed genres
INSERT INTO genres (name) VALUES
    ('Pop'),
    ('Rock'),
    ('Hip-Hop / Rap'),
    ('R&B / Soul'),
    ('Electronic / EDM'),
    ('Jazz'),
    ('Classical'),
    ('K-Pop'),
    ('Indie'),
    ('Metal');
