<?php

namespace app\controller\robot;

use support\Request;
use app\model\Complaint;
use app\model\RedemptionRecords;
use app\controller\GeneralMethod;
use resource\enums\ComplaintEnums;
use resource\enums\RedemptionRecordsEnums;
use support\Response;

class ManagementController extends GeneralMethod
{

    /**
     * 不分离后台 - 系统配置页面
     * 
     * @return Response 
     */
    public function pageSystemConfiguration(Request $request)
    {
        // 获取未发货数量
        $records = RedemptionRecords::where('status', RedemptionRecordsEnums\Status::NoShipment->value)->count();
        // 获取投诉数量
        $complaint = Complaint::where('read', ComplaintEnums\Read::Unread->value)->count();
        return view('shop/system-configuration', [
            'secretKey' => getenv('SECURE_API_KEY'),
            'records' => $records,
            'complaint' => $complaint
        ]);
    }

    /**
     * 不分离后台 - 商城配置页面
     * 
     * @return Response 
     */
    public function pageMallConfiguration(Request $request)
    {
        // 获取未发货数量
        $records = RedemptionRecords::where('status', RedemptionRecordsEnums\Status::NoShipment->value)->count();
        // 获取投诉数量
        $complaint = Complaint::where('read', ComplaintEnums\Read::Unread->value)->count();
        return view('shop/mall-configuration', [
            'secretKey' => getenv('SECURE_API_KEY'),
            'records' => $records,
            'complaint' => $complaint
        ]);
    }

    /**
     * 不分离后台 - 用户管理页面
     * 
     * @return Response 
     */
    public function pageUserManagement(Request $request, $page = null)
    {
        // 获取未发货数量
        $records = RedemptionRecords::where('status', RedemptionRecordsEnums\Status::NoShipment->value)->count();
        // 获取投诉数量
        $complaint = Complaint::where('read', ComplaintEnums\Read::Unread->value)->count();
        $page = !empty($page) ? $page : 1;
        return view('shop/user-management', [
            'secretKey' => getenv('SECURE_API_KEY'),
            'page' => $page,
            'records' => $records,
            'complaint' => $complaint
        ]);
    }

    /**
     * 不分离后台 - 商品管理页面
     * 
     * @return Response 
     */
    public function pageProductManagement(Request $request, $page = null)
    {
        // 获取未发货数量
        $records = RedemptionRecords::where('status', RedemptionRecordsEnums\Status::NoShipment->value)->count();
        // 获取投诉数量
        $complaint = Complaint::where('read', ComplaintEnums\Read::Unread->value)->count();
        $page = !empty($page) ? $page : 1;
        return view('shop/product-management', [
            'secretKey' => getenv('SECURE_API_KEY'),
            'page' => $page,
            'records' => $records,
            'complaint' => $complaint
        ]);
    }

    /**
     * 不分离后台 - 发货管理页面
     * 
     * @return Response 
     */
    public function pageShippingManagement(Request $request, $page = null)
    {
        // 获取未发货数量
        $records = RedemptionRecords::where('status', RedemptionRecordsEnums\Status::NoShipment->value)->count();
        // 获取投诉数量
        $complaint = Complaint::where('read', ComplaintEnums\Read::Unread->value)->count();
        $page = !empty($page) ? $page : 1;
        return view('shop/shipping-management', [
            'secretKey' => getenv('SECURE_API_KEY'),
            'page' => $page,
            'records' => $records,
            'complaint' => $complaint
        ]);
    }

    /**
     * 不分离后题 - 投诉管理页面
     * 
     * @return Response 
     */
    public function pageComplaintManagement(Request $request, $page = null)
    {
        // 获取未发货数量
        $records = RedemptionRecords::where('status', RedemptionRecordsEnums\Status::NoShipment->value)->count();
        // 获取投诉数量
        $complaint = Complaint::where('read', ComplaintEnums\Read::Unread->value)->count();
        $page = !empty($page) ? $page : 1;
        return view('shop/complaint-management', [
            'secretKey' => getenv('SECURE_API_KEY'),
            'page' => $page,
            'records' => $records,
            'complaint' => $complaint
        ]);
    }

    /**
     * 不分离后台 - 问题反馈页面
     * 
     * @return Response 
     */
    public function pageFeedback(Request $request)
    {
        // 获取未发货数量
        $records = RedemptionRecords::where('status', RedemptionRecordsEnums\Status::NoShipment->value)->count();
        // 获取投诉数量
        $complaint = Complaint::where('read', ComplaintEnums\Read::Unread->value)->count();
        return view('shop/feedback', [
            'secretKey' => getenv('SECURE_API_KEY'),
            'records' => $records,
            'complaint' => $complaint
        ]);
    }

    /**
     * 不分离后台 - 礼物记录页面
     * 
     * @return Response 
     */
    public function pageGiftRecords(Request $request, $page = null)
    {
        // 获取未发货数量
        $records = RedemptionRecords::where('status', RedemptionRecordsEnums\Status::NoShipment->value)->count();
        // 获取投诉数量
        $complaint = Complaint::where('read', ComplaintEnums\Read::Unread->value)->count();
        $page = !empty($page) ? $page : 1;
        return view('shop/gift-records', [
            'secretKey' => getenv('SECURE_API_KEY'),
            'page' => $page,
            'records' => $records,
            'complaint' => $complaint
        ]);
    }

    /**
     * 不分离后台 - 用户分析页面
     * 
     * @return Response 
     */
    public function pageUserAnalysis(Request $request, $page = null)
    {
        // 获取未发货数量
        $records = RedemptionRecords::where('status', RedemptionRecordsEnums\Status::NoShipment->value)->count();
        // 获取投诉数量
        $complaint = Complaint::where('read', ComplaintEnums\Read::Unread->value)->count();
        $page = !empty($page) ? $page : 1;
        return view('shop/user-analysis', [
            'secretKey' => getenv('SECURE_API_KEY'),
            'page' => $page,
            'records' => $records,
            'complaint' => $complaint
        ]);
    }

    /**
     * 不分离后台 - 礼物盲盒页面
     * 
     * @return Response 
     */
    public function pageGiftBlindBox(Request $request, $page = null)
    {
        // 获取未发货数量
        $records = RedemptionRecords::where('status', RedemptionRecordsEnums\Status::NoShipment->value)->count();
        // 获取投诉数量
        $complaint = Complaint::where('read', ComplaintEnums\Read::Unread->value)->count();
        $page = !empty($page) ? $page : 1;
        return view('shop/gift-blind-box', [
            'secretKey' => getenv('SECURE_API_KEY'),
            'page' => $page,
            'records' => $records,
            'complaint' => $complaint
        ]);
    }

    /**
     * 不分离后台 - 弹幕记录页面
     * 
     * @return Response 
     */
    public function pageDanmuRecords(Request $request, $page = null)
    {
        // 获取未发货数量
        $records = RedemptionRecords::where('status', RedemptionRecordsEnums\Status::NoShipment->value)->count();
        // 获取投诉数量
        $complaint = Complaint::where('read', ComplaintEnums\Read::Unread->value)->count();
        $page = !empty($page) ? $page : 1;
        return view('shop/danmu-records', [
            'secretKey' => getenv('SECURE_API_KEY'),
            'page' => $page,
            'records' => $records,
            'complaint' => $complaint
        ]);
    }
}
