<?php


namespace app\common\model;


class AgencyOrder extends Agency
{
    protected $autoWriteTimestamp = 'datetime';
    protected  $table = 'think_agency_order';


    public function agency()
    {
        return $this->belongsTo('Agency');
    }

    public function getStatusAttr($value)
    {
        return $value == 2 ? '已支付' : '未支付';
    }

    public function getPayTypeAttr($value)
    {
          if ($value == 1){
              return 'Alipay';
          } elseif ($value == 2){
              return 'WeChat Pay';
          } else {
              return 'Offline Pay';
          }
    }
}