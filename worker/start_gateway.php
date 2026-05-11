<?php

use GatewayWorker\Gateway;

$config = require dirname(__DIR__) . '/config/gateway.php';
$gatewayConfig = $config['gateway'];

$gateway                  = new Gateway($gatewayConfig['listen']);
$gateway->name            = $gatewayConfig['name'];
$gateway->count           = $gatewayConfig['count'];
$gateway->lanIp           = $gatewayConfig['lan_ip'];
$gateway->startPort       = $gatewayConfig['start_port'];
$gateway->registerAddress = $gatewayConfig['register_address'];
