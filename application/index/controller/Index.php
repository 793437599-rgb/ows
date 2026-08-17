<?php

namespace app\index\controller;
header("Content-type: text/html; charset=utf-8");

use app\common\controller\HomeBase;
use app\common\controller\Translation;
use app\common\model\Article as ArticleModel;
use app\common\model\Category as CategoryModel;
use org\Email;
use think\Db;
use think\Session;

class Index extends HomeBase
{

    /**
     * 首页
     */
    public function index()
    {
        $questions = model('common/Question')->selects(['status'=>1,'stick'=>1]);
        $this->assign(['questions'=>$questions]);
        
        $new = Db::name('new') ->order('is_top DESC, id DESC' ) ->select( );
         foreach ($new as $key => &$value) {
            if ($value['link']=='') {
                $value['link']=   '/index/index/new/id/'. $value['id']; 
            }
        }
        $this->assign(['new'=>$new]);
        return $this->fetch('index');
    }

    public function new($id)
    {
        $listTop = Db::name('new')->where( 'id',$id )->find();
        $listTop['content']= htmlspecialchars_decode( $listTop['content']);
        return $this->fetch('new', [
            'new' => $listTop,
        ]);
    }

    public function admincheck($id) //后台来的跳转 
    {
        $data = $this->request->param();
        if ($id!='') {
            $user = Db::name('user')->where('search_id',$id) ->find();
            if ($user) {
                Session::set('user_id', $user['id']);
                Session::set('user_infoname', $user['username']);
                $this->redirect('/profile');
            }
        }
        return false;
       
    }

    public function zh()
    {
        $time=config('lang_set_times') ? config('lang_set_times') : 604800;
        cookie('think_var', 'zh-cn',$time);
        $this->redirect('/');
    }

    public function lang()
    {
        $time=config('lang_set_times') ? config('lang_set_times') : 604800;
        $lang = $_GET['lang'];
        switch ($lang) {
            case 'cn':
                cookie('think_var', 'zh-cn',$time);
                break;
            case 'en':
                cookie('think_var', 'en-us',$time);
                break;
            case 'ja':
                cookie('think_var', 'ja-jp',$time);
                break;
            case 'de':
                cookie('think_var', 'de-de',$time);
                break;
            case 'ko':
                cookie('think_var', 'ko-kr',$time);
                break;
            case 'ru':
                cookie('think_var', 'ru-ru',$time);
                break;
            case 'fr':
                cookie('think_var', 'fr-fa',$time);
                break;
            default:
                cookie('think_var', 'en-us',$time);
                break;
        }
        return status_code(20000);
    }


    /**
     * 列表页
     */
    public function lists()
    {
        $cid = $this->request->param('cid', 1);
        $page = $this->request->param('page', 1);
        $category_model = new CategoryModel();
        $map[] = ['exp', "FIND_IN_SET({$cid},cid)"];
        $category_children_ids = $category_model->where("FIND_IN_SET({$cid},path)")->column('id');
        //$category_children_ids = $category_model->where(['path' => ['like', "%,{$cid},%"]])->column('id');
        $category_children_ids = (!empty($category_children_ids) && is_array($category_children_ids)) ? implode(',', $category_children_ids) . ',' . $cid : $cid;
        $articleModel = new ArticleModel();
        $lists = $articleModel->where('cid', 'in', $category_children_ids)->order('is_top , sort , publish_time DESC')->paginate(10);

        $category = CategoryModel::get($cid);
        $category_list = $category_model->where(['pid' => $category['pid']])->where('type', 1)->select();

        if ($category['list_template']) {
            $template = $category['list_template'];
        } else {
            $template = 'lists';
        }
        //热门点击
        $map11['status'] = 1;
        $listReading = Db::name('article')->where($map11)->order('sort DESC')->limit(15)->select();
        //置顶
        $map12['is_top'] = 1;
        $listTop = Db::name('article')->where($map12)->order('sort DESC')->limit(3)->select();

        return $this->fetch($template, [
            'lists' => $lists,
            'cid' => $cid,
            'category' => $category,
            'category_list' => $category_list,
            'reading' => $listReading,
            'top' => $listTop,
        ]);
    }

    /**
     * 文章页
     */
    public function article($id)
    {
        $article = ArticleModel::get(['status' => 1, 'id' => $id]);
        if ($article) {
            $article->reading = ($article->reading + rand(1, 9));
            $article->save();
        }
        $category = CategoryModel::get($article['cid']);
        $category_model = new CategoryModel();
        $category_list = $category_model->where(['pid' => $category['pid']])->where('type=1')->select();
        $title = $category['name'];
        //热门点击
        $map11['status'] = 1;
        $listReading = Db::name('article')->where($map11)->order('sort DESC')->limit(15)->select();
        //置顶
        $map12['is_top'] = 1;
        $listTop = Db::name('article')->where($map12)->order('sort DESC')->limit(3)->select();
        return $this->fetch('article', [
            'article' => $article,
            'cid' => $article['cid'],
            'category' => $category,
            'category_list' => $category_list,
            'reading' => $listReading,
            'top' => $listTop,
        ]);
    }

    /**
     * 空模板
     */
    public function none($cid = 0)
    {
        return $this->fetch('none', ['cid' => $cid]);
    }

    public function email_shar()  //邮箱分享 发送方法
    {
        if ($this->request->isPost()) {
            $data = $this->request->param();
            //return $this->success('Email sent successfully' ); die;
            $email = $data['mail'] ;
            //$mail = Db::name('mail')->find($id);
            if (empty($email)) {
                $this->error('The mailbox cannot be empty');
            }
            $user = Db::name('user')->find(128);
            //邮件发送
            $template = './static/mail.html';
            $conent = file_get_contents($template);
            $img = dominacom().$data['imgsrc'];
            $time =  date("Y-m-d H:i:s"); //时间
            $username = $user['id'];
            $need = [
                '{{$time}}','{{$img}}'
            ];
            $replaced = [
                $time,$img
            ];
            $ultimately  = str_replace($need, $replaced, $conent);
            $file  =  '/uploads/20200831/45d04d2171e715edc8c9ab78589f9beb.jpg';
            $send = Email::SendEmail('wse mail', $ultimately,$email, $file);
            if(!$send){
                return json(['code'=>0,'邮件发送失败，请稍后重试']);
            }
            $this->success('Email sent successfully' );
        }
    }

    public function code_shar()  //链接分享 发送  
    {
        if ($this->request->isPost()) {
            $data = $this->request->param();
            $email = $data['mail'];
            //$mail = Db::name('email')->find($id);
            $info = Db::name('shar')->where('urlcode',$data['urlcode'])->find();  ;
            if (empty($info)) {
                return $this->error('Operation failed. Please try again');
            }
            if ($info['types']==2) {
                //判断是否在only_mail 字段中
                if (!strstr($info['onlymail'],$email)) {
                  return  $this->error('You cannot view this link');
                }
            }
            //$user = Db::name('user')->find($info['user_id']);
                $code = parent::getrandstr();
                $request = $this->request->instance();
                $domain = $request->domain();
                Session::set('email_shar', $code);
                parent::sentEmailshar($email, $code, $domain);
            $this->success('Email sent successfully' );
        }
    }

    public function code_shar_check(){  //验证加跳转
        $data = $this->request->param();
        $key =$data['code'];
        //$email = $data['mail'];
        if ($key == Session::get('email_shar')) {
            $info =Db::name('shar')->where('urlcode', $data['urlcode'])->find();
            //Session::delete('email_shar');
            return $this->success('success', '/index/index/shar_link/i/'.$data['urlcode']);
        } else {
            return $this->error('Verification code error, please try again' );
        }
    }


    public function shar_link($i){  //分享界面
        $info = Db::name('shar')->where('urlcode',$i)->find();
        if (empty($info)) {
            $this->error('Operation failed. Please try again');
        }
        $this->assign(['info'=>$info]);
        return $this->fetch();
    }


    public function creat_link()  //生成链接
    {
        $user_id = Session::get('user_id');
        $data = $this->request->param();
        $link['imgurl'] =$data['imgcode'];

        if (empty( $data['imgcode'])) {
            return status_code(13000);
        }
        $pic = Db::name('shar')->where('imgurl',$data['imgcode'])->find();
        $allos =1;
         $opt = '';
        if ($pic) {
            //避免生sj过期
            Db::name('shar')->where('imgurl',$data['imgcode'])->setField('create_time',date("Y-m-d H:i:s"));
            if (!empty($pic['onlymail'])) {
                 $onlymail = explode(',', $pic['onlymail']);
                 foreach ($onlymail as $key => $val) {
                    $opt .= "<tr>
                        <td class='mail-text'>{$val}</td>
                        <td><a class='fa fa-envelope-o' aria-hidden='true' data-sendtime='08/10/2020'></a></td>
                        <td><a class='fa fa-trash-o' aria-hidden='true'></a></td>
                    </tr>";  
                }
            }
             $allos = $pic['types'];
            return status_code(20000,$allos,['link'=>dominacom().'/shar/'.$pic['urlcode'],'mails'=>$opt]);
        }
        $link['user_id'] =$user_id;
        $link['urlcode'] = rand_shar_code();
        $link['create_time'] =date("Y-m-d H:i:s");
        $result = Db::name('shar')->insert($link);
        if ($result) {
            return status_code(20000,$allos,['link'=>dominacom().'/shar/'.$link['urlcode'],'mails'=>$opt]);
 
        }
        return status_code(13000);
    }

    public function set_link()  //修改type  所有人查看  部分人查看  link  type
    {
        $data = $this->request->param();
        if (empty($data['link'])) {
            return status_code(13000);
        }
        $pic = Db::name('shar')->where('urlcode',$data['link'])->find();
        if ($pic) {
            //避免生sj过期
            $res=Db::name('shar')->where('urlcode',$data['link'])->setField('types',$data['type']);
            if ($res) {
                return status_code(20000);
            }
        } 
        return status_code(13000);
    }


    public function set_link_addmail()  //增加可查看邮箱 link mail
    {
        $data = $this->request->param();
        if (empty($data['link'])) {
            return status_code(13000);
        }
        $pic = Db::name('shar')->where('urlcode',$data['link'])->find();
        if ($pic) {
            $add = $data['addmail'].','.$pic['onlymail'];
            $res=Db::name('shar')->where('urlcode',$data['link'])->setField('onlymail',trim($add,','));
            if ($res) {
                return status_code(20000);
            }
        } 
        return status_code(13000);
    }

    public function set_link_del()  //减少可查看邮箱 link mail
    {
        $data = $this->request->param();
        if (empty($data['link'])) {
            return status_code(13000);
        }
        $pic = Db::name('shar')->where('urlcode',$data['link'])->find();
        if ($pic) {
            $onlymail = explode(',', $pic['onlymail']);
            array_splice($onlymail,array_keys($onlymail,$data['delmail'])[0],1);
            $onlymail=implode(',', $onlymail);
          
            $res=Db::name('shar')->where('urlcode',$data['link'])->setField('onlymail',trim($onlymail,','));
            if ($res) {
                return status_code(20000);
            }
        } 
        return status_code(13000);
    }

    public function trans()
    {
        $tables = [
            [
                'name' => 'question',
                'field' => [
                    'content'
                ],
            ],
        ];
        $langs = ['zh-cn', 'fr-fa', 'ja-jp', 'ko-kr', 'ru-ru'];
        $values = [];
        foreach ($tables as $table) {
            $name = $table['name'];
            $tableHandle = db($name);
            foreach ($table['field'] as $val) {
                $value = $tableHandle->column($val);
                $values = array_merge($values, $value);
            }
        }
        $values = array_unique(array_filter($values));
        $translation = new Translation();
        $values = array_map('htmlspecialchars_decode', $values);
        $content = '';
        foreach ($values as $v){
            $content .= $v . "\n\n\n";
        }
        echo $content;
    }
}
 
