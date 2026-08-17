<?php

namespace app\index\controller;
header("Content-type: text/html; charset=utf-8");

use app\common\controller\HomeBase;
use app\common\controller\PayPal;
use app\common\controller\Revision;
use app\common\model\UserOrder;
use PayPalCheckoutSdk\Orders\OrdersCaptureRequest;
use think\Db;
use think\Exception;
use think\Log;
use think\Cache;
use think\Request;

class Ottpay extends HomeBase
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
        $this->orderModel = new UserOrder();
    }

    public function encrypt($input, $key)
    {
        return base64_encode(openssl_encrypt($input, 'aes-128-ecb', $key, OPENSSL_RAW_DATA));
    }

    public function decrypt($sStr, $sKey)
    {
        return openssl_decrypt($sStr, 'aes-128-ecb', $sKey);
    }

    public function createOrder($order_no, $type)
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
//             判断是否使用教育服务编码
            $edu_code = $order['edu_code'] ?? '';
//            $use_id = $order['id'] ?? 0;
//            if ($order['app_id'] == 19) {
//                $preposition = model('common/UserOrder')
//                    ->where(['user_id' => $order['user_id'], 'app_id' => ['in', '3,5'], 'edu_code' => ['neq', '']])
//                    ->order('create_time')
//                    ->find();
//                $use_id = $preposition['id'] ?? 0;
//                $edu_code = $preposition['edu_code'] ?? '';
//            }
            $map = ['code' => $edu_code];
            $deduction = false;
            $find_code = Db::name('agency_code')->where($map)->find();
            // 价格为0 订单跳过支付
            if (!empty($find_code) || bccomp($order['fee'], 0, 2) == 0) {
                $deduction = true;
            }
            if ($deduction === true) {       //修改状态   有code的订单   
                $mark=0;
                $osid = Db::name('admin_personnel')->where('only', '1')->order('usetime')->find();
                if ($osid) {
                    $assign_id = $osid['uid'];
                    if (in_array($order['app_id'], [6, 7, 8, 19])) {
                        $pre_order = model('common/UserOrder')
                            ->where('user_id', $order['user_id'])
                            ->where('app_id', 'in', [3, 5, 18])
                            ->order('create_time')
                            ->find();
                        if (!empty($pre_order['designate'])) {
                            $assign_id = $pre_order['designate'];
                            $mark=1;
                        }
                    }
                    $edu_pay = model('common/UserOrder')->save(['status' => 3, 'designate' => $assign_id, 'pay_type' => 5, 'pay_time' => date('Y-m-d H:i:s')], ['id' => $order['id']]);
                    if ($mark == 0) {
                        Db::name('admin_personnel')->where('uid', $assign_id)->setfield('usetime', time());
                    } 
                } else {
                    $edu_pay = model('common/UserOrder')->save(['status' => 3, 'pay_type' => 5, 'pay_time' => date('Y-m-d H:i:s')], ['id' => $order['id']]);
                }
                if ($order['app_id'] == 19) {
                    // 同步数据
                    User::data_sync_to_the_master_order($order['id']);
                }
                if ($edu_pay !== false) {
                    return status_code(20001, '', url('index/Ottpay/pay_success', ['order_no' => $order_no]));
                } else {
                    return status_code(15000, 'Payment Failure');
                }
            }
            //$this->orderModel->save(['pay_order_no' => $pay_order['id'], 'expire_time' => date('Y-m-d H:i:s', time() + 3 * 60 * 60)], ['id' => $order['id']]);
           
            $payno = $this->generateRandomString() . $order['id'];
            while (Db::name('usecode')->where('no', $payno)->find()) {
                $payno = $this->generateRandomString() . $order['id'];
            }
            // $newno = substr($order['order_number'],0,strlen($order['order_number'])-10); 
            // $newno =$newno.time();
            $fee = (string)($order['fee'] * 100);
            $return_data_arr = $this->get_code($fee, $type, $payno);
            if ($return_data_arr === false) {
                return status_code(15000, 'Payment Failure');
            }
            if (@strlen($return_data_arr['code_url']) > 5) {
                Cache::set('orderno_' . $order['id'] . $type, $return_data_arr['code_url'], 540);
                Db::name('usecode')->insert(['no' => $payno, 'code' => $return_data_arr['code_url'], 'create_time' => date('Y-m-d H:i:s')]);
                //model('common/UserOrder')->save(['pay_url'=>$return_data_arr['code_url']],['id'=>$order['id']]);
                return status_code(20000, '', $return_data_arr['code_url']);
            }
            Db::name('usecode')->insert(['no' => $payno, 'code' => '', 'create_time' =>  date('Y-m-d H:i:s')]);
            return status_code(15000, 'Payment Failure');
        } catch (Exception $e) {
            return status_code(10050, $e->getMessage() . $e->getFile() . $e->getLine());
        }

    }

    public function get_code($amount, $type, $no)
    {
        $data_array = array();
        //$data_array['amount'] = $order['fee']*100;
        $data_array['amount'] = $amount;
        $data_array['biz_type'] = $type; //3中方法  requst 获取来的数据  ALIPAY  UNIONPAY
        $data_array['operator_id'] = '0000022888'; //using your 10-digital operator number provided by OTTPAY;
        $data_array['order_id'] = $no;   //不同一
        $data_array['call_back_url'] = "https://wse.org/api/upload/okstee"; //   接收到的数据
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

    public function huilv()
    {
        $data_array['fee_type'] = 'USD';
        $temp_data_array = $data_array;
        ksort($temp_data_array);
        $data_str = implode(array_values($temp_data_array)); //拼接
        $data_md5 = strtoupper(md5($data_str));      //转大写
        $user_key = '7E115684782CE541'; //using your Sign Key provided by OTTPAY;
        $aesKeyStr = strtoupper(substr(md5($data_md5 . $user_key), 8, 16));
        $data_json = json_encode($data_array);
        $encrypted_data = $this->encrypt($data_json, $aesKeyStr);
        $params_array = array();
        $params_array['action'] = 'EX_RATE_QUERY';
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

    public function pay_success($order_no)
    {
        $this->assign(['order_no' => $order_no]);
        return $this->fetch('/pay/pay_success');
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

    public function generateRandomString($length = 11)
    {
        $characters = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ';
        $charactersLength = strlen($characters);
        $randomString = '';
        for ($i = 0; $i < $length; $i++) {
            $randomString .= $characters[rand(0, $charactersLength - 1)];
        }
        return $randomString;
    }


    public function pay_status($order_id)
    {
        $order = $this->orderModel->where(['id' => $order_id])->find();
        if (empty($order)) {
            return status_code(12000);
        }
        if ($order['status'] == 7) {
            return status_code(20000, '', url('index/Ottpay/pay_success', ['order_no' => $order['order_number']]));
        } 
        if ($order['status'] == 3) {
            return status_code(20000, '', url('index/Ottpay/pay_success', ['order_no' => $order['order_number']]));
        }
        return status_code(12000);
    }

    public function pay_fee($order_id,$chen)
    {  
        $order = Db::name('user_order')->where(['id' => $order_id])->field('tag,online,fee')->find();
        if ($chen==1 ) {
            $money  =Db::name('system')->where('id', 29)->value('value'  );
            $money  =   round($money*1.13 );  
             if (  $order ['online']==0 ) {
                $data['online'] = 1;
                $data['fee'] =  $order['fee']+$money;
                Db::name('user_order')->where('id', $order_id) ->update($data);
             }
        }
        // else{
        //     if (  $order ['online']==1 ) {
        //         $data['online'] = 0;
        //         $data['fee'] =  $order['fee']-4972 ;
        //         Db::name('user_order')->where('id', $order_id) ->update($data);
        //      }
        // }
        return status_code(20000);
    }

    public function pay_fee_onlien($order_id)
    {  
        $order = Db::name('user_order')->where(['id' => $order_id])->field('tag,online,fee')->find();
        $money  =Db::name('system')->where('id', 29)->value('value'  );
        $money  =   round($money*1.13 );  
        $data['fee'] =   $order['fee']+$money;
        Db::name('user_order')->where('id', $order_id) ->update($data);
        return status_code(20000);
    }

    public function pay_fee_school($order_id)
    {  
        $order = Db::name('user_order')->where(['id' => $order_id])->field('tag,online,fee')->find();
        $money  =Db::name('system')->where('id', 31)->value('value'  );
        $money  =   round($money*1.13 );  
        $data['fee'] =   $money;
        Db::name('user_order')->where('id', $order_id) ->update($data);
        return status_code(20000);
    }


}
