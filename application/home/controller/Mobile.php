<?php
namespace app\home\controller;

use app\common\controller\NewBase;
use app\common\model\Article as ArticleModel;
use app\common\model\Category as CategoryModel;
use think\Db;
use think\Route;

class Mobile extends NewBase {
	public function index(){
		echo 'app/index/mobile';
	}
}