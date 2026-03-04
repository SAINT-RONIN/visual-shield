CREATE TABLE IF NOT EXISTS videos (
    id               INT AUTO_INCREMENT PRIMARY KEY,
    user_id          INT          NOT NULL,
    original_name    VARCHAR(255) NOT NULL,
    stored_path      VARCHAR(500) NOT NULL,
    file_size        BIGINT       NOT NULL,
    duration_seconds FLOAT        DEFAULT NULL,
    status           ENUM('queued','processing','completed','failed') DEFAULT 'queued',
    sampling_rate    INT          NOT NULL DEFAULT 15,
    effective_rate   INT          DEFAULT NULL,
    created_at       DATETIME     DEFAULT CURRENT_TIMESTAMP,
    updated_at       DATETIME     DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);
