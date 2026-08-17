<?php

namespace app\common\model;

use think\Model;

class Edu extends ModelBase
{
    //

    /**
     * 序列化transcript图集
     * @param $value
     * @return string
     */
    protected function setTranscriptAttr($value)
    {
        return serialize($value);
    }

    /**
     * 反序列化transcript图集
     * @param $value
     * @return mixed
     */
    protected function getTranscriptAttr($value)
    {
        //return unserialize($value);
    }

    public function credential()
    {
        return $this->hasMany('UserCredential', 'edu_id', 'id');
    }


}
