<?php

namespace app\index\controller;

use app\common\controller\HomeBase;
use app\common\controller\PayPal;
use app\common\controller\Revision;
use app\common\model\UserOrder;
use PayPalCheckoutSdk\Orders\OrdersCaptureRequest;
use think\Db;
use think\Exception;
use think\Log;
use think\Request;

class Pay extends HomeBase
{
    protected $type;
    protected $money;
    protected $currency;
    protected $order_no;
    protected $PayClient;
    protected $pay_success = '';
    protected $pay_error = '';
    protected $orderModel;

    public function __construct(Request $request = null)
    {
        parent::__construct($request);
        $this->type = $request->post('type', 'PayPal');
        $this->currency = $request->post('currency', 'USD');
        $this->order_no = $request->post('order_no', '');
        $this->orderModel = new UserOrder();
        if ($this->type == 'PayPal') {
            $this->PayClient = new PayPal();
        } else {
            throw new Exception($this->type . 'payment method is not supported temporarily');
        }
    }

    public function createOrder($order_no)
    {
        try {
            $order = $this->orderModel->where(['order_number' => $order_no])->find();
            if (empty($order)) {
                return status_code(12000);
            }
            $order = $order->toArray();
            if ($order['status'] == 0) {
                return status_code(12002);
            } elseif ($order['status'] >= 3) {
                return status_code(12001);
            }
            // 判断是否使用教育服务编码
            $edu_code = $order['edu_code'] ?? '';
            $use_id = $order['id'] ?? 0;
            if ($order['app_id'] == 19) {
                $preposition = model('common/UserOrder')
                    ->where(['user_id' => $order['user_id'], 'app_id' => ['in', '3,5'], 'edu_code' => ['neq', '']])
                    ->order('create_time')
                    ->find();
                $use_id = $preposition['id'] ?? 0;
                $edu_code = $preposition['edu_code'] ?? '';
            }
            $map = [
                'code' => $edu_code,
            ];
            $deduction = false;
            $find_code = Db::name('agency_code')->where($map)->find();
            if (!empty($find_code) && $find_code['use_id'] == $use_id) {
                $deduction = true;
            }
            if ($deduction === true) {
                $edu_pay = model('common/UserOrder')->save(['status' => 3, 'pay_type' => 3, 'pay_time' => date('Y-m-d H:i:s')], ['id' => $order['id']]);
                if ($edu_pay !== false) {
                    return status_code(20000, '', url('index/pay/pay_success', ['order_no' => $order_no]));
                } else {
                    return status_code(15000, 'Payment Failure');
                }
            }
            $pay_order = $this->PayClient->createOrder($order['fee']);
            if ($pay_order === false) {
                return status_code(12005);
            }
            $paypal_url = $pay_order['links'][1]['href'];
            $result = $this->orderModel->save([
                'pay_order_no' => $pay_order['id'],
                'expire_time' => date('Y-m-d H:i:s', time() + 3 * 60 * 60),
                'paypal_url' => $paypal_url,
            ], ['id' => $order['id']]);
            if (!$result) {
                return status_code(12004);
            }
            return status_code(20000, '', $paypal_url);
        } catch (Exception $e) {
            return status_code(10050, $e->getMessage() . $e->getFile() . $e->getLine());
        }
    }

    public function authorization()
    {
        Log::init(['type' => 'File', 'path' => '../paypal_logs/']);
        $token = input('get.token');
        $pay_id = input('get.PayerID');
        if (empty($token)) {
            abort(500, 'Missing required parameter:token');
        }
        $order = $this->orderModel->where('pay_order_no', $token)->find();
        if (empty($order)) {
            abort(500, 'The order does not exist');
        }

//        if ($order['status'] >= 3) {
//            abort(500, 'The current order has been paid, please do not pay again');
//        }

        $capture = $this->PayClient->capture($token);
        if ($capture === false) {
            abort(500, 'Order payment failed');
        }
        $order_number = $order['order_number'];
        $paypal_order_result = $this->orderModel->save(['status' => 3, 'pay_time' => date('Y-m-d H:i:s')], ['order_number' => $order_number]);
        $revision_result = Revision::evaluation($order, 3);
        if ($revision_result === false) {
            Log::write("修订记录添加失败:[order_number:{$order_number}]");
        }

        if ($paypal_order_result === false) {
            Log::write("订单状态更新失败:[order_number:{$order_number}]");
            abort(500, 'Order payment failed');
        }
        return $this->fetch('pay_success', ['order_no' => $order['order_number']]);
    }

    public function palpay_notice()
    {
        $notify = $this->PayClient->notify();
        if ($notify === true) {
            return 'success';
        }
        return 'fail';
    }

    public function pay_success($order_no)
    {
        $this->assign(['order_no' => $order_no]);
        return $this->fetch();
    }
}