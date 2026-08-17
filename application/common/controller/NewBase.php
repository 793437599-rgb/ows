<?php
namespace app\common\controller;

use think\Cache;
use think\Controller;
use think\Db;
use think\Lang;
use think\Request;

class NewBase extends Controller
{


    protected function _initialize()
    {
        $this->redirect('/');
        parent::_initialize();
        $this->getSystem();
        $this->getNav();
        $this->getSlide();
        $this->getLink();
        //访问统计
		addData();
        addSpiderData();
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
            $site_config=[];
            foreach ($list as $k => $v) {
                $site_config[$v['name']] = $v['value'];
            }
            Cache::set('site_config', $site_config);
        }        
        $this->assign('C',$site_config);
    }

    /**
     * 获取前端导航列表
     */
    protected function getNav()
    {
        if (Cache::has('nav')) {
            $nav = Cache::get('nav');
        } else {
            $nav = Db::name('nav')->where(['status' => 1])->order(['sort' => 'ASC','id'=>'ASC'])->select();
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
        $this->assign('link', $link);
    }
    
    /**
     * 六位随机初始密码
     */
    protected function getrandstr(){
        $str='ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz1234567890';
        $randStr = str_shuffle($str);//打乱字符串
        $rands= substr($randStr,0,6);//substr(string,start,length);返回字符串的一部分
        //echo $rands;
        return $rands;
    }
    /**
     * 注册邮件，包含密码
     */
    public function sentEmail($email,$username,$pwd,$domain){

        $content='
        <div>Welcome to WSE. Please use the user name and temporary password
        below to access your Clearinghouse service.</div>
        <div>
            <includetail>
                <div id="original-content">
                    <br>
                    Your user name is:'.$username.'<br><br>
                    Your new password is:'.$pwd.'<br><br>
                    
                    PLEASE NOTE: Your user name is case sensitive. And you can copy and paste the username.<br>
                    &nbsp;<br>
                   
                    LOGIN:&nbsp;'.$domain.' <br>
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
        $result=\org\Email::SendEmail('WSE message ',$content,$email);
        if($result===true){
            $this->success('success','/index/user/register_edu');
        } else {
            $this->error('Failed to register, mailbox already occupied！');
        }
    }
    /**
     * 重置密码邮件
     */
    public function sentEmailreset($email,$pwd,$domain){

        $content='
        <div>Welcome to WSE. Please use the user name and temporary password
        below to access your Clearinghouse service.</div>
        <div>
            <includetail>
                <div id="original-content">
                    Your user name is:'.$email.'<br><br>
                    Your new password is:'.$pwd.'<br><br>
                    You may use this password to access My Access.<br>
                    <br>
                    <br>
                     LOGIN: '.$domain.' <br>
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
        $result=\org\Email::SendEmail('WSE message ',$content,$email);
        if($result===true){
            $this->success('发送成功','/index/user/forget_pass_info');
        } else {

            $this->error('发送失败','/index/user/forget_pass');
        }
    }
    /**
     * 获取ip地址
    */
    public function get_ip()
    {
        if (isset($_SERVER["HTTP_X_REAL_IP"]))
        {
            return $_SERVER["HTTP_X_REAL_IP"];
        }
        else if (isset($_SERVER["HTTP_X_FORWARDED_FOR"]))
        {
            return preg_replace('/^.+,\s*/', '', $_SERVER["HTTP_X_FORWARDED_FOR"]);
        }
        else
        {
            return $_SERVER["REMOTE_ADDR"];
        }
    }
    /**
     * 上传文件
     */
    public function upload(){
        if($_FILES["file"]["size"]>2*1024*1024){
            $this->compress();
        }else{
            $file=request()->file('file');
            if (empty($file)){
                abort(404, '异常消息');
            }
            $info = $file->move(ROOT_PATH . 'public' . DS . 'uploads');
            if($info){
                // 成功上传后 获取上传信息
                $url= DS . 'uploads'.DS.$info->getSaveName();

                $url=str_replace('\\','/',$url);

                $this->success($url);
            }else{
                // 上传失败获取错误信息
                echo $file->getError();
            }
        }
    }
    public function compress(){
        $source = $_FILES['file']['tmp_name'];
        $image_name = md5(time().$source);
        $path = ROOT_PATH . 'public' . DS . 'uploads'.DS.'headimg'.DS.$image_name.'.jpg';
        $dst_img = $path; //可加存放路径
        $img_url = DS .'uploads'.DS.'headimg'.DS.$image_name.'.jpg';
        $percent = 0.5;  #原图压缩，不缩放
        $image = (new \Imgcompress($source,$percent))->compressImg($dst_img);
        $this->success($img_url);
        //return $img_url;
    }
    public function uploads(){
        if($_FILES["file"]["size"]>2*1024*1024){
            $this->compress();
        }else{
            $files=request()->file('file');
            // 移动到框架应用根目录/public/uploads/ 目录下
            $info = $files->move(ROOT_PATH . 'public' . DS . 'uploads');
            if($info){
                // 成功上传后 获取上传信息
                $url= DS . 'uploads'.DS.$info->getSaveName();

                $url=str_replace('\\','/',$url);
                $this->success($url);
            }else{
                // 上传失败获取错误信息
                echo $file->getError();
            }
        }
    }
  
    /**
     * 图片路径信息反序列化
     */
    public function unserialize_img($user_upload){
        if(!empty($data['certificate_img'])){
            $user_upload['certificate_img']=unserialize($user_upload['certificate_img']);
        }else{
            $user_upload['certificate_img']='';
        }
        if(!empty($data['sc_xwz_img'])){
            $user_upload['sc_xwz_img']=unserialize($user_upload['sc_xwz_img']);
        }else{
            $user_upload['sc_xwz_img']='';
        }
        if(!empty($data['sm_img'])){
            $user_upload['sm_img']=unserialize($user_upload['sm_img']);
        }else{
            $user_upload['sm_img']='';
        }
        if(!empty($data['hz_img'])){
            $user_upload['hz_img']=unserialize($user_upload['hz_img']);
        }else{
            $user_upload['hz_img']='';
        }
        if(!empty($data['sc_hz_img'])){
            $user_upload['sc_hz_img']=unserialize($user_upload['sc_hz_img']);
        }else{
            $user_upload['sc_hz_img']='';
        }
        if(!empty($data['sc_crj_img'])){
            $user_upload['sc_crj_img']=unserialize($user_upload['sc_crj_img']);
        }else{
            $user_upload['sc_crj_img']='';
        }
        if(!empty($data['cj_img'])){
            $user_upload['cj_img']=unserialize($user_upload['cj_img']);
        }else{
            $user_upload['cj_img']='';
        }
        if(!empty($data['sc_cj_img'])){
            $user_upload['sc_cj_img']=unserialize($user_upload['sc_cj_img']);
        }else{
            $user_upload['sc_cj_img']='';
        }
        return $user_upload;
    }
}
