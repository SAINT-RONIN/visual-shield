<?php

declare(strict_types=1);

namespace App\Repositories;

use App\DTOs\VideoFilterDTO;
use App\Framework\Database;
use App\Models\Video;
use PDO;

/**
 * Data-access layer for the `videos` table.
 *
 * Every method that reads a video returns a typed Video object (or null)
 * instead of a raw associative array. Some queries JOIN analysis data
 * for dashboard display — the Video model handles both shapes.
 */
class VideoRepository
{
    private PDO $db;

    public function __construct()
    {
        $this->db = Database::getInstance();
    }

    /**
     * Insert a new video record after a successful upload.
     *
     * @return int The auto-incremented ID of the new video row.
     */
    public function create(int $userId, string $originalName, string $storedPath, int $fileSize, ?float $duration, int $samplingRate): int
    {
        $stmt = $this->db->prepare(
            'INSERT INTO videos (user_id, original_name, stored_path, file_size, duration_seconds, sampling_rate) VALUES (?, ?, ?, ?, ?, ?)'
        );
        $stmt->execute([$userId, $originalName, $storedPath, $fileSize, $duration, $samplingRate]);

        return (int) $this->db->lastInsertId();
    }

    /**
     * Fetch all videos for a user with enriched analysis and segment data.
     *
     * Used by the dashboard. LEFT JOINs analysis_results for headline
     * metrics and sub-queries flagged_segments for segment counts.
     *
     * @return Video[] List of videos (newest first) with joined metrics.
     */
    public function findAllByUserId(int $userId, VideoFilterDTO $filters): array
    {
        $sql = 'SELECT v.*, ar.highest_flash_frequency, ar.average_motion_intensity,
                    (SELECT COUNT(*) FROM flagged_segments fs WHERE fs.video_id = v.id AND fs.severity = \'high\') as high_segments,
                    (SELECT COUNT(*) FROM flagged_segments fs WHERE fs.video_id = v.id AND fs.severity = \'medium\') as medium_segments,
                    (SELECT COUNT(*) FROM flagged_segments fs WHERE fs.video_id = v.id) as total_segments
             FROM videos v
             LEFT JOIN analysis_results ar ON ar.video_id = v.id
             WHERE v.user_id = :userId';

        $params = ['userId' => $userId];

        if ($filters->status !== null) {
            $sql .= ' AND v.status = :status';
            $params['status'] = $filters->status;
        }

        if ($filters->search !== null) {
            $sql .= ' AND v.original_name LIKE :search';
            $params['search'] = '%' . $filters->search . '%';
        }

        $sql .= " ORDER BY v.{$filters->sort} {$filters->order}";
        $sql .= ' LIMIT :limit OFFSET :offset';

        $stmt = $this->db->prepare($sql);

        foreach ($params as $key => $value) {
            $stmt->bindValue($key, $value);
        }

        $stmt->bindValue('limit', $filters->limit, PDO::PARAM_INT);
        $stmt->bindValue('offset', $filters->offset, PDO::PARAM_INT);
        $stmt->execute();

        return array_map(fn(array $row) => Video::fromRow($row), $stmt->fetchAll());
    }

    /**
     * Count all videos for a user matching the given filters.
     *
     * Uses the same WHERE clauses as findAllByUserId but returns only the count.
     */
    public function countAllByUserId(int $userId, VideoFilterDTO $filters): int
    {
        $sql = 'SELECT COUNT(*) FROM videos v WHERE v.user_id = :userId';
        $params = ['userId' => $userId];

        if ($filters->status !== null) {
            $sql .= ' AND v.status = :status';
            $params['status'] = $filters->status;
        }

        if ($filters->search !== null) {
            $sql .= ' AND v.original_name LIKE :search';
            $params['search'] = '%' . $filters->search . '%';
        }

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);

        return (int) $stmt->fetchColumn();
    }

    /**
     * Fetch a single video owned by a specific user (no joined data).
     *
     * Used for ownership checks before actions like delete or re-analyse.
     */
    public function findByIdAndUserId(int $id, int $userId): ?Video
    {
        $stmt = $this->db->prepare('SELECT * FROM videos WHERE id = ? AND user_id = ?');
        $stmt->execute([$id, $userId]);
        $row = $stmt->fetch();

        return $row ? Video::fromRow($row) : null;
    }

    /**
     * Fetch a single video by ID with enriched analysis and segment data.
     *
     * Used by the report page and after uploads/re-analysis to return
     * the full video with headline metrics.
     */
    public function findById(int $id): ?Video
    {
        $stmt = $this->db->prepare(
            'SELECT v.*, ar.highest_flash_frequency, ar.average_motion_intensity,
                    (SELECT COUNT(*) FROM flagged_segments fs WHERE fs.video_id = v.id AND fs.severity = \'high\') as high_segments,
                    (SELECT COUNT(*) FROM flagged_segments fs WHERE fs.video_id = v.id AND fs.severity = \'medium\') as medium_segments,
                    (SELECT COUNT(*) FROM flagged_segments fs WHERE fs.video_id = v.id) as total_segments
             FROM videos v
             LEFT JOIN analysis_results ar ON ar.video_id = v.id
             WHERE v.id = ?'
        );
        $stmt->execute([$id]);
        $row = $stmt->fetch();

        return $row ? Video::fromRow($row) : null;
    }

    /** Set the video's analysis status (e.g. queued, processing, completed, failed). */
    public function updateStatus(int $id, string $status): void
    {
        $stmt = $this->db->prepare('UPDATE videos SET status = ? WHERE id = ?');
        $stmt->execute([$status, $id]);
    }

    /** Store the effective sampling rate that was actually used during analysis. */
    public function updateEffectiveRate(int $id, int $effectiveRate): void
    {
        $stmt = $this->db->prepare('UPDATE videos SET effective_rate = ? WHERE id = ?');
        $stmt->execute([$effectiveRate, $id]);
    }

    /** Update the progress percentage and status message for the frontend progress bar. */
    public function updateProgress(int $id, int $progress, string $message): void
    {
        $stmt = $this->db->prepare('UPDATE videos SET progress = ?, progress_message = ? WHERE id = ?');
        $stmt->execute([$progress, $message, $id]);
    }

    /** Store an error message when analysis fails. */
    public function updateError(int $id, string $message): void
    {
        $stmt = $this->db->prepare('UPDATE videos SET error_message = ? WHERE id = ?');
        $stmt->execute([$message, $id]);
    }

    /** Reset a video's state so it re-enters the analysis queue. */
    public function resetForReanalysis(int $id, int $samplingRate): void
    {
        $stmt = $this->db->prepare(
            'UPDATE videos SET status = \'queued\', progress = 0, progress_message = NULL, error_message = NULL, sampling_rate = ? WHERE id = ?'
        );
        $stmt->execute([$samplingRate, $id]);
    }

    /**
     * Poll for the oldest video waiting to be analysed.
     *
     * Returns the first "queued" video ordered by creation time
     * so uploads are processed in FIFO order.
     */
    public function findNextQueued(): ?Video
    {
        $stmt = $this->db->prepare("SELECT * FROM videos WHERE status = 'queued' ORDER BY created_at ASC LIMIT 1");
        $stmt->execute();
        $row = $stmt->fetch();

        return $row ? Video::fromRow($row) : null;
    }

    /** Update a video's original name (title). */
    public function updateOriginalName(int $id, string $originalName): void
    {
        $stmt = $this->db->prepare('UPDATE videos SET original_name = ? WHERE id = ?');
        $stmt->execute([$originalName, $id]);
    }

    /** Delete a video record by primary key. Foreign-key cascades handle related rows. */
    public function delete(int $id): void
    {
        $stmt = $this->db->prepare('DELETE FROM videos WHERE id = ?');
        $stmt->execute([$id]);
    }
}
