<?php


namespace app\common\model;


class Share extends ModelBase
{
    protected $autoWriteTimestamp = 'datetime';
    protected $insert = ['create_ip'];
    public function  setCreateIpAttr()
    {
        return request()->ip();
    }
}