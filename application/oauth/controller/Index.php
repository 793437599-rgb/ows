<?php

namespace app\oauth\controller;

class Index extends Common {

    public function __construct(\think\Request $request = null) {
        parent::__construct($request);
        $this->init();
    }

    /**
     * 授权页面和授权
     */
    public function authorize() {
        // 该页面请求地址类似：
        // http://sxx.qkl.local/v2/oauth/authorize?response_type=code&client_id=testclient&state=xyz&redirect_uri=http://sxx.qkl.local/v2/oauth/cb&scope=basic%20get_user_info%20upload_pic
        $response = new \OAuth2\Response();
        $pdo      = $this->server->getStorage('client');
        // 验证 authorize request
        // 这里会验证client_id，redirect_uri等参数和client是否有scope
        $authorize_status = $this->server->validateAuthorizeRequest($this->oauth_request, $response);
        if ( !$authorize_status) {
            $response->send();
            die;
        }
        $OauthClients = model('oauth/OauthClients');
        $result       = $OauthClients->get_info($this->appid);
        // 当然这部分常规是基于自己现有的帐号系统验证
        $is_authorized = $this->checkLogin($this->oauth_request, $openid, $user_id);
        if ($this->client_type == 'user') {
            if ( !$is_authorized) {
                return $this->apiReturn($this->getError(), $this->return_type);
            }
        }
        // 这里是授权获取code，并拼接Location地址返回相应
        // Location的地址类似：http://sxx.qkl.local/v2/oauth/cb?code=69d78ea06b5ee41acbb9dfb90500823c8ac0241d&state=xyz
        // 这里的$uid不是上面oauth_users表的uid, 是自己系统里的帐号的id，你也可以省略该参数
        $this->server->handleAuthorizeRequest($this->oauth_request, $response, $is_authorized, $openid);
        if ( !request()->isPost()) {
            $response->send();
        } else {
            if ($is_authorized) {
                // 这里会创建Location跳转，你可以直接获取相关的跳转url，用于debug
                $location   = $response->getHttpHeader('Location');
                $url_result = parse_url($location);
                parse_str($url_result['query'], $result);
                // 拉取oauth_authorization_codes记录的信息，包含id_token
                /*$code_info = $this->server->getStorage('authorization_code')
                    ->getAuthorizationCode($result['code']);
                if(!empty($code_info['id_token'])){
                    dump($code_info);
                    $id_token=$code_info['id_token'];
                    $KeyStorage=$this->getKeyStorage()->keys;
                    $key=$KeyStorage['public_key'];
                    $jwt=new \OAuth2\Encryption\Jwt();
                    $id_token=$jwt->decode($id_token,$key);
                    dump($id_token);
                    die;
                }*/
                return $this->apiReturn($result, 'api');
            } else {
                $result                      = array();
                $result['error']             = 'consent_required';
                $result['error_description'] = 'The user denied access to your application';
                return $this->apiReturn($result, 'api');
            }
        }
    }

    /**
     * 生成并获取access_token
     * client_id            应用的client_id（ app_id ）
     * client_secret        应用的client_secret（ app_secret ）
     * code                 授权得到的code
     * grant_type           固定字符串：authorization_code
     * redirect_uri         授权回调URI
     */
    public function access_token() {
        // Handle a request for an OAuth2.0 Access Token and send the response to the client
        $this->server->handleTokenRequest($this->oauth_request)->send();
    }

    /**
     * 刷新access_token
     * client_id            应用的client_id（ app_id ）
     * client_secret        应用的client_secret（ app_secret ）
     * refresh_token
     * grant_type           固定字符串：refresh_token
     */
    public function refresh_token() {
        // Handle a request for an OAuth2.0 Access Token and send the response to the client
        $this->server->handleTokenRequest($this->oauth_request)->send();
    }

    public function check_access($param = array()) {
        if ($this->check_action_is_directaccess()) {
            die('Illegal operation');
        }
        if ( !$this->check_scope($param['scope'], $this->client_info['scope'])) {
            return false;
        }
        $grant_type = trim($param['grant_type']);
        if (empty($grant_type)) {
            $grant_type = $this->grant_type ? $this->grant_type : array();
        }
        if (strpos($grant_type, ' ') !== false) {
            $grant_type = explode(' ', $grant_type);
        } else {
            $grant_type = array($grant_type);
        }
        if (in_array('authorization_code', $grant_type)) {
            return $this->check_access_token($param['access_token']);
        } else if (in_array('client_credentials', $grant_type)) {
            return $this->check_client_credentials($param);
        } else {
            $this->error = 'Illegal parameter';
            return false;
        }
    }

    public function check_access_token($access_token = '') {
        if ($this->check_action_is_directaccess()) {
            die('Illegal operation');
        }
        $access_token = $access_token ? $access_token : $this->access_token;
        // 拉取oauth_access_tokens记录的信息
        $access_token_info = $this->server->getStorage('access_token')
            ->getAccessToken($access_token);
        if (empty($access_token_info)) {
            $this->error = 'invalid access_token';
            return false;
        }
        if ($access_token_info['expires'] <= time()) {
            $this->error = 'Expired access_ token';
            //$this -> error = $access_token_info;
            return false;
        }
        // 拉取oauth_clients记录的信息
        $OauthClients      = model('oauth/OauthClients');
        $oauth_client_info = $OauthClients->get_info($access_token_info['client_id']);
        if ( !$this->check_domain($oauth_client_info['allow_domain'], $oauth_client_info['allow_ip'])) {
            return false;
        }
        return $oauth_client_info;
    }

    public function check_client_credentials($param) {
        if ($this->check_action_is_directaccess()) {
            die('Illegal operation');
        }
        if ( !$this->pdo->checkRestrictedGrantType($param['client_id'], $param['grant_type'])) {
            $this->error = 'grant_type error';
            return false;
        }
        if ( !$this->pdo->checkClientCredentials($param['client_id'], $param['client_secret'])) {
            $this->error = 'client_secret error';
            return false;
        }
        // 拉取oauth_clients记录的信息
        $OauthClients      = model('oauth/OauthClients');
        $oauth_client_info = $OauthClients->get_info($param['client_id']);
        if ( !$this->check_domain($oauth_client_info['allow_domain'], $oauth_client_info['allow_ip'])) {
            return false;
        }
        return $oauth_client_info;
    }

    public function auth() {
        $access_token = $this->access_token;
        // 拉取oauth_access_tokens记录的信息
        $access_token_info           = $this->server->getStorage('access_token')->getAccessToken($access_token);
        $result                      = array();
        $result['error']             = '';
        $result['error_description'] = 'access_token ok';
        if (empty($access_token_info)) {
            $result['error']             = 'invalid access_token';
            $result['error_description'] = 'invalid access_token';
            $this->ajaxReturn($result);
        }
        $access_token_endtime = strtotime($access_token_info['expires']);
        if ($access_token_endtime <= time()) {
            $result['error']             = 'Expired access_ token';
            $result['error_description'] = 'The access_ token is expired';
            $this->ajaxReturn($result);
        }
        // 拉取oauth_clients记录的信息
        $OauthClients      = D('Lxkus/OauthClients');
        $oauth_client_info = $OauthClients->get_info($access_token_info['client_id']);
        if ( !$this->check_domain($oauth_client_info['allow_domain'], $oauth_client_info['allow_ip'])) {
            $result['error']             = 'invalid domain or ip';
            $result['error_description'] = 'invalid domain or ip';
            $this->ajaxReturn($result);
        }
        $this->ajaxReturn($result);
    }

    public function show() {
        $response = new \OAuth2\Response();
        $pdo      = $this->server->getStorage('client');
        // 验证 authorize request
        // 这里会验证client_id，redirect_uri等参数和client是否有scope
        $authorize_status = $this->server->validateAuthorizeRequest($this->oauth_request, $response);
        if ( !$authorize_status) {
            $response->send();
            die;
        }
        // 显示授权登录页面
        $param_userinfo = $this->oauth_request->request('userinfo');
        if (empty($param_userinfo)) {
            $this->show_browse_auth_token();
            $this->assign('param', $this->param);
            return $this->fetch('authorize');
        }
        if ( !$this->check_browse_auth_token($this->param['auth_token'], true)) {
            $msg                         = $this->getError();
            $msg                         = $msg ? $msg : '非法操作';
            $result['error']             = 'auth_token_error';
            $result['error_description'] = $msg;
            $this->apiReturn($msg);
        }
        if ($this->referrer != $_SERVER['REQUEST_SCHEME'] . '://' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI']) {
            $result                      = array();
            $result['error']             = 'consent_required';
            $result['error_description'] = 'The user denied access to your application';
            return $this->apiReturn($result);
        }
        // 当然这部分常规是基于自己现有的帐号系统验证
        $is_authorized = $this->checkLogin($this->oauth_request, $openid, $user_id);
        if ($this->client_type == 'user') {
            if ( !$is_authorized) {
                return $this->apiReturn($this->getError());
            }
        }
        // 这里是授权获取code，并拼接Location地址返回相应
        // Location的地址类似：http://sxx.qkl.local/v2/oauth/cb?code=69d78ea06b5ee41acbb9dfb90500823c8ac0241d&state=xyz
        // 这里的$uid不是上面oauth_users表的uid, 是自己系统里的帐号的id，你也可以省略该参数
        $this->server->handleAuthorizeRequest($this->oauth_request, $response, $is_authorized, $openid);
        if ( !$is_authorized) {
            $result                      = array();
            $result['error']             = 'consent_required';
            $result['error_description'] = 'The user denied access to your application';
            return $this->apiReturn($result);
        }
        // 这里会创建Location跳转，你可以直接获取相关的跳转url，用于debug
        $location   = $response->getHttpHeader('Location');
        $url_result = parse_url($location);
        parse_str($url_result['query'], $result);
        //获取oauth_clients表的对应的client应用的数据
        $clientInfo             = $pdo->getClientDetails($this->appid);
        $_POST['client_secret'] = $clientInfo['client_secret'];
        $_POST['grant_type']    = 'authorization_code';
        $_POST['code']          = $result['code'];
        // Handle a request for an OAuth2.0 Access Token and send the response to the client
        //$this->server->handleTokenRequest($this->oauth_request)->send();
        $TokenController = $this->server->getTokenController();
        $oauth_request = \OAuth2\Request::createFromGlobals();
        $token           = $TokenController->grantAccessToken($oauth_request,$response);
        if ($token == false) {
            $result                      = json_decode($response->getResponseBody(),true);
            $result['code']             = $response->getStatusCode();
            $result['code_text']             = $response->getStatusText();
            $this->apiReturn($result);
        }
        if ($token['openid'] != $openid) {
            $result                      = array();
            $result['error']             = 'authorization_failed';
            $result['error_description'] = 'The authorization is failed';
            return $this->apiReturn($result);
        }
        $OauthClients      = model('oauth/OauthClients');
        $oauth_client_info = $OauthClients->get_info($this->appid);
        if ( !empty($oauth_client_info['redirect_uri']) && is_string($oauth_client_info['redirect_uri'])) {
            $result          = !empty($token) && is_array($token) ? $token : array();
            $result['state'] = isset($this->param['state']) && !empty($this->param['state']) ? $this->param['state'] : '';
            $OauthAccess = new \app\common\util\OauthAccess($this->client_type == 'user' ? 'lxw_user' : 'lxw');
            /*if(!empty($oauth_client_info['authorize_domain'])){
                $authorize_domain=$oauth_client_info['authorize_is_ssl']==1?'https://':'http://';
                $authorize_domain=$authorize_domain.$oauth_client_info['authorize_domain'];
                $OauthAccess->set_domain($authorize_domain);
            }
            if($oauth_client_info['need_authorize']!=1){
                $OauthAccess->set_session_key( $this->default_session_key );
            }*/
            if ($this->client_type == 'user') {
                $userinfo       = $this->oauth_request->request('userinfo');
                $third_userinfo = $this->oauth_request->request('third_userinfo');
                unset($userinfo['password']);
                $extra                   = array();
                $extra['userinfo']       = $third_userinfo;
                $extra['third_userinfo'] = $userinfo;
                $OauthAccess->set_extra($extra);
            }
            $status = $OauthAccess->notify($oauth_client_info['redirect_uri'], $result, $user_id, '', $oauth_client_info['need_authorize'] == 1);
            if ($status === false) {
                $result                      = array();
                $result['error']             = 'notify failed';
                $result['error_description'] = $OauthAccess->getError();
                if (is_html($result['error_description']) || preg_match('/<pre>.*<\/pre>/', $result['error_description'])) {
                    echo $result['error_description'];
                    die;
                } else {
                    return $this->apiReturn($result);
                }
            } else if (is_array($status) && $status['status'] != 1) {
                $result                      = array();
                $result['error']             = 'notify failed';
                $result['error_description'] = $result['error_description'] == '' ? $status['data']['error_description'] : $status['error_description'];
                $result['error_description'] = 'callback error：' . $status['data']['error_description'];
                return $this->apiReturn($result);
            }
        }
        $result           = array();
        $result['status'] = 'success';
        return $this->apiReturn($result);
    }

    /**
     * 具体基于自己现有的帐号系统验证
     * @param        $request
     * @param string $openid
     * @param int    $user_id
     * @return bool
     */
    private function checkLogin($request = null, &$openid = '', &$user_id = 0) {
        if (is_null($request)) {
            $request = $this->oauth_request;
        }
        $openid      = '';
        $user_id     = 0;
        $status      = true;
        $action_type = $request->request('action_type');
        $action_type = $action_type ? $action_type : 'authorize_login';
        if ($this->client_type == 'user') {
            if ($action_type == 'authorize_login') {
                $status = $this->authorize_login($request, $openid, $user_id);
            } else if ($action_type == 'authorize_register') {
                $status = $this->authorize_register($request, $openid, $user_id);
            } else {
                $this->error = '不支持的授权类型！';
                return false;
            }
        }
        return $status;
    }

    private function authorize_login($request = null, &$openid = '', &$user_id = 0) {
        if ($this->client_type != 'user') {
            $this->error = '不支持的操作！';
            return false;
        }
        if (is_null($request)) {
            $request = $this->oauth_request;
        }
        $openid           = '';
        $user_id          = 0;
        $OauthClientUsers = model('oauth/OauthClientUsers');
        $appid            = $request->request('client_id');
        $userinfo         = $request->request('userinfo');
        $third_userinfo   = $request->request('third_userinfo');
        $extra            = $request->request('extra');
        if ( !isset($userinfo['email']) || empty($userinfo['email'])) {
            $this->error = '用户email账号为空！';
            return false;
        }
        $map          = array();
        $map['email'] = $userinfo['email'];
        $db_userinfo  = db('user')->where($map)->field('id,password,status,status_info')->find();
        if (empty($db_userinfo) || $db_userinfo['status'] != 1) {
            $this->error = '用户不存在或被禁用！';
            return false;
        }
        if ($this->client_is_default == 1) {/*本地client用户*/
            if ( !isset($userinfo['password']) || md5(trim($userinfo['password'] . '123')) !== $db_userinfo['password']) {
                $this->error = '密码错误！';
                return false;
            }
        }
        if ($db_userinfo['status_info'] == 2 || $db_userinfo['status_info'] == 3 || $db_userinfo['status'] == 2) {
            $this->error = 'The account has been blocked!';
            return false;
        }
        $userinfo['user_id'] = $user_id = $db_userinfo['id'];
        $openid              = $OauthClientUsers->add_oauth_client_user($appid, $userinfo, $third_userinfo, $extra);
        if ($openid === false) {
            $this->error = $OauthClientUsers->getError();
            return false;
        }
        return true;
    }

    private function authorize_register($request = null, &$openid = '', &$user_id = 0) {
        $this->error = '暂不支持授权注册！';     /*本授权注册方法尚未完成全面测试*/
        return false;
        if ($this->client_type != 'user') {
            $this->error = '不支持的操作！';
            return false;
        }
        if (is_null($request)) {
            $request = $this->oauth_request;
        }
        $appid          = $request->request('client_id');
        $data           = $request->request('userinfo');
        $third_userinfo = $request->request('third_userinfo');
        $extra          = $request->request('extra');
        \think\Db::startTrans();
        if (config('verify') == 1) {
            if (empty($data['g-recaptcha-response'])) {
                \think\Db::rollback();
                $this->error = 'Man-machine verification failed';
                return false;
            }
            $recaptcha = new \app\common\controller\Recaptcha($data['g-recaptcha-response']);
            $resonse   = $recaptcha->siteverify();
            unset($data['g-recaptcha-response']);
            if ( !$resonse['success']) {
                \think\Db::rollback();
                $this->error = 'Man-machine verification failed';
                return false;
            }
        }
        // if (preg_match('/^[\x7f-\xff]+$/', $data['firstname']) && preg_match('/^[\x7f-\xff]+$/', $data['lastname'])) {
        //     $data['username'] = $data['lastname'] . ' ' . $data['middlename'] . ' ' . $data['firstname'];
        // } else {
        //     $data['username'] = $data['middlename'] . ' ' . $data['firstname'] . ' ' . $data['lastname'];
        // }
        $data['username']   = $data['firstname'] . ' ' . $data['middlename'] . ' ' . $data['lastname'];
        $data['chinaname2'] = $data['lastname'] . ' ' . $data['middlename'] . ' ' . $data['firstname'];
        unset($data['__token__'], $data['firstname'], $data['middlename'], $data['lastname']);
        $search_ids = model('common/User')->column('search_id');
        $search_id  = 'WSE' . rand('100000', '999999');
        while (in_array($search_id, $search_ids)) {
            $search_id = 'WSE' . rand('100000', '999999');
        }
        $data['search_id'] = $search_id;
        $data['password']  = md5($data['password'] . '123');
        //$data['password'] = md5(preg_replace('/(123$)/', '', $data['password']));
        $userModel = new \app\common\model\User();
        $result    = $userModel->validate(true)->allowField(true)->save($data);
        if ($result === false) {
            \think\Db::rollback();
            $this->error = $userModel->getError();
            return false;
        }
        $user_id = $userModel->getLastInsID();
        $userinfo            = array();
        $userinfo['email']   = $data['email'];
        $userinfo['user_id'] = $user_id;
        $OauthClientUsers    = model('oauth/OauthClientUsers');
        $openid              = $OauthClientUsers->add_oauth_client_user($appid, $userinfo, $third_userinfo, $extra);
        if ($openid === false) {
            \think\Db::rollback();
            $this->error = $OauthClientUsers->getError();
            return false;
        }
        \think\Db::commit();
        return true;
    }

}