<?php

/**
 * This file is part of webman.
 *
 * Licensed under The MIT License
 * For full copyright and license information, please see the MIT-LICENSE.txt
 * Redistributions of files must retain the above copyright notice.
 *
 * @author    walkor<walkor@workerman.net>
 * @copyright walkor<walkor@workerman.net>
 * @link      http://www.workerman.net/
 * @license   http://www.opensource.org/licenses/mit-license.php MIT License
 */

namespace app\middleware;

use app\model\Users;
use Carbon\Carbon;
use Webman\MiddlewareInterface;
use Webman\Http\Response;
use Webman\Http\Request;
use support\Redis;

/**
 * 鉴权
 * @package app\middleware
 */
class SignatureMiddleware implements MiddlewareInterface
{
    public function process(Request $request, callable $next): Response
    {
        $signature = $request->header('X-Signature');
        $secretKey = getenv('SECURE_API_KEY');

        $body = $request->rawBody();
        $computedSignature = hash_hmac('sha256', $body, $secretKey);

        if (hash_equals($computedSignature, $signature)) {
            return $next($request);
        }

        return response('Invalid signature', 403);
    }
}
