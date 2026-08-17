<?php

namespace app\common\util;

class Rsa {
    private static $is_server                   = true;/*是否在服务器上运行/是否线上*/
    private static $private_key                 = "";/*私钥内容*/
    private static $public_key                  = "";/*公钥内容*/
    private static $public_key_resource         = false; /*公钥资源*/
    private static $private_key_resource        = false; /*私钥资源*/
    private static $private_key_client          = "";/*私钥内容(客户端)*/
    private static $public_key_client           = "";/*公钥内容(客户端)*/
    private static $public_key_resource_client  = false; /*公钥资源(客户端)*/
    private static $private_key_resource_client = false; /*私钥资源(客户端)*/
    private static $sign_type                   = "";/*签名算法*/
    private static $sign_type_default           = "rsa2";/*默认签名算法*/
    private static $sign_type_list              = array( 'rsa' => OPENSSL_ALGO_SHA1 , 'rsa2' => OPENSSL_ALGO_SHA256 );/*签名算法列表*/
    private static $padding                     = "";/*填充补齐方式*/
    private static $padding_default             = "pkcs1";/*默认填充补齐方式*/
    /*填充补齐方式列表*/
    private static $padding_list          = array(           /*公钥解密和私钥加密*/
        'pkcs1'      => OPENSSL_PKCS1_PADDING ,             /*PKCS#1填充*/
        'pkcs1_oaep' => OPENSSL_PKCS1_OAEP_PADDING ,   /*PKCS#1填充*/
        'no_padding' => OPENSSL_NO_PADDING ,           /*不使用填充*/
        /*'padding_2'=>OPENSSL_SSLV23_PADDING,*/
    );
    private static $padding_list_2        = array(         /*公钥加密和私钥解密*/
        'pkcs1'      => OPENSSL_PKCS1_PADDING ,             /*PKCS#1填充*/
        'pkcs1_oaep' => OPENSSL_PKCS1_OAEP_PADDING ,   /*PKCS#1填充*/
        'no_padding' => OPENSSL_NO_PADDING ,           /*不使用填充*/
        /*'padding_2'=>OPENSSL_SSLV23_PADDING, */
    );
    private static $padding_num_list      = array(       /*各种填充方式，对应需要减去的字节数*/
        OPENSSL_NO_PADDING         => 0 ,
        OPENSSL_PKCS1_PADDING      => 11 ,
        OPENSSL_PKCS1_OAEP_PADDING => 41 ,             /*理论上说应该是41，但实践表明41不行*/
    );
    private static $error                 = '';
    private static $ssl_config            = array(
        "config"           => "/usr/local/openssl/openssl.cnf" ,
        "private_key_bits" => 1024 ,//字节数    512 1024  2048   4096 等
        /*选择在创建CSR时应该使用哪些扩展。可选值有 OPENSSL_KEYTYPE_DSA, OPENSSL_KEYTYPE_DH, OPENSSL_KEYTYPE_RSA 或 OPENSSL_KEYTYPE_EC. 默认值是 OPENSSL_KEYTYPE_RSA.*/
        "private_key_type" => OPENSSL_KEYTYPE_RSA , //加密类型
    );
    private static $key_len               = 1024;
    private static $key_len_client        = 1024;
    private static $numberofdays          = 3650;//有效时长
    private static $privkeypass           = 'wse_rsa';
    private static $privkeypass_default   = null;
    private static $certificate_root_path = '';
    private static $certificate_path      = array(
        'ca_public_key'     => "certificate/ca_public_key.key" , //key路径
        'ca_key'            => "certificate/ca_key.key" ,        //pem路径
        'ca_pem'            => "certificate/ca.pem" ,            //pem路径
        'ca_p12'            => "certificate/ca.p12" ,            //pem路径
        'ca_crt'            => "certificate/ca.crt" ,            //crt路径
        'ca_csr'            => "certificate/ca.csr" ,            //csr路径
        'server_public_key' => "server/server_public_key.key" ,  //key路径
        'server_key'        => "server/server_key.key" ,         //key路径
        'server_pem'        => "server/server.pem" ,             //pem路径
        'server_p12'        => "server/server.p12" ,             //key路径
        'server_crt'        => "server/server.crt" ,             //crt路径
        'server_csr'        => "server/server.csr" ,             //csr路径
        'client_public_key' => "client/client_public_key.key" ,  //key路径
        'client_key'        => "client/client_key.key" ,         //key路径
        'client_pem'        => "client/client.pem" ,             //pem路径
        'client_p12'        => "client/client.p12" ,             //p12路径
        'client_crt'        => "client/client.crt" ,             //crt路径
        'client_csr'        => "client/client.csr" ,             //csr路径
    );
    private static $ssl_path              = array(
        'ssl_pem' => "ssl/ssl.pem" ,
        'ssl_crt' => "ssl/ssl.crt" ,
    );
    private static $default_dn            = array(
        "countryName"            => 'CA' ,           //所在国家名称
        "stateOrProvinceName"    => 'Henan' ,        //所在省份名称
        "localityName"           => 'Zhengzhou' ,    //所在城市名称
        "organizationName"       => 'wse' ,          //注册人姓名
        "organizationalUnitName" => 'wse' ,          //组织名称
        "commonName"             => 'www.wse.org' ,  //公共名称/域名
        "subjectAltName"         => 'www.wse.org' ,  //公共名称/域名
        "emailAddress"           => 'data@wse.org' , //邮箱
    );

    public function __construct ( $config = array() ) {
        self::init( $config );
    }

    public static function init ( $config = array() ) {
        self::$key_len =& self::$ssl_config['private_key_bits'];
        self::$is_server = \app\common\util\Server::check_is_server();
        if ( !self::$is_server ) {
            self::$ssl_config['config'] = "D:\phpstudy_pro\Extensions\Apache2.4.39\conf\openssl.cnf";
            /*self::$ssl_config['config']="E:\phpstudy2018\PHPTutorial\Apache\conf\openssl.cnf";*/
            //self::$ssl_config['config']=str_replace('\\','/',self::$ssl_config['config']);
        }
        if ( empty( self::$certificate_root_path ) ) {
            self::$certificate_root_path = dirname( __FILE__ ) . "/certificate/wse/";
            self::$certificate_root_path = str_replace( '\\' , '/' , self::$certificate_root_path );
        }
        if ( is_array( $config ) && !empty( $config['sign_type'] ) ) {
            $config['sign_type'] = strtolower( $config['sign_type'] );
            if ( !empty( self::$sign_type_list[$config['sign_type']] ) ) {
                self::$sign_type = $config['sign_type'];
            }
        }
        if ( is_array( $config ) && !empty( $config['padding'] ) ) {
            $config['padding'] = strtolower( $config['padding'] );
            if ( !empty( self::$padding_list[$config['padding']] ) ) {
                self::$padding = $config['padding'];
            }
        }
        if ( empty( self::$sign_type ) ) {
            self::$sign_type = strtolower( config( 'RSA_config.sign_type' ) );
            self::$sign_type = self::$sign_type ? self::$sign_type : self::$sign_type_default;
        }
        if ( empty( self::$padding ) ) {
            self::$padding = strtolower( config( 'RSA_config.padding' ) );
            self::$padding = self::$padding ? self::$padding : self::$padding_default;
        }
        if ( is_array( $config ) && !empty( $config['private_key'] ) ) {
            self::$private_key = $config['private_key'];
        } else {
            $file_client = self::$certificate_root_path . self::$certificate_path['client_key'];
            $file_server = self::$certificate_root_path . self::$certificate_path['server_key'];
            if ( is_file( $file_client ) ) {
                self::$private_key_client = file_get_contents( $file_client );
            }
            if ( is_file( $file_server ) ) {
                self::$private_key = file_get_contents( $file_server );
            }
        }
        if ( is_array( $config ) && !empty( $config['public_key'] ) ) {
            self::$public_key = $config['public_key'];
        } else {
            $file_client = self::$certificate_root_path . self::$certificate_path['client_public_key'];
            $file_server = self::$certificate_root_path . self::$certificate_path['server_public_key'];
            if ( is_file( $file_client ) ) {
                self::$public_key_client = file_get_contents( $file_client );
            }
            if ( is_file( $file_server ) ) {
                self::$public_key = file_get_contents( $file_server );
            }
        }
        if ( !empty( self::$private_key ) ) {
            $private_key = self::get_private_key( self::$private_key );
            self::$private_key_resource = openssl_pkey_get_private( $private_key );
        }
        if ( !empty( self::$public_key ) ) {
            $public_key = self::get_public_key( self::$public_key );
            self::$public_key_resource = openssl_pkey_get_public( $public_key );
        }
        if ( !empty( self::$private_key_client ) ) {
            $private_key_client = self::get_private_key( self::$private_key_client );
            self::$private_key_resource_client = openssl_pkey_get_private( $private_key_client );
        }
        if ( !empty( self::$public_key_client ) ) {
            $public_key_client = self::get_public_key( self::$public_key_client );
            self::$public_key_resource_client = openssl_pkey_get_public( $public_key_client );
        }
        if ( self::$public_key_resource !== false ) {
            self::$key_len = openssl_pkey_get_details( self::$public_key_resource )['bits'];
            self::$ssl_config['private_key_bits'] = self::$key_len;
        }
        if ( self::$public_key_resource_client !== false ) {
            self::$key_len_client = openssl_pkey_get_details( self::$public_key_resource_client )['bits'];
            self::$ssl_config['private_key_bits'] = self::$key_len;
        }

        return self::class;
    }

    public static function get ( $field = '' ) {
        $field_list = array( 'error' , 'sign_type' , 'padding' , 'public_key' , 'public_key_resource' , 'private_key' , 'private_key_resource' );
        if ( empty( $field ) ) {
            $result = array();
            foreach ( $field_list as $val ) {
                $result[$val] = self::$$val;
                /*dump($val.'：'.$result[$val]);*/
            }
        } else {
            if ( in_array( $field , $field_list ) ) {
                $result = self::$$field;
            } else {
                $result = '';
            }
        }

        return $result;
    }

    /**
     * 获取私有key字符串 重新格式化  为保证任何key都可以识别
     * @param string $private_key 私钥
     * @return string
     */
    public static function get_private_key ( $private_key = '' ) {
        /*return $private_key;*/
        $search = [
            "-----BEGIN PRIVATE KEY-----" ,
            "-----END PRIVATE KEY-----" ,
            "\n" ,
            "\r" ,
            "\r\n" ,
        ];
        /*$search_2 = [
            "-----BEGIN RSA PRIVATE KEY-----",
            "-----END RSA PRIVATE KEY-----",
            "\n",
            "\r",
            "\r\n"
        ];*/
        $private_key = str_replace( $search , "" , $private_key );

        return $search[0] . PHP_EOL . wordwrap( $private_key , 64 , "\n" , true ) . PHP_EOL . $search[1];
    }

    /**
     * 获取公共key字符串  重新格式化 为保证任何key都可以识别
     * @param string $public_key 公钥
     * @return string
     */
    public static function get_public_key ( $public_key = '' ) {
        /*return $public_key;*/
        $search = [
            "-----BEGIN PUBLIC KEY-----" ,
            "-----END PUBLIC KEY-----" ,
            "\n" ,
            "\r" ,
            "\r\n" ,
        ];
        $public_key = str_replace( $search , "" , $public_key );

        return $search[0] . PHP_EOL . wordwrap( $public_key , 64 , "\n" , true ) . PHP_EOL . $search[1];
    }

    /**
     * 获取私有key字符串（无报文头）
     * @param string $private_key 私钥
     * @return string
     */
    public static function get_private_key_without_fixes ( $private_key = '' ) {
        $search = [
            "-----BEGIN PRIVATE KEY-----" ,
            "-----END PRIVATE KEY-----" ,
            "\n" ,
            "\r" ,
            "\r\n" ,
        ];
        /*$search_2 = [
            "-----BEGIN RSA PRIVATE KEY-----",
            "-----END RSA PRIVATE KEY-----",
            "\n",
            "\r",
            "\r\n"
        ];*/
        $private_key = str_replace( $search , "" , $private_key );

        return $private_key;
    }

    /**
     * 获取公共key字符串（无报文头）
     * @param string $public_key 公钥
     * @return string
     */
    public static function get_public_key_without_fixes ( $public_key = '' ) {
        $search = [
            "-----BEGIN PUBLIC KEY-----" ,
            "-----END PUBLIC KEY-----" ,
            "\n" ,
            "\r" ,
            "\r\n" ,
        ];
        $public_key = str_replace( $search , "" , $public_key );

        return $public_key;
    }

    /**
     * 用私钥加密
     * @param string $input     需要加密的明文
     * @param string $is_client 使用客户端或服务端密钥  true 客户端 | false 服务端（默认）
     * @return string
     */
    public static function private_encrypt ( $input = '' , $is_client = false ) {
        $is_client = $is_client === true;
        $padding = self::$padding_list[self::$padding];
        $part_len = $is_client ? ( self::$key_len_client / 8 - self::$padding_num_list[$padding] ) : ( self::$key_len / 8 - self::$padding_num_list[$padding] );
        $private_key_resource = $is_client ? self::$private_key_resource_client : self::$private_key_resource;
        $output = '';
        $input = str_split( $input , $part_len );
        foreach ( $input as $part ) {
            $encrypt_string = '';
            openssl_private_encrypt( $part , $encrypt_string , $private_key_resource , $padding );
            $output .= $encrypt_string;
        }

        return base64_encode( $output );
    }

    /**
     * 解密 私钥加密后的密文
     * @param string $input     需要解密的密文
     * @param string $is_client 使用客户端或服务端密钥  true 客户端 | false 服务端（默认）
     * @return string
     */
    public static function public_decrypt ( $input = '' , $is_client = false ) {
        $is_client = $is_client === true;
        $padding = self::$padding_list[self::$padding];
        $part_len = $is_client ? ( self::$key_len_client / 8 ) : ( self::$key_len / 8 );
        $public_key_resource = $is_client ? self::$public_key_resource_client : self::$public_key_resource;
        $output = "";
        $input = base64_decode( $input );
        $parts = str_split( $input , $part_len );
        foreach ( $parts as $part ) {
            $decrypt_string = '';
            openssl_public_decrypt( $part , $decrypt_string , $public_key_resource , $padding );
            $output .= $decrypt_string;
        }
        if ( self::$padding_list_2[self::$padding] == OPENSSL_NO_PADDING ) {
            $output = ltrim( $output , '0' );
        }

        return $output;
    }

    /**
     * 用公钥加密
     * @param string $input     需要加密的明文
     * @param string $is_client 使用客户端或服务端密钥  true 客户端 | false 服务端（默认）
     * @return string
     */
    public static function public_encrypt ( $input = '' , $is_client = false ) {
        $is_client = $is_client === true;
        $padding = self::$padding_list[self::$padding];
        $part_len = $is_client ? ( self::$key_len_client / 8 - self::$padding_num_list[$padding] ) : ( self::$key_len / 8 - self::$padding_num_list[$padding] );
        $public_key_resource = $is_client ? self::$public_key_resource_client : self::$public_key_resource;
        $output = '';
        $input = str_split( $input , $part_len );
        foreach ( $input as $part ) {
            $encrypt_string = '';
            openssl_public_encrypt( $part , $encrypt_string , $public_key_resource , $padding );
            $output .= $encrypt_string;
        }

        return base64_encode( $output );
    }

    /**
     * 解密 公钥加密后的密文
     * @param string $input     需要解密的密文
     * @param string $is_client 使用客户端或服务端密钥  true 客户端 | false 服务端（默认）
     * @return string
     */
    public static function private_decrypt ( $input = '' , $is_client = false ) {
        $is_client = $is_client === true;
        $padding = self::$padding_list[self::$padding];
        $part_len = $is_client ? ( self::$key_len_client / 8 ) : ( self::$key_len / 8 );
        $private_key_resource = $is_client ? self::$private_key_resource_client : self::$private_key_resource;
        $output = "";
        $input = base64_decode( $input );
        $parts = str_split( $input , $part_len );
        foreach ( $parts as $part ) {
            $decrypt_string = '';
            openssl_private_decrypt( $part , $decrypt_string , $private_key_resource , $padding );
            $output .= $decrypt_string;
        }
        if ( self::$padding_list[self::$padding] == OPENSSL_NO_PADDING ) {
            $output = ltrim( $output , '0' );
        }

        return $output;
    }

    /**
     * 创建签名
     * @param string $data      数据
     * @param string $is_client 使用客户端或服务端密钥  true 客户端 | false 服务端（默认）
     * @return null|string
     */
    public static function createSign ( $data = '' , $is_client = false ) {
        $is_client = $is_client === true;
        $public_key_resource = $is_client ? self::$public_key_resource_client : self::$public_key_resource;
        if ( !is_string( $data ) ) {
            $data = '';
        }

        return openssl_sign( $data , $sign , $public_key_resource , self::$sign_type_list[self::$sign_type] ) ? base64_encode( $sign ) : '';
    }

    /**
     * 验证签名
     * @param string $data      数据
     * @param string $sign      签名
     * @param string $is_client 使用客户端或服务端密钥  true 客户端 | false 服务端（默认）
     * @return bool
     */
    public static function verifySign ( $data = '' , $sign = '' , $is_client = false ) {
        $is_client = $is_client === true;
        $public_key_resource = $is_client ? self::$public_key_resource_client : self::$public_key_resource;
        if ( !is_string( $sign ) || !is_string( $sign ) ) {
            return false;
        }

        return (bool) openssl_verify(
            $data ,
            base64_decode( $sign ) ,
            $public_key_resource ,
            self::$sign_type_list[self::$sign_type]
        );
    }

    /**
     * 生成自定义的根证书
     * @param string $dir      证书保存目录
     * @param array  $option   证书相关信息
     * @param string $password 加密密码
     * @param bool   $is_file  是否保存文件  true 保存到目录 | false 不保存，直接返回
     * @return array
     */
    public static function create_ca_certificate ( $dir = '' , array $option = array() , $password = '' , $is_file = true ) {
        $dn = array(
            "countryName"            => ( isset( $option['countryName'] ) && is_string( $option['countryName'] ) && !empty( $option['countryName'] ) )                                  ? $option['countryName'] :            self::$default_dn['countryName'] ,            //所在国家名称
            "stateOrProvinceName"    => ( isset( $option['stateOrProvinceName'] ) && is_string( $option['stateOrProvinceName'] ) && !empty( $option['stateOrProvinceName'] ) )          ? $option['stateOrProvinceName'] :    self::$default_dn['stateOrProvinceName'] ,    //所在省份名称
            "localityName"           => ( isset( $option['localityName'] ) && is_string( $option['localityName'] ) && !empty( $option['localityName'] ) )                               ? $option['localityName'] :           self::$default_dn['localityName'] ,           //所在城市名称
            "organizationName"       => ( isset( $option['organizationName'] ) && is_string( $option['organizationName'] ) && !empty( $option['organizationName'] ) )                   ? $option['organizationName'] :       self::$default_dn['organizationName'] ,       //注册人姓名
            "organizationalUnitName" => ( isset( $option['organizationalUnitName'] ) && is_string( $option['organizationalUnitName'] ) && !empty( $option['organizationalUnitName'] ) ) ? $option['organizationalUnitName'] : self::$default_dn['organizationalUnitName'] , //组织名称
            "commonName"             => ( isset( $option['commonName'] ) && is_string( $option['commonName'] ) && !empty( $option['commonName'] ) )                                     ? $option['commonName'] :             self::$default_dn['commonName'] ,             //公共名称/域名
            "subjectAltName"         => ( isset( $option['subjectAltName'] ) && is_string( $option['subjectAltName'] ) && !empty( $option['subjectAltName'] ) )                         ? $option['subjectAltName'] :         self::$default_dn['subjectAltName'] ,         //公共名称/域名
            "emailAddress"           => ( isset( $option['emailAddress'] ) && is_string( $option['emailAddress'] ) && !empty( $option['emailAddress'] ) )                               ? $option['emailAddress'] :           self::$default_dn['emailAddress'] ,           //邮箱
        );
        $dn['subjectAltName'] = $dn['subjectAltName'] ? $dn['subjectAltName'] : $dn['commonName'];
        self::$privkeypass = ( is_string( $password ) && !empty( $password ) ) ? $password : self::$privkeypass_default; //私钥加解密密码
        self::$ssl_config['encrypt_key'] = self::$privkeypass;
        if ( !empty( $dir ) && is_array( $dir ) ) {
            $root_path = isset( $dir['root_path'] ) && $dir['root_path'] ? $dir['root_path'] : self::$certificate_root_path;
            $file_path = isset( $dir['file_path'] ) && $dir['file_path'] ? $dir['file_path'] : self::$certificate_path;
        } else {
            $root_path = ( empty( $dir ) || $dir === self::$certificate_root_path ) ? self::$certificate_root_path : $dir;
            $file_path = self::$certificate_path;
        }
        /*foreach($file_path as $file_key=>$file){
            if(strpos($file_key,'ca_')!==false&&file_exists($root_path.$file)){
                unlink($root_path.$file);
            }
        }*/
        //生成证书
        $privkey = openssl_pkey_new( self::$ssl_config );
        $csr = openssl_csr_new( $dn , $privkey , self::$ssl_config );
        if ( is_file( $root_path . self::$ssl_path['ssl_crt'] ) && is_file( $root_path . self::$ssl_path['ssl_pem'] ) ) {
            $ssl_cert = file_get_contents( $root_path . self::$ssl_path['ssl_crt'] );
            $ssl_private = array( file_get_contents( $root_path . self::$ssl_path['ssl_pem'] ) , null );
        } else {
            $ssl_cert = null;
            $ssl_private = $privkey;
        }
        $scert = openssl_csr_sign( $csr , $ssl_cert , $ssl_private , self::$numberofdays , self::$ssl_config );
        $pri = openssl_pkey_get_private( $privkey , self::$privkeypass );
        openssl_pkey_export( $privkey , $private_key , self::$privkeypass , self::$ssl_config );
        $public_key = openssl_pkey_get_details( $privkey );
        $public_key = $public_key["key"];
        file_put_contents( $root_path . $file_path['ca_public_key'] , $public_key );
        file_put_contents( $root_path . $file_path['ca_key'] , $private_key );
        $is_file = $is_file !== false;
        if ( $is_file ) {
            //导出证书和密钥文件
            openssl_pkey_export_to_file( $privkey , $root_path . $file_path['ca_pem'] , self::$privkeypass , self::$ssl_config );
            openssl_pkcs12_export_to_file( $scert , $root_path . $file_path['ca_p12'] , $pri , self::$privkeypass );
            openssl_x509_export_to_file( $scert , $root_path . $file_path['ca_crt'] );
            openssl_csr_export_to_file( $csr , $root_path . $file_path['ca_csr'] );
        }
        openssl_free_key( $privkey );
        /*$count=0;
        $success=0;
        foreach($file_path as $file_key=>$file){
            if(strpos($file_key,'ca_')!==false){
                $count++;
                $file_edit_time=filemtime ( $root_path.$file);
                if(file_exists($root_path.$file)&&(time()-$file_edit_time<10)){
                    $success++;
                }
            }
        }
        echo '共'.$count.'个文件，成功生成'.$success.'个文件';*/
        $result = array();
        $result['public_key'] = $public_key;
        $result['private_key'] = $private_key;

        return $result;
    }

    /**
     * 根据已有的根证书生成自定义的服务端证书
     * @param string $dir      证书保存目录
     * @param array  $option   证书相关信息
     * @param string $password 加密密码
     * @param bool   $is_file  是否保存文件  true 保存到目录 | false 不保存，直接返回
     * @return array
     */
    public static function create_server_certificate ( $dir = '' , array $option = array() , $password = '' , $is_file = true ) {
        $dn = array(
            "countryName"            => ( isset( $option['countryName'] ) && is_string( $option['countryName'] ) && !empty( $option['countryName'] ) )                                  ? $option['countryName'] :            self::$default_dn['countryName'] ,            //所在国家名称
            "stateOrProvinceName"    => ( isset( $option['stateOrProvinceName'] ) && is_string( $option['stateOrProvinceName'] ) && !empty( $option['stateOrProvinceName'] ) )          ? $option['stateOrProvinceName'] :    self::$default_dn['stateOrProvinceName'] ,    //所在省份名称
            "localityName"           => ( isset( $option['localityName'] ) && is_string( $option['localityName'] ) && !empty( $option['localityName'] ) )                               ? $option['localityName'] :           self::$default_dn['localityName'] ,           //所在城市名称
            "organizationName"       => ( isset( $option['organizationName'] ) && is_string( $option['organizationName'] ) && !empty( $option['organizationName'] ) )                   ? $option['organizationName'] :       self::$default_dn['organizationName'] ,       //注册人姓名
            "organizationalUnitName" => ( isset( $option['organizationalUnitName'] ) && is_string( $option['organizationalUnitName'] ) && !empty( $option['organizationalUnitName'] ) ) ? $option['organizationalUnitName'] : self::$default_dn['organizationalUnitName'] , //组织名称
            "commonName"             => ( isset( $option['commonName'] ) && is_string( $option['commonName'] ) && !empty( $option['commonName'] ) )                                     ? $option['commonName'] :             self::$default_dn['commonName'] ,             //公共名称/域名
            "subjectAltName"         => ( isset( $option['subjectAltName'] ) && is_string( $option['subjectAltName'] ) && !empty( $option['subjectAltName'] ) )                         ? $option['subjectAltName'] :         self::$default_dn['subjectAltName'] ,         //公共名称/域名
            "emailAddress"           => ( isset( $option['emailAddress'] ) && is_string( $option['emailAddress'] ) && !empty( $option['emailAddress'] ) )                               ? $option['emailAddress'] :           self::$default_dn['emailAddress'] ,           //邮箱
        );
        $dn['subjectAltName'] = $dn['subjectAltName'] ? $dn['subjectAltName'] : $dn['commonName'];
        self::$privkeypass = ( is_string( $password ) && !empty( $password ) ) ? $password : self::$privkeypass_default; //私钥加解密密码
        self::$ssl_config['encrypt_key'] = self::$privkeypass;
        if ( !empty( $dir ) && is_array( $dir ) ) {
            $root_path = isset( $dir['root_path'] ) && $dir['root_path'] ? $dir['root_path'] : self::$certificate_root_path;
            $file_path = isset( $dir['file_path'] ) && $dir['file_path'] ? $dir['file_path'] : self::$certificate_path;
        } else {
            $root_path = ( empty( $dir ) || $dir === self::$certificate_root_path ) ? self::$certificate_root_path : $dir;
            $file_path = self::$certificate_path;
        }
        /*foreach($file_path as $file_key=>$file){
            if(strpos($file_key,'server_')!==false&&file_exists($root_path.$file)){
                unlink($root_path.$file);
            }
        }*/
        //生成证书
        $privkey = openssl_pkey_new( self::$ssl_config );
        $csr = openssl_csr_new( $dn , $privkey , self::$ssl_config );
        if ( is_file( $root_path . $file_path['ca_crt'] ) && is_file( $root_path . $file_path['ca_pem'] ) ) {
            $ca_cert = file_get_contents( $root_path . $file_path['ca_crt'] );
            $ca_private = array( file_get_contents( $root_path . $file_path['ca_pem'] ) , self::$privkeypass );
        } else {
            $ca_cert = null;
            $ca_private = $privkey;
        }
        $scert = openssl_csr_sign( $csr , $ca_cert , $ca_private , self::$numberofdays , self::$ssl_config );
        $pri = openssl_pkey_get_private( $privkey , self::$privkeypass );
        openssl_pkey_export( $privkey , $private_key , self::$privkeypass , self::$ssl_config );
        $public_key = openssl_pkey_get_details( $privkey );
        $public_key = $public_key["key"];
        file_put_contents( $root_path . $file_path['server_public_key'] , $public_key );
        file_put_contents( $root_path . $file_path['server_key'] , $private_key );
        $is_file = $is_file !== false;
        if ( $is_file ) {
            openssl_pkey_export_to_file( $privkey , $root_path . $file_path['server_pem'] , self::$privkeypass , self::$ssl_config );
            openssl_pkcs12_export_to_file( $scert , $root_path . $file_path['server_p12'] , $pri , self::$privkeypass );
            openssl_x509_export_to_file( $scert , $root_path . $file_path['server_crt'] );
            openssl_csr_export_to_file( $csr , $root_path . $file_path['server_csr'] );
        }
        openssl_free_key( $privkey );
        $result = array();
        $result['public_key'] = $public_key;
        $result['private_key'] = $private_key;

        return $result;
    }

    /**
     * 根据已有的根证书生成自定义的客户端证书
     * @param string $dir      证书保存目录
     * @param array  $option   证书相关信息
     * @param string $password 加密密码
     * @param bool   $is_file  是否保存文件  true 保存到目录 | false 不保存，直接返回
     * @return array
     */
    public static function create_client_certificate ( $dir = '' , array $option = array() , $password = '' , $is_file = true ) {
        $dn = array(
            "countryName"            => ( isset( $option['countryName'] ) && is_string( $option['countryName'] ) && !empty( $option['countryName'] ) )                                  ? $option['countryName'] :            self::$default_dn['countryName'] ,            //所在国家名称
            "stateOrProvinceName"    => ( isset( $option['stateOrProvinceName'] ) && is_string( $option['stateOrProvinceName'] ) && !empty( $option['stateOrProvinceName'] ) )          ? $option['stateOrProvinceName'] :    self::$default_dn['stateOrProvinceName'] ,    //所在省份名称
            "localityName"           => ( isset( $option['localityName'] ) && is_string( $option['localityName'] ) && !empty( $option['localityName'] ) )                               ? $option['localityName'] :           self::$default_dn['localityName'] ,           //所在城市名称
            "organizationName"       => ( isset( $option['organizationName'] ) && is_string( $option['organizationName'] ) && !empty( $option['organizationName'] ) )                   ? $option['organizationName'] :       self::$default_dn['organizationName'] ,       //注册人姓名
            "organizationalUnitName" => ( isset( $option['organizationalUnitName'] ) && is_string( $option['organizationalUnitName'] ) && !empty( $option['organizationalUnitName'] ) ) ? $option['organizationalUnitName'] : self::$default_dn['organizationalUnitName'] , //组织名称
            "commonName"             => ( isset( $option['commonName'] ) && is_string( $option['commonName'] ) && !empty( $option['commonName'] ) )                                     ? $option['commonName'] :             self::$default_dn['commonName'] ,             //公共名称/域名
            "subjectAltName"         => ( isset( $option['subjectAltName'] ) && is_string( $option['subjectAltName'] ) && !empty( $option['subjectAltName'] ) )                         ? $option['subjectAltName'] :         self::$default_dn['subjectAltName'] ,         //公共名称/域名
            "emailAddress"           => ( isset( $option['emailAddress'] ) && is_string( $option['emailAddress'] ) && !empty( $option['emailAddress'] ) )                               ? $option['emailAddress'] :           self::$default_dn['emailAddress'] ,           //邮箱
        );
        $dn['subjectAltName'] = $dn['subjectAltName'] ? $dn['subjectAltName'] : $dn['commonName'];
        self::$privkeypass = ( is_string( $password ) && !empty( $password ) ) ? $password : self::$privkeypass_default; //私钥加解密密码
        self::$ssl_config['encrypt_key'] = self::$privkeypass;
        if ( !empty( $dir ) && is_array( $dir ) ) {
            $root_path = isset( $dir['root_path'] ) && $dir['root_path'] ? $dir['root_path'] : self::$certificate_root_path;
            $file_path = isset( $dir['file_path'] ) && $dir['file_path'] ? $dir['file_path'] : self::$certificate_path;
        } else {
            $root_path = ( empty( $dir ) || $dir === self::$certificate_root_path ) ? self::$certificate_root_path : $dir;
            $file_path = self::$certificate_path;
        }
        /*foreach($file_path as $file_key=>$file){
            if(strpos($file_key,'client_')!==false&&file_exists($root_path.$file)){
                unlink($root_path.$file);
            }
        }*/
        //生成证书
        $privkey = openssl_pkey_new( self::$ssl_config );
        $csr = openssl_csr_new( $dn , $privkey , self::$ssl_config );
        if ( is_file( $root_path . $file_path['ca_crt'] ) && is_file( $root_path . $file_path['ca_pem'] ) ) {
            $ca_cert = file_get_contents( $root_path . $file_path['ca_crt'] );
            $ca_private = array( file_get_contents( $root_path . $file_path['ca_pem'] ) , self::$privkeypass );
        } else {
            $ca_cert = null;
            $ca_private = $privkey;
        }
        $scert = openssl_csr_sign( $csr , $ca_cert , $ca_private , self::$numberofdays , self::$ssl_config );
        $pri = openssl_pkey_get_private( $privkey , self::$privkeypass );
        openssl_pkey_export( $privkey , $private_key , self::$privkeypass , self::$ssl_config );
        $public_key = openssl_pkey_get_details( $privkey );
        $public_key = $public_key["key"];
        file_put_contents( $root_path . $file_path['client_public_key'] , $public_key );
        file_put_contents( $root_path . $file_path['client_key'] , $private_key );
        $is_file = $is_file !== false;
        if ( $is_file ) {
            openssl_pkey_export_to_file( $privkey , $root_path . $file_path['client_pem'] , self::$privkeypass , self::$ssl_config );
            openssl_pkcs12_export_to_file( $scert , $root_path . $file_path['client_p12'] , $pri , self::$privkeypass );
            openssl_x509_export_to_file( $scert , $root_path . $file_path['client_crt'] );
            openssl_csr_export_to_file( $csr , $root_path . $file_path['client_csr'] );
        }
        openssl_free_key( $privkey );
        $result = array();
        $result['public_key'] = $public_key;
        $result['private_key'] = $private_key;

        return $result;
    }

    /**
     * 生成自定义的证书
     * @param string $dir      证书保存目录
     * @param array  $option   证书相关信息
     * @param string $password 加密密码
     */
    public static function creat_new_rsa_key ( $dir = '' , array $option = array() , $password = '' ) {
        $dn = array(
            "countryName"            => ( isset( $option['countryName'] ) && is_string( $option['countryName'] ) && !empty( $option['countryName'] ) )                                  ? $option['countryName'] :            self::$default_dn['countryName'] ,            //所在国家名称
            "stateOrProvinceName"    => ( isset( $option['stateOrProvinceName'] ) && is_string( $option['stateOrProvinceName'] ) && !empty( $option['stateOrProvinceName'] ) )          ? $option['stateOrProvinceName'] :    self::$default_dn['stateOrProvinceName'] ,    //所在省份名称
            "localityName"           => ( isset( $option['localityName'] ) && is_string( $option['localityName'] ) && !empty( $option['localityName'] ) )                               ? $option['localityName'] :           self::$default_dn['localityName'] ,           //所在城市名称
            "organizationName"       => ( isset( $option['organizationName'] ) && is_string( $option['organizationName'] ) && !empty( $option['organizationName'] ) )                   ? $option['organizationName'] :       self::$default_dn['organizationName'] ,       //注册人姓名
            "organizationalUnitName" => ( isset( $option['organizationalUnitName'] ) && is_string( $option['organizationalUnitName'] ) && !empty( $option['organizationalUnitName'] ) ) ? $option['organizationalUnitName'] : self::$default_dn['organizationalUnitName'] , //组织名称
            "commonName"             => ( isset( $option['commonName'] ) && is_string( $option['commonName'] ) && !empty( $option['commonName'] ) )                                     ? $option['commonName'] :             self::$default_dn['commonName'] ,             //公共名称/域名
            "subjectAltName"         => ( isset( $option['subjectAltName'] ) && is_string( $option['subjectAltName'] ) && !empty( $option['subjectAltName'] ) )                         ? $option['subjectAltName'] :         self::$default_dn['subjectAltName'] ,         //公共名称/域名
            "emailAddress"           => ( isset( $option['emailAddress'] ) && is_string( $option['emailAddress'] ) && !empty( $option['emailAddress'] ) )                               ? $option['emailAddress'] :           self::$default_dn['emailAddress'] ,           //邮箱
        );
        $dn['subjectAltName'] = $dn['subjectAltName'] ? $dn['subjectAltName'] : $dn['commonName'];
        self::$privkeypass = ( is_string( $password ) && !empty( $password ) ) ? $password : self::$privkeypass_default; //私钥加解密密码
        self::$ssl_config['encrypt_key'] = self::$privkeypass;
        if ( !empty( $dir ) && is_array( $dir ) ) {
            $root_path = isset( $dir['root_path'] ) && $dir['root_path'] ? $dir['root_path'] : self::$certificate_root_path;
            $file_path = isset( $dir['file_path'] ) && $dir['file_path'] ? $dir['file_path'] : self::$certificate_path;
        } else {
            $root_path = ( empty( $dir ) || $dir === self::$certificate_root_path ) ? self::$certificate_root_path : $dir;
            $file_path = self::$certificate_path;
        }
        /*foreach($file_path as $file_key=>$file){
            if((strpos($file_key,'server_')!==false||strpos($file_key,'client_')!==false)&&file_exists($root_path.$file)){
                unlink($root_path.$file);
            }
        }*/
        //生成证书
        $privkey = openssl_pkey_new( self::$ssl_config );
        $csr = openssl_csr_new( $dn , $privkey , self::$ssl_config );
        if ( is_file( $root_path . $file_path['ca_crt'] ) && is_file( $root_path . $file_path['ca_pem'] ) ) {
            $ca_cert = file_get_contents( $root_path . $file_path['ca_crt'] );
            $ca_private = array( file_get_contents( $root_path . $file_path['ca_pem'] ) , self::$privkeypass );
        } else {
            $ca_cert = null;
            $ca_private = $privkey;
        }
        //$sscert = openssl_csr_sign($csr, null, $privkey, self::$numberofdays,self::$ssl_config);
        $sscert = openssl_csr_sign( $csr , $ca_cert , $ca_private , self::$numberofdays , self::$ssl_config );
        $pri = openssl_pkey_get_private( $privkey , self::$privkeypass );
        openssl_pkey_export( $privkey , $private_key , self::$privkeypass , self::$ssl_config );
        $public_key = openssl_pkey_get_details( $privkey );
        $public_key = $public_key["key"];
        file_put_contents( $root_path . $file_path['server_public_key'] , $public_key );
        file_put_contents( $root_path . $file_path['server_key'] , $private_key );
        openssl_pkey_export_to_file( $privkey , $root_path . $file_path['server_pem'] , self::$privkeypass , self::$ssl_config );
        openssl_pkcs12_export_to_file( $sscert , $root_path . $file_path['server_p12'] , $pri , self::$privkeypass );
        openssl_x509_export_to_file( $sscert , $root_path . $file_path['server_crt'] );
        openssl_csr_export_to_file( $csr , $root_path . $file_path['server_csr'] );
        openssl_pkey_export( $privkey , $private_key , self::$privkeypass , self::$ssl_config );
        $public_key = openssl_pkey_get_details( $privkey );
        $public_key = $public_key["key"];
        file_put_contents( $root_path . $file_path['client_public_key'] , $public_key );
        file_put_contents( $root_path . $file_path['client_key'] , $private_key );
        openssl_pkey_export_to_file( $privkey , $root_path . $file_path['client_pem'] , self::$privkeypass , self::$ssl_config );
        openssl_pkcs12_export_to_file( $sscert , $root_path . $file_path['client_p12'] , $pri , self::$privkeypass );
        openssl_x509_export_to_file( $sscert , $root_path . $file_path['client_crt'] );
        openssl_csr_export_to_file( $csr , $root_path . $file_path['client_csr'] );
        openssl_free_key( $privkey );
    }

    /**
     * 生成一对公私钥（不保存，直接返回）
     * @param null $key_size
     * @return array|false
     * 成功返回 公私钥数组
     * 失败 返回 false
     */
    public static function create_key ( $key_size = null ) {
        $ssl_config = self::$ssl_config;
        if ( !empty( $key_size ) ) {
            $ssl_config['private_key_bits'] = $key_size;
        }
        //生成证书
        $privkey = openssl_pkey_new( $ssl_config );
        openssl_pkey_export( $privkey , $private_key , null , $ssl_config );
        $public_key = openssl_pkey_get_details( $privkey );
        $public_key = $public_key["key"];
        $result = array();
        $result['public_key'] = $public_key;
        $result['private_key'] = $private_key;

        return $result;
    }
    /*私钥pfx转pem*/
    /*filePath为pfx文件路径*/
    public static function signfrompfx ( $strData , $filePath , $keyPass ) {
        if ( !file_exists( $filePath ) ) {
            return false;
        }
        $pkcs12 = file_get_contents( $filePath );
        if ( openssl_pkcs12_read( $pkcs12 , $certs , $keyPass ) ) {
            $privateKey = $certs['pkey'];
            $publicKey = $certs['cert'];
            $signedMsg = "";
            if ( openssl_sign( $strData , $signedMsg , $privateKey ) ) {
                $signedMsg = bin2hex( $signedMsg );//这个看情况。有些不需要转换成16进制，有些需要base64编码。看各个接口

                return $signedMsg;
            } else {
                return '';
            }
        } else {
            return '0';
        }
    }

    /*公钥cer转pem（即x.509证书dem格式转换为pem）*/
    public static function verifyReturn ( $data , $signature , $filePath ) {
        /*
        filePath为crt,cert文件路径。x.509证书
        cer to dem， Convert .cer to .pem, cURL uses .pem
        */
        $certificateCAcerContent = file_get_contents( $filePath );
        $certificateCApemContent = '-----BEGIN CERTIFICATE-----' . PHP_EOL
                                   . chunk_split( base64_encode( $certificateCAcerContent ) , 64 , PHP_EOL )
                                   . '-----END CERTIFICATE-----' . PHP_EOL;
        $pubkeyid = openssl_get_publickey( $certificateCApemContent );
        $len = strlen( $signature );
        $signature = pack( "H" . $len , $signature ); //Php-16进制转换为2进制,看情况。有些接口不需要，有些需要base64解码
        /*state whether signature is okay or not*/
        $ok = openssl_verify( $data , $signature , $pubkeyid );
        openssl_free_key( $pubkeyid );

        return $ok;
    }
}