<?php

namespace app\common\traits;

trait traitModel {
    private   $logs_root        = './Logs/';
    private   $allow_field_list = array();
    private   $third_model      = array();
    protected $patchValidate    = false;

    public function __get ( $name ) {
        if ( isset( $this -> data[$name] ) ) {
            return $this -> data[$name];
        } else {
            if ( in_array( $name , $this -> allow_field_list ) ) {
                return $this -> $name;
            } else {
                return \Think\Model ::__get( $name );
            }
        }
    }

    protected function get_allow_field_list ( $allow_field = array() ) {
        if ( is_string( $allow_field ) ) {
            $allow_field = array( $allow_field );
        } else if ( is_array( $allow_field ) ) {
            //todo
        } else {
            $allow_field = array();
        }
        $this -> allow_field_list = array_merge( $this -> allow_field_list , $allow_field );

        return $this -> allow_field_list;
    }

    /*获取model*/
    protected function get_third_model ( $model_name = '' , $module = 'Lxkus' ) {
        $model_name = !empty( $model_name ) && is_string( $model_name ) ? $model_name : '';
        if ( !isset( $this -> third_model[$model_name] ) || !$this -> third_model[$model_name] instanceof \Think\Model ) {
            if ( strtolower( $module ) == 'lxkus' && parse_name( $model_name ) == parse_name( $this -> name ) ) {
                $this -> third_model[$model_name] =& $this;
            } else {
                $this -> third_model[$model_name] = model( $module . '/' . $model_name );
            }
        }

        return $this -> third_model[$model_name];
    }

    public function change_order ( $field = 'sort' , $common_map = array() , $target_map = array() , $value = 1 ) {
        if ( !empty( $target_map ) && is_numeric( $value ) ) {
            $data = array();
            $data[$field] = $value;
            db( $this -> name ) -> where( $target_map ) -> update( $data );
        }
        if ( !empty( $common_map ) ) {
            $pk = $this -> getPk();
            $list = db( $this -> name ) -> where( $common_map ) -> field( '`' . $pk . '`,`' . $field . '`' )
                                        -> order( '`' . $field . '` asc,`' . $pk . '` asc' ) -> select();
            if ( !empty( $list ) ) {
                $data = array();
                $value_temp = 1;
                foreach ( $list as $val ) {
                    $temp = array();
                    $temp[$field] = $value_temp;
                    $temp[$pk] = $val[$pk];
                    $data[] = $temp;
                    $value_temp++;
                }
                $this -> isUpdate( true ) -> saveAll( $data );
            }
        }

        return true;
    }

    public function change_default ( $field = 'is_default' , $common_map = array() , $target_map = array() , $default_value = 1 , $other_value = 2 ) {
        $default_value = is_numeric( $default_value ) ? $default_value : 1;
        $other_value = is_numeric( $other_value ) ? $other_value : 2;
        $pk = $this -> getPk();
        if ( !empty( $common_map ) ) {
            $list = db( $this -> name ) -> where( $common_map ) -> field( '`' . $pk . '`,`' . $field . '`' )
                                        -> order( '`' . $field . '` asc,`' . $pk . '` asc' ) -> select();
            if ( !empty( $list ) ) {
                $data = array();
                foreach ( $list as $val ) {
                    $temp = array();
                    $temp[$field] = $other_value;
                    $temp[$pk] = $val[$pk];
                    $data[] = $temp;
                }
                $this -> isUpdate( true ) -> saveAll( $data );
            }
        }
        if ( !empty( $target_map ) ) {
            $pk_list = db( $this -> name ) -> where( $target_map ) -> column( $pk );
            $data = array();
            foreach ( $pk_list as $pk_temp ) {
                $temp = array();
                $temp[$pk] = $pk_temp;
                $temp[$field] = $default_value;
                $data[] = $temp;
            }
            $this -> isUpdate( true ) -> saveAll( $data );
        }

        return true;
    }

    public function is_json ( $str = '' ) {
        if ( !is_string( $str ) || is_null( json_decode( $str ) ) ) {
            return false;
        }
        if ( PHP_VERSION ) {
            $version_allowed = false;
            if ( is_numeric( PHP_VERSION ) ) {
                if ( PHP_VERSION > 5.3 ) {
                    $version_allowed = true;
                }
            } else if ( strpos( PHP_VERSION , '.' ) !== false ) {
                $version = explode( '.' , PHP_VERSION );
                if ( $version[0] > 5 ) {
                    $version_allowed = true;
                } else if ( $version[0] == 5 ) {
                    if ( $version[1] > 3 ) {
                        $version_allowed = true;
                    } else if ( $version[1] == 3 ) {
                        if ( isset( $version[2] ) && $version[2] > 0 ) {
                            $version_allowed = true;
                        }
                    }
                }
            }
            if ( $version_allowed ) {
                json_decode( $str );
                if ( json_last_error() != JSON_ERROR_NONE ) {
                    return false;
                }
            }
        }
        $str = str_replace( '\\' , '' , $str );
        preg_match( '/{.*}/' , $str , $out_obj );
        preg_match( '/\[.*\]/' , $str , $out_arr );
        if ( empty( $out_obj ) && empty( $out_arr ) ) {
            return false;
        }

        return true;
    }

    public function check_json ( $json = '' , $is_strict = false ) {
        $is_strict = $is_strict === true;
        if ( !is_string( $json ) ) {
            return false;
        }
        if ( $is_strict ) {
            if ( empty( $json ) ) {
                return false;
            }
        }
        if ( !empty( $json ) && !$this -> is_json( $json ) ) {
            return false;
        }

        return true;
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