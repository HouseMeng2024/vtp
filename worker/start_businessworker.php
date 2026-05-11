<?php

use GatewayWorker\BusinessWorker;

$config = require dirname(__DIR__) . '/config/gateway.php';
$workerConfig = $config['business_worker'];

$worker                  = new BusinessWorker();
$worker->name            = $workerConfig['name'];
$worker->count           = $workerConfig['count'];
$worker->eventHandler    = $workerConfig['event_handler'];
$worker->registerAddress = $workerConfig['register_address'];
