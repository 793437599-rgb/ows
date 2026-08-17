<?php


namespace app\common\model;


class QuestionCategoty extends ModelBase
{
    protected $autoWriteTimestamp = 'datetime';

    public function getStatusAttr($value)
    {
        return $value == 1 ? '显示' : '隐藏';
    }

    public function questions()
    {
        return $this->hasMany('Question', 'category_id');
    }
}