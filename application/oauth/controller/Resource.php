<?php

namespace app\oauth\controller;

class Resource extends Common {

    protected $auth_token = '';

    public function __construct() {
        parent::__construct();
        $this->auth_token = $this->param['auth_token'];
        if ( !$this->check_browse_auth_token($this->auth_token, true)) {
            $msg = $this->getError();
            $msg = $msg ? $msg : '非法操作';
            $this->apiReturn($msg, 'api');
        }
        unset($this->param['auth_token']);
    }

    public function StartCaptchaServlet() {
        $GtSdk  = new \Api\GeetestLib(CAPTCHA_ID, PRIVATE_KEY);
        $data   = array(
            "client_type" => "web",      #web:电脑上的浏览器；h5:手机上的浏览器，包括移动应用内完全内置的web_view；native：通过原生SDK植入APP应用的方式
            "ip_address" => "127.0.0.1", # 请在此处传输用户请求验证时所携带的IP
        );
        $status = $GtSdk->pre_process($data, 1);
        session('gtserver', $status);
        echo $GtSdk->get_response_str();
    }

    public function VerifyLoginServlet() {
        $GtSdk = new \Api\GeetestLib(CAPTCHA_ID, PRIVATE_KEY);
        $data  = array(
            "client_type" => "web",     #web:电脑上的浏览器；h5:手机上的浏览器，包括移动应用内完全内置的web_view；native：通过原生SDK植入APP应用的方式
            "ip_address" => "127.0.0.1" # 请在此处传输用户请求验证时所携带的IP
        );
        if ($_SERVER['HTTP_HOST'] == 'www.cscss.cn') {
            $status = 1;
        } else {
            $status = session('gtserver');
        }
        if ($status == 1) {   //服务器正常
            $result = $GtSdk->success_validate(I('geetest_challenge'), I('geetest_validate'), I('geetest_seccode'), $data);
            if ($result) {
                echo '{"status":"success"}';
            } else {
                echo '{"status":"fail"}';
            }
        } else {  //服务器宕机,走failback模式
            if ($GtSdk->fail_validate($_POST['geetest_challenge'], $_POST['geetest_validate'], $_POST['geetest_seccode'])) {
                echo '{"status":"success"}';
            } else {
                echo '{"status":"fail"}';
            }
        }
    }

    /**
     *解析身份证
     */
    public function getcard() {
        $idcard      = I('post.idcard');
        $data        = card($idcard);
        $area_result = get_region_info($data['region_id']);
        if ($area_result['level'] >= 2) {
            $data['sheng'] = $area_result['province'];
        } else {
            $data['sheng'] = '';
        }
        if ($area_result['level'] >= 3) {
            $data['city'] = $area_result['city'];
        } else {
            $data['city'] = '';
        }
        if ($area_result['level'] >= 4) {
            $data['quxian'] = $area_result['area'];
        } else {
            $data['quxian'] = '';
        }
        $this->ajaxReturn($data);
    }

    /* 图片验证码生成后发送短信验证码，用于登录和注册 */
    public function sendtelverify() {
        $param = I('post.');
        if (empty($param['phone'])) {
            $this->error('手机号码不能为空');
        }
        //$status=getRegistCode($param['phone'],$param['type'],$msg);
        $is_example = get_test_status('user');
        $is_example = $is_example ? true : false;
        $status     = send_code($param, $msg, $is_example);
        if ( !$status) {
            $msg = $msg ? $msg : '发送失败';
            $this->error($msg, '', true);
        }
        $this->success('发送成功', '', true);
    }

    public function get_wse_attestation() {
        $type_list    = array('secure_site', 'infomation_detail');
        $default_type = 'secure_site';
        $param        = $this->param;
        $type         = in_array($param['type'], $type_list) ? $param['type'] : $default_type;
        unset($param['type']);
        $OauthAccess = new \app\common\util\OauthAccess('wse');
        if ($type == 'secure_site') {
            $wse_attestation = $OauthAccess->get_secure_site_arguments();
        } else {
            $wse_attestation = $OauthAccess->get_open_arguments($type, $param);
        }
        $this->apiReturn($wse_attestation, 'api');
    }

}