<?php

namespace app\common\model;

use think\Model;

class UserAddress extends ModelBase
{
    protected $insert = ['create_ip'];

    public function setCreateIpAttr()
    {
        return request()->ip();
    }
}