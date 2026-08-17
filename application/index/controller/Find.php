<?php

namespace app\index\controller;

use aliphone\AliyunSmsDemo;
use app\common\controller\HomeBase;
use app\common\controller\Recaptcha;
use app\common\model\Edu as EduModel;
use app\common\model\Ku as KuModel;
use app\common\model\Score as ScoreModel;
use app\common\model\User as UserModel;
use com\Geetestlib;
use org\Email;
use phpmailer\PHPMailer;
use think\Cache;
use think\Db;
use think\db\exception\DataNotFoundException;
use think\db\exception\ModelNotFoundException;
use think\exception\DbException;
use think\Request;
use think\response\Json;
use think\response\Redirect;
use think\Session;


class Find extends HomeBase
{
   
    public function __construct(\think\Request $request = null) {
        parent::__construct($request);
        $cif=  Db::name('system')->where([ 'id' => 26])->value('value');   
        if ($cif!=1) {
            echo "<script>alert('！');window.history.back(-1);</script>";
            die();
        }
    }
//分隔线速度==========================================================================================111111111111111111111111
    public function index()
    {
        return $this->fetch();
    }

    //游客查询  1
    public function query()
    {
        if ($this->request->isPost()) {
            $data = $this->request->param();
            if (!$data['name']) {
                $this->error('Please input name');
                return status_code(10003, 'Please input name');
            }if ( $data['mode']==2) {
                return $this->query_user();
            }
            $name = trim($data['name']);
            $code = trim($data['search_id']);
            $user_ids = Db::name('user')->whereOr("username REGEXP '" . get_regexp_string($name) . "'")->whereOr('chinaname', $name)->whereOr("chinaname2 REGEXP '" . get_regexp_string($name) . "'")->column('id');
            $transcript = model('common/Transcript')->field('user_id,serial_number,certificate_png')->where(['user_id' => ['in', $user_ids], 'serial_number' => $code])->fetchSql(true)->select();
            $certificate = model('common/Certificate')->field('user_id,serial_number,certificate_png')->where(['user_id' => ['in', $user_ids], 'serial_number' => $code])->union($transcript)->find();
            if (!empty($certificate)) {
                Session::set('serial_number110', $certificate['serial_number']);
                return status_code(20000, '', ['url' => url('index/find/certificate_query')]);   //联动1
            }
            return status_code(10004, 'Query fail', ['url' => url('index/find/fail_query')]);     //失败页面
        }
    }

    public function certificate_query()  //联动1
    {
        $serial_number = Session::get('serial_number110');
        if (empty($serial_number)) {
            return redirect(url('index/find/index'));
        }
        $transcript = model('common/Transcript')->field('user_id,serial_number,certificate_png')->where(['serial_number' => $serial_number])->fetchSql(true)->select();;
        $certificate = model('common/Certificate')->field('user_id,serial_number,certificate_png')->where(['serial_number' => $serial_number])->union($transcript)->find();
        $user_id = $certificate['user_id'];
        $user = model('common/User')->find($user_id);
        $this->assign(['user' => $user]);
        return $this->fetch();
    }

    public function email_query_check()  {  //1 的 查询按钮
        $data = $this->request->param();
        if (1 == 1) {
            $serial_number = Session::get('serial_number110');
            $transcript = model('common/Transcript')->field('user_id,order_id,serial_number,certificate_png')->where(['serial_number' => $serial_number])->fetchSql(true)->select();;
            $certificate = model('common/Certificate')->field('user_id,order_id,serial_number,certificate_png')->where(['serial_number' => $serial_number])->union($transcript)->find();
            $data = [
                'certificate' => $certificate,
                'expire_time' => time() + 5 * 60,
            ];
            Session::set('certificate110', serialize($data));
            return status_code(20000, '', ['url' => url('index/find/certificate')]);
        } else {
            return $this->error('Verification code error, please try again');
        }
    }


    public function fail_query()  //失败页面
    {
        return $this->fetch();
    }

    //游客查询  2
    public function query_user()
    {
        $data = $this->request->param();
        $name = trim($data['name']);
        $code = 'wse'.trim($data['search_id1']);
        $user_ids = Db::name('user')->whereOr("username REGEXP '" . get_regexp_string($name) . "'")->whereOr('chinaname', $name)->whereOr("chinaname2 REGEXP '" . get_regexp_string($name) . "'")->column('id');
        $user_info = Db::name('user')->where(['id' => ['in', $user_ids], 'search_id' => $code])->find();
        $orders= model('UserOrder')->where(['app_id' =>['in', '3,5'] ,'status' => 7, 'user_id' => $user_info['id']])->find();
        if (empty($orders) ) {
            return status_code(20001, 'Query fail');
        }
        if (!empty($user_info)) {
            Session::set('query_user110', $user_info['id']);
            return status_code(20000, '', ['url' => url('index/find/query_user_view')]);   //联动2
        } 
        return status_code(10004, 'Query fail', ['url' => url('index/find/fail_query')]);   //失败页面
    }

    public function query_user_view()   //联动2
    {
        $query_user = Session::get('query_user110');
        if (empty($query_user)) {
            return redirect(url('index/find/index'));
        }
        $user_id = $query_user;
        $user = model('common/User')->find($user_id);
        $transcript = [];
        $transcript['serial_number1']='';
        $transcript['serial_number']='';
        $e= 0;
        $eva= [];
        $condition['app_id'] = ['in', '3,5'];
        $condition['user_id'] = $user_id;
        $condition['status'] = 7;
        $orderid = model('UserOrder') ->where($condition)->select();
        foreach ($orderid as $key => $value) {
            if ($value['app_id']==3) {
                $t=$value['id'];         //  这是4条数据
                $transcript = model('common/Certificate')->where('order_id', $t)->find();
                $transcript['serial_number1'] = model('common/Transcript')->where('order_id', $t)->value('serial_number');
            }
            if ($value['app_id']==5) {
                if ($value['status']==7) {
                    $e=$value['id'];         
                    $eva['serial_number'] = model('common/Certificate')->where('order_id', $e)->value('serial_number');
                    $eva['serial_number1'] = model('common/Transcript')->where('order_id', $e)->value('serial_number'); 
                }
            }
        }  
        $this->assign(compact('user', 'transcript', 'eva', 'e'));
        return $this->fetch();
    }
 
    public function certificate()      {//证书展示页面 
        $data = Session::get('certificate110');
        if (empty($data)) {
            return redirect(url('index/find/index'));
        }
        $data = unserialize($data);
        if (time() > $data['expire_time']) {
            Session::delete('serial_number110');
            Session::delete('certificate110');
            return redirect(url('index/find/index'));
        }
        $certificate = $data['certificate'];
        $this->assign('ray_c', $certificate);
        $this->assign('certificate', $certificate);
        $query = [];
        $query['user_id'] = $certificate['user_id'];
        $query['create_ip'] = \request()->ip();
        $query['create_time'] = date('Y-m-d H:i:s');
        $query['serial_number'] = $certificate['serial_number'];
        $query['search_type'] = 1;
        Db::name('query_log')->insert($query);
        $certificatePngs = $data['certificate']['certificate_png'];
        $user = UserModel::get($certificate['user_id']);
        $this->assign('certificatePngs', $certificatePngs);
        $this->assign('user', $user);  
        $order = model('UserOrder') ->where(['o.id' => $certificate['order_id']])->alias('o') ->join('user_order_detail c', 'c.order_id=o.id')->find();
        $this->assign('order', $order);
        $key1 = model('common/Transcript')->where(['order_id' => $certificate['order_id']]) ->find();
        $key2 = model('common/Certificate')->where(['order_id' => $certificate['order_id']]) ->find();
         $certificates = Db::name('transcript_credential') ->where('order_id', $certificate['order_id'])->find();
        $this->assign('key1', $key1);
        $this->assign('certificates', $certificates);
        $this->assign('key2', $key2);
        return $this->fetch();
    }


     public function email_query_check2()
    {
        Session::set('time_query', time()+1800);
        return status_code(20000, '', ['url' => url('index/find/certificate_query_detail')]);
    }

    public function certificate_query_detail()  //详情页面  2  接上
    {
        $user_id = Session::get('query_user110');
        $time_query= Session::get('time_query');
        if (empty($time_query)) {
            return redirect(url('index/find/index'));
        }
        if ($time_query<time() ) {
            return redirect(url('index/find/index'));
        }
        if (empty($user_id)) {
            return redirect(url('index/find/index'));
        }
        $user = model('common/User')->find($user_id);
        $order = model('UserOrder') ->where(['o.user_id' =>  $user_id,'o.status' => 7, 'o.app_id' => ['in', '3,5']])->alias('o') ->join('user_order_detail c', 'c.order_id=o.id')->find();
        $certificate = Db::name('transcript_credential') ->where('order_id',  $order['order_id'])->find();//寻找所有trani的图片
        $certificate['certificate_png'] =unserialize( $certificate['certificate_png']); 
        if (!empty($certificate['certificate_png'])) {
            foreach ($certificate['certificate_png'] as $key => &$value) {
                $value = trim($value, '.');
            }
        }
        $ray_c = Db::name('certificate') ->where('order_id',  $order['order_id'])->find();//寻找所有trani的图片
        $ray_c['certificate_png'] =unserialize($ray_c['certificate_png']); 
        if (!empty($ray_c['certificate_png'])) {
            foreach ($ray_c['certificate_png'] as $key => &$value) {
                $value = trim($value, '.');
            }
        }
        $this->assign(compact('user', 'certificate','ray_c',  'order'   ));
        return $this->fetch();
    }















}