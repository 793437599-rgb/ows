<?php

/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2019/7/29 0029
 * Time: 15:16
 */
namespace app\home\controller;

header("Content-type: text/html; charset=utf-8");

use app\common\controller\NewBase;
use app\common\model\Applications as AppModel;
use app\common\model\AppStep;
use app\common\model\User as UserModel;
use think\Cookie;
use think\Db;
use think\Request;
use think\Session;

class User extends NewBase
{
    public function index(){
        $user_id = Session::get('user_id');
        if(!empty($user_id)){
            $result = UserModel::get($user_id);
            $edu = $result->edu()->find();
            $upload_result = $result->upload()->find();
            $upload = parent::unserialize_img($upload_result);

            $passport = Db::name('user_passport')->alias('p')
                ->join('country c','p.is_country=c.id')
                ->where('user_id',$user_id)
                ->field('p.*,c.name,c.cname,c.name_fa,c.name_jp,c.name_kr,c.name_de,c.name_ru')
                ->find();

            $credential = $result->credential()->find();
            $address = $result->address()->find();
            $app = Db::name('applications')->where('status',1)->select();

            $country = Db::name('user')->alias('u')
                ->join('country c','u.birth_country=c.id')
                ->join('country c2','u.nationality=c2.id')
                ->where('u.id',$user_id)
                ->field('c.name,c.cname,c.name_fa,c.name_jp,c.name_kr,c.name_de,c.name_ru,
                c2.name as national,c2.cname as national_cn,c2.name_fa as national_fa,c2.name_jp as national_jp,
                c2.name_kr as national_kr,c2.name_de as national_de,c2.name_ru as national_ru')
                ->find();
            $major = Db::name('edu')->alias('e')
                ->join('cs_subject s','e.major=s.id')
                ->join('cs_schools s2','e.school=s2.id')
                ->where('e.u_id',$user_id)
                ->field('s.title_en,s.title,s.title_fa,s.title_ru,s.title_jp,s.title_kr
                    ,s2.name_en,s2.name_cn,s2.name_fa,s2.name_ru,s2.name_jp,s2.name_de,s2.name_kr'
                )
                ->find();

            $think_var = Cookie::get('think_var');

            if ($think_var == 'zh-cn'){
                if ($result['sex'] == 'Female'){
                    $result['sex'] = '女';
                }
                if ($result['sex'] == 'Male'){
                    $result['sex'] = '男';
                }
            }

            // 获取用户地址表中的地址数据
            $national = Db::name('user_address')->where("user_id",$user_id)->find();
            // 获取国家名称
            $add_coun = Db::name('cs_world_area_country')->where("code",$national['national'])->field('name')->find();
            $add_country = $add_coun['name'];
            // 获取省/市/州名称
            $add_state = Db::name('cs_world_area_state')->where("country_code",$national['national'])->field('code,name')->select();
            if (count($add_state)==0){
                // 代表此国家不存在行政区
                $address_names = $add_country;
            }else if (count($add_state)==1){
                // 代表此国家行政区划分与中国不同 无 省/市 例如:加拿大
                // 获取城市名称
                $add_city = Db::name('cs_world_area_city')->where("code",$national['city'])->field('name')->find();
                $add_city_name = $add_city['name'];
                // 获取 县/区 名称
                $add_county = Db::name('cs_world_area_region')->where("code",$national['county'])->field('name')->find();
                $add_county_name = $add_county['name'];
                if ($add_county_name != ''){
                    $address_names = $add_country.'--'.$add_city_name.'--'.$add_county_name;
                }else{
                    $address_names = $add_country.'--'.$add_city_name;
                }

            }else{
                // 代表此国家与中国行政区划分类似
                // 获取一级行政区名称
                $add_state_county = Db::name('cs_world_area_state')->where("code",$national['province'])->field('name')->find();
                $add_state_county_name = $add_state_county['name'];
                // 获取二级行政区名称
                $add_city = Db::name('cs_world_area_city')->where("code",$national['city'])->field('name')->find();
                $add_city_name = $add_city['name'];
                // 获取三级行政区名称
                $add_county = Db::name('cs_world_area_region')->where("code",$national['county'])->field('name')->find();
                $add_county_name = $add_county['name'];
                if ($add_county_name != ''){
                    // 代表此国家含有 县/区 例如:中国
                    $address_names = $add_country.'--'.$add_state_county_name.'--'.$add_city_name.'--'.$add_county_name;
                }else{
                    // 代表此国家不含有 县/区 例如:美国
                    $address_names = $add_country.'--'.$add_state_county_name.'--'.$add_city_name;
                }
            }

            $arr_mobile = explode('--',$result['mobile']);

            if ($arr_mobile[0]=='' || $arr_mobile[1]==''){
                $result['mobile'] = '---';
            }


            return $this->fetch('index',[
                'user'=>$result,'applications'=>$app,
                'edu'=>$edu,'address'=>$address,
                'upload'=>$upload,'country'=>$country,
                'passport'=>$passport,'major'=>$major,
                'credential'=>$credential,'address_names'=>$address_names,
                'national'=>$national
            ]);
        }else{
            $this->redirect('/home/user/log');
        }
    }
    /**
     * 登陆
     */
    public function log(){
        return $this->fetch('login');
    }
    public function login()
    {
        if($this->request->isPost()){
            $data = $this ->request->param();

            $validate_result = $this->validate($data,'Login');

            if($validate_result===true){
                if(!empty($data['rem'])){
                    // 代表勾选保持登陆状态
                    setcookie("useremail",$data['email'],time()+3600*24*7);
                    setcookie("userpwd",$data['password'],time()+3600*24*7);
                    unset($data['rem']);
                }
                $data['email'] = trim($data['email']);
                $data['password']=md5(trim($data['password'].'123'));

                $result=Db::name('user')
                    ->where('email',$data['email'])
                    ->where('password',$data['password'])
                    ->find();



                if($result){
                    Session::set('user_id',$result['id']);

                    if($result['status_info']==2||$result['status_info']==3||$result['status']==2){
                        $this->error('The account has been blocked!');
                    }
                    $login['last_login_time']=date('Y-m-d H:i:s');
                    $login['last_login_ip']=parent::get_ip();
                    //登录成功更新IP及时间
                    Db::name('user')->where('email',$data['email'])
                        ->where('password',$data['password'])
                        ->update($login);
                    $this->success('Login success','/home/user/index');
                }else{
                    $this->error('Login failed, the account password is incorrect');
                }
            }else{
                $this->error($validate_result);
            }
        }else{
            $this->redirect('log');
        }
    }

    public function change_pass(){
        $user_id = Session::get('user_id');


        if (empty($user_id)){
            $this->redirect('/home/user/log');
        }
        $result = Db::name('user')->where('id',$user_id)->find();
        return $this->fetch('change_pass',['user'=>$result]);
    }

    public function change_pwd(){
        $user_id = Session::get('user_id');
        if(empty($user_id)){
            $this->redirect('/home/user/log');
        }
        if($this->request->isPost()){
            $data = $this ->request->param();

            if ($data['pwd_new']!=$data['pwd_new2']){
                $this->error('Two new passwords do not match');
            }
            $result = Db::name('user')->where('id',$data['id'])
                ->where('password',md5(trim($data['pwd_old']).'123'))
                ->find();
            if(empty($result)){
                $this->error('Original password error');
            }
            $pwd = md5(trim($data['pwd_new']).'123');
            $res = Db::name('user')->where('id',$data['id'])->update(['password'=>$pwd]);
            if ($res){
                $this->success('Password updated successfully','index/user/log');
            }else{
                $this->error('Password updated error');
            }

        }
    }
    /**
     * 重置密码
     */
    public function forget_pass(){
        if($this->request->isPost()) {
            $email = input("post.email");
            $token = request()->session('__token__');
            if (empty($email)) {
                $this->error('邮箱不能为空');
            }
            if (!empty($email) && $token = $_POST['__token__']) {
                $result = Db::name('user')->where('email', $email)->find();
                if ($result) {
                    Session::set('email', $email);
                    if($result['status_info']==2||$result['status_info']==3||$result['status']==2){
                        $this->error('The account has been blocked!');
                    }
                    //邮件发送新密码
                    $pwd = parent::getrandstr();
                    $request = Request::instance();
                    $domain=$request->domain();
                    //存数据库
                    $password = md5($pwd);
                    Db::name('user')->where('email', $email)->update(['password'=>$password]);
                    parent::sentEmailreset($email,$pwd,$domain);
                    $this->success('success', '/home/user/forget_pass_info');
                } else {
                    $this->error('The email account is not yet in use');
                }
            }
        }
        return $this->fetch();
    }

    public function forget_pass_info(){
        return $this->fetch();
    }
    /**
     * 注册
     */
    public function add(){
        return $this->fetch('register');
    }

    // Ajax注册处理
    public function create(){
        if ($this->request->isPost()) {
            $data = $this->request->param();

            //验证
            $result = $this->validate($data,'User');
            if($result!==true){
                $this->error($result);
            }
            // 获取注册时间
            $data['create_time']=date('Y-m-d H:i:s');
            if(preg_match('/^[\x7f-\xff]+$/', $data['firstname'])&&preg_match('/^[\x7f-\xff]+$/', $data['lastname'])){
                $data['username']=$data['lastname'].','.$data['middlename'].','.$data['firstname'];
            }else{
                $data['username']=$data['firstname'].','.$data['middlename'].','.$data['lastname'];
            }

            $data['create_ip']=parent::get_ip();
            //验证完成后，销毁额外数据

            unset($data['__token__'],$data['firstname'],$data['middlename'],$data['lastname'],$data['check']);

            // 邮箱/姓名 数据库查询.
            $result = Db::name('user')->where('email', $data['email'])->whereOr('username',$data['username'])->find();
  
            // 判断此邮箱是否可以查询:有代表不可注册,无代表可以注册.
            if($result){
                $this->error('Failed to register, mailbox already occupied or username duplicate！');
            }else{
                $pwd = $data['password'];
                $data['password']=md5($pwd.'123');
                do{
                    $search_id ='WSE'.rand('100000','999999');
                    $search = Db::name('user')->where('search_id',$search_id)->find();
                     
                }while($search);
                $data['search_id'] = $search_id;

                // 注册用户
                $create = Db::name('user')->insert($data);
                // 获取注册成功后插入的数据ID
                $user_id =  Db::name('user')->getLastInsID();

                // 重组数据 $email_data
                $email_data=['user_id'=>$user_id,'email'=>$data['email']];
                $email = Db::name('user_email')->insert($email_data);
                $address = Db::name('user_address')->insert(['user_id'=>$user_id]);
                if(!$email){
                    $this->error('Email error！');
                }

                if ($address){
                    if ($create) {
                        Session::set('user_id', $user_id);
//                        ini_set('session.gc_maxlifetime',7200);//设置session的有效时间是7200秒
                        $request = Request::instance();
//                        $domain=$request->domain();
//                        parent::sentEmail($data['email'],$data['username'],$pwd,$domain);
                        $this->success('success','/home/user/register_edu');
                    } else {
                        $this->error('Failed to register, mailbox already occupied！');
                    }
                }else{
                    $this->error("Error");
                }
            }
        }
    }

    // 注册用户 第二页面
    public function register_edu(){
        $think_var = Cookie::get('think_var');
        $country_name = getCountryName($think_var);
        $sub_title = getSubTitle($think_var);
        $school_name = getNameLang($think_var);

        $user_id = Session::get('user_id');
        if(empty($user_id)){
            $this->redirect('log');
        }
        // 获取排序为0的课程项目
        $subject = Db::name('cs_subject')->where('sort',0)->field("id,$sub_title")->select();
        $think_var = Cookie::get('think_var');
        foreach ($subject as $k=>$v){
            if ($think_var == 'zh-cn'){
                $subject[$k]['titlt'] = $subject[$k]['title'];
            } else if($think_var == 'en-us'){
                $subject[$k]['title'] = $subject[$k]['title_en'];
                unset($subject[$k]['title_en']);
            }else if($think_var == 'ja-jp'){
                $subject[$k]['title'] = $subject[$k]['title_jp'];
                unset($subject[$k]['title_jp']);
            }else if($think_var == 'de-de') {
                $subject[$k]['title'] = $subject[$k]['title_de'];
                unset($subject[$k]['title_de']);
            }else if($think_var == 'ko-kr') {
                $subject[$k]['title'] = $subject[$k]['title_kr'];
                unset($subject[$k]['title_kr']);
            }else if($think_var == 'ru-ru'){
                $subject[$k]['title'] = $subject[$k]['title_ru'];
                unset($subject[$k]['title_ru']);
            }else if($think_var == 'fr-fa'){
                $subject[$k]['title'] = $subject[$k]['title_fa'];
                unset($subject[$k]['title_fa']);
            } else {
                $subject[$k]['title'] = $subject[$k]['title_en'];
                unset($subject[$k]['title_en']);
            }
        }
        // 获取加拿大国家的大学
        $schools = Db::name('cs_schools')->where('gj','加拿大')->order('name_en','asc')->field("id,$school_name")->select();
        foreach ($schools as $k=>$v){
            $schools_new[$k]['id'] = $schools[$k]['id'];
            $schools_new[$k]['name'] = $schools[$k][$school_name];
        }

        $country = Db::name('country')->order('id','asc')->field('id,'.$country_name.' as name')->select();
        if ($this->request->isPost()){
            $data = $this->request->param();
            $sort = $data['faculty'];
            $result2 = Db::name('cs_subject')->where('sort',$sort)->field('id,'.$sub_title.' as title')->select();
            $opt = '<option>Choose...</option>';
            foreach($result2 as $key){
                $opt .= "<option value=".$key['id'].">".$key[$sub_title]."</option>";
            }
            echo json_encode($opt);
        }else{
            return $this->fetch('register_edu',[
                'schools'=>$schools_new,'subject'=>$subject,
                'country'=>$country
            ]);
        }
    }

    // 获取科学领域下的共同领域项目
    public function getSubject(){
        $think_var = Cookie::get('think_var');
        $sub_title = getSubTitle($think_var);

        $user_id = Session::get('user_id');
        if(empty($user_id)){
            $this->redirect('log');
        }

        if ($this->request->isPost()){
            $data = $this->request->param();
            $sort = $data['faculty'];
            $result2 = Db::name('cs_subject')->where('sort',$sort)->field("id,$sub_title")->select();
            foreach ($result2 as $k=>$v){
                $result2_new[$k]['id'] = $result2[$k]['id'];
                $result2_new[$k]['title'] = $result2[$k][$sub_title];
            }
            return json_encode($result2_new);
        }
    }

    // 注册表第二页数据,数据储存进数据库
    public function create_extra(){
        // 获取用户登陆后储存的sessionID
        $user_id = Session::get('user_id');
        // 判断用户ID是否存在,进行下一步操作
        if(empty($user_id)){
            // 无用户ID,跳转登陆页面
            $this->redirect('log');
        }
        if ($this->request->isPost()) {
            // 获取用户表单所填信息ID
            $data = $this->request->param();
            // 用户表(user)需要数据
            $up_user['nationality'] = $data['nationality'];
            // 教育表(educa)需要数据
            $up_educa['faculty'] = $data['faculty'];
            $up_educa['major'] = $data['major'];
            $up_educa['school'] = $data['school'];
            $up_educa['user_id'] = $user_id;

            // 更新数据库内国籍和现居住国家信息
            $result_user = Db::name('user')->where('id',$user_id)->update($up_user);
            if ($result_user){
                // 获取教育表内的数据 进行判断 防止恶意重复提交
                $educa = Db::name('educa')->where("user_id",$user_id)->find();
                if ($educa){
                    // 代表教育表已经存在了数据 只需要修改
                    $up_educa['sort'] = $educa['sort']+1;
                    $result_educa = Db::name('educa')->where("user_id",$user_id)->update($up_educa);
                }else{
                    // 代表教育表未存在数据 需要插入
                    $up_educa['user_id'] = $user_id;
                    $result_educa = Db::name('educa')->insert($up_educa);
                }

                if ($result_educa){
                    $this->success('Edu save success','profile');
                }else{
                    $this->error('Edu save error');
                }
            }else{
                $this->error('Edu save error');
            }

        }
    }

    // 注册第三张表单,全部信息表单
    public function profile(){
        // 进行网站国家语言类型判断
        $think_var = Cookie::get('think_var');
        $sub_title = getSubTitle($think_var);
        $school_name = getNameLang($think_var);
        $country_name = getCountryName($think_var);

        // 获取sessionID
        $user_id = Session::get('user_id');

        // 获取收货地址中所有国家
        $country_arr = Db::name('cs_world_area_country')->field('code,name')->select();

        if(!empty($user_id)) {
            // id sessionID联合查询用户数据.获取全部信息
            $result = Db::name('user')->where('id', $user_id)->find();

            // 格式化当前时间,获取2019-10-1类型格式时间  (注:后续可能调整时间戳格式,暂时保留)
            $date = date('Y-m-d');

            // 扩散当前时间
            $this->assign('date',$date);

            // 根据ID升序排列,获取城市信息(ID 城市名称)
            $country = Db::name('country')->order('id','asc')->field("id,$country_name")->select();
            foreach ($country as $k=>$v){
                $country_new[$k]['id'] = $country[$k]['id'];
                $country_new[$k]['name'] = $country[$k][$country_name];
            }

            // 获取加拿大学校信息,根据ID升序
            $schools = Db::name('cs_schools')->where('gj','加拿大')->order('id','asc')->field("id,$school_name")->select();

            // 获取sort为0的科目信息
            $subject = Db::name('cs_subject')->where('sort',0)->field("id,$sub_title")->select();
            // 获取共同研究的科目信息
            $major = Db::name('educa')->where("user_id",$user_id)->field('faculty')->find();

            // 获取专业课程数组
            $subject2 = Db::name('cs_subject')->where("sort",$major['faculty'])->field("id,$sub_title")->select();

            if (!empty($subject2)){
                foreach ($subject2 as $k=>$v){
                    $subject2_new[$k]['id'] = $subject2[$k]['id'];
                    $subject2_new[$k]['title'] = $subject2[$k][$sub_title];
                }
            }else{
                $subject2_new = '';
            }



            // 获取登录用户的教育信息
            $edu = Db::name('edu')->where('u_id',$user_id)->find();

            // 获取登录用户提交的资料
            $upload = Db::name('user_upload')->where('uid',$user_id)->find();

            // 获取用户相关的居住地址信息
            $address = Db::name("user_address")->where("user_id",$user_id)->find();

            // 获取居住国家一级行政区的信息
            $add_province = Db::name('cs_world_area_state')->where("country_code",$address['national'])->field('code,name')->select();

            // 获取国家二级行政区信息
            $add_city = Db::name('cs_world_area_city')->where("state_code",$address['province'])->field('code,name')->select();

            // 获取国家三级行政区信息
            if ($address['city']){
                $add_county = Db::name('cs_world_area_region')->where("city_code",$address['city'])->field('code,name')->select();
            }else{
                $add_county = '';
            }


            // 获取登录用户的邮箱信息
            $email = Db::name('user_email')->where('user_id',$user_id)->select();

            // 获取登录用户的电话信息
            $phone = Db::name('user_phone')->where('user_id',$user_id)->select();

            // 获取登录用户的passport信息
            $passport = Db::name('user_passport')->where('user_id',$user_id)->find();

            // 获取登录用户的....
            $credential = Db::name('user_credential')->where('user_id',$user_id)->select();

            // 获取status为1的申请
            $app = Db::name('applications')->where('status',1)->select();

            // 获取用户相关联的签证资料 think_qianzheng
            $qianzheng = Db::name('qianzheng')->where("user_id",$user_id)->find();
            // 获取用户相关联的教育背景资料 think_educa
            $educa = Db::name('educa')->where("user_id",$user_id)->find();

            if ($educa['faculty']){
                // 代表选择了研究领域信息
                $major = Db::name('cs_subject')->where("sort",$educa['faculty'])->select();
            }else{
                // 代表未选择研究领域信息
                $major = '';
            }



            // 切割 学位证书/成绩单 信息
            if ($educa['diploma_img'] != ''){
                $educa['diploma_img'] = explode(',',$educa['diploma_img']);
            }

            if ($educa['trans_img'] != ''){
                $educa['trans_img'] = explode(',',$educa['trans_img']);
            }



            //拆分用户名
            if ($this->request->isPost()){
                $data = $this->request->param();
                $sort = $data['faculty'];
                $result2 = Db::name('cs_subject')->where('sort',$sort)->field("id,$sub_title")->select();
                return json_encode($result2);
            }

            $username = explode(",",$result['username']);

            if(preg_match('/^[\x7f-\xff]+$/', $result['username'])){
                $result['lastname'] = $username[0];
                $result['middlename'] = $username[1];
                $result['firstname'] = $username[2];
            }else{
                $result['firstname'] = $username[0];
                $result['middlename'] = $username[1];
                $result['lastname'] = $username[2];
            }

            $user_order2 = Db::name('user_order')->where('status',1)->where('user_id',$user_id)->find();
            if($user_order2&&$result['status_info']==1){
                $or_input = 1;
                $this->assign('or_input',$or_input);
            }else{
                $or_input=0;
                $this->assign('or_input',$or_input);
            }

            $result['why_tc'] = explode(",",$result['why_tc']);
            $mobile = $result['mobile'];
            if (!empty($mobile)){
                $arr = explode('--',$mobile);
                if (count($arr)<2){
                    // 代表区间 电话号码缺一
                    $result['qujian'] = '';
                    $result['mobile'] = '';
                }else{
                    $result['qujian'] = $arr[0];
                    $result['mobile'] = $arr[1];
                }
            }else{
                $result['qujian'] = '';
                $result['mobile'] = '';
            }

            foreach ($schools as $k=>$v){
                $educa_school[$k]['id'] = $schools[$k]['id'];
                $educa_school[$k]['name'] = $schools[$k][$school_name];
            }

            foreach ($subject as $k=>$v){
                $subject_new[$k]['id'] = $subject[$k]['id'];
                $subject_new[$k]['title'] = $subject[$k][$sub_title];
            }

            // 获取电话区间及关联国家ID
            $mobile_arr = Db::name('mobile')->select();




            return $this->fetch('profile', [
                'user' => $result,'country_arr'=>$country_arr,'country'=>$country_new,'applications'=>$app,
                'address'=>$address,'schools'=>$educa_school,'add_province'=>$add_province,'add_city'=>$add_city,
                'add_county'=>$add_county, 'email'=>$email,'credential'=>$credential,'subject'=>$subject_new,
                'subject2'=>$subject2_new, 'phone'=>$phone,'passport'=>$passport,'edu'=>$edu,'upload'=>$upload,
                'qianzheng'=>$qianzheng,'educa'=>$educa,'major'=>$major,'mobile_arr'=>$mobile_arr,'uid'=>$user_id,'diploma_img'=>$educa['diploma_img'],
                'trans_img'=>$educa['trans_img']
            ]);
        }
        $this->redirect('index/user/log');
    }

    // Ajax 获取研究领域对应的课程项目
    public function getProfile(){
        // 判断用户是否登陆
        $user_id = Session::get('user_id');
        if(empty($user_id)){
            $this->redirect('/home/user/log');
        }
        // post提交表单,获取post表单数据
        if ($this->request->isPost()){
            $data = $this->request->param();
            $think_var = Cookie::get('think_var');
            $sub_title = getSubTitle($think_var);
            $profile = Db::name('cs_subject')->where("sort",$data['faculty'])->field("id,$sub_title")->select();

            foreach ($profile as $k=>$v){
                $profile_new[$k]['id'] = $profile[$k]['id'];
                $profile_new[$k]['title'] = $profile[$k][$sub_title];
            }

            return $profile_new;
        }
    }

    // Ajax 保存用户出生信息
    public function modify_save(){
        // 判断用户是否登陆
        $user_id = Session::get('user_id');
        if(empty($user_id)){
            $this->redirect('/home/user/log');
        }
        // post提交表单,获取post表单数据
        if ($this->request->isPost()){
            $data = $this->request->param();
            // 重整用户名信息
            $data['username'] = trim($data['first_name']).','.trim($data['moddle_name']).','.trim($data['last_name']);
            //销毁多余信息
            unset($data['first_name'],$data['moddle_name'],$data['last_name']);
            $bool = Db::name('user')->where("id",$user_id)->field('username,sort')->find();
            $data['sort'] = $bool['sort']+1;
            // 获取更新时间
            $data['create_time'] = date("Y-m-d H:i:s");
            // 获取更新IP
            $data['create_ip'] = parent::get_ip();
            if ($bool['username']!=$data['username']){
                // 代表用户名更改 需要验证
                $is_bool = Db::name('user')->where("username",$data['username'])->find();
                if ($is_bool){
                    // 代表用户名存在 需要进行修改
                    $this->error('Username already exists');
                }else{
                    // 代表用户名不存在,可以进行用户名修改
                    //更新用户信息
                    $result_user = Db::name('user')->where('id',$user_id)->update($data);
                    if ($result_user){
                        // 更新成功
                        $this->success('Infomation save success');
                    }else{
                        // 更新失败
                        $this->error('Infomation save error');
                    }
                }
            }else{
                // 代表用户名未更改 无需验证
                //更新用户信息
                $result_user = Db::name('user')->where('id',$user_id)->strict(false)->update($data);
                if ($result_user){
                    $this->success('Infomation save success');
                }else{
                    $this->error('Infomation save error');
                }
            }
        }
    }

    // Ajax 保存用户收货地址
    public function address_save(){
        $user_id = Session::get('user_id');
        // 判断用户ID是否存在
        if ($user_id){
            // 进行POST判断
            if ($this->request->isPost()) {
                // 获取数据data
                $data = $this->request->param();
                // 拼接用户电话信息
                $user_data['mobile'] = trim($data['qujian']).'--'.trim($data['mobile']);
                unset($data['qujian'],$data['mobile']);
                $bool = Db::name('user')->where("id",$user_id)->field('sort')->find();
                $user_data['sort'] = $bool['sort']+1;

                // 获取更新时间
                $user_data['create_time'] = $data['create_time'] = date("Y-m-d H:i:s");
                // 获取更新IP
                $user_data['create_ip'] = $data['create_ip'] = parent::get_ip();
                $result_user = Db::name('user')->where("id",$user_id)->update($user_data);
                $address = Db::name('user_address')->where("user_id",$user_id)->find();

                if ($address){
                    $data['sort'] = $address['sort']+1;
                    // 代表更新用户收货信息
                    $result_address = Db::name('user_address')->where("user_id",$user_id)->update($data);
                }else{
                    // 代表添加用户收货信息
                    $data['user_id'] = $user_id;
                    $result_address = Db::name('user_address')->insert($data);
                }

                if ($result_user && $result_address){
                    // 更新成功
                    $msg = 1;
                }else{
                    $msg = 0;
                }
                return $msg;
            }
        }
    }

    // Ajax 保存签证信息
    public function student_save(){
        // 判断用户是否登陆
        $user_id = Session::get('user_id');
        if(empty($user_id)){
            $this->redirect('/home/user/log');
        }
        // post提交表单,获取post表单数据
        if ($this->request->isPost()){
            $data = $this->request->param();
            // 更新用户表中why_tc信息
            $user_arr = Db::name('user')->where('id',$user_id)->field('sort')->find();
            $user_data['sort'] = $user_arr['sort']+1;
            $user_data['why_tc'] = $data['arr'][0].','.$data['arr'][1].','.$data['arr'][2];
            unset($data['arr']);
            $result_user = Db::name('user')->where('id',$user_id)->update($user_data);

            if ($result_user){
                // 获取qianzheng表内当前用户数据
                $qianzheng = Db::name('qianzheng')->where("user_id",$user_id)->find();
                if ($qianzheng){
                    // 代表签证表内有数据 更新信息
                    // 获取更新时间和更新IP
                    $data['create_time'] = date('Y-m-d H:i:s');
                    $data['create_ip'] = parent::get_ip();
                    $data['sort'] = $qianzheng['sort']+1;
                    $result_qianzheng = Db::name('qianzheng')->where("user_id",$user_id)->update($data);
                }else{
                    // 代表签证表内无数据 插入数据
                    // 获取插入时间和插入IP
                    $data['insert_time'] = date('Y-m-d H:i:s');
                    $data['insert_ip'] = parent::get_ip();
                    $data['user_id'] = $user_id;
                    $result_qianzheng = Db::name('qianzheng')->insert($data);
                }

                // 进行数据操作后判别 获取反馈信息
                if ($result_qianzheng){
                    // 更新成功
                    $this->success('Infomation save success');
                }else{
                    // 更新失败
                    $this->error('Infomation save error');
                }
            }else{
                // 更新失败
                $this->error('Infomation save error');
            }
        }
    }

    // Ajax 保存教育信息
    public function educa_save(){
        // 判断用户是否登陆
        $user_id = Session::get('user_id');
        if(empty($user_id)){
            $this->redirect('/home/user/log');
        }
        // post提交表单,获取post表单数据
        if ($this->request->isPost()){
            $data = $this->request->param();
            if (isset($data['diploma_arr'])){
                $data['diploma_img'] = trim(implode(',',$data['diploma_arr']));
            }

            if (isset($data['transcript_arr'])){
                $data['trans_img'] = trim(implode(',',$data['transcript_arr']));
            }

            unset($data['diploma_arr'],$data['transcript_arr']);

            // 进行教育数据表educa搜索用户教育信息是否存在
            $educa = Db::name('educa')->where("user_id",$user_id)->find();
            if ($educa){
                // 代表用户教育信息存在 更新数据
                $data['update_time'] = date("Y-m-d H:i:s");
                $data['update_ip'] = parent::get_ip();
                $data['sort'] = $educa['sort']+1;
                $result_educa = Db::name('educa')->where("user_id",$user_id)->update($data);
            }else{
                // 代表用户教育信息不存在 插入一条新的数据
                $data['insert_time'] = date("Y-m-d H:i:s");
                $data['insert_ip'] = parent::get_ip();
                $data['user_id'] = $user_id;
                $result_educa = Db::name('educa')->insert($data);
            }

            if ($result_educa){
                // 更新成功
                $this->success('Infomation save success');
            }else{
                // 更新失败
                $this->error('Infomation save error');
            }
        }
    }

    // 删除教育背景资料中(证件照片/身份证)的图片
    public function del_img(){
        // 判断用户是否登陆
        $user_id = Session::get('user_id');
        if(empty($user_id)){
            $this->redirect('/home/user/log');
        }
        // post提交表单,获取post表单数据
        if ($this->request->isPost()){
            $data = $this->request->param();
            if($data['id'] == 'portrait_del'){
                // 代表删除功能为证件照片删除
                $educa_arr['port_img'] = '';
                $result_educa = Db::name('educa')->where("user_id",$user_id)->update($educa_arr);
            }elseif ($data['id'] == 'id_del'){
                // 代表删除功能为身份证照片删除
                $educa_arr['id_img'] = '';
                $result_educa = Db::name('educa')->where("user_id",$user_id)->update($educa_arr);
            }

            if ($result_educa){
                // 更新成功
                $this->success('Infomation save success');
            }else{
                // 更新失败
                $this->error('Infomation save error');
            }
        }
    }

    // 删除教育背景资料中的(学位证书/成绩单)的图片
    public function diploma_del(){
        // 判断用户是否登陆
        $user_id = Session::get('user_id');
        if(empty($user_id)){
            $this->redirect('/home/user/log');
        }
        // post提交表单,获取post表单数据
        if ($this->request->isPost()){
            $data = $this->request->param();
            // 获取教育表(think_educa)内的信息
            $think_educa = Db::name('educa')->where("user_id",$user_id)->field('diploma_img,trans_img')->find();
            $diploma_img = explode(',',$think_educa['diploma_img']);
            $trans_img = explode(',',$think_educa['trans_img']);

            if ($data['id'] == 'diploma_del'){
                // 代表点击的是教育背景资料中的学位证书删除功能
                // 同时删除图片
                $path = $_SERVER['DOCUMENT_ROOT'].$diploma_img[$data['number']];
                if (is_file($_SERVER['DOCUMENT_ROOT'].$diploma_img[$data['number']])){
                    // 判断文件是否存在
                    unlink($path);
                }
                unset($diploma_img[$data['number']]);
                $educa_arr['diploma_img'] = implode(',',$diploma_img);
            }elseif($data['id'] == 'trans_del'){
                // 代表点击的是教育背景资料中的成绩单删除功能
                // 同时删除文件夹下图片
                $path = $_SERVER['DOCUMENT_ROOT'].$trans_img[$data['number']];
                if (is_file($_SERVER['DOCUMENT_ROOT'].$trans_img[$data['number']])){
                    // 判断文件是否存在
                    unlink($path);
                }
                unset($trans_img[$data['number']]);
                $educa_arr['trans_img'] = implode(',',$trans_img);
            }

            $result_educa = Db::name('educa')->where("user_id",$user_id)->update($educa_arr);

            if ($result_educa){
                // 更新成功
                $this->success('Infomation save success');
            }else{
                // 更新失败
                $this->error('Infomation save error');
            }
        }
    }

    // Ajax 各个国家三级联动 获取一级行政区
    public function getProvince(){
        $user_id = Session::get('user_id');
        // 用户ID判断
        if ($user_id){
            if ($this->request->isPost()){
                $data = $this->request->param();
                $country_code = $data['national'];
                // 进行所属国家查询
                $province = Db::name('cs_world_area_state')->where("country_code",$country_code)->field("code,name")->select();
                // 进行$province数组置换
                if (count($province)!=1){
                    // 代表国家中含有省/市/州

                } else{
                    // 代表国家内不含有省/市/州
                    $state_code = $province[0]['code'];
                    $city = Db::name("cs_world_area_city")->where("state_code",$state_code)->field("code,name")->select();
                    $province[0] = [$province,$city];
                }
                return json_encode($province);
            }
        }
    }

    // Ajax 省市县三级联动 获取二级行政区域
    public function getCity(){
        $user_id = Session::get('user_id');
        // 用户ID判断
        if ($user_id){
            if ($this->request->isPost()){
                $data = $this->request->param();
                $state_code = $data['province'];
                $city = Db::name("cs_world_area_city")->where("state_code",$state_code)->field("code,name")->select();
                return json_encode($city);
            }
        }
    }

    // Ajax 省市县三级联动 获取三级行政区
    public function getCounty(){
        $user_id = Session::get('user_id');
        // 用户ID判断
        if ($user_id){
            if ($this->request->isPost()){
                $data = $this->request->param();
                $city_code = $data['city'];
                $county = Db::name("cs_world_area_region")->where("city_code",$city_code)->field("code,name")->select();
                return json_encode($county);
            }
        }
    }

    // Ajax 修改用户地址
    public function getAddress(){
        $user_id = Session::get('user_id');
        // 判断用户ID是否存在
        if ($user_id){
            // 进行POST判断
            if ($this->request->isPost()){
                // 获取数据data
                $data = $this->request->param();
                // 获取更新时间
                $data['create_time'] = date("Y-m-d H:i:s");
                // 获取更新IP
                $data['create_ip'] = parent::get_ip();
                $resurt = Db::name('user_address')->where("user_id",$user_id)->update($data);
                if ($resurt){
                    $msg = 1;
                }else{
                    $msg = 2;
                }
                // 返回值JSON
                return json_encode($msg);
            }
        }
    }

    // 添加邮箱
    public function add_email(){
        $user_id = Session::get('user_id');
        if(empty($user_id)){
            $this->redirect('/home/user/log');
        }
        if ($this->request->isPost()) {
            $data = $this->request->param();
            $data['create_time'] = date('Y-m-d H:i:s');
            $data['create_ip'] = parent::get_ip();
            $find = Db::name('user_email')->where('user_id',$user_id)->count();
            $find2 = Db::name('user_email')->where('user_id',$user_id)->where('email',$data['email'])->find();
            if($find>=3||!empty($find2)){
                $this->error('This Email has been added, or the number of Email cannot exceed 3');
            }
            $result = Db::name('user_email')->insert($data);
            if ($result){
                $this->success('Add Email Success');
            }else{
                $this->error('Add Email Error');
            }
        }
    }
    public function del_email(){
        $e_id = input('get.id');

        $user_id = Session::get('user_id');
        if(empty($user_id)){
            $this->redirect('/home/user/log');
        }
        $email =Db::name('user_email')->where('id',$e_id)->find();
        $user = Db::name('user')->where('id',$user_id)->find();

        if ($email['email']==$user['email']){
            $this->error('The default Email cannot be deleted');
        }
        $result = Db::name('user_email')->where('id',$e_id)->delete();

        //提示信息，可删除
        if ($result){
            $this->success('Delete Email Success');
        }else{
            $this->error('Delete Email Error');
        }
    }
    public function add_phone(){
        $user_id = Session::get('user_id');
        if(empty($user_id)){
            $this->redirect('/home/user/log');
        }
        if ($this->request->isPost()) {
            $data = $this->request->param();
            $data['create_time'] = date('Y-m-d H:i:s');
            $data['create_ip'] = parent::get_ip();
            $find = Db::name('user_phone')->where('user_id',$user_id)->count();
            $find2 = Db::name('user_phone')->where('user_id',$user_id)->where('phone',$data['phone'])->find();
            if($find>=3||!empty($find2)){
                $this->error('This Phone has been added, or the number of Phone cannot exceed 3');
            }
            $result = Db::name('user_phone')->insert($data);
            if ($result){
                $this->success('Add Phone Success');
            }else{
                $this->error('Add Phone Error');
            }
        }
    }

    function upload(){
        parent::upload();
    }

    public function del_phone(){
        $e_id = input('get.id');
        $user_id = Session::get('user_id');
        if(empty($user_id)){
            $this->redirect('/home/user/log');
        }
        $phone =Db::name('user_phone')->where('id',$e_id)->find();
        $user = Db::name('user')->where('id',$user_id)->find();
        if ($phone['phone']==$user['mobile']){
            $this->error('The default phone number cannot be deleted');
        }
        $result = Db::name('user_phone')->where('id',$e_id)->delete();
        //提示信息，可删除
        if ($result){
            $this->success('Delete Phone success');
        }else{
            $this->error('Delete Phone error');
        }
    }

    public function add_address(){
        $user_id = Session::get('user_id');
        if(empty($user_id)){
            $this->redirect('/home/user/log');
        }
        if ($this->request->isPost()) {
            $data = $this->request->param();
            $data['create_time'] = date('Y-m-d H:i:s');
            $data['create_ip'] = parent::get_ip();
            $find = Db::name('user_address')->where('user_id',$user_id)->count();
            $find2 = Db::name('user_address')->where('user_id',$user_id)->where('address_name',$data['address_name'])->find();
            if($find>=3||!empty($find2)){
                $this->error('This Address has been added, or the number of Address cannot exceed 3');
            }
            $result = Db::name('user_address')->insert($data);
            if ($result){
                $this->success('Add Address success');
            }else{
                $this->error('Add Address error');
            }
        }
    }
    public function del_address(){
        $e_id = input('get.id');

        $user_id = Session::get('user_id');
        if(empty($user_id)){
            $this->redirect('/home/user/log');
        }
        $result = Db::name('user_address')->where('id',$e_id)->delete();

        //提示信息，可删除
        if ($result){
            $this->success('Delete Address success');
        }else{
            $this->error('Delete Address error');
        }
    }

    public function add_credent(){
        $user_id = Session::get('user_id');
        if(empty($user_id)){
            $this->redirect('/home/user/log');
        }
        $data['user_id'] = input('post.user_id');
        $data['credential'] = input('post.credential');
        $data['create_time']=date('Y-m-d H:i:s');

        $result = Db::name('user_credential')->insert($data);
        if ($result){
            $this->success('Delete email success');
        }else{
            $this->error('Delete email error');
        }
    }
    public function del_credent(){
        $id = input('get.id');
        $result = Db::name('user_credential')->where('id',$id)->delete();
        if ($result){
            $this->success('Delete email success');
        }else{
            $this->error('Delete email error');
        }
    }
    /**
     * 应用申请
     * */
    //可申请的服务
    public function applications_lists(){
        $user_id = Session::get('user_id');
        if(empty($user_id)){
            $this->redirect('/home/user/log');
        }
        $user = UserModel::get($user_id);
        $app_order = Db::name('user_order')->where('user_id',$user_id)->select();
        $app = Db::name('applications')->where('status',1)->select();

        return $this->fetch('applications_lists',['user'=>$user,'app_order'=>$app_order,'applications'=>$app]);
    }
    //所有申请的服务
    public function applications_all(){
        $user_id = Session::get('user_id');
        if(empty($user_id)){
            $this->redirect('/home/user/log');
        }
        $user = UserModel::get($user_id);
        $app_order = Db::name('user_order')->alias('o')
            ->join('applications a','o.app_id=a.id')
            ->where('o.user_id',$user_id)
            ->where('o.status',0)
            ->field('o.*,a.app_name,a.name_en,a.name_jp,a.name_ru,a.name_kr,a.name_fa,a.name_de')
            ->select();
        $app = Db::name('applications')->where('status',1)->select();
        foreach ($app_order as $k=>$v){
            $app_step = Db::name('app_step')
                ->where('app_id',$v['app_id'])
                ->where('status',0)
                ->order('sort','asc')
                ->select();
            $app_order[$k]['step'] = $app_step;
        }
        return $this->fetch('applications_all',[
            'user'=>$user,'app_order'=>$app_order,'applications'=>$app
        ]);
    }

    public function applications_lists_info(){
        $fee_id = input('get.id');
        $app = Db::name('applications')->where('status',1)->select();
        $result = Db::name('applications')->where('id',$fee_id)->find();
        return $this->fetch('applications_lists_info',['fee'=>$result,'applications'=>$app]);
    }
    //服务步骤及详情，现在还没有步骤状态判断
    public function applications_info($id){
        $user_id = Session::get('user_id');
        if(empty($user_id)){
            $this->redirect('/home/user/log');
        }
        if(empty($id)){
            $this->redirect('applications_lists');
        }
        $user = UserModel::get($user_id);
        $app = Db::name('applications')->where('status',1)->select();
        $upload = Db::name('user_upload')->where('uid',$user_id)->find();
        $user_order = Db::name('user_order')->where('app_id',$id)->where('user_id',$user_id)->find();
        //dump($user_order);die;
        $this->assign('step_ok',explode(',',$user_order['step_ok']));
        $result = AppModel::get($id);
        $app_step = new AppStep();
        $step = $app_step->where('app_id',$id)->where('status',1)->order('sort','asc')->select();
        $admin_step = $app_step->where('app_id',$id)->where('status',0)->order('sort','asc')->select();
        return $this->fetch('applications_info',[
            'fee'=>$result,'applications'=>$app
            ,'user'=>$user,'app_step'=>$step
            ,'admin_step'=>$admin_step,'upload'=>$upload
            ,'order'=>$user_order
        ]);
    }
    public function app_step($app_id,$sort){
        $user_id = Session::get('user_id');
        if(empty($user_id)){
            $this->redirect('log');
        }
        $this->assign('sort',$sort);
        $user = UserModel::get($user_id);
        $result = AppModel::get($app_id);
        $app = Db::name('applications')->where('status',1)->select();
        $user_order = Db::name('user_order')->where('app_id',$app_id)->where('user_id',$user_id)->find();
        if(empty($user_order)){
            $data=[
                'app_id'=>$app_id,
                'user_id'=>$user_id,
                'order_number'=>'E'.$result['short_name'].rand('100000','999999').time(),    //订单编号
                'app_name'=>$result['app_name'],
                'name_en'=>$result['name_en'],
                'fee'=>$result['fee'],
                'create_time'=>date('Y-m-d H:i:s'),
                'create_ip'=>parent::get_ip()
            ];
            //dump($data);die;
            Db::name('user_order')->insert($data);
        }
        $upload = Db::name('user_upload')->where('uid',$user_id)->find();
        $app_step = new AppStep();
        $step = $app_step->where('app_id',$app_id)->where('status',1)->order('sort','asc')->select();
        return $this->fetch('app_step',[
            'fee'=>$result
            ,'user'=>$user,'app_step'=>$step
            ,'upload'=>$upload
            ,'order'=>$user_order
        ]);
    }
//操作订单步骤，展示步骤详情
    public function app_step_info($app_id,$sort){
        $user_id = Session::get('user_id');
        if(empty($user_id)){
            $this->redirect('log');
        }
        $this->assign('sort',$sort);
        $user = UserModel::get($user_id);
        $result = AppModel::get($app_id);
        $upload = Db::name('user_upload')->where('uid',$user_id)->find();

        if(!empty($upload)){
            $upload['sfz_img']=unserialize($upload['sfz_img']);
            $upload['xwz_img']=unserialize($upload['xwz_img']);
            $upload['sc_xwz_img']=unserialize($upload['sc_xwz_img']);
            $upload['sm_img']=unserialize($upload['sm_img']);
            $upload['hz_img']=unserialize($upload['hz_img']);
            $upload['sc_hz_img']=unserialize($upload['sc_hz_img']);
            $upload['crj_img']=unserialize($upload['crj_img']);
            $upload['cj_img']=unserialize($upload['cj_img']);
            $upload['sc_cj_img']=unserialize($upload['sc_cj_img']);
            $upload['miss_img']=unserialize($upload['miss_img']);
        }

        $user_order = Db::name('user_order')->alias('o')
            ->join('applications a','o.app_id=a.id')
            ->where('o.user_id',$user_id)
            ->where('o.app_id',$app_id)
            ->where('o.status',0)
            ->field('o.*,a.app_name,a.name_en,a.name_jp,a.name_ru,a.name_kr,a.name_fa,a.name_de')
            ->find();
        $user_order2 = Db::name('user_order')->where('status',1)->where('user_id',$user_id)->find();
        $user_order3 = Db::name('user_order')->where('app_id',$app_id)->where('status',1)->where('user_id',$user_id)->find();
        if($user_order2&&$user['status_info']==1){
            $or_input = 1;
            $this->assign('or_input',$or_input);
        }else{
            $or_input=0;
            $this->assign('or_input',$or_input);
        }
        if($user_order3){
            $or_input = 1;
            $this->assign('or_input3',$or_input);
        }else{
            $or_input=0;
            $this->assign('or_input3',$or_input);
        }
        if(!empty($user_order['other_third'])){
            $user_order['other_third']=unserialize($user_order['other_third']);
        }
        //dump($user_order);die;
        $this->assign('step_ok',explode(',',$user_order['step_ok']));
        $app_step = new AppStep();
        $step = $app_step->where('app_id',$app_id)
            ->where('status',1)
            ->order('sort','asc')
            ->select();
        //订单为空时，添加订单数据
        if(empty($user_order)){
            $data=[
                'app_id'=>$app_id,
                'user_id'=>$user_id,
                'order_number'=>'E'.$result['short_name'].rand('100000','999999').time(),    //订单编号
                'app_name'=>$result['app_name'],
                'name_en'=>$result['name_en'],
                'fee'=>$result['fee'],
                'create_time'=>date('Y-m-d H:i:s'),
                'create_ip'=>parent::get_ip()
            ];
            Db::name('user_order')->insert($data);
        }
        $date = date('Y-m-d');
        $this->assign('date',$date);
        $sort_str = $app_step->where('app_id',$app_id)->where('sort',$sort) ->where('status',1)->find();
        //其他信息
        $country = Db::name('country')->order('id','asc')->field('id,name')->select();
        $schools = Db::name('cs_schools')
            ->where('gj','加拿大')
            ->order('id','asc')
            ->select();
        $passport = Db::name('user_passport')->where('user_id',$user_id)->find();
        $address_default = $user->address()->where('id',$user['address'])->find();
        $subject = Db::name('cs_subject')->select();
        $edu = Db::name('edu')->alias('e')
            ->where('e.u_id',$user_id)
            ->join('cs_subject s','e.major=s.id')
            ->field('e.*,s.title_en')
            ->find();
        if(!empty($edu['cj_img'])){
            $edu['cj_img']=unserialize($edu['cj_img']);
        }
        if(!empty($edu['credential'])){
            $edu['credential']=unserialize($edu['credential']);
        }
        $app = Db::name('applications')->where('status',1)->select();
        $username = explode(" ",$user['username']);
        if(preg_match('/^[\x7f-\xff]+$/', $user['username'])){
            $user['lastname'] = $username[0];
            $user['middlename'] = $username[1];
            $user['firstname'] = $username[2];
        }else{
            $user['firstname'] = $username[0];
            $user['middlename'] = $username[1];
            $user['lastname'] = $username[2];
        }
        return $this->fetch($sort_str['step_url'],[
            'fee'=>$result,'country'=>$country,'edu'=>$edu,'applications'=>$app
            ,'user'=>$user,'app_step'=>$step,'school'=>$schools,'subject'=>$subject
            ,'upload'=>$upload,'address'=>$address_default
            ,'order'=>$user_order,'passport'=>$passport,'order2'=>$user_order2,'order3'=>$user_order3
        ]);
    }


    //操作订单步骤，提交数据
    public function save_upload(){
        $user_id = Session::get('user_id');
        if(empty($user_id)){
            $this->redirect('log');
        }

        if($this->request->isPost()){
            $data = $this->request->param();

            $user_result = Db::name('user')
                ->where('id',$user_id)
                ->strict(false)
                ->update($data);
            if(isset($data['sfz_img'])){
                $data['sfz_img'] = serialize($data['sfz_img']);
            }
            if(isset($data['cj_img'])){
                $data['cj_img'] = serialize($data['cj_img']);
            }
            if(isset($data['crj_img'])){
                $data['crj_img'] = serialize($data['crj_img']);
            }
            if(isset($data['credential'])){
                $data['credential'] = serialize($data['credential']);
            }
            if(isset($data['other_third'])){
                $data['other_third_fee']=0;
                foreach ($data['other_third'] as $key=>$value){

                    $data['other_third_fee'] += $value['third_send'];
                }

                $data['other_third'] = serialize($data['other_third']);
            }
            if(isset($data['miss_img'])){
                $data['miss_img'] = serialize($data['miss_img']);
            }
            //dump($data);die;
            $result_upload = Db::name('user_upload')->where('uid',$user_id)->find();
            if($result_upload){
                //dump($data);die;
                Db::name('user_upload')
                    ->where('uid',$user_id)
                    ->strict(false)
                    ->update($data);

            }else{
                $data['uid']=$user_id;
                Db::name('user_upload')->strict(false)->insert($data);
            }

            $result_edu = Db::name('edu')->where('u_id',$user_id)->find();
            if($result_edu){
                $edu_result = Db::name('edu')
                    ->where('u_id',$user_id)
                    ->strict(false)
                    ->update($data);
            }else{
                $data['u_id']=$user_id;
                Db::name('edu')->strict(false)->insert($data);
            }

            $result_order = Db::name('user_order')->where('id',$data['user_order_id'])->find();
            if($result_order){
                $order_result = Db::name('user_order')
                    ->where('user_id',$user_id)
                    ->where('id',$data['user_order_id'])
                    ->strict(false)
                    ->update($data);
            }else{
                $data['user_id']=$user_id;
                Db::name('user_order')->strict(false)->insert($data);
            }

            //更新信息后返回原地址，如点击下一步，则在更新之后跳转
        }
    }
    public function save_next($app_id,$sort){
        $user_id = Session::get('user_id');
        if(empty($user_id)){
            $this->redirect('log');
        }
        $app_step = new AppStep();
        $user_order = Db::name('user_order')
            ->where('app_id',$app_id)
            ->where('user_id',$user_id)
            ->find();
        if($sort>1){
            //找到上一步步骤，存储上一步完成状态
            $sort_pre = $app_step
                ->where('app_id',$app_id)
                ->where('sort',$sort-1)
                ->where('status',1)
                ->find();

            if(empty($user_order['step_ok'])){
                $data['step_ok'] = $sort_pre['id'];
            }else{
                $ok = explode(',',$user_order['step_ok']);
                if(!in_array($sort_pre['id'],$ok)){
                    $data['step_ok']  = $user_order['step_ok'].",".$sort_pre['id'];
                }
            }
            $data['update_time'] = date('Y-m-d H:i:s');

        }else{
            $this->redirect('/app_step_info/app_id/'.$app_id.'/sort/1');
        }
        if($this->request->isPost()){
            $data = $this->request->param();
            $sort_pre = $app_step
                ->where('app_id',$app_id)
                ->where('sort',$sort-1)
                ->where('status',1)
                ->find();

            if(empty($user_order['step_ok'])){
                $data['step_ok'] = $sort_pre['id'];
            }else{
                $ok = explode(',',$user_order['step_ok']);
                if(!in_array($sort_pre['id'],$ok)){
                    $data['step_ok']  = $user_order['step_ok'].",".$sort_pre['id'];
                }
            }
            $data['update_time'] = date('Y-m-d H:i:s');
            if(isset($data['other_third'])){
                $data['other_third'] = serialize($data['other_third']);
            }
            //dump($data);die;
            $user_result = Db::name('user')
                ->where('id',$user_id)
                ->strict(false)
                ->update($data);
            if(isset($data['sfz_img'])){
                $data['sfz_img'] = serialize($data['sfz_img']);
            }
            if(isset($data['cj_img'])){
                $data['cj_img'] = serialize($data['cj_img']);
            }
            if(isset($data['crj_img'])){
                $data['crj_img'] = serialize($data['crj_img']);
            }
            if(isset($data['credential'])){
                $data['credential'] = serialize($data['credential']);
            }
            $result_upload = Db::name('user_upload')->where('uid',$user_id)->find();
            if($result_upload){
                $user_upload = Db::name('user_upload')
                    ->where('uid',$user_id)
                    ->strict(false)
                    ->update($data);
            }else{
                $data['uid']=$user_id;
                Db::name('user_upload')->strict(false)->insert($data);
            }

            $result_edu = Db::name('edu')->where('u_id',$user_id)->find();
            if($result_edu){
                $edu_result = Db::name('edu')
                    ->where('u_id',$user_id)
                    ->strict(false)
                    ->update($data);
            }else{
                $data['u_id']=$user_id;
                Db::name('edu')->strict(false)->insert($data);
            }

            $result_order = Db::name('user_order')->where('user_id',$user_id)->find();

            if($result_order){
                $order_result = Db::name('user_order')
                    ->where('user_id',$user_id)
                    ->where('id',$data['user_order_id'])
                    ->strict(false)
                    ->update($data);
            }else{
                $data['user_id']=$user_id;
                Db::name('user_order')->strict(false)->insert($data);
            }
            //更新信息后返回原地址，如点击下一步，则在更新之后跳转
        }else{
            $order_result = Db::name('user_order')
                ->where('user_id',$user_id)
                ->where('app_id',$app_id)
                ->strict(false)
                ->update($data);
        }
        if($order_result){
            $this->redirect('/app_step_info/app_id/'.$app_id.'/sort/'.$sort);
        }else{
            $this->error('error');
        }
    }
    public function user_order_sign(){
        $user_id = Session::get('user_id');
        if(!empty($user_id)){
            if($this->request->isPost()){
                $data = $this->request->param();
                $user_order = Db::name('user_order')
                    ->where('id',$data['user_order_id'])
                    ->where('user_id',$user_id)
                    ->find();
                //dump($data);die;
                $up_dir = ROOT_PATH.'public' . DS . 'uploads'.DS.'sign'.DS;//存放在当前目录的upload文件夹下
                $base64_img = trim($data['sign_img']);
                $data_sign = time().'.'.'png';
                $new_file = $up_dir.$data_sign;
                $info = file_put_contents($new_file, base64_decode(str_replace('data:image/png;base64', '', $base64_img)));
                if ($info){
                    $data['sign_img'] =  '/uploads/sign/'.$data_sign;
                    
                    $data['sign_ip'] = $this->get_ip();
                    $sort_pre = Db::name('app_step')
                        ->where('sort',$data['sort'])
                        ->where('app_id',$user_order['app_id'])
                        ->where('status',1)
                        ->find();
                    //dump($sort_pre);die;
                    if(isset($data['miss_img'])){
                        $data['miss_img'] = serialize($data['miss_img']);
                    }
                    //dump($data);die;
                    if(!empty($data['miss_img'])){
                        Db::name('user_upload')
                            ->where('uid',$user_id)
                            ->strict(false)
                            ->update($data);
                    }

                    if(empty($user_order['step_ok'])){
                        $data['step_ok'] = $sort_pre['id'];
                    }else{
                        $ok = explode(',',$user_order['step_ok']);
                        if(!in_array($sort_pre['id'],$ok)){
                            $data['step_ok'] = $user_order['step_ok'].",".$sort_pre['id'];
                        }else{
                            $data['step_ok'] = $user_order['step_ok'];
                        }
                    }
                    //判断是否完成全部步骤，是则添加付款金额

                    $is_need = Db::name('app_step')->where('app_id',$user_order['app_id'])
                        ->where('status',1)
                        ->field('id')->select();
                    $is_need = array_column($is_need,'id');
                    sort($is_need);
                    $is_ok = explode(',',$data['step_ok']);
                    sort($is_ok);
                    $diff = array_diff($is_need,$is_ok);

                    if(empty($diff)){
                        $app = Db::name('applications')
                            ->where('id',$user_order['app_id'])
                            ->field('fee,copy_fee,send_north,send_int')
                            ->find();
                        $data['basic_fee'] = $app['fee'];
                        $data['copy_fee'] = $app['copy_fee'];
                        $data['fee'] = ($app['fee']+$app['copy_fee']*$user_order['copy_number']+$user_order['other_third_fee']+$user_order['send_for']+$user_order['third_send'])*1.13;
                    }
                    $data['update_time'] = date('Y-m-d H:i:s');

                    $user_upload = Db::name('user_order')
                        ->where('id',$data['user_order_id'])
                        ->strict(false)
                        ->update($data);
                    if($user_upload&&empty($diff)){
                        $this->success('Sign success','/application_pay');
                    }else{
                        $this->success('Sign success','/applications_info/id/'.$user_order['app_id']);
                    }
                }else{
                    $this->error('error');
                }
            }
        }else{
            $this->redirect('/log');
        }
    }
    //删除补充资料图片信息
    public function miss_img_del(){
        $user_id = Session::get('user_id');
        if(empty($user_id)){
            $this->redirect('/home/user/log');
        }
        if($this->request->isPost()){
            $data = $this->request->param();
            //dump($data);
            if(isset($data['miss_img'])){
                $data['miss_img'] = serialize($data['miss_img']);
            }else{
                $data['miss_img']='';
            }
            //dump($data);die;
            $result = Db::name('user_upload')->where('uid',$user_id)->update($data);
            if($result){
                $this->success('Delete success');
            }else {
                $this->error('Delete error');
            }
        }

    }
    //删除其他第三方信息
    public function other_third_del(){
        $user_id = Session::get('user_id');
        if(empty($user_id)){
            $this->redirect('/home/user/log');
        }
        if($this->request->isPost()){
            $data = $this->request->param();
            //dump($data);
            if(isset($data['other_third'])){
                $data['other_third'] = serialize($data['other_third']);
            }else{
                $data['other_third']='';
            }
            //dump($data);die;
            $result = Db::name('user_order')
                ->where('id',$data['user_order_id'])
                ->update($data);
            if($result){
                $this->success('Delete success');
            }else {
                $this->error('Delete error');
            }
        }

    }

    public function apply_all(){
        $user_id = Session::get('user_id');
        if(empty($user_id)){
            $this->redirect('/home/user/log');
        }
        $user = UserModel::get($user_id);
        $app = Db::name('applications')->where('status',1)->select();

        $upload = Db::name('user_upload')->where('uid',$user_id)->find();
        //dump($user);die;
        return $this->fetch('apply_all',[
            'user'=>$user,'upload'=>$upload
            ,'applications'=>$app
        ]);
    }
    public function apply_introduction(){
        $user_id = Session::get('user_id');
        if(empty($user_id)){
            $this->redirect('/home/user/log');
        }
        $user = UserModel::get($user_id);
        $app = Db::name('applications')->where('status',1)->select();

        $upload = Db::name('user_upload')->where('uid',$user_id)->find();
        //dump($user);die;
        return $this->fetch('apply_introduction',[
            'user'=>$user,'upload'=>$upload
            ,'applications'=>$app
        ]);
    }
    public function apply_how(){
        $user_id = Session::get('user_id');
        if(empty($user_id)){
            $this->redirect('/home/user/log');
        }
        $user = UserModel::get($user_id);
        $app = Db::name('applications')->where('status',1)->select();

        $upload = Db::name('user_upload')->where('uid',$user_id)->find();
        //dump($user);die;
        return $this->fetch('apply_how',[
            'user'=>$user,'upload'=>$upload
            ,'applications'=>$app
        ]);
    }
    public function apply_select(){
        $user_id = Session::get('user_id');
        if(empty($user_id)){
            $this->redirect('/home/user/log');
        }
        $user = UserModel::get($user_id);
        $app = Db::name('applications')->where('status',1)->select();
        $third = Db::name('third')->where('status',1)->select();
        $upload = Db::name('user_upload')->where('uid',$user_id)->find();
        $edu = Db::name('edu')->alias('e')
            ->join('cs_schools s','e.school=s.id')
            ->join('cs_subject b','e.faculty=b.id')
            ->where('u_id',$user_id)
            ->field('s.name_en,b.title_en,e.academic_level,e.id')
            ->select();
        //dump($user);die;
        return $this->fetch('apply_select',[
            'user'=>$user,'upload'=>$upload,'third'=>$third
            ,'applications'=>$app,'edu'=>$edu
        ]);
    }

    public function application_pay($order_no){
        $user_id = Session::get('user_id');
        if(empty($user_id)){
            $this->redirect('/home/user/log');
        }
        $user = UserModel::get($user_id);
        $order = Db::name('order')->where('order_number',$order_no)->find();
        $app = Db::name('applications')->where('status',1)->select();
        $app_order = Db::name('user_order')->alias('o')
            ->join('applications a','o.app_id=a.id')
            ->field('o.*,a.fee as basic_fee,a.copy_fee,a.app_name,a.name_en,a.name_jp,a.name_ru,a.name_kr,a.name_fa,a.name_de')
            ->where('o.user_id',$user_id)
            ->where('o.status',0)
            ->select();
        //dump($order);die;
        $edu = Db::name('edu')->alias('e')
            ->join('cs_schools s','e.school=s.id')
            ->join('cs_subject b','e.faculty=b.id')
            ->field('s.name_en,b.title_en,e.academic_level,e.id')
            ->select();
        $upload = Db::name('user_upload')->where('uid',$user_id)->find();
        return $this->fetch('application_pay',[
            'user'=>$user,'upload'=>$upload,'order'=>$order
            ,'applications'=>$app,'edu'=>$edu,
        ]);
    }

    public function transfer(){
        return $this->fetch('apply');
    }

    public function apply_order(){
        $user_id = Session::get('user_id');
        if(empty($user_id)){
            $this->redirect('/home/user/log');
        }
        $user = UserModel::get($user_id);
        $app = Db::name('applications')->where('status',1)->select();
        $order = Db::name('user_order')->alias('o')
            ->join('applications a','o.app_id=a.id')
            ->where('o.user_id',$user_id)
            ->where('o.pay','<>',"")
            ->field('o.*,a.app_name,a.name_en,a.name_jp,a.name_ru,a.name_kr,a.name_fa,a.name_de')
            ->select();
        return $this->fetch('apply_order',['user'=>$user,'applications'=>$app,'order'=>$order]);
    }
    public function applications_cps(){
        return $this->fetch();
    }
    public function applications_lists_immigration_programs(){
        return $this->fetch();
    }

    public function applications_iehp(){
        return $this->fetch();
    }
    public function applications_eca_renew(){
        return $this->fetch();
    }
}
