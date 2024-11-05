<?php

namespace app\controller;

use support\Request;

class IndexController
{
    public function main(Request $request)
    {
        // 获取登录信息配置
        $cookie = strval(readFileContent(runtime_path() . '/tmp/cookie'));
        // 获取直播间信息配置
        $room_id = intval(readFileContent(runtime_path() . '/tmp/connect'));

        return view('main/console', ['name' => 'webman']);
    }
}
