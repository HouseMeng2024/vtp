<?php
declare (strict_types = 1);

namespace app\common\model;

use think\Model;
use think\model\concern\SoftDelete;

/**
 * 内容分类模型。
 */
class ContentCategory extends Model
{
    use SoftDelete;
}

