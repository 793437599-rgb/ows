<?php


namespace app\index\controller;


use app\common\controller\HomeBase;
use mailer\tp5\Mailer;
use think\Cache;
use think\Db;
use think\db\exception\DataNotFoundException;
use think\db\exception\ModelNotFoundException;
use think\Exception;
use think\exception\DbException;
use think\response\Json;
use think\Session;
use think\Validate;

class Share extends HomeBase
{
    /**
     * Notes: 创建分享链接接口

     * Date: 2020/9/10  9:38
     * @return array|Json
     */
    public function create()
    {
        $user_id = $this->user_id;
        $share = [];
        $share['user_id'] = $user_id;
        $share['token'] = uniqid('');
        $share['authority'] = 1;
        $share['secret'] = request()->post('secret');
        if (!$share['secret']) {
            return status_code(10005, 'Failed to create share link');
        }
        $share['expire_time'] = date('Y-m-d H:i:s', strtotime('+10 days'));
        $result = model('Share')->save($share);
        if (!$result) {
            return status_code(10005, 'Failed to create share link');
        }
        return status_code(20000, 'successfully', ['shareLink' => url('index/share/index', ['token' => $share['token']]), 'token' => $share['token']]);
    }

    /**
     * Notes: 更新分享链接接口

     * Date: 2020/9/10  9:38
     * @param $token
     * @return array|Json
     */
    public function store($token)
    {

        $data = request()->post();
        $shareLink = model('Share')->where('token', $token)->find();
        if (empty($shareLink)) {
            return $this->create();
        }
        if (!empty($data['validity'])) {
            $expire = ['+ 10 days', '+ 20 days', '+ 30 days'];
            $validity = (int)$data['validity'];
            if ($validity < 1 || $validity > 3) {
                $validity = 1;
            }
            $data['expire_time'] = date('Y-m-d H:i:s', strtotime($expire[$validity - 1], strtotime($shareLink['create_time'])));
        }
        $assign_emails = [];
        if (!empty($data['email'])) {
            if (!empty($shareLink['assign_emails'])) {
                $assign_emails = explode(',', $shareLink['assign_emails']);
            }
            $type = request()->post('type', 'add');
            if ($type == 'add') {
                if (in_array($data['email'], $assign_emails)) {
                    return status_code(10005, 'This mailbox already exists');
                }
                array_push($assign_emails, $data['email']);
            } else if ($type == 'del') {
                $key = array_search($data['email'], $assign_emails);
                if ($key !== false) {
                    unset($assign_emails[$key]);
                }
            } else {
                return status_code(10003);
            }
            $data['assign_emails'] = trim(implode(',', $assign_emails), ',');
        }
        $result = model('Share')->allowField(true)->save($data, ['token' => $token]);
        if ($result) {
            return status_code(20000, 'successfully', ['shareLink' => url('index/share/index', ['token' => $token]), 'token' => $token, 'emails' => $assign_emails]);
        }
        return status_code(10005, 'Link generation failed');
    }

    /**
     * Notes: 文件预览展示

     * Date: 2020/9/10  15:03
     * @param $str
     * @throws Exception
     */
    public function photo($str)
    {

        $path = unlockString($str, 'photo');
        $referer = empty($_SERVER['HTTP_REFERER']) ? '' : $_SERVER['HTTP_REFERER'];
        if ($referer != Session::get('authorization_link')) {
            throw new Exception('Access forbidden');
        }
//        return showImg($path, 10);
        return imagickShowImage($path);
    }

    /**
     * Notes: 分享展示页

     * Date: 2020/9/11  10:43
     * @param $token
     * @param string $email
     * @return array|mixed|Json
     */
    public function index($token, $email = '')
    {
        $share = model('share')->where('token', $token)->find();
        if (empty($share) || time() > strtotime($share['expire_time'])) {
            abort(404, 'The link does not exist or has expired');
        }

        $data = request()->param();
        // 提交查看申请，记录
        if (request()->isPost()) {
            $verify = Cache::get("share_verify_code_" . $share['token'] . $data['email']);
            if (empty($verify) || $verify != $data['code']) {
                return status_code(10003, 'Incorrect email verification code');
            }
            Cache::rm("share_verify_code_" . $share['token'] . $data['email']);
            Cache::set("share_access_right_{$email}", true);
            return status_code(20000, 'successfully', ['link' => url('index/share/index', compact('token', 'email'))]);
        }

        $access = Cache::get("share_access_right_{$email}");
        if (empty($email) || !$access) {
            return $this->fetch('verify', ['token' => $token, 'email' => $email]);
        }

        Cache::rm("share_access_right_{$email}");
        $secret = $share['secret'];
        $analysis = self::secretAnalysis($secret);
        $share['images'] = array_map(function ($item) {
            return url('index/share/photo', ['str' => lockString('.' . trim($item, '.'), 'photo')]);
        }, $analysis['images']);
        Session::set('authorization_link', url('index/share/index', ['token' => $token, 'email' => $email]));
        $share['days'] = ceil((strtotime($share['expire_time']) - strtotime($share['create_time'])) / (24 * 60 * 60));
        $share['residue'] = ceil((strtotime($share['expire_time']) - time()) / (24 * 60 * 60));

        // 写入记录
        $query = [];
        $query['create_ip'] = request()->ip();
        $query['create_time'] = date('Y-m-d H:i:s');
        $query['search_type'] = 2;
        $query['verify_email'] = $email;
        $query['user_id'] = $analysis['user_id'];
        Db::name('query_log')->insert($query);
        return $this->fetch('index', compact('share'));
    }

    /**
     * Notes: 分享链接邮箱验证码发送接口

     * Date: 2020/9/11  10:41
     * @param $email
     * @return array|Json
     * @throws \mailer\lib\Exception
     */
    public function getMailCode($email)
    {
        $data = request()->post('', '', 'trim');
        $rule = [
            'email' => 'email',
            'token' => 'require'
        ];
        $msg = [
            'email' => 'please enter your vaild email',
            'token' => ''
        ];
        $validate = new Validate($rule, $msg);
        $validate_result = $validate->check($data);
        if (!$validate_result) {
            return status_code(10003, $validate->getError());
        }
        $share = model('share')->where('token', $data['token'])->find();
        if (2 == $share['authority']) {
            $assign_emails = explode(',', $share['assign_emails']);
            if (!in_array($data['email'], $assign_emails)) {
                return status_code(10003, 'No permission, no access');
            }
        }
        $code = mt_rand(111111, 999999);
        $key = "share_verify_code_" . $data['token'] . $email;
        $mailer = Mailer::instance();
        $sendResult = $mailer->to($email)->subject('E-mail verification')->view('share/mail_code', ['code' => $code])->send();
        if ($sendResult) {
            Cache::set($key, $code, 60 * 60);
            return status_code(20000, 'successfully');
        }
        return status_code(10005);
    }

    /**
     * Notes: 邮箱分享功能
     * Date: 2020/9/12  9:56
     */
    public function emailShare_old($email, $secret,$imgage)
    {
        //$email='asdasdsa';
        if ($email == '' ) {
            return status_code(10003, 'Mail   Error');
        }
        $rule = [
            'email' => 'email',
        ]; 
        $msg = [
            'email' => 'please enter your vaild email',
        ];
        $icons = [
            './canada/images/admin_head_img.png',
            './canada/images/BlockHash.png',
            './canada/images/DepositTime.png',
            './canada/images/BlockHeight.png',
            './canada/images/TypeOfDeposit.png',
        ];
        $validate = new Validate($rule, $msg);
        $validate_result = $validate->check(['email' => $email]);
        if (!$validate_result) {
            return status_code(10003, $validate->getError());
        }
 
        $emailType = mailbox_recognition($email);
        $files = self::secretAnalysis($secret);
        $type = $files['type'];
        $flag = false;
        if ( $type == 'report') {
            $flag = true;
        }
        $imgage =  explode(',',$imgage);
        foreach ($imgage as $key => &$value) {
            $value = self::synthesis('.'.$value, $flag);
        }
        $files=$imgage;
        $icons = array_map(function ($icon) use ($emailType){
            if ($emailType == 'gmail'){
                $icon = trim($icon, '.');
                return request()->domain() . '/' . $icon;
            }else{
                return base64EncodeImage($icon);
            }
        },$icons);
        $mailer = Mailer::instance();
        $expire_date = date('Y-m-d',strtotime('+10 days'));
        $sendResult = $mailer->to($email)->subject('E-mail verification')->view('share/mail', compact('files','expire_date','icons'))->send();
        if ($sendResult) {
            $result = Db::name('user')->where('id', $this->user_id)->field('chinaname,username')->find();
            $data['user_id'] = $this->user_id ;
            $data['name'] = $result['chinaname'] ==''? $result['username']  :  $result['chinaname']   ;
            $data['mail'] = $email;
            $data['secret'] = $secret;
            $data['imgage'] = $imgage;
            $data['start'] = 1;
            $data['create_time'] = time();
            $res =  Db::name('email')->insert($data);
            return status_code(20000,'Successful sharing');
        }
        return status_code(10005,'Sharing failed');
    }

    public function emailShare($email, $secret,$imgage)
    {
        if ($email == '' ) {
            return status_code(10003, 'Mail   Error');
        }
        $rule = ['email' => 'email', ]; 
        $msg = [ 'email' => 'please enter your vaild email',];
        $validate = new Validate($rule, $msg);
        $validate_result = $validate->check(['email' => $email]);
        if (!$validate_result) {
            return status_code(10003, $validate->getError());
        }
        $result = Db::name('user')->where('id', $this->user_id)->field('chinaname,username')->find();
        $up_educa['user_id'] = $this->user_id ;
        $up_educa['name'] = $result['chinaname'] ==''? $result['username']  :  $result['chinaname']   ;
        $up_educa['mail'] = $email;
        $up_educa['secret'] = $secret;
        $up_educa['imgage'] = $imgage;
        $up_educa['start'] = 0;
        $up_educa['create_time'] = time();
        $res =  Db::name('email')->insert($up_educa);
        if ($res) {
            return status_code(20000,'Your application has been submitted, please wait for wse review');
        }
        return status_code(10005,'Submitted failed');
    }

    /**
     * Notes: 数据组装

     * Date: 2020/9/10  15:03
     * @param $data
     * @return array
     * @throws \Exception
     */
    public static function structure($data, $secret, $isLock = true)
    {
        if (!is_array($data)) {
            exception('传入数据错误，必须是数组');
        }
        $src = $data;
        if ($isLock) {
            $src = array_map(function ($item) {
                return url('index/share/photo', ['str' => lockString($item, 'photo')]);
            }, $data);
        }else {
            $src = array_map(function ($item) {
                return trim($item,'.');
            }, $data);
        }
        $return = [
            'thumbnail' => $src[0],
            'src' => implode(',', $src),
            'secret' => $secret,
        ];
        return $return;
    }


    /**
     * Notes: 秘钥解析

     * Date: 2020/9/11  10:41
     * @param $secret
     * @return array|mixed
     */
    protected static function secretAnalysis($secret)
    {
        $param = unserialize(unlockString($secret));
        if (empty($param)) {
            return [];
        }
        $analysis = [];
        $analysis['type'] = $param['type'];
        if ($param['type'] == 'certificate') {
            $data = model('common/Certificate')->find($param['id']);
            $analysis['images'] = $data['certificate_png'];
            $analysis['user_id'] = $data['user_id'];
        } elseif ($param['type'] == 'report') {
            $data = model('common/Transcript')->find($param['id']);
            $analysis['images'] = $data['certificate_png'];
            $analysis['user_id'] = $data['user_id'];
        } else {
            $data = model('common/OrderDetail')->where('order_id', $param['id'])->find();
            $analysis['user_id'] = $data['user_id'];
            if ($param['type'] == 'diploma') {
                $analysis['images'] = $data['diploma'];
            } else {
                $analysis['images'] = $data['transcript'];
            }
        }
        return  $analysis;
    }

    /**
     * Notes: 合成图片

     * Date: 2020/9/11  11:42
     * @param $file
     * @return string
     * @throws \ImagickException
     */
    protected static function synthesis($file,$flag = true){
      
        if (!file_exists($file)){
            return '';
        }
        $filename = pathinfo($file,PATHINFO_BASENAME);
        if ($flag) {
            $filepath = "./share/temp/{$filename}";
        } else {
            $filepath = $file;
        }
        $filepath = $file;
        if (!file_exists($filepath) && $flag === true) {
            $note = '../template/images/note.png';
            $IM = new \imagick($file);
            $watermark = new \Imagick($note);
            $IM->compositeImage($watermark, \Imagick::COMPOSITE_OVER, 50, 1260);
            $IM->writeImage($filepath);
            $IM->clear();
            $watermark->clear();
            if (!file_exists($filepath)) {
                return '';
            }
            
        } 
        return base64EncodeImage($filepath);
    }

    public function pay_getcode( )
    {
        $data = request()->param();
        $email = $data['email'];
        if ($data['pay_type']==1 ) {
            $type =  'WECHATPAY'; 
        }else{
            $type =  'ALIPAY'; 
        }
        if ($email == '' ) {
            return status_code(10003, 'Mail   Error');
        }
        if (strpos($email, '.edu') !== false) {  //正则
            return status_code(10005,'The mailbox is not supported');
        }
        $result = Db::name('user')->where('id', $this->user_id)->field('chinaname,username')->find();
        $up_mail['user_id'] = $this->user_id ;
        $up_mail['name'] = $result['chinaname'] ==''? $result['username']  :  $result['chinaname']   ;
        $up_mail['mail'] = $email;
        $up_mail['fee'] =  50;  //50
        if ($this->user_id==152) {
            $up_mail['fee'] =  1;
        }
        $up_mail['secret'] =  $data['secret'];
        $up_mail['imgage'] =  $data['imgage'];
        $up_mail['start'] = 0;
        $up_mail['pay_type'] = $data['pay_type'];
        $up_mail['create_time'] = time();
        $res =  Db::name('email')->insertGetId($up_mail);
        if ($res) {
            $payno = $res. $this->getrandstr() ;
            while (Db::name('usecode')->where('no', $payno)->find()) {
                $payno = $res.$this->getrandstr() ;
            }
            $fee = (string)($up_mail['fee'] * 100);
            $return_data_arr = $this->get_code_mail($fee, $type, $payno);
             if ($return_data_arr === false) {
                return status_code(15000, 'Payment Failure');
            }
            if (@strlen($return_data_arr['code_url']) > 5) {
                //Cache::set('sendno_' . $res . $type, $return_data_arr['code_url'], 480);
                Db::name('usecode')->insert(['no' => $payno, 'code' => $return_data_arr['code_url'], 'create_time' => date('Y-m-d H:i:s')]);
                return status_code(20000, '', $return_data_arr['code_url']);
            }
            return status_code(15000, 'Payment Failure');
        }else{
            return status_code(10005,'Submitted failed');
        }
       
    }


    public function get_code_mail($amount, $type, $no)
    {
        $data_array = array();
        $data_array['amount'] = $amount;
        $data_array['biz_type'] = $type; //3中方法  requst 获取来的数据  ALIPAY  UNIONPAY
        $data_array['operator_id'] = '0000022888'; //using your 10-digital operator number provided by OTTPAY;
        $data_array['order_id'] = $no;   //不同一
        $data_array['call_back_url'] = "https://wse.org/api/upload/mailed"; //   接收到的数据
        $temp_data_array = $data_array;

        ksort($temp_data_array);
        $data_str = implode(array_values($temp_data_array)); //拼接
        $data_md5 = strtoupper(md5($data_str));      //转大写
        $user_key = '7E115684782CE541'; //using your Sign Key provided by OTTPAY;
        $aesKeyStr = strtoupper(substr(md5($data_md5 . $user_key), 8, 16));
        $data_json = json_encode($data_array);
        $encrypted_data = $this->encrypt($data_json, $aesKeyStr);

        $params_array = array();
        $params_array['action'] = 'ACTIVEPAY';
        $params_array['version'] = '1.0';
        $params_array['merchant_id'] = 'ON00006215'; //using your Merchant ID provided by OTTPAY;
        $params_array['data'] = $encrypted_data;
        $params_array['md5'] = $data_md5;
        $params_json = json_encode($params_array, JSON_UNESCAPED_UNICODE);
        $resp_data = $this->sendRequest($params_json, 'https://frontapi.ottpay.com:443/processV2');

        $resp_arr = (array)json_decode($resp_data, true);
        if ($resp_arr['rsp_code'] == 'FAIL') {
            return false;
        }
        $aesKeyStr = strtoupper(substr(md5($resp_arr['md5'] . $user_key), 8, 16));
        $decrypted_data = $this->decrypt($resp_arr['data'], $aesKeyStr);
        $return_data_arr = (array)json_decode($decrypted_data, true);
        return $return_data_arr;
    }
    

    public function encrypt($input, $key)
    {
        return base64_encode(openssl_encrypt($input, 'aes-128-ecb', $key, OPENSSL_RAW_DATA));
    }
    
    public function decrypt($sStr, $sKey)
    {
        return openssl_decrypt($sStr, 'aes-128-ecb', $sKey);
    }

    function sendRequest($data, $url)
    {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
        curl_setopt($ch, CURLOPT_TIMEOUT, 60);
        curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type: application/json', 'Content-length:' . strlen($data)));
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_REFERER, $_SERVER['SERVER_NAME']);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLINFO_HEADER_OUT, true);
        $resp = curl_exec($ch);
        curl_close($ch);
        return $resp;
    }

    public function getrandstr($length = 11)
    {
        $characters = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ';
        $charactersLength = strlen($characters);
        $randomString = '';
        for ($i = 0; $i < $length; $i++) {
            $randomString .= $characters[rand(0, $charactersLength - 1)];
        }
        return $randomString;
    }

    public function pay_status()
    {
        $data = request()->param();
        $res = Db::name('email')->where('user_id',$this->user_id)->where('imgage',$data['imgage'])->where('mail',$data['emails'])->find();
        if (empty($res)) {
            return status_code(12000);
        }

        if ($res['pay_start'] == 1) {
            return status_code(20000, '', url('index/Share/pay_success', ['order_no' => $order['order_number']]));
        } 
        return status_code(12000);
    }

    public function pay_success()
    {
        return $this->fetch('/pay/pay_mail');
    }

}

