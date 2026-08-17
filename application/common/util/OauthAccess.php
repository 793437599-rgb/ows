<?php

namespace app\common\util;

use app\common\util\Rsa;

/**
 * 向第三方授权机构发起授权申请
 * @author xiaomugua
 */
class OauthAccess {
    protected static $instance_count       = 0;
    protected static $type_list            = array( 'lxw' , 'lxw_user' , 'wse' , 'wse_user' );
    protected        $type                 = '';
    protected        $ApiUrl               = '';
    protected        $domain               = '';
    protected        $search_domain        = '';
    protected        $url_list             = array(
        'authorize'      => '/oauth/Index/authorize' ,
        'access_token'   => '/oauth/Index/access_token' ,
        'refresh_token'  => '/oauth/Index/refresh_token' ,
        'check_token'    => '/oauth/Index/auth' ,
        'authorize_show' => '/oauth/Index/show' ,
    );
    protected        $open_url             = array(
        'check_invite_code' => '/open/index/check_edu_code' ,
        'secure_site'       => '/open/index/secure_site' ,
    );
    protected        $client_id            = '';
    protected        $client_secret        = '';
    protected        $session_key          = '';
    protected static $default_session_key  = 'wse_lxw_connection_str';
    protected        $redirect_uri         = '';
    protected        $extra                = [];
    protected        $extra_not_allowed    = array( 'response_type' , 'client_id' , 'client_secret' , 'grant_type' , 'code' , 'access_token' , 'refresh_token' , 'state' , 'sign' );
    protected        $error                = '';
    protected        $access_token         = '';
    protected        $access_token_endtime = 0;
    protected        $state                = '';
    protected        $need_authorize       = false;

    public function __construct ( $type = '' ) {
        self ::$instance_count++;
        if ( in_array( $type , self ::$type_list ) ) {
            $this -> type = $type;
        }
        if ( in_array( $this -> type , array( 'lxw' , 'lxw_user' ) ) ) {
            $config = config( 'third_part.' . $this -> type );
        } else if ( in_array( $this -> type , array( 'wse' , 'wse_user' ) ) ) {
            if ( $this -> type == 'wse' ) {
                $config = model( 'oauth/OauthClients' ) -> get_system_client( 1 );
            } else if ( $this -> type == 'wse_user' ) {
                $config = model( 'oauth/OauthClients' ) -> get_system_client( 2 );
            }
            if ( !empty( $config ) ) {
                $config['url'] = $config['authorize_is_ssl'] == 1 ? 'https://' : 'http://';
                $config['url'] .= $config['authorize_domain'];
                $config['url'] = rtrim( $config['url'] , '/' );
                $config['search_url'] = $config['authorize_is_ssl'] == 1 ? 'https://' : 'http://';
                $config['search_url'] .= 've.wse.com';
            }
        }
        if ( !empty( $config ) ) {
            $this -> domain = $config['url'];
            $this -> search_domain = $config['search_url'];
            $this -> client_id = $config['client_id'];
            $this -> client_secret = $config['client_secret'];
            $this -> redirect_uri = $config['redirect_uri'];
            $this -> need_authorize = $config['need_authorize'] == 1;
            if ( $this -> need_authorize ) {
                $this -> session_key = $config['session_key'];
            } else {
                $this -> session_key = self ::$default_session_key;
            }
        }
    }

    public function get ( $method = '' ) {
        $allow_method = array( 'type' , 'ApiUrl' , 'domain' , 'client_id' , 'session_key' , 'redirect_uri' , 'need_authorize' , 'error' , 'access_token' , 'access_token_endtime' , 'state' );
        $allow_static_method = array( 'default_session_key' , 'type_list' );
        $result = null;
        if ( in_array( $method , $allow_static_method ) ) {
            $result = self ::$$method;
        } else {
            if ( self ::$instance_count > 0 ) {
                if ( in_array( $method , $allow_method ) ) {
                    $result = $this -> $method;
                }
            }
        }

        return $result;
    }

    public function getApiUrl () {
        return $this -> ApiUrl;
    }

    protected function get_url ( $method = 'authorize' , $type = '' ) {
        $type = in_array( $type , array( 'oauth' , 'open' ) ) ? $type : 'open';
        $result = '';
        if ( $type == 'oauth' ) {
            if ( !isset( $this -> url_list[$method] ) ) {
                $this -> error = '不支持的操作';

                return false;
            }
            $result = $this -> url_list[$method];
        } else if ( $type == 'open' ) {
            if ( !isset( $this -> open_url[$method] ) ) {
                $this -> error = '不支持的操作';

                return false;
            }
            $result = $this -> open_url[$method];
        }
        
        if(is_array($result)){
            if(isset($result[1])&&$result[1]=='search_url'){
                $result = $this -> search_domain . $result[0];
            }else{
                if(strpos($result[0],$this->domain)!==0&&strpos($result[0],$this->search_domain)!==0){
                    $result = $this -> domain . $result[0];
                }else{
                    $result = $result[0];
                }
            }
        }elseif(is_string($result)){
            if(strpos($result,$this->domain)!==0&&strpos($result,$this->search_domain)!==0){
                $result = $this -> domain . $result;
            }
        }

        return $result;
    }

    public function getError () {
        return $this -> error;
    }

    protected function check_config ( $field_list = array() ) {
        if ( is_string( $field_list ) ) {
            if ( strpos( $field_list , ',' ) === false ) {
                $field_list = array( $field_list );
            } else {
                $field_list = explode( ',' , $field_list );
            }
        }
        $field_list = !empty( $field_list ) && is_array( $field_list ) ? $field_list : array( 'domain' , 'client_id' , 'client_secret' , 'session_key' , 'redirect_uri' );
        foreach ( $field_list as $field ) {
            if ( empty( $this -> $field ) || !is_string( $this -> $field ) ) {
                $this -> error = '缺少必要配置';

                return false;
            }
        }

        return true;
    }

    protected function get_config ( $app_id = '' ) {}

    public function set_session_key ( $session_key = '' ) {
        if ( is_string( $session_key ) ) {
            $this -> session_key = $session_key;
        }
    }

    public function set_extra ( $extra = [] ) {
        if ( !empty( $extra ) && is_array( $extra ) ) {
            foreach ( $extra as $key => $item ) {
                if ( !in_array( $key , $this -> extra_not_allowed ) ) {
                    $this -> extra[$key] = $item;
                }
            }
        }

        return $this;
    }

    public function set_redirect_uri ( $redirect_uri = '' ) {
        if ( !empty( $redirect_uri ) && is_string( $redirect_uri ) ) {
            $this -> redirect_uri = $redirect_uri;
        }

        return $this;
    }

    public function set_domain ( $domain = '' ) {
        if ( !empty( $domain ) && is_string( $domain ) ) {
            $this -> domain = $domain;
        }

        return $this;
    }

    public function set_state ( $state = '' , $is_encrypt = true ) {
        if ( empty( $state ) ) {
            $this -> state = '';
        } else {
            $is_encrypt = $is_encrypt !== false;
            $this -> state = $state;
            if ( $is_encrypt ) {
                $Rsa = new Rsa();
                if ( !is_string( $this -> state ) ) {
                    $encrypt_string = $this -> state;
                } else {
                    $encrypt_string = array( 'state' => $this -> state );
                }
                $encrypt_string = urldecode( http_build_query( $encrypt_string ) );;
                $encrypt_string = $Rsa -> public_encrypt( $encrypt_string );
                $encrypt_string = gzcompress( $encrypt_string , 9 );
                $this -> state = bin2hex( $encrypt_string );
            }
        }

        return $this;
    }

    public function get_state ( $type = '' ) {
        $type_list = array( 'get' , 'auto' );
        $type = in_array( $type , $type_list ) ? $type : 'auto';
        if ( $type == 'auto' && empty( $this -> state ) ) {
            $this -> state = $this -> get_random_string( 6 );
        }

        return $this -> state;
    }

    public function analyze_state ( $state = '' ) {
        if ( is_string( $state ) ) {
            if ( strlen( $state ) > 6 ) {
                $state = hex2bin( $state );
                $state = gzuncompress( $state );
                $Rsa = new Rsa();
                $result_temp = $Rsa -> private_decrypt( $state );
                parse_str( $result_temp , $result );
                if ( count( $result ) == 1 && isset( $result['state'] ) ) {
                    $result = $result['state'];
                }
            } else {
                $result = $state;
            }
        } else {
            $result = $state;
        }

        return $result;
    }

    public function get_authorize_arguments () {
        $config_status = $this -> check_config();
        if ( $config_status === false ) {
            return false;
        }
        $data = array();
        $data['response_type'] = 'code';
        $data['scope'] = 'basic openid';
        $data['client_id'] = $this -> client_id;
        $data['redirect_uri'] = $this -> redirect_uri;
        $data['state'] = $this -> get_state();
        if ( !empty( $this -> extra ) ) {
            foreach ( $this -> extra as $key => $item ) {
                if ( !in_array( $key , $this -> extra_not_allowed ) && !empty( $item ) ) {
                    $data[$key] = $item;
                }
            }
        }
        $data['sign'] = $this -> getSign( $data ,$error_info );
        $url = $this -> get_url( 'authorize_show' , 'oauth' );
        $result = array();
        $result['url'] = $url;
        $result['data'] = $data;
        $result['error'] = array(
            'state_old' => $this -> state ,
            'state'     => $data['state'] ,
            'sign_info'     => $error_info ,
        );
        $this -> set_state( '' );

        return $result;
    }

    public function get_secure_site_arguments () {
        $config_status = $this -> check_config();
        if ( $config_status === false ) {
            return false;
        }
        $data = array();
        if ( $this -> need_authorize ) {
            $access_token = $this -> get_authorize();
            if ( $access_token === false || empty( $access_token ) ) {
                $error_msg = $this -> getError();
                $this -> error = $error_msg ? $error_msg : '获取授权失败';

                return false;
            }
            $data['access_token'] = $access_token;
        }
        $data['t'] = time();
        $data['sign'] = $this -> getSign( $data , $error );
        $url = $this -> get_url( 'secure_site' , 'open' );
        $result = array();
        $result['url'] = $url;
        $result['data'] = $data;

        return $result;
    }
    
    public function get_open_arguments ($type='secure_site',$param=array()) {
        $config_status = $this -> check_config();
        if ( $config_status === false ) {
            return false;
        }
        $type = isset($this->open_url[$type])?$type:'secure_site';
        $data = array();
        if ( $this -> need_authorize ) {
            $access_token = $this -> get_authorize();
            if ( $access_token === false || empty( $access_token ) ) {
                $error_msg = $this -> getError();
                $this -> error = $error_msg ? $error_msg : '获取授权失败';
                
                return false;
            }
            $data['access_token'] = $access_token;
        }
        $param = !empty($param)&&is_array($param)?$param:array();
        if(!empty($param)){
            $data = array_merge($data,$param);
        }
        $data['t'] = time();
        $data['sign'] = $this -> getSign( $data , $error );
        $url = $this -> get_url( $type , 'open' );
        $result = array();
        $result['url'] = $url;
        $result['data'] = $data;
        
        return $result;
    }

    public function get_authorize ( $user_id = 0 , $is_new = false ) {
        $is_new = $is_new === true;
        if ( $is_new || empty( $this -> access_token ) || $this -> access_token_endtime <= time() ) {
            $map = array();
            $map['client_id'] = $this -> client_id;
            if ( strpos( $this -> type , '_user' ) !== false ) {
                if ( !is_numeric( $user_id ) || $user_id <= 0 || intval( $user_id ) != $user_id ) {
                    $this -> error = '参数错误：用户未登录';

                    return false;
                }
                $map['user_id'] = $user_id;
            } else {
                $map['user_id'] = 0;
            }
            $open_info = db( 'token' ) -> where( $map ) -> find();
            if ( !$is_new && !empty( $open_info ) && $open_info['access_token_endtime'] > time() ) {
                $this -> access_token = $open_info['access_token'];
                $this -> access_token_endtime = $open_info['access_token_endtime'];
            } else if ( !$is_new && !empty( $open_info ) && $open_info['access_token_endtime'] <= time() ) {
                $allow_refresh = false;
                if ( !empty( $open_info['refresh_token'] ) && ( $open_info['refresh_token_endtime'] > time() ) ) {
                    $allow_refresh = true;
                }else if( !empty( $open_info['refresh_token'] ) && ( $open_info['refresh_token_endtime'] == 0 ) ) {
                    /*$allow_refresh = true;*/
                }
                if ( $allow_refresh ) {
                    $refresh_token_info = $this -> refresh_token( $open_info['refresh_token'] , $user_id );
                    if ( $refresh_token_info === false ) {
                        $error_msg = $this -> getError();
                        if ( is_array( $error_msg ) && !empty( $error_msg['error'] ) || empty( $error_msg['code'] ) ) {
                            $this -> error = '授权获取access_token失败：' . $error_msg['error_description'];
                        }

                        return false;
                    } else if ( is_array( $refresh_token_info ) && ( empty( $refresh_token_info['access_token'] ) || !empty( $refresh_token_info['error'] ) ) ) {
                        $this -> error = '授权获取access_token失败：' . $refresh_token_info['error_description'];

                        return false;
                    }
                    $this -> access_token = $refresh_token_info['access_token'];
                    $this -> access_token_endtime = time() + $refresh_token_info['expires_in'] - 10;
                }
            }
            if ( $is_new || empty( $this -> access_token ) ) {
                $authorize_result = $this -> authorize();
                if ( $authorize_result === false ) {
                    $error_msg = $this -> getError();
                    if ( is_array( $error_msg ) && !empty( $error_msg['error'] ) || empty( $error_msg['code'] ) ) {
                        $this -> error = '授权获取code失败：' . $error_msg['error_description'];
                    }

                    return false;
                } else if ( is_array( $authorize_result ) && ( empty( $authorize_result['code'] ) || !empty( $authorize_result['error'] ) ) ) {
                    $this -> error = '授权获取code失败：' . $authorize_result['error_description'];

                    return false;
                }
                $access_token_result = $this -> access_token( $authorize_result['code'] , $user_id );
                if ( $access_token_result === false ) {
                    $error_msg = $this -> getError();
                    if ( empty( $error_msg['access_token'] ) || !empty( $error_msg['error'] ) ) {
                        $this -> error = '授权获取access_token失败：' . $error_msg['error_description'];
                    }

                    return false;
                } else if ( is_array( $access_token_result ) && ( empty( $access_token_result['access_token'] ) || !empty( $access_token_result['error'] ) ) ) {
                    $this -> error = '授权获取access_token失败：' . $access_token_result['error_description'];

                    return false;
                }
                $this -> access_token = $access_token_result['access_token'];
                $this -> access_token_endtime = time() + $access_token_result['expires_in'] - 10;
            }
        }

        return $this -> access_token;
    }

    public function update_access_token ( $client_id = '' , $result = array() , $user_id = 0 ) {
        if ( empty( $result ) || !is_array( $result ) || !isset( $result['access_token'] ) ) {
            $this -> error = 'empty data';

            return false;
        }
        $openid = isset( $result['openid'] ) && !empty( $result['openid'] ) ? $result['openid'] : '';
        $unionid = isset( $result['unionid'] ) && !empty( $result['unionid'] ) ? $result['unionid'] : '';
        $map = array();
        $map['client_id'] = $client_id;
        $map['openid'] = $openid;
        /*if(is_numeric($user_id)&&$user_id>0&&intval($user_id)!=$user_id){
            $map['user_id'] = $user_id;
        }else{
            $map['user_id'] = 0;
        }*/
        if ( strpos( $this -> type , '_user' ) !== false ) {
            if ( !is_numeric( $user_id ) || $user_id <= 0 || intval( $user_id ) != $user_id ) {
                $this -> error = '参数错误：用户未登录';

                return false;
            }
            $map['user_id'] = $user_id;
        } else {
            $map['user_id'] = 0;
        }
        $open_info = db( 'token' ) -> where( $map ) -> field( 'id' ) -> find();
        $data = array();
        $data['client_id'] = $client_id;
        $data['openid'] = $openid;
        if ( !empty( $unionid ) ) {
            $data['unionid'] = $unionid;
        }
        if ( is_numeric( $user_id ) && $user_id > 0 ) {
            $data['user_id'] = $user_id;
        }
        if ( !empty( $open_info ) ) {
            $data['id'] = $open_info['id'];
        }
        $data['access_token'] = $result['access_token'];
        $data['access_token_endtime'] = time() + $result['expires_in'] - 10;
        if ( isset( $result['refresh_token'] ) && !empty( $result['refresh_token'] ) ) {
            $data['refresh_token'] = $result['refresh_token'];
            $data['refresh_token_endtime'] = 0;
        }
        $data['os'] = get_os();
        $data['broswer'] = get_broswer();
        $data['last_ip'] = get_ip();
        if ( isset( $data['id'] ) && $data['id'] ) {
            $status = db( 'token' ) -> where( [ 'id' => $data['id'] ] ) -> update( $data );
            if ( $status === false ) {
                $this -> error = '更新第三方授权数据失败';

                return false;
            }
        } else {
            unset( $data['id'] );
            $status = db( 'token' ) -> insert( $data );
            if ( !$status ) {
                $this -> error = '更新第三方授权数据失败';

                return false;
            }
        }

        return true;
    }

    public function notify ( $redirect_uri = '' , $param = array() , $user_id = 0 , $extra_param = array() ) {
        $param = !empty( $param ) && is_array( $param ) ? $param : array();
        $extra_param = !empty( $extra_param ) && is_array( $extra_param ) ? $extra_param : array();
        $data = array();
        $data['result'] = $param;
        $data['state'] = $param['state'] ? $param['state'] : '';
        if ( $this -> need_authorize ) {
            $access_token = $this -> get_authorize( $user_id );
            if ( $access_token === false || empty( $access_token ) ) {
                $error_msg = $this -> getError();
                $this -> error = $error_msg ? $error_msg : '获取授权失败';

                return false;
            }
            $data['access_token'] = $access_token;
        } else {
            if ( is_numeric( $user_id ) && $user_id > 0 ) {
                $user_info = db( 'user' ) -> where( 'id' , 'eq' , $user_id ) -> find();
                $data['user'] = array();
                $data['user']['email'] = $user_info['email'];
            }
        }
        if ( !empty( $extra_param ) ) {
            $data['extra'] = $extra_param;
        }
        $data['sign'] = $this -> getSign( $data , $error_info );
        if ( strpos( strtolower( $redirect_uri ) , '/oauth/' ) === false ) {
            $data = $this -> diy_urlencode( $data );
        }
        $result = $this -> send_query( $redirect_uri , $data , 'post' );

        return $result;
    }

    public function check_invite_code ( $invite_code = '' , $option = array() ) {
        $this -> error = '暂不支持该操作';

        return false;
        $data = array();
        $data['code'] = $invite_code;
        $data['document_group_id'] = $option['document_group_id'];
        $data['document_type_id'] = $option['document_type_id'];
        $data['number'] = $option['numbers'];
        $data['xwcc'] = $option['xwcc'];
        if ( $this -> need_authorize ) {
            $access_token = $this -> get_authorize( $option['user_id'] );
            if ( $access_token === false || empty( $access_token ) ) {
                $error_msg = $this -> getError();
                $this -> error = $error_msg ? $error_msg : '获取授权失败';

                return false;
            }
            $data['access_token'] = $access_token;
        }
        $data['sign'] = $this -> getSign( $data , $error );
        $this -> ApiUrl = $this -> get_url( 'check_invite_code' , 'open' );
        $result = $this -> send_query( $this -> ApiUrl , $data , 'post' );
        if($result === false){
            return false;
        }
        if( is_array( $result ) && !empty($result['error']) ){
            $this -> error = $result['error_description'] ? $result['error_description'] : ( $result['error'] ? $result['error'] : '验证请求失败' );
            return false;
        }
        if ( !is_array( $result ) || $result['code'] != 1 ) {
            $msg = $result['msg'];
            $this -> error = $msg ? $msg : '验证请求失败';

            return false;
        }
        if ( $result['data']['status'] != 1 ) {
            $msg = $result['msg'];
            $this -> error = $msg ? $msg : '验证请求失败';

            return false;
        }

        return $result;
    }

    /**
     * 获取授权code（向第三方授权平台发起授权申请）
     */
    public function authorize () {
        $config_status = $this -> check_config();
        if ( $config_status === false ) {
            return false;
        }
        $data = array();
        $data['response_type'] = 'code';
        $data['scope'] = 'basic openid';
        $data['client_id'] = $this -> client_id;
        $data['redirect_uri'] = $this -> redirect_uri;
        $data['state'] = $this -> get_state();
        if ( !empty( $this -> extra ) ) {
            foreach ( $this -> extra as $key => $item ) {
                if ( !in_array( $key , $this -> extra_not_allowed ) && !empty( $item ) ) {
                    $data[$key] = $item;
                }
            }
        }
        $data['sign'] = $this -> getSign( $data );
        $this -> ApiUrl = $this -> get_url( 'authorize' , 'oauth' );
        $result = $this -> send_query( $this -> ApiUrl , $data , 'post' );
        $this -> set_state( '' );

        return $result;
    }

    /**
     * 获取access_token（向第三方授权平台发起授权申请）
     * @param string $code
     * @param int    $user_id
     * @return bool|mixed|string
     */
    public function access_token ( $code = '' , $user_id = 0 ) {
        $config_status = $this -> check_config();
        if ( $config_status === false ) {
            return false;
        }
        $data = array();
        $data['client_id'] = $this -> client_id;
        $data['client_secret'] = $this -> client_secret;
        $data['code'] = $code;
        $data['grant_type'] = 'authorization_code';
        $data['redirect_uri'] = $this -> redirect_uri;
        $data['sign'] = $this -> getSign( $data );
        $this -> ApiUrl = $this -> get_url( 'access_token' , 'oauth' );
        $result = $this -> send_query( $this -> ApiUrl , $data , 'POST' );
        if ( !empty( $result['access_token'] ) && empty( $result['error'] ) ) {
            $status = $this -> update_access_token( $this -> client_id , $result , $user_id );
            if ( $status === false ) {
                //todo
            }
        }

        return $result;
    }

    /**
     * 刷新access_token（向第三方授权平台发起授权申请）
     * @param string $refresh_token
     * @param int    $user_id
     * @return bool|mixed|string
     */
    public function refresh_token ( $refresh_token = '' , $user_id = 0 ) {
        $config_status = $this -> check_config();
        if ( $config_status === false ) {
            return false;
        }
        $data = array();
        $data['client_id'] = $this -> client_id;
        $data['client_secret'] = $this -> client_secret;
        $data['refresh_token'] = $refresh_token;
        $data['grant_type'] = 'refresh_token';
        $data['sign'] = $this -> getSign( $data );
        $this -> ApiUrl = $this -> get_url( 'refresh_token' , 'oauth' );
        $result = $this -> send_query( $this -> ApiUrl , $data , 'POST' );
        if ( !empty( $result['access_token'] ) && empty( $result['error'] ) ) {
            $status = $this -> update_access_token( $this -> client_id , $result , $user_id );
            if ( $status === false ) {
                //todo
            }
        }

        return $result;
    }

    /**
     * 检查access_token是否有效
     * @param string $access_token
     * @return array|bool|mixed|string
     */
    public function check_token ( $access_token = '' ) {
        $config_status = $this -> check_config();
        if ( $config_status === false ) {
            return false;
        }
        $data = array();
        $data['client_id'] = $this -> client_id;
        $data['client_secret'] = $this -> client_secret;
        $data['access_token'] = $access_token;
        $data['grant_type'] = 'authorization_code';
        $data['sign'] = $this -> getSign( $data );
        $this -> ApiUrl = $this -> get_url( 'check_token' , 'oauth' );
        $result = $this -> send_query( $this -> ApiUrl , $data , 'POST' );

        return $result;
    }

    public function send_request ( $method = 'post' , $data = array() , $header = array() , $url = '' ) {
        $method = is_string( $method ) ? strtolower( $method ) : '';
        $method = in_array( $method , array( 'get' , 'post' ) ) ? $method : 'post';
        $data = !empty( $data ) ? $data : array();
        //$data = $this->diy_urlencode($data);
        $header = !empty( $header ) && is_array( $header ) ? $header : array();
        $url = !empty( $url ) && is_string( $url ) ? $url : $this -> ApiUrl;
        if ( $method == 'post' ) {
            $result = $this -> http_post( $url , $data , $header );
        } else if ( $method == 'get' ) {
            $result = $this -> http_get( $url , $data , $header );
        }
        if ( $result === false ) {
            return false;
        }
        if ( !empty( $result ) && is_array( $result ) ) {
            $result['api'] = $url;
        }

        return $result;
    }

    protected function send_query ( $url = '' , $data = array() , $method = 'POST' , $header = array() ) {
        $method = strtoupper( $method );
        $method = in_array( $method , [ 'POST' , 'GET' ] ) ? $method : 'POST';
        $header_param = array();
        if ( !empty( $header['Referrer'] ) || !empty( $header['Referer'] ) ) {
            $referrer = !empty( $header['Referrer'] ) ? $header['Referrer'] : ( !empty( $header['Referer'] ) ? $header['Referer'] : '' );
            if ( !empty( $referrer ) ) {
                $header_param[] = 'Referer: ' . $referrer . ';';
                $header_param[] = 'Referrer: ' . $referrer . ';';
            }
            unset( $header['Referrer'] );
            unset( $header['Referer'] );
        }
        $header_param = array_merge( $header_param , $header );
        $res = $this -> http_curl( $url , $data , $method , $header_param , false );
        if ( is_json( $res ) ) {
            $res = json_decode( $res , true );
        }

        return $res;
    }

    protected function diy_urlencode ( $data = '' ) {
        if ( is_string( $data ) ) {
            $data = urlencode( $data );
        } else {
            if ( is_object( $data ) ) {
                $data = json_decode( json_encode( $data ) , true );
            }
            if ( is_array( $data ) ) {
                foreach ( $data as $key => $val ) {
                    $data[$key] = $this -> diy_urlencode( $val );
                }
            }
        }

        return $data;
    }

    /**
     *  作用：验证签名
     * @param array $data
     * @return string
     */
    protected function verifySign ( $data = array() ) {
        $sign = $data['sign'];
        unset( $data['sign'] );
        $sign_string = $this -> getSign( $data );
        if ( $sign != $sign_string ) {
            $this -> error = '签名验证失败';

            return false;
        }

        return true;
    }

    /**
     *  作用：生成签名
     * @param array $data
     * @param array $error_info
     * @return string
     */
    protected function getSign ( $data = array() , &$error_info = array() ) {
        $error_info = array();
        $data = is_array( $data ) ? $data : json_decode( json_encode( $data ) , true );
        foreach ( $data as $key => $value ) {
            if ( !is_numeric( $value ) && empty( $value ) ) {
                unset( $data[$key] );
            }
        }
        //签名步骤一：按字典序排序参数
        ksort( $data );
        $error_info['data'] = $data;
        $string = http_build_query( $data );
        $string = urldecode( $string );
        $error_info['param'] = $string;
        $string = md5( $string );
        $error_info['first_md5'] = $string;
        //echo "【string】 =".$string."</br>";
        //签名步骤二：在string前加入KEY
        $string = $this -> session_key . $string;
        $error_info['last_param'] = $string;
        //echo "【string】 =".$string."</br>";
        //签名步骤三：MD5加密
        $string = md5( $string );
        $error_info['sign'] = $string;

        return $string;
    }

    /**
     * get请求
     * @param string $url
     * @param array  $data
     * @param array  $header
     * @return bool|string
     */
    public function http_get ( $url = '' , $data = array() , $header = array() ) {
        if ( strpos( $url , 'http://' ) === false && strpos( $url , 'https://' ) === false ) {
            $url = 'http://' . $url;
        }
        $param = http_build_query( $data );
        $url = strpos( $url , '?' ) === false ? $url . '?' . $param : $url . '&' . $param;
        $referrer = get_http() . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'];
        $header_default = array( 'Accept: */*' , 'User-Agent: Mozilla/4.0 (compatible; MSIE 6.0; Windows NT 5.1; SV1)' , 'Connection: Keep-Alive' , 'charset=utf-8' , 'Referer:' . $referrer , 'Referrer:' . $referrer );
        $header = array_merge( $header_default , $header );
        //初始化
        $curl = curl_init();
        curl_setopt( $curl , CURLOPT_URL , $url );
        // 执行后不直接打印出来
        curl_setopt( $curl , CURLOPT_RETURNTRANSFER , true );
        curl_setopt( $curl , CURLOPT_HEADER , false );
        curl_setopt( $curl , CURLOPT_HTTPHEADER , $header );
        if ( 1 == strpos( "$" . $url , "https://" ) ) {
            // 跳过证书检查
            curl_setopt( $curl , CURLOPT_SSL_VERIFYPEER , false );
            // 不从证书中检查SSL加密算法是否存在
            curl_setopt( $curl , CURLOPT_SSL_VERIFYHOST , false );
        }
        //执行并获取HTML文档内容
        $output = curl_exec( $curl );
        $this -> error = curl_error( $curl );
        //释放curl句柄
        curl_close( $curl );
        $temp = json_decode( $output , true );
        if ( !empty( $temp ) && is_array( $temp ) ) {
            $output = $temp;
        } else {
            $this -> error = $output;
            $output = false;
        }

        return $output;
    }

    /**
     * post请求
     * @param string $url
     * @param array  $data
     * @param array  $header
     * @return bool|string
     */
    public function http_post ( $url = '' , $data = array() , $header = array() ) {
        if ( strpos( $url , 'http://' ) === false && strpos( $url , 'https://' ) === false ) {
            $url = 'http://' . $url;
        }
        $referrer = get_http() . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'];
        $header_default = array( 'Accept: */*' , 'User-Agent: Mozilla/4.0 (compatible; MSIE 6.0; Windows NT 5.1; SV1)' , 'Connection: Keep-Alive' , 'charset=utf-8' , 'Referer:' . $referrer , 'Referrer:' . $referrer );
        $header = array_merge( $header_default , $header );
        //初始化
        $curl = curl_init();
        curl_setopt( $curl , CURLOPT_URL , $url );
        // 执行后不直接打印出来
        curl_setopt( $curl , CURLOPT_RETURNTRANSFER , true );
        // 设置请求方式为post
        curl_setopt( $curl , CURLOPT_POST , true );
        // post的变量
        curl_setopt( $curl , CURLOPT_POSTFIELDS , http_build_query( $data ) );
        // 请求头，可以传数组
        curl_setopt( $curl , CURLOPT_HEADER , false );
        curl_setopt( $curl , CURLOPT_HTTPHEADER , $header );
        if ( 1 == strpos( "$" . $url , "https://" ) ) {
            // 跳过证书检查
            curl_setopt( $curl , CURLOPT_SSL_VERIFYPEER , false );
            // 不从证书中检查SSL加密算法是否存在
            curl_setopt( $curl , CURLOPT_SSL_VERIFYHOST , false );
        }
        $output = curl_exec( $curl );
        $this -> error = curl_error( $curl );
        curl_close( $curl );
        $temp = json_decode( $output , true );
        if ( !empty( $temp ) && is_array( $temp ) ) {
            $output = $temp;
        } else {
            if ( $output !== false ) {
                $this -> error = $output;
            }
            $output = false;
        }

        return $output;
    }

    protected function http_curl ( $url , $params , $method = 'POST' , $header = array() , $multi = false ) {  //其实要用post
        $header = is_array( $header ) && !empty( $header ) ? $header : array();
        $opts = array(
            CURLOPT_TIMEOUT        => 60 ,
            CURLOPT_RETURNTRANSFER => 1 ,
            CURLOPT_SSL_VERIFYPEER => false ,
            CURLOPT_SSL_VERIFYHOST => false ,
        );
        $referrer = get_http() . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'];
        $header_default = array( 'Accept: */*' , 'User-Agent: Mozilla/4.0 (compatible; MSIE 6.0; Windows NT 5.1; SV1)' , 'Connection: Keep-Alive' , 'charset=utf-8' , 'Referer:' . $referrer , 'Referrer:' . $referrer );
        if ( $multi ) {
            $header_default[] = 'Content-Type: multipart/form-data;';
        } else {
            $params = http_build_query( $params );
        }
        $header = array_merge( $header_default , $header );
        $opts[CURLOPT_HTTPHEADER] = $header;
        /* 根据请求类型设置特定参数 */
        switch (strtoupper( $method )) {
            case 'GET':
                if ( is_array( $params ) ) {
                    $opts[CURLOPT_URL] = $url . '?' . http_build_query( $params );
                } else {
                    $opts[CURLOPT_URL] = $url . '?' . $params;
                }
                break;
            case 'POST':
                //判断是否传输文件
                if ( $multi ) {
                    $opts[CURLOPT_SAFE_UPLOAD] = 1;
                }
                $opts[CURLOPT_URL] = $url;
                $opts[CURLOPT_POST] = 1;
                $opts[CURLOPT_POSTFIELDS] = $params;
                break;
            default:
                $this -> error = '不支持的请求方式！';

                return false;
        }
        /* 初始化并执行curl请求 */
        $ch = curl_init();
        @curl_setopt_array( $ch , $opts );
        $data = curl_exec( $ch );
        $error = curl_error( $ch );
        $error_code = curl_errno( $ch );
        curl_close( $ch );
        if ( $error_code ) {
            $this -> error = '请求发生错误：' . $error . '；错误码：' . $error_code;

            return false;
        }

        return $data;
    }

    /**
     * 生成随机字符串
     * @param int   $len 字符串长度
     * @param array $option_param
     *                   letter      bool    是否含有小写字母
     *                   letter_big  bool    是否含有大写字母
     *                   underline   bool    是否含有下划线
     *                   point       bool    是否含有 .
     *                   minus       bool    是否含有 -
     *                   at          bool    是否含有 @
     * @return string
     */
    public function get_random_string ( $len = 6 , $option_param = array() ) {
        $option = array(
            'letter'     => true ,
            'letter_big' => true ,
            'number'     => true ,
            'underline'  => false ,
            'point'      => false ,
            'minus'      => false ,
            'at'         => false ,
        );
        $string_source = array(
            'letter'     => 'abcdefghijklmnopqrstuvwxyz' ,
            'letter_big' => 'ABCDEFGHIJKLMNOPQRSTUVWXYZ' ,
            'number'     => '0123456789' ,
            'underline'  => '_' ,
            'point'      => '.' ,
            'minus'      => '-' ,
            'at'         => '@' ,
        );
        $option_param = !empty( $option_param ) && is_array( $option_param ) ? $option_param : array();
        $others = '';
        $param_key = array();
        foreach ( $option_param as $key => $val ) {
            if ( isset( $option[$key] ) ) {
                if ( $val === true || $val === false ) {
                    $option[$key] = $val;
                }
                $param_key[] = $key;
            } else if ( $key == 'others' && ( $val === true || $val === false ) ) {
                $others = $val;
            }
        }
        if ( $others !== '' ) {
            foreach ( $option as $key => $val ) {
                if ( !in_array( $key , $param_key ) ) {
                    $option[$key] = $others;
                }
            }
        }
        $len = intval( $len ) > 0 ? intval( $len ) : 12;
        $string = '';
        foreach ( $option as $key => $val ) {
            if ( $val ) {
                if ( isset( $string_source[$key] ) && is_string( $string_source[$key] ) && !empty( $string_source[$key] ) ) {
                    $string .= $string_source[$key];
                }
            }
        }
        $last_index = strlen( $string ) - 1;
        $result = '';
        for ( $i = 0; $i < $len; $i++ ) {
            $index = mt_rand( 0 , $last_index );
            $result .= $string[$index];
        }
        while ( strlen( $result ) < $len ) {
            $left = $len - strlen( $result );
            $result .= get_random_string( $left , $option );
        }

        return $result;
    }
}