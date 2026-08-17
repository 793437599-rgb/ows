<?php
namespace app\index\controller;

use app\common\controller\HomeBase;
use app\common\model\Article as ArticleModel;
use app\common\model\Category as CategoryModel;
use think\Db;

class Zt extends HomeBase {

    public function index(){
        $template = $this->request->param('template');
        $category=Db::name('category')->where('list_template','/zt/'.$template)->find();
        if (!$category) {
            abort(404,'页面不存在');
        }
        return $this->fetch($template,[
            'title' => $category['name'],
            'category'=>$category,
        ]);
    }
    public function page(){
        $id = $this->request->param('id');
        if (!is_numeric($id)) {
           $id =regstr($id);
        }
        $category=Db::name('category')->where('id',$id)->find();
        $topList2  = Db::name('article')->where(['status'=>1])->order('is_top,sort,publish_time DESC')->limit(8) ->select();
        //print_r($category);exit;
        if (!$category) {
            abort(404,'页面不存在');
        }
        return $this->fetch('page',[
        	'topList2'=>$topList2,
            'title' => $category['name'],
            'category'=>$category,
            'ids'=>$id,
        ]);
    }
    

}