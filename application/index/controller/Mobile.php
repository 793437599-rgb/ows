<?php
namespace app\index\controller;

use app\common\controller\HomeBase;
use app\common\model\Article as ArticleModel;
use app\common\model\Category as CategoryModel;
use think\Db;
use think\Route;

class Mobile extends HomeBase {
	public function index(){
		echo 'app/index/mobile';
	}
}