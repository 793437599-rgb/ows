<?php
namespace app\common\controller;

use PayPalCheckoutSdk\Core\AccessTokenRequest;
use PayPalCheckoutSdk\Core\PayPalHttpClient;
use PayPalCheckoutSdk\Core\ProductionEnvironment;
use PayPalCheckoutSdk\Core\SandboxEnvironment;
use PayPalCheckoutSdk\Orders\OrdersCaptureRequest;
use PayPalCheckoutSdk\Orders\OrdersCreateRequest;
use PayPalCheckoutSdk\Orders\OrdersGetRequest;
use PayPalHttp\HttpException;
use think\Cache;
use think\Controller;
use think\Db;
use think\Exception;
use think\Log;

class PayPal extends Controller
{
    private $client;
    private $clientId;
    private $clientSecret;
    private $environment;
    private $AccessToken;
    public function __construct()
    {
        Log::init(['type' => 'File', 'path' => '../paypal_logs/']);
        $this->clientId = ' ';
        $this->clientSecret = ' ';
//        $this->clientId = ' ';
//        $this->clientSecret = ' ';
        $this->environment = new ProductionEnvironment($this->clientId, $this->clientSecret);
//        $this->environment = new SandboxEnvironment($this->clientId, $this->clientSecret);
        $this->client = new PayPalHttpClient($this->environment);

        $this->getAccessToken();
    }
    /**
     * 获取访问令牌
     * @return bool
     */
    private function getAccessToken()
    {
        $request = new AccessTokenRequest($this->environment);
        $access_token = Cache::get('paypal_assess_token');
        try {
            if (!empty($cache_token)) {
                return $this->AccessToken = $access_token;
            } else {
                $response = $this->client->execute($request);
                $response = json_decode(json_encode($response), true);
                if ($response['statusCode'] != 200) {
                    throw new Exception('访问令牌获取失败');
                }
                Cache::set('paypal_assess_token', $response['result']['access_token'], 3 * 60 * 60);
                $access_token = $response['result']['access_token'];
            }
            $this->AccessToken = $access_token;

            
        } catch (HttpException  $ex) {
            throw new Exception($ex->getMessage());
        }
    }

    public function capture($token)
    {
        try {
            $response = $this->checkoutOrder($token);
            if (!isset($response->result->status)) {
                throw new HttpException(__CLASS__ . '参数未获取到，token:' . $token);
            }
            $status = $response->result->status;
            switch ($status) {
                case 'COMPLETED':       //订单已经完成
                    break;
                case 'APPROVED':
                    $captureResponse = $this->captureOrder($token);
                    if ($captureResponse->result->status != 'COMPLETED') {
                        throw new HttpException(__CLASS__ . 'captureOrder 失败，token:' . $token . ' status:' . $captureResponse->result->status);
                    }
                    break;
                default:        //其他
                    throw new HttpException(__CLASS__ . '参数未知，token:' . $token . ' status:' . $status);
            }
            //订单支付完成
            return true;
        } catch (HttpException $exception) {
            Log::write($exception->getMessage());
            return false;
        }
    }
    /**
     * 创建订单
     */
    public function createOrder($amount, $debug = false)
    {
        $request = new OrdersCreateRequest();
//        $request->prefer('return=representation');
        $request->body = self::buildRequestBody($amount);
        $request->headers['Authorization'] = 'Bearer ' . $this->AccessToken;
        $request->prefer('return=minimal');
        $response = $this->client->execute($request);
        if ($debug) {
            print "Status Code: {$response->statusCode}\n";
            print "Status: {$response->result->status}\n";
            print "Order ID: {$response->result->id}\n";
            print "Intent: {$response->result->intent}\n";
            print "Links:\n";
            foreach ($response->result->links as $link) {
                print "\t{$link->rel}: {$link->href}\tCall Type: {$link->method}\n";
            }
        }
        if ($response->result->status == 'CREATED') {
            return json_decode(json_encode($response->result), true);
        }
        return false;
    }
    
    public function captureOrder($orderId, $debug = false)
    {
        $request = new OrdersCaptureRequest($orderId);
        $request->headers['Authorization'] = 'Bearer ' . $this->AccessToken;
        $request->prefer('return=minimal');
        $response = $this->client->execute($request);
        if ($debug) {
            print "Status Code: {$response->statusCode}\n";
            print "Status: {$response->result->status}\n";
            print "Order ID: {$response->result->id}\n";
            print "Links:\n";
            foreach ($response->result->links as $link) {
                print "\t{$link->rel}: {$link->href}\tCall Type: {$link->method}\n";
            }
            print "Capture Ids:\n";
            foreach ($response->result->purchase_units as $purchase_unit) {
                foreach ($purchase_unit->payments->captures as $capture) {
                    print "\t{$capture->id}";
                }
            }
        }
        return $response;
    }

    public function checkoutOrder($order_no)
    {
        $request = new OrdersGetRequest($order_no);
        $request->headers['Authorization'] = 'Bearer ' . $this->AccessToken;
        $response = $this->client->execute($request);
        return $response;
    }

    private static function buildRequestBody($amount)
    {
        $purchase_units = [
            [
                "amount" => [
                    "value" => $amount,
                    "currency_code" => "USD",
                ],
//                'description'=>$order['name_en'],
//                'soft_descriptor'=>$order['name_en'],
//                'amount_breakdown'=>[
//                    'item_total'=> $order['fee'],
//                    'shipping'=>$order['send_for'],
//                ],
            ],
        ];
        $orderbody = [];
        $orderbody['intent'] = 'CAPTURE';
        $orderbody['purchase_units'] = $purchase_units;
        $orderbody['intent'] = 'CAPTURE';
        $orderbody['application_context'] = [
            'return_url' => 'https://www.wse.org/index/pay/Authorization',
            'cancel_url' => 'https://www.wse.org/cancel'
        ];
        return $orderbody;
    }

    public function notify()
    {
        $data = file_get_contents("php://input");
        $data = json_decode($data, true);
        try {
            $content = $data['resource'] ?? '';
            if (empty($content)) {
                throw new Exception('参数错误!');
            }
            $paypal_order_no = $content['id'] ?? '';
            $order = model('UserOrder')->where('pay_order_no', $paypal_order_no)->find();
            if (empty($order)) {
                throw new Exception("订单不存在: [paypal_order_no:{$paypal_order_no}]");
            }
            $response = $this->checkoutOrder($paypal_order_no);
            $status = $response->result->status;
            switch ($status) {
                case 'COMPLETED':       //订单已经完成
                    break;
                case 'APPROVED':
                    throw new Exception(__CLASS__ . '订单尚未捕获，token:' . $paypal_order_no . ' status:' . $status);
                    break;
                default:        //其他
                    throw new Exception(__CLASS__ . '参数未知，token:' . $paypal_order_no . ' status:' . $status);
            }
            $order_number = $order['order_number'];
            $paypal_order_result = model('UserOrder')->save(['status' => 3, 'pay_time' => date('Y-m-d H:i:s')], ['id' => $order['id']]);
            $revision_result = Revision::evaluation($order, 3);
            if ($revision_result === false) {
                Log::write("修订记录添加失败:[order_number:{$order_number}]");
            }

            if ($paypal_order_result === false) {
                throw new Exception("订单状态更新失败:[order_number:{$order_number}]");
            }
            return true;
        } catch (Exception $exception) {
            Log::write($exception->getMessage());
            return false;
        }
    }

    public function verifyNotify($data)
    {
        try {
            $content = $data['resource'] ?? '';



            if (empty($order)) { //订单不存在

            }
            if ($order['status'] > 2) { //订单已经已支付
                Log::write('订单已支付:' . $paypal_order_no);
                return true;
            }
            //判断订单是否伪造
            $payment = $this->checkoutOrder($paypal_order_no);
            if (!$payment) { // 属于伪造订单
                Log::write('伪造订单:' . $paypal_order_no);
                return false;
            }
            if ($payment['status'] != 'COMPLETED' && $payment['status'] != 'PENDING') { // 支付未完成
                Log::write('支付未完成2:' . $paypal_order_no);
                return false;
            }

            $create_time = $payment['purchase_units'][0]['payments']['captures'][0]['create_time'];
            $update_time = $payment['purchase_units'][0]['payments']['captures'][0]['update_time'];
            $gross_amount = $payment['purchase_units'][0]['payments']['captures'][0]['seller_receivable_breakdown']['gross_amount']['value'] ?? 0;
            $paypal_fee = $payment['purchase_units'][0]['payments']['captures'][0]['seller_receivable_breakdown']['paypal_fee']['value'] ?? 0;
            $net_amount = $payment['purchase_units'][0]['payments']['captures'][0]['seller_receivable_breakdown']['net_amount']['value'] ?? 0;
            $pay_time = date('Y-m-d H:i:s',strtotime($update_time));
            $data = [
                'pay_time' => $pay_time,
                'paypal_fee' => $paypal_fee,
                'gross_amount' => $gross_amount,
                'net_amount' => $net_amount,
            ];
            if ($payment['status'] == 'COMPLETED' || $payment['status'] == 'APPROVED') {
                $data['status'] = 3;
            }

            if ($payment['status'] == 'PENDING') {
                $data['status'] = 2;
            }
            $paypal_order_result = Db::name('user_order')->where('pay_order_no', $paypal_order_no)->update();
            if (!$paypal_order_result) {
                Log::write('订单状态更新失败:' . $paypal_order_no);
                return false;
            }
            writeupdatelog($order['user_id'], $order['app_name'],'成功支付' );
            $revision_result = Revision::evaluation($order,3);
            if (!$revision_result) {
                Log::write('修订记录添加失败:' . $paypal_order_no);
            }
            Log::write('订单支付成功:' . $paypal_order_no);
            return true;
        }catch (\Exception $exception){
            Log::write('订单支付失败:' . $exception->getMessage());
            return true;
        }
    }
}