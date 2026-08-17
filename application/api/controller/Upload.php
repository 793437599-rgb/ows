<?php

namespace app\api\controller;

use app\index\controller\User;
use think\Controller;
use think\Session;
use think\Db;
use think\Exception;
use think\Cache;
use mailer\tp5\Mailer;
use think\Log;

/**
 * 通用上传接口
 * Class Upload
 * @package app\api\controller
 */
class Upload extends Controller
{
    // protected function _initialize()
    // {
    //     parent::_initialize();
    //     if (!Session::has('admin_id')) {
    //         $result = [
    //             'error'   => 1,
    //             'message' => '未登录'
    //         ];

    //         return json($result);
    //     }
    // }

    /**
     * 通用图片上传接口
     * @return \think\response\Json
     */
    public function upload()
    {
        if ($_FILES["file"]["type"] != 'application/pdf') {
            // if ($_FILES["file"]["size"] > 1 * 1024 * 1024) {
            //     return $this->compress();
            // }
        }
        $config = [
            //'size' => 4197152,
            'ext' => 'jpg,gif,png,bmp,pdf,jpeg'
        ];
        $file = $this->request->file('file');
        $upload_path = str_replace('\\', '/', ROOT_PATH . 'public/uploads');

        $save_path = '/uploads/';
        $info = $file->validate($config)->move($upload_path);
        if ($info) {
            $result = [
                'error' => 0,
                'url' => str_replace('\\', '/', $save_path . $info->getSaveName())
            ];
        } else {
            $result = [
                'error' => 1,
                'message' => '图片上传有误，请重试'
            ];
        }
        return json($result);
    }

    public function compress()
    {  //压缩
        $source = $_FILES['file']['tmp_name'];
        $image_name = substr(md5(time() . $source), 0, 13);
        $path = ROOT_PATH . 'public' . DS . 'uploads' . DS . date('Ymd');
        if (!file_exists($path)) {
            mkdir($path, 0777, true);
        }
        $save_path = $path . DS . $image_name . '.jpg';
        $img_url = DS . 'uploads' . DS . date('Ymd') . DS . $image_name . '.jpg';
        $percent = 0.5;  #原图压缩，不缩放
        $image = (new \Imgcompress($source, $percent))->compressImg($save_path);
        $result = [
            'error' => 0,
            'url' => str_replace('\\', '/', $img_url)
        ];
        return json($result);
    }


    public function okstee()
    {
        $data = $this->request->param();
        $file  = 'ott.log'; 
        if (!empty($data)) {
            file_put_contents($file,print_r($data,'true') ,FILE_APPEND);       
        } 
        if ($data['rsp_code'] != 'SUCCESS') {
            throw new Exception('参数错误!');
        }
        $user_key = 'xx';
        $aesKeyStr = strtoupper(substr(md5($data['md5'] . $user_key), 8, 16));
        $decrypted_data = $this->decrypt($data['data'], $aesKeyStr);
        $return_data_arr = (array)json_decode($decrypted_data, true);
        file_put_contents($file,print_r($return_data_arr,'true') ,FILE_APPEND);
        $inf ='-------------'.date('Y-m-d H:i:s').'-------------'  ;  
        file_put_contents($file,$inf,FILE_APPEND);
        try {
            $paypal_order_no = $return_data_arr['order_id'] ?? '';
            if (preg_match('/\d+/', $paypal_order_no, $arr)) {
                $oid = $arr[0];
            }
            $order = model('UserOrder')->where('id', $oid)->find();
            if (empty($order)) {
                throw new Exception("订单不存在: [paypal_order_no:{$paypal_order_no}]");
            }
            $order_number = $order['order_number'];

            $osid = Db::name('admin_personnel')->where('only', '1')->order('usetime')->find();
            if ($osid) {
                $mark=0;
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
                $paypal_order_result = model('UserOrder')->save(['status' => 3, 'designate' => $assign_id, 'pay_type' => 3, 'pay_time' => date('Y-m-d H:i:s')], ['id' => $order['id']]);
                if ($order['app_id'] == 19) {
                    User::data_sync_to_the_master_order($order['id']);
                }
                if ($mark == 0) {
                    Db::name('admin_personnel')->where('uid', $assign_id)->setfield('usetime', time());
                } 
            } else {
                $paypal_order_result = model('UserOrder')->save(['status' => 3, 'pay_type' => 3, 'pay_time' => date('Y-m-d H:i:s')], ['id' => $order['id']]);
            }
            if (Cache::get('orderno_' . $order['id'])) {
                Cache::rm('orderno_' . $order['id']);
            }
            Db::name('payinfo')->insert(['oid' => $order['id'],
                'order_id' => $return_data_arr['order_id'],
                'finish_time' => $return_data_arr['finish_time'],
                'biz_id' => $return_data_arr['bizpay_order_id'],
                'amount' => $return_data_arr['amount'],
            ]);
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

    public function arr2str($array)
    {
        //static变量：不会被销毁保留上次值
        static $list = [];
        if (is_array($array)) {
            foreach ($array as $key => $value) {
                $list[] = sprintf("%s => %s, ", $key, $value);
            }
        }
        return join("", $list);
    }

    public function decrypt($sStr, $sKey)
    {
        return (openssl_decrypt($sStr, 'aes-128-ecb', $sKey));
    }

  
    public function mailed()
    {
        $data = $this->request->param();
        $file  = 'ott.log'; 
        if (!empty($data)) {
            file_put_contents($file,print_r($data,'true') ,FILE_APPEND);       
        } 
 
        $user_key = 'xx';
        $aesKeyStr = strtoupper(substr(md5($data['md5'] . $user_key), 8, 16));
        $decrypted_data = $this->decrypt($data['data'], $aesKeyStr);
        $return_data_arr = (array)json_decode($decrypted_data, true);
        file_put_contents($file,print_r($return_data_arr,'true') ,FILE_APPEND);
        $inf ='-------------'.date('Y-m-d H:i:s').'-------------'  ;  
        file_put_contents($file,$inf,FILE_APPEND);
        try {
            $paypal_order_no = $return_data_arr['order_id'] ?? '';
            if (preg_match('/\d+/', $paypal_order_no, $arr)) {
                $oid = $arr[0];
            }
            $order =Db::name('email')->where('id', $oid)->find();
            if (empty($order)) {
                throw new Exception("订单不存在: [paypal_order_no:{$paypal_order_no}]");
            }
            $emaildata['pay_start'] = 1 ;
            $emaildata['finish_time'] =  $return_data_arr['finish_time'] ;
            $emaildata['biz_id'] = $return_data_arr['bizpay_order_id'] ;
            $emaildata['amount'] = $return_data_arr['amount'] ;
            $result = Db::name('email')->where('id', $oid)->update($emaildata);

            $icons = [
                './canada/images/admin_head_img.png',
                './canada/images/BlockHash.png',
                './canada/images/DepositTime.png',
                './canada/images/BlockHeight.png',
                './canada/images/TypeOfDeposit.png',
            ];

             $emailType = mailbox_recognition($order['mail']);
            $files = self::secretAnalysis($order['secret']);
            $type = $files['type'];
            $flag = false;
            if ( $type == 'report') {
                $flag = true;
            }
            $order['imgage'] =  explode(',',$order['imgage']);
            foreach ($order['imgage'] as $key => &$value) {
                $value = self::synthesis('.'.$value, $flag);
            }
            $files=$order['imgage'];
            $icons = array_map(function ($icon) use ($emailType){
                if ($emailType == 'gmail'){
                    $icon = trim($icon, '.');
                    return request()->domain() . '/' . $icon;
                }else{
                    return base64EncodeImage($icon);
                }
            },$icons);
            $mailer = Mailer::instance();
            $expire_date = date('Y-m-d',strtotime('+10 days'));
            $sendResult = $mailer->to($order['mail'])->subject('E-mail verification')->view('share/mail', compact('files','expire_date','icons'))->send();
            if ($sendResult) {
                $reqdate['start'] = 1;
                Db::name('email')->where('id', $oid)->update($reqdate);
            }
            return true;
        } catch (Exception $exception) {
            Log::write($exception->getMessage());
            return false;
        }

    }

    protected static function secretAnalysis($secret)
    {
        $param = unserialize(unlockString($secret));
        if (empty($param)) {
            return [];
        }
        $analysis = [];
        $analysis['type'] = $param['type'];
        if ($param['type'] == 'certificate') {
            $data = model('common/Certificate')->find($param['id']);
            $analysis['images'] = $data['certificate_png'];
            $analysis['user_id'] = $data['user_id'];
        } elseif ($param['type'] == 'report') {
            $data = model('common/Transcript')->find($param['id']);
            $analysis['images'] = $data['certificate_png'];
            $analysis['user_id'] = $data['user_id'];
        } else {
            $data = model('common/OrderDetail')->where('order_id', $param['id'])->find();
            $analysis['user_id'] = $data['user_id'];
            if ($param['type'] == 'diploma') {
                $analysis['images'] = $data['diploma'];
            } else {
                $analysis['images'] = $data['transcript'];
            }
        }
        return  $analysis;
    }

    protected static function synthesis($file,$flag = true){
        if (!file_exists($file)){
            return '';
        }
        $filename = pathinfo($file,PATHINFO_BASENAME);
        if ($flag) {
            $filepath = "./share/temp/{$filename}";
        } else {
            $filepath = $file;
        }
        $filepath = $file;
        if (!file_exists($filepath) && $flag === true) {
            $note = '../template/images/note.png';
            $IM = new \imagick($file);
            $watermark = new \Imagick($note);
            $IM->compositeImage($watermark, \Imagick::COMPOSITE_OVER, 50, 1260);
            $IM->writeImage($filepath);
            $IM->clear();
            $watermark->clear();
            if (!file_exists($filepath)) {
                return '';
            }
        } 
        return base64EncodeImage($filepath);
    }

 
 

}
