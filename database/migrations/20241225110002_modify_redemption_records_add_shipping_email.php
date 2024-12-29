<?php

declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

final class ModifyRedemptionRecordsAddShippingEmail extends AbstractMigration
{
    /**
     * Migrate Up.
     */
    public function up()
    {
        $table = $this->table('bl_redemption_records');
        $table->addColumn('tracking_number', 'string', ['comment' => '快递单号', 'null' => true, 'after' => 'shipping_phone'])
            ->addColumn('shipping_email', 'string', ['comment' => '邮件地址', 'null' => true, 'after' => 'shipping_phone'])
            ->save();
    }

    /**
     * Migrate Down.
     */
    public function down()
    {
        $table = $this->table('bl_redemption_records');
        $table->removeColumn('tracking_number')
            ->removeColumn('shipping_email')
            ->save();
    }
}
