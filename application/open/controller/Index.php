<?php

namespace app\open\controller;

use think\Db;

class Index extends Common {

    public function __construct(\think\Request $request = null) {
        parent::__construct($request);
        $get_page = array('secure_site');
        if (in_array($this->action, $get_page)) {
            $this->param('', '', 'get');
        }
        $this->init();
    }

    public function secure_site() {
        if ( !$this->check_example_host() && !$this->oauth_start_status) {
            $allow_domain = [
                
            ];
            if ( !$this->check_referrer_host($allow_domain)) {
                return $this->fetch('secure_site_no_trust');
            }
        }
        return $this->fetch('secure_site', ['site' => $this->referrer_host]);
    }

    public function check_edu_code() {
        if (empty($this->param['user_code']) or empty($this->param['document_group_id']) or empty($this->param['document_type_id']) or empty($this->param['number'])) {
            return return_code(0, '用户服务编码不能为空');
        }
        $user_info = Db::name('user')->where('search_id', $this->param['user_code'])->find();
        if (empty($user_info)) {
            return return_code(0, '用户服务编码不能为空');
        }
        $user_degree           = get_user_degree($this->param['xwcc'], false, '');
        $education_information = Db::name('user_order')->alias('order')
            ->join('user_order_detail detail', 'order.id=detail.order_id')
            ->where('order.app_id', 3)
            ->where('order.user_id', $user_info['id'])
            /*->where('order.status',7)*/
            //->where('detail.degree', $user_degree)
            ->find();
        if (empty($education_information)) {
            return return_code(0, '该用户该学历信息不存在');
        }
        $document_type_source = config('documet_type');
        $document_group       = $document_type_source['en-us'][$this->param['document_group_id'] - 1]['title'];
        $document_type_id_key = array_keys($document_type_source['en-us'][$this->param['document_group_id'] - 1]['items_id'], $this->param['document_type_id']);
        $document_type_id_key = $document_type_id_key[0];
        $document_type        = $document_type_source['en-us'][$this->param['document_group_id'] - 1]['items'][$document_type_id_key];
        /*if ( !( $education_information['document_group'] == $document_group && $education_information['document_type'] == $document_type && $education_information['id_no'] == $this->param['number'] )) {
            return return_code(0, '该用户该学历信息不存在...');
        }*/
        /*if($education_information['status']!=7){
            return return_code(0,'该用户该学历信息未通过审核');
        }*/
        if ( !empty($this->param['code'])) {
            $agency_code_info = Db::name('agency_code')->alias('c')->join('agency a', 'c.age_id=a.id')
                ->field('c.*,a.username')
                ->where('c.code', $this->param['code'])
                ->find();
            if ($agency_code_info['use_id'] == 0) {
                return return_code(1, '验证失败', ['status' => 0, 'name' => '']);
            }
            if ($education_information['order_id'] != $agency_code_info['use_id']) {
                return return_code(1, '验证失败', ['status' => 0, 'name' => '']);
            }
            return return_code(1, '查询成功 ', ['status' => 1, 'name' => $agency_code_info['username']]);
        } else {
            return return_code(1, '验证成功', ['status' => 1, 'name' => '']);
        }
    }

    public function get_degree($degree) {
        if ($degree == 'Master') {
            $id = 3;
        } else if ($degree == 'Ph.D') {
            $id = 4;
        } else if ($degree == 'Diploma') {
            $id = 1;
        } else if ($degree == 'Bachelor') {
            $id = 2;
        } else {
            $id = 2;
        }
        return $id;
    }

    public function notify() {
        $OauthAccess                 = new \app\common\util\OauthAccess();
        $notify_result               = isset($this->param['result']) ? $this->param['result'] : array();
        $state                       = isset($this->param['state']) ? $this->param['state'] : '';
        $state_result                = $OauthAccess->analyze_state($state);
        $result                      = array();
        $result['status']            = 'fail';
        $result['error']             = 'invalid access_token';
        $result['error_description'] = 'invalid access_token';
        \think\Db::startTrans();
        if ($this->oauth_start_status) {
            $access_token      = isset($this->param['access_token']) ? $this->param['access_token'] : '';
            $access_token_info = db('oauth_access_tokens')->where('access_token', 'eq', $access_token)->find();
            if (empty($access_token_info)) {
                \think\Db::rollback();
                return return_code(0, $result['error'], $result);
            }
            $map                    = array();
            $map['client_id']       = $access_token_info['client_id'];
            $map['openid']          = $access_token_info['user_id'];
            $map['status']          = 1;
            $oauth_client_user_info = db('oauth_client_users')->where($map)->find();
            if (empty($oauth_client_user_info)) {
                \think\Db::rollback();
                return return_code(0, $result['error'], $result);
            }
            $oauth_client_user_info['userinfo']       = json_decode($oauth_client_user_info['userinfo'], true);
            $oauth_client_user_info['third_userinfo'] = json_decode($oauth_client_user_info['third_userinfo'], true);
            $status                                   = $OauthAccess->update_access_token($access_token_info['client_id'], $notify_result, $oauth_client_user_info['user_id']);
            if ($status === false) {
                \think\Db::rollback();
                $result['error']             = 'failed';
                $result['error_description'] = 'notify action failed';
                return return_code(0, $result['error'], $result);
            }
            $user_result            = $oauth_client_user_info['third_userinfo'];
            $user_result['user_id'] = $oauth_client_user_info['user_id'];
        } else {
            $user_result = isset($this->param['user']) ? $this->param['user'] : array();
        }
        $action = isset($state_result['action']) ? $state_result['action'] : '';
        if ( !empty($action) && method_exists($this, $action)) {
            $extra_result = isset($this->param['extra']) ? $this->param['extra'] : array();
            $action_param = isset($state_result['param']) ? $state_result['param'] : array();
            $status       = $this->$action($user_result, $extra_result, $action_param);
            if ($status === false) {
                \think\Db::rollback();
                $msg                         = $this->getError();
                $result['error']             = 'failed';
                $result['error_description'] = $msg;
                return return_code(0, $msg, $result);
            }
        }
        \think\Db::commit();
        $result           = array();
        $result['status'] = 'success';
        return return_code(1, $result['status'], $result);
    }

    /**
     * 授权传输回调
     * @param array $third_user
     * @param array $extra
     * @param array $param
     * @return bool
     */
    protected function transmission_authorization($third_user = array(), $extra = array(), $param = array()) {
        if ( !isset($param['oid']) || !is_numeric($param['oid']) || $param['oid'] <= 0) {
            $this->error = '添加传输记录失败：oid为空';
            return false;
        }
        $user_id = db('user_order')->where('id', 'eq', $param['oid'])->value('user_id');
        if (is_numeric($user_id) && $user_id > 0) {
            if (isset($third_user['user_id']) && $third_user['user_id'] > 0) {
                if ($third_user['user_id'] != $user_id) {
                    $this->error = '添加传输记录失败：账户不对应';
                    return false;
                }
            }
        } else {
            $user_id = 0;
        }
        $third_info          = db('third')->where('id', 'eq', 14)->find();
        $data                = array();
        $data['third_id']    = $third_info['id'];
        $data['third_re']    = $third_info['third_cname'];
        $data['quhao']       = isset($third_user['mobile_prefix']) ? $third_user['mobile_prefix'] : '';
        $data['call']        = isset($third_user['mobile']) ? $third_user['mobile'] : '';
        $data['order_id']    = $param['oid'];
        $data['aim']         = '';
        $data['user_id']     = $user_id;
        $data['create_time'] = date("Y-m-d H:i:s");
        $data['create_ip']   = get_ip();
        $data['lxw_send']    = 1;    /*传输状态：0不开启传输，等待审核，1等待传输*/
        $map                 = array();
        $map['user_id']      = $user_id;
        $map['third_id']     = $third_info['id'];
        $map['order_id']     = $param['oid'];
        $user_tran_info      = db('user_tran')->where($map)->order('create_time desc,id desc')->find();
        if ( !empty($user_tran_info)) {
            if (in_array($user_tran_info['lxw_send'], array(6, 8))) {
                //todo  //上一条传输记录已完成
            }/*elseif(in_array($user_tran_info['lxw_send'],array(3,5,7))&&strtotime($user_tran_info['create_time'])>0){
                //todo  //上一条传输记录已失败，并且已经过了等待期
            }*/ else {
                if ($user_tran_info['lxw_send'] == 0) {
                    $data['id'] = $user_tran_info['id'];
                } else {
                    //无需添加传输记录
                    return 1;
                }
            }
        }
        if (isset($data['id']) && $data['id'] > 0) {
            $status = db('user_tran')->where('id', 'eq', $data['id'])->update($data);
        } else {
            $status = db('user_tran')->insert($data);
        }
        if ( !$status) {
            $this->error = '添加传输记录失败';
            return false;
        }
        model('common/Statistics')->clear_cache();
        return true;
    }



     public function test() {  //接受
         return return_code(1, '成功');
         die();
        if (empty($data)) {
            return return_code(0, '传输数据不能为空');    
        } 
        if (empty($this->param['type'])   or empty($this->param['idcard']){
            return return_code(0, '传输数据不完整');
        }
        $user_info = Db::name('user')->where('id_number', $this->param['idcard'])->find();
        if (empty($user_info)) {
            return return_code(0, '该用户信息不存在');
        }
        $order = Db::name('user_order')
            ->where('app_id', 3)
            ->where('user_id', $user_info['id'])
            ->find();
        if (empty($order)) {
            return return_code(0, '该用户信息不存在');
        }
        $new_data= array( );
        if (!empty($this->param['email'])  {
            $new_data['email']= $this->param['email'];
            $exit = Db::name('user')->where('email', $new_data['email'])->find();
            if ($exit) {
                return return_code(0, '该邮箱已使用');
            }
        }
        if (!empty($this->param['mobile'])  {
            $new_data['mobile']= $this->param['mobile'];
        }
        if (!empty($this->param['mobile_prefix'])  {
            $new_data['qujian']= $this->param['mobile_prefix'];
        }
           $new_data['uid']= $user_info['id'];
        //$res = Db::name('user')->where('id',$user_info['id'])->update( $new_data);
        $file  = 'tet.log'; 
        if (!empty($data)) {
            file_put_contents($file,print_r($new_data,'true') ,FILE_APPEND);       
        } 
        return return_code(1, '成功');
    }

}
