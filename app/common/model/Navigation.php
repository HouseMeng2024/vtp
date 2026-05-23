<?php
declare (strict_types = 1);

namespace app\common\model;

use think\Model;
use think\model\concern\SoftDelete;

/**
 * 导航模型。
 */
class Navigation extends Model
{
    use SoftDelete;
}

