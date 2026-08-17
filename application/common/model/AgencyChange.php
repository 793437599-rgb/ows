<?php


namespace app\common\model;


class AgencyChange extends ModelBase
{
    protected $autoWriteTimestamp = 'datetime';
    protected $table = 'think_agency_change_record';
    protected $updateTime = false;
}