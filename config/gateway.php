<?php

return [
    'register' => [
        'listen' => 'text://127.0.0.1:1236',
    ],

    'gateway' => [
        'listen'           => 'websocket://0.0.0.0:2346',
        'name'             => 'VtpGateway',
        'count'            => 1,
        'lan_ip'           => '127.0.0.1',
        'start_port'       => 2900,
        'register_address' => '127.0.0.1:1236',
    ],

    'business_worker' => [
        'name'             => 'VtpBusinessWorker',
        'count'            => 1,
        'event_handler'    => app\worker\Events::class,
        'register_address' => '127.0.0.1:1236',
    ],
];
