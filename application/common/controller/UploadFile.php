<?php
namespace app\common\controller;

use think\Controller;

class UploadFile extends  Controller
{
    protected $field;
    protected $ext;
    protected $path;
    protected $savename;
    public function __construct($ext = 'jpg,png,gif',$field = 'file',$path = './uploads/files',$savename = true)
    {
        $this->field = $field;
        $this->path = $path;
        $this->savename =$savename;
        $this->ext = $ext;
    }
    public function localUpload()
    {
        $file = request()->file($this->field);
        $result = ['result_code'=>true,'path'=>'','msg'=>''];
        if($file){
            $info = $file->validate(['ext'=>$this->ext])->move($this->path,$this->savename);
            if($info){
                $result['path'] = $this->path . DS . $info->getSaveName();
            }else{
                $result['result_code'] = false;
                $result['msg'] = $file->getError();
            }
        }else{
            $result['result_code'] = false;
            $result['msg'] = '没有文件上传';
        }
        return $result;
    }
}