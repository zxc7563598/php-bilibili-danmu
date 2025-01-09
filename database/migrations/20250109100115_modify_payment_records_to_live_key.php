<?php

declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

final class ModifyPaymentRecordsToLiveKey extends AbstractMigration
{
    /**
     * Migrate Up.
     */
    public function up()
    {
        $table = $this->table('bl_payment_records');
        $table->addColumn('live_key', 'string', ['comment' => '开通时的直播ID', 'null' => true, 'after' => 'payment_at'])
            ->save();
    }

    /**
     * Migrate Down.
     */
    public function down()
    {
        $table = $this->table('bl_payment_records');
        $table->removeColumn('live_key')
            ->save();
    }
}