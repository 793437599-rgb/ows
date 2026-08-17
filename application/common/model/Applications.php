<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2019/8/9 0009
 * Time: 14:18
 */
namespace app\common\model;

use think\Model;

class Applications extends ModelBase
{
    public function steps(){
        return $this->hasMany('AppStep','app_id');
    }
}