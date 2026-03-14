-- adds role column to users and the messages table for chat
-- safe to run multiple times (IF NOT EXISTS / IF NOT EXISTS column check)

ALTER TABLE users
    ADD COLUMN IF NOT EXISTS role ENUM('user','admin') NOT NULL DEFAULT 'user' AFTER is_active;

CREATE TABLE IF NOT EXISTS messages (
    message_id   INT UNSIGNED     NOT NULL AUTO_INCREMENT,
    listing_id   INT UNSIGNED     NOT NULL,
    sender_id    INT UNSIGNED     NOT NULL,
    receiver_id  INT UNSIGNED     NOT NULL,
    body         TEXT             NOT NULL,
    is_read      TINYINT(1)       NOT NULL DEFAULT 0,
    sent_at      DATETIME         NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (message_id),
    FOREIGN KEY (listing_id)  REFERENCES listings(listing_id) ON DELETE CASCADE,
    FOREIGN KEY (sender_id)   REFERENCES users(user_id),
    FOREIGN KEY (receiver_id) REFERENCES users(user_id),
    INDEX idx_receiver (receiver_id),
    INDEX idx_conversation (listing_id, sender_id, receiver_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
