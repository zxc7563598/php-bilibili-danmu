<?php

declare(strict_types=1);

use Phinx\Db\Adapter\MysqlAdapter;
use Phinx\Migration\AbstractMigration;

final class CreateUserCurrencyLogs extends AbstractMigration
{
    /**
     * Migrate Up.
     */
    public function up()
    {
        $table = $this->table('bl_user_currency_logs', ['id' => 'records_id', 'comment' => '用户模块 - 用户货币变更记录']);
        $table->addColumn('user_id', 'integer', ['comment' => '舰长id', 'null' => false, 'limit' => MysqlAdapter::INT_BIG])
            ->addColumn('type', 'integer', ['comment' => '变更类型（增加或修改）', 'null' => false, 'limit' => MysqlAdapter::INT_TINY])
            ->addColumn('source', 'integer', ['comment' => '变更来源（主播，签到，系统清理等）', 'null' => false, 'default' => 0, 'limit' => MysqlAdapter::INT_TINY])
            ->addColumn('currency_type', 'integer', ['comment' => '变更货币类型（积分或硬币）', 'null' => false, 'default' => 0, 'limit' => MysqlAdapter::INT_TINY])
            ->addColumn('currency', 'integer', ['comment' => '货币', 'null' => false, 'limit' => MysqlAdapter::INT_MEDIUM])
            ->addColumn('pre_currency', 'integer', ['comment' => '变更前货币', 'null' => false, 'limit' => MysqlAdapter::INT_MEDIUM])
            ->addColumn('after_currency', 'integer', ['comment' => '变更后货币', 'null' => false, 'limit' => MysqlAdapter::INT_MEDIUM])
            ->addColumn('created_at', 'integer', ['comment' => '创建时间', 'null' => false, 'limit' => MysqlAdapter::INT_BIG])
            ->addColumn('updated_at', 'integer', ['comment' => '更新时间', 'null' => true, 'limit' => MysqlAdapter::INT_BIG])
            ->addColumn('deleted_at', 'integer', ['comment' => '逻辑删除', 'null' => true, 'limit' => MysqlAdapter::INT_BIG])
            ->create();

        $this->execute("
            INSERT INTO bl_user_currency_logs (
                user_id, type, source, currency_type, currency, pre_currency, after_currency, created_at, updated_at, deleted_at
            )
            SELECT 
                user_id, 
                type, 
                source, 
                point_type AS currency_type, 
                point AS currency, 
                pre_point AS pre_currency, 
                after_point AS after_currency, 
                created_at, 
                updated_at, 
                deleted_at
            FROM bl_system_change_point_records
        ");
        $this->execute("
            INSERT INTO bl_user_currency_logs (
                user_id, type, source, currency_type, currency, pre_currency, after_currency, created_at, updated_at, deleted_at
            )
            SELECT 
                user_id, 
                1 AS type, 
                5 AS source, 
                0 AS currency_type, 
                point AS currency, 
                pre_point AS pre_currency, 
                after_point AS after_currency, 
                created_at, 
                updated_at, 
                deleted_at
            FROM bl_redemption_records
        ");
        $this->execute("
            INSERT INTO bl_user_currency_logs (
                user_id, type, source, currency_type, currency, pre_currency, after_currency, created_at, updated_at, deleted_at
            )
            SELECT 
                user_id, 
                0 AS type, 
                3 AS source, 
                0 AS currency_type, 
                point AS currency, 
                pre_point AS pre_currency, 
                after_point AS after_currency, 
                created_at, 
                updated_at, 
                deleted_at
            FROM bl_payment_records
        ");
        
    }

    /**
     * Migrate Down.
     */
    public function down()
    {
        $this->table('bl_user_currency_logs')->drop()->save();
    }
}
