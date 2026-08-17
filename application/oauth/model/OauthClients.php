<?php

namespace app\oauth\Model;

use think\Model;

class OauthClients extends Model
{
    use \app\common\traits\traitModel;
    protected $autoWriteTimestamp = false;
    protected $_validate = array(
        array('client_id','require','Oauth应用app_id为空'),
        array('client_secret','require','Oauth应用app_secret为空'),
        array('session_key','require','Oauth应用授权session_key为空'),
        array('client_name','require','Oauth应用名称为空'),
        array('allow_domain','is_string','非法Oauth应用安全域名',0,'function'),
        array('client_user_fields','is_string','非法Oauth应用用户账号所需字段',0,'function'),
        array('redirect_uri','is_string','非法Oauth应用授权URI',0,'function'),
        array('grant_types','is_string','非法Oauth应用授权类型',0,'function'),
	    array('scope','is_string','非法Oauth应用权限',0,'function'),
        //array('user_id','number','非法Oauth应用user_id'),
        array('is_default',array(1,2),'非法Oauth应用状态',0,'in'),
        array('is_user',array(1,2),'非法Oauth应用状态',0,'in'),
        array('need_authorize',array(1,2),'非法的Oauth应用回调授权需求状态',0,'in'),
        array('authorize_domain','require','Oauth应用回调授权域名为空'),
        array('authorize_is_ssl',array(1,2),'非法的Oauth应用回调授权域名ssl支持状态',0,'in'),
    );
    /*授权模式*/
    protected $grant_type_list=array(
        'authorization_code'=>array('name'=>'authorization_code','title'=>'授权码模式','info'=>'授权码模式(即先登录获取code,再获取token)'),
        'password'=>array('name'=>'password','title'=>'密码模式','info'=>'密码模式(将用户名,密码传过去,直接获取token)'),
        'client_credentials'=>array('name'=>'client_credentials','title'=>'客户端模式','info'=>'客户端模式(无用户,用户向客户端注册,然后客户端以自己的名义向’服务端’获取资源)'),
        'implicit'=>array('name'=>'implicit','title'=>'简化模式','info'=>'简化模式(在redirect_uri 的Hash传递token; Auth客户端运行在浏览器中,如JS,Flash)'),
        'refresh_token'=>array('name'=>'refresh_token','title'=>'刷新access_token','info'=>''),
    );
    protected $auto = [];
    protected $insert = ['is_default','is_user'];
    protected $update = [];
    //自定义初始化
    protected function initialize()
    {
        //需要调用`Model`的`initialize`方法
        parent::initialize();
        //TODO:自定义的初始化
        $this->get_allow_field_list(array('grant_type_list'));
    }
    //自定义初始化(只会在初始化实例时执行一次)
    protected static function init()
    {
        //TODO:自定义的初始化
        self::event('before_insert', function ($self) {
            if(in_array($self->is_default,array(1,2))){
                $self->is_default = 2;
            }
            if(in_array($self->is_user,array(1,2))){
                $self->is_user = 2;
            }
            if(!isset($data['need_authorize'])||!in_array($data['need_authorize'],array(1,2))){
                $data['need_authorize']=2;
            }
            if(empty($self->session_key)||!is_string($self->session_key)){
                $self->session_key = $self->get_new_session_key();
            }
        });
        self::event('before_update', function ($self) {});
        self::event('before_delete', function ($self) {});
        
        $clear_cache=function($self){
            $oauth_appid=property_exists($self,'client_id')&&$self->client_id?$self->client_id:'';
            if(empty($oauth_appid)){
                $updateWhere=$self->getWhere();
                $oauth_appid=isset($updateWhere['client_id'])&&$updateWhere['client_id']?$updateWhere['client_id']:'';
                if(empty($oauth_appid)){
                    $oauth_appid=property_exists($self,'id')&&$self->id?$self->id:(isset($updateWhere['id'])&&$updateWhere['id']?$updateWhere['id']:'');
                }
            }
            $self->clear_cache($oauth_appid);
        };
        self::event('after_insert', $clear_cache);
        self::event('after_update', $clear_cache);
        self::event('after_delete', $clear_cache);
    }
    public function clear_cache($oauth_appid=''){
        $oauth_appid_list=array();
        if(is_string($oauth_appid)||is_numeric($oauth_appid)){
            $oauth_appid_list[]=$oauth_appid;
        }elseif(is_array($oauth_appid)){
            $oauth_appid_list=array_merge($oauth_appid_list,$oauth_appid);
        }
        $oauth_appid_list = is_array($oauth_appid_list)&&!empty($oauth_appid_list)?$oauth_appid_list:array(0);
        $oauth_appid_list = array_unique($oauth_appid_list);
        cache('wse_get_system_oauth_clients_info_1',null);
        cache('wse_get_system_oauth_clients_info_2',null);
        $oauth_client_list=db('oauth_clients')->where('client_id|id','in',$oauth_appid_list)->field('id,client_id,session_key')->select();
        foreach($oauth_client_list as $oauth_client_temp){
            cache('wse_get_oauth_clients_info_by_appid_'.$oauth_client_temp['client_id'],null);
            cache('wse_get_oauth_client_keys_'.$oauth_client_temp['client_id'],null);
            cache('wse_get_oauth_clients_info_by_id_'.$oauth_client_temp['id'],null);
            cache('wse_get_oauth_client_session_key_info_'.$oauth_client_temp['session_key'],null);
        }
    }
    /*--------------  检查字段 start  --------------*/
    public function check_field(&$param=array(),$scene='edit'){
        $param['id']=intval($param['id']);
        $param['id']=$param['id']>0?$param['id']:0;
        if(isset($param['client_secret'])&&empty($param['client_secret'])){
            unset($param['client_secret']);
        }
        if(!empty($param['user_id'])){
            $oauth_users_info=model('oauth/OauthUsers')->get_info($param['user_id']);
            $param['user_id']=$oauth_users_info['uid'];
        }
        if(isset($param['client_id'])){
            $param['client_id'] = diy_trim($param['client_id']);
            $map = array();
            $map['client_id'] = $param['client_id'];
            $map['id'] = array('neq' , $param['id']);
            $status = db( 'oauth_clients' )->where( $map )->field( 'id' )->find();
            if ( !empty( $status ) ) {
                $this->error = lang('error_10000013');
                return false;
            }
        }
        $scene=!empty($scene)?$scene:'edit';
        if(in_array($scene,array('add','edit','edit_all'))){
            if(empty($param['scope'])){
                $param['scope']='';
            }
            if(empty($param['grant_types'])){
                $param['grant_types']='';
            }
            if(empty($param['allow_domain'])){
                $param['allow_domain']='';
            }
            if(empty($param['allow_ip'])){
                $param['allow_ip']='';
            }
            if(empty($param['client_user_fields'])){
                $param['client_user_fields']='';
            }
            if(empty($param['third_user_fields'])){
                $param['third_user_fields']='';
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
        if(isset($param['grant_types'])){
            if(!empty($param['grant_types'])){
                if(is_array($param['grant_types'])){
                    $grant_type=$param['grant_types'];
                    $param['grant_types']=array();
                    foreach($this->grant_type_list as $grant_type_temp){
                        if(in_array($grant_type_temp['name'],$grant_type)){
                            $param['grant_types'][]=$grant_type_temp['name'];
                        }
                    }
                    $param['grant_types']=implode(' ',$param['grant_types']);
                }
            }else{
                $param['grant_types']='';
            }
        }
        if(isset($param['allow_domain'])){
            if(!empty($param['allow_domain'])){
                if(is_array($param['allow_domain'])){
                    //todo
                }elseif(!is_string($param['allow_domain'])){
                    $param['allow_domain']='';
                }else{
                    $param['allow_domain']=diy_explode(',',$param['allow_domain']);
                }
                if(is_array($param['allow_domain'])){
                    $param['allow_domain']=array_unique($param['allow_domain']);
                    $param['allow_domain']=array_map(function($allow_domain_item){
                        $allow_domain_item=strtolower($allow_domain_item);
                        return $allow_domain_item;
                    },$param['allow_domain']);
                    sort($param['allow_domain']);
                    $param['allow_domain']=diy_implode(',',$param['allow_domain']);
                }
            }else{
                $param['allow_domain']='';
            }
        }
        if(isset($param['allow_ip'])){
            if(!empty($param['allow_ip'])){
                if(is_array($param['allow_ip'])){
                    //todo
                }elseif(!is_string($param['allow_ip'])){
                    $param['allow_ip']='';
                }else{
                    $param['allow_ip']=diy_explode(',',$param['allow_ip']);
                }
                if(is_array($param['allow_ip'])){
                    $param['allow_ip']=array_unique($param['allow_ip']);
                    sort($param['allow_ip']);

                    $param['allow_ip_number'] = array();
                    foreach($param['allow_ip'] as $allow_ip_item){
                        if ( is_ip( $allow_ip_item , 'ipv4' , true ) ) {
                            $param['allow_ip_number'][] = sprintf('%u',ip2long($allow_ip_item));
                        } else if ( is_ip( $allow_ip_item , 'ipv6' , true ) ) {
                            $param['allow_ip_number'][] = sprintf('%u',ip2long_v6($allow_ip_item));
                        } else if ( is_ip( $allow_ip_item , 'ip' , false ) ) {
                            $param['allow_ip_number'][] = sprintf('%u',$allow_ip_item);
                        }
                    }
                    $param['allow_ip']=diy_implode(',',$param['allow_ip']);
                    $param['allow_ip_number']=diy_implode(',',$param['allow_ip_number']);
                }
            }else{
                $param['allow_ip']='';
                $param['allow_ip_number']='';
            }
        }
        if(isset($param['client_user_fields'])){
            if(!empty($param['client_user_fields'])){
                if(is_array($param['client_user_fields'])){
                    //todo
                }elseif(!$this->is_json($param['client_user_fields'])){
                    $param['client_user_fields']='';
                }else{
                    $param['client_user_fields']=json_decode($param['client_user_fields'],true);
                }
                if(is_array($param['client_user_fields'])){
                    $client_user_fields_temp=array();
                    foreach($param['client_user_fields'] as $item){
                        if(!empty($item)){
                            $client_user_field_temp=array();
                            foreach($item as $v){
                                if(!empty($v)&&!in_array($v,$client_user_field_temp)){
                                    $client_user_field_temp[]=$v;
                                }
                            }
                            if(!empty($client_user_field_temp)){
                                $client_user_field_temp=array_unique($client_user_field_temp,SORT_REGULAR);
                                if(!in_array($client_user_field_temp,$client_user_fields_temp)){
                                    $client_user_fields_temp[]=$client_user_field_temp;
                                }
                            }
                        }
                    }
                    if(!empty($client_user_fields_temp)){
                        $param['client_user_fields']=array_unique($client_user_fields_temp,SORT_REGULAR);
                        $param['client_user_fields']=json_encode($param['client_user_fields']);
                    }else{
                        $param['client_user_fields']='';
                    }
                }
            }else{
                $param['client_user_fields']='';
            }
        }
        if(isset($param['third_user_fields'])){
            if(!empty($param['third_user_fields'])){
                if(is_array($param['third_user_fields'])){
                    //todo
                }elseif(!$this->is_json($param['third_user_fields'])){
                    $param['third_user_fields']='';
                }else{
                    $param['third_user_fields']=json_decode($param['third_user_fields'],true);
                }
                if(is_array($param['third_user_fields'])){
                    $third_user_fields_temp=array();
                    foreach($param['third_user_fields'] as $item){
                        if(!empty($item)){
                            $third_user_field_temp=array();
                            foreach($item as $v){
                                if(!empty($v)&&!in_array($v,$third_user_field_temp)){
                                    $third_user_field_temp[]=$v;
                                }
                            }
                            if(!empty($third_user_field_temp)){
                                $third_user_field_temp=array_unique($third_user_field_temp,SORT_REGULAR);
                                if(!in_array($third_user_field_temp,$third_user_fields_temp)){
                                    $third_user_fields_temp[]=$third_user_field_temp;
                                }
                            }
                        }
                    }
                    if(!empty($third_user_fields_temp)){
                        $param['third_user_fields']=array_unique($third_user_fields_temp,SORT_REGULAR);
                        $param['third_user_fields']=json_encode($param['third_user_fields']);
                    }else{
                        $param['third_user_fields']='';
                    }
                }
            }else{
                $param['third_user_fields']='';
            }
        }
        return true;
    }
    public function check_scope($scope=''){
        $scope = !empty($scope)?$scope:'';
        if(!is_string($scope)){
            $this->error = lang('error_10000015');
            return false;
        }
        $scope=preg_replace('/[ ]{1,}/',' ',$scope);
        return $scope;
    }
    /*--------------  检查字段  end   --------------*/
    /**
     * 获取新的 app_secret
     * @param false $is_new
     * @return array|string
     */
    public function get_new_app_secret($is_new=false){
        $is_new = $is_new === true ;
        $result=array();
        if($is_new){
            $result['app_id'] = $this->get_new_app_id();
        }
        $option=array(
            'letter'=>true,
            'letter_big'=>true,
            'number'=>true,
            'underline'=>true,
            'others'=>false,
        );
        $result['app_secret'] = 'wse'.get_random_string(29,$option);
        $info = db('oauth_clients')->where("client_secret",$result['app_secret'])->field('id')->find();
        if(!empty($info)){
            $result['app_secret'] = $this->get_new_app_secret(false);
        }
        if(!$is_new){
            $result=$result['app_secret'];
        }
        return $result;
    }
    /**
     * 获取新的 app_id
     * @return string
     */
    public function get_new_app_id(){
        $option=array(
            'letter'=>true,
            'number'=>true,
            'others'=>false,
        );
        $app_id = 'wse'.get_random_string(15,$option);
        $oauth_clients_info = db('oauth_clients')->where("client_id",$app_id)->field('id')->find();
        if(!empty($oauth_clients_info)){
            return $this->get_new_app_id();
        }
        return $app_id;
    }
    /**
     * 获取新的 session_key
     * @param string $new_session_key
     * @return string
     */
    public function get_new_session_key($new_session_key=''){
        $new_session_key_list=array();
        if(is_string($new_session_key)&&!empty($new_session_key)){
            $new_session_key_list[]=$new_session_key;
        }elseif(is_array($new_session_key)){
            $new_session_key_list=array_merge($new_session_key_list,$new_session_key);
        }
        $option=array(
            'letter'=>true,
            'number'=>true,
            'underline'=>true,
            'others'=>false,
        );
        $session_key = get_random_string(32,$option);
        $map=array();
        $map['session_key']=$session_key;
        $oauth_clients_info = db('oauth_clients')->where($map)->field('id')->find();
        if(!empty($oauth_clients_info)||in_array($session_key,$new_session_key_list)){
            return $this->get_new_session_key();
        }
        return $session_key;
    }
    public function get_client_keys($oauth_appid='',$field=''){
        $oauth_client_info=$this->get_info($oauth_appid);
        if(empty($oauth_client_info)){
            $this->error = lang('error_10000014');
            return false;
        }
        $result=cache('wse_get_oauth_client_keys_'.$oauth_client_info['client_id']);
        if(empty($result)){
            $map=array();
            $map['client_id']=$oauth_client_info['client_id'];
            $result=db('oauth_public_keys')->where($map)->find();
            if(!empty($result)){
                cache('wse_get_oauth_client_keys_'.$result['client_id'],$result,30*24*60*60);
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

    /**
     * 设置应用密钥
     * @param string $oauth_appid
     * @return array|false
     */
    public function set_client_keys($oauth_appid=''){
        $oauth_client_info=$this->get_info($oauth_appid);
        if(empty($oauth_client_info)){
            $this->error = lang('error_10000014');
            return false;
        }
        $Rsa=new \app\common\util\Rsa();
        $keys=$Rsa->create_key();
        $keys['public_key']=$Rsa->get_public_key_without_fixes($keys['public_key']);
        $keys['private_key']=$Rsa->get_private_key_without_fixes($keys['private_key']);
        if(!is_array($keys)||empty($keys['public_key'])||empty($keys['private_key'])){
            $this->error = lang('error_10000016');
            return false;
        }
        $map=array();
        $map['client_id']=$oauth_client_info['client_id'];
        $oauth_client_keys_info=db('oauth_public_keys')->where($map)->find();
        $data=array();
        $data['client_id']=$oauth_client_info['client_id'];
        $data['public_key']=$keys['public_key'];
        $data['private_key']=$keys['private_key'];
        $data['encryption_algorithm']='RS256';
        if(empty($oauth_client_keys_info)){
            $status=db('oauth_public_keys')->isUpdate(false)->save($data);
            if(!$status){
                $this->error = lang('error_10000017');
                return false;
            }
        }else{
            $status=db('oauth_public_keys')->where($map)->isUpdate(true)->save($data);
            if($status===false){
                $this->error = lang('error_10000018');
                return false;
            }
        }
        cache('wse_get_oauth_client_keys_'.$oauth_client_info['client_id'],null);
        return $data;
    }
    /**
     * 重置oauth应用的session_key
     * @param string $oauth_appid
     * @return false
     */
    public function reset_session_key($oauth_appid=''){
        $oauth_client_info=$this->get_info($oauth_appid);
        if(empty($oauth_client_info)){
            $this->error = lang('error_10000014');
            return false;
        }
        $data=array();
        $data['id']=$oauth_client_info['id'];
        $data['session_key']=$this->get_new_session_key();
        $status=$this->edit($data,'reset_session_key');
        if($status===false){
            $this->error = $this->getError();
            $this->error = $this->error?$this->error:lang('error_10000019');
            return false;
        }
        return true;
    }
    /**
     * 重置oauth应用的app_secret
     * @param string $oauth_appid
     * @param string $scene
     * @return false
     */
    public function reset_app_secret($oauth_appid='',$scene='reset_appsecret'){
        $oauth_client_info=$this->get_info($oauth_appid);
        if(empty($oauth_client_info)){
            $this->error = lang('error_10000014');
            return false;
        }
        if(!in_array($scene,array('reset_appid','reset_appsecret'))){
            $this->error = lang('status_0');
            return false;
        }
        $new_app_secret=$this->get_new_app_secret($scene=='reset_appid');
        $data=array();
        $data['id']=$oauth_client_info['id'];
        if($scene=='reset_appid'){
            $data['client_id']=$new_app_secret['app_id'];
            $data['client_secret']=$new_app_secret['app_secret'];
        }elseif($scene=='reset_appsecret'){
            $data['client_secret']=$new_app_secret;
        }
        $status=$this->edit($data,$scene);
        if($status===false){
            $this->error = $this->getError();
            $this->error = $this->error?$this->error:lang('status_0');
            return false;
        }
        return true;
    }
    /**
     * 获取oauth的应用信息（根据id或appid）
     * @param string $oauth_appid
     * @param string $field
     * @return array|string
     */
    public function get_info($oauth_appid='',$field=''){
        $oauth_appid=trim((string)$oauth_appid);
        $result=cache('wse_get_oauth_clients_info_by_appid_'.$oauth_appid);
        if(empty($result)){
            $result=cache('wse_get_oauth_clients_info_by_id_'.$oauth_appid);
        }
        if(empty($result)){
            $result=db('oauth_clients')->where('id|client_id','eq',$oauth_appid)->find();
            if(!empty($result)){
                $result['is_default']=$result['is_default']>0?model('oauth/OauthUsers')->get_info($result['user_id'],'is_default'):2;
                $result['grant_types']=diy_explode(' ',$result['grant_types']);
                $result['scope']=diy_explode(' ',$result['scope']);
                $result['allow_domain']=diy_explode(',',$result['allow_domain']);
                $result['allow_ip']=diy_explode(',',$result['allow_ip']);
                $result['allow_ip_number']=diy_explode(',',$result['allow_ip_number']);
                if(is_json($result['client_user_fields'])){
                    $result['client_user_fields']=json_decode($result['client_user_fields'],true);
                }else{
                    $result['client_user_fields']=array();
                }
                if(is_json($result['third_user_fields'])){
                    $result['third_user_fields']=json_decode($result['third_user_fields'],true);
                }else{
                    $result['third_user_fields']=array();
                }
                cache('wse_get_oauth_clients_info_by_id_'.$result['id'],$result,30*24*60*60);
                cache('wse_get_oauth_clients_info_by_appid_'.$result['client_id'],$result,30*24*60*60);
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
    /**
     * 获取oauth的应用信息（根据session_key）
     * @param string $session_key
     * @param string $field
     * @return array|mixed
     */
    public function get_session_key_info($session_key='',$field=''){
        $session_key = is_string($session_key)&&!empty($session_key)?trim($session_key):'';
        $result=cache('wse_get_oauth_client_session_key_info_'.$session_key);
        if(empty($result)){
            $map=array();
            $map['session_key']=$session_key;
            $result=db('oauth_clients')->where($map)->find();
            if(!empty($info)){
                $result['is_default']=$result['is_default']>0?model('oauth/OauthUsers')->get_info($result['user_id'],'is_default'):2;
                $result['grant_types']=diy_explode(' ',$result['grant_types']);
                $result['scope']=diy_explode(' ',$result['scope']);
                $result['allow_domain']=diy_explode(',',$result['allow_domain']);
                $result['allow_ip']=diy_explode(',',$result['allow_ip']);
                $result['allow_ip_number']=diy_explode(',',$result['allow_ip_number']);
                if(is_json($result['client_user_fields'])){
                    $result['client_user_fields']=json_decode($result['client_user_fields'],true);
                }else{
                    $result['client_user_fields']=array();
                }
                if(is_json($result['third_user_fields'])){
                    $result['third_user_fields']=json_decode($result['third_user_fields'],true);
                }else{
                    $result['third_user_fields']=array();
                }
                cache('wse_get_oauth_client_session_key_info_'.$session_key,$result,30*24*60*60);
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
    /**
     * 获取access_token信息
     * @param string $access_token
     * @param string $field
     * @param bool $is_oauth_client
     * @return array|string
     */
    public function get_access_token_info($access_token='',$field='',$is_oauth_client=true){
        $is_oauth_client = $is_oauth_client!==false;
        $map=array();
        $map['access_token']=$access_token;
        $result=db('oauth_access_tokens')->where($map)->find();
        if(!empty($result)){
            if($is_oauth_client){
                $result=$this->get_info($result['client_id']);
            }
        }else{
            $result=array();
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
    /**
     * 获取refresh_token信息
     * @param string $refresh_token
     * @param string $field
     * @param bool $is_oauth_client
     * @return array|string
     */
    public function get_refresh_token_info($refresh_token='',$field='',$is_oauth_client=true){
        $is_oauth_client = $is_oauth_client!==false;
        $map=array();
        $map['refresh_token']=$refresh_token;
        $result=db('oauth_refresh_tokens')->where($map)->find();
        if(!empty($result)){
            if($is_oauth_client){
                $result=$this->get_info($result['client_id']);
            }
        }else{
            $result=array();
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
    public function get_system_client($is_user=2,$field=''){
        $is_user = in_array($is_user,array(1,2))?$is_user:2;
        $result=cache('wse_get_system_oauth_clients_info_'.$is_user);
        if(empty($result)){
            $system_oauth_user=model('oauth/OauthUsers')->get_system_oauth_user();
            $map=array();
            $map['user_id']=isset($system_oauth_user['uid'])?$system_oauth_user['uid']:0;
            $map['is_user']=$is_user;
            $result=db('oauth_clients')->where($map)->order('user_id desc,id desc')->find();
            if(!empty($result)){
                $result['grant_types']=diy_explode(' ',$result['grant_types']);
                $result['scope']=diy_explode(' ',$result['scope']);
                $result['allow_domain']=diy_explode(',',$result['allow_domain']);
                $result['allow_ip']=diy_explode(',',$result['allow_ip']);
                $result['allow_ip_number']=diy_explode(',',$result['allow_ip_number']);
                if(is_json($result['client_user_fields'])){
                    $result['client_user_fields']=json_decode($result['client_user_fields'],true);
                }else{
                    $result['client_user_fields']=array();
                }
                if(is_json($result['third_user_fields'])){
                    $result['third_user_fields']=json_decode($result['third_user_fields'],true);
                }else{
                    $result['third_user_fields']=array();
                }
                cache('wse_get_system_oauth_clients_info_'.$is_user,$result,30*24*60*60);
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
    public function edit($param=array(),$scene='edit',$transaction=true){
        $param['id']=isset($param['id'])?intval($param['id']):0;
        $param['id']=$param['id']>0?$param['id']:0;
        $scene=!empty($scene)?$scene:'edit';
        if(!$this->check_field($param,$scene)){
            return false;
        }
        $OauthUsers=model('oauth/OauthUsers');
        $oauth_user_info=$OauthUsers->get_info($param['user_id']);
        if(isset($param['is_user'])&&$param['is_user']==2){
            if($oauth_user_info['is_default']==1){
                $param['client_user_fields']=!empty($param['client_user_fields'])?$param['client_user_fields']:'';
                $param['third_user_fields']=$param['client_user_fields'];
            }else{
                unset($param['client_user_fields']);
                $param['third_user_fields']=!empty($param['third_user_fields'])?$param['third_user_fields']:'';
            }
        }else{
            $param['client_user_fields']='';
            $param['third_user_fields']='';
        }
        $transaction = $transaction!==false;
        if($transaction){
            \think\Db::startTrans();
        }
        if(isset($param['id'])&&$param['id']>0){
            $status=$this->validate('oauth/OauthClients.'.$scene)->isUpdate(true)->save($param,['id'=>$param['id']]);
            if($status===false){
                if($transaction){
                    \think\Db::rollback();
                }
                return false;
            }
            $id=$param['id'];
        }else{
            unset($param['id']);
            $status=$this->validate('oauth/OauthClients.add')->isUpdate(false)->save($param);
            if(!$status){
                if($transaction){
                    \think\Db::rollback();
                }
                return false;
            }
            $id=$this->id;
        }
        if(isset($param['is_user'])&&$param['is_user']==2&&isset($oauth_user_info['is_default'])&&$oauth_user_info['is_default']==1){
            $map=array();
            $map['is_user']=2;
            $map['is_default']=2;
            $app_id_list=db('oauth_clients')->where($map)->column('client_id');
            $app_id_list=!empty($app_id_list)&&is_array($app_id_list)?$app_id_list:array('-1');
            $data=array();
            $data['client_user_fields']=$param['client_user_fields'];
            $status=db('oauth_clients')->where($map)->update($data);
            if($status===false){
                if($transaction){
                    \think\Db::rollback();
                }
                $this->error='更新其他client相关用户信息失败';
                return false;
            }
            $this->clear_cache($app_id_list);
        }
        if($transaction){
            \think\Db::commit();
        }
        return $id;
    }
}