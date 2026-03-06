ALTER TABLE videos
    ADD COLUMN progress         TINYINT UNSIGNED NOT NULL DEFAULT 0,
    ADD COLUMN progress_message VARCHAR(100)              DEFAULT NULL,
    ADD COLUMN error_message    VARCHAR(500)              DEFAULT NULL;


