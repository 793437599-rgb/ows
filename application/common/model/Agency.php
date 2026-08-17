<?php


namespace app\common\model;


class Agency extends ModelBase
{
    protected $autoWriteTimestamp = 'datetime';

    public function  getStatusAttr($value)
    {
          return $value == 1 ? '正常' : '禁用';
    }

    public function logins()
    {
        return $this->hasMany('AgencyLogin', 'agency_id');
    }

    public function changes()
    {
        return $this->hasMany('AgencyChange', 'agency_id');
    }

    public function orders()
    {
        return $this->hasMany('AgencyOrder', 'agency_id');
    }

    public function binds()
    {
        return $this->hasMany('UserOrder', 'edu_code','unique_code');
    }

    public function changeRecords()
    {
        return $this->hasMany('AgencyChange', 'agency_id');
    }


}