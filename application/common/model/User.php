<?php
namespace app\common\model;
use traits\model\SoftDelete;
use app\common\controller\WorldArea;
use think\Model;

class User extends ModelBase
{
    use SoftDelete;
    protected $autoWriteTimestamp = 'datetime';
    protected $insert = ['create_ip'];
    protected $update = ['update_ip'];
    protected $deleteTime = 'delete_time';

    public function setCreateIpAttr()
    {
        return request()->ip();
    }

    public function setUpdateIpAttr()
    {
        return request()->ip();
    }

    public function getNationalityAttr($value){
        $nationality = WorldArea::getNameByCode($value);
        if (empty($nationality)){
            return $value;
        }
        return  $nationality;
    }

    public function orders()
    {
        return $this->hasMany('UserOrder','user_id','id');
    }
    
    public function getBirthTimeAttr($value)
    {
        if($value=='0000-00-00'){
            $value='';
        }
        return $value;
    }

    public function ku(){
    	return $this->hasmany('Ku');
    }

    public function phone(){
        return $this->hasMany('UserPhone','user_id','id');
    }

    public function email(){
        return $this->hasOne('UserEmail','user_id','id');
    }

    public function address(){
        return $this->hasMany('UserAddress','user_id','id');
    }

    public function credential(){
        return $this->hasMany('UserCredential','user_id','id');
    }

    public function edu(){
        return $this->hasMany('Edu','u_id','id');
    }

    public function passport(){
        return $this->hasMany('UserPassport','user_id','id');
    }

    public function upload(){
        return $this->hasMany('UserUpload','uid','id');
    }
    
    public function agency()
    {
        return $this->belongsTo('Agency');
    }
}