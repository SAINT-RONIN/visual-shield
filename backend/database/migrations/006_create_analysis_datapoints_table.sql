CREATE TABLE IF NOT EXISTS analysis_datapoints (
    id               INT AUTO_INCREMENT PRIMARY KEY,
    video_id         INT   NOT NULL,
    time_point       FLOAT NOT NULL,
    flash_frequency  FLOAT DEFAULT 0,
    motion_intensity FLOAT DEFAULT 0,
    luminance        FLOAT DEFAULT 0,
    flash_detected   BOOLEAN DEFAULT FALSE,
    FOREIGN KEY (video_id) REFERENCES videos(id) ON DELETE CASCADE
);
CREATE INDEX idx_datapoints_video ON analysis_datapoints(video_id);
