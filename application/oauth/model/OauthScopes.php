<?php

namespace app\oauth\Model;

use think\Model;

class OauthScopes extends Model
{
    use \app\common\traits\traitModel;
    protected $autoWriteTimestamp = false;
    protected $_validate = array(
        array('scope','require','Oauth授权权限为空'),
        //array('is_default','check_is_default','非法Oauth授权权限默认状态',0,'callback'),
        array('open_type','check_open_type','非法Oauth授权权限类型',0,'callback'),
        array('model','check_model','非法Oauth授权所属模块',0,'callback'),
    );
    protected $is_default_list=array(1,0);
    protected $open_type_list=array(1,2,3);
    protected $model_list=array('Wsekus','Index','Home','Open');
    protected $auto = [];
    protected $insert = ['is_default','open_type'];
    protected $update = [];
    //自定义初始化
    protected function initialize()
    {
        //需要调用`Model`的`initialize`方法
        parent::initialize();
        //TODO:自定义的初始化
        $this->get_allow_field_list(array('is_default_list','open_type_list','model_list'));
    }
    //自定义初始化(只会在初始化实例时执行一次)
    protected static function init()
    {
        //TODO:自定义的初始化
        self::event('before_insert', function ($self) {
            if(!$self->check_is_default($self->is_default,true)){
                $self->is_default = 0;
            }
            if(!$self->check_open_type($self->open_type)){
                $self->open_type = 2;
            }
        });
        self::event('before_update', function ($self) {});
        self::event('before_delete', function ($self) {});

        $clear_cache=function($self){
            $self->clear_cache();
        };
        self::event('after_insert', $clear_cache);
        self::event('after_update', $clear_cache);
        self::event('after_delete', $clear_cache);
    }
    public function clear_cache(){
        foreach($this->open_type_list as $open_type_temp){
            foreach($this->is_default_list as $is_default_temp){
                cache('wse_get_oauth_scopes_list_'.$open_type_temp.'_'.$is_default_temp,null);
            }
        }
    }
    /*--------------  检查字段 start  --------------*/
    public function check_field(&$param=array()){
        $param['id']=intval($param['id']);
        $param['id']=$param['id']>0?$param['id']:0;
        if(isset($param['scope'])){
            $param['scope'] = diy_trim($param['scope']);
            $map = array();
            $map['scope'] = $param['scope'];
            $map['id'] = array('neq' , $param['id']);
            $status = db( 'oauth_scopes' )->where( $map )->field( 'id' )->find();
            if ( !empty( $status ) ) {
                $this->error = lang('error_10000033');
                return false;
            }
        }
        if(isset($param['model'])){
            if(!empty($param['model'])){
                if(is_array($param['model'])){
                    $model_list=$param['model'];
                    $param['model']=array();
                    foreach($this->model_list as $model_temp){
                        if(in_array($model_temp,$model_list)){
                            $param['model'][]=$model_temp;
                        }
                    }
                    $param['model']=implode(' ',$param['model']);
                }
            }else{
                $param['model']='';
            }
        }
        return true;
    }
    public function check_is_default($is_default=-1,$strict=false){
        $strict = $strict===true;
        if($strict){
            $status = in_array($is_default,$this->is_default_list);
        }else{
            $status = true;
        }
        return $status;
    }
    public function check_open_type($open_type=-1){
        return in_array($open_type,$this->open_type_list);
    }
    public function check_model($model=''){
        $model_list=explode(' ',$model);
        $status=true;
        foreach($model_list as $model_temp){
            if(!in_array($model_temp,$this->model_list)){
                $status=false;
                $this->error=lang('error_10000034',[$model_temp]);
                break;
            }
        }
        return $status;
    }
    /*--------------  检查字段  end   --------------*/
    public function get_list($open_type=3,$is_default='all',$field=''){
        $open_type = strtolower($open_type);
        $is_default = strtolower($is_default);
        $is_default_list=array_merge($this->is_default_list,array('all'));
        $is_default=in_array($is_default,$is_default_list)?$is_default:'all';
        $open_type_list=array_merge($this->open_type_list,array('all'));
        $open_type=in_array($open_type,$open_type_list)?$open_type:3;
        if($open_type=='all'){
            $result=array();
            foreach($this->open_type_list as $open_type_temp){
                $result_temp=$this->get_list($open_type_temp,$is_default,$field);
                $result = array_merge($result,$result_temp);
            }
            return $result;
        }
        if($is_default=='all'){
            $result=array();
            foreach($this->is_default_list as $is_default_temp){
                $result_temp=$this->get_list($open_type,$is_default_temp,$field);
                $result = array_merge($result,$result_temp);
            }
            return $result;
        }
        $result=cache('wse_get_oauth_scopes_list_'.$open_type.'_'.$is_default);
        if(empty($result)){
            $map=array();
            $map['is_default']=$is_default;
            $map['open_type']=$open_type;
            $result=db('oauth_scopes')->where($map)->select();
            if(!empty($result)){
                $result=array_map(function($v){
                    $v['model']=diy_explode(' ',$v['model']);
                    return $v;
                },$result);
                cache('wse_get_oauth_scopes_list_'.$open_type.'_'.$is_default,$result,30*24*60*60);
            }else{
                $result=array();
            }
        }
        if(!empty($field)){
            if(!empty($result[0])&&isset($result[0][$field])){
                $result=array_column($result,$field);
                $result=is_array($result)&&!empty($result)?$result:array();
            }else{
                $result=array();
            }
        }
        return $result;
    }
    public function get_info($id=0,$field=''){
        $result=cache('wse_get_oauth_scopes_info_by_id_'.$id);
        if(empty($result)){
            $result=cache('wse_get_oauth_scopes_info_by_scope_'.$id);
        }
        if(empty($result)){
            $result=db('oauth_scopes')->where('id|scope','eq',$id)->find();
            if(!empty($result)){
                $result['model']=diy_explode(' ',$result['model']);
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
        $param['id']=isset($param['id'])?intval($param['id']):0;
        if($param['id']>0){
            $map=array();
            $map['id']=$param['id'];
            $info=db('oauth_scopes')->where($map)->find();
            if(empty($info)){
                $this->error=lang('error_10000049');
                return false;
            }
        }
        if(!$this->check_field($param)){
            return false;
        }
        \think\Db::startTrans();
        if($param['id']>0){
            $status=$this->validate('oauth/OauthScopes.edit')->isUpdate(true)->save($param,['id'=>$param['id']]);
            if($status===false){
                \think\Db::rollback();
                return false;
            }
            $id=$param['id'];
        }else{
            unset($param['id']);
            $status=$this->validate('oauth/OauthScopes')->isUpdate(false)->save($param);
            if(!$status){
                \think\Db::rollback();
                return false;
            }
            $id=$this->id;
        }
        if(isset($param['is_default'])&&$param['is_default']==1){
            $this->change_default('is_default',array('open_type'=>$param['open_type']),array('id'=>$param['id']),1,0);
        }
        \think\Db::commit();
        return $id;
    }
}