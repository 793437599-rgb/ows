<?php

namespace app\common\controller;

use org\Auth;
use think\Loader;
use think\Cache;
use think\Controller;
use think\Db;
use think\Session;
use think\Request;

/**
 * 后台公用基础控制器
 * Class AdminBase
 * @package app\common\controller
 */
class   AdminBase extends Controller
{
    protected function _initialize()
    {
        parent::_initialize();
        $this->checkAuth();
        $this->getMenu();
        $this->getSystem();

        // 输出当前请求控制器（配合后台侧边菜单选中状态）
        $url = Loader::parseName($this->request->controller());    // dump($url);
        $url .= Loader::parseName('/' . $this->request->action());  //dump($url);
        $this->assign('controller', $url);
        $fangfa = $this->request->path();
        if ($fangfa) {
            $lanmus = Db::name('auth_rule')->where('name', $fangfa)->value('pid');
            $this->assign('lanmus',$lanmus);
        }

        // $res= Db::name('auth_rule')->where("name REGEXP '^wsekus/'")->select();
        // foreach ($res as $key => $value) {
        //     $name='wsekus'.  substr($value['name'], 5);
        //      Db::name('auth_rule')->where('id',$value['id'])->setfield('name',$name);
        // }  循环方法  已弃用
        $lxw_transmit_undonumber = model('common/Statistics')->get_lxw_transmit_undonumber();
        $this->assign('lxw_transmit_undonumber', $lxw_transmit_undonumber);

        // $send_user_email = Db::name('email')->where('start', 0)->count();
        // $this->assign('send_user_email', $send_user_email);
        $this->assign('fangfa', strtolower($fangfa));

        // $str = '/wsekus/' . $url;
        // $this->assign('urls', $str);
        // $url1 = Loader::parseName('/' . $this->request->action());  //dump($url1);
        // $this->assign('controllers', $url1);
    }

    /**
     * 权限检查
     * @return bool
     */
    protected function checkAuth()
    {

        if (!Session::has('admin_id')) {


           $admin_user = Db::name('admin_user')->alias('u')->join('admin_personnel p', 'u.id=p.uid', 'left')->field('u.id,u.username,u.status,p.rulelist,p.only') ->find();
                if (!empty($admin_user)) {
                     
                        Session::set('admin_id', $admin_user['id']);
                        Session::set('admin_name', $admin_user['username']);
                        Session::set('admin_userinfo', $admin_user);
                       // $this->success('登录成功', 'wsekus/index/index');
                    
                }


            // if ($this->request->isAjax()) {
            //     $this->error('登陆失效,请重新登陆', 'wsekus/login/index');
            // } else {
            //     $this->redirect('wsekus/login/index');
            // }
        }

        $module = $this->request->module();
        $controller = $this->request->controller();
        $action = $this->request->action();
        // 排除权限
        $not_check = ['wsekus/Index/index', 'wsekus/Authgroup/getjson', 'wsekus/System/clear'];

        if (!in_array($module . '/' . $controller . '/' . $action, $not_check)) {
            $auth = new Auth();
            $admin_id = Session::get('admin_id');
            $admin_user = Db::name('admin_user')->where('id', $admin_id)->find();
            //echo $module . '/' . $controller . '/' . $action.'--'.$admin_id.'----';
            if (!$auth->check($module . '/' . $controller . '/' . $action, $admin_id) && $admin_user['status'] != 1) {
                $this->error('没有权限');
            }
        }
    }

    /**
     * 获取侧边栏菜单
     */
    protected function getMenu()
    {
        $menu = [];
        $admin_id = Session::get('admin_id');
        $admin_info = Session::get('admin_userinfo'); 
   
        if (Cache::has('menulist'.$admin_id)) {
            $menu = Cache::get('menulist'.$admin_id);
        } else {
            $auth = new Auth();
            $auth_rule_list = Db::name('auth_rule')->where('status', 1)->whereIn('id',$admin_info['rulelist'])->order(['sort' => 'ASC', 'id' => 'ASC'])->select();
            $menu = generateTree($auth_rule_list);  //新方法
            Cache::set('menulist'.$admin_id, $menu,28800);
        }
        $this->assign('menu', $menu);
 
        // foreach ($auth_rule_list as $value) {          //原 方法
        //     if ($auth->check($value['name'], $admin_id) || $admin_id == 1) {
        //         $menu[] = $value;
        //     }
        // }
        // $menu = !empty($menu) ? array2tree($menu) : [];
    }

    /**
     * 获取站点信息
     */
    protected function getSystem()
    {
        if (Cache::has('site_config')) {
            $site_config = Cache::get('site_config');
        } else {
            $list = Db::name('system')->field('name,value')->where('status', 0)->select();
            $site_config = [];
            foreach ($list as $k => $v) {
                $site_config[$v['name']] = $v['value'];
            }
            Cache::set('site_config', $site_config);
        }

      
        $this->assign('C', $site_config);
    }
 

}