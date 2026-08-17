<?php
namespace app\home\controller;

use app\common\controller\NewBase;
use app\common\model\Article as ArticleModel;
use app\common\model\Category as CategoryModel;
use think\Cookie;
use think\Db;
use think\Route;
use think\Cache;
class Index extends NewBase {

	/**
	 * 首页
	 */
    public function index() {
 

        //top4
        $pp=\think\Request::instance()->isMobile();

        //Route::rule('news/:','news?id');
        $topList  = Db::name('article')->where(['status'=>1])->order('is_top,sort,publish_time DESC')->limit(6) ->select(); 
        $topList2  = Db::name('article')->where(['status'=>1])->order('is_top,sort,publish_time DESC')->limit(8) ->select();
        $studyList  = Db::name('nav')->where(['pid'=>6])->order('status,sort,update_time DESC')->limit(6) ->select();  
        $workList  = Db::name('nav')->where(['pid'=>7])->order('status,sort,update_time DESC')->limit(6) ->select(); 
        $veduCanada  = Db::name('nav')->where(['pid'=>125])->order('status,sort,update_time DESC')->limit(8) ->select();
        $eduCanada  = Db::name('nav')->where(['pid'=>123])->order('status,sort,update_time DESC')->limit(3) ->select();
        $ptCanada  = Db::name('nav')->where(['pid'=>123])->order('status,sort,update_time DESC')->limit(13) ->select();

        $title='Home';
        // if($pp) {
        //     //echo 123;die;
        //     return $this->fetch('index_m',[
        //         'topList'=>$topList,
        //         'topList2'=>$topList2,
        //         'studyList'=>$studyList,
        //         'workList'=>$workList,
        //         'veduCanada'=>$veduCanada,
        //         'eduCanada'=>$eduCanada,
        //         'ptCanada'=>$ptCanada,
        //         'title'=>$title,
        //     ]);
        // }
        return $this->fetch('index',[
            'topList'=>$topList,
            'topList2'=>$topList2,
            'studyList'=>$studyList,
            'workList'=>$workList,
            'veduCanada'=>$veduCanada,
            'eduCanada'=>$eduCanada,
            'ptCanada'=>$ptCanada,
            'title'=>$title,
        ]);
    }

public function lang() {
   $lang = $_GET['lang'];
    switch ($lang) {
        case 'zn':
            cookie('think_var', 'zh-cn');
            break;
        case 'en':
            cookie('think_var', 'en-us');
            break;
        case 'ja':
            cookie('think_var', 'ja-jp');
            break;
        case 'de':
            cookie('think_var', 'de-de');
            break;
        case 'ko':
            cookie('think_var', 'ko-kr');
            break;
        case 'ru':
            cookie('think_var', 'ru-ru');
            break;
        case 'fr':
            cookie('think_var', 'fr-fa');
            break;
        default:
            cookie('think_var','zh-cn');
            break;
        }
	}
    /**
     * 列表页  
     */
    public function lists(){
        $cid = $this->request->param('cid',1);
        $page  = $this->request->param('page',1);
        $category_model = new CategoryModel();
        $map[]=['exp',"FIND_IN_SET({$cid},cid)"];
        $category_children_ids = $category_model->where("FIND_IN_SET({$cid},path)")->column('id');
        //$category_children_ids = $category_model->where(['path' => ['like', "%,{$cid},%"]])->column('id');
        $category_children_ids = (!empty($category_children_ids) && is_array($category_children_ids)) ? implode(',', $category_children_ids) . ',' . $cid : $cid;
        $articleModel=new ArticleModel();
        $lists = $articleModel->where('cid','in',$category_children_ids)->order('is_top , sort , publish_time DESC')->paginate(10);

        $category= CategoryModel::get($cid);
        $category_list= $category_model->where(['pid' => $category['pid']])->where('type',1)->select();

        if($category['list_template']){
            $template=$category['list_template'];
        }else{
             $template='lists';
        }
        //热门点击
        $map11['status']  = 1;
        $listReading=Db::name('article')->where($map11)->order('sort DESC')->limit(15) ->select();  
        //置顶
        $map12['is_top']  = 1;
        $listTop=Db::name('article')->where($map12)->order('sort DESC')->limit(3) ->select();  
        
		return $this->fetch($template,[
            'lists' => $lists,
            'cid'=>$cid,
            'category'=>$category,
            'category_list'=>$category_list,
            'reading'=>$listReading,
            'top'=>$listTop,
        ]);
    }

    /**
     * 文章页  
     */
    public function article($id){
    	$article = ArticleModel::get(['status' => 1,'id'=>$id]);
    	if ($article) {
            $article->reading=($article->reading+rand(1,9));
    		$article->save();
    	}
        $category= CategoryModel::get($article['cid']);
        $category_model = new CategoryModel();
        $category_list= $category_model->where(['pid' => $category['pid']])->where('type=1')->select();
		$title=$category['name'];
        //热门点击
        $map11['status']  = 1;
        $listReading=Db::name('article')->where($map11)->order('sort DESC')->limit(15) ->select();  
        //置顶
        $map12['is_top']  = 1;
        $listTop=Db::name('article')->where($map12)->order('sort DESC')->limit(3) ->select();  
		return $this->fetch('article',[
            'article' => $article,
            'cid'=>$article['cid'],
            'category'=>$category,
            'category_list'=>$category_list,
            'reading'=>$listReading,
            'top'=>$listTop,
        ]);
    }

    /**
     * 空模板 
     */
    public function none($cid=0){
        return $this->fetch('none',['cid' => $cid]);
    }


}
 