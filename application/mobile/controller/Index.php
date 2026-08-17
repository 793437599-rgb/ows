<?php

namespace app\mobile\controller;
use app\common\controller\HomeBase;
use app\common\model\Article as ArticleModel;
use app\common\model\Category as CategoryModel;
use think\Db;
use think\Route;

class Index extends HomeBase
{
	public function index(){
		
		//echo 'mobile';
		return $this->fetch();
	}
}
