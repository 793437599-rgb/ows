<?php

namespace app\oauth\Model;

use think\Model;

class OauthUsers extends Model
{
    use \app\common\traits\traitModel;
    protected $autoWriteTimestamp = false;
    protected $_validate = array(
        array('username','require','Oauth账户账号为空'),
        array('password','check_password','Oauth账户密码不符合密码规则',0,'callback'),
        //array('first_name','require','Oauth账户用户名为空'),
        //array('last_name','require','Oauth账户用户姓为空'),
        array('email','require','Oauth账户绑定邮箱为空'),
        array('email_verified',array(0,1),'非法Oauth邮箱验证状态',0,'in'),
        array('scope','is_string','非法Oauth账户权限',0,'function'),
    );
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
            if(!isset($self->email_verified)||!in_array($self->email_verified,array(0,1))){
                $self->email_verified=0;
            }
        });
        self::event('before_update', function ($self) {});
        self::event('before_delete', function ($self) {});

        $clear_cache=function($self){
            $oauth_uid=$self->uid?$self->uid:0;
            if(empty($oauth_uid)){
                $updateWhere=$self->getWhere();
                $oauth_uid=$updateWhere['uid']?$updateWhere['uid']:0;
            }
            $self->clear_cache($oauth_uid);
        };
        self::event('after_insert', $clear_cache);
        self::event('after_update', $clear_cache);
        self::event('after_delete', $clear_cache);
    }
    public function clear_cache($oauth_uid=0){
        $oauth_uid_list=array();
        if(is_numeric($oauth_uid)){
            $oauth_uid_list[]=$oauth_uid;
        }elseif(is_array($oauth_uid)){
            $oauth_uid_list=array_merge($oauth_uid_list,$oauth_uid);
        }
        cache('wse_get_system_oauth_user_info',null);
        cache('wse_get_third_oauth_user_list',null);
        $oauth_uid_list = is_array($oauth_uid_list)&&!empty($oauth_uid_list)?$oauth_uid_list:array(0);
        $oauth_uid_list = array_unique($oauth_uid_list);
        $oauth_info_list=db('oauth_users')->where(array('uid'=>array('in',$oauth_uid_list)))->field('uid,username,email')->select();
        foreach($oauth_info_list as $oauth_info_temp){
            cache('wse_get_oauth_users_info_by_uid_'.$oauth_info_temp['uid'],null);
            cache('wse_get_oauth_users_info_by_username_'.$oauth_info_temp['username'],null);
            cache('wse_get_oauth_users_info_by_email_'.$oauth_info_temp['email'],null);
        }
    }
    /*--------------  检查字段 start  --------------*/
    public function check_field(&$param=array()){
        $param['uid']=intval($param['uid']);
        $param['uid']=$param['uid']>0?$param['uid']:0;
        if(isset($param['password'])&&empty($param['password'])){
            unset($param['password']);
        }
        if(isset($param['username'])){
            $param['username'] = diy_trim($param['username']);
            $map = array();
            $map['username'] = $param['username'];
            $map['uid'] = array('neq' , $param['uid']);
            $status = db('oauth_users')->where( $map )->field( 'uid' )->find();
            if ( !empty( $status ) ) {
                $this->error = lang('error_10000013');
                return false;
            }
        }
        if(isset($param['email'])){
            $param['email'] = diy_trim($param['email']);
            $map = array();
            $map['email'] = $param['email'];
            $map['uid'] = array('neq' , $param['uid']);
            $status = db('oauth_users')->where( $map )->field( 'uid' )->find();
            if ( !empty( $status ) ) {
                $this->error = lang('error_10000040');
                return false;
            }
        }
        if(isset($param['scope'])){
            if(!empty($param['scope'])){
                if(is_array($param['scope'])){
                    $scope=$param['scope'];
                    $scope_list=model('oauth/OauthScopes')->get_list('all');
                    $param['scope']=array();
                    foreach($scope_list as $scope_temp){
                        if(in_array($scope_temp['scope'],$scope)){
                            $param['scope'][]=$scope_temp['scope'];
                        }
                    }
                    $param['scope']=implode(' ',$param['scope']);
                }
            }else{
                $param['scope']='';
            }
        }
        return true;
    }
    public function check_scope($scope=''){
        $scope = !empty($scope)?$scope:'';
        if(!is_string($scope)){
            $this->error=lang('error_10000041');
            return false;
        }
        $scope=preg_replace('/[ ]{1,}/',' ',$scope);
        return $scope;
    }
    public function check_password($password=''){
        return true;
    }
    /*--------------  检查字段  end   --------------*/
    public function getLastName($value)
    {
        if(empty($value)){
            $value='';
        }
        return $value;
    }
    public function getFirstName($value)
    {
        if(empty($value)){
            $value='';
        }
        return $value;
    }
    public function get_info($oauth_uid=0,$field=''){
        $result=cache('wse_get_oauth_users_info_by_uid_'.$oauth_uid);
        if(empty($result)){
            $result=cache('wse_get_oauth_users_info_by_username_'.$oauth_uid);
            if(empty($result)){
                $result=cache('wse_get_oauth_users_info_by_email_'.$oauth_uid);
            }
        }
        if(empty($result)){
            $result=db('oauth_users')->where('uid|username|email','eq',$oauth_uid)->find();
            if(!empty($result)){
                $result['scope']=explode(' ',$result['scope']);
                cache('wse_get_oauth_users_info_by_uid_'.$result['uid'],$result,30*24*60*60);
                cache('wse_get_oauth_users_info_by_username_'.$result['username'],$result,30*24*60*60);
                cache('wse_get_oauth_users_info_by_email_'.$result['email'],$result,30*24*60*60);
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
    public function edit($param=array()){
        $param['uid']=isset($param['uid'])?intval($param['uid']):0;
        if($param['uid']>0){
            $map=array();
            $map['uid']=$param['uid'];
            $info=db('oauth_users')->where($map)->find();
            if(empty($info)){
                $this->error=lang('error_10000042');
                return false;
            }
        }
        if(!$this->check_field($param)){
            return false;
        }
        \think\Db::startTrans();
        if($param['uid']>0){
            $status=$this->validate('oauth/OauthUsers.edit')->isUpdate(true)->save($param,['uid'=>$param['uid']]);
            if($status===false){
                \think\Db::rollback();
                return false;
            }
            $uid=$param['uid'];
        }else{
            unset($param['uid']);
            $status=$this->validate('oauth/OauthUsers')->isUpdate(false)->save($param);
            if(!$status){
                \think\Db::rollback();
                return false;
            }
            $uid=$this->uid;
        }
        if(isset($param['is_default'])&&$param['is_default']==1){
            $this->change_is_default(1,array('uid'=>$uid));
            $OauthClients=model('oauth/OauthClients');
            $map=array();
            $map['is_user']=2;
            $map['user_id']=$uid;
            $map['is_default']=1;
            $client_user_fields=$OauthClients->where($map)->value('client_user_fields');
            $client_user_fields=$client_user_fields?$client_user_fields:'';
            $map=array();
            $map['is_user']=2;
            $map['user_id']=array('neq',$uid);
            $map['is_default']=2;
            $app_id_list=$OauthClients->where($map)->column('client_id');
            $app_id_list=!empty($app_id_list)&&is_array($app_id_list)?$app_id_list:array('-1');
            $data=array();
            $data['client_user_fields']=$client_user_fields;
            $status=$OauthClients->save($data,$map);
            if($status===false){
                \think\Db::rollback();
                $this->error=$OauthClients->getError();
                $this->error=$this->error?$this->error:'更新其他client相关用户信息失败';
                return false;
            }
            $OauthClients->clear_cache($app_id_list);
        }
        \think\Db::commit();
        return $uid;
    }
    public function get_system_oauth_user($field=''){
        $result=cache('wse_get_system_oauth_user_info');
        if(empty($result)){
            $map=array();
            $map['is_default']=1;
            $result=db('oauth_users')->where($map)->find();
            if(!empty($result)){
                $result['scope']=explode(' ',$result['scope']);
                cache('wse_get_system_oauth_user_info',$result,30*24*60*60);
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
    public function get_list($field='',$has_system=false){
        $has_system = $has_system===true;
        if($has_system){
            $system_user=$this->get_system_oauth_user($field);
            $user=$this->get_list($field,false);
            array_unshift($user,$system_user);
            return $user;
        }
        $result=cache('wse_get_third_oauth_user_list');
        if(empty($result)){
            $map=array();
            $map['is_default']=2;
            $result = db('oauth_users')->where($map)->order('id asc')->select();
            if(!empty($result)){
                foreach($result as $key=>$val){
                    $result[$key]['scope'] = explode(' ',$val['scope']);
                }
                cache('wse_get_third_oauth_user_list',$result,30*24*60*60);
            }else{
                $result = array();
            }
        }
        if(!empty($field)){
            if(isset($result[0][$field])){
                $result = array_column($result,$field);
            }else{
                $result = array();
            }
        }
        return $result;
    }
    public function change_is_default($common_map = array() , $target_map = array()){
        $common_map = !empty($common_map)?$common_map:1;
        $this->change_default('is_default',$common_map,$target_map,1,2);

        $oauth_uid_list=db('oauth_users')->where($common_map)->column('uid');
        $oauth_uid_list = is_array($oauth_uid_list)&&!empty($oauth_uid_list)?$oauth_uid_list:array(0);
        $this->clear_cache($oauth_uid_list);



        $oauth_appid_list=db('oauth_clients')->where(1)->column('client_id');
        $oauth_appid_list = is_array($oauth_appid_list)&&!empty($oauth_appid_list)?$oauth_appid_list:array(0);
        model('oauth/OauthClients')->clear_cache($oauth_appid_list);

        return true;
    }
}