<?php

declare(strict_types=1);

use Carbon\Carbon;
use Phinx\Db\Adapter\MysqlAdapter;
use Phinx\Migration\AbstractMigration;

final class CreateShopConfig extends AbstractMigration
{
    /**
     * Migrate Up.
     */
    public function up()
    {
        $table = $this->table('bl_shop_config', ['id' => 'config_id', 'comment' => '配置模块 - 信息配置表']);
        $table->addColumn('title', 'string', ['comment' => '标题', 'null' => false])
            ->addColumn('description', 'string', ['comment' => '说明', 'null' => true])
            ->addColumn('content', 'text', ['comment' => '内容', 'null' => true])
            ->addColumn('created_at', 'integer', ['comment' => '创建时间', 'null' => false, 'limit' => MysqlAdapter::INT_BIG])
            ->addColumn('updated_at', 'integer', ['comment' => '更新时间', 'null' => true, 'limit' => MysqlAdapter::INT_BIG])
            ->addColumn('deleted_at', 'integer', ['comment' => '逻辑删除', 'null' => true, 'limit' => MysqlAdapter::INT_BIG])
            ->create();
        // 添加数据
        $tableAdd = $this->table('bl_shop_config');
        $tableAdd->insert([
            ['config_id' => 1, 'title' => 'login-background-image', 'description' => '登录页面背景图', 'content' => 'shop-config/login-background-image/image_677359922727f2.71437396.jpeg', 'created_at' => time(), 'updated_at' => time()],
            ['config_id' => 2, 'title' => 'personal-background-image', 'description' => '个人中心背景图', 'content' => 'shop-config/personal-background-image/image_6767c1f04358a0.02948271.jpeg', 'created_at' => time(), 'updated_at' => time()],
            ['config_id' => 3, 'title' => 'theme-color', 'description' => '主题色', 'content' => '#7232DD,#9267DC', 'created_at' => time(), 'updated_at' => time()],
            ['config_id' => 4, 'title' => 'protocols-surname', 'description' => '协议人姓名', 'content' => '温以泠', 'created_at' => time(), 'updated_at' => time()],
            ['config_id' => 5, 'title' => 'protocols-uid', 'description' => '协议人UID', 'content' => '3494365156608185', 'created_at' => time(), 'updated_at' => time()],
            ['config_id' => 6, 'title' => 'protocols-name', 'description' => '协议名称', 'content' => '卖身协议', 'created_at' => time(), 'updated_at' => time()],
            ['config_id' => 7, 'title' => 'protocols-signature', 'description' => '协议人签名', 'content' => 'shop-config/protocols-signature/image_67735a11f250d2.09706811.jpeg', 'created_at' => time(), 'updated_at' => time()],
            ['config_id' => 8, 'title' => 'protocols-content', 'description' => '协议内容', 'content' => '<p><strong style="background-color: rgb(255, 255, 255); color: rgb(0, 0, 0);">特别提醒：请用户仔细阅读本协议所有条款（包括附件），尤其是加粗字体及加下划线部分，请务必仔细阅读。如您对本协议内容有任何疑问，那就别有疑问。一旦您通过勾选/点击或确认本协议，即意味着您已阅读本协议所有条款，并对本协议条款的含义及相应的法律后果已全部通晓并充分理解，您同意以数据电文形式订立本协议，虽然本协议完全不具有任何法律约束力。为重视未成年人权益的保障，您在使用本服务时应具备完全民事行为能力。若您不具备完全民事行为能力，请立即v我50。</strong></p><p><br></p><p><strong style="background-color: rgb(255, 255, 255); color: rgb(51, 51, 51);">基本协议</strong></p><p><br></p><p><span style="background-color: rgb(255, 255, 255); color: rgb(51, 51, 51);">1.坚持乙方的绝对领导。直播间里温以泠永远是第一位</span></p><p><br></p><p><span style="background-color: rgb(255, 255, 255); color: rgb(51, 51, 51);">2.爱护乙方，做文明观众，做到“打不还手，骂不还口，笑脸迎送冷屁股”</span></p><p><br></p><p><span style="background-color: rgb(255, 255, 255); color: rgb(51, 51, 51);">3.诚心接受乙方感情上的独裁，“不要和陌生人说话”，尤其不能跟陌生女人说话。当然，问路的老太太除外。</span></p><p><br></p><p><span style="background-color: rgb(255, 255, 255); color: rgb(51, 51, 51);">4.坚持工资奖金全部上缴制度。不涂改工资条，不在衣柜里藏钱。不过，每月可以申请领取500元零花（日元）</span></p><p><br></p><p><span style="background-color: rgb(255, 255, 255); color: rgb(51, 51, 51);">5.甲方有义务从心里热爱乙方崇敬乙方，视乙方为自己的上帝，与乙方沟通时须使用低三下四/讨好/献媚等温柔语气，不得用生硬、顶撞的语气</span></p><p><br></p><p><span style="background-color: rgb(255, 255, 255); color: rgb(51, 51, 51);">6.乙方拥有精神羞辱权，有权剥夺甲方的一切自由和尊严，用语言和行为强迫甲方，达到羞辱的目的。</span></p><p><br></p>', 'created_at' => time(), 'updated_at' => time()],
            ['config_id' => 9, 'title' => 'virtual-gift-order-successful-icon', 'description' => '虚拟礼物下单成功图标', 'content' => 'shop-config/virtual-gift-order-successful-icon/image_6767c4375515d7.85405077.png', 'created_at' => time(), 'updated_at' => time()],
            ['config_id' => 10, 'title' => 'virtual-gift-order-successful-title', 'description' => '虚拟礼物下单成功标题', 'content' => '下单成功！', 'created_at' => time(), 'updated_at' => time()],
            ['config_id' => 11, 'title' => 'virtual-gift-order-successful-content', 'description' => '虚拟礼物下单成功内容', 'content' => '已经在给你准备啦！记得抓紧来找我要嗷～', 'created_at' => time(), 'updated_at' => time()],
            ['config_id' => 12, 'title' => 'virtual-gift-order-successful-button', 'description' => '虚拟礼物下单成功按钮', 'content' => '啊啊啊啊啊我来了我来了', 'created_at' => time(), 'updated_at' => time()],
            ['config_id' => 13, 'title' => 'realism-gift-order-successful-icon', 'description' => '实体礼物下单成功图标', 'content' => 'shop-config/realism-gift-order-successful-icon/image_6767c43a5b8e63.86345586.png', 'created_at' => time(), 'updated_at' => time()],
            ['config_id' => 14, 'title' => 'realism-gift-order-successful-title', 'description' => '实体礼物下单成功标题', 'content' => '下单成功！', 'created_at' => time(), 'updated_at' => time()],
            ['config_id' => 15, 'title' => 'realism-gift-order-successful-content', 'description' => '实体礼物下单成功内容', 'content' => '已经收到通知啦，很快就会发货嘿嘿嘿', 'created_at' => time(), 'updated_at' => time()],
            ['config_id' => 16, 'title' => 'realism-gift-order-successful-button', 'description' => '实体礼物下单成功按钮', 'content' => '我自愿体谅主包的辛苦，晚点发也可以', 'created_at' => time(), 'updated_at' => time()],
            ['config_id' => 17, 'title' => 'tribute-gift-order-successful-icon', 'description' => '贡品下单成功图标', 'content' => 'shop-config/tribute-gift-order-successful-icon/image_6767c43e17a254.98589663.png', 'created_at' => time(), 'updated_at' => time()],
            ['config_id' => 18, 'title' => 'tribute-gift-order-successful-title', 'description' => '贡品下单成功标题', 'content' => '...', 'created_at' => time(), 'updated_at' => time()],
            ['config_id' => 19, 'title' => 'tribute-gift-order-successful-content', 'description' => '贡品下单成功内容', 'content' => '你连垃圾都不如', 'created_at' => time(), 'updated_at' => time()],
            ['config_id' => 20, 'title' => 'tribute-gift-order-successful-button', 'description' => '贡品下单成功按钮', 'content' => '呜呜呜主人我会继续努力的', 'created_at' => time(), 'updated_at' => time()],
            ['config_id' => 21, 'title' => 'tribute-gift-order-successful-rankings', 'description' => '贡品下单成功是否开启排名', 'content' => '1', 'created_at' => time(), 'updated_at' => time()],
            ['config_id' => 22, 'title' => 'tribute-gift-order-successful-rankingslist', 'description' => '贡品下单成功排名列表', 'content' => '[{"comparison":"3","position":10,"content":"你以为把积分上供就能挨骂了？\r\n你什么都得不到，废物东西\r\n就算是废物都有被我踩在脚底下的价值\r\n你那点积分就跟你一样一点用都没有\r\n犯贱就继续上，让我看看你个废物东西能有多贱"},{"comparison":4,"position":5,"content":"你可真是个垃圾\r\n怎么，花钱给主播上供让你感觉很好吗？\r\n才上到个第5名，你也就这点能耐了，没用的东西\r\n这么爱上就多上点，让我看看你废物到什么程度"},{"comparison":4,"position":4,"content":"你可真是个垃圾\r\n怎么，花钱给主播上供让你感觉很好吗？\r\n才上到个第4名，你也就这点能耐了，没用的东西\r\n这么爱上就多上点，让我看看你废物到什么程度"},{"comparison":4,"position":3,"content":"你可真是个垃圾\n怎么，花钱给主播上供让你感觉很好吗？\n才上到个第3名，你也就这点能耐了，没用的东西\n这么爱上就多上点，让我看看你废物到什么程度"},{"comparison":4,"position":2,"content":"你可真是个垃圾\r\n怎么，花钱给主播上供让你感觉很好吗？\r\n才上到个第2名，你也就这点能耐了，没用的东西\r\n这么爱上就多上点，让我看看你废物到什么程度"},{"comparison":"4","position":"1","content":"上供都上这么勤快真贱啊，废物玩意\n你也就只配上供了知道吗臭傻逼\n给我多去赚点积分，在这个第一大傻逼的位子上待着\n方便我什么时候心情好了骂你两句"}]', 'created_at' => time(), 'updated_at' => time()],
            ['config_id' => 23, 'title' => 'listening-open-vip', 'description' => '大航海监听', 'content' => '1', 'created_at' => time(), 'updated_at' => time()],
            ['config_id' => 24, 'title' => 'vip-lv1-bonus-points', 'description' => '开通舰长奖励积分', 'content' => '60', 'created_at' => time(), 'updated_at' => time()],
            ['config_id' => 25, 'title' => 'vip-lv2-bonus-points', 'description' => '开通提督奖励积分', 'content' => '600', 'created_at' => time(), 'updated_at' => time()],
            ['config_id' => 26, 'title' => 'vip-lv3-bonus-points', 'description' => '开通总督奖励积分', 'content' => '6000', 'created_at' => time(), 'updated_at' => time()],
            ['config_id' => 27, 'title' => 'live-streaming-link', 'description' => '直播间链接', 'content' => 'https://live.bilibili.com/h5/30118851', 'created_at' => time(), 'updated_at' => time()],
        ]);
        $tableAdd->saveData();
    }

    /**
     * Migrate Down.
     */
    public function down()
    {
        $this->table('bl_shop_config')->drop()->save();
    }
}
