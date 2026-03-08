-- =============================================================
-- MerchVault Database Schema
-- INF1005 WST - Lab P4 Team 10
-- Run: mysql -u root -p merch_vault < sql/schema.sql
-- =============================================================

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
SET time_zone = "+00:00";

CREATE DATABASE IF NOT EXISTS merch_vault
    CHARACTER SET utf8mb4
    COLLATE utf8mb4_unicode_ci;

USE merch_vault;

-- -----------------------------------------------
-- TABLE: users
-- -----------------------------------------------
CREATE TABLE IF NOT EXISTS users (
    user_id       INT UNSIGNED     NOT NULL AUTO_INCREMENT,
    username      VARCHAR(50)      NOT NULL,
    email         VARCHAR(255)     NOT NULL,
    password_hash VARCHAR(255)     NOT NULL,
    display_name  VARCHAR(100)     NOT NULL,
    bio           TEXT             DEFAULT NULL,
    avatar_path   VARCHAR(255)     DEFAULT NULL,
    location      VARCHAR(100)     DEFAULT NULL,
    joined_at     DATETIME         NOT NULL DEFAULT CURRENT_TIMESTAMP,
    is_active     TINYINT(1)       NOT NULL DEFAULT 1,
    PRIMARY KEY (user_id),
    UNIQUE KEY uq_email    (email),
    UNIQUE KEY uq_username (username),
    INDEX idx_email    (email),
    INDEX idx_username (username)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -----------------------------------------------
-- TABLE: categories
-- -----------------------------------------------
CREATE TABLE IF NOT EXISTS categories (
    category_id   INT UNSIGNED     NOT NULL AUTO_INCREMENT,
    name          VARCHAR(100)     NOT NULL,
    slug          VARCHAR(100)     NOT NULL,
    icon_class    VARCHAR(50)      DEFAULT NULL,
    PRIMARY KEY (category_id),
    UNIQUE KEY uq_name (name),
    UNIQUE KEY uq_slug (slug)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT IGNORE INTO categories (name, slug, icon_class) VALUES
    ('Band Tees',       'band-tees',       'bi-tencent-qq'),
    ('Vinyl Records',   'vinyl-records',   'bi-disc'),
    ('Concert Posters', 'concert-posters', 'bi-image'),
    ('Instruments',     'instruments',     'bi-music-note-beamed'),
    ('Accessories',     'accessories',     'bi-bag'),
    ('Event Tickets',   'event-tickets',   'bi-ticket-perforated');

-- -----------------------------------------------
-- TABLE: genres
-- -----------------------------------------------
CREATE TABLE IF NOT EXISTS genres (
    genre_id      INT UNSIGNED     NOT NULL AUTO_INCREMENT,
    name          VARCHAR(100)     NOT NULL,
    PRIMARY KEY (genre_id),
    UNIQUE KEY uq_name (name)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT IGNORE INTO genres (name) VALUES
    ('Rock'), ('Metal'), ('Pop'), ('Hip-Hop'), ('Jazz'),
    ('Classical'), ('Electronic'), ('Indie'), ('Punk'), ('R&B'),
    ('Country'), ('Folk'), ('K-Pop'), ('Anime OST'), ('Other');

-- -----------------------------------------------
-- TABLE: listings
-- -----------------------------------------------
CREATE TABLE IF NOT EXISTS listings (
    listing_id      INT UNSIGNED        NOT NULL AUTO_INCREMENT,
    seller_id       INT UNSIGNED        NOT NULL,
    category_id     INT UNSIGNED        NOT NULL,
    genre_id        INT UNSIGNED        DEFAULT NULL,
    title           VARCHAR(200)        NOT NULL,
    description     TEXT                NOT NULL,
    artist_band     VARCHAR(150)        DEFAULT NULL,
    price           DECIMAL(10,2)       NOT NULL,
    condition_type  ENUM('new','like_new','good','fair','poor') DEFAULT NULL,
    size            VARCHAR(20)         DEFAULT NULL,
    status          ENUM('available','reserved','sold') NOT NULL DEFAULT 'available',
    created_at      DATETIME            NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at      DATETIME            NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    views           INT UNSIGNED        NOT NULL DEFAULT 0,
    PRIMARY KEY (listing_id),
    FOREIGN KEY (seller_id)   REFERENCES users(user_id)      ON DELETE CASCADE,
    FOREIGN KEY (category_id) REFERENCES categories(category_id),
    FOREIGN KEY (genre_id)    REFERENCES genres(genre_id),
    INDEX idx_status     (status),
    INDEX idx_seller     (seller_id),
    INDEX idx_category   (category_id),
    INDEX idx_genre      (genre_id),
    INDEX idx_price      (price),
    INDEX idx_created_at (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -----------------------------------------------
-- TABLE: ticket_details
-- -----------------------------------------------
CREATE TABLE IF NOT EXISTS ticket_details (
    ticket_id     INT UNSIGNED     NOT NULL AUTO_INCREMENT,
    listing_id    INT UNSIGNED     NOT NULL,
    event_name    VARCHAR(200)     NOT NULL,
    event_date    DATE             NOT NULL,
    venue_name    VARCHAR(200)     NOT NULL,
    venue_city    VARCHAR(100)     NOT NULL,
    seat_section  VARCHAR(50)      DEFAULT NULL,
    seat_row      VARCHAR(20)      DEFAULT NULL,
    seat_number   VARCHAR(50)      DEFAULT NULL,
    quantity      INT UNSIGNED     NOT NULL DEFAULT 1,
    is_e_ticket   TINYINT(1)       NOT NULL DEFAULT 1,
    PRIMARY KEY (ticket_id),
    UNIQUE KEY uq_listing (listing_id),
    FOREIGN KEY (listing_id) REFERENCES listings(listing_id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -----------------------------------------------
-- TABLE: listing_images
-- -----------------------------------------------
CREATE TABLE IF NOT EXISTS listing_images (
    image_id      INT UNSIGNED     NOT NULL AUTO_INCREMENT,
    listing_id    INT UNSIGNED     NOT NULL,
    file_path     VARCHAR(255)     NOT NULL,
    is_primary    TINYINT(1)       NOT NULL DEFAULT 0,
    sort_order    INT UNSIGNED     NOT NULL DEFAULT 0,
    uploaded_at   DATETIME         NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (image_id),
    FOREIGN KEY (listing_id) REFERENCES listings(listing_id) ON DELETE CASCADE,
    INDEX idx_listing (listing_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -----------------------------------------------
-- TABLE: cart_items
-- -----------------------------------------------
CREATE TABLE IF NOT EXISTS cart_items (
    cart_item_id  INT UNSIGNED     NOT NULL AUTO_INCREMENT,
    user_id       INT UNSIGNED     NOT NULL,
    listing_id    INT UNSIGNED     NOT NULL,
    quantity      INT UNSIGNED     NOT NULL DEFAULT 1,
    added_at      DATETIME         NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (cart_item_id),
    UNIQUE KEY uq_user_listing (user_id, listing_id),
    FOREIGN KEY (user_id)    REFERENCES users(user_id)       ON DELETE CASCADE,
    FOREIGN KEY (listing_id) REFERENCES listings(listing_id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -----------------------------------------------
-- TABLE: orders
-- -----------------------------------------------
CREATE TABLE IF NOT EXISTS orders (
    order_id         INT UNSIGNED   NOT NULL AUTO_INCREMENT,
    buyer_id         INT UNSIGNED   NOT NULL,
    total_amount     DECIMAL(10,2)  NOT NULL,
    status           ENUM('pending','confirmed','shipped','completed','cancelled')
                                    NOT NULL DEFAULT 'pending',
    shipping_name    VARCHAR(150)   NOT NULL,
    shipping_address TEXT           NOT NULL,
    shipping_postal  VARCHAR(20)    NOT NULL,
    shipping_country VARCHAR(100)   NOT NULL DEFAULT 'Singapore',
    created_at       DATETIME       NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at       DATETIME       NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (order_id),
    FOREIGN KEY (buyer_id) REFERENCES users(user_id),
    INDEX idx_buyer (buyer_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -----------------------------------------------
-- TABLE: order_items
-- -----------------------------------------------
CREATE TABLE IF NOT EXISTS order_items (
    order_item_id INT UNSIGNED     NOT NULL AUTO_INCREMENT,
    order_id      INT UNSIGNED     NOT NULL,
    listing_id    INT UNSIGNED     NOT NULL,
    seller_id     INT UNSIGNED     NOT NULL,
    price_paid    DECIMAL(10,2)    NOT NULL,
    quantity      INT UNSIGNED     NOT NULL DEFAULT 1,
    PRIMARY KEY (order_item_id),
    FOREIGN KEY (order_id)   REFERENCES orders(order_id)    ON DELETE CASCADE,
    FOREIGN KEY (listing_id) REFERENCES listings(listing_id),
    FOREIGN KEY (seller_id)  REFERENCES users(user_id),
    INDEX idx_order  (order_id),
    INDEX idx_seller (seller_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
