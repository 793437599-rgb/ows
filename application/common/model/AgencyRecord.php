<?php


namespace app\common\model;


class AgencyRecord extends ModelBase
{
    protected $autoWriteTimestamp = 'datetime';
    protected $table = 'think_agency_login_record';
    protected $updateTime = false;
}