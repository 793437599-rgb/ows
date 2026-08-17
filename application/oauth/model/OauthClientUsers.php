<?php

namespace app\oauth\Model;

use think\Model;

class OauthClientUsers extends Model
{
    use \app\common\traits\traitModel;
    protected $autoWriteTimestamp = true;   //自动写入创建和更新的时间戳
    // 定义时间戳字段名
    protected $createTime = 'ctime';
    protected $updateTime = 'etime';
    protected $string_delimiter=',';
    protected $expire_time = 0;     // oauth_client_users 有效期/过期时间
    protected $_validate = array(
        array('client_id','require','Oauth应用appid为空'),
        array('openid','require','用户在Oauth应用中的openid为空'),
        array('user','require','第三方Oauth应用中的用户账号为空'),
        array('userinfo','check_userinfo','非法的第三方Oauth应用中的用户账号信息详情',0,'callback'),
        array('third_user','require','第三方Oauth应用中的用户账号为空'),
        array('third_userinfo','check_userinfo','非法的第三方Oauth应用中的用户账号信息详情',0,'callback'),
        array('status',array(1,2),'非法Oauth应用用户状态',0,'in'),
    );
    protected $auto = [];
    protected $insert = ['status'];
    protected $update = [];
    //自定义初始化
    protected function initialize()
    {
        //需要调用`Model`的`initialize`方法
        parent::initialize();
        //TODO:自定义的初始化
        $this->get_allow_field_list(array());
    }
    //自定义初始化(只会在初始化实例时执行一次)
    protected static function init()
    {
        //TODO:自定义的初始化
        self::event('before_insert', function ($self) {
            if(in_array($self->status,array(1,2))){
                $self->status = 1;
            }
        });
        self::event('before_update', function ($self) {});
        self::event('before_delete', function ($self) {});

        $clear_cache=function($self){
            $id=$self->id?$self->id:0;
            if(empty($id)){
                $updateWhere=$self->getWhere();
                $id=$updateWhere['id']?$updateWhere['id']:0;
            }
            $openid=$self->openid?$self->openid:'';
            if(empty($openid)){
                $updateWhere=$self->getWhere();
                $openid=$updateWhere['openid']?$updateWhere['openid']:'';
            }
            $self->clear_cache($id,$openid);
        };
        self::event('after_insert', $clear_cache);
        self::event('after_update', $clear_cache);
        self::event('after_delete', $clear_cache);
    }
    public function clear_cache($id=0,$openid=''){
        $id_list=array();
        if(is_numeric($id)){
            $id_list[]=$id;
        }elseif(is_array($id)){
            $id_list=array_merge($id_list,$id);
        }
        $id_list = is_array($id_list)&&!empty($id_list)?$id_list:array(0);
        $id_list = array_unique($id_list);
        $openid_list=array();
        if(is_string($openid)){
            $openid_list[]=$openid;
        }elseif(is_array($openid)){
            $openid_list=array_merge($openid_list,$openid);
        }
        $openid_list = is_array($openid_list)&&!empty($openid_list)?$openid_list:array();
        $openid_list = array_unique($openid_list);
        $map=array();
        if(!empty($openid_list)){
            $map="`id` in ('".implode(',', $id_list)."') || `openid` in ('".implode(',', $openid_list)."')";
        }else{
            $map['id']=array('in',$id_list);
        }
        $oauth_info_list=db('oauth_client_users')->where($map)->field('id,openid')->select();
        foreach($oauth_info_list as $oauth_info_temp){
            cache('wse_get_oauth_client_users_info_by_id_'.$oauth_info_temp['id'],null);
            cache('wse_get_oauth_client_users_info_by_openid_'.$oauth_info_temp['openid'],null);
        }
    }
    /*--------------  检查字段 start  --------------*/
    public function check_field(&$param=array()){
        $param['id']=intval($param['id']);
        $param['id']=$param['id']>0?$param['id']:0;
        $param['client_id'] = diy_trim($param['client_id']);
        if(isset($param['userinfo'])){
            if(is_string($param['userinfo'])&&$this->is_json($param['userinfo'])){
                $param['userinfo']=json_decode($param['userinfo'],true);
            }
            $param['user']=diy_trim($param['user']);
            if(empty($param['userinfo'])){
                $this->error = lang('error_10000024');
                return false;
            }elseif(is_array($param['userinfo'])){
                ksort($param['userinfo']);
                array_walk($param['userinfo'],function(&$item,&$key){
                    $item=(string)trim($item);
                    $key=(string)trim($key);
                });
                $user_temp=implode(' ',$param['userinfo']);
                $user_temp=trim($user_temp);
                if(empty($param['user'])||$user_temp!=$param['user']){
                    $param['user']=$user_temp;
                }
            }else{
                $this->error = lang('error_10000022');
                return false;
            }
        }
        if(isset($param['third_userinfo'])){
            if(is_string($param['third_userinfo'])&&$this->is_json($param['third_userinfo'])){
                $param['third_userinfo']=json_decode($param['third_userinfo'],true);
            }
            $param['third_user']=diy_trim($param['third_user']);
            if(empty($param['third_userinfo'])){
                $this->error = lang('error_10000036');
                return false;
            }elseif(is_array($param['third_userinfo'])){
                ksort($param['third_userinfo']);
                array_walk($param['third_userinfo'],function(&$item,&$key){
                    $item=(string)trim($item);
                    $key=(string)trim($key);
                });
                $user_temp=implode(' ',$param['third_userinfo']);
                $user_temp=trim($user_temp);
                if(empty($param['third_user'])||$user_temp!=$param['third_user']){
                    $param['third_user']=$user_temp;
                }
            }else{
                $this->error = lang('error_10000037');
                return false;
            }
        }
        if(!empty($param['client_id'])){
            if(isset($param['openid'])){
                $param['openid'] = diy_trim($param['openid']);
                $map = array();
                $map['client_id'] = $param['client_id'];
                $map['openid'] = $param['openid'];
                $map['id'] = array('neq' , $param['id']);
                $status = db('oauth_client_users')->where( $map )->field( 'id' )->find();
                if ( !empty( $status ) ) {
                    $this->error = lang('error_10000025');
                    return false;
                }
            }
            if(isset($param['user'])){
                $param['user'] = diy_trim($param['user']);
                if(empty($param['user'])){
                    $this->error = lang('error_10000021');
                    return false;
                }
                $map = array();
                $map['client_id'] = $param['client_id'];
                $map['user'] = $param['user'];
                $map['id'] = array('neq' , $param['id']);
                $status = db('oauth_client_users')->where( $map )->field( 'id' )->find();
                if ( !empty( $status ) ) {
                    $this->error = lang('error_10000026');
                    return false;
                }
            }
            if(isset($param['third_user'])){
                $param['third_user'] = diy_trim($param['third_user']);
                if(empty($param['third_user'])){
                    $this->error = lang('error_10000038');
                    return false;
                }
                $map = array();
                $map['client_id'] = $param['client_id'];
                $map['third_user'] = $param['third_user'];
                $map['id'] = array('neq' , $param['id']);
                $status = db('oauth_client_users')->where( $map )->field( 'id' )->find();
                if ( !empty( $status ) ) {
                    $this->error = lang('error_10000039');
                    return false;
                }
            }
        }
        return true;
    }
    public function check_userinfo(&$userinfo=array()){
        if(empty($userinfo)){
            return false;
        }
        if($this->is_json($userinfo)){
            return true;
        }
        if(is_array($userinfo)){
            array_walk($userinfo,function(&$item,&$key){
                $item=(string)trim($item);
                $key=(string)trim($key);
            });
            $userinfo=json_encode($userinfo);
        }
        return true;
    }
    /*--------------  检查字段  end   --------------*/
    public function add_oauth_client_user($client_id='',$userinfo=array(),$third_userinfo=array(),$extra=''){
        $client_info=model('oauth/OauthClients')->get_info($client_id);
        if(empty($client_info)){
            $this->error=lang('error_10000014');
            return false;
        }
        if(empty($userinfo)||!is_array($userinfo)){
            $this->error=lang('error_10000021');
            return false;
        }
        $user_id=isset($userinfo['user_id'])&&$userinfo['user_id']>0?intval($userinfo['user_id']):0;
        unset($userinfo['user_id']);
        ksort($userinfo);
        array_walk($userinfo,function(&$item,&$key){
            $item=trim((string)$item);
            $key=trim((string)$key);
        });
        $status=true;
        $first_error_field='';
        $user_temp='';
        $userinfo_temp=array();
        foreach($client_info['client_user_fields'] as $val){
            $status=true;
            $user_temp='';
            $userinfo_temp=array();
            foreach($val as $v){
                if(!isset($userinfo[$v])||empty($userinfo[$v])){
                    $status=false;
                    if(empty($first_error_field)){
                        $first_error_field=$v;
                    }
                }else{
                    $user_temp.=$userinfo[$v].$this->string_delimiter;
                    $userinfo_temp[$v]=$userinfo[$v];
                }
            }
            if($status){
                $user_temp=trim($user_temp,$this->string_delimiter);
                break;
            }
        }
        if(!$status){
            $this->error=lang('error_10000027',[$first_error_field]);
            return false;
        }
        if(empty($user_temp)){
            $user_temp=$userinfo;
            $user_temp=implode($this->string_delimiter,$user_temp);
            $user_temp=trim($user_temp);
        }

        if($client_info['is_default']!=1){
            if(empty($third_userinfo)||!is_array($third_userinfo)){
                $this->error=lang('error_10000036');
                return false;
            }
            ksort($third_userinfo);
            array_walk($third_userinfo,function(&$item,&$key){
                $item=trim((string)$item);
                $key=trim((string)$key);
            });
            $status=true;
            $first_error_field='';
            $third_user_temp='';
            $third_userinfo_temp=array();
            foreach($client_info['third_user_fields'] as $val){
                $status=true;
                $third_user_temp='';
                $third_userinfo_temp=array();
                foreach($val as $v){
                    if(!empty($v)){
                        if(!isset($third_userinfo[$v])||empty($third_userinfo[$v])){
                            $status=false;
                            if(empty($first_error_field)){
                                $first_error_field=$v;
                            }
                        }else{
                            $third_user_temp.=$third_userinfo[$v].$this->string_delimiter;
                            $third_userinfo_temp[$v]=$third_userinfo[$v];
                        }
                    }
                }
                if($status){
                    $third_user_temp=trim($third_user_temp,$this->string_delimiter);
                    break;
                }
            }
            if(!$status){
                $this->error = lang('error_10000027',$first_error_field);
                return false;
            }
            if(empty($third_user_temp)){
                $third_user_temp=$third_userinfo;
                $third_user_temp=implode($this->string_delimiter,$third_user_temp);
                $third_user_temp=trim($third_user_temp);
            }
        }

        $map=array();
        if($client_info['is_default']!=1){
            if(empty($third_user_temp)){
                $map['client_id']=$client_id;
                $map['user']=$user_temp;
            }else{
                $map="`client_id`='".$client_id."' && (`user`='".$user_temp."' || `third_user`='".$third_user_temp."')";
            }
        }else{
            $map['client_id']=$client_id;
            $map['user']=$user_temp;
        }
        $info=db('oauth_client_users')->where($map)->order('ctime desc,id desc')->find();
        if(!empty($info) && $this->expire_time>0 && ($info['etime']-$info['ctime'])>$this->expire_time ){
            $info = array();
        }
        $data=array();
        $data['client_id']=$client_id;
        $data['user']=$user_temp;
        $data['userinfo']=json_encode($userinfo_temp);
        if(!empty($user_id)&&$user_id>0){
            if(empty($info)){
                $data['user_id']=$user_id;
            }else{
                if($info['user_id']==0){
                    $data['user_id']=$user_id;
                }
            }
        }
        if($client_info['is_default']!=1){
            if(!empty($third_user_temp)){
                $data['third_user']=$third_user_temp;
            }else{
                $data['third_user']='';
            }
            if(!empty($third_userinfo_temp)){
                $data['third_userinfo']=json_encode($third_userinfo_temp);
            }else{
                $data['third_userinfo']='';
            }
            $data['third_user']=!empty($data['third_user'])?$data['third_user']:'';
        }
        if(!empty($extra)){
            if(is_string($extra)){
                $data['extra']=$extra;
            }else{
                $data['extra']=json_encode($extra);
            }
        }
        $data['extra']=!empty($data['extra'])?$data['extra']:'';
        $data['status']=1;
        if(!empty($info)){
            $data['id']=$info['id'];
            $openid=$info['openid'];
        }else{
            $openid=$this->get_new_openid($client_id);
        }
        $data['openid']=$openid;
        if(isset($data['id'])&&$data['id']>0){
            $status=$this->validate('oauth/OauthClientUsers')->isUpdate(true)->save($data,['id'=>$data['id']]);
        }else{
            $status=$this->validate('oauth/OauthClientUsers')->isUpdate(false)->save($data);
        }
        if($status===false){
            $this->error = lang('error_10000028');
            return false;
        }
        return $openid;
    }
    public function get_new_openid($client_id=''){
        $client_id=is_string($client_id)?trim($client_id):'';
        $option=array(
            'letter'=>true,
            'letter_big'=>true,
            'number'=>true,
            'others'=>false,
        );
        $openid='LX'.get_random_string(30,$option);
        $map=array();
        $map['client_id']=$client_id;
        $map['openid']=$openid;
        $info=db('oauth_client_users')->where($map)->field('id')->find();
        if(!empty($info)){
            return $this->get_new_openid($client_id);
        }
        return $openid;
    }
    public function get_info($oauth_openid='',$field=''){
        $result=cache('wse_get_oauth_client_users_info_by_openid_'.$oauth_openid);
        if(empty($result)){
            $result=cache('wse_get_oauth_client_users_info_by_id_'.$oauth_openid);
        }
        if(empty($result)){
            $result=db('oauth_client_users')->where('id|openid','eq',$oauth_openid)->find();
            if(!empty($result)){
                if($this->is_json($result['userinfo'])){
                    $result['userinfo'] = json_decode($result['userinfo'],true);
                }
                if($this->is_json($result['third_userinfo'])){
                    $result['third_userinfo'] = json_decode($result['third_userinfo'],true);
                }
                if($this->is_json($result['extra'])){
                    $result['extra'] = json_decode($result['extra'],true);
                }
                cache('wse_get_oauth_client_users_info_by_id_'.$result['id'],$result,30*24*60*60);
                cache('wse_get_oauth_client_users_info_by_openid_'.$result['openid'],$result,30*24*60*60);
            }else{
                $result=array();
            }
        }
        if(!empty($field)){
            if(isset($result[$field])){
                $result=$result[$field];
            }else{
                $result='';
            }
        }
        return $result;
    }
}