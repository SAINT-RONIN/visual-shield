<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Repositories\Interfaces\VideoRepositoryInterface;
use App\DTOs\VideoFilterDTO;
use App\Models\Video;
use PDO;

/**
 * Data-access layer for the `videos` table.
 *
 * Every method that reads a video returns a typed Video object (or null)
 * instead of a raw associative array. Some queries JOIN analysis data
 * for dashboard display â€” the Video model handles both shapes.
 */
class VideoRepository extends BaseRepository implements VideoRepositoryInterface
{

    // Inserts a new video record after a successful upload; returns the generated ID.
    public function create(int $userId, string $originalName, string $storedPath, int $fileSize, ?float $duration, int $samplingRate): int
    {
        $stmt = $this->db->prepare(
            'INSERT INTO videos (user_id, original_name, stored_path, file_size, duration_seconds, sampling_rate) VALUES (?, ?, ?, ?, ?, ?)'
        );
        $stmt->execute([$userId, $originalName, $storedPath, $fileSize, $duration, $samplingRate]);

        return (int) $this->db->lastInsertId();
    }

    // LEFT JOINs analysis_results and sub-queries flagged_segments for dashboard metrics.
    /** @return Video[] */
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

        // sort and order are validated against a strict whitelist in VideoFilterDTO,
        // so interpolation here is safe â€” PDO cannot bind identifiers (column names).
        $allowedSorts = ['created_at', 'original_name', 'status'];
        $allowedOrders = ['asc', 'desc'];
        $sortCol = in_array($filters->sort, $allowedSorts, true) ? $filters->sort : 'created_at';
        $orderDir = in_array($filters->order, $allowedOrders, true) ? strtoupper($filters->order) : 'DESC';

        $sql .= " ORDER BY v.{$sortCol} {$orderDir}";
        $sql .= ' LIMIT :limit OFFSET :offset';

        $stmt = $this->db->prepare($sql);

        foreach ($params as $key => $value) {
            $stmt->bindValue($key, $value);
        }

        $stmt->bindValue('limit', $filters->limit, PDO::PARAM_INT);
        $stmt->bindValue('offset', $filters->offset, PDO::PARAM_INT);
        $stmt->execute();

        return $this->fetchAllHydrated($stmt, Video::fromRow(...));
    }

    // Same WHERE clauses as findAllByUserId but returns only the count.
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

    // No joined data — used for ownership checks before delete or re-analyse.
    public function findByIdAndUserId(int $id, int $userId): ?Video
    {
        $stmt = $this->db->prepare('SELECT * FROM videos WHERE id = ? AND user_id = ?');
        $stmt->execute([$id, $userId]);

        return $this->fetchOneOrNull($stmt, Video::fromRow(...));
    }

    // Used by the report page and after uploads/re-analysis; returns the full video with headline metrics.
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

        return $this->fetchOneOrNull($stmt, Video::fromRow(...));
    }

    // Sets the video's analysis status (queued, processing, completed, failed).
    public function updateStatus(int $id, string $status): void
    {
        $stmt = $this->db->prepare('UPDATE videos SET status = ? WHERE id = ?');
        $stmt->execute([$status, $id]);
    }

    // Stores the effective sampling rate that was actually used during analysis.
    public function updateEffectiveRate(int $id, int $effectiveRate): void
    {
        $stmt = $this->db->prepare('UPDATE videos SET effective_rate = ? WHERE id = ?');
        $stmt->execute([$effectiveRate, $id]);
    }

    // Updates the progress percentage and status message for the frontend progress bar.
    public function updateProgress(int $id, int $progress, string $message): void
    {
        $stmt = $this->db->prepare('UPDATE videos SET progress = ?, progress_message = ? WHERE id = ?');
        $stmt->execute([$progress, $message, $id]);
    }

    // Stores an error message when analysis fails.
    public function updateError(int $id, string $message): void
    {
        $stmt = $this->db->prepare('UPDATE videos SET error_message = ? WHERE id = ?');
        $stmt->execute([$message, $id]);
    }

    // Resets a video's state so it re-enters the analysis queue.
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

        return $this->fetchOneOrNull($stmt, Video::fromRow(...));
    }

    // Updates a video's original name (title).
    public function updateOriginalName(int $id, string $originalName): void
    {
        $stmt = $this->db->prepare('UPDATE videos SET original_name = ? WHERE id = ?');
        $stmt->execute([$originalName, $id]);
    }

    // Deletes a video record by primary key; foreign-key cascades handle related rows.
    public function delete(int $id): void
    {
        $stmt = $this->db->prepare('DELETE FROM videos WHERE id = ?');
        $stmt->execute([$id]);
    }
}
