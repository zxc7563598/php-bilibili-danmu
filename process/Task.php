<?php

namespace process;

use app\queue\SendMessage;
use Workerman\Crontab\Crontab;
use Workerman\Timer;

class Task
{
    public function onWorkerStart()
    {
        // new Crontab('*/3 * * * * *', function () {
        //     SendMessage::processQueue();
        // });

        Timer::add(3, function () {
            SendMessage::processQueue();
        });
    }
}
