<?php

namespace app\common\traits;

trait traitController {
    private   $logs_root     = './../logs/';
    private   $module        = '';
    private   $controller    = '';
    private   $action        = '';
    private   $is_useragent  = false;
    protected $is_example    = 0;/*是否测试:1测试0不是测试（即正式）*/
    protected $error         = '';/*错误提示*/
    protected $extra_auth    = false;
    protected $referrer      = '';
    protected $referrer_host = '';

    public function getError () {
        return $this -> error;
    }

    public function __get ( $name ) {
        if ( isset( $this -> $name ) ) {
            return $this -> $name;
        }
    }

    /**
     * 检查方法是否直接访问
     * 检查方法是被直接访问（ 返回 true ），还是被二次调用（ 返回 false ）
     * @return bool
     */
    protected function check_action_is_directaccess () {
        $this -> check_request_module();
        $function = debug_backtrace();
        $is_directaccess = false;
        $controller_suffix = config( 'controller_suffix' );
        $url_controller_layer = config( 'url_controller_layer' );
        $url_controller_layer = $controller_suffix ? $url_controller_layer : '';
        foreach ( $function as $item ) {
            if ( isset( $item['object'] ) ) {
                $class = explode( '\\' , $item['class'] );
                if ( count( $class ) > 1 ) {
                    $class[2] = preg_replace( '/' . $url_controller_layer . '$/' , '' , $class[2] );
                    array_splice( $class , 0 , 1 );
                    array_splice( $class , 1 , 1 );
                    if ( strtolower( $class[0] ) == $item['object'] -> module && strtolower( $class[1] ) == $item['object'] -> controller && strtolower( $item['function'] ) == $item['object'] -> action ) {
                        $is_directaccess = true;
                        break;
                    }
                }
            }
        }

        return $is_directaccess;
    }

    protected function check_request_module () {
        $request = request();
        $this -> is_example = $request -> param( 'is_example' ) == 1;
        $this -> module = strtolower( $request -> module() );
        $this -> controller = strtolower( $request -> controller() );
        $this -> action = strtolower( $request -> action() );
        $this -> referrer = isset( $_SERVER['HTTP_REFERER'] ) && !empty( $_SERVER['HTTP_REFERER'] ) ? $_SERVER['HTTP_REFERER'] : ( isset( $_SERVER['HTTP_REFERRER'] ) && !empty( $_SERVER['HTTP_REFERRER'] ) ? $_SERVER['HTTP_REFERRER'] : '' );
        $referrer = parse_url( $this -> referrer );
        $this -> referrer_host = isset( $referrer['host'] ) && is_string( $referrer['host'] ) ? strtolower( $referrer['host'] ) : '';
    }

    protected function show_browse_auth_token () {
        $auth_token = session( 'wse_browse_auth_token' );
        if ( empty( $auth_token ) || !is_array( $auth_token ) || ( isset( $auth_token['expire'] ) && ( ( $auth_token['expire'] > 0 && $auth_token['endtime'] < time() ) || $auth_token['expire'] <= 0 ) ) ) {
            $expire = 5*60;
            $auth_token = array();
            $auth_token['token'] = get_random_string( 32 );
            $auth_token['expire'] = $expire;
            $auth_token['endtime'] = isset( $expire ) && $expire > 0 ? ( time() + $expire ) : 0;
            session( 'wse_browse_auth_token' , $auth_token );
        } else {
            $auth_token['endtime'] = isset( $auth_token['expire'] ) && $auth_token['expire'] > 0 ? ( time() + $auth_token['expire'] ) : 0;
            session( 'wse_browse_auth_token' , $auth_token );
        }
        $this -> assign( 'browse_auth_token' , $auth_token['token'] );
    }

    protected function check_browse_auth_token ( $auth_token_param = '' , $is_strict = true ) {
        $is_strict = $is_strict === true;
        if ( $is_strict ) {
            if ( !$this -> check_domain( get_allow_domain( false ) , get_allow_ip() ) ) {
                $this -> error = '非法操作';

                return false;
            }
        }
        $auth_token = session( 'wse_browse_auth_token' );
        if ( empty( $auth_token ) || !is_array( $auth_token ) || ( isset( $auth_token['expire'] ) && $auth_token['expire'] > 0 && $auth_token['endtime'] < time() ) ) {
            $this -> error = 'token已过期';

            return false;
        } else if ( $auth_token['token'] !== $auth_token_param ) {
            $this -> error = 'token验证失败';

            return false;
        } else {
            $auth_token['endtime'] = isset( $auth_token['expire'] ) && $auth_token['expire'] > 0 ? ( time() + $auth_token['expire'] ) : 0;
            session( 'wse_browse_auth_token' , $auth_token );
        }

        return true;
    }

    protected function ajaxSuccess ( $message = '' , $jumpUrl = '' , $waitSecond = 1 ) {
        $result = array();
        $result['status'] = 1;
        $result['info'] = $message;
        $result['url'] = $jumpUrl;
        $result['wait'] = $waitSecond;
        json( $result ) -> send();
        die;
    }

    protected function ajaxError ( $message = '' , $jumpUrl = '' , $waitSecond = 3 ) {
        $result = array();
        $result['status'] = 0;
        $result['info'] = $message;
        $result['url'] = $jumpUrl;
        $result['wait'] = $waitSecond;
        json( $result ) -> send();
        die;
    }
    /**
     * 请确保项目文件有可写权限，不然打印不了日志。
     */
    protected function writeLog($text='',$file_path='common_log.log',$logs_root=null) {
        // $text=iconv("GBK", "UTF-8//IGNORE", $text);
        //$text = characet ( $text );
        //file_put_contents ( dirname ( __FILE__ ).DIRECTORY_SEPARATOR."./../../log.txt", date ( "Y-m-d H:i:s" ) . "  " . $text . "\r\n", FILE_APPEND );
        $logs_root = is_null($logs_root)?$this->logs_root:$logs_root;
        $file_path = empty($file_path)?'common_log.log':$file_path;
        file_put_contents ( $logs_root.$file_path, date ( "Y-m-d H:i:s" ) . "  " . $text . "\r\n", FILE_APPEND );
    }
}