<?php

declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

final class SoftDeleteDuplicateDanmuLogs extends AbstractMigration
{
    public function up(): void
    {
        $now = 1747909320;
        $sql = <<<SQL
UPDATE bl_danmu_logs
JOIN (
    SELECT id FROM (
        SELECT id,
               ROW_NUMBER() OVER (PARTITION BY uid, send_at ORDER BY id ASC) AS rn,
               COUNT(*) OVER (PARTITION BY uid, send_at) AS cnt
        FROM bl_danmu_logs
        WHERE deleted_at IS NULL
    ) AS sub
    WHERE sub.rn > 1 AND sub.cnt = 6
) AS dup ON bl_danmu_logs.id = dup.id
SET bl_danmu_logs.deleted_at = {$now}
SQL;
        $this->execute($sql);
    }

    public function down(): void
    {
        $now = 1747909320;
        $sql = <<<SQL
UPDATE bl_danmu_logs
SET deleted_at = NULL
WHERE deleted_at = {$now}
SQL;
        $this->execute($sql);
    }
}
