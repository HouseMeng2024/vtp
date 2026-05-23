<?php
declare (strict_types = 1);

namespace app\common\model;

use think\Model;
use think\model\concern\SoftDelete;

abstract class AdminModel extends Model
{
    use SoftDelete;
}
