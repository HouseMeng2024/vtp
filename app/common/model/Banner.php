<?php
declare (strict_types = 1);

namespace app\common\model;

use think\Model;
use think\model\concern\SoftDelete;

/**
 * 幻灯模型。
 */
class Banner extends Model
{
    use SoftDelete;
}

