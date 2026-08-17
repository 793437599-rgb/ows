<?php


namespace app\common\model;


class Report  extends  ModelBase
{
   // protected $autoWriteTimestamp = 'datetime';
    public function getEvidenceAttr($value)
    {
        return empty($value) ? [] : unserialize($value);
    }
}