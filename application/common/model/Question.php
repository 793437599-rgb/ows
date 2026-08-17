<?php


namespace app\common\model;


class Question extends ModelBase
{
    protected $autoWriteTimestamp = 'datetime';
    public function category()
    {
       return $this->belongsTo('QuestionCategoty','category_id');
    }
}