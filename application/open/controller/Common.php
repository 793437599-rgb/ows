<?php

namespace app\open\controller;

use think\Controller;

class Common extends Controller {

    use \app\common\traits\traitController;

    protected $param              = array();
    protected $session_key        = 'wse_lxw_connection_str';
    protected $error_info         = array();
    protected $oauth_start_status = true; //是否启用oauth
    protected $oauth_client       = array();
    protected $oauth_client_user  = array();
    protected $need_sign          = true;

    public function __construct(\think\Request $request = null) {
        parent::__construct($request);
        $this->check_request_module();
        $this->param();
        $this->check_need_sign();
        $OauthAccess              = new \app\common\util\OauthAccess('wse');
        $this->oauth_start_status = $OauthAccess->get('need_authorize') == 1;
    }

    protected function check_need_sign() {
        $this->is_example = $this->param('is_example', null, 'auto') == 1;
        if ($this->is_example) {
            $this->need_sign = false;
        }
        if ($this->check_example_host()) {
            $this->need_sign = false;
        }
        $grant_type = $this->param('grant_type', null, 'auto');
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

    protected function init() {
        if ( !$this->check_example_host()) {
            if ($this->oauth_start_status) {
                $OauthIndexController = new \app\oauth\controller\Index();
                $OauthIndexController->set_return_type('function');
                $this->oauth_client = $OauthIndexController->check_access($this->param);
                if (empty($this->oauth_client)) {
                    $error = $OauthIndexController->getError();
                    $error = $error ? $error : 'invalid access';
                    return return_code(0, $error, array('error' => $error));
                }
                $this->session_key       = $this->oauth_client['session_key'];
                $this->oauth_client_user = model('oauth/OauthClientUsers')->get_info($this->oauth_client['user_id']);
            } else {
                $OauthAccess       = new \app\common\util\OauthAccess();
                $this->session_key = $OauthAccess->get('default_session_key');
            }
        }
        if ($this->need_sign) {
            $status = $this->check_sign($this->param, $error_info);
            if ( !$status) {
                $result               = [];
                $result['status']     = $status;
                $result['error_info'] = $error_info;
                return return_code(0, 'Signature verification failed', $result);
            }
            $this->param('', 'diy_trim');
        }
    }

    protected function check_referrer_host($allow_domain_list = array()) {
        $allow_default_domain_list = get_allow_domain(false);
        $allow_domain_list         = is_array($allow_domain_list) ? $allow_domain_list : array();
        $allow_domain_list         = array_merge($allow_default_domain_list, $allow_domain_list);
        if ( !in_array($this->referrer_host, $allow_domain_list)) {
            return false;
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
        $sign = isset($param['sign']) ? $param['sign'] : '';
        unset($param['sign']);
        ksort($param);
        $param   = http_build_query($param);
        $param   = urldecode($param);
        $is_dump = true;    //线上
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
    }

    /**
     * 参数接收方法
     * @param string          $field
     * @param string|function $filter
     * @param string          $method
     * @return array|mixed
     */
    protected function param($field = '', $filter = '', $method = 'post') {
        $default_filter = 'trim,urldecode';
        $filter         = is_string($filter) && is_function($filter) ? $default_filter . ',' . $filter : $default_filter;
        $method         = is_string($method) ? strtolower($method) : 'post';
        if ($this->is_example) {
            $method = 'auto';
        } else {
            $method = in_array($method, array('get', 'post', 'auto')) ? $method : 'post';
        }
        if (empty($this->param)) {
            //$param=$this->request->param('','',$filter);
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
     * @param int    $status
     * @param string $msg
     * @param int    $type
     * @param array  $data
     */
    protected function apiReturn($status = 0, $msg = '查询成功', $type = 1, $data = array()) {
        $result         = array();
        $result['code'] = in_array($status, array(0, 1)) ? $status : 0;
        $result['msg']  = $msg ? $msg : '查询成功';
        $type           = in_array($type, array(1, 2)) ? $type : 1;
        if ($type == 1) {
            $result['data'] = new \stdClass();
        } else {
            /*$result['data']=new \ArrayObject();*/
            $result['data'] = array();
        }
        if ( !empty($data)) {
            if (is_array($data) && isset($data[0])) {
                if ($type == 1) {
                    foreach ($data as $key => $val) {
                        $result['data']->$key = $val;
                    }
                } else {
                    $result['data'] = $data;
                }
            } else {
                $result['data'] = $data;
            }
        }
        json($result)->send();
        die;
    }

    public function _empty() {
        $this->apiReturn(0, '非法操作');
    }

}
