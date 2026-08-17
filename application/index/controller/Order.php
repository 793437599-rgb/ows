<?php


namespace app\index\controller;


use app\common\controller\HomeBase;
use app\common\model\UserOrder;
use think\Db;
use app\common\controller\WorldArea;
use think\Session;

class Order extends HomeBase
{
    protected $orderModel;

    public function _initialize()
    {
        parent::_initialize();
        $this->orderModel = new UserOrder();
    }

    /**
     * Notes:订单详情
     * @param  string  $order_no
     * @return mixed
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function detail($order_no = '')
    {
        $user_order = Db::name('user_order')->alias('o')
            ->join('applications a', 'o.app_id=a.id', 'left')
            ->join('user u', 'o.user_id = u.id', 'left')
            ->join('user_order_detail d', 'd.order_id = o.id', 'left')
            ->field('d.*,d.id as detail_id,o.*,a.name_cn,a.name_en,a.name_jp,a.name_ru,a.name_kr,a.name_fa,a.name_de,u.username,u.sex,u.email')
            ->where('o.order_number', $order_no)
            ->find();
        !empty($user_order['certificate_img']) ? $user_order['certificate_img'] = unserialize($user_order['certificate_img']) : '';
        !empty($user_order['diploma']) ? $user_order['diploma'] = unserialize($user_order['diploma']) : '';
        !empty($user_order['hand_diploma']) ? $user_order['hand_diploma'] = unserialize($user_order['hand_diploma']) : '';
        !empty($user_order['transcript']) ? $user_order['transcript'] = unserialize($user_order['transcript']) : '';
        !empty($user_order['hand_transcript']) ? $user_order['hand_transcript'] = unserialize($user_order['hand_transcript']) : '';
        !empty($user_order['prove_file']) ? $user_order['prove_file'] = unserialize($user_order['prove_file']) : '';
        $name = getNameLang($this->lang);
//        dump($user_order);die();
        $applications = Db::name('applications')->field("*, {$name} as name")->where('status', 1)->select(); // 所有应用数据
        $user = model('User')->find($user_order['user_id']);
        $shapping_address = $user_order['address'] . ',' . $user_order['mail_city'] . ',' . $user_order['mail_province'] . ',' . $user_order['mail_nationality'];
        $this->assign([
            'order' => $user_order,
            'applications' => $applications,
            'user' => $user,
            'shapping_address' => $shapping_address,
        ]);
        return $this->fetch();
    }

    public function precondition($app_id)
    {
        $user_id = $this->user_id;
        // 判断之前有没有学历评估订单
        $where = [];
        $where['user_id'] = $user_id;
        $app = model('Applications')->find($app_id);
        // 应用前置应用
        if (empty($app['preapplication_id'])) {
            return status_code(20000, 'ok');
        }
        $where['app_id'] = ['in', $app['preapplication_id']];
        $where['status'] = 7;
        $pre_order = $this->orderModel->where($where)->find();
        if (empty($pre_order)) {
            return status_code(12006);
        }
        return status_code(20000, 'ok');
    }

    public function academic_update()
    {
        $user_id = Session::get('user_id');
        $name = getNameLang($this->lang);
        $app = Db::name('applications')->field("*, {$name} as name")->where('id', 'in', [3, 18])->find();
        $academic_order = $this->orderModel->where(['user_id' => $user_id, 'app_id' => 19])->find();
        if (empty($academic_order)) {
            $academic_order->app_id = $app['id'];
            $academic_order->user_id = $user_id;
            $academic_order->order_number = 'E' . $app['short_name'] . rand('100000', '999999') . time();
            $academic_order->app_name = $app['name_cn'];
            $academic_order->name_en = $app['name_en'];
            $academic_order->fee = $app['fee'];
            $academic_order->basic_fee = $app['fee'];
            $academic_order->create_time = date('Y-m-d H:i:s');
            $academic_order->create_ip = parent::get_ip();
            $academic_order->save();
        }
        if (!$academic_order) {
            return json(['code' => 0, 'msg' => 'The system is busy, please try again later']);
        } else {
            return json(['code' => 1, 'msg' => 'ok']);
        }
    }

    public static function detailData($data)
    {
        $order_detail_data = [
            'user_id' => $data['user_id'],
            'order_id' => $data['order_id'],
        ];
        isset($data['document_group']) ? $order_detail_data['document_group'] = $data['document_group'] : '';
        isset($data['document_type']) ? $order_detail_data['document_type'] = $data['document_type'] : '';
        isset($data['id_number']) ? $order_detail_data['id_no'] = $data['id_number'] : '';
        isset($data['certificate_img']) ? $order_detail_data['certificate_img'] = $data['certificate_img'] : '';
        isset($data['edu_nationality']) ? $order_detail_data['edu_nationality'] = $data['edu_nationality'] : '';
        isset($data['edu_province']) ? $order_detail_data['edu_province'] = $data['edu_province'] : '';
        isset($data['edu_city']) ? $order_detail_data['edu_city'] = $data['edu_city'] : '';
        isset($data['countys']) ? $order_detail_data['edu_county'] = $data['countys'] : '';
        isset($data['school']) ? $order_detail_data['university'] = $data['school'] : '';
        isset($data['university_type']) ? $order_detail_data['university_type'] = $data['university_type'] : '';
        isset($data['degree_program']) ? $order_detail_data['degree_program'] = $data['degree_program'] : '';
        isset($data['degree']) ? $order_detail_data['degree'] = $data['degree'] : '';
        isset($data['student_id']) ? $order_detail_data['student_id'] = $data['student_id'] : '';
        isset($data['graduated']) ? $order_detail_data['graduated'] = $data['graduated'] : '';
        isset($data['faculty']) ? $order_detail_data['faculty'] = $data['faculty'] : '';
        isset($data['major']) ? $order_detail_data['major'] = $data['major'] : '';
        isset($data['diploma']) ? $order_detail_data['diploma'] = $data['diploma'] : '';
        isset($data['transcript']) ? $order_detail_data['transcript'] = $data['transcript'] : '';
        isset($data['hand_transcript']) ? $order_detail_data['hand_transcript'] = $data['hand_transcript'] : '';
        isset($data['hand_diploma']) ? $order_detail_data['hand_diploma'] = $data['hand_diploma'] : '';
        isset($data['national']) ? $order_detail_data['mail_nationality'] = $data['national'] : '';
        isset($data['province']) ? $order_detail_data['mail_province'] = $data['province'] : '';
        isset($data['city']) ? $order_detail_data['mail_city'] = $data['city'] : '';
        isset($data['county']) ? $order_detail_data['mail_county'] = $data['county'] : '';
        isset($data['detailed']) ? $order_detail_data['address'] = $data['detailed'] : '';
        isset($data['qujian']) ? $order_detail_data['area_code'] = $data['qujian'] : '';
        isset($data['mobile']) ? $order_detail_data['mobile'] = $data['mobile'] : '';
        isset($data['addressee']) ? $order_detail_data['addressee'] = $data['addressee'] : '';
        isset($data['zip']) ? $order_detail_data['zip'] = $data['zip'] : '';
        isset($data['completion_date']) ? $order_detail_data['start_date'] = $data['completion_date'] : '';
        isset($data['issued_date']) ? $order_detail_data['completion_date'] = $data['issued_date'] : '';
        isset($data['update_desc']) ? $order_detail_data['update_desc'] = $data['update_desc'] : '';
        isset($data['prove_file']) ? $order_detail_data['prove_file'] = $data['prove_file'] : '';
        isset($data['protocol']) ? $order_detail_data['protocol'] = $data['protocol'] : '';
        $order_detail_data['username'] = $data['username'];
        $order_detail_data['nationality'] = $data['nationality'];
        do {
            $search_id = 'no' . date('Y')  . generateRandomString(8);
            $res = Db::name('user_order_detail')->where('education_code', $search_id)->find();
        } while ($res);
        $order_detail_data['education_code'] = $search_id;
        return $order_detail_data;
    }

    /**
     * 计算订单相关费用
     * @param $order_id 订单id
     * @return array|\think\response\Json
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public static function calculateOrder($order_id)
    {
        $orderModel = new UserOrder();
        $order = $orderModel->find($order_id);
        $master_order_price = $orderModel->where('id', $order['master_order_id'])->value('update_price');
        if (!$order) {
            return status_code(12000);
        }
        $app = $order->application;
        if (!$app) {
            return status_code(10004);
        }
        $data = [];
        // 大使馆认证费用
        $embassy = Db::name('embassy')->where(['id' => $order['embassy_id']])->find();
        // 快递费用
        $express = Db::name('express')->where(['id' => $order['express_id']])->find();

        $embassy_fee = 0;
        $express_fee = 0;
        $embassy_tax = 0;
        $express_tax = 0;
        if (!empty($embassy)) {
            $embassy_fee = $embassy['price'];
            $embassy_tax = $embassy['tax'];
        }
        if (!empty($express)) {
            $express_fee = $express['base_price'];
            $express_tax = $express['tax'];
        }
        $fee = $app['fee'];
        if ($order['app_id'] == 19 && isset($master_order_price) && $master_order_price != $fee) {
            $fee = $master_order_price;
        }
        $copy_fee = $app['copy_fee'] * $order['copy_number'];
        $total_tax = $fee * $app['tax'] + $copy_fee * $app['tax'] + $embassy_fee * $embassy_tax + $express_fee * $express_tax;
        $total_order_fee = $fee + $app['copy_fee'] * $order['copy_number'] + $embassy_fee + $express_fee;
        $total_fee = $total_tax + $total_order_fee;
        $data['total'] = ['tax_fee' => $total_tax, 'order_fee' => $total_order_fee, 'fee' => $total_fee];
        $data['embassy'] = ['fee' => $embassy_fee, 'tax' => $embassy_tax];
        $data['express'] = ['fee' => $express_fee, 'tax' => $express_tax];
        $data['copy'] = ['fee' => $copy_fee, 'tax' => $app['tax']];
        $data['app'] = ['fee' => $fee, 'tax' => $app['tax']];

        if (bccomp((string)$order['fee'], (string)$total_fee, 2) !== 0) {
            $order_data = [];
            $order_data['fee'] = $total_fee;
            $order_data['basic_fee'] = $fee;
            $order_data['copy_fee'] = $copy_fee;
            $order_data['express_fee'] = $express_fee;
            $order_data['embassy_fee'] = $embassy_fee;
            $order_data['taxes'] = $total_tax;
            $result = $orderModel->save($order_data, ['id' => $order['id']]);
            if (!$result) {
                return status_code(12004);
            }
        }
        return status_code(20000, '', $data);
    }

    public function third_save()  //留信的数据传输
    {
        // sleep(3);
        // return 0;
        $user_id = $this->user_id;
        if ($user_id) {
            if ($this->request->isPost()) {
                $data = $this->request->param('', '', 'trim');
                $data['user_id'] = $user_id;
                $data['create_time'] = date("Y-m-d H:i:s");
                $data['create_ip'] = parent::get_ip();
                $data['lxw_send'] = 1;          //1 初始化状态   需要进行定时任务
                $tran_id = Db::name('user_tran')->insert($data);  //insertGetId
                //$res=$this->send_lwx($user_id,$data['quhao'],$data['call'],$data['pwd'],$tran_id); 
                if ($tran_id) {
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
 
 

    function aaa()
    {
        dump('请求方法：' . request()->method());
        $data = request()->param('', '', 'trim,urldecode');
        dump($data);
    }


}