<?php

namespace app\controller\robot;

use support\Request;
use Hejunjie\Bililive;
use Endroid\QrCode\Builder\Builder;
use Endroid\QrCode\Writer\PngWriter;
use Endroid\QrCode\Encoding\Encoding;

class PageController
{
    public function main(Request $request)
    {
        return view('main/console', [
            'secretKey' => getenv('SECURE_API_KEY')
        ]);
    }


    public function init(Request $request)
    {
        return view('main/init', [
            'secretKey' => getenv('SECURE_API_KEY')
        ]);
    }

    public function login(Request $request)
    {
        // 获取登录信息
        $getQrcode = Bililive\Login::getQrcode();
        $qrcode = md5($getQrcode['qrcode_key'] . 'qrcode') . '.png';
        // 确认目录信息，不存在则创建
        if (!is_dir(public_path() . '/attachment/qrcode')) {
            mkdir(public_path() . '/attachment/qrcode', 0777, true);
        }
        // 信息存储，并生成二维码
        $code = new Builder();
        $code->build(new PngWriter(), null, null, $getQrcode['url'], new Encoding('UTF-8'), null, 300, 10)
            ->saveToFile(public_path() . '/attachment/qrcode/' . $qrcode);
        // 返回数据
        return view('main/login', [
            'url' => '/attachment/qrcode/' . $qrcode,
            'qrcode_key' => $getQrcode['qrcode_key'],
            'secretKey' => getenv('SECURE_API_KEY')
        ]);
    }
}
