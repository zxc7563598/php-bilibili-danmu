<?php

namespace app\controller\shop;

use app\controller\GeneralMethod;
use app\core\LoginPublicMethods;
use app\model\Complaint;
use app\model\RedemptionRecords;
use app\model\UserVips;
use support\Request;
use support\Redis;
use Webman\Http\Response;
use resource\enums\UserVipsEnums;
use resource\enums\ComplaintEnums;
use resource\enums\RedemptionRecordsEnums;

class ManagementController extends GeneralMethod
{
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
}
