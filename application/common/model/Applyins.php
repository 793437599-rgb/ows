<?php


namespace app\common\model;


class Applyins  extends  ModelBase
{
    protected $autoWriteTimestamp = 'datetime';
    protected $table = 'think_apply_ins';

    public function getEvidenceAttr($value)
    {
        return empty($value) ? [] : unserialize($value);
    }
}