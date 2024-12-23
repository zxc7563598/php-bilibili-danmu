<?php

namespace app\controller\shop;

use app\controller\GeneralMethod;
use app\core\LoginPublicMethods;
use app\model\UserVips;
use support\Request;
use support\Redis;
use Webman\Http\Response;
use resource\enums\UserVipsEnums;

class ManagementController extends GeneralMethod
{
    public function pageSystemConfiguration(Request $request)
    {
        return view('shop/system-configuration', [
            'secretKey' => getenv('SECURE_API_KEY')
        ]);
    }
    public function pageMallConfiguration(Request $request)
    {
        return view('shop/mall-configuration', [
            'secretKey' => getenv('SECURE_API_KEY')
        ]);
    }
    public function pageUserManagement(Request $request, $page = null)
    {
        $page = !empty($page) ? $page : 1;
        return view('shop/user-management', [
            'secretKey' => getenv('SECURE_API_KEY'),
            'page' => $page
        ]);
    }
    public function pageProductManagement(Request $request)
    {
        return view('shop/product-management', [
            'secretKey' => getenv('SECURE_API_KEY')
        ]);
    }
    public function pageShippingManagement(Request $request)
    {
        return view('shop/shipping-management', [
            'secretKey' => getenv('SECURE_API_KEY')
        ]);
    }
    public function pageComplaintManagement(Request $request)
    {
        return view('shop/complaint-management', [
            'secretKey' => getenv('SECURE_API_KEY')
        ]);
    }
    public function pageFeedback(Request $request)
    {
        return view('shop/feedback', [
            'secretKey' => getenv('SECURE_API_KEY')
        ]);
    }
}
