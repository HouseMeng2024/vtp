<?php
declare (strict_types = 1);

namespace app\common\model;

use think\Model;
use think\model\concern\SoftDelete;

/**
 * 文章模型。
 */
class Article extends Model
{
    use SoftDelete;
}

