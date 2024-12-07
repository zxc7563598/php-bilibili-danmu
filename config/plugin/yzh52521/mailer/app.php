<?php

return [
    'enable' => true,
    'mailer' => [
        'scheme'   => 'smtp',// "smtps": using TLS, "smtp": without using TLS.
        'host'     => '', // 服务器地址
        'username' => '', //用户名
        'password' => '', // 密码
        'port'     => 465, // SMTP服务器端口号,一般为25
        'options'  => [], // See: https://symfony.com/doc/current/mailer.html#tls-peer-verification
        //'dsn'      => '',
    ],
    'from'   => [
        'address' => 'hello@example.com',
        'name'    => 'Example',
    ],
];
