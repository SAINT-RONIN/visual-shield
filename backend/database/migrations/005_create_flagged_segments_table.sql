CREATE TABLE IF NOT EXISTS flagged_segments (
    id            INT AUTO_INCREMENT PRIMARY KEY,
    video_id      INT          NOT NULL,
    start_time    FLOAT        NOT NULL,
    end_time      FLOAT        NOT NULL,
    segment_type  ENUM('flash','motion') NOT NULL,
    severity      ENUM('low','medium','high') NOT NULL,
    metric_value  FLOAT        DEFAULT NULL,
    created_at    DATETIME     DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (video_id) REFERENCES videos(id) ON DELETE CASCADE
);
