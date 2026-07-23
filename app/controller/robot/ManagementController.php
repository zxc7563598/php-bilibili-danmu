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
     * 获取导航栏共享数据（未发货数量、未读投诉数量）
     *
     * @return array{records: int, complaint: int}
     */
    private function getNavCounts(): array
    {
        return [
            'records' => RedemptionRecords::where('status', RedemptionRecordsEnums\Status::NoShipment->value)->count(),
            'complaint' => Complaint::where('read', ComplaintEnums\Read::Unread->value)->count(),
        ];
    }

    /**
     * 渲染后台页面视图，自动注入共享导航数据
     *
     * @param string $view 视图名称
     * @param array $data 额外数据
     * @return Response
     */
    private function renderPage(string $view, array $data = []): Response
    {
        return view($view, array_merge([
            'secretKey' => getenv('SECURE_API_KEY'),
        ], $this->getNavCounts(), $data));
    }

    /**
     * 不分离后台 - 系统配置页面
     *
     * @return Response
     */
    public function pageSystemConfiguration(Request $request): Response
    {
        return $this->renderPage('shop/system-configuration');
    }

    /**
     * 不分离后台 - 商城配置页面
     *
     * @return Response
     */
    public function pageMallConfiguration(Request $request): Response
    {
        return $this->renderPage('shop/mall-configuration');
    }

    /**
     * 不分离后台 - 用户管理页面
     *
     * @return Response
     */
    public function pageUserManagement(Request $request, $page = null): Response
    {
        return $this->renderPage('shop/user-management', [
            'page' => !empty($page) ? $page : 1,
        ]);
    }

    /**
     * 不分离后台 - 商品管理页面
     *
     * @return Response
     */
    public function pageProductManagement(Request $request, $page = null): Response
    {
        return $this->renderPage('shop/product-management', [
            'page' => !empty($page) ? $page : 1,
        ]);
    }

    /**
     * 不分离后台 - 发货管理页面
     *
     * @return Response
     */
    public function pageShippingManagement(Request $request, $page = null): Response
    {
        return $this->renderPage('shop/shipping-management', [
            'page' => !empty($page) ? $page : 1,
        ]);
    }

    /**
     * 不分离后台 - 投诉管理页面
     *
     * @return Response
     */
    public function pageComplaintManagement(Request $request, $page = null): Response
    {
        return $this->renderPage('shop/complaint-management', [
            'page' => !empty($page) ? $page : 1,
        ]);
    }

    /**
     * 不分离后台 - 问题反馈页面
     *
     * @return Response
     */
    public function pageFeedback(Request $request): Response
    {
        return $this->renderPage('shop/feedback');
    }

    /**
     * 不分离后台 - 礼物记录页面
     *
     * @return Response
     */
    public function pageGiftRecords(Request $request, $page = null): Response
    {
        return $this->renderPage('shop/gift-records', [
            'page' => !empty($page) ? $page : 1,
        ]);
    }

    /**
     * 不分离后台 - 用户分析页面
     *
     * @return Response
     */
    public function pageUserAnalysis(Request $request, $page = null): Response
    {
        return $this->renderPage('shop/user-analysis', [
            'page' => !empty($page) ? $page : 1,
        ]);
    }

    /**
     * 不分离后台 - 礼物盲盒页面
     *
     * @return Response
     */
    public function pageGiftBlindBox(Request $request, $page = null): Response
    {
        return $this->renderPage('shop/gift-blind-box', [
            'page' => !empty($page) ? $page : 1,
        ]);
    }

    /**
     * 不分离后台 - 弹幕记录页面
     *
     * @return Response
     */
    public function pageDanmuRecords(Request $request, $page = null): Response
    {
        return $this->renderPage('shop/danmu-records', [
            'page' => !empty($page) ? $page : 1,
        ]);
    }
}
