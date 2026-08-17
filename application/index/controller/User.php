<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2019/7/29 0029
 * Time: 15:16
 */

namespace app\index\controller;

header("Content-type: text/html; charset=utf-8");

use app\common\controller\GoogleTranslate;
use app\common\controller\HomeBase;
use app\common\controller\Recaptcha;
use app\common\controller\WorldArea;
use app\common\model\Applications;
use app\common\model\Applications as AppModel;
use app\common\model\AppStep;
use app\common\model\Edu;
use app\common\model\OrderDetail;
use app\common\model\User as UserModel;
use app\common\model\UserAddress;
use app\common\model\UserOrder;
use app\common\model\UserUpload;
use mailer\tp5\Mailer;
use think\Cache;
use think\Cookie;
use think\Db;
use think\db\exception\DataNotFoundException;
use think\db\exception\ModelNotFoundException;
use think\Exception;
use think\exception\DbException;
use think\exception\PDOException;
use think\Log;
use think\Request;
use think\response\Json;
use think\Session;
use think\Validate;

class User extends HomeBase
{
    protected $userModel;
    protected $eduModel;
    protected $uploadModel;
    protected $userAddress;
    protected $orderModel;
    protected $applications = [];
    protected $orderDetail;
    protected $appModel;

    public function __construct(Request $request)
    {
        parent::__construct($request);
        $this->userModel = new \app\common\model\User();
        $this->eduModel = new Edu();
        $this->uploadModel = new UserUpload();
        $this->userAddress = new UserAddress();
        $this->orderModel = new UserOrder();
        $this->orderDetail = new OrderDetail();
        $this->appModel = new Applications();
    }

    public function _initialize()
    {
        parent::_initialize();
        $applications = Db::name('applications')->where('status', 1)->select(); // 所有应用数据
        $this->applications = $applications;
        $this->assign('applications', $applications);
    }

    /**
     * Notes: 登录页面
     * Date: 2020/9/15  14:28
     * @return mixed
     */
    public function log()
    {
        if (Session::has('user_id')) {
            $this->redirect('index/user/applications_lists');
        }
        return $this->fetch('login');
    }

    /**
     * Notes: 用户登录
     * Date: 2020/9/15  14:27
     */
    public function login()
    {
        if ($this->request->isPost()) {
            $data = $this->request->param();
            // if (config('verify') == 1) {
            //     if (empty($data['g-recaptcha-response'])) {
            //         $this->error('Man-machine verification failed');
            //     }
            //     // 人机验证
            //     // 判断是否需要验证

            //     $recaptcha = new Recaptcha($data['g-recaptcha-response']);
            //     $resonse = $recaptcha->siteverify();
            //     unset($data['g-recaptcha-response']);
            //     if (!$resonse['success']) {
            //         $this->error('Man-machine verification failed');
            //     }
            // }
            $validate_result = $this->validate($data, 'Login');
            if ($validate_result === true) {
                if (!empty($data['rem'])) {
                    // 代表勾选保持登陆状态
                    setcookie("useremail", $data['email'], time() + 3600 * 24 * 7);
                    setcookie("userpwd", $data['password'], time() + 3600 * 24 * 7);
                    unset($data['rem']);
                }
                $data['email'] = trim($data['email']);
                $data['password'] = md5(trim($data['password'] . '123'));

                $condition = [
                    'email' => $data['email'],
                    'password' => $data['password'],
                ];
                $result = $this->userModel->findData($condition);
                if ($result) {
                    if ($result['login_start']  == 1) {
                        $this->error('Login failed,Your account has been disabled!');
                    }
                    if ($result['status'] != 1) {
                        $this->error('Login failed,Your account has been disabled!');
                    }
                    Session::set('user_id', $result['id']);
                    Session::set('user_infoname', $result['username']);
                    if ($result['status_info'] == 2 || $result['status_info'] == 3 || $result['status'] == 2) {
                        $this->error('The account has been blocked!');
                    }
                    $login = [];
                    $login['last_login_time'] = date('Y-m-d H:i:s');
                    $login['last_login_ip'] = parent::get_ip();

                    $signre = Db::name('user_order')->where('user_id', $result['id'])->where('resign', 1)->find();
                    if ($signre) {
                        session::set('signre', $signre['app_id']);
                        $this->success('Login success', 'index/user/profile');
                    }
                    $this->userModel->save($login, ['id' => $result['id']]);
                    $url = Cookie::get('temp_url');
                    if (empty($url)) {
                        $url = url('index/user/profile');
                    }
                    $this->success('Login success', $url);
                } else {
                    $this->error('Login failed, the account password is incorrect');
                }
            } else {
                $this->error($validate_result);
            }
        } else {
            $this->redirect('log');
        }
    }

    /**
     * Notes: 密码修改页面
     * Date: 2020/9/15  14:28
     */
    public function change_pass()
    {
        $result = Db::name('user')->where('id', $this->user_id)->find();
        return $this->fetch('change_pass', ['user' => $result]);
    }

    /**
     * Notes: 密码修改
     * Date: 2020/9/15  14:29
     */
    public function change_pwd()
    {
        $user_id = $this->user_id;
        if ($this->request->isPost()) {
            $data = $this->request->param();
            if ($data['pwd_new'] != $data['pwd_new2']) {
                $this->error('Two new passwords do not match');
            }
            $result = Db::name('user')->where('id', $user_id)
                ->where('password', md5(trim($data['pwd_old']) . '123'))
                ->find();
            if (empty($result)) {
                $this->error('Original password error');
            }
            $pwd = md5(trim($data['pwd_new']) . '123');
            $res = Db::name('user')->where('id', $user_id)->update(['password' => $pwd]);
            if ($res) {
                $this->success('Password updated successfully', 'index/user/log');
            } else {
                $this->error('Password updated error');
            }
        }
    }

    /**
     * 重置密码
     */
    public function forget_pass()
    {
        //         if ($this->request->isPost()) {
        //             $email = input("post.email");
        //             $token = request()->session('__token__');
        //             if (empty($email)) {
        //                 $this->error('邮箱不能为空');
        //             }
        //             if (!empty($email) && $token = $_POST['__token__']) {
        // //                $this->error($token . '----' . $_POST['__token__']);
        //                 $result = Db::name('user')->where('email', $email)->find();
        //                 if ($result) {
        //                     Session::set('email', $email);
        //                     if ($result['status_info'] == 2 || $result['status_info'] == 3 || $result['status'] == 2) {
        //                         $this->error('The account has been blocked!');
        //                     }
        //                     //邮件发送新密码
        //                     $pwd = parent::getrandstr();
        //                     $request = Request::instance();
        //                     $domain = $request->domain();
        //                     //存数据库
        //                     $password = md5($pwd . '123');
        //                     Db::name('user')->where('email', $email)->update(
        //                         ['password' => $password]
        //                     );
        //                     parent::sentEmailreset($email, $pwd, $domain);
        //                     $this->success('success', '/index/user/forget_pass_info');
        //                 } else {
        //                     $this->error('The email account is not yet in use');
        //                 }
        //             }
        //         }
        return $this->fetch();
    }

    public function forget_pass_info()
    {
        return $this->fetch();
    }

    /**
     * 注册页面
     * @return mixed
     */
    public function add()
    {
        return $this->fetch('register');
    }

    /**
     * 用户注册操作
     */
    public function create()
    {
        if ($this->request->isPost()) {
            $data = $this->request->param();
            if (config('verify') == 1) {
                if (empty($data['g-recaptcha-response'])) {
                    $this->error('Man-machine verification failed');
                }
                $recaptcha = new Recaptcha($data['g-recaptcha-response']);
                $resonse = $recaptcha->siteverify();
                unset($data['g-recaptcha-response']);
                if (!$resonse['success']) {
                    $this->error('Man-machine verification failed');
                }
            }
            // if (preg_match('/^[\x7f-\xff]+$/', $data['firstname']) && preg_match('/^[\x7f-\xff]+$/', $data['lastname'])) {
            //     $data['username'] = $data['lastname'] . ' ' . $data['middlename'] . ' ' . $data['firstname'];
            // } else {
            //     $data['username'] = $data['middlename'] . ' ' . $data['firstname'] . ' ' . $data['lastname'];
            // }
            $data['username'] = $data['firstname'] . ' ' . $data['middlename'] . ' ' . $data['lastname'];
            $data['chinaname2'] = $data['lastname'] . ' ' . $data['middlename'] . ' ' . $data['firstname'];
            unset($data['__token__'], $data['firstname'], $data['middlename'], $data['lastname']);
            $search_ids = model('common/User')->column('search_id');
            $search_id = 'WSE' . rand('100000', '999999');
            while (in_array($search_id, $search_ids)) {
                $search_id = 'WSE' . rand('100000', '999999');
            }
            $data['search_id'] = $search_id;
            $data['password'] = md5($data['password'] . '123');
            //$data['password'] = md5(preg_replace('/(123$)/', '', $data['password']));
            $userModel = new \app\common\model\User();
            $result = $userModel->validate(true)->allowField(true)->save($data);
            if ($result === false) {
                return status_code(11005, $userModel->getError());
            }
            $user_id = $userModel->getLastInsID();
            Session::set('user_id', $user_id);
            Session::set('username', $data['username']);
            return status_code(20000, '', url('index/user/profile'));
        }
    }

    // 注册用户 第二页面
    public function register_edu()
    {
        $think_var = Cookie::get('think_var');
        $country_name = getCountryName($think_var);
        $sub_title = getSubTitle($think_var);
        $school_name = getNameLang($think_var);
        $user_id = Session::get('user_id');
        if (empty($user_id)) {
            $this->redirect('log');
        }
        // 获取排序为0的课程项目
        $subject = Db::name('cs_subject')->where('sort', 0)->field(
            "id,$sub_title"
        )->select();
        $think_var = Cookie::get('think_var');
        foreach ($subject as $k => $v) {
            if ($think_var == 'zh-cn') {
                $subject[$k]['titlt'] = $subject[$k]['title'];
            } else {
                if ($think_var == 'en-us') {
                    $subject[$k]['title'] = $subject[$k]['title_en'];
                    unset($subject[$k]['title_en']);
                } else {
                    if ($think_var == 'ja-jp') {
                        $subject[$k]['title'] = $subject[$k]['title_jp'];
                        unset($subject[$k]['title_jp']);
                    } else {
                        if ($think_var == 'de-de') {
                            $subject[$k]['title'] = $subject[$k]['title_de'];
                            unset($subject[$k]['title_de']);
                        } else {
                            if ($think_var == 'ko-kr') {
                                $subject[$k]['title']
                                    = $subject[$k]['title_kr'];
                                unset($subject[$k]['title_kr']);
                            } else {
                                if ($think_var == 'ru-ru') {
                                    $subject[$k]['title'] = $subject[$k]['title_ru'];
                                    unset($subject[$k]['title_ru']);
                                } else {
                                    if ($think_var == 'fr-fa') {
                                        $subject[$k]['title']
                                            = $subject[$k]['title_fa'];
                                        unset($subject[$k]['title_fa']);
                                    } else {
                                        $subject[$k]['title']
                                            = $subject[$k]['title_en'];
                                        unset($subject[$k]['title_en']);
                                    }
                                }
                            }
                        }
                    }
                }
            }
        }
        // 获取加拿大国家的大学
        $schools = Db::name('cs_schools')->where('gj', '加拿大')->order(
            'name_en',
            'asc'
        )->field(
            "id,$school_name"
        )->select();
        foreach ($schools as $k => $v) {
            $schools_new[$k]['id'] = $schools[$k]['id'];
            $schools_new[$k]['name'] = $schools[$k][$school_name];
        }
        $country = Db::name('country')->order('id', 'asc')->field(
            'id,' . $country_name . ' as name'
        )->select();
        if ($this->request->isPost()) {
            $data = $this->request->param();
            $sort = $data['faculty'];
            $result2 = Db::name('cs_subject')->where('sort', $sort)->field(
                'id,' . $sub_title . ' as title'
            )->select();
            $opt = '<option>Choose...</option>';
            foreach ($result2 as $key) {
                $opt .= "<option value=" . $key['id'] . ">" . $key[$sub_title]
                    . "</option>";
            }
            echo json_encode($opt);
        } else {
            return $this->fetch(
                'register_edu',
                [
                    'schools' => $schools_new,
                    'subject' => $subject,
                    'country' => $country,
                ]
            );
        }
    }

    // 获取科学领域下的共同领域项目
    public function getSubject()
    {
        $think_var = Cookie::get('think_var');
        $sub_title = getSubTitle($think_var);
        $user_id = Session::get('user_id');
        if (empty($user_id)) {
            $this->redirect('log');
        }
        if ($this->request->isPost()) {
            $data = $this->request->param();
            $sort = $data['faculty'];
            $result2 = Db::name('cs_subject')->where('sort', $sort)->field(
                "id,$sub_title"
            )->select();
            foreach ($result2 as $k => $v) {
                $result2_new[$k]['id'] = $result2[$k]['id'];
                $result2_new[$k]['title'] = $result2[$k][$sub_title];
            }
            return json_encode($result2_new);
        }
    }

    // 注册表第二页数据,数据储存进数据库  弃用
    public function create_extra()
    {
        // 获取用户登陆后储存的sessionID
        $user_id = Session::get('user_id');
        // 判断用户ID是否存在,进行下一步操作
        if (empty($user_id)) {
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
            $result_user = Db::name('user')->where('id', $user_id)->update(
                $up_user
            );
            if ($result_user) {
                // 获取教育表内的数据 进行判断 防止恶意重复提交
                $educa = Db::name('educa')->where("user_id", $user_id)->find();
                if ($educa) {
                    // 代表教育表已经存在了数据 只需要修改
                    $up_educa['sort'] = $educa['sort'] + 1;
                    $result_educa = Db::name('educa')->where(
                        "user_id",
                        $user_id
                    )->update($up_educa);
                } else {
                    // 代表教育表未存在数据 需要插入
                    $up_educa['user_id'] = $user_id;
                    $result_educa = Db::name('educa')->insert($up_educa);
                }
                if ($result_educa) {
                    $this->success('Edu save success', 'profile');
                } else {
                    $this->error('Edu save error');
                }
            } else {
                $this->error('Edu save error');
            }
        }
    }

    // 注册第三张表单,全部信息表单
    public function profile()
    {
        $user_id = $this->user_id;
        $user = model('User')->find($user_id);
        if (!empty($user['qujian'])) {
            $user['qujianimg'] = Db::name('mobile')->where('qujian', '+' . $user['qujian'])->value('imgs');
        } else {
            $user['qujian'] = '1';
            $user['qujianimg'] = Db::name('mobile')->where('qujian', '+1')->value('imgs');
        }
        if (empty($user)) {
            $this->error(lang('User does not exist'));
        }
        if (!empty($user['certificate_img'])) {
            $user['certificate_img'] = unserialize($user['certificate_img']);
        }
        if (!empty($user['birth_time'])) {
            $user['birth_time'] = date('d-m-Y', strtotime($user['birth_time']));
        }
        $username = explode(" ", $user['username']);
        if (preg_match('/^[\x7f-\xff]+$/', $user['username'])) {
            $user['lastname'] = $username[0];
            $user['middlename'] = $username[1];
            $user['firstname'] = $username[2];
        } else {
            $username_length = count($username);
            if ($username_length == 2) {
                $user['firstname'] = $username[0];
                $user['middlename'] = '';
                $user['lastname'] = $username[1];
            } else {
                $user['firstname'] = empty($username[0]) ? '' : $username[0];
                $user['middlename'] = empty($username[1]) ? '' : $username[1];
                $user['lastname'] = empty($username[2]) ? '' : $username[2];
            }
        }
        $address = model('UserAddress')->where('user_id', $user_id)->find();
        $country = Db::name('cs_world_area_country')->where('alias', 'egt', 1)->select();
        $provices = [];
        $cities = [];
        $nationalities = WorldArea::getTypeList([], 'country');
        if (!empty($address['province'])) {
            $country_code = Db::name('cs_world_area_country')
                ->where('name_en', $address['national'])
                ->value('code');
            $provices = Db::name('cs_world_area_state')
                ->where("country_code", $country_code)
                ->where('name|name_en', 'neq', '000')
                ->select();
        }
        $open = $user['info_start'] == 2 && $user['open_start'] == 0 ? 1 : 0;
        if ($open) {
            model('User')->where('id', $user_id)->setField('open_start', 1);
        }

        if (!empty($address['city'])) {
            if (empty($address['province'])) {
                $code = Db::name('cs_world_area_country')->where('name|name_en', $address['national'])->value('code');
                $code .= '000';
            } else {
                $code = Db::name('cs_world_area_state')->where('name|name_en', $address['province'])->value('code');
            }
            $cities = Db::name("cs_world_area_city")->where("state_code", $code)->select();
        }
        $mobiles = Db::name('mobile')->select();
        $signre = Db::name('user_order')->where('user_id', $user_id)->where('resign', 1)->find();
        if ($signre) {
            $this->assign('signre', $signre['app_id']);
        }
        $this->assign(compact('user', 'nationalities', 'country', 'mobiles', 'address', 'provices', 'cities', 'open'));
        return $this->fetch();
    }

    // Ajax 获取研究领域对应的课程项目
    public function getProfile()
    {
        // 判断用户是否登陆
        $user_id = Session::get('user_id');
        if (empty($user_id)) {
            $this->redirect('/index/user/log');
        }
        // post提交表单,获取post表单数据
        if ($this->request->isPost()) {
            $data = $this->request->param();
            $think_var = Cookie::get('think_var');
            $sub_title = getSubTitle($think_var);
            $profile = Db::name('cs_subject')->where("sort", $data['faculty'])
                ->field("id,$sub_title")->select();
            foreach ($profile as $k => $v) {
                $profile_new[$k]['id'] = $profile[$k]['id'];
                $profile_new[$k]['title'] = $profile[$k][$sub_title];
            }
            return $profile_new;
        }
    }

    // Ajax 保存用户收货地址
    public function address_save()
    {
        // 判断用户是否登陆
        $user_id = $this->user_id;
        if (!$this->request->isPost()) {
            return status_code(10014);
        }
        $user = model('User')->find($user_id);
        $data = $this->request->param('', '', 'trim');
        $data['addressee'] = $user['username'];
        $data['user_id'] = $user_id;
        $user_data['mobile'] = $data['mobile'];
        $user_data['qujian'] = trim($data['qujian'], '+');
        $user_data['sort'] = $user['sort'] + 1;
        $user_data['update_ip'] = parent::get_ip();
        $user_data['info_start'] = 2;
        if ($user['info_start'] == 1 || $user['info_start'] == 3) {
            $user_data['info_start'] = 3;
        }
        unset($data['qujian'], $data['mobile']);
        Db::startTrans();
        try {
            $result_user = $user->allowField(true)->save($user_data, ['id' => $user_id]);
            $result_address = model('UserAddress')->refresh(['user_id' => $user_id], $data);
            Db::commit();
        } catch (Exception $e) {
            Db::rollback();
            return status_code(10006, $e->getMessage());
        }
        if ($result_user || $result_address) {
            return status_code(20000);
        } else {
            return status_code(10006);
        }
    }

    // Ajax 保存签证信息      弃用
    public function student_save()
    {
        $user_id = Session::get('user_id');
        if (empty($user_id)) {
            $this->redirect('/index/user/log');
        }
        if ($this->request->isPost()) {
            $data = $this->request->param();
            if (isset($data['diploma_arr'])) {
                $data['diploma_img'] = trim(implode(',', $data['diploma_arr']));
            }
            $user_arr = Db::name('user')->where('id', $user_id)->field('sort')->find();
            $user_data['sort'] = 1;
            $result_user = Db::name('user')->where('id', $user_id)->update($user_data);
            if ($result_user) {
                $passport = Db::name('user_passport')->where("user_id", $user_id)->find();
                $data['create_time'] = date('Y-m-d H:i:s');
                if ($passport) {
                    $data['update_ip'] = parent::get_ip();
                    $resulent_passport = Db::name('user_passport')->where("user_id", $user_id)->update($data);
                } else {
                    $data['create_ip'] = parent::get_ip();        //获取插入时间和插入IP
                    $data['user_id'] = $user_id;             // 代表签证表内无数据 插入数据
                    $resulent_passport = Db::name('user_passport')->insert($data);
                }
                if ($resulent_passport) {
                    // 更新成功
                    $this->success('Infomation save success');
                } else {
                    // 更新失败
                    $this->error('Infomation save error');
                }
            } else {
                // 更新失败
                $this->error('Infomation save error');
            }
        }
    }

    // 删除教育背景资料中(证件照片/身份证)的图片
    public function del_img()
    {
        $user_id = Session::get('user_id');
        if (empty($user_id)) {
            $this->redirect('/index/user/log');
        }
        if ($this->request->isPost()) {
            $data = $this->request->param();
            if ($data['id'] == 'portrait_del') {
                // 代表删除功能为证件照片删除
                $educa_arr['port_img'] = '';
                $result_educa = Db::name('educa')->where("user_id", $user_id)->update($educa_arr);
            } elseif ($data['id'] == 'id_del') {
                // 代表删除功能为身份证照片删除
                $educa_arr['id_img'] = '';
                $result_educa = Db::name('educa')->where("user_id", $user_id)->update($educa_arr);
            }
            if ($result_educa) {
                $this->success('Infomation save success');
            } else {
                $this->error('Infomation save error');
            }
        }
    }

    // 删除教育背景资料中的(学位证书/成绩单)的图片
    public function diploma_del()
    {
        // 判断用户是否登陆
        $user_id = Session::get('user_id');
        if (empty($user_id)) {
            $this->redirect('/index/user/log');
        }
        // post提交表单,获取post表单数据
        if ($this->request->isPost()) {
            $data = $this->request->param();
            // 获取教育表(think_educa)内的信息
            $think_educa = Db::name('educa')->where("user_id", $user_id)->field(
                'diploma_img,trans_img'
            )->find();
            $diploma_img = explode(',', $think_educa['diploma_img']);
            $trans_img = explode(',', $think_educa['trans_img']);
            if ($data['id'] == 'diploma_del') {
                // 代表点击的是教育背景资料中的学位证书删除功能
                // 同时删除图片
                $path = $_SERVER['DOCUMENT_ROOT'] . $diploma_img[$data['number']];
                if (is_file($_SERVER['DOCUMENT_ROOT'] . $diploma_img[$data['number']])) {
                    // 判断文件是否存在
                    unlink($path);
                }
                unset($diploma_img[$data['number']]);
                $educa_arr['diploma_img'] = implode(',', $diploma_img);
            } elseif ($data['id'] == 'trans_del') {
                // 代表点击的是教育背景资料中的成绩单删除功能
                // 同时删除文件夹下图片
                $path = $_SERVER['DOCUMENT_ROOT'] . $trans_img[$data['number']];
                if (is_file($_SERVER['DOCUMENT_ROOT'] . $trans_img[$data['number']])) {
                    // 判断文件是否存在
                    unlink($path);
                }
                unset($trans_img[$data['number']]);
                $educa_arr['trans_img'] = implode(',', $trans_img);
            }
            $result_educa = Db::name('educa')->where("user_id", $user_id)
                ->update($educa_arr);
            if ($result_educa) {
                // 更新成功
                $this->success('Infomation save success');
            } else {
                // 更新失败
                $this->error('Infomation save error');
            }
        }
    }

    // Ajax 各个国家三级联动 获取一级行政区
    public function getProvince()
    {
        $user_id = Session::get('user_id');
        $think_var = Cookie::get('think_var');
        $ziduans = 'name_en';
        if ($think_var == 'zh-cn') {
            $ziduans = 'name';
        }
        // 用户ID判断
        if ($user_id) {
            if ($this->request->isPost()) {
                $data = $this->request->param();
                $country_code = $data['national'];
                // 进行所属国家查询
                $province = Db::name('cs_world_area_state')->where(
                    "country_code",
                    $country_code
                )->field(
                    "code,$ziduans as name"
                )->select();
                // 进行$province数组置换
                if (count($province) != 1) {
                    // 代表国家中含有省/市/州
                } else {
                    // 代表国家内不含有省/市/州
                    $state_code = $province[0]['code'];
                    $city = Db::name("cs_world_area_city")->where(
                        "state_code",
                        $state_code
                    )->field(
                        "code,$ziduans as name"
                    )->select();
                    $province[0] = [$province, $city];
                }
                return json_encode($province);
            }
        }
    }

    // Ajax 省市县三级联动 获取二级行政区域
    public function getCity()
    {
        $user_id = Session::get('user_id');
        $think_var = Cookie::get('think_var');
        $ziduans = 'name_en';
        if ($think_var == 'zh-cn') {
            $ziduans = 'name';
        }
        // 用户ID判断
        if ($user_id) {
            if ($this->request->isPost()) {
                $data = $this->request->param();
                $state_code = $data['province'];
                $city = Db::name("cs_world_area_city")->where(
                    "state_code",
                    $state_code
                )->field(
                    "code,$ziduans as name"
                )->select();
                return json_encode($city);
            }
        }
    }

    // Ajax 省市县三级联动 获取三级行政区
    public function getCounty()
    {
        $user_id = Session::get('user_id');
        $think_var = Cookie::get('think_var');
        $ziduans = 'name_en';
        if ($think_var == 'zh-cn') {
            $ziduans = 'name';
        }
        // 用户ID判断
        if ($user_id) {
            if ($this->request->isPost()) {
                $data = $this->request->param();
                $city_code = $data['city'];
                $county = Db::name("cs_world_area_region")->where("city_code", $city_code)->field("code,$ziduans as name")->select();
                return json_encode($county);
            }
        }
    }

    // Ajax 修改用户地址
    public function getAddress()
    {
        $user_id = Session::get('user_id');
        if ($user_id) {
            if ($this->request->isPost()) {
                $data = $this->request->param();
                $data['create_time'] = date("Y-m-d H:i:s");
                $data['create_ip'] = parent::get_ip();
                $resurt = Db::name('user_address')->where("user_id", $user_id)->update($data);
                if ($resurt) {
                    $msg = 1;
                } else {
                    $msg = 2;
                }
                return json_encode($msg);
            }
        }
    }

    // 添加邮箱
    public function add_email()
    {
        $user_id = Session::get('user_id');
        if (empty($user_id)) {
            $this->redirect('/index/user/log');
        }
        if ($this->request->isPost()) {
            $data = $this->request->param();
            $data['create_time'] = date('Y-m-d H:i:s');
            $data['create_ip'] = parent::get_ip();
            $find = Db::name('user_email')->where('user_id', $user_id)->count();
            $find2 = Db::name('user_email')->where('user_id', $user_id)->where('email', $data['email'])->find();
            if ($find >= 3 || !empty($find2)) {
                $this->error('This Email has been added, or the number of Email cannot exceed 3');
            }
            $result = Db::name('user_email')->insert($data);
            if ($result) {
                $this->success('Add Email Success');
            } else {
                $this->error('Add Email Error');
            }
        }
    }

    public function del_email()
    {
        $e_id = input('get.id');
        $user_id = Session::get('user_id');
        if (empty($user_id)) {
            $this->redirect('/index/user/log');
        }
        $email = Db::name('user_email')->where('id', $e_id)->find();
        $user = Db::name('user')->where('id', $user_id)->find();
        if ($email['email'] == $user['email']) {
            $this->error('The default Email cannot be deleted'); //默认电子邮件无法删除
        }
        $result = Db::name('user_email')->where('id', $e_id)->delete();
        if ($result) {
            $this->success('Delete Email Success');
        } else {
            $this->error('Delete Email Error');
        }
    }

    public function add_phone()
    {
        $user_id = Session::get('user_id');
        if (empty($user_id)) {
            $this->redirect('/index/user/log');
        }
        if ($this->request->isPost()) {
            $data = $this->request->param();
            $data['create_time'] = date('Y-m-d H:i:s');
            $data['create_ip'] = parent::get_ip();
            $find = Db::name('user_phone')->where('user_id', $user_id)->count();
            $find2 = Db::name('user_phone')->where('user_id', $user_id)->where('phone', $data['phone'])->find();
            if ($find >= 3 || !empty($find2)) {
                $this->error('This Phone has been added, or the number of Phone cannot exceed 3');
            }
            $result = Db::name('user_phone')->insert($data);
            if ($result) {
                $this->success('Add Phone Success');
            } else {
                $this->error('Add Phone Error');
            }
        }
    }

    function upload()
    {
        parent::upload();
    }

    public function del_phone()
    {
        $e_id = input('get.id');
        $user_id = Session::get('user_id');
        if (empty($user_id)) {
            $this->redirect('/index/user/log');
        }
        $phone = Db::name('user_phone')->where('id', $e_id)->find();
        $user = Db::name('user')->where('id', $user_id)->find();
        if ($phone['phone'] == $user['mobile']) {
            $this->error('The default phone number cannot be deleted');
        }
        $result = Db::name('user_phone')->where('id', $e_id)->delete();
        if ($result) {
            $this->success('Delete Phone success');
        } else {
            $this->error('Delete Phone error');
        }
    }

    public function add_address()
    {
        $user_id = Session::get('user_id');
        if (empty($user_id)) {
            $this->redirect('/index/user/log');
        }
        if ($this->request->isPost()) {
            $data = $this->request->param();
            $data['create_time'] = date('Y-m-d H:i:s');
            $data['create_ip'] = parent::get_ip();
            $find = Db::name('user_address')->where('user_id', $user_id)->count();
            $find2 = Db::name('user_address')->where('user_id', $user_id)->where('address_name', $data['address_name'])->find();
            if ($find >= 3 || !empty($find2)) {
                $this->error('This Address has been added, or the number of Address cannot exceed 3');
            }
            $result = Db::name('user_address')->insert($data);
            if ($result) {
                $this->success('Add Address success');
            } else {
                $this->error('Add Address error');
            }
        }
    }

    public function del_address()
    {
        $e_id = input('get.id');
        $user_id = Session::get('user_id');
        if (empty($user_id)) {
            $this->redirect('/index/user/log');
        }
        $result = Db::name('user_address')->where('id', $e_id)->delete();
        if ($result) {
            $this->success('Delete Address success');
        } else {
            $this->error('Delete Address error');
        }
    }

    /**
     * 应用申请
     * */
    public function applications_lists() //可申请的服务
    {
        $user_id = $this->user_id;
        $user = UserModel::get($user_id);
        $sub_query = model('UserOrder')->field('id,app_id,step_ok,order_number,status,resign')->order('create_time Desc')->where(['user_id' => $user_id])->buildSql();
        $orders = Db::table($sub_query . 'a')->group('app_id')->select();
        $applications = Model('Applications')->with(['steps' => function ($query) {
            return $query->where('status', 1)->field('id,app_id');
        }])->where('status', 1)->select();
        $order_app_ids = array_column($orders, 'app_id');
        foreach ($applications as &$value) {
            $key = array_search($value['id'], $order_app_ids);
            if ($key === false) {
                $value->order_status = -1; //表示当前应用用户未申请
                $value->order_status_des = 'Not Apply';
                $value->step_complete = [];
            } else {
                $value->order_status = $orders[$key]['status'];
                $value->order_no = $orders[$key]['order_number'];
                $value->resign = $orders[$key]['resign'];
                $value->step_complete = explode(',', $orders[$key]['step_ok']);
            }
            if ($value['id'] == 8) {
                $value->order_status_des = order_status_des($value->order_status, true);
            } else {
                $value->order_status_des = order_status_des($value->order_status);
            }
        }
        if (!empty($user['birth_time'])) {
            $user['age'] = birthdayToAge($user['birth_time']);
        }
        //  dump($user->toArray());die;
        return $this->fetch('applications_lists', ['user' => $user, 'applications' => $applications,]);
    }

    //所有申请的服务
    public function applications_all()
    {
        $user_id = Session::get('user_id');
        if (empty($user_id)) {
            $this->redirect('/index/user/log');
        }
        $user = UserModel::get($user_id);
        $app_order = Db::name('user_order')->alias('o')
            ->join('applications a', 'o.app_id=a.id')
            ->where('o.user_id', $user_id)
            ->field('o.*,a.name_cn,a.name_en,a.name_jp,a.name_ru,a.name_kr,a.name_fa,a.name_de')
            ->select();
        foreach ($app_order as $k => $v) {
            $app_step = Db::name('app_step')
                ->where('app_id', $v['app_id'])
                ->where('status', 0)
                ->order('sort', 'asc')
                ->select();
            $app_order[$k]['step'] = $app_step;
        }
        return $this->fetch(
            'applications_all',
            [
                'user' => $user,
                'app_order' => $app_order,
            ]
        );
    }

    public function applications_lists_info()
    {
        $fee_id = input('get.id');
        $result = Db::name('applications')->where('id', $fee_id)->find();
        return $this->fetch('applications_lists_info', ['fee' => $result]);
    }

    public function applications_info($id)
    {
        if (in_array($id, [7])) {
            $this->redirect('/app_step_info/app_id/' . $id . '/sort/1');
        }
        $user_id = $this->user_id;
        $user = UserModel::get($user_id);
        if (!empty($user['birth_time'])) {
            $user['age'] = birthdayToAge($user['birth_time']);
        }
        $app = $this->appModel->find($id);
        $user_order = Db::name('user_order')->where('app_id', $id)->where('user_id', $user_id)->order('create_time desc')->find();
        $computation = Order::calculateOrder($user_order['id']);
        if (gettype($computation) == 'object') {
            $computation = $computation->getData();
        }
        if ($computation['code'] != 20000) {
            $total_fee = $app['fee'];
        } else {
            $total_fee = $computation['data']['total']['fee'];
        }
        if ($user_order['step_ok'] == '') {
            $this->redirect('/app_step_info/app_id/' . $id . '/sort/1');
        }
        $this->assign('step_ok', explode(',', $user_order['step_ok']));
        $this->assign('step_error', explode(',', $user_order['step_error']));
        $name = getNameLang($this->lang);
        $step_name = getStepName($this->lang);
        $appModel = new AppModel();
        $app = $appModel->field("*,{$name} as name")->find($id);
        $app_step = new AppStep();
        $step = $app_step->field("*,{$step_name} as name")->where(['app_id' => $id, 'status' => 1])->order('sort', 'asc')->select();
        return $this->fetch('applications_info', ['app' => $app, 'user' => $user, 'app_step' => $step, 'order' => $user_order, 'data' => $computation['data'], 'total_fee' => $total_fee]);
    }

    public function app_step($app_id, $sort)
    {
        $user_id = Session::get('user_id');
        if (empty($user_id)) {
            $this->redirect('log');
        }
        $this->assign('sort', $sort);
        $user = UserModel::get($user_id);
        $result = AppModel::get($app_id);
        $lang = $this->lang;
        $name = getNameLang($lang);
        $step_name = getStepName($lang);
        $app = $this->appModel->with('step')->field("*, {$name} as name")->find($app_id);
        $user_order = Db::name('user_order')->where('app_id', $app_id)->where('user_id', $user_id)->find();
        $steps = $app->step()->field("id,app_id,step_url,sort,status")->where('status', 1)->order('sort', 'asc')->select();
        if (empty($user_order) || ($user_order['status'] == 7 && $user_order['repeatable'] == 1)) {
            $data = [
                'app_id' => $app_id,
                'user_id' => $user_id,
                'order_number' => 'E' . $result['short_name'] . rand('100000', '999999') . time(),    //订单编号
                'name_cn' => $result['name_cn'],
                'name_en' => $result['name_en'],
                'fee' => $result['fee'],
                'basic_fee' => $result['fee'],
                'create_time' => date('Y-m-d H:i:s'),
                'create_ip' => parent::get_ip(),
            ];
            if (in_array($app_id, [3, 5, 18])) {
                $update_fee = model('common/Applications')->where('id', 7)->value('fee');
                $data['update_price'] = $update_fee;
            }
            //dump($data);die;
            Db::name('user_order')->insert($data);
        }
        $upload = Db::name('user_upload')->where('uid', $user_id)->find();
        $app_step = new AppStep();
        $step = $app_step->where('app_id', $app_id)->where('status', 1)->order('sort', 'asc')->select();
        return $this->fetch('app_step', ['fee' => $result, 'user' => $user, 'app_step' => $step, 'upload' => $upload, 'order' => $user_order, 'app' => $app, 'steps' => $steps]);
    }

    /**
     * Notes: 步骤详情数据展示
     * Date: 2020/9/17  10:28
     * @param  int  $app_id  应用id
     * @param  int  $sort    步骤顺序
     */
    public function app_step_info($app_id, $sort)
    {
        $user_id = $this->user_id;
        $lang = $this->lang;
        $user = UserModel::get($user_id);
        !empty($user['certificate_img']) ? $user['certificate_img'] = unserialize($user['certificate_img']) : $user['certificate_img'] = '';
        if (!empty($user['birth_time'])) {
            $user['age'] = birthdayToAge($user['birth_time']);
        }
        $result = AppModel::get($app_id);
        $mobiles = Db::name('mobile')->select();
        if (empty($user)) {
            $this->redirect(url('index/user/add'));
        }

        if ( $app_id==8 && $sort==1) {
            return $this->select_education();
        }

        $user['qujianimg'] = '';
        if (!empty($user['qujian'])) {
            $user['qujianimg'] = Db::name('mobile')->where('qujian', '+' . $user['qujian'])->value('imgs');
        }
        $app = $this->appModel->with('steps')->field("*")->find($app_id);
        $steps = $app->steps()->where('status', 1)->order('sort', 'asc')->select();
        $step = [];
        if (!$steps) {
            $steps = [];
        } else {
            $steps = $steps->toArray();
        }
        $step_key = array_search($sort, array_column($steps, 'sort'));
        if ($step_key !== false) {
            $step = $steps[$step_key];
        }
        if ($app_id == 7) {
            return $this->fetch($step['step_url'], compact('user', 'app'));
        }
        $user_order = Db::name('user_order')->where(['user_id' => $user_id, 'app_id' => $app_id])->order('create_time DESC')->find();
        //订单为空时，添加订单数据
        if (empty($user_order) && $app_id != 7 || ($user_order['status'] == 7 && $app['repeatable'] == 1)) {
      
            $data = [
                'app_id' => $app_id,
                'user_id' => $user_id,
                'order_number' => 'E' . $result['short_name'] . rand('100000', '999999') . time(),    //订单编号
                'app_name' => $result['name_cn'],
                'name_en' => $result['name_en'],
                'fee' => $result['fee'],
                'basic_fee' => $result['fee'],
                'create_time' => date('Y-m-d H:i:s'),
                'create_ip' => parent::get_ip(),
            ];
            // 当订单为评估订单时，需添加数据更新价格
            if (in_array($app_id, [3, 5, 18])) {
                $update_fee = model('common/Applications')->where('id', 7)->value('fee');
                $data['update_price'] = $update_fee;
            }
            model('UserOrder')->save($data);
        }

        $order = Db::name('user_order')->alias('o')
            ->join('applications a', 'o.app_id=a.id', 'left')
            ->join('user_order_detail d', 'd.order_id = o.id', 'left')
            ->where('o.user_id', $user_id)
            ->where('o.app_id', $app_id)
            ->field('d.*,d.id as detail_id,o.*')
            ->order('o.create_time DESC')
            ->find();

        $subjects = Db::name('subject')->where('sort', 0)->select(); //专业存入缓存
        // 专业相关数据处理
        if (Cache::has('majorslist')) {
            $majors = Cache::get('majorslist');
        } else {
            $majors = Db::name('schools_speciality')->field('id,name,name_en') ->select();
            $majors = array_map(function ($val) {
                $val['name_en'] = ucwords(strtolower($val['name_en']));
                return $val;
            }, $majors);
            $majors = uniqueArr($majors, 'name_en');
            $majors = uniqueArr($majors, 'name');
            $majors = arraySort($majors, 'name_en');
            Cache::set('majorslist', $majors,604800);
        }

        $this->assign(compact('subjects', 'majors'));
        if (!empty($order['start_date'])) {
            $order['start_date'] = dateFormatParse($order['start_date'], 'Y-m-d', 'd-m-Y');
        }
        if (!empty($order['completion_date'])) {
            $order['completion_date'] = dateFormatParse($order['completion_date'], 'Y-m-d', 'd-m-Y');
        }
        // 申请完成无法重复申请
        if (($order['status'] >= 3) && ($app_id != 8) && ($app_id != 19)) {
            $this->redirect(url('index/user/applications_info', ['id' => $app_id]));
        }
        $order['certificate_img'] = unserialize($order['certificate_img']);
        $order['diploma'] = unserialize($order['diploma']);
        $order['transcript'] = unserialize($order['transcript']);
        $order['hand_diploma'] = unserialize($order['hand_diploma']);
        $order['hand_transcript'] = unserialize($order['hand_transcript']);
        $order['prove_file'] = unserialize($order['prove_file']);
        $order['start_date'] = strtotime($order['start_date']) < 0 ? '' : $order['start_date'];
        $order['completion_date'] = strtotime($order['completion_date']) < 0 ? '' : $order['completion_date'];
        $order['qujianimg'] = '';
        if (empty($order['certificate_img'])) {
            $order['certificate_img'] = $user['certificate_img'];
        }
        if (!empty($order['area_code'])) {
            $order['qujianimg'] = Db::name('mobile')->where('qujian', '+' . $order['area_code'])->value('imgs');
        } else {
            $order['area_code'] = $user['qujian'];
            $order['qujianimg'] = Db::name('mobile')->where('qujian', '+' . $user['qujian'])->value('imgs');
        }
        // 判断签名图片是否还存在服务器上
        $sign_img = '.' . $order['sign_img'];
        if (!is_file($sign_img)) {
            $user_order['sign_img'] = '';
        }
        $edu_nationality = $order['edu_nationality'];
        $edu_country_code = WorldArea::getCodeByNmae($edu_nationality);
        $edu_province_code = WorldArea::getCodeByNmae($order['edu_province'], 'state');
        $edu_provinces = WorldArea::getProvinces($edu_country_code);
        $edu_cities = WorldArea::getCities($edu_province_code, 'province');
        $schools = Db::name('schools')->where(['country_name' => $edu_nationality])->select();
        $this->assign(compact('edu_provinces', 'edu_cities', 'schools'));

        $username = explode(" ", $user['username']);
        if (preg_match('/^[\x7f-\xff]+$/', $user['username'])) {
            $user['lastname'] = !empty($username[0]) ? $username[0] : "";;
            $user['middlename'] = !empty($username[1]) ? $username[1] : "";
            $user['firstname'] = !empty($username[2]) ? $username[2] : "";
        } else {
            $user['firstname'] = !empty($username[0]) ? $username[0] : "";
            $user['middlename'] = !empty($username[1]) ? $username[1] : "";
            $user['lastname'] = !empty($username[2]) ? $username[2] : "";
        }
        $default_address = [];
        $user_address = Db::name('user_address')->where('user_id', $user_id)->find();
        $order_address_keys = ['mail_nationality', 'mail_province', 'mail_city', 'address', 'mobile', 'zip', 'addressee',];
        foreach ($order_address_keys as $val) {
            $default_address[$val] = $order[$val];
        }
        $address = array_filter($default_address);
        if (empty($address) && $user_address) {
            $default_address = [
                'mail_nationality' => $user_address['national'],
                'mail_province' => $user_address['province'],
                'mail_city' => $user_address['city'],
                'mail_county' => $user_address['county'],
                'address' => $user_address['detailed'],
                //    'area_code' => $user['qujian'],
                'mobile' => $user['mobile'],
                'zip' => $user_address['zip'],
                'addressee' => $user_address['addressee'],
            ];
        }
        $country = Db::name('cs_world_area_country')->where('alias', 'egt', 1)->select();
        $provices = [];
        if (!empty($default_address['mail_nationality'])) {
            $country_code = Db::name('cs_world_area_country')
                ->where('name_en', $default_address['mail_nationality'])
                ->value('code');
            $provices = Db::name('cs_world_area_state')
                ->where("country_code", $country_code)
                ->where('name|name_en', 'neq', '000')
                ->select();
        }
        if (empty($provices)) {
            $code = Db::name('cs_world_area_country')->where('name_en|name', $default_address['mail_nationality'])->value('code');
            $code .= '000';
        } else {
            $code = Db::name('cs_world_area_state')->where('name_en|name', $default_address['mail_province'])->value('code');
        }
        $cities = Db::name("cs_world_area_city")->where("state_code", $code)->select();
        $this->assign(compact('country', 'provices', 'cities', 'default_address'));
        $step_ok = explode(',', $order['step_ok']);
        $step_error = explode(',', $order['step_error']);
        $embassy = Db::name('embassy')->where(['status' => 1])->select();
        $express = Db::name('express')->where(['status' => 1])->select();
        if (empty($order['document_group']) && empty($order['document_type']) && empty($order['id_no'])) {
            $order['document_group'] = $user['document_group'];
            $order['document_type'] = $user['document_type'];
            $order['id_no'] = $user['id_number'];
        }
        return $this->fetch($step['step_url'], compact('user_address', 'app', 'steps', 'order', 'user', 'step_ok', 'step_error', 'sort', 'mobiles', 'embassy', 'express'));
    }

    /**  保存订单数据
     * @param $app_id
     * @param $sort
     * @return array|Json|void
     */
    public function save_upload($app_id, $sort)
    {
        if (!request()->isPost()) {
            return status_code(10012);
        }

        return $this->save_next($app_id, $sort + 1);
    }
 

    /**
     * 订单提交下一步
     * @param $app_id
     * @param $sort
     * @return array|Json|void
     */
    public function save_next($app_id, $sort)
    {
        $user_id = $this->user_id;
        $appStep = new AppStep();
        $user_order = $this->orderModel->findData(['app_id' => $app_id, 'user_id' => $user_id], '*', 'create_time desc');
        $user = model('common/User')->find($user_id);
        $stepCon = new Step($app_id, $user_order);
        $data = $this->request->param();
        $data['username'] = $user['username'];
        $data['nationality'] = $user['nationality'];
        if (!empty($data['completion_date'])) {
            $data['completion_date'] = dateFormatParse($data['completion_date'], 'd-m-Y', 'Y-m-d');
        }
        if (!empty($data['issued_date'])) {
            $data['issued_date'] = dateFormatParse($data['issued_date'], 'd-m-Y', 'Y-m-d');
        }
        $previous = $appStep->findData(['app_id' => $app_id, 'sort' => ($sort - 1), 'status' => 1]);
        $data['order_id'] = $user_order['id'];
        $data['user_id'] = $user_id;
        if (!$previous) {
            $returnData = status_code(10003)->getData();
            return $this->error($returnData['msg']);
        }
        isset($data['certificate_img']) ? $data['certificate_img'] = serialize($data['certificate_img']) : '';
        isset($data['transcript']) ? $data['transcript'] = serialize($data['transcript']) : '';
        isset($data['diploma']) ? $data['diploma'] = serialize($data['diploma']) : '';
        isset($data['hand_transcript']) ? $data['hand_transcript'] = serialize($data['hand_transcript']) : '';
        isset($data['hand_diploma']) ? $data['hand_diploma'] = serialize($data['hand_diploma']) : '';
        isset($data['credential']) ? $data['credential'] = serialize($data['credential']) : '';
        isset($data['prove_file']) ? $data['prove_file'] = serialize($data['prove_file']) : '';
        // 判断是否上传签名图片，对base64文件进行处理
        if (isset($data['sign_img'])) {
            $old_sign_img = $user_order['sign_img'];
            $up_dir = './uploads' . DS . 'sign' . DS;//存放在当前目录的upload文件夹下
            $sign_img = saveBase64File($data['sign_img'], $up_dir);
            if (!$sign_img) {
                return status_code(10005);
            }
            if ($user_order['sign_img'] != $sign_img || empty($order_detail['protocol'])) {
                // 生成协议PDF
                $content = get_pact($app_id, $sort - 1);
                $outFile = "./protocol/" . md5(uniqid(true)) . '.pdf';
                if (!empty($order_detail['protocol'])) {
                    $outFile = '.' . $order_detail['protocol'];
                }
                $protocol = protocol($content, $sign_img, $outFile);
                if (!$protocol) {
                    return status_code(10005);
                }
                $data['protocol'] = trim($protocol, '.');
            }
            $data['sign_ip'] = $this->get_ip();
            $data['sign_img'] = trim($sign_img, '.');
            if ($old_sign_img != $data['sign_img'] && file_exists('.' . $old_sign_img) && !empty($old_sign_img)) {
                unlink('.' . $old_sign_img);
            }
        }
        //判断是否上传代理唯一编码(教育服务编码)，添加绑定时间       判断是否已使用过的code
        if (!empty($data['edu_code'])) {
            $ageex = Db::name('agency_code')->where('code', $data['edu_code'])->find();
            if (empty($ageex)) {
                return status_code(10404, 'The current education service number does not exist!');
            }
            if ($ageex['ban']==1) {
                return status_code(10404, 'The current education service number does not exist!');
            }
            if (($ageex['use_id'] > 0) && ($ageex['use_id'] != $user_order['id'])) {
                return status_code(10404, 'The current education service number does not exist!');
            }
            $agency = model('Agency')->where('id', $ageex['age_id'])->find();
            $data['agency_id'] = $agency['id'];
            $data['bind_time'] = date('Y-m-d H:i:s');
        }
        $scape = !empty($data['data_state']) ? $data['data_state'] : 'Complete';
        $step_data = $stepCon->stepEdit($previous['id'], $scape);
        if ($stepCon->isComplete()) {
            $step_data['status'] = 1;
        } else {
            $step_data['status'] = 0;
        }
        $order_data = array_merge($data, $step_data);
        $order_data['update_time'] = date('Y-m-d H:i:s');
        if (\request()->isGet()) {
            if ($sort > 1) {
                $result_order = $this->orderModel->refresh(['id' => $user_order['id']], $order_data);
                if ($result_order) {
                    return $this->redirect(url('index/user/app_step_info', ['app_id' => $app_id, 'sort' => $sort]));
                } else {
                    $returnData = status_code(10003)->getData();
                    return $this->error($returnData['msg']);
                }
            }
        } elseif ($this->request->isPost()) {
            Db::startTrans();
            try {
                $user_data = $address_data = $user_edu_data = $data;
                $user_order_result = $this->orderModel->allowField(true)->save($order_data, ['id' => $user_order['id']]);
                $order_detail_data = Order::detailData($data);
                $order_detail_result = $this->orderDetail->refresh(['order_id' => $user_order['id']], $order_detail_data);
                $user_result = true;
                if (!empty($data['edu_code'])) {
                    Db::name('agency_code')->where('code', $data['edu_code'])->setField('use_id', $user_order['id']);
                }
                $user_address_result = true;
                if (!empty($data['is_userupdata']) && $data['is_userupdata']) {
                    $user_result = $this->userModel->allowField(true)->save($user_data, ['id' => $user_id]);
                    $user_address_result = $this->userAddress->refresh(['user_id' => $user_id], $address_data);
                }
                if ($user_result || $user_address_result || $user_order_result || $order_detail_result) {
                    Db::commit();
                    $url = url('index/user/app_step_info', [
                        'app_id' => $app_id,
                        'sort' => $sort
                    ]);
                    return status_code(20000, '', $url);
                } else {
                    Db::rollback();
                    return status_code(10005);
                }
            } catch (Exception $exception) {
                return status_code(10005, 'json', $exception->getMessage());
            }
        }
    }


    public function usercode_bind()
    {   //用户绑定code
        $data = $this->request->param();
        if (($data['code'] == '') || ($data['orderid'] <= 0)) {
            return status_code(10005, "{:lang('The current education service number does not exist!')}");
        }
        $ageex = Db::name('agency_code')->where('code', $data['code'])->find();
        $user_order = Db::name('user_order')->where('id', $data['orderid'])->find();
        if (empty($ageex)) {
            return status_code(10401, "{:lang('The current education service number does not exist!')}");
        }
        if ($ageex['ban']==1) {
            return status_code(10405, "{:lang('The current education service number does not exist!')}");
        }
        if (($ageex['use_id'] > 0) && ($ageex['use_id'] != $user_order['id'])) {
            return status_code(10404, "{:lang('The current education service number does not exist!')}");
        }
        return status_code(20000, '{:lang("Code can use")}');// 可用 不是绑定
        // $agency = model('Agency')
        //     ->where('id', $ageex['age_id'])
        //     ->where('status', 1)
        //     ->find();

        // if (empty($agency)) {
        //     return status_code(10401, "{:lang('The current education service number does not exist!')}");
        // }
        // $savedata = [];
        // $savedata['agency_id'] = $agency['id'];
        // $savedata['bind_time'] = date('Y-m-d H:i:s');
        // $savedata['edu_code'] = $data['code'];

        // Db::startTrans();
        // try {
        //     $agency_code_result = Db::name('agency_code')->where('code', $data['code'])->setField('use_id', $user_order['id']);
        //     $user_order_result = $this->orderModel->allowField(true)->save($savedata, ['id' => $user_order['id']]);
        //     if ($agency_code_result && $user_order_result) {
        //         Db::commit();
        //         return status_code(20000, '{:lang("Binding Succeeded")}');
        //     }
        //     Db::rollback();
        //     return status_code(10005, "{:lang('Binding Failed')}");
        // } catch (Exception $e) {
        //     Db::rollback();
        //     return status_code(10005, $e->getMessage());
        // }
    }

    /**
     * 最后一步订单提交
     * @param $app_id
     * @param $sort
     */
    public function user_order_sign($app_id, $sort)
    {
        $data = $this->request->param();
        $user_order = $this->orderModel->where('id', $data['user_order_id'])->find();
        if ($user_order['resign'] == 1) {
            if (isset($data['sign_img'])) {
                $old_sign_img = $user_order['sign_img'];
                $up_dir = './uploads' . DS . 'sign' . DS;//存放在当前目录的upload文件夹下
                $sign_img = saveBase64File($data['sign_img'], $up_dir);
                if (!$sign_img) {
                    return status_code(10005);
                }
                if ($user_order['sign_img'] != $sign_img || empty($order_detail['protocol'])) {
                    // 生成协议PDF
                    $content = get_pact($app_id, $sort);
                    $outFile = "./protocol/" . md5(uniqid(true)) . '.pdf';
                    if (!empty($order_detail['protocol'])) {
                        $outFile = '.' . $order_detail['protocol'];
                    }
                    $protocol = protocol($content, $sign_img, $outFile);
                    if (!$protocol) {
                        return status_code(10005);
                    }
                    $data['protocol'] = trim($protocol, '.');
                }
                $data['sign_ip'] = $this->get_ip();
                $data['sign_img'] = trim($sign_img, '.');
            }
            $user_order_result = $this->orderModel->allowField(true)->save($data, ['id' => $user_order['id']]);
            Db::name('user_order_detail')->where('order_id', $data['user_order_id'])->setField('protocol', $data['protocol']);
            if ($user_order_result) {
                Db::name('user_order')->where('id', $data['user_order_id'])->setField('resign', 0);
                $url = url('index/user/applications_info', ['id' => $user_order['app_id']]);
                return json(array('data' => $url, 'code' => 20000));
            }
            return json(array('code' => 10005));
        }
        $result_data = $this->save_next($app_id, $sort + 1);
        if (gettype($result_data) == 'object') {
            $result_data = $result_data->getData();
        }


        if ($result_data['code'] == 20000) {
            if (1 == $user_order['status']) {
                $url = url('index/user/application_pay', ['order_no' => $user_order['order_number']]);
            } else {
                $url = url('index/user/applications_info', ['id' => $user_order['app_id']]);
            }
            $result_data['data'] = $url;
        }
        return json($result_data);
    }

    //删除补充资料图片信息
    public function miss_img_del()
    {
        $user_id = Session::get('user_id');
        if (empty($user_id)) {
            $this->redirect('/index/user/log');
        }
        if ($this->request->isPost()) {
            $data = $this->request->param();
            //dump($data);
            if (isset($data['miss_img'])) {
                $data['miss_img'] = serialize($data['miss_img']);
            } else {
                $data['miss_img'] = '';
            }
            $result = Db::name('user_upload')->where('uid', $user_id)->update(
                $data
            );
            if ($result) {
                $this->success('Delete success');
            } else {
                $this->error('Delete error');
            }
        }
    }

    //删除其他第三方信息
    public function other_third_del()
    {
        $user_id = Session::get('user_id');
        if (empty($user_id)) {
            $this->redirect('/index/user/log');
        }
        if ($this->request->isPost()) {
            $data = $this->request->param();
            //dump($data);
            if (isset($data['other_third'])) {
                $data['other_third'] = serialize($data['other_third']);
            } else {
                $data['other_third'] = '';
            }
            $result = Db::name('user_order')
                ->where('id', $data['user_order_id'])
                ->update($data);
            if ($result) {
                $this->success('Delete success');
            } else {
                $this->error('Delete error');
            }
        }
    }

    public function apply_all()
    {
        $user_id = Session::get('user_id');
        if (empty($user_id)) {
            $this->redirect('/index/user/log');
        }
        $user = UserModel::get($user_id);
        $upload = Db::name('user_upload')->where('uid', $user_id)->find();
        $third = Db::name('third')->where('status', 1)->whereNotIn('id', [24, 36, 42, 44, 167, 154])->orderRaw('rand()')->Limit(9)->select();
        return $this->fetch('apply_all', ['user' => $user, 'upload' => $upload, 'third' => $third,]);
    }

    public function apply_all_verify()
    {
        $user_id = Session::get('user_id');
        if (empty($user_id)) {
            $this->redirect('/index/user/log');
        }
        $order = $this->orderModel->where(['user_id' => $user_id, 'app_id' => ['in', '3,5']])->where('status', 7)->find();
        if ($order) {
            return status_code(20000, '/apply_select');
        }
        return status_code(10001);
    }

    public function apply_introduction()
    {
        $user_id = Session::get('user_id');
        if (empty($user_id)) {
            $this->redirect('/index/user/log');
        }
        $user = UserModel::get($user_id);
        $upload = Db::name('user_upload')->where('uid', $user_id)->find();
        //dump($user);die;
        return $this->fetch(
            'apply_introduction',
            [
                'user' => $user,
                'upload' => $upload,
            ]
        );
    }

    public function apply_how()
    {
        $user_id = Session::get('user_id');
        if (empty($user_id)) {
            $this->redirect('/index/user/log');
        }
        $user = UserModel::get($user_id);
        $upload = Db::name('user_upload')->where('uid', $user_id)->find();
        //dump($user);die;
        return $this->fetch(
            'apply_how',
            [
                'user' => $user,
                'upload' => $upload,
            ]
        );
    }

    public function apply_select()
    {
        $user_id = Session::get('user_id');
        if (empty($user_id)) {
            $this->redirect('/index/user/log');
        }
        $map=array();
        $map['user_id']=$user_id;
        //$map['third_id']=14;
        //$map['order_id']=$param['oid'];
        //$exists=Db::name('user_tran')->where($map)->order('create_time desc,id desc')->find();
        $exists = Db::name('user_tran')->where($map)->column('third_id');
            //->order('create_time desc') ->whereTime('create_time','>','-700 days')
        $cc = 0;
        // if ($exists) {
        //     if ($exists['create_time']) {
        //         $a = strtotime($exists['create_time']);
        //         $a += 604800;
        //         if ($a > time()) {
        //             $cc = 1;  // 不到7天
        //         }
        //     }
        // } 
        $this->assign('exists', json_encode($exists));
        $user = UserModel::get($user_id);
        $orders = Db::name('user_order')->where('o.user_id', $user_id)->where('o.status', 'egt', 3)->wherein('o.app_id', [3])
          ->alias('o')
          ->join('user_order_detail d', 'd.order_id = o.id', 'left')
          ->select();
        $orderinfo =   array();  
        if ($orders) {
            $orderinfo =   $orders[0];
            if ($orderinfo['id_no']=='' ) {
                Db::name('user_order_detail')->where('order_id', $orderinfo['order_id'])->setField('id_no', $user['id_number']);
            }
        }
        $third = Db::name('third')->where('status', 1)->where('third_country', 4)->select();
        $guijia = Db::name('third')->Distinct(true)->column('third_country');
        $sss = Db::name('country')->whereIn('id', $guijia)->column(['id', 'name', 'cname', 'name_jp', 'name_de', 'name_kr', 'name_fa', 'name_ru',]);
        $mobiles = Db::name('mobile')->group('qujian')->select(); // 去重
        foreach ($mobiles as $key => &$value) {
            $value['qujian'] = trim($value['qujian'], '+');
        }
        return $this->fetch('apply_select', ['user' => $user, 'mobiles' => $mobiles, 'third' => $third, 'order' => $orderinfo, 'guojias' => $sss, 'orders' => $orders,]);
    }
    public function get_user_oauth_arguments(){
        $user_id = Session::get('user_id');
        if (empty($user_id)) {
            $this->error('请登录后重试',url('/index/user/log'));
        }
        $userinfo=Db::name('user')->where('id','eq',$user_id)->field('id,email')->find();
        if (empty($userinfo)) {
            $this->error('请登录后重试',url('/index/user/log'));
        }
        $oid=request()->request('oid');
        if(!is_numeric($oid)||$oid<=0){
            $this->error('非法参数');
        }
        $map=array();
        $map['user_id']=$user_id;
        $map['order_id']=$oid;
        $order_detail_info=Db::name('user_order_detail')->where($map)->field('degree')->find();
        if(empty($order_detail_info)||empty($order_detail_info['degree'])){
            $this->error('非法学历等级');
        }
        $sendModel = new \app\common\controller\SendLxw();
        $edu_level=(string) $sendModel->get_degree($order_detail_info['degree']);
        $OauthAccess=new \app\common\util\OauthAccess('lxw_user');
        $extra=array();
        $extra['third_userinfo']=array('email'=>$userinfo['email'],'edu_level'=>$edu_level);

        $order_detail = model('common/UserOrder')->where(['user_id'=>$user_id,'app_id' =>['in','3,5']])->with('orderDetail')->order('app_id')->find();
        $order_detail = $order_detail['order_detail'];
        $d_type=model('common/UserOrderDetail')->get_documet_type_key($order_detail['document_group'],$order_detail['document_type']);
        $extra['extra']=array(
            'numbers'=>($order_detail['id_no']?$order_detail['id_no']:$userinfo['id_number']),
            'document_group_id'=>$d_type['key1'],
            'document_type_id'=>$d_type['key2']
        );

        $OauthAccess->set_extra($extra);
        $state=array('action'=>'transmission_authorization','param'=>array('oid'=>$oid));
        $OauthAccess->set_state($state);
        $authorize_arguments=$OauthAccess->get_authorize_arguments();
        $this->success('查询成功',$authorize_arguments['url'],diy_urlencode($authorize_arguments['data']),$authorize_arguments['error']);
    }
    public function search_authorization_notify_status(){
        $user_id = Session::get('user_id');
        if (empty($user_id)) {
            $this->error('请登录后重试',url('/index/user/log'));
        }
        $userinfo=Db::name('user')->where('id','eq',$user_id)->field('id,email')->find();
        if (empty($userinfo)) {
            $this->error('请登录后重试',url('/index/user/log'));
        }
        $oid=request()->request('oid');
        if(!is_numeric($oid)||$oid<=0){
            $this->error('非法参数');
        }
        $third_id=request()->request('third_id');
        if(!is_numeric($third_id)||$third_id<=0){
            $this->error('非法参数');
        }
        $map=array();
        $map['user_id']=$user_id;
        $map['third_id']=$third_id;
        $map['order_id']=$oid;
        $user_tran_info=Db::name('user_tran')->where($map)->order('create_time desc,id desc')->find();
        if (empty($user_tran_info)) {
            $this->error('添加数据传输任务中');
        }
        if(in_array($user_tran_info['lxw_send'],array(6,8))){
            //上一条传输记录已完成
            $this->error('添加数据传输任务中');
        }/*elseif(in_array($user_tran_info['lxw_send'],array(3,5,7))&&strtotime($user_tran_info['create_time'])>0){
            //上一条传输记录已失败，并且已经过了等待期
            $this->error('添加数据传输任务中');
        }*/
        $this->success('添加数据传输任务完成');
    }

    /**
     * 应用支付项展示
     * @param $order_no  订单号
     */
    public function application_pay($order_no)
    {
        $user_id = $this->user_id;
        $user = UserModel::get($user_id);
        $order = $this->orderModel->with('orderDetail')->where(['order_number' => $order_no])->find();
        if (empty($order)) {
            abort(404, 'The order does not exist');
        }
        $edu_code = $order['edu_code'] ?? '';
        $use_id = $order['id'] ?? 0;
        $map = [
            'code' => $edu_code,
        ];
        $deduction = false;
        $find_code = Db::name('agency_code')->where($map)->find();
        if (!empty($find_code) && $find_code['use_id'] == $use_id) {
            $agency = model('common/Agency')->where('status', 1)->find($find_code['age_id']);
            if (!empty($agency)) {
                $deduction = true;   
            }
        }
        $order_detail = $order['order_detail'];
        $name = getNameLang($this->lang);
        $app = Db::name('applications')->field("*,{$name} as name")->find($order['app_id']);
        if (empty($app)) {
            abort(404, 'Applications don\'t exist');
        }
        if ($order['status'] >= 3) {
            abort(404, 'The order has been paid, please do not pay twice');
        }
        $step_errs = array_filter(explode(',', trim($order['step_error'], ',')));
        if (!empty($step_errs)) {
            $appStep = new AppStep();
            $step = $appStep->find($step_errs[0]);
            $sort = $step['sort'];
            return $this->redirect(url('index/user/app_step_info', ['app_id' => $order['app_id'], 'sort' => $sort, 'data_error' => 'unfinished',]));
        }
        $computation = Order::calculateOrder($order['id']);  //计算订单费用
        if (gettype($computation) == 'object') {
            $computation = $computation->getData();
        }
        if ($computation['code'] != 20000) {
            $this->error($computation['msg']);
        }
        $protocol = $order_detail['protocol'];
        $master_order_no = ''; 
        if ($order['app_id'] == 3) { 
            if ( is_fee_school($order_detail['university']) ) {
                return $this->fetch('application_school', [ 'user' => $user, 'deduction' =>  $deduction,'app' => $app, 'order' => $order, 'data' => $computation['data'], 'protocol' => $protocol]);
            }
        }

        $money  =Db::name('system')->where('id', 29)->value('value'  );
        if ( $order['edu_code']==''  and $order['online']==1 and $order['app_id']==3 ) {
            $computation['data']['total']['order_fee']+=  $money ;  
            $computation['data']['total']['tax_fee']+=  round($money*0.13 );  
            $computation['data']['total']['fee']+=   round($money*1.13 );  
            return $this->fetch('application_online', [ 'user' => $user, 'deduction' => false,'app' => $app, 'order' => $order, 'data' => $computation['data'], 'protocol' => $protocol]);
        }
        if ($order['app_id'] == 19) {  //当前信息修改
            $master_order_no = model('common/UserOrder')->where('id', $order['master_order_id'])->value('order_number');
        }
        return $this->fetch('application_pay', ['master_order_no' => $master_order_no, 'user' => $user, 'deduction' => $deduction,'app' => $app, 'order' => $order, 'data' => $computation['data'], 'protocol' => $protocol, 'sum_money1' => $money, 'sum_money2' => round($money*0.13 ), 'sum_money3' => round($money*1.13 )]);
    }

    public function transfer()
    {
        return $this->fetch('apply');
    }

    public function apply_order()
    {
        $user_id = Session::get('user_id');
        if (empty($user_id)) {
            $this->redirect('/index/user/log');
        }
        $user = UserModel::get($user_id);
        $tlist = Db::name('user_tran')->where('user_id', $user_id)->select();
        $order = model('common/UserOrder')->with(['orderDetail'])->where('user_id', $user_id)->where('app_id', 3)->find();
        return $this->fetch('apply_order', ['user' => $user, 'tlist' => $tlist, 'order' => $order]);
    }

    public function applications_cps()
    {
        return $this->fetch();
    }

    public function applications_lists_immigration_programs()
    {
        return $this->fetch();
    }

    public function applications_iehp()
    {
        return $this->fetch();
    }

    public function applications_eca_renew()
    {
        return $this->fetch();
    }

    public function privacy()
    {
        $this->assign('tip', 1);
        return $this->fetch();
    }

    public function getschools()
    {
        $post = input('post.national');
        $ziduans = 'name_en';
        $think_var = Cookie::get('think_var');
        if ($think_var == 'zh-cn') {
            $ziduans = 'name_cn';
        }
        $info = Db::name('cs_world_area_country')->where('code', $post)->find();
        $city = Db::name('cs_schools')->where('gj', $info['name'])->select();
        $opt = '<option>--Choose--</option>';
        foreach ($city as $key => $val) {
            $opt .= "<option value='{$val[$ziduans]}'  >{$val[$ziduans]}</option>";
        }
        echo json_encode($opt);
    }

    public function getschoolss()
    {
        $post = input('post.national');
        $gj = get_world_area('country', $post, 'name');
        $city = Db::name('cs_schools')->where('gj', $gj)->select();
        $opt = '<option>--Choose--</option>';
        foreach ($city as $key => $val) {
            $opt .= "<option value='{$val['name_en']}'  >{$val['name_en']}</option>";
        }
        echo json_encode($opt);
    }

    public function getthird()
    {
        $post = input('post.pro_id');
        $ziduans = 'third_ename';
        $think_var = Cookie::get('think_var');
        if ($think_var == 'zh-cn') {
            $ziduans = "third_cname";
        } else {
            if ($think_var == 'ja-jp') {
                $ziduans = "third_jname";
            } else {
                if ($think_var == 'de-de') {
                    $ziduans = "third_dname";
                } else {
                    if ($think_var == 'ko-kr') {
                        $ziduans = "third_kname";
                    } else {
                        if ($think_var == 'ru-ru') {
                            $ziduans = "third_rname";
                        } else {
                            if ($think_var == 'fr-fa') {
                                $sub_title = "third_fname";
                            }
                        }
                    }
                }
            }
        }
        $city = Db::name('third')->where('status', 1)->where('third_country', $post)->select();
        $opt = '';
        foreach ($city as $key => $val) {
            $opt .= "<option value='{$val['id']}'  >{$val[$ziduans]}</option>";
        }
        echo json_encode($opt);
    }

    public function getthirdinfo()
    {
        $post = input('post.id');
        $info = Db::name('third')->where('id', $post)->find();
        if ($info) {
            $think_var = Cookie::get('think_var');
            if ($think_var == 'zh-cn') {
              $info['namess'] =  $info['third_cname'];
            } else {
              $info['namess'] =  $info['third_ename'];
            }
        }
        return $info;
    }

    public function third_save()
    {
        $user_id = Session::get('user_id');
        if ($user_id) {
            if ($this->request->isPost()) {
                // 获取数据data
                $data = $this->request->param('', '', 'trim');

                $data['user_id'] = $user_id;
                $data['create_time'] = date("Y-m-d H:i:s");
                $data['create_ip'] = parent::get_ip();

                $map=array();
                $map['user_id']=$user_id;
                $map['third_id']=$data['third_id'];
                $map['order_id']=$data['order_id'];
                $user_tran_info=Db::name('user_tran')->where($map)->order('create_time desc,id desc')->find();
                if (!empty($user_tran_info)) {
                    if(in_array($user_tran_info['lxw_send'],array(6,8))){
                        //todo  //上一条传输记录已完成
                    }/*elseif(in_array($user_tran_info['lxw_send'],array(3,5,7))&&strtotime($user_tran_info['create_time'])>0){
                        //todo  //上一条传输记录已失败，并且已经过了等待期
                    }*/else{
                        if($user_tran_info['lxw_send']==0){
                            $data['id']=$user_tran_info['id'];
                        }else{
                            //无需添加传输记录
                            return 1;
                        }
                    }
                }

                if(isset($data['id'])&&$data['id']>0){
                    $status = Db::name('user_tran')->where('id','eq',$data['id'])->update($data);
                }else{
                    $status = Db::name('user_tran')->insert($data);
                }
                if ($status) {
                    $msg = 1;
                } else {
                    $msg = 0;
                }
                return $msg;
            }
        } else {
            $this->redirect('/index/user/log');
        }
    }


    public function orderinfo()
    {
        $user_id = $this->user_id;
        $transcript = model('common/Transcript')->where('user_id', $user_id)->count();
        $certificates = model('common/Certificate')->where('user_id', $user_id)->count();
        $certificate_number = $transcript + $certificates;
        $this->assign(compact('certificate_number'));
        return $this->fetch();
    }

    public function userinfo()  //判断资料是否完整
    {
        $user_id = $this->user_id;
        $user = $this->userModel->find($user_id);
        if (empty($user)) {
            return status_code(10004);
        }
        if ($user['info_start'] == 3) {
            return status_code(20000);
        }
        return status_code(10070);
    }

    public function remind()  // 取消弹出
    {
        $user_id = Session::get('user_id');
        if ($user_id) {
            $result = Db::name('user')->where('id', $user_id)->update(['open_start' => 1]);
            return $result;
        } else {
            $this->redirect('/index/user/log');
        }
    }

    public function storageList($show)
    {
        $user_id = $this->user_id;
        $user = UserModel::get($user_id);
        $assessments = model('UserOrder')->with(['certificate', 'transcript', 'orderDetail'])->where('user_id', $user_id)->select();
        $information_deposit_size = 0;
        $deposit_size = 0;
        foreach ($assessments as $key => $val) {
            if (empty($val['certificate']) && empty($val['transcript'])) {
                unset($assessments[$key]);
            } else {
                if ($val['certificate']['certificate_png']!='') {
              
                foreach ($val['certificate']['certificate_png'] as $certificate_png) {
                    if (file_exists($certificate_png)) {
                        $deposit_size += filesize($certificate_png);
                    }
                }  }
                if ($val['transcript']['certificate_png']!='') {
                foreach ($val['transcript']['certificate_png'] as $transcript_png) {
                    if (file_exists($transcript_png)) {
                        $deposit_size += filesize($transcript_png);
                    }
                }}
                $certificate_pdf_size = 0;
                $transcript_pdf_size = 0;
                if (file_exists($val['certificate']['certificate_path'])) {
                    $certificate_pdf_size = filesize($val['certificate']['certificate_path']);
                }
                if (file_exists($val['transcript']['certificate_path'])) {
                    $transcript_pdf_size = filesize($val['transcript']['certificate_path']);
                }
                $deposit_size += $certificate_pdf_size + $transcript_pdf_size;
            }
            if (is_array($val['order_detail']['diploma'])) {
                foreach ($val['order_detail']['diploma'] as $diploma) {
                    $diploma =  ('.' . trim($diploma, '.'));
                    if (file_exists($diploma)) {
                        $information_deposit_size += filesize( $diploma );
                    }
                }
            }
            if (is_array($val['order_detail']['transcript'])) {
                foreach ($val['order_detail']['transcript'] as $transcript) {
                     $transcript =  ('.' . trim($transcript, '.'));
                    if (file_exists($transcript)) {
                        $information_deposit_size += filesize( $transcript) ;
                    }
                }
            }
            $val['deposit_size'] = transformB($deposit_size);
            $val['information_deposit_size'] = transformB($information_deposit_size);
        }
        $this->assign(compact('user', 'show', 'assessments'));
        return $this->fetch('');
    }

    public function orderinfo_translation()
    {
        $user_id = Session::get('user_id');
        $user = UserModel::get($user_id);
        $this->assign('user', $user);
        return $this->fetch();
    }

    public function orderinfo_query_history()
    {
        $user_id = $this->user_id;
        $list = Db::name('query_log')->where('user_id', $user_id)->select();
        $this->assign('list', $list);
        return $this->fetch();
    }

    public function revisions()   //显示修改列表页面
    {
        $user_id = $this->user_id;
        $revisions = Db::name('revision')->where(['user_id' => $user_id])->select();
        $this->assign(compact('revisions'));
        return $this->fetch();
    }

    public function orderinfo_sharing()
    {
        Session::set('authorization_link', url('index/user/orderinfo_sharing'));
        $user_id = $this->user_id;
        $orders = model('common/UserOrder')->with('orderDetail')->where(['user_id' => $user_id, 'status' => 7])->select();
      
        $originalTranscripts = [];       // 原始成绩单
        $originalDiplomas = [];
        foreach ($orders as $val) {
            if (!empty($val['orderDetail']['transcript'])) {
                $secret = lockString(serialize(['id' => $val['id'], 'type' => 'transcript']));
                $originalTranscripts[] = Share::structure($val['orderDetail']['transcript'], $secret, false);
            }
            if (!empty($val['orderDetail']['diploma'])) {
                $secret = lockString(serialize(['id' => $val['id'], 'type' => 'diploma']));
                $originalDiplomas[] = Share::structure($val['orderDetail']['diploma'], $secret, false);
            }
        }

        $certificates = model('Certificate')->where('user_id', $user_id)->field('id,certificate_png')->select();
        $credentials = [];
        foreach ($certificates as $val) {
            if (!empty($val['certificate_png'])) {
                $certificatePng = $val['certificate_png'];
                $secret = lockString(serialize(['id' => $val['id'], 'type' => 'certificate']));
                $credentials[] = Share::structure($certificatePng, $secret, false);
            }
        }

        $transcripts = model('Transcript')->where('user_id', $user_id)->field('id,certificate_png')->select();
        $reports = [];
        foreach ($transcripts as $val) {
            if (!empty($val['certificate_png'])) {
                $certificatePng = $val['certificate_png'];
                $secret = lockString(serialize(['id' => $val['id'], 'type' => 'report']));
                $reports[] = Share::structure($certificatePng, $secret, false);
            }
        }
        $usermail = Db::name('user')->where(['id' => $user_id])->value('email');
        $this->assign(compact('reports', 'credentials', 'usermail','user_id', 'originalDiplomas', 'originalTranscripts'));
        return $this->fetch();
    }

    /**
     * Notes: 修改邮箱页面
     * Date: 2020/9/15  14:36
     * @return array|mixed|Json|void
     */
    public function email_modification()
    {
        $user_id = $this->user_id;
        $user = model('User')->find($user_id);
        if (empty($user)) {
            if (\request()->isAjax()) {
                return status_code(10004, 'The current user does not exist or has been disabled');
            }
            return $this->error(lang('The current user does not exist or has been disabled'));
        }
        $this->assign(['email' => $user['email']]);
        return $this->fetch();
    }

    /**
     * Notes: 获取邮箱验证码
     * Date: 2020/9/15  14:36
     * @param  string  $email  邮箱
     * @param  string  $type
     * @return array|Json
     */
    public function getEmailCode($email, $type = 'Original')
    {
        $user_id = $this->user_id;
        // 原始邮箱验证
        $user = model('user')->find($user_id);
        if (empty($user)) {
            return status_code(10004);
        }
        $rule = ['email' => 'email|unique:user'];
        $msg = [
            'email.email' => 'please enter your vaild email',
            'email.unique' => 'Email has been used',
        ];
        $data = ['email' => $email, 'id' => $user_id];
        if ($type == 'New') {
            unset($data['id']);
        }
        $vaildate = new Validate($rule, $msg);
        $valicate_result = $vaildate->check($data);
        if (!$valicate_result) {
            return status_code(10003, $vaildate->getError());
        }
        $code = mt_rand(111111, 999999);
        $key = "{$type}_{$email}_{$user_id}_code";
        $mailer = Mailer::instance();
        config('template.view_path', '');
        $sendResult = $mailer->to($email, $user['username'])->subject('E-mail verification')->view('index@email/email_verify_code', ['code' => $code])->send();
        if ($sendResult) {
            Cache::set($key, $code, 60);
            return status_code(20000);
        }
        return status_code(13000);
    }

    /**
     * Notes: 邮箱修改
     * Date: 2020/9/15  14:37
     * @return array|Json
     */
    public function replaceEmail()
    {
        $user_id = $this->user_id;
        $user = model('user')->find($user_id);
        if (empty($user)) {
            return status_code(10004);
        }
        $data = \request()->post();
        $rules = [
            'orig_email' => 'require|email',
            'new_email' => 'require|email',
            'orig_code' => 'require|number|length:6',
            'new_code' => 'require|number|length:6',
        ];
        $msg = [
            'orig_email' => 'Please enter the correct original email address',
            'new_email' => 'Please enter the correct new email',
            'orig_code' => 'Please enter the 6-digit verification code',
            'new_code' => 'Please enter the 6-digit verification code',
        ];
        $validate = new Validate($rules, $msg);
        $data['user_id'] = $user_id;
        if (strtolower($data['orig_email'] == strtolower($data['new_email']))) {
            return status_code(10003, 'The new mailbox cannot be the same as the old mailbox');
        }
        $isExist = model('common/User')->where('email', $data['new_email'])->count();
        if ($isExist > 0) {
            return status_code(10003, 'Email has been used, please change and try again');
        }
        $validate_result = $validate->check($data);
        if (!$validate_result) {
            return status_code(10003, $validate->getError());
        }
        $orig_code = Cache::get("Original_{$data['orig_email']}_{$user_id}_code");
        $new_code = Cache::get("New_{$data['new_email']}_{$user_id}_code");
        if (empty($orig_code) || $data['orig_code'] != $orig_code) {
            return status_code(10003, 'Verification code expired or verification code error');
        }
        if (empty($new_code) || $data['new_code'] != $new_code) {
            return status_code(10003, 'Verification code expired or verification code error');
        }
        $result = model('User')->where('id', $user_id)->setField('email', $data['new_email']);
        if ($result) {
            Session::delete('user_id');
            return status_code(20000, 'Mailbox modified successfully', ['url' => url('index/user/log')]);
        }
        return status_code(10005, 'Email modification failed, please try again later');
    }

    /**
     * Notes:个人主页数据修改
     * Date: 2020/9/15  14:35
     * @return array|Json
     */
    public function saveInfo()
    {
        $user_id = $this->user_id;
        $data = \request()->post();

        $require_fields = ['first_name', 'last_name', 'birth_time', 'nationality', 'sex', 'document_group', 'document_type', 'id_number', 'qujian', 'mobile', 'head_img', 'certificate_img', 'national', 'city', 'detailed', 'zip']; // 必填字段
        $notSubmits = [];
        $isComplete = true;
        foreach ($require_fields as $val) {
            if (empty($data[$val])) {
                $notSubmits[] = $val;
                $isComplete = false;
            }
        }
        $info_status = $isComplete ? 2 : 1;
        $data['info_start'] = $info_status;
        $data['certificate_img'] = empty($data['certificate_img']) ? '' : serialize($data['certificate_img']);
        if (!empty($data['birth_time'])) {
            $data['birth_time'] = dateFormatParse($data['birth_time'], 'd-m-Y', 'Y-m-d');
        } else {
            $data['birth_time'] = null;
        }
        $data['qujian'] = trim($data['qujian'], '+');
        if (!empty($data['head_img'])) {
            if ( substr($data['head_img'],0,8) =='/uploads') {
               unset( $data['head_img']  );
            }else   {
               $data['head_img'] = save64img($data['head_img']);
            }
        }
        Db::startTrans();
        $data['username'] = trim($data['first_name']) . ' ' . trim($data['moddle_name']) . ' ' . trim($data['last_name']);
        try {
            $user_result = model('User')->allowField(true)->save($data, ['id' => $user_id]);
            $address = model('UserAddress')->where('user_id', $user_id)->find();
            if (!$address) {
                $data['user_id'] = $user_id;
                $address_result = model('UserAddress')->allowField(true)->save($data);
            } else {
                $address_result = model('UserAddress')->allowField(true)->save($data, ['user_id' => $user_id]);
            }
            if ($user_result !== false && $address_result !== false) {
                Db::commit();
                $msg = $isComplete ? 'Save successfully' : 'The information is saved successfully, but the information is not filled in, please complete it in time!';
                return status_code(20000, $msg, ['notSubmit' => $notSubmits, 'info_status' => $info_status]);
            } else {
                Db::rollback();
                return status_code(20000, 'Save failed');
            }
        } catch (Exception $exception) {
            Db::rollback();
            return status_code(10005, $exception->getMessage());
        }
    }


    public function updata_sign($app_id = 3, $sort = 6)
    {
        $user_id = $this->user_id;
        $lang = $this->lang;
        $user = UserModel::get($user_id);
        $result = AppModel::get($app_id);
        if (empty($user)) {
            $this->redirect(url('index/user/add'));
        }
        $app = $this->appModel->with('steps')->field("*")->find($app_id);
        $steps = $app->steps()->where('status', 1)->order('sort', 'asc')->select();
        $step = [];
        if (!$steps) {
            $steps = [];
        } else {
            $steps = $steps->toArray();
        }
        $step_key = array_search($sort, array_column($steps, 'sort'));
        if ($step_key !== false) {
            $step = $steps[$step_key];
        }
        $user_order = Db::name('user_order')->where(['user_id' => $user_id, 'app_id' => $app_id])->order('create_time DESC')->find();
        $order = Db::name('user_order')->alias('o')
            ->join('applications a', 'o.app_id=a.id', 'left')
            ->join('user_order_detail d', 'd.order_id = o.id', 'left')
            ->where('o.user_id', $user_id)
            ->where('o.app_id', $app_id)
            ->field('d.*,d.id as detail_id,o.*')
            ->order('o.create_time DESC')
            ->find();
        // 判断签名图片是否还存在服务器上
        $sign_img = '.' . $order['sign_img'];
        if (!is_file($sign_img)) {
            $user_order['sign_img'] = '';
        }
        $step_ok = explode(',', $order['step_ok']);
        $step_error = explode(',', $order['step_error']);
        return $this->fetch($step['step_url'], compact('app', 'steps', 'order', 'user', 'step_ok', 'step_error', 'sort'));
    }

    /**
     * 修改教育订单数据
     * @param  int  $sort
     * @return mixed
     */
    public function modification_of_education_order_data($sort = 1)
    {
        $user = UserModel::get($this->user_id);
        $this->assign(compact('user'));
        return $this->jump_application_designation_method(19, $sort);
    }

    /**
     * Notes: 展示订单数据
     */
    private function applications_update2()
    {
        $orders = model('UserOrder')
            ->where('user_id', $this->user_id)
            ->where('app_id', 'in', [3, 5, 11])
            ->where('status', 'egt', 3)
            ->select();
        // 主订单

        $this->assign('orders', $orders);
        return $this->fetch('applications_update2');
    }

    /**
     * Notes: 评估数据更新页面数据渲染 copy_data_display
     */

    private function applocations_data_update_step2()
    {
        $order_number = input('order_no');
        $user = UserModel::get($this->user_id);
        // 原始订单
        $order = model('UserOrder')->alias('o')
            ->with('orderDetail')
            ->join('applications a', 'o.app_id=a.id', 'left')
            ->join('user_order_detail d', 'd.order_id = o.id', 'left')
            ->where('o.order_number', $order_number)
            ->field('d.*,d.id as detail_id,o.*')
            ->order('o.create_time DESC')
            ->find();
        if (\request()->isPost()) {
            $data = \request()->post();
            // 修改订单
            $change_order = model('common/UserOrder')
                ->where('user_id', $this->user_id)
                ->where('master_order_id', $order['id'])
                ->where('status', 'lt', 7)
                ->find();

            if (empty($change_order)) {
                $app = Db::name('applications')->find(19);
                $fee = $app['fee'];
                if ($order['update_price'] != $fee) {
                    $fee = $order['update_price'];
                }
                $order_no = 'E' . $app['short_name'] . rand('100000', '999999') . time();
                $change_order_data = [
                    'app_id' => 19,
                    'user_id' => $this->user_id,
                    'order_number' => $order_no,    //订单编号
                    'app_name' => $app['name_cn'],
                    'name_en' => $app['name_en'],
                    'fee' => $fee,
                    'basic_fee' => $fee,
                    'create_time' => date('Y-m-d H:i:s'),
                    'create_ip' => parent::get_ip(),
                    'status' => 1,
                    'master_order_id' => $order['id'],
                ];
                $res = Db::name('user_order')->insert($change_order_data);
                if (!$res) {
                    $order_no = '';
                }
            } else {
                $res = Db::name('user_order')->where('id', $change_order['id'])->setField('status', 2);
                $order_no = $change_order['order_number'];
                if ($res === false) {
                    $order_no = '';
                }
            }
            $update_order = Db::name('user_order')->where('order_number', $order_no)->find();
            if (empty($order_no) || empty($update_order)) {
                return status_code(10500, 'Operation Failed!');
            }
            $raw_data = [];
            // 可修改项原数据
            if (!empty($order['order_detail'])) {
                $order_detail = $order['order_detail']->toArray();
                foreach ($data as $index => $val) {
                    if (isset($order_detail[$index])) {
                        $raw_data[$index] = $order_detail[$index];
                    } else if (isset($order[$index])) {
                        $raw_data[$index] = $order[$index];
                    } else if (isset($user[$index])) {
                        $raw_data[$index] = $user[$index];
                    }
                }
            }

            if (!empty($data)) {
                foreach ($data as $key => $val) {
                    if (in_array($key, ['certificate_img', 'diploma', 'transcript', 'hand_diploma', 'hand_transcript'])) {
                        if (empty($val)) {
                            $data[$key] = $val;
                        } else {
                            $arr = explode(',', $val);
                            $data[$key] = $arr;
                        }
                    }
                }
            }

            unset($data['order_no']);
            unset($data['sort']);
            $revised_data = $data;
//            ksort($raw_data);
//            ksort($revised_data);
            $revised_data['certificate_img'] = empty($revised_data['certificate_img']) ? [] : $revised_data['certificate_img'];
            $revised_data['diploma'] = empty($revised_data['diploma']) ? [] : $revised_data['diploma'];
            $revised_data['transcript'] = empty($revised_data['transcript']) ? [] : $revised_data['transcript'];
            $revised_data['hand_diploma'] = empty($revised_data['hand_diploma']) ? [] : $revised_data['hand_diploma'];
            $revised_data['hand_transcript'] = empty($revised_data['hand_transcript']) ? [] : $revised_data['hand_transcript'];
            // 暂时将待修改数据写入缓存
            //Cache::set($key, serialize($data), 30 * 60);
            $order_update_record = Db::name('order_update_record')->where('order_id', $update_order['id'])->find();
            $record = [
                'raw_data' => serialize($raw_data),
                'revised_data' => serialize($revised_data),
            ];

            if (empty($order_update_record)) {
                $record['original_order_id'] = $order['id'];
                $record['create_time'] = date('Y-m-d H:i:s');
                $record['order_id'] = $update_order['id'];
                $record['create_ip'] = get_ip();
                $record['uid'] = $this->user_id;
                $result = Db::name('order_update_record')->insert($record);
            } else {
                $record['update_time'] = date('Y-m-d H:i:s');
                $record['update_ip'] = get_ip();
                $result = Db::name('order_update_record')->where('order_id', $update_order['id'])->update($record);
            }
            if (!$result) {
                return status_code(10500, 'Operation Failed!');
            }
            $change_data = self::get_modified_data($order_no);
            // 判断数据是否进行修改
            if (count($change_data) == 0) {
                return status_code(10500, 'The data has not been modified!');
            }
            return status_code(20000, 'Success!', url('index/user/modification_of_education_order_data', ['order_no' => $order_no, 'sort' => 3]));
        }

        $order['certificate_img'] = unserialize($order['certificate_img']);
        $order['diploma'] = unserialize($order['diploma']);
        $order['transcript'] = unserialize($order['transcript']);
        $order['hand_diploma'] = unserialize($order['hand_diploma']);
        $order['hand_transcript'] = unserialize($order['hand_transcript']);
        $order['prove_file'] = unserialize($order['prove_file']);
        $order['start_date'] = strtotime($order['start_date']) < 0 ? '' : $order['start_date'];
        $order['completion_date'] = strtotime($order['completion_date']) < 0 ? '' : $order['completion_date'];
        // 教育数据
        $edu_nationality = $order['edu_nationality'];
        $edu_country_code = WorldArea::getCodeByNmae($edu_nationality);
        $edu_province_code = WorldArea::getCodeByNmae($order['edu_province'], 'state');
        $edu_provinces = WorldArea::getProvinces($edu_country_code);
        $edu_cities = WorldArea::getCities($edu_province_code, 'province');
        $schools = Db::name('schools')->where(['country_name' => $edu_nationality])->select();

        $subjects = Db::name('subject')->where('sort', 0)->select(); //专业存入缓存
        // 专业相关数据处理
        if (Cache::has('majorslist')) {
            $majors = Cache::get('majorslist');
        } else {
            $majors = Db::name('schools_speciality')->field('id,name,name_en') ->select();
            $majors = array_map(function ($val) {
                $val['name_en'] = ucwords(strtolower($val['name_en']));
                return $val;
            }, $majors);
            $majors = uniqueArr($majors, 'name_en');
            $majors = uniqueArr($majors, 'name');
            $majors = arraySort($majors, 'name_en');
            Cache::set('majorslist', $majors,604800);
        }

        $this->assign(compact('edu_provinces', 'edu_cities', 'schools', 'subjects', 'majors'));
        // 快递地址
        $country = Db::name('cs_world_area_country')->where('alias', 'egt', 1)->select();
        $provices = [];
        if (empty($provices)) {
            $code = Db::name('cs_world_area_country')->where('name_en|name', $order['mail_nationality'])->value('code');
            $code .= '000';
        } else {
            $code = Db::name('cs_world_area_state')->where('name_en|name', $order['mail_province'])->value('code');
        }
        $cities = Db::name("cs_world_area_city")->where("state_code", $code)->select();
        $this->assign(compact('country', 'provices', 'cities'));
        if (empty($order['certificate_img'])) {
            $order['certificate_img'] = $user['certificate_img'];
        }
        $this->assign('order', $order);
        return $this->fetch('applocations_data_update_step2');
    }

    /**
     * Notes: 获取修改数据
     * @param  string  $order_no
     * @return array
     */
    private static function get_modified_data($order_no)
    {
        $order = model('common/UserOrder')->where('order_number', $order_no)->find();
        $record = Db::name('order_update_record')->where('order_id', $order['id'])->find();
        $raw_data = unserialize($record['raw_data']) == false ? [] : unserialize($record['raw_data']);
        $revised_data = unserialize($record['revised_data']) == false ? [] : unserialize($record['revised_data']);
        $data = [];
        $fields = [
            'username' => 'Username',
            'sex' => 'Gender',
            'email' => 'Mailbox',
            'document_group' => 'Document group',
            'document_type' => 'Document type',
            'id_no' => 'License Number',
            'university' => 'Name of academic institution',
            'located' => 'Educational location',
            'university_type' => 'University nature',
            'degree' => 'Certificate/Diploma/Degree',
            'faculty' => 'Research areas',
            'major' => 'Profession',
            'start_date' => 'Admission time',
            'completion_date' => 'Graduation time',
            'graduated' => 'Whether to graduate',
            'certificate_img' => 'ID picture',
            'diploma' => 'Upload Degree Certificate',
            'transcript' => 'Upload Transcript',
            'hand_diploma' => 'Handheld Diploma',
            'hand_transcript' => 'Handheld Transcript',
        ];
        if ($raw_data['degree'] && $raw_data['degree_program']) {
            $raw_data['degree'] = $raw_data['degree'] . ' of ' . $raw_data['degree_program'];
        }
        if ($revised_data['degree'] && $revised_data['degree_program']) {
            $revised_data['degree'] = $revised_data['degree'] . ' of ' . $revised_data['degree_program'];
        }

        if (isset($raw_data['edu_nationality'], $raw_data['edu_province'], $raw_data['edu_city'])) {
            $raw_data['located'] = '';
            $raw_data['located'] .= $raw_data['edu_nationality'] . ', ';
            if ($raw_data['edu_province'] !== '') {
                $raw_data['located'] .= $raw_data['edu_province'] . ', ';
            }
            $raw_data['located'] .= $raw_data['edu_city'];
        }

        if (isset($revised_data['edu_nationality'], $revised_data['edu_province'], $revised_data['edu_city'])) {
            $revised_data['located'] = '';
            $revised_data['located'] .= $revised_data['edu_nationality'] . ', ';
            if ($revised_data['edu_province'] !== '') {
                $revised_data['located'] .= $revised_data['edu_province'] . ', ';
            }
            $revised_data['located'] .= $revised_data['edu_city'];
        }
        if (isset($revised_data['university_type'])) {
            switch ($revised_data['university_type']) {
                case 1:
                    $revised_data['university_type'] = 'Public University';
                    break;
                case 2:
                    $revised_data['university_type'] = 'Private University';
                    break;
                case 3:
                    $revised_data['university_type'] = 'Language School';
                    break;
                case 4:
                    $revised_data['university_type'] = 'Preparatory School';
                    break;
                default:
                    $revised_data['university_type'] = 'Other School';
            }
        }

        foreach ($revised_data as $index => $val) {
            $vars = [];
            $vars['title'] = $fields[$index] ?? 'Other Information';
            $vars['before'] = $raw_data[$index] ?? '';
            $vars['after'] = $val;
            if (in_array($index, ['edu_nationality', 'edu_province', 'edu_city','degree_program'])) {
                continue;
            }
            if (is_array($vars['before']) && is_string($vars['after']))
                if (gettype($vars['before']) != gettype($vars['after'])) {
                    array_push($data, $vars);
                    continue;
                }

            if (is_string($vars['before'])) {
                if (strtolower($vars['before']) != strtolower($vars['after'])) {
                    array_push($data, $vars);
                }
            } else {
                if ($vars['before'] != $vars['after']) {
                    array_push($data, $vars);
                }
            }

        }
        return $data;
    }

    /**
     * Notes: 数据修改第3步
     * @return mixed
     */
    private function second_review()
    {
        $order_no = input('order_no');
        $order = model('common/UserOrder')->where('order_number', $order_no)->find();
        $data = self::get_modified_data($order_no);
        $master_order_no = model('common/UserOrder')->where('id', $order['master_order_id'])->value('order_number');
        $this->assign(compact('data', 'master_order_no', 'order'));
        return $this->fetch('second_review');
    }

    /**
     * Notes: 订单数据同步修改
     * @param  string  $order_id  订单id
     * @return array|Json
     */
    public static function data_sync_to_the_master_order($order_id)
    {
        $order = model('common/UserOrder')->find($order_id);
        if (empty($order)) {
            return status_code(10400, 'The order does not exist!');
        }
        $record = Db::name('order_update_record')->where('order_id', $order_id)->order('update_time')->find();
        if (empty($record)) {
            return status_code(15000, 'There is no data to modify!');
        }
        $revised_data = unserialize($record['revised_data']);
        if ($revised_data === false) {
            Log::write('数据格式错误');
            return status_code(15000, 'Data format error!');
        }
        foreach ($revised_data as $key => $vo) {
            if (is_array($vo)) {
                $revised_data[$key] = serialize($vo);
            }
        }
        Db::startTrans();
        try {
//            dump($revised_data);die();
            // 订单主表数据修改
            model('common/UserOrder')
                ->allowField(true)
                ->save($revised_data, ['id' => $record['original_order_id']]);
            // 订单详情数据修改
            model('common/OrderDetail')
                ->allowField(true)
                ->save($revised_data, ['order_id' => $record['original_order_id']]);
            $user_data = [];
            if (isset($revised_data['sex'])) {
                $user_data['sex'] = $revised_data['sex'];
            }
            if (!empty($user_data)) {
                model('common/User')
                    ->allowField(true)
                    ->save($user_data, ['id' => $order['user_id']]);
            }
            // 订单状态修改
            model('common/UserOrder')
                ->where('id', $order_id)
                ->setField('status', 7);
            Db::commit();
            return status_code(20000, 'Update completed!');
        } catch (Exception $e) {
//            var_dump('File: ' . $e->getFile() . '. Line: ' . $e->getLine() . '. Msg: ' . $e->getMessage());die();
            Db::rollback();
            Log::write('File: ' . $e->getFile() . '. Line: ' . $e->getLine() . '. Msg: ' . $e->getMessage(), 'error');
            return status_code(15000, 'File: ' . $e->getFile() . '. Line: ' . $e->getLine() . '. Msg: ' . $e->getMessage());
        }
    }


    public function user_copy_application($sort = 1)
    {
        $user = UserModel::get($this->user_id);
        $this->assign(compact('user'));
        return $this->jump_application_designation_method(8, $sort);
    }

    private function select_education()
    {
        $orders = model('common/UserOrder')
            ->where('user_id', $this->user_id)
            ->where('app_id', 'in', [3, 5, 18])
            ->with('orderDetail')
            ->select();  
        $user_id = $this->user_id;
        $lang = $this->lang;
        $user = UserModel::get($user_id);
        $this->assign(['orders' => $orders]);
        return $this->fetch('select_education', compact('user'));
    }

    public function file_preview($order_no='')
    {
        $data = $this->request->param();
        if (empty($data['order_no'])) {
            $this->redirect(url('/'));
        }
        $user_id = $this->user_id;
        $order = model('common/UserOrder')->where('order_number', $data['order_no'])->find();
        if (empty($order )) {
            abort(500, 'The page does not exist!');
        }
        // $transcript = model('common/Transcript')->where('order_id', $order['id'])->find();
        // $certificates = model('common/Certificate')->where('order_id', $order['id'])->find();
        // if (!empty( $certificates['certificate_png'])) {
        //     $certificates['certificate_png']= trim($certificates['certificate_png'][0], '.');
        // }
       //     dump($certificates['certificate_png'] );die(); 
        //dump($certificates['certificate_png']);die(); 
        //$certificates['certificate_png'] = unserialize($certificates['certificate_png']); //都是一张
  //  dump($transcript['certificate_png']);die(); 
        //$transcript['certificate_png'] = unserialize($transcript['certificate_png']);//可能是多张
      
        $tran = Db::name('transcript_credential')->where('order_id', $order['id'])->find(); //可能是多张
        $tran['certificate_png'] = unserialize($tran['certificate_png']);
        if (!empty($tran['certificate_png'])) {
            foreach ($tran['certificate_png'] as $key => &$value) {
                $value = trim($value, '.');
            }
        }
        $certificate = Db::name('certificate')->where('order_id', $order['id'])->find(); // 都是一张
        $certificate['certificate_png'] = unserialize($certificate['certificate_png']);
        if (!empty($certificate['certificate_png'])) {
            foreach ($certificate['certificate_png'] as $key => &$value) {
                $value = trim($value, '.');
            }
        }
        $this->assign('oid',$order['id']);
        $this->assign('order_no', $data['order_no']);
        $this->assign(compact('tran', 'certificate' ));

        return $this->fetch('file_preview');
    }

    /**
     * Notes: 跳转应用的指定方法
     * @param int $app_id 应用id
     * @param int $sort 步骤id
     * @return mixed
     */
    private function jump_application_designation_method($app_id, $sort)
    {
        $app = model('common/Applications')->find($app_id);
        $steps = Db::name('app_step')->where('app_id', $app_id)->where('status', 1)->select();

        $step_method = '';
        if (!empty($steps)) {
            $sorts = array_column($steps, 'sort');
            $key = array_search($sort, $sorts);
            if ($key !== false) {
                $current_step = $steps[$key];
                $step_method = $current_step['step_url'];
            }
        }
        if (!method_exists($this, $step_method)) {
            abort(500, 'The page does not exist!');
        }
        $step_ok = [1];   
        $step_error = [];
        $this->assign(compact('steps', 'app', 'step_ok', 'step_error', 'sort'));
        return $this->$step_method();
    }


    public function user_copy_select( )
    {
        $user_id = $this->user_id;
        //$user = UserModel::get($this->user_id);
        //$this->assign(compact('user'));
        $v ='';
        $data = $this->request->param(); 
        if ($data['t1']) {
            $v.='certificate,';
        }
        if ($data['t2']) {
            $v.='transcript';  //table name
        }
        $data['v']=$v;
        $result = AppModel::get(8);

        $order = Db::name('user_order')
            ->where('user_id', $user_id)
            ->where('app_id', 8)
            ->where('status','<',7)
            ->where('master_order_id',$data['order_id'])
            ->find();
        $save_data = [
            'app_id' => 8,
            'user_id' => $user_id,
            'order_number' => 'E' . $result['short_name'] . rand('100000', '999999') . time(),    //订单编号
            'app_name' => $result['name_cn'],
            'name_en' => $result['name_en'],
            'fee' => $result['fee'],
            'step_ok' =>  '51,70,69',
            'basic_fee' => $result['fee'],
            'create_time' => date('Y-m-d H:i:s'),
            'create_ip' => parent::get_ip(),
            'master_order_id' => $data['order_id'],  //申请的副本的原id
            'ordr_number' => $v,  //记录需要的东西
            'copy_number'=>1,  //默认为1分
        ];
        Db::startTrans();
        try { 
            if (empty($order)) {
                model('UserOrder')->save($save_data);
            } else{
                model('UserOrder')->save(['ordr_number' => $v], ['id' => $order['id']]);   
            }
            Db::commit();
            $url = url('index/user/copy_apply', [
                'app_id' => 8,
                'sort' => 4
            ]);
            return status_code(20000, '操作成功',$url);
        } catch (Exception $e) {
            Db::rollback();
            return status_code(10005, $e->getMessage());
        }

    }

    public function copy_apply($app_id=8, $sort=4) //新增的 填写付款地址链接
    {
        $user_id = $this->user_id;
        $lang = $this->lang;
        $user = UserModel::get($user_id);
        if (empty($user)) {
            $this->redirect(url('index/user/add'));
        }
        if (!empty($user['birth_time'])) {
            $user['age'] = birthdayToAge($user['birth_time']);
        }
        $result = AppModel::get($app_id);
        $mobiles = Db::name('mobile')->select();
        $app = $this->appModel->with('steps')->field("*")->find($app_id);
        $steps = $app->steps()->where('status', 1)->order('sort', 'asc')->select();
        $step = [];
        if (!$steps) {
            $steps = [];
        } else {
            $steps = $steps->toArray();
        }
        $step_key = array_search($sort, array_column($steps, 'sort'));
        if ($step_key !== false) {
            $step = $steps[$step_key];
        }

        $order = Db::name('user_order')->alias('o')
            ->join('applications a', 'o.app_id=a.id', 'left')
            ->join('user_order_detail d', 'd.order_id = o.id', 'left')
            ->where('o.user_id', $user_id)
            ->where('o.app_id', $app_id)
            ->field('d.*,d.id as detail_id,o.*')
            ->order('o.create_time DESC')
            ->find();
      
        if (!empty($order['area_code'])) {
            $order['qujianimg'] = Db::name('mobile')->where('qujian', '+' . $order['area_code'])->value('imgs');
        } else {
            $order['area_code'] = $user['qujian'];
            $order['qujianimg'] = Db::name('mobile')->where('qujian', '+' . $user['qujian'])->value('imgs');
        }
        $default_address = [];
        $user_address = Db::name('user_address')->where('user_id', $user_id)->find();
        $default_address = [
            'mail_nationality' => $user_address['national'],
            'mail_province' => $user_address['province'],
            'mail_city' => $user_address['city'],
            'mail_county' => $user_address['county'],
            'address' => $user_address['detailed'],
            'mobile' => $user['mobile'],
            'zip' => $user_address['zip'],
            'addressee' => $user_address['addressee'],
        ];
       
        $country = Db::name('cs_world_area_country')->where('alias', 'egt', 1)->select();
        $provices = [];
        if (!empty($default_address['mail_nationality'])) {
            $country_code = Db::name('cs_world_area_country')
                ->where('name_en', $default_address['mail_nationality'])
                ->value('code');
            $provices = Db::name('cs_world_area_state')
                ->where("country_code", $country_code)
                ->where('name|name_en', 'neq', '000')
                ->select();
        }
        if (empty($provices)) {
            $code = Db::name('cs_world_area_country')->where('name_en|name', $default_address['mail_nationality'])->value('code');
            $code .= '000';
        } else {
            $code = Db::name('cs_world_area_state')->where('name_en|name', $default_address['mail_province'])->value('code');
        }
        $cities = Db::name("cs_world_area_city")->where("state_code", $code)->select();
        $this->assign(compact('country', 'provices', 'cities', 'default_address'));
        $step_ok = explode(',', $order['step_ok']);
        $step_error = explode(',', $order['step_error']);
        $embassy = Db::name('embassy')->where(['status' => 1])->select();
        $express = Db::name('express')->where(['status' => 1])->select();
        if (empty($order['document_group']) && empty($order['document_type']) && empty($order['id_no'])) {
            $order['document_group'] = $user['document_group'];
            $order['document_type'] = $user['document_type'];
            $order['id_no'] = $user['id_number'];
        }
        return $this->fetch($step['step_url'], compact('user_address', 'app', 'steps', 'order', 'user', 'step_ok', 'step_error', 'sort', 'mobiles', 'embassy', 'express'));
    }



    public function save_next_apply($app_id, $sort)  //副本 保存收货地址方法    只使用于副本
    {
        $user_id = $this->user_id;
        $user = model('common/User')->find($user_id);
        $data = $this->request->param();
        $user_order = $this->orderModel->findData(['id' => $data['user_order_id'] ]  );
        $data['username'] = $user['username'];         //od info
        $data['nationality'] = $user['nationality'];
        $data['order_id'] = $user_order['id'];
        $data['user_id'] = $user_id;

        $data['step_ok'] ='51,70,68,69';
        if ( $user_order['status']==0) {
            $data['status'] = 1;
        }
        $data['update_time'] = date('Y-m-d H:i:s');
        Db::startTrans();
        try {
            $user_order_result = $this->orderModel->allowField(true)->save($data, ['id' => $data['user_order_id']]);
            $order_detail_data = Order::detailData($data);
            $order_detail_result = $this->orderDetail->refresh(['order_id' => $user_order['id']], $order_detail_data);
            $user_result = true;
            if ($user_result  || $user_order_result || $order_detail_result) {
                Db::commit();
                $url = url('index/user/app_step_info', [
                    'app_id' => $app_id,
                    'sort' => $sort
                ]);
                return status_code(20000, '', $url);
            } else {
                Db::rollback();
                return status_code(10005, 'Save failed');
            }
        } catch (Exception $exception) {
            return status_code(10005, 'json', $exception->getMessage());
        }
    }


     public function save_next_pay()  //step5    进入支付页面
    {
        $user_id = $this->user_id;
        $user = model('common/User')->find($user_id);
        $data = $this->request->param();
     
        $user_order = $this->orderModel->findData(['id' => $data['user_order_id'] ]  );
        if (empty($user_order)) {
            return status_code(10005, 'Save failed');
        }
        $step_ok = explode( ',',$user_order['step_ok']  );
        if (in_array('51', $step_ok) && in_array('68', $step_ok) && in_array('69', $step_ok) && in_array('70', $step_ok)){
            $data['step_ok'] ='51,68,69,70,71';
            $data['status'] = 1;
            $data['update_time'] = date('Y-m-d H:i:s');
            $res = $this->orderModel->allowField(true)->save($data, ['id' => $data['user_order_id']]);
            if ($res) {
                return status_code(20000);
            }
            
        }
        return status_code(10005, 'Save failed');
        // if (strpos($user_order['step_ok'],'51')!==false && strpos($user_order['step_ok'],'68')!==false){
        //     echo "string";
        // }
    }

}
