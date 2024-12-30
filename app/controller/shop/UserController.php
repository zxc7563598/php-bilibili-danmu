<?php

namespace app\controller\shop;

use Carbon\Carbon;
use Hejunjie\Tools;
use support\Request;
use app\model\Goods;
use app\model\GoodSubs;
use app\model\Complaint;
use Webman\Http\Response;
use app\model\ShopConfig;
use app\model\UserAddress;
use app\model\PaymentRecords;
use app\model\RedemptionRecords;
use app\controller\GeneralMethod;
use resource\enums\UserAddressEnums;
use resource\enums\RedemptionRecordsEnums;

class UserController extends GeneralMethod
{

    /**
     * 获取个人中心&意见反馈页背景图片
     * 
     * @return Response 
     */
    public function getBackground(Request $request): Response
    {
        $param = $request->data;
        sublog('积分商城', '获取个人中心&意见反馈页背景图片', $param);
        sublog('积分商城', '获取个人中心&意见反馈页背景图片', '===================');
        // 获取数据
        $config = ShopConfig::where('title', 'personal-background-image')->first([
            'content' => 'content'
        ]);
        // 返回数据
        return success($request, [
            'background' => !empty($config->content) ? getImageUrl($config->content) : null
        ]);
    }

    /**
     * 获取用户地址列表
     * 
     * @return Response
     */
    public function getAddressList(Request $request): Response
    {
        $param = $request->data;
        $user_vips = $request->user_vips;
        sublog('积分商城', '获取用户地址列表', $user_vips);
        sublog('积分商城', '获取用户地址列表', $param);
        sublog('积分商城', '获取用户地址列表', '===================');
        // 获取参数
        $user_address = UserAddress::where('user_id', $user_vips->user_id)->get([
            'id' => 'id',
            'name' => 'name',
            'phone' => 'phone',
            'province' => 'province',
            'city' => 'city',
            'county' => 'county',
            'detail' => 'detail',
            'selected' => 'selected'
        ]);
        // 返回数据
        return success($request, [
            'address' => $user_address
        ]);
    }

    /**
     * 获取用户地址详情
     * 
     * @param integer $id 地址id
     * 
     * @return Response
     */
    public function getAddressDetails(Request $request): Response
    {
        $param = $request->data;
        $user_vips = $request->user_vips;
        sublog('积分商城', '获取用户地址详情', $user_vips);
        sublog('积分商城', '获取用户地址详情', $param);
        sublog('积分商城', '获取用户地址详情', '===================');
        // 获取参数
        $id = !empty($param['id']) ? $param['id'] : null;
        // 获取数据
        $user_address = false;
        if (!is_null($id)) {
            $user_address = UserAddress::where('user_id', $user_vips->user_id)->where('id', $id)->first([
                'id' => 'id',
                'name' => 'name',
                'phone' => 'phone',
                'province' => 'province',
                'city' => 'city',
                'county' => 'county',
                'detail' => 'detail'
            ]);
        }
        // 返回数据
        return success($request, [
            'record' => $user_address,
            'enumeration' => [],
        ]);
    }

    /**
     * 变更用户地址
     * 
     * @param integer $id 地址id
     * @param string $name 名称
     * @param string $phone 手机号
     * @param string $province 省
     * @param string $city 市
     * @param string $county 区
     * @param string $detail 详细地址
     * 
     * @return Response
     */
    public function setAddressList(Request $request): Response
    {
        $param = $request->data;
        $user_vips = $request->user_vips;
        sublog('积分商城', '变更用户地址', $user_vips);
        sublog('积分商城', '变更用户地址', $param);
        sublog('积分商城', '变更用户地址', '===================');
        // 获取参数
        $id = !empty($param['id']) ? $param['id'] : null;
        $name = $param['name'];
        $phone = $param['phone'];
        $province = $param['province'];
        $city = $param['city'];
        $county = $param['county'];
        $detail = $param['detail'];
        // 获取数据
        $user_address = new UserAddress();
        if (!is_null($id)) {
            $user_address = UserAddress::where('id', $id)->first();
        }
        $user_address->user_id = $user_vips->user_id;
        $user_address->name = $name;
        $user_address->phone = $phone;
        $user_address->province = $province;
        $user_address->city = $city;
        $user_address->county = $county;
        $user_address->detail = $detail;
        $user_address->selected = UserAddressEnums\Selected::Yes->value;
        $user_address->save();
        // 返回数据
        return success($request, []);
    }

    /**
     * 选择地址信息
     *
     * @param integer $id 地址id
     * 
     * @return Response
     */
    public function setAddressSelected(Request $request): Response
    {
        $param = $request->data;
        $user_vips = $request->user_vips;
        sublog('积分商城', '选择地址信息', $user_vips);
        sublog('积分商城', '选择地址信息', $param);
        sublog('积分商城', '选择地址信息', '===================');
        // 获取参数
        $id = $param['id'];
        // 把所有地址的选择去掉
        UserAddress::where('user_id', $user_vips->user_id)->update(['selected' => UserAddressEnums\Selected::No->value]);
        // 选择当前id的地址
        $user_address = UserAddress::where('id', $id)->first();
        $user_address->selected = UserAddressEnums\Selected::Yes->value;
        $user_address->save();
        // 返回数据
        return success($request, []);
    }

    /**
     * 获取赊销协议
     *
     * @param integer $goods_id 商品id
     * 
     * @return Response
     */
    public function getProtocolCredit(Request $request): Response
    {
        $param = $request->data;
        $user_vips = $request->user_vips;
        sublog('积分商城', '获取地址信息', $user_vips);
        sublog('积分商城', '获取地址信息', $param);
        sublog('积分商城', '获取地址信息', '===================');
        // 获取参数
        $goods_id = $param['goods_id'];
        // 获取数据
        $sn = $user_vips->created_at->timezone(config('app')['default_timezone'])->format('Ymd') . Tools\Str::padString(0, $user_vips->user_id);
        // 获取配置信息
        $config_database = ShopConfig::whereIn('title', [
            'protocols-surname',
            'protocols-uid',
            'protocols-signature',
            'protocols-content',
            'protocols-name'
        ])->get([
            'title' => 'title',
            'content' => 'content'
        ]);
        $config = [];
        foreach ($config_database as $_config) {
            $config[$_config->title] = $_config->content;
        }
        // 返回信息
        return success($request, [
            'sn' => $sn,
            'title' => isset($config['protocols-name']) ? $config['protocols-name'] : '',
            'id_card' => !empty($user_vips->uid) ? $user_vips->uid : null,
            'real_name' => !empty($user_vips->name) ? $user_vips->name : null,
            'company' => [
                'uid' => isset($config['protocols-uid']) ? $config['protocols-uid'] : '',
                'name' => isset($config['protocols-surname']) ? $config['protocols-surname'] : '',
                'face' => isset($config['protocols-signature']) ? getImageUrl($config['protocols-signature']) : ''
            ],
            'protocols' => isset($config['protocols-content']) ? $config['protocols-content'] : '',
            'signing_date' => Carbon::today()->timezone(config('app')['default_timezone'])->format('Y-m-d'),
            'signing' => !empty($user_vips->sign_image) ? getImageUrl($user_vips->sign_image) : null
        ]);
    }

    /**
     * 签名上传
     *
     * @param string $base64 Base64图片信息
     * 
     * @return Response
     */
    public function uploadSigning(Request $request): Response
    {
        $param = $request->data;
        $user_vips = $request->user_vips;
        sublog('积分商城', '签名上传', $user_vips);
        sublog('积分商城', '签名上传', '===================');
        // 获取参数
        $base64 = $param['base64'];
        // base64存储图片 
        $path = public_path('attachment/user-info/' . implode('/', str_split(Tools\Str::padString(0, $user_vips->user_id), 2)) . '/signing/');
        $base64ToImage = Tools\Img::base64ToImage($base64, $path);
        $image_path = Tools\Str::replaceFirst(public_path() . '/attachment/', '', $base64ToImage);
        $user_vips->sign_image = $image_path;
        $user_vips->save();
        // 返回数据
        return success($request, [
            'path' => $image_path,
            'url' => getImageUrl($image_path)
        ]);
    }

    /**
     * 获取开通记录
     *
     * @return Response
     */
    public function getConsumers(Request $request): Response
    {
        $user_vips = $request->user_vips;
        sublog('积分商城', '获取开通记录', $user_vips);
        sublog('积分商城', '获取开通记录', '===================');
        // 获取数据
        $payment_records = PaymentRecords::join('bl_user_vips', 'bl_user_vips.user_id', '=', 'bl_payment_records.user_id')
            ->where('bl_payment_records.user_id', $user_vips->user_id)
            ->orderBy('bl_payment_records.payment_at', 'desc')->get([
                'uid' => 'bl_user_vips.uid',
                'name' => 'bl_user_vips.name',
                'vip_type' => 'bl_payment_records.vip_type',
                'point' => 'bl_payment_records.point',
                'payment_at' => 'bl_payment_records.payment_at'
            ]);
        // 处理数据
        foreach ($payment_records as &$_payment_records) {
            $_payment_records->days = Carbon::parse($_payment_records->payment_at)->timezone(config('app')['default_timezone'])->format('Y-m-d');
            unset($_payment_records->payment_at);
        }
        // 返回数据
        return success($request, [
            'records' => $payment_records
        ]);
    }

    /**
     * 获取兑换记录
     *
     * @return Response
     */
    public function getRedeeming(Request $request): Response
    {
        $user_vips = $request->user_vips;
        sublog('积分商城', '获取兑换记录', $user_vips);
        sublog('积分商城', '获取兑换记录', '===================');
        // 获取数据
        $redemption_records = RedemptionRecords::join('bl_user_vips', 'bl_user_vips.user_id', '=', 'bl_redemption_records.user_id')
            ->where('bl_redemption_records.user_id', $user_vips->user_id)
            ->orderBy('bl_redemption_records.created_at', 'desc')->get([
                'records_id' => 'bl_redemption_records.records_id',
                'goods_id' => 'bl_redemption_records.goods_id',
                'sub_id' => 'bl_redemption_records.sub_id',
                'point' => 'bl_redemption_records.point',
                'status' => 'bl_redemption_records.status',
                'created_at' => 'bl_redemption_records.created_at',
                'name' => 'bl_user_vips.name'
            ]);
        // 获取商品信息
        $goods = Goods::get();
        $good_subs = GoodSubs::get([
            'sub_id' => 'sub_id',
            'name' => 'name'
        ]);
        $subs = [];
        foreach ($good_subs as $_good_subs) {
            $subs[$_good_subs->sub_id] = $_good_subs->name . '*1  ';
        }
        $goods_list = [];
        foreach ($goods as $_goods) {
            $goods_list[$_goods->goods_id] = [
                'name' => $_goods->name,
                'cover' => getImageUrl($_goods->cover_image),
                'amount' => round($_goods->amount)
            ];
        }
        // 处理列表数据
        $order = [];
        foreach ($redemption_records as $_redemption_records) {
            $sub_id = explode(',', $_redemption_records->sub_id);
            $commodity_type = [];
            foreach ($sub_id as $_sub_id) {
                if (isset($subs[$_sub_id])) {
                    $commodity_type[] = $subs[$_sub_id];
                }
            }
            $created_at = $_redemption_records->created_at->timezone(config('app')['default_timezone'])->format('Y-m-d');
            $order[] = [
                'id' => $_redemption_records->records_id,
                'point' => $_redemption_records->point,
                'status' => RedemptionRecordsEnums\Status::from($_redemption_records->status)->label(),
                'create_time' => $created_at,
                'goods_name' => $goods_list[$_redemption_records->goods_id]['name'],
                'cover' => $goods_list[$_redemption_records->goods_id]['cover'],
                'amount' => $goods_list[$_redemption_records->goods_id]['amount'],
                'commodity_type' => implode(',', $commodity_type)
            ];
        }

        // 返回数据
        return success($request, [
            'order' => $order,
        ]);
    }

    /**
     * 上传投诉
     *
     * @param string $title 投诉标题
     * @param string $complaint 投诉内容
     *
     * @return Response
     */
    public function setComplaint(Request $request): Response
    {
        $param = $request->data;
        $user_vips = $request->user_vips;
        sublog('积分商城', '上传投诉', $user_vips);
        sublog('积分商城', '上传投诉', '===================');
        // 获取参数
        $title = $param['title'];
        $content = $param['complaint'];
        // 获取数据
        $complaint = new Complaint();
        $complaint->user_id = $user_vips->user_id;
        $complaint->title = $title;
        $complaint->content = $content;
        $complaint->save();
        // 返回成功
        return success($request, []);
    }
}
