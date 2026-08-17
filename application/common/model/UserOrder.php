<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2019/8/9 0009
 * Time: 14:26
 */

namespace app\common\model;

use think\Model;

class UserOrder extends ModelBase
{
    public function user()
    {
        return $this->belongsTo('User');
    }

    public function getPayTypeAttr($value)
    {
        if ($value == 1) {
            return '线上支付';
        } else if ($value == 2) {
            return '后台手动付款';
        } else if ($value == 3) {
            return '劵码抵消';
        } else {
            return '线上支付';
        }
    }

    public function orderDetail()
    {
        return $this->hasOne('OrderDetail','order_id');
    }

    public function application()
    {
        return $this->belongsTo('Applications','app_id');
    }

    public function certificate()
    {
        return $this->hasOne('Certificate', 'order_id');
    }

    public function transcript()
    {
        return $this->hasOne('Transcript', 'order_id');
    }
    /**
     * 获取责任人
     * @param $user_id
     * @param string $app_ids
     * @return array|bool|string
     */
    public function getAgency($condition,$app_ids = '')
    {
        $edu_code = $this->where('user_id|agency_id',$condition)->whereIn('app_id',$app_ids)->distinct(true)->column('edu_code');
        $agencies = model('Agency')->whereIn('unique_code',$edu_code)->column('username');
        return $agencies;
    }
}