<?php

namespace app\common\util;

class Server {
    private static $server_ip                   = array(    /*服务器ip列表：仅在这些ip运行时视为服务器，其他ip均视为本地*/
        "1" ,
    );
    private static $server_domain               = array(    /*服务器域名列表：仅在这些域名运行时视为服务器，其他域名均视为本地*/
       
    );
    private static $is_check                    = false; /*是否检查过：在服务器上运行/是否线上*/
    private static $is_server                   = true; /*是否在服务器上运行/是否线上*/
    private static $allow_field                 = array(
        'server_ip','server_domain','is_check','is_server','allow_field'
    );
    public function __construct () {
        self::check_is_server();
    }
    public static function check_is_server(){
        if(!self::$is_check){
            self::is_server();
        }
        return self::$is_server;
    }
    private static function is_server(){
        $is_server_ip = false;
        $is_server_domain = false;
        foreach ( self::$server_ip as $ip_temp ) {
            if ( $_SERVER['SERVER_ADDR'] == $ip_temp ) {
                $is_server_ip = true;
                break;
            }
        }
        foreach ( self::$server_domain as $domain_temp ) {
            if ( strpos( $_SERVER['SERVER_NAME'] , $domain_temp ) !== false ) {
                $is_server_domain = true;
                break;
            }
        }
        if ( !$is_server_ip && !$is_server_domain ) {
            self::$is_server = false;
        }
    }
    public static function get ( $name ) {
        if ( in_array( $name , self::$allow_field ) ) {
            if($name=='allow_field'){
                $result = self::$allow_field;
                array_splice($result,array_search('allow_field',$result),1);
                return $result;
            }else{
                try{
                    return isset(self::$$name)?self::$$name:null;
                }catch (\Exception $exception){
                    return null;
                }
            }
        } else {
            return null;
        }
    }
}