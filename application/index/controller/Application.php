<?php


namespace app\index\controller;


use app\common\controller\HomeBase;
use app\common\controller\Recaptcha;
use app\common\model\User as UserModel;
use think\Db;
use think\Session;

class Application extends HomeBase
{
    protected $beforeActionList = [
        'getPubApps'
    ];

    public function getPubApps()
    {
        $name = getNameLang($this->lang);
        $applications = Db::name('applications')->field("*, {$name} as name")->where('status', 1)->select(); // 所有应用数据
        $this->applications = $applications;
        $this->assign('applications', $applications);
    }

    public function institutional_apply()
    {
        $user_id = $this->user_id;
        $user  = UserModel::get($user_id);
        $api_status = model('Apiapply')->where(['user_id' => $user_id])->order('create_time desc')->value('status');
        $ins_status = model('Applyins')->where(['user_id' => $user_id])->order('create_time desc')->value('status');
        $api_status = $api_status ?: 0;
        $ins_status = $ins_status ?: 0;
        $this->assign(['user' => $user, 'api_status' => $api_status, 'ins_status' => $ins_status]);
        return $this->fetch();
    }

    public function institutional_application()  //页面1
    {
        $user_id = $this->user_id;
        $apply_status = model('Applyins')->where(['user_id' => $user_id])->order('create_time desc')->value('status');
        $apply_status == null ? 0 : $apply_status;
        if ($apply_status == 1) {
            $this->redirect('index/application/institutional_apply');
        }
        $user = $user = UserModel::get($user_id);
        $mobile_arr = Db::name('mobile')->select();
        $this->assign(['mobile_arr' => $mobile_arr, 'user' => $user, 'apply_status' => $apply_status]);
        return $this->fetch();
    }

    public function saveins()
    {
        $user_id = $this->user_id;
        if (!$this->request->isPost()) {
            return status_code(10012);
        }
        $data = $this->request->param();
        $data['user_id'] = $user_id;
        $data['order_no'] = order_no();
        $data['evidence']  = empty($data['evidence']) ? '' : serialize($data['evidence']);
        $result = model('Applyins')->allowField(true)->save($data);
        if (!$result) {
            return status_code(10005, 'Stored error');
        }
        return status_code(20000, 'Stored successfully');
    }

    public function api()
    {
        $user_id = $this->user_id;
        $apply_status = model('Apiapply')->where(['user_id' => $user_id])->order('create_time desc')->value('status');
        $apply_status == null ? 0 : $apply_status;
        if ($apply_status == 1) {
            $this->redirect('index/application/institutional_apply');
        }
        $user = UserModel::get($user_id);
        $mobile_arr = Db::name('mobile')->select();
        return $this->fetch('api_apply', ['mobile_arr' => $mobile_arr, 'user' => $user, 'apply_status' => $apply_status]);
    }

    public function saveapi()
    {
        $user_id = $this->user_id;
        if (!$this->request->isPost()) {
            return status_code(10012);
        }
        // post提交表单,获取post表单数据
        $data = $this->request->param();
        $data['user_id'] = $user_id;
        $data['order_no'] = order_no();
        $content = file_get_contents('./api/temp.js');
        $content = str_replace('{{$logo}}', $data['imgbase'], $content);
        $script_name = $data['order_no'] . '.js';
        $script_result = file_put_contents("./api/{$script_name}", $content);
        $data['script_src'] = request()->domain() . '/api/' . $script_name;
        if (!$script_result) {
            return status_code(10005, 'Stored error');
        }
        $result = model('Apiapply')->allowField(true)->save($data);
        if (!$result) {
            return status_code(10005, 'Stored error');
        }
        $url = url('index/Application/app_complete', ['token' => $data['order_no']]);
        return status_code(20000, 'Stored successfully', ['url' => $url]);
    }

    public function app_complete($token = '')
    {
        $user_id = Session::get('user_id');
        if (empty($token)) {
            $record = model('Apiapply')->where('user_id', $user_id)->order('create_time desc')->find();
        } else {
            $record = model('Apiapply')->where('order_no', $token)->find();
        }
        if (empty($record)) {
            $this->redirect(url('index/application/api'));
        }
        return $this->fetch('apply_complete', ['record' => $record]);
    }

    public function secure_site()
    {
        
        $REFERER = isset($_SERVER['HTTP_REFERER'])&&!empty($_SERVER['HTTP_REFERER'])?$_SERVER['HTTP_REFERER']:(isset($_SERVER['HTTP_REFERRER'])&&!empty($_SERVER['HTTP_REFERRER'])?$_SERVER['HTTP_REFERRER']:'');
        $url =  parse_url($REFERER);
        
        if (empty($url['host'])) {
            return $this->fetch('open@index/secure_site_no_trust' );
        }
    
        $allow_domain_list = [
            'cscss.com.cn',  'www.cscss.com.cn',  'ku.cscss.com.cn', 'verification.cscss.com.cn', 'www.cscss.cn','jiaoliuren.com','jiaoliuren.cn','www.jiaoliuren.com','www.jiaoliuren.cn',
        ];
        $allow_default_domain_list=get_allow_domain(false);
        $allow_domain_list = is_array($allow_domain_list)?$allow_domain_list:array();
        $allow_domain_list = array_merge($allow_default_domain_list,$allow_domain_list);
        
        if(!in_array($url['host'],$allow_domain_list)){
            return $this->fetch('open@index/secure_site_no_trust' );
        }
        
        return $this->fetch('open@index/secure_site' );
    }

    public function pact($id = 1)
    {
        $res = Db::name('pact')->where('id', $id)->find();
        $this->assign('res', $res);
        return $this->fetch();
    }

    public function cooperative_agency()
    {
        if (request()->isPost()) {
            $data = $this->request->param();
            if (config('verify') == 1) {
                if (empty($data['g-recaptcha-response'])) {
                    return status_code(10001, 'Man-machine verification failed');
                }
                // 人机验证
                $recaptcha = new Recaptcha($data['g-recaptcha-response']);
                $resonse = $recaptcha->siteverify();
                unset($data['g-recaptcha-response']);
                if (!$resonse['success']) {
                    return status_code(10001, 'Man-machine verification failed');
                }
            }
            $username = $data['name'];
            $code = $data['code'];
            $agency = model('common/Agency')
                ->where(['username' => $username, 'unique_code' => $code])
                ->find();
            if (empty($agency)) {
                return status_code(10404, 'Proxy does not exist');
            }
         
            $agency['authorized'] = translate($agency['authorized'], $this->lang);
            return status_code(20000, 'Query success!', $agency);
        }
        return $this->fetch();
    }
    
    public function qahe()
    {
        return $this->fetch();
    }

    public function cscss()
    {
        return $this->fetch();
    }

    public function qahe_pen()
    {
        return $this->fetch();
    }
    
}
