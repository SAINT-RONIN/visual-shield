CREATE TABLE IF NOT EXISTS analysis_results (
    id                       INT AUTO_INCREMENT PRIMARY KEY,
    video_id                 INT NOT NULL UNIQUE,
    total_frames_analyzed    INT DEFAULT 0,
    total_flash_events       INT DEFAULT 0,
    highest_flash_frequency  FLOAT DEFAULT 0,
    average_motion_intensity FLOAT DEFAULT 0,
    effective_sampling_rate  INT DEFAULT 0,
    created_at               DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (video_id) REFERENCES videos(id) ON DELETE CASCADE
);
