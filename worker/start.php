<?php

use Workerman\Worker;

require_once __DIR__ . '/../vendor/autoload.php';

$runtimePath = dirname(__DIR__) . DIRECTORY_SEPARATOR . 'runtime' . DIRECTORY_SEPARATOR;

Worker::$pidFile    = $runtimePath . 'gateway_worker.pid';
Worker::$stdoutFile = $runtimePath . 'gateway_worker.log';
Worker::$logFile    = $runtimePath . 'gateway_worker.log';

require_once __DIR__ . '/start_register.php';
require_once __DIR__ . '/start_gateway.php';
require_once __DIR__ . '/start_businessworker.php';

Worker::runAll();
