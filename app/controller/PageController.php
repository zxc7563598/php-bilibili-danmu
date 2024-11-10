<?php

namespace app\controller;

use app\server\Bilibili;
use Endroid\QrCode\Builder\Builder;
use Endroid\QrCode\Encoding\Encoding;
use Endroid\QrCode\ErrorCorrectionLevel;
use Endroid\QrCode\RoundBlockSizeMode;
use Endroid\QrCode\Writer\PngWriter;
use support\Request;
use Hejunjie\Bililive;
use Hejunjie\Tools;

class PageController
{
    public function main(Request $request)
    {
        return view('main/console', [
            'secretKey' => getenv('SECURE_API_KEY')
        ]);
    }

    public function login(Request $request)
    {
        // 获取登录信息
        $getQrcode = Bililive\Login::getQrcode();
        // 信息存储，并生成二维码
        $code = new Builder();
        $code->build(new PngWriter(), null, null, $getQrcode['url'], new Encoding('UTF-8'), null, 300, 10)
            ->saveToFile(public_path() . '/qrcode.png');
        // 返回数据
        return view('main/login', [
            'url' => $request->host() . '/qrcode.png',
            'qrcode_key' => $getQrcode['qrcode_key'],
            'secretKey' => getenv('SECURE_API_KEY')
        ]);
    }
}
