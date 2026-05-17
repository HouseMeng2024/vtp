<?php
declare (strict_types = 1);

namespace app\common\base;

use app\BaseController;
use app\common\support\ConfigValue;

class IndexBase extends BaseController
{
    /**
     * 初始化前台控制器。
     */
    protected function initialize()
    {
        parent::initialize();
        ConfigValue::loadGroupsToConfig('system', ['system']);
        ConfigValue::loadGroupsToConfig('index', ['index']);
    }
}
