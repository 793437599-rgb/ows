<?php

namespace app\oauth\controller;

use think\Controller;

class Common extends Controller {

    use \app\common\traits\traitController;

    protected $param               = array();
    protected $request             = null;
    protected $session_key         = 'wse_lxw_connection_str';
    protected $default_session_key = 'wse_lxw_connection_str';
    protected $client_type         = '';
    protected $client_is_default   = '2';
    protected $appid               = '';
    protected $app_secret          = '';
    protected $grant_type          = 'authorization_code';
    protected $scope               = 'basic';
    protected $client_info         = array();
    protected $pdo                 = null;
    protected $oauth_request       = null;
    protected $keys                = array(
        'public_key' => '',
        'private_key' => '',
        'encryption_algorithm' => 'RS256',
    );
    protected $server              = null;
    protected $access_token        = '';
    protected $refresh_token       = '';
    protected $openid              = '';
    protected $need_sign           = true;
    private   $return_type         = 'api';
    private   $return_type_list    = array('api', 'ajax', 'function', 'string');

    public function __construct(\think\Request $request = null) {
        parent::__construct($request);
        $this->check_request_module();
        $this->param();
        $this->check_need_sign();
    }

    public function set_return_type($return_type = '') {
        if (in_array($return_type, $this->return_type_list)) {
            $this->return_type = $return_type;
        }
        return $this;
    }

    protected function check_need_sign() {
        $this->is_example = $this->param('is_example') == 1;
        if ($this->is_example) {
            $this->need_sign = false;
        }
        if ($this->check_example_host()) {
            $this->need_sign = false;
        }
        $grant_type = $this->param('grant_type');
        $grant_type = $grant_type ? $grant_type : $this->grant_type;
        if ( !is_array($grant_type)) {
            $grant_type = (string)$grant_type;
            $grant_type = trim($grant_type);
            if (empty($grant_type)) {
                $grant_type = $this->grant_type ? $this->grant_type : '';
            }
            if (empty($grant_type)) {
                $grant_type = array();
            } else if (strpos($grant_type, ' ') !== false) {
                $grant_type = explode(' ', $grant_type);
            } else {
                $grant_type = array($grant_type);
            }
        }
        if (in_array('client_credentials', $grant_type)) {
            $this->need_sign = false;
        }
        return $this->need_sign;
    }

    protected function check_example_host() {
        $example_host = array(
            'chu.edu.com.ve', 'edu.ch.university',
        );
        if (in_array($this->referrer_host, $example_host)) {
            return true;
        }
        return false;
    }

    public function init() {
        if ($this->check_action_is_directaccess()) {
            die('Illegal operation');
        }

        $this->server = $this->server();

        $this->oauth_request = \OAuth2\Request::createFromGlobals();
        $grant_type          = $this->oauth_request->request('grant_type');
        $scope               = $this->oauth_request->request('scope');
        $appid               = $this->oauth_request->request('client_id');
        $app_secret          = $this->oauth_request->request('client_secret');
        $access_token        = $this->oauth_request->request('access_token');
        $refresh_token       = $this->oauth_request->request('refresh_token');
        $grant_type          = !empty($grant_type) ? $grant_type : $this->oauth_request->query('grant_type');
        $scope               = !empty($scope) ? $scope : $this->oauth_request->query('scope');
        $appid               = !empty($appid) ? $appid : $this->oauth_request->query('client_id');
        $app_secret          = !empty($app_secret) ? $app_secret : $this->oauth_request->query('client_secret');
        $access_token        = !empty($access_token) ? $access_token : $this->oauth_request->query('access_token');
        $refresh_token       = !empty($refresh_token) ? $refresh_token : $this->oauth_request->query('refresh_token');
        if ( !empty($grant_type)) {
            $this->grant_type = $grant_type;
        }
        if ( !empty($scope)) {
            $this->scope = $scope;
        }
        if ( !empty($appid)) {
            $this->appid = $appid;
        }
        if ( !empty($app_secret)) {
            $this->app_secret = $app_secret;
        }
        if ( !empty($access_token)) {
            $this->access_token = $access_token;
        }
        if ( !empty($refresh_token)) {
            $this->refresh_token = $refresh_token;
        }
        if ($this->refresh_token) {
            $this->client_info = model('oauth/OauthClients')->get_refresh_token_info($this->refresh_token);
        } else if ($this->access_token) {
            $this->client_info = model('oauth/OauthClients')->get_access_token_info($this->access_token);
        } else {
            $this->client_info = model('oauth/OauthClients')->get_info($this->appid);
        }
        if (empty($this->client_info)) {
            return $this->apiReturn(lang('error_10000014'), $this->return_type);
        }
        if ($this->appid) {
            if ($this->client_info['client_id'] != $this->appid) {
                return $this->apiReturn('Illegal operation', $this->return_type);
            }
        }
        if ($this->app_secret) {
            if ($this->client_info['client_secret'] != $this->app_secret) {
                return $this->apiReturn('Illegal operation...', $this->return_type);
            }
        }
        $this->appid             = $this->client_info['client_id'];
        $this->client_is_default = $this->client_info['is_default'];
        $this->session_key       = $this->client_info['session_key'];
        $this->client_type       = $this->client_info['is_user'] == 1 ? 'client' : 'user';
        $client_keys                        = model('oauth/OauthClients')->get_client_keys($this->appid);
        $this->keys['public_key']           = isset($client_keys['public_key']) && !empty($client_keys['public_key']) ? $client_keys['public_key'] : '';
        $this->keys['private_key']          = isset($client_keys['private_key']) && !empty($client_keys['private_key']) ? $client_keys['private_key'] : '';
        $this->keys['encryption_algorithm'] = isset($client_keys['encryption_algorithm']) && !empty($client_keys['encryption_algorithm']) ? $client_keys['encryption_algorithm'] : '';
        $rootCache = APP_PATH . "common/util/certificate/wse/server/";
        if (is_file($rootCache . 'server_public_key.key')) {
            $this->keys['public_key'] = file_get_contents($rootCache . 'server_public_key.key');
        }
        if (is_file($rootCache . 'server_key.key')) {
            $this->keys['private_key'] = file_get_contents($rootCache . 'server_key.key');
        }
        if ( !$this->check_domain($this->client_info['allow_domain'], $this->client_info['allow_ip'], $is_local)) {
            return false;
        }
        if ($this->need_sign && !( $is_local && $_SERVER['REQUEST_SCHEME'] . '://' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'] == $this->referrer )) {
            if ( !$this->check_sign($this->param, $error_info)) {
                $result                      = array();
                $result['error']             = lang('error_10000035');
                $result['error_description'] = $error_info;
                $this->apiReturn($result);
            }
        }

        if ( !$this->pdo->checkRestrictedGrantType($this->appid, $this->grant_type)) {
            return $this->apiReturn('grant_type fail', $this->return_type);
        }
        if ( !$this->pdo->checkClientCredentials($this->appid, $this->client_info['client_secret'])) {
            return $this->apiReturn('client_secret fail', $this->return_type);
        }
        if ( !$this->check_scope($this->scope, $this->client_info['scope'])) {
            return $this->apiReturn($this->getError(), $this->return_type);
        }
    }

    protected function server($server_config = array()) {
        $dsn                                         = config('database.type') . ':dbname=' . config('database.database') . ';host=' . config('database.hostname');
        $username                                    = config('database.username');
        $password                                    = config('database.password');
        $default_server_config                       = array();
        $default_server_config['auth_code_lifetime'] = 5 * 60;/*code有效期*/
        $default_server_config['access_lifetime']    = 30 * 24 * 60 * 60;/*access_token有效期*/
        //$default_server_config['always_issue_new_refresh_token'] = true;
        $default_server_config['refresh_token_lifetime'] = 3 * 30 * 24 * 60 * 60;/*refresh_token有效期*/
        if ($this->client_type == 'user') {
            // configure the server for OpenID Connect
            $default_server_config['use_openid_connect'] = true;
            $default_server_config['issuer']             = HOME_URL;
            $default_server_config['id_lifetime']        = 60 * 60;/*id_token有效期*/
        }
        $server_config = is_array($server_config) && !empty($server_config) ? $server_config : array();
        $server_config = array_merge($default_server_config, $server_config);
        // error reporting (this is a demo, after all!)
        //ini_set('display_errors',1);error_reporting(E_ALL);
        // Autoloading (composer is preferred, but for this example let's just do this)
        require_once( EXTEND_PATH . 'OAuth2/Autoloader.php' );
        \OAuth2\Autoloader::register();
        // $dsn is the Data Source Name for your database, for exmaple "mysql:dbname=my_oauth2_db;host=localhost"
        $this->pdo = new \OAuth2\Storage\Pdo(array('dsn' => $dsn, 'username' => $username, 'password' => $password), array('table_prefix' => config('database.prefix')));
        // Pass a storage object or array of storage objects to the OAuth2 server class
        $server = new \OAuth2\Server($this->pdo, $server_config);
        // 第二个参数，必须设置值为public_key
        $server->addStorage($this->getKeyStorage(), 'public_key');
        // Add the "Client Credentials" grant type (it is the simplest of the grant types)
        $ClientCredentials = new \OAuth2\GrantType\ClientCredentials($this->pdo);
        $server->addGrantType($ClientCredentials);
        // create the grant type
        $grantType = new \OAuth2\GrantType\AuthorizationCode($this->pdo);
        // Add the "Authorization Code" grant type (this is where the oauth magic happens)
        $server->addGrantType($grantType);
        // create the grant type
        $grantType = new \OAuth2\GrantType\RefreshToken($this->pdo);
        // add the grant type to your OAuth server
        $server->addGrantType($grantType);
        return $server;
    }

    protected function getKeyStorage() {
        // create storage
        return new \OAuth2\Storage\Memory(array('keys' => array(
            'public_key' => $this->keys['public_key'],
            'private_key' => $this->keys['private_key'],
        )));
    }

    protected function check_scope($scope = '', $allow_scope = '') {
        if ( !is_array($scope)) {
            $scope = (string)$scope;
            $scope = trim($scope);
            if (empty($scope)) {
                $scope = array();
            } else if (strpos($scope, ' ') !== false) {
                $scope = explode(' ', $scope);
            } else {
                $scope = array($scope);
            }
        }
        if ( !is_array($allow_scope)) {
            $allow_scope = (string)$allow_scope;
            $allow_scope = trim($allow_scope);
            if (empty($allow_scope)) {
                $allow_scope = array();
            } else if (strpos($allow_scope, ' ') !== false) {
                $allow_scope = explode(' ', $allow_scope);
            } else {
                $allow_scope = array($allow_scope);
            }
        }
        $not_allow_scope = array_diff($scope, $allow_scope);
        if ( !empty($not_allow_scope)) {
            $this->error = 'invalid scope : ' . implode(' ', $not_allow_scope);
            return false;
        }
        return true;
    }

    protected function check_domain($allow_domain = array(), $allow_ip = array(), &$is_local = false) {
        $is_local      = false;
        $system_client = model('oauth/OauthClients')->get_system_client(2);
        /*if(!in_array($this->referrer_host,$system_client['allow_domain'])){
            if(!in_array($this->referrer_host,$allow_domain)){
                $result=array();
                $result['error']='domain denied';
                $result['error_description']='The domain was denied';
                $result['referrer_domain']=$this->referrer_host;
                return $this->apiReturn($result,$this->return_type);
            }
        }else{
            $is_local=true;
        }*/
        /*对域名进行限制*/
        $is_system_domain = false;
        if ( !empty($system_client['allow_domain'])) {
            if (in_array($this->referrer_host, $system_client['allow_domain'])) {
                $is_system_domain = true;
            }
        }
        if ( !$is_system_domain) {
            if ( !in_array($this->referrer_host, $allow_domain)) {
                $result                      = array();
                $result['error']             = 'domain denied';
                $result['error_description'] = 'The domain was denied';
                $result['referrer_domain']   = $this->referrer_host;
                return $this->apiReturn($result, $this->return_type);
            }
        }
        /*对ip进行限制*/
        $is_system_ip = false;
        $referrer_ip  = model('oauth/IpDomain')->get_info($this->referrer_host, 'ip,ipv6');
        if ( !empty($system_client['allow_ip'])) {
            if (in_array($referrer_ip['ipv6'], $system_client['allow_ip'])) {
                $is_system_ip = true;
            } else {
                if ( !empty($referrer_ip['ip'])) {
                    if (in_array($referrer_ip['ip'], $system_client['allow_ip'])) {
                        $is_system_ip = true;
                    }
                }
            }
        }
        if ( !empty($system_client['allow_ip']) && !$is_system_ip) {
            if ( !empty($allow_ip) && is_array($allow_ip)) {
                $is_client_ip_allowed = false;
                foreach ($allow_ip as $ip_temp) {
                    $referrer_ip_long = ip2long_v6($referrer_ip['ipv6']);
                    $ip_temp_long     = '';
                    if (is_ip($ip_temp, 'ipv4', true)) {
                        $ip_temp_long = sprintf('%u', ip2long($ip_temp));
                    } else if (is_ip($ip_temp, 'ipv6', true)) {
                        $ip_temp_long = ip2long_v6($ip_temp);
                    } else if (is_ip($ip_temp, 'ip', false)) {
                        $ip_temp_long = $ip_temp;
                    }
                    if ( !$is_client_ip_allowed && $referrer_ip_long == $ip_temp_long) {
                        $is_client_ip_allowed = true;
                        break;
                    }
                }
                if ( !$is_client_ip_allowed) {
                    $result                      = array();
                    $result['error']             = 'ip denied';
                    $result['error_description'] = 'The ip was denied';
                    $result['referrer_ip']       = $referrer_ip;
                    return $this->apiReturn($result, $this->return_type);
                }
            }
        }
        if (empty($system_client['allow_ip'])) {
            $is_local = $is_system_domain;
        } else {
            $is_local = $is_system_domain && $is_system_ip;
        }
        return true;
    }

    /**
     * 验证签名
     * @param array $param
     * @param array $info
     * @return bool
     */
    protected function check_sign($param = array(), &$info = array()) {
        $sign_type_list = array('md5', 'rsa2');
        $sign           = isset($param['sign']) && !empty($param['sign']) ? $param['sign'] : '';
        $sign_type      = isset($param['sign_type']) && is_string($param['sign_type']) ? strtolower($param['sign_type']) : '';
        $sign_type      = in_array($sign_type, $sign_type_list) ? $sign_type : 'md5';
        unset($param['sign']);
        ksort($param);
        $param = http_build_query($param);
        $param = urldecode($param);
        $is_dump = true;
        if ($sign_type == 'md5') {
            if ( !$is_dump) {
                /*---------------执行 1：无输出    start--------------*/
                $new_sign = md5($this->session_key . md5($param));
                if ($new_sign != $sign) {
                    return false;
                }
                /*---------------执行 1：无输出    end--------------*/
            } else {
                /*-----执行 2：有输出，用于检查签名如何出错，出错在哪儿    start----*/
                $result                   = array();
                $result['param_post']     = $_POST;
                $result['param_get']      = $_GET;
                $result['param_original'] = $this->param;
                $result['param']          = $param;
                $result['first_md5']      = md5($param);
                $result['last_param']     = $this->session_key . md5($param);
                $result['sign']           = md5($result['last_param']);
                $this->error_info         = $result;
                if ($result['sign'] != $sign) {
                    $info = $result;
                    return false;
                }
                /*-----执行 2：有输出，用于检查签名如何出错，出错在哪儿    end----*/
            }
            return true;
        } else if ($sign_type == 'rsa2') {
            /*已完成，尚未测试*/
            $this->error = 'RSA2 signature is not supported at the moment';
            return false;
            $rsa = new \Common\Util\Rsa();
            if ($is_dump) {
                /*-----有输出，用于检查签名如何出错，出错在哪儿    start----*/
                $result                   = array();
                $result['param_post']     = $_POST;
                $result['param_original'] = $this->param;
                $result['param']          = $param;
                $result['first_md5']      = md5($param);
                $result['last_param']     = $this->session_key . md5($param);
                $result['sign_check']     = $rsa->verifySign($result['last_param'], $sign, true);
                $this->error_info         = $result;
                /*-----有输出，用于检查签名如何出错，出错在哪儿    end----*/
            }
            return $rsa->verifySign($this->session_key . md5($param), $sign, true);
        }
    }

    /**
     * 参数接收方法
     * @param string $field
     * @param string $filter
     * @param string $method
     * @return array|mixed
     */
    protected function param($field = '', $filter = '', $method = 'auto') {
        $default_filter = 'trim,urldecode';
        $filter         = is_string($filter) && is_function($filter) ? $default_filter . ',' . $filter : $default_filter;
        $method         = is_string($method) ? strtolower($method) : 'auto';
        if ($this->is_example) {
            $method = 'auto';
        } else {
            $method = in_array($method, array('get', 'post', 'auto')) ? $method : 'auto';
        }
        if (empty($this->param)) {
            if ($method == 'get') {
                $param = $this->request->get('', '', $filter);
            } else if ($method == 'post') {
                $param = $this->request->post('', '', $filter);
            } else {
                $param = $this->request->param('', '', $filter);
            }
            foreach ($param as $key => $val) {
                if (( !is_numeric($key) && empty($key) ) || ( !is_numeric($val) && empty($val) )) {
                    unset($param[$key]);
                }
            }
            $this->param = $param;
        } else {
            $param = $this->param;
        }
        if ( !empty($field)) {
            if (isset($param[$field])) {
                $param = $param[$field];
            } else {
                $param = '';
            }
        }
        return $param;
    }

    /**
     * 接口统一返回方法
     * @param array  $result_param
     * @param string $return_type
     * @return false
     */
    protected function apiReturn($result_param = array(), $return_type = '') {
        $return_type = $return_type ? $return_type : $this->return_type;
        if ($return_type == 'api') {
            $result = array();
            if (is_array($result_param)) {
                $result = $result_param;
            } else if (is_string($result_param)) {
                $result['error'] = $result['error_description'] = $result_param;
            } else {
                $result = $result_param;
            }
            json($result)->send();
            die;
        } else if ($return_type == 'ajax') {
            $this->ajaxError($result_param);
        } else if ($return_type == 'function') {
            $this->error = json_encode($result_param);
            return false;
        } else if ($return_type == 'string') {
            echo json_encode($result_param);
            die;
        }
    }

    public function _empty() {
        $this->apiReturn('Illegal operation');
    }

}