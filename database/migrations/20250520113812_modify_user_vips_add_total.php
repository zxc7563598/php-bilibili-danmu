<?php

declare(strict_types=1);

use Phinx\Db\Adapter\MysqlAdapter;
use Phinx\Migration\AbstractMigration;

final class ModifyUserVipsAddTotal extends AbstractMigration
{
    public function up(): void
    {
        $table = $this->table('bl_user_vips');
        $table->addColumn('total_gift_amount', 'decimal', [
            'precision' => 10,
            'scale' => 2,
            'comment' => '累计赠送礼物金额',
            'null' => false,
            'default' => 0,
            'after' => 'point',
        ])->addColumn('total_danmu_count', 'integer', [
            'limit' => MysqlAdapter::INT_BIG,
            'comment' => '累计发送弹幕数量',
            'null' => false,
            'default' => 0,
            'after' => 'point',
        ])->save();

        // 更新字段数据：累计礼物金额
        $this->execute("
            UPDATE bl_user_vips uv
            LEFT JOIN (
                SELECT uid, SUM(total_price) AS total_amount
                FROM bl_gift_records
                GROUP BY uid
            ) gr ON uv.uid = gr.uid
            SET uv.total_gift_amount = IFNULL(gr.total_amount, 0)
        ");

        // 更新字段数据：累计弹幕数量
        $this->execute("
            UPDATE bl_user_vips uv
            LEFT JOIN (
                SELECT uid, COUNT(*) AS total_danmu
                FROM bl_danmu_logs
                GROUP BY uid
            ) dl ON uv.uid = dl.uid
            SET uv.total_danmu_count = IFNULL(dl.total_danmu, 0)
        ");
    }

    public function down(): void
    {
        $table = $this->table('bl_user_vips');
        $table->removeColumn('total_gift_amount')
            ->removeColumn('total_danmu_count')
            ->save();
    }
}
