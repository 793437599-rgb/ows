<?php

namespace app\common\controller;

use app\common\model\User as UserModel;
use think\Cache;
use think\Controller;
use think\Cookie;
use think\Db;
use think\Lang;
use think\Request;
use think\Session;

class HomeBase extends Controller
{
    use \app\common\traits\traitController;
    protected $lang;
    protected $user_id;

    public function __construct(Request $request)
    {
        header("Access-Control-Allow-Origin:*");
        //$view = Db::name('system')->where('name', 'view')->value('value');
        $view ='canada';
        config('template.view_path', '../themes/' . $view . '/');
        parent::__construct($request);
        //检测页面是否需要登录
    }

    protected function _initialize()
    {
        parent::_initialize();
        $this->getSystem();
        $this->getNav();
        $this->getSlide();
        $this->getLink();
        //访问统计
        addData();
        addSpiderData();
        $this->lang = Cookie::get('think_var') ?: 'en-us';
        $this->checkIsLogin();
        $this->user_id  = \session('user_id');
        // Lang::load(APP_PATH . 'common\lang\zh-cn.php','zh-cn');
        // Lang::load(APP_PATH . 'common\lang\fr-fa.php','fr-fa');
        // Lang::load(APP_PATH . 'common\lang\ja-jp.php','ja-jp');
        // Lang::load(APP_PATH . 'common\lang\ko-kr.php','ko-kr');
        // Lang::load(APP_PATH . 'common\lang\ru-ru.php','ru-ru');
        $this->assign('lang',baidu_languages_transform($this->lang));

        $this->show_browse_auth_token();
    }

    /**
     * 获取站点信息
     */
    protected function getSystem()
    {
        if (Cache::has('system_config')) {
            $site_config = Cache::get('site_config');
        } else {
            $site_config = Db::name('system')->where('status', 0)->column('name,value');
            $site_config['site_tongji'] = htmlspecialchars_decode($site_config['site_tongji']);
            Cache::set('system_config', $site_config);
        } 
        $a = Request()->domain();
        //$a = 'https://www.wse.org';
        $a = str_replace('ve.', '', $a);
        $this->assign('urlss', $a);
        $this->assign('C', $site_config);
    }

    /**
     * 获取前端导航列表
     */
    protected function getNav()
    {
        if (Cache::has('nav')) {
            $nav = Cache::get('nav');
        } else {
            $nav = Db::name('nav')->where(['status' => 1])->order(['sort' => 'ASC', 'id' => 'ASC'])->select();
            $nav = !empty($nav) ? hTree($nav) : [];
            if (!empty($nav)) {
                Cache::set('nav', $nav);
            }
        }
        //print_r($nav);exit;
        $this->assign('nav', $nav);
    }

    /**
     * 获取前端轮播图
     */
    protected function getSlide()
    {
        if (Cache::has('slide')) {
            $slide = Cache::get('slide');
        } else {
            $slide[0] = Db::name('slide')->where(['status' => 1, 'cid' => 1])->order(['sort' => 'DESC'])->select();
            $slide[1] = Db::name('slide')->where(['status' => 1, 'cid' => 2])->order(['sort' => 'DESC'])->select();
            $slide[2] = Db::name('slide')->where(['status' => 1, 'cid' => 3])->order(['sort' => 'DESC'])->select();
            if (!empty($slide)) {
                Cache::set('slide', $slide);
            }
        }

        $this->assign('slide', $slide);
    }

    /**
     * 获取前端友情链接
     */
    protected function getLink()
    {
        if (Cache::has('link')) {
            $link = Cache::get('link');
        } else {
            $link[0] = Db::name('link')->where(['status' => 1, 'sort' => 0])->select();
            $link[1] = Db::name('link')->where(['status' => 1, 'sort' => 1])->select();
            $link[2] = Db::name('link')->where(['status' => 1, 'sort' => 2])->select();
            if (!empty($link)) {
                Cache::set('link', $link);
            }
        }
        //dump($link);exit;
        $this->assign('link', $link);
    }

    /**
     * 注册邮件，包含密码
     */
    public function sentEmail($email, $username, $pwd, $domain)
    {
        $content = '
        <div>Welcome to WSE. Please use the user name and temporary password
        below to access your Clearinghouse service.</div>
        <div>
            <includetail>
                <div id="original-content">
                    <br>
                    Your user name is:' . $username . '<br><br>
                    Your new password is:' . $pwd . '<br><br>
                    
                    PLEASE NOTE: Your user name is case sensitive. And you can copy and paste the username.<br>
                    &nbsp;<br>
                   
                    LOGIN:&nbsp;' . $domain . ' <br>
                    <br>
                    You may use this user name and password to access My Access. <br><br>
                    We would like to remind you that your password must meet the following <br>
                    restrictions: a minimum of 6 characters, a maximum of 30 characters, <br>
                    must contain a minimum of 1 upper case letters, a minimum of 1 lower <br>
                    case letters, a minimum of 1 numbers, and contain a minimum of 0 of <br>
                    the following non-standard characters: >~^(+_{#;")`|!<=]}*/$:?[\',@\&.%.<br>
                   
                    Thank you for choosing WSE. <br><br>
                    EDU DATA EXCHANGE CERTIFICATE CENTRE INC. .<br>
                    Adress: 
                    70 MANOR HAMPTON ST EAST GWILLIMBURY, Ontario Canada L9N0P9.<br>
                    <br>
                    Phone: (+1) 9028066666
                    <br>
                </div>
            </includetail>
        </div>';
        $result = \org\Email::SendEmail('WSE message ', $content, $email);
        if ($result === true) {
            $this->success('success', '/index/user/register_edu');
        } else {
            $this->error('Failed to register, mailbox already occupied！');
        }
    }

    /**
     * 重置密码邮件
     */
    public function sentEmailreset($email, $pwd, $domain)
    {
        $content = '
        <div>Welcome to WSE. Please use the user name and temporary password
        below to access your Clearinghouse service.</div>
        <div>
            <includetail>
                <div id="original-content">
                    Your user name is:' . $email . '<br><br>
                    Your new password is:' . $pwd . '<br><br>
                    You may use this password to access My Access.<br>
                    <br>
                    <br>
                     LOGIN: ' . $domain . ' <br>
                    <br>
                    You may use this user name and password to access My Access. <br><br>
                    We would like to remind you that your password must meet the following <br>
                    restrictions: a minimum of 6 characters, a maximum of 30 characters, <br>
                    must contain a minimum of 1 upper case letters, a minimum of 1 lower <br>
                    case letters, a minimum of 1 numbers, and contain a minimum of 0 of <br>
                    the following non-standard characters: >~^(+_{#;")`|!<=]}*/$:?[\',@\&.%.<br>
                    Thank you for choosing WSE. <br><br>
                    EDU DATA EXCHANGE CERTIFICATE CENTRE INC. .<br>
                    Adress: 
                    70 MANOR HAMPTON ST EAST GWILLIMBURY, Ontario Canada L9N0P9.<br>
                    <br>
                    Phone: (+1) 9028066666
                </div>
            </includetail>
        </div>';
        $result = \org\Email::SendEmail('WSE message ', $content, $email);
        if ($result === true) {
            $this->success('发送成功', '/index/user/forget_pass_info');
        } else {
            $this->error('发送失败', '/index/user/forget_pass');
        }
    }

    /**
     * 获取ip地址
     */
    public function get_ip()
    {
        if (isset($_SERVER["HTTP_X_REAL_IP"])) {
            return $_SERVER["HTTP_X_REAL_IP"];
        } else {
            if (isset($_SERVER["HTTP_X_FORWARDED_FOR"])) {
                return preg_replace('/^.+,\s*/', '', $_SERVER["HTTP_X_FORWARDED_FOR"]);
            } else {
                return $_SERVER["REMOTE_ADDR"];
            }
        }
    }

    /**
     * 上传文件
     */
    public function upload()
    {
        if ($_FILES["file"]["size"] > 2 * 1024 * 1024) {
            $this->compress();
        } else {
            $file = request()->file('file');
            if (empty($file)) {
                abort(404, '异常消息');
            }
            $info = $file->move(ROOT_PATH . 'public' . DS . 'uploads');
            if ($info) {
                // 成功上传后 获取上传信息
                $url = DS . 'uploads' . DS . $info->getSaveName();

                $url = str_replace('\\', '/', $url);

                $this->success($url);
            } else {
                // 上传失败获取错误信息
                echo $file->getError();
            }
        }
    }

    public function compress()
    {
        $source = $_FILES['file']['tmp_name'];
        $image_name = md5(time() . $source);
        $path = ROOT_PATH . 'public' . DS . 'uploads' . DS . 'headimg' . DS . $image_name . '.jpg';
        $dst_img = $path; //可加存放路径
        $img_url = DS . 'uploads' . DS . 'headimg' . DS . $image_name . '.jpg';
        $percent = 0.5;  #原图压缩，不缩放
        $image = (new \Imgcompress($source, $percent))->compressImg($dst_img);
        $this->success($img_url);
        //return $img_url;
    }

    public function uploads()
    {
        if ($_FILES["file"]["size"] > 2 * 1024 * 1024) {
            //echo '123';
            $this->compress();
        } else {
            $files = request()->file('file');
            // 移动到框架应用根目录/public/uploads/ 目录下
            $info = $files->move(ROOT_PATH . 'public' . DS . 'uploads');
            if ($info) {
                // 成功上传后 获取上传信息
                $url = DS . 'uploads' . DS . $info->getSaveName();

                $url = str_replace('\\', '/', $url);
                $this->success($url);
            } else {
                // 上传失败获取错误信息
                echo $file->getError();
            }
        }
    }


    protected function checkIsLogin()
    {
        $controller = strtolower(request()->controller());
        $action = strtolower(request()->action());
        $checks = config('login_check');
        if (!empty($checks[$controller]) && is_array($checks[$controller]) && in_array(strtolower($action),array_map('strtolower',$checks[$controller]))) {
            $this->checkLogin();
        }
    }

    protected function checkLogin()
    {
        $user_id = Session::get('user_id');
        if (empty($user_id)) {
            if (!\request()->isAjax()) {
                $temp_url = \request()->url(true);
                Cookie::set('temp_url', $temp_url);
                $this->redirect('/index/user/log');
            } else {
                $temp_url = $_SERVER['HTTP_REFERER'];
                Cookie::set('temp_url', $temp_url);
                $url = url('index/user/log');
                $msg = 'Not Login In';
                echo json_encode(status_code(11401, $msg, $url)->getData());
                die();
            }
        } else {
            Cookie::delete('temp_url');
        }
        $this->user_id = $user_id;
    }

    public function getrandstr()
    {
        $str = 'abcdefghijklmnopqrstuvwxyz1234567890';
        $randStr = str_shuffle($str);//打乱字符串
        $rands = substr($randStr, 0, 6);//substr(string,start,length);返回字符串的一部分
        //echo $rands;
        return $rands;
    }


    public function sentEmailquery($email, $pwd)
    {
        $template = './static/query_mail.html';
        $conent = file_get_contents($template);
        $need = [
            '{{$key}}' 
        ];
        $replaced = [
            $pwd
        ];
        $ultimately  = str_replace($need, $replaced, $conent);
        $result = \org\Email::SendEmail('WSE message', $ultimately, $email);
        if ($result === true) {
            return status_code(20000, 'Email sent successfully');
        } else {
            return status_code(10500, 'Email failed to send');
        }
 
    }


    public function sentEmailshar($email, $code, $domain)
    {
        $content = '
        <div>Welcome to WSE. You are querying certificate sharing.</div>
        <div>
            <includetail>
                <div id="original-content">
                    Your new captcha is:' . $code . '<br> 
                    <br>
                    You may use this captcha to View sharing links.<br>
                    <br>
                    Thank you for choosing WSE. <br><br>
                    EDU DATA EXCHANGE CERTIFICATE CENTRE INC. .<br>
                    Adress: 
                    70 MANOR HAMPTON ST EAST GWILLIMBURY, Ontario Canada L9N0P9.<br>
                    <br>
                    Phone: (+1) 9028066666
                </div>
            </includetail>
        </div>';
        $result = \org\Email::SendEmail('WSE message ', $content, $email);
        if ($result === true) {
            $this->success('Email sent successfully');
        } else {
            $this->error('Email failed to send');
        }
    }
}