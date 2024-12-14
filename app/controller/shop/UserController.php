<?php

namespace app\controller\shop;

use app\controller\GeneralMethod;
use app\core\UserPublicMethods;
use app\model\Goods;
use app\model\GoodSubs;
use app\model\PaymentRecords;
use app\model\RedemptionRecords;
use app\model\UserAddress;
use app\model\UserVips;
use Carbon\Carbon;
use support\Request;
use resource\enums\UserVipsEnums;
use resource\enums\UserAddressEnums;
use resource\enums\RedemptionRecordsEnums;
use Webman\Http\Response;
use yzh52521\mailer\Mailer;
use Hejunjie\Tools;

class UserController extends GeneralMethod
{
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
            'detail' => 'detail'
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
        $sn = $user_vips->created_at->timezone(config('app')['default_timezone'])->format('Ymd') . supplementStr($user_vips->user_id);
        $room_uinfo = !empty(strval(readFileContent(runtime_path() . '/tmp/room_uinfo.cfg'))) ? json_decode(strval(readFileContent(runtime_path() . '/tmp/room_uinfo.cfg')), true) : [];
        // 返回信息
        return success($request, [
            'sn' => $sn,
            'id_card' => !empty($user_vips->uid) ? $user_vips->uid : null,
            'real_name' => !empty($user_vips->name) ? $user_vips->name : null,
            'company' => [
                'name' => isset($room_uinfo['uid']) ? $room_uinfo['uid'] : '',
                'uid' => isset($room_uinfo['uname']) ? $room_uinfo['uname'] : '',
                'face' => isset($room_uinfo['face']) ? $room_uinfo['face'] : ''
            ],
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
        $path = public_path('attachment/user-info/' . implode('/', splitStr(supplementStr($user_vips->user_id), 2)) . '/signing/');
        $storage = ImageStorageBase64($path, $base64);
        if (is_int($storage)) {
            return fail($request, $storage);
        }
        $image_path = ReplaceFirst(public_path() . '/attachment/', '', $storage);
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
        $param = $request->data;
        $user_vips = $request->user_vips;
        sublog('积分商城', '获取开通记录', $user_vips);
        sublog('积分商城', '获取开通记录', '===================');
        // 获取数据
        $is_add = true;
        $payment_records = PaymentRecords::join('bl_user_vips', 'bl_user_vips.user_id', '=', 'bl_payment_records.user_id');
        if (!in_array($user_vips->uid, [
            4325051,
            3494365156608185
        ])) {
            $is_add = false;
            $payment_records = $payment_records->where('bl_payment_records.user_id', $user_vips->user_id);
        }
        $payment_records = $payment_records->orderBy('bl_payment_records.payment_at', 'desc')->get([
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
            'records' => $payment_records,
            'is_add' => $is_add
        ]);
    }

    /**
     * 获取兑换记录
     *
     * @return Response
     */
    public function getRedeeming(Request $request): Response
    {
        $param = $request->data;
        $user_vips = $request->user_vips;
        sublog('积分商城', '获取兑换记录', $user_vips);
        sublog('积分商城', '获取兑换记录', '===================');
        // 获取数据
        $redemption_records = RedemptionRecords::join('bl_user_vips', 'bl_user_vips.user_id', '=', 'bl_redemption_records.user_id');
        $is_copy = true;
        $is_complete = true;
        if (!in_array($user_vips->uid, [
            4325051,
            3494365156608185
        ])) {
            $redemption_records = $redemption_records->where('bl_redemption_records.user_id', $user_vips->user_id);
            $is_copy = false;
            $is_complete = false;
        }
        $redemption_records = $redemption_records->orderBy('bl_redemption_records.created_at', 'desc')->get([
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
                'amount' => round($_goods->amount),
                'freight_fee' => '主包包邮'
            ];
        }
        // 处理列表数据
        $order = [];
        foreach ($redemption_records as $_redemption_records) {
            $shop_complete = false;
            $sub_id = explode(',', $_redemption_records->sub_id);
            $commodity_type = [];
            foreach ($sub_id as $_sub_id) {
                if (isset($subs[$_sub_id])) {
                    $commodity_type[] = $subs[$_sub_id];
                }
            }
            if ($is_complete) {
                switch ($_redemption_records->status) {
                    case RedemptionRecordsEnums\Status::NoShipment->value:
                        $shop_complete = true;
                        break;
                }
            }
            $created_at = $_redemption_records->created_at->timezone(config('app')['default_timezone'])->format('Y-m-d');
            if (in_array($user_vips->uid, [
                4325051,
                3494365156608185
            ])) {
                $created_at .= '   ' . $_redemption_records->name;
            }
            $order[] = [
                'id' => $_redemption_records->records_id,
                'point' => $_redemption_records->point,
                'status' => RedemptionRecordsEnums\Status::from($_redemption_records->status)->label(),
                'create_time' => $created_at,
                'goods_name' => $goods_list[$_redemption_records->goods_id]['name'],
                'cover' => $goods_list[$_redemption_records->goods_id]['cover'],
                'amount' => $goods_list[$_redemption_records->goods_id]['amount'],
                'commodity_type' => implode(',', $commodity_type),
                'is_copy' => $is_copy,
                'is_complete' => $shop_complete,
            ];
        }

        // 返回数据
        return success($request, [
            'order' => $order,
        ]);
    }

    /**
     * 获取兑换地址
     *
     * @param integer $id 记录id
     * 
     * @return Response
     */
    public function getRedeemingAddress(Request $request): Response
    {
        $param = $request->data;
        $user_vips = $request->user_vips;
        sublog('积分商城', '获取兑换地址', $user_vips);
        sublog('积分商城', '获取兑换地址', '===================');
        // 获取参数
        $records_id = $param['id'];
        // 验证权限
        if (!in_array($user_vips->uid, [
            4325051,
            3494365156608185
        ])) {
            return fail($request, 800010);
        }
        // 获取数据
        $records = RedemptionRecords::where('records_id', $records_id)->first([
            'shipping_address' => 'shipping_address',
            'shipping_name' => 'shipping_name',
            'shipping_phone' => 'shipping_phone',
        ]);
        // 返回数据
        return success($request, [
            'address' => $records
        ]);
    }

    /**
     * 标记兑换完成
     *
     * @param integer $id 记录id
     * 
     * @return Response
     */
    public function setRedeemingComplete(Request $request): Response
    {
        $param = $request->data;
        $user_vips = $request->user_vips;
        sublog('积分商城', '标记兑换完成', $user_vips);
        sublog('积分商城', '标记兑换完成', '===================');
        // 获取参数
        $records_id = $param['id'];
        // 验证权限
        if (!in_array($user_vips->uid, [
            4325051,
            3494365156608185
        ])) {
            return fail($request, 800010);
        }
        $records = RedemptionRecords::where('records_id', $records_id)->first();
        $records->status = RedemptionRecordsEnums\Status::Shipment->value;
        $records->save();
        // 返回数据
        return success($request, []);
    }

    /**
     * 上传投诉
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
        $complaint = $param['complaint'];
        // 获取数据
        $subject = UserVipsEnums\VipType::from($user_vips->vip_type)->label() . $user_vips->name . ', uid:' . $user_vips->uid . '进行投诉';
        $set_html_body = '<p>' . $title . ' : ' . $complaint . '</p>';
        // 发送邮件
        sublog('邮件发送', '积分商城投诉', $subject);
        sublog('邮件发送', '积分商城投诉', $set_html_body);
        $mailer = Mailer::setFrom(['992182040@qq.com' => "积分商城投诉"])
            ->setTo('junjie.he.925@gmail.com')
            ->setCc('482335887@qq.com')
            ->setSubject($subject)
            ->setHtmlBody($set_html_body)
            ->send();
        sublog('邮件发送', '积分商城投诉', '发送结果');
        sublog('邮件发送', '积分商城投诉', $mailer);
        sublog('邮件发送', '积分商城投诉', '----------');
        // 返回成功
        return success($request, []);
    }

    /**
     * 补充开通记录
     *
     * @param array $data 上传数据
     * 
     * @return Response
     */
    public function addConsumers(Request $request): Response
    {
        $param = $request->data;
        $user_vips = $request->user_vips;
        sublog('积分商城', '补充开通记录', $user_vips);
        sublog('积分商城', '补充开通记录', $param);
        sublog('积分商城', '补充开通记录', '===================');
        // 获取参数
        $data = $param['data'];
        // 验证添加权限
        if (!in_array($user_vips->uid, [
            4325051,
            3494365156608185
        ])) {
            return fail($request, 800010);
        }
        // 添加记录
        $res = [];
        foreach ($data as $_data) {
            try {
                $payment_at = Carbon::parse($_data['payment_at'] . ' 12:00:00')->timezone(config('app')['default_timezone'])->timestamp;
            } catch (\Exception $e) {
                return fail($request, 800009);
            }
            $res[] = UserPublicMethods::userOpensVip($_data['uid'], $_data['uname'], $_data['vip'], $_data['price'], $_data['point'], $payment_at);
        }
        return success($request, $res);
    }

    /**
     * 增加兑换记录
     *
     * @param array $data 上传数据
     * 
     * @return Response
     */
    public function addRedemption(Request $request): Response
    {
        $param = $request->data;
        $user_vips = $request->user_vips;
        sublog('积分商城', '增加兑换记录', $user_vips);
        sublog('积分商城', '增加兑换记录', $param);
        sublog('积分商城', '增加兑换记录', '===================');
        // 获取参数
        $data = $param['data'];
        // 验证添加权限
        if (!in_array($user_vips->uid, [
            4325051,
            3494365156608185
        ])) {
            return fail($request, 800010);
        }
        $res = [];
        foreach ($data as $_data) {
            $uid = $_data['uid'];
            $goods_id = $_data['goods_id'];
            $sub_id = explode(',', $_data['sub_id']);
            $users = UserVips::where('uid', $uid)->first();
            if (!empty($users)) {
                $redeemingGoods = UserPublicMethods::redeemingGoods($users->user_id, $goods_id, $sub_id);
                $res[] = [
                    'uid' => $uid,
                    'success' => $redeemingGoods
                ];
            } else {
                $res[] = [
                    'uid' => $uid,
                    'success' => '不存在'
                ];
            }
        }
        // 返回数据
        return success($request, $res);
    }

    /**
     * 图片Base64上传
     *
     * @param integer $goods_name 礼物名称
     * @param string $img_type 图片类型
     * @param string $base64 Base64图片信息
     * 
     * @return Response
     */
    public function uploadBase64Images(Request $request): Response
    {
        $param = $request->data;
        $user_vips = $request->user_vips;
        sublog('积分商城', '简单的图片Base64上传', $param['goods_name']);
        sublog('积分商城', '简单的图片Base64上传', $param['img_type']);
        // 验证添加权限
        if (!in_array($user_vips->uid, [
            4325051,
            3494365156608185
        ])) {
            return fail($request, 800010);
        }
        // 获取参数
        $goods_name = isset($param['goods_name']) ? $param['goods_name'] : null;
        $img_type = $param['img_type'];
        $base64 = $param['base64'];
        // 图片类型分类
        $path = public_path() . 'attachment/';
        switch ($img_type) {
            case 'sub-cover-image': // 规格图片
                $path = public_path('attachment/shop/' . $goods_name . '/sub_cover_image/');
                break;
            case 'cover-image': // 商品封面
                $path = public_path('attachment/shop/' . $goods_name . '/cover_image/');
                break;
            case 'carousel-images': // 轮播图
                $path = public_path('attachment/shop/' . $goods_name . '/carousel_images/');
                break;
            case 'details-images': // 商品详情图
                $path = public_path('attachment/shop/' . $goods_name . '/details_images/');
                break;
            case 'service-description-images': // 服务说明图
                $path = public_path('attachment/shop/' . $goods_name . '/service_description_images/');
                break;
            default:
                return fail($request, 800024);
                break;
        }
        // base64存储图片
        $storage = ImageStorageBase64($path, $base64);
        if (is_int($storage)) {
            return fail($request, $storage);
        }
        $image_path = ReplaceFirst(public_path() . '/attachment/', '', $storage);
        sublog('接口调用', '简单的图片Base64上传', json_encode([
            'path' => $image_path,
            'url' => getImageUrl($image_path)
        ], JSON_UNESCAPED_UNICODE + JSON_UNESCAPED_SLASHES + JSON_PRESERVE_ZERO_FRACTION));
        sublog('接口调用', '简单的图片Base64上传', '返回数据');
        sublog('接口调用', '简单的图片Base64上传', '===================');
        // 返回数据
        return success($request, [
            'path' => $image_path,
            'url' => getImageUrl($image_path)
        ]);
    }
}
