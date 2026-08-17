<?php


namespace app\common\controller;


use DocuSign\eSign\Model\Seal;
use think\Controller;
use think\Db;

class Revision extends Controller
{
    protected $project = [
        'Information modification',
        'Academic qualification evaluation',
        'Copy application'
    ];

    protected $orderField = [
        'document_group' => 'Document Group',
        'document_type' => 'Document Type',
        'id_no' => 'ID Number',
        'edu_nationality' => 'Educational Information-Country',
        'university' => 'Name of Academic Institution on final award',
        'degree_program' => 'Name of Certificate/Diploma/Degree Program',
        'degree' => 'Degree',
        'student_id' => 'Student Card Number',
        'graduated' =>'Whether to graduate',
        'faculty' => 'Main subject area of study',
        'major' => 'Major',
        'start_date' => 'Start date',
        'completion_date' => 'Graduation date',
        'edu_code' => 'Education Service Encode',
        'copy_number' => 'Number of copies',
        'express_id' => 'Delivery Options',
        'diploma' => 'Diploma',
        'transcript' => 'Transcript',
        'mail_nationality'=>'Mailing address-Country',
        'mail_province'=>'Mailing address-Province',
        'mail_city'=>'Mailing address-City',
        'address'=>'Mailing address-Address',
        'zip'=>'Mailing address-Postcode',
        'area_code'=>'Mailing address-area code',
        'mobile'=>'Mailing address-mobile',
    ];

    /**
     * Notes: 信息修改修订记录

     * Date: 2020/9/15  10:07
     * @param $ldata
     * @param $rdata
     * @return bool
     */
    public function modification($ldata,$rdata)
    {
        $comparison = [];
        $revised_ip = request()->ip();
        $num = 0;
        foreach ($rdata as $key => $val){
            if (!isset($ldata[$key]) || !isset($this->orderField[$key])){
                continue;
            }
            if (trim($ldata[$key]) != trim($val)){
                $comparison[$num]['before'] = self::transform($key,$ldata[$key]);
                $comparison[$num]['after'] = self::transform($key,$val);
                if (!empty($this->orderField[$key])){
                    $comparison[$num]['title'] = $this->orderField[$key];
                }else {
                    $comparison[$num]['title'] = 'Other information';
                }
                $num++;
            }
        }
        if (empty($comparison)){
            return true;
        }
        $revision = [];
        $revision['user_id'] = $ldata['id'];
        $revision['order_id'] = $ldata['id'];
        $revision['project'] = 'Data Update';
        $revision['revised_ip'] = $revised_ip;
        $revision['comparison'] = serialize($comparison);
        $revision['content'] = 'Successfully modified user information';
        $revision['create_time'] = date('Y-m-d H:i:s');
        $result = Db::name('revision')->insert($revision);
        if ($result){
            return true;
        }
        return false;
    }

    /**
     * Notes:

     * Date: 2020/9/15  10:07
     * @param $order
     * @param $status
     * @return bool
     */
    public static function evaluation($order, $status)
    {
        if (!in_array($status, [3, 4, 7]) || $order['status'] == $status) {
            return true;
        }
        $app = model('Applications')->find($order['app_id']);
        $project= empty($app['alias']) ? $app['name_en'] : $app['alias'];
        $revision = [];
        $revision['user_id'] = $order['user_id'];
        $revision['order_id'] = $order['id'];
        $revision['project'] = $project;
        $revision['revised_ip'] = request()->ip();
        $revision['comparison'] = '';
        if ($status == 3) {
            $revision['content'] = 'Successful payment application';
        } else if ($status == 4) {
            $revision['content'] = 'Successfully passed the review';
        } else if ($status == 7) {
            $revision['content'] = 'Evaluation completed';
        }
        $revision['create_time'] = date('Y-m-d H:i:s');
        $result = Db::name('revision')->insert($revision);
        if ($result) {
            return true;
        }
        return false;
    }


    public static function special($order)
    {
        $revision['user_id'] = $order['user_id'];
        $revision['order_id'] = $order['id'];
        $short_name = model('Applications')->where('id', $order['app_id'])->value('short_name');
        if ($short_name == 'D') {
            $revision['project'] = 'Copy Application';
            $revision['content'] = "Successfully paid and applied for " . $order['copy_number'] . " copies of the certificate";
        } else {
            return true;
        }
        $revision['revised_ip'] = request()->ip();
        $revision['comparison'] = '';
        $revision['create_time'] = date('Y-m-d H:i:s');
        $result = Db::name('revision')->insert($revision);
        if ($result) {
            return true;
        }
        return false;
    }

    public static function transform($key,$value)
    {
//        if ($key == 'status'){
//            if ($value > 0 && $value < 3) {
//                return 'Unpaid';
//            } else if ($value == 3) {
//                return 'Paid';
//            } else if ($value == 4) {
//                return 'Audited';
//            } else if ($value == 5) {
//                return 'Reviewed and Failed';
//            } else if ($value == 7) {
//                return 'Completed';
//            } else {
//                return 'Other';
//            }
//        } else
        if ($key == 'graduated') {
            if ($value == 1) {
                return 'Graduated';
            } else {
                return 'Undergraduate';
            }
        } else {
            return $value;
        }
    }

}
