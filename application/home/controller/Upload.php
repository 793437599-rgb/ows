<?php
namespace app\home\controller;
use think\Controller;
use think\Db;
use think\Url;
use app\index\model\Users;
use app\index\model\UserLevel;
use app\index\model\Test;
class Upload extends Controller{
	public function upload(){
		$file=request()->file('file');
		
		//dump($file);exit;
		$info = $file->move(ROOT_PATH . 'public' . DS . 'uploads');
		//$info = $file->move(ROOT_PATH . 'public' . DS . 'uploads'.DS.date("Ymd"), md5( date('YmdHis')));
		if($info){
            
            $url= DS . 'uploads'.DS.$info->getSaveName();
            
            $url=str_replace('\\','/',$url);
            
			$this->success($url);
        }else{
            // 上传失败获取错误信息
            echo $file->getError();
        }
	}
	public function uploads(){
		$files=request()->file('file');
		
		//dump($file);exit;
		//foreach($files as $file){
			// 移动到框架应用根目录/public/uploads/ 目录下
			$info = $files->move(ROOT_PATH . 'public' . DS . 'uploads');
			if($info){
				// 成功上传后 获取上传信息
				$url= DS . 'uploads'.DS.$info->getSaveName();
            
	            $url=str_replace('\\','/',$url);
	            
				$this->success($url);
			}else{
				// 上传失败获取错误信息
				echo $files->getError();
			}
		//}
	}
}
?>