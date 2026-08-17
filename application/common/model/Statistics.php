<?php

namespace app\common\model;

class Statistics {
    public function clear_cache(){
        cache('wse_get_lxw_transmit_undonumber',null);
    }
    public function get_lxw_transmit_undonumber(){
        $result = cache('wse_get_lxw_transmit_undonumber');
        if(empty($result)){
            $result = array('undonumber_strict'=>0,'undonumber'=>0);

            $map=array();
            $map['third_id'] = 14;
            $map['lxw_send']=['not in',[6,8]];
            $result['undonumber_strict'] = db('user_tran')->where($map)->count();

            $map=array();
            $map['third_id'] = 14;
            $map['lxw_send']=['not in',[2,4,5,6,7,8]];
            $result['undonumber'] = db('user_tran')->where($map)->count();

            $result['undonumber_strict'] = is_numeric($result['undonumber_strict'])?$result['undonumber_strict']:0;
            $result['undonumber'] = is_numeric($result['undonumber'])?$result['undonumber']:0;
            cache('wse_get_lxw_transmit_undonumber',$result,30*24*60*60);
        }
        return $result;
    }
}