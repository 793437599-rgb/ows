<?php

namespace app\oauth\Model;

use think\Model;

class IpDomain extends Model {
    use \app\common\traits\traitModel;
    protected $autoWriteTimestamp = false;
    protected $_validate = array(
        array( 'domain' , 'require' , '域名为空' ) ,
        array( 'level' , 'require' , '域名级别为空' ) ,
        array( 'level' , 'number' , '域名级别必须为数字' ) ,
        array( 'top_domain' , 'require' , '对应的顶级域名为空' ) ,
        //array('parent_domain','require','对应的上级域名为空'),
        array( 'domain_suffix' , 'require' , '对应的域名后缀为空' ) ,
        //array('manual_ip','require','手动设置的IP为空'),
        array( 'ip' , 'require' , 'IP为空' ) ,
        array( 'ip_long' , 'number' , '数字IP不合法' ) ,
        array( 'ipv6' , 'require' , 'IPv6为空' ) ,
        array( 'ipv6_long' , 'number' , '数字IP（IPv6）不合法' ) ,
        array( 'is_manual' , array( 1 , 2 ) , '域名ip手动状态不合法' , 0 , 'in' ) ,
        array( 'status' , array( 1 , 2 ) , '域名状态不合法' , 0 , 'in' ) ,
    );
    protected $auto = [];
    protected $insert = ['status','etime'];
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
            if(!isset($self->status)||in_array($self->status,array(1,2))){
                $self->status = 1;
            }
            if(!isset($self->ctime)||is_numeric($self->ctime)){
                $self->ctime = time();
            }
            if(!isset($self->etime)||is_numeric($self->etime)){
                $self->etime = $self->ctime;
            }
        });
        self::event('before_update', function ($self) {
            if(!isset($self->etime)||is_numeric($self->etime)){
                $self->etime = time();
            }
        });
        self::event('before_delete', function ($self) {});

        $clear_cache=function($self){
            $updateWhere=$self->getWhere();
            $domain = property_exists($self,'domain')&&$self->domain?$self->domain:'';
            $domain = !empty($domain)?$domain:(isset($updateWhere['domain'])&&$updateWhere['domain']?$updateWhere['domain']:'');
            $ip = property_exists($self,'ipv6_long')&&$self->ipv6_long?$self->ipv6_long:'';
            $ip = !empty($ip)?$ip:(isset($updateWhere['ipv6_long'])&&$updateWhere['ipv6_long']?$updateWhere['ipv6_long']:'');
            $self->clear_cache($domain,$ip);
        };
        self::event('after_insert', $clear_cache);
        self::event('after_update', $clear_cache);
        self::event('after_delete', $clear_cache);
    }

    public function clear_cache ( $domain = '' , $ip = '' ) {
        $domain_list = array();
        if ( is_string( $domain ) ) {
            $domain_list[] = $domain;
        } else if ( is_array( $domain ) ) {
            $domain_list = array_merge( $domain_list , $domain );
        }
        $domain_list = array_unique( $domain_list );
        foreach ( $domain_list as $domain_temp ) {
            cache('wse_get_domain_ip_info_' . $domain_temp , null );
        }
        $ip_list = array();
        if ( is_numeric( $ip ) ) {
            $ip_list[] = $ip;
        } else if ( is_string( $ip ) ) {
            $ip_list[] = $ip;
        } else if ( is_array( $ip ) ) {
            $ip_list = array_merge( $ip_list , $ip );
        }
        if ( !empty( $domain_list ) ) {
            $map = array();
            $map['domain'] = array( 'in' , $domain_list );
            $ip_info_list = db( 'ip_domain' ) -> where( $map ) -> field( 'ip_long,ipv6_long' ) -> select();
            if ( !empty( $ip_info_list ) ) {
                $ip_long = array_column( $ip_info_list , 'ip_long' );
                $ipv6_long = array_column( $ip_info_list , 'ipv6_long' );
                $ip_list = array_merge( $ip_list , $ip_long );
                $ip_list = array_merge( $ip_list , $ipv6_long );
            }
        }
        $ip_list = array_unique( $ip_list );
        foreach ( $ip_list as $ip_item ) {
            $ip_type = '';
            $ip_cache_temp = '';
            if ( is_ip( $ip_item , 'ipv4' , 'auto' ) ) {
                $ip_type = 'ipv4';
                if ( is_numeric( $ip_item ) ) {
                    $ip_cache_temp = $ip_item;
                } else {
                    $ip_cache_temp = ip2long( $ip_item );
                }
            } else if ( is_ip( $ip_item , 'ipv6' , 'auto' ) ) {
                $ip_type = 'ipv6';
                if ( is_numeric( $ip_item ) ) {
                    $ip_cache_temp = $ip_item;
                } else {
                    $ip_cache_temp = ip2long_v6( $ip_item );
                }
            }
            if ( in_array( $ip_type , array( 'ipv4' , 'ipv6' ) ) ) {
                $ip_cache_temp = sprintf( '%u' , $ip_cache_temp );
                cache('wse_get_ip_domain_list_from_ip_' . $ip_cache_temp , null );
            }
        }
    }

    /*-----检查字段  start-----*/
    public function check_field ( $param = array() ) {
        return true;
    }
    /*-----检查字段  end-----*/

    public function get_list ( $ip = '' , $field = '' ) {
        $is_number_ip = false;
        if ( is_ip( $ip , 'ipv4' , true ) ) {
            $ip_type = 'ipv4';
            $ip_cache = sprintf( '%u' , ip2long( $ip ) );
        } else if ( is_ip( $ip , 'ipv4' , false ) ) {
            $ip_type = 'ipv4';
            $is_number_ip = true;
            $ip_cache = sprintf( '%u' , $ip );
        } else if ( is_ip( $ip , 'ipv6' , true ) ) {
            $ip_type = 'ipv6';
            $ip_cache = ip2long_v6( $ip );
        } else if ( is_ip( $ip , 'ipv6' , false ) ) {
            $ip_type = 'ipv6';
            $is_number_ip = true;
            $ip_cache = sprintf( '%u' , $ip );
        } else {
            $this -> error = '该ip非正常ip';

            return false;
        }
        $result = cache('wse_get_ip_domain_list_from_ip_' . $ip_cache );
        if ( empty( $result ) ) {
            $map = array();
            if ( $ip_type == 'ipv4' ) {
                $map['ip_long'] = $ip_cache;
                if ( !$is_number_ip ) {
                    $map['ip'] = $ip;
                }
            } else if ( $ip_type == 'ipv6' ) {
                $map['ipv6_long'] = $ip_cache;
                if ( !$is_number_ip ) {
                    $map['ipv6'] = $ip;
                }
            }
            $result = db( 'ip_domain' ) -> where( $map ) -> order( 'ip_long asc,ipv6_long asc' ) -> select();
            if ( !empty( $result ) ) {
                foreach( $result as $key=>$item){
                    if( $item['is_manual']==2 ){
                        $temp = $this->get_ip_result_from_ip($item['manual_ip']);
                        $result[$key] = array_merge($result[$key],$temp);
                    }
                }

                cache('wse_get_ip_domain_list_from_ip_' . $ip_cache , $result , 30 * 24 * 60 * 60 );
            } else {
                $result = array();
            }
        }
        if ( !empty( $field ) ) {
            if ( isset( $result[0][$field] ) ) {
                $result = array_column_diy( $result , $field );
            } else {
                $result = array();
            }
        }

        return $result;
    }

    /**
     * 获取域名ip关系详情
     * @param string $domain
     * @param string $field
     * @return array|mixed|string
     */
    public function get_info ( $domain = '' , $field = '' ) {
        $domain_cache = str_replace( '.' , '_' , $domain );
        $result = cache('wse_get_domain_ip_info_' . $domain_cache );
        if ( empty( $result ) || ($result['etime']+30*24*60*60<=time()) ) {
            $map = array();
            $map['domain'] = $domain;
            $result = db( 'ip_domain' ) -> where( $map ) -> find();
            if ( !empty( $result ) ) {
                if( $result['is_manual']==2 ){
                    $temp = $this->get_ip_result_from_ip($result['manual_ip']);
                    $result = array_merge($result,$temp);
                }

                $domain_cache = str_replace( '.' , '_' , $result['domain'] );
                cache('wse_get_domain_ip_info_' . $domain_cache , $result , 30 * 24 * 60 * 60 );
            } else {
                $status = $this -> update_domain_ip( $domain );
                if ( $status !== false ) {
                    $result = $this -> get_info( $domain );
                }
            }
        }
        if(!empty($field)){
            if ( is_string( $field ) ) {
                $field = strpos( $field , ',' ) === false ? array( $field ) : explode( ',' , $field );
            }
            $result_temp = array();
            foreach ( $field as $field_temp ) {
                if ( isset( $result[$field_temp] ) ) {
                    $result_temp[$field_temp] = $result[$field_temp];
                } else {
                    $result_temp[$field_temp] = '';
                }
            }
            if ( count( $field ) == 1 ) {
                $result = !empty($result_temp)?$result_temp[$field[0]]:'';
            } else {
                $result = $result_temp;
            }
        }

        return $result;
    }

    public function update_domain_ip ( $domain = '' , $transaction = true ) {
        $transaction = $transaction !== false;
        if ( $transaction ) {
            \think\Db::startTrans();
        }
        $data = $this -> get_domain_result( $domain , true );
        if ( isset( $data['add'] ) && !empty( $data['add'] ) ) {
            $status = $this ->validate('oauth/IpDomain')-> saveAll( $data['add'] );
            if ( !$status ) {
                if ( $transaction ) {
                    \think\Db::rollback();
                }
                $this -> error = $this -> error ? $this -> error : '添加域名ip记录失败';

                return false;
            }
        }
        if ( isset( $data['save'] ) && !empty( $data['save'] ) ) {
            $status = $this ->validate('oauth/IpDomain')-> saveAll( $data['save'] );
            if ( $status === false ) {
                if ( $transaction ) {
                    \think\Db::rollback();
                }
                $this -> error = $this -> error ? $this -> error : '更新域名ip记录失败';

                return false;
            }
        }
        if ( $transaction ) {
            \think\Db::commit();
        }
        $domain_list = array_merge(array_column_diy($data['add'],'domain'),array_column_diy($data['save'],'domain'));
        $ip_list = array_merge(array_column_diy($data['add'],'ipv6_long'),array_column_diy($data['save'],'ipv6_long'));
        $this->clear_cache($domain_list,$ip_list);

        return true;
    }

    /**
     * 获取域名对应的IP数据
     * @param string           $domain
     * @param array|true|false $domain_list 已存在的的域名信息，false 表示，仅返回结果，不区分数据库中是否存在该数据；
     *                                      true 或 array 表示接下来要保存入数据库，需要区分新增数据与更新已有数据，true会自动获取数据库中已有数据。array 则为外部传入数据库已有数据，无需自动获取
     * @param string           $get_ip_action 当前仅支持 dns_get_record 与 gethostbyname ，默认为 gethostbyname
     * @return array
     */
    public function get_domain_result ( $domain = '' , $domain_list = array() ,$get_ip_action='dns_get_record' ) {
        if ( !empty( $domain_list ) && is_array( $domain_list ) ) {
            $is_update = true;
        } else {
            $is_update = $domain_list === true;
            $domain_list = array();
        }

        $get_ip_action = in_array($get_ip_action,array('gethostbyname','dns_get_record'))&&function_exists($get_ip_action)?$get_ip_action:'gethostbyname';

        $result = array();
        if ( $is_update ) {
            $result['add'] = array();
            $result['save'] = array();
        }
        if ( !empty( $domain ) && is_string( $domain ) ) {
            $domain_parts = explode( '.' , $domain );
            $domain_parts_count = count( $domain_parts );
            $domain_suffix = $domain_parts[$domain_parts_count - 1];
            unset( $domain_parts[$domain_parts_count - 1] );
            $domain_parts = array_reverse( $domain_parts );
            $level = 0;
            $domain_temp = $domain_suffix;
            $top_domain = '';
            $parent_domain = '';
            foreach ( $domain_parts as $domain_part ) {
                $has_domain = false;
                $domain_temp = $domain_part . '.' . $domain_temp;
                $ip_result = array( 'ip' => '' , 'ipv6' => '' );
                if( $get_ip_action == 'dns_get_record' ){
                    try{
                        $dns = dns_get_record($domain_temp,DNS_ANY);
                        /*if( $dns === false ){
                            $dns = dns_get_record($domain_temp,DNS_ANY);
                        }*/
                    }catch (\Exception $exception){
                        $this->error = $exception->getMessage();
                        $dns = array();
                    }
                    if($dns === false){
                        $dns = array();
                    }
                    if(!empty($dns)){
                        $has_domain = true;
                        $level += 1;
                        if ( $level == 1 ) {
                            $top_domain = $domain_temp;
                        }
                    }
                    foreach ( $dns as $dns_record ) {
                        if ( $dns_record['host'] == $domain_temp ) {
                            if ( $dns_record['type'] == 'A' ) {
                                if ( isset( $dns_record['ip'] ) && !empty( $dns_record['ip'] ) ) {
                                    $ip_result['ip'] = $dns_record['ip'];
                                }
                            } else if ( $dns_record['type'] == 'AAAA' ) {
                                if ( isset( $dns_record['ipv6'] ) && !empty( $dns_record['ipv6'] ) ) {
                                    $ip_result['ipv6'] = $dns_record['ipv6'];
                                }
                            } else if ( $dns_record['type'] == 'A6' ) {
                                if ( isset( $dns_record['ipv6'] ) && !empty( $dns_record['ipv6'] ) ) {
                                    $ip_result['ipv6'] = $dns_record['ipv6'];
                                }
                            } else if ( $dns_record['type'] == 'CNAME' ) {
                                if ( !empty( $dns_record['target'] ) ) {
                                    $host = gethostbyname( $dns_record['target'] );
                                    if ( is_ip( $host , 'ipv4' , true ) ) {
                                        $ip_result['ip'] = $host;
                                    } else if ( is_ip( $host , 'ipv6' , true ) ) {
                                        $ip_result['ipv6'] = $host;
                                    } else if ( is_ip( $host , 'ipv4' , false ) ) {
                                        $ip_result['ip'] = long2ip( $host );
                                    } else if ( is_ip( $host , 'ipv6' , false ) ) {
                                        $ip_result['ipv6'] = long2ip_v6( $host );
                                    }
                                }
                            }
                        }
                    }
                }
                /* 当无法通过上面的方法获取IP数据时，通过 gethostbyname 方法 来获取IP */
                if ( $get_ip_action == 'gethostbyname' || !$has_domain || ( empty( $ip_result['ip'] ) && empty( $ip_result['ipv6'] ) ) ) {
                    $host = gethostbyname( $domain_temp );
                    if( !$has_domain && $domain_temp ){
                        $has_domain = true;
                        $level += 1;
                        if( $domain_temp!=$host ){
                            if ( $level == 1 ) {
                                $top_domain = $domain_temp;
                            }
                        }
                    }
                    if ( is_ip( $host , 'ipv4' , true ) ) {
                        $ip_result['ip'] = $host;
                    } else if ( is_ip( $host , 'ipv6' , true ) ) {
                        $ip_result['ipv6'] = $host;
                    } else if ( is_ip( $host , 'ipv4' , false ) ) {
                        $ip_result['ip'] = long2ip( $host );
                    } else if ( is_ip( $host , 'ipv6' , false ) ) {
                        $ip_result['ipv6'] = long2ip_v6( $host );
                    }
                }
                if ( $level==1 && $has_domain && empty( $ip_result['ip'] ) && empty( $ip_result['ipv6'] ) ) {
                    $domain_suffix_list = $this->allow_domain_suffix();
                    if(in_array('.'.$domain_temp,$domain_suffix_list)){
                        $has_domain = false;
                        $level = 0;
                    }
                }
                if ( $has_domain || !empty( $ip_result['ip'] ) || !empty( $ip_result['ipv6'] ) ) {
                    $temp = array();
                    $temp['domain'] = $domain_temp;
                    $temp['level'] = $level;
                    $temp['top_domain'] = $top_domain;
                    $temp['parent_domain'] = $parent_domain ? $parent_domain : '';
                    $temp['domain_suffix'] = $domain_suffix;
                    if ( !empty( $ip_result['ip'] ) ) {
                        $temp['ip'] = $ip_result['ip'];
                        $temp['ip_long'] = trim( sprintf( '%u' , ip2long( $temp['ip'] ) ) );
                        $temp['ipv6_long'] = $temp['ip_long'];
                        $temp['ipv6'] = long2ip_v6( $temp['ipv6_long'] );
                    }
                    if ( !empty( $ip_result['ipv6'] ) ) {
                        $temp['ipv6'] = $ip_result['ipv6'];
                        $temp['ipv6_long'] = ip2long_v6( $temp['ipv6'] );
                        if ( is_ip( $temp['ipv6_long'] , 'ipv4' , false ) ) {
                            $temp['ip_long'] = $temp['ipv6_long'];
                            $temp['ip'] = long2ip( $temp['ip_long'] );
                        }
                    }
                    if ( $is_update ) {
                        if ( empty( $domain_list ) ) {
                            $map = array();
                            $map['domain_suffix'] = $domain_suffix;
                            $map['top_domain'] = $top_domain;
                            $domain_list = db( 'ip_domain' ) -> where( $map ) -> column( 'domain' );
                            $domain_list = !empty( $domain_list ) ? $domain_list : array();
                        }
                        $temp['status'] = 1;
                        if ( in_array( $domain_temp , $domain_list ) ) {
                            $temp['etime'] = time();
                        } else {
                            $temp['ctime'] = $temp['etime'] = time();
                        }
                    }
                    if ( $level >= 1 ) {
                        $parent_domain = $domain_temp;
                    }
                } else {
                    if ( $level == 0 ) {
                        $domain_suffix = $domain_part . '.' . $domain_suffix;
                    }
                }
                if( $has_domain || !empty( $ip_result['ip'] ) || !empty( $ip_result['ipv6'] ) ){
                    if($is_update){
                        if ( in_array( $domain_temp , $domain_list ) ) {
                            $result['save'][] = $temp;
                        } else {
                            $result['add'][] = $temp;
                        }
                    }else{
                        $result[] = $temp;
                    }
                }
            }
        }

        return $result;
    }

    /**
     * 从IP中解析出对应的ipv4字符串IP，ipv4数字IP，ipv6字符串IP，ipv6数字IP
     * @param string | int(unsigned)  $ip
     * @return array
     */
    public function get_ip_result_from_ip($ip=''){
        $result = array();
        if( is_ip($ip,'ip','auto') ){
            $ip_result = array();
            if ( is_ip( $ip , 'ipv4' , true ) ) {
                $ip_result['ip'] = $ip;
            } else if ( is_ip( $ip , 'ipv6' , true ) ) {
                $ip_result['ipv6'] = $ip;
            } else if ( is_ip( $ip , 'ipv4' , false ) ) {
                $ip_result['ip'] = long2ip( $ip );
            } else if ( is_ip( $result['manual_ip'] , 'ipv6' , false ) ) {
                $ip_result['ipv6'] = long2ip_v6( $ip );
            }
            if ( !empty( $ip_result['ip'] ) ) {
                $result['ip'] = $ip_result['ip'];
                $result['ip_long'] = trim( sprintf( '%u' , ip2long( $result['ip'] ) ) );
                $result['ipv6_long'] = $result['ip_long'];
                $result['ipv6'] = long2ip_v6( $result['ipv6_long'] );
            }
            if ( !empty( $ip_result['ipv6'] ) ) {
                $result['ipv6'] = $ip_result['ipv6'];
                $result['ipv6_long'] = ip2long_v6( $result['ipv6'] );
                if ( is_ip( $result['ipv6_long'] , 'ipv4' , false ) ) {
                    $result['ip_long'] = $result['ipv6_long'];
                    $result['ip'] = long2ip( $result['ip_long'] );
                }else{
                    $result['ip_long'] = '';
                    $result['ip'] = '';
                }
            }
        }
        return $result;
    }
    /**
     * @description 域名中允许的扩展名
     * @return array
     */
    public function allow_domain_suffix()
    {
        $arr = array(
            '.com', '.com.cn', '.cn', '.net', '.net.cn', '.org', '.org.cn', '.gov.cn', '.hk','.edu','.mil','.tech','.pub',
            '.cc', '.info', '.biz', '.mobi', '.us', '.me', '.co', '.co.jp', '.tv', '.la', '.jp', '.vip', '.ink', '.int',
            '.xin', '.top', '.club', '.xyz', '.wang', '.win','.中国','.公司','.网络','.red','.name','.mobi','.travel','.eu',
            '.pro','.cpa.pro','.law.pro','.med.pro','.museum','.coop','.aero','.mo','.tw','.gi','.aq','.uk','.gb','.firm',
            '.store','.web','.arts','.rec','.com.ve','.university'
        );
        sort($arr);
        return array_unique(array_reverse($arr));
    }
}