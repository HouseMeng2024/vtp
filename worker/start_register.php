<?php

use GatewayWorker\Register;

$config = require dirname(__DIR__) . '/config/gateway.php';

new Register($config['register']['listen']);
