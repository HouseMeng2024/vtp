<?php
declare (strict_types = 1);

namespace app\common\model;

class AdminUser extends AdminModel
{
    protected $hidden = ['password'];
}
