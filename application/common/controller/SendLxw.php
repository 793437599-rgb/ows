<?php
namespace app\common\controller;

use app\common\model\UserOrder;
use think\Db;
use app\common\controller\WorldArea;

class SendLxw
{
    protected $error = '';
    protected $access_token='';
    public function __construct($access_token='')
    {
        $this->orderModel = new UserOrder();
        $config = config('third_part.lxw');
        $this->sendurl          = $config['url'];
        $this->client_id          = $config['client_id'];
        $this->session_key      = $config['session_key'];
        if(!empty($access_token)){
            $this->access_token = $access_token ;
        }
    }
    protected function get_access_token(&$errmsg='',$is_new=false)
    {
        $errmsg='';
        $is_new = $is_new===true;
        if($is_new||empty($this->access_token)){
            $OauthAccess = new \app\common\util\OauthAccess('lxw');
            $this->access_token = $OauthAccess->get_authorize(0,$is_new);
            if($this->access_token===false){
                $errmsg = $this->error = $OauthAccess->getError();
                return false;
            }
        }
        return $this->access_token;
    }

    /**
     * 向留信网需要授权的接口发起请求
     * @param string $url
     * @param array $data
     * @param string $method
     * @param boolean $is_file
     * @return array|string
     */
    protected function send_query_lxw($url='',$data=array(),$method='POST',$is_file=false){
        //$data['is_example'] =2;
        $is_new_access_token = false;
        GetAccessToken:
        $data['access_token'] =$this->get_access_token($errmsg,$is_new_access_token);
        if ($data['access_token'] === false) {
            return ['status' => 0,'msg' =>$errmsg];
        }
        foreach ($data as $key => $value) {
            if(is_string($value)){
                $data[$key]=trim($value);
            }
        }
        $data['sign'] = $this->get_sign($data); 
        $data=diy_urlencode($data);
        $method = strtoupper($method);
        $method=in_array($method,['POST','GET'])?$method:'POST';
        $header = array();
        $is_file = $is_file===true ; 
        $res = http_curl($url,$data,$method,$header,$is_file);
        if(is_json($res)){
            $res=json_decode($res,true); 
        }
        if(is_array($res)){
            if(isset($res['status'])&&$res['status']==0&&isset($res['data']['error'])&&$res['data']['error']=='invalid access_token'){
                $is_new_access_token=true;
                goto GetAccessToken;
            }
        }elseif(is_html($res)){
            echo '<br/>'.$res.'<br/>';
            die;
        }
        return $res;
    }
    protected function get_sign($data){
        foreach ($data as $key => $value) {
            if (empty( $value )) {
              unset($data[$key]);
            }
        }
        ksort($data);
        $string=http_build_query($data);
        $string=urldecode($string);             //dump($string);
        $string=md5($string);                   //dump($string);
        $string=$this->session_key.$string;     //dump($string);
        $string=md5($string);                   //dump($string);
        return $string;
    }

    public function get_key($title,$title2){
        $document_types = config('documet_type');  //获取id
        $title_list=array_column($document_types['en-us'], 'title');
        $title_key=array_keys($title_list,$title);  //1'Residency Documents'
        $title_key=$title_key[0];
        $title_id=$document_types['en-us'][$title_key]['id'];
        $title_name=$document_types['zh-cn'][$title_key]['title'];

        $title_key2=array_keys($document_types['en-us'][$title_key]['items'],$title2); //2   'Other'
        $title_key2=$title_key2[0];
        $title_id2=$document_types['en-us'][$title_key]['items_id'][$title_key2];
        $title_name2=$document_types['zh-cn'][$title_key]['items'][$title_key2];
        //dump($title);dump($title2); 
        return  array('key1' => $title_id,'key2' => $title_id2,'name1' => $title_name,'name2' => $title_name2);
    }

    public function get_degree($degree){
        if ($degree=='Master') {  
            $id=3;
        }elseif ($degree== 'Ph.D') {
            $id=4;
        }elseif ($degree== 'Diploma') {
            $id=1;
        }elseif ($degree== 'Bachelor') {
            $id=2;
        }elseif ($degree== 'Doctor') {
            $id=4;
        }else{
            $id=2;
        }
        return  $id;
    }

    public function get_university_type($order_detail){
        $type = $order_detail->getData('university_type');  
        if ($type==1) {
            $id=5;
        }elseif ($type==2) {
            $id=1;
        }elseif ($type==3) {
            $id=3;
        }elseif ($type==4) {
            $id=4;
        } elseif ($type==5) {
            $id=2;
        }else{
            $id='';
        } 
        return  $id;
    }

    public function get_school($school){
        $schools = Db::name('schools')->where(['name_en' => $school])->find();
        if ($schools) {
            return  $schools['name_cn'];
        }
        return  '';
    }

    public function get_major($major){
        $majors = Db::name('subject')->where(['title_en' => $major])->find();
        if ($majors) {
            return  $majors['title'];
        }
        return  '';
    }

    public function send_lwx($user_id='',$px='',$call='',$pwd='',$tran_id='',$app_id=''){
        $this->userModel = new \app\common\model\User();
        $order = $this->orderModel->where(['user_id'=>$user_id,'app_id' =>3])->with('orderDetail') ->find();
        $user = $this->userModel->find($user_id);
        //$order_detail = model('common/OrderDetail')->where(['order_id' => $order['id']])->find();
        if (!$order or !$user) {
            $this->error = '非法操作';
            return 0;  //返回 0
        }
        $order_detail = $order['order_detail'];
        $data=array();
        $data['numbers'] = $order_detail['id_no'];
        $data['xwcc'] =  $this->get_degree($order_detail['degree']); //+1参数
        $data['user_code'] =  $user['search_id'];
        $data['xingming'] = $user['chinaname']==''?$user['username']:$user['chinaname'];  
        $data['email'] =  $user['email'];
        $data['mobile_prefix'] = $px==''?$user['qujian']:$px;       
        $data['password'] =  $pwd==''?'123456a':$pwd; //传密码参数
        $data['mobile'] =  $call==''?$user['mobile']:$call; 
        $d_type=$this->get_key($order_detail['document_group'],$order_detail['document_type']); 
        $data['document_group_id'] = $d_type['key1'];
        $data['document_group'] = $order_detail['document_group'];
        $data['document_group_cn'] = $d_type['name1'];
        $data['document_type_id'] = $d_type['key2'];
        $data['document_type'] = $order_detail['document_type'];
        $data['document_type_cn'] = $d_type['name2'];
        $data['country'] =WorldArea::getcountryname($user['nationality']) ;
        $address = Db::name('user_address')->where(['user_id' => $user_id])->find();
        if ($address) {
            $data['province'] = $address['province']  ;  
            $data['city'] = $address['city']  ;
        }
        $data['cxbh'] =  $user['search_id'];
        $data['lxgj'] =   WorldArea::getcountryname($order_detail['edu_nationality']) ;
        $data['sex'] = $user['sex']=='Female'?2:1;
        $data['yxmc_cn'] = $this->get_school($order_detail['university']); 
        $data['yxmc_en'] = $order_detail['university'];
        $data['zymc_cn'] = $this->get_major($order_detail['major']);             
        $data['zymc_en'] = $order_detail['major'];
        $data['yxlx'] = $this->get_university_type($order_detail)  ;
        $data['birthday'] = $user['birth_time']; 
        $data['bysj'] = $order_detail['completion_date'];
        $data['xxsj'] =  $order_detail['start_date'];
        $data['kemu'] = $this->get_major($order_detail['faculty']); 
        $data['xuehao'] =$order_detail['student_id']; 
        $data['lx_province'] =$order_detail['edu_province']?:''; 
        $data['lx_city'] =$order_detail['edu_city']?:'';  
        $agency = '';
        if (!empty($order['agency_id'])) {
            $agency = model('common/Agency')->where('id', $order['agency_id'])->value('username');
        }
        $data['zerenren'] =  $agency;
        $data['examiner'] =  getdesignate($order['designate']);
        $examiner = Db::name('admin_user')->alias('a')->join('__ADMIN_PERSONNEL__ b','a.id=b.uid')->where(['a.id' => $order['designate']])->field('username,tra_name')->find();
        $data['examiner'] = [$examiner['username']];
        if(!empty($examiner['tra_name'])){
            $data['examiner'][] = $examiner['tra_name'];
        }
        $data['invite_code'] =  $order['edu_code']?:'';
        
        $url =  $this->sendurl.'/Open/Index/transmission_to_lxw_data';
        $res =  $this->send_query_lxw($url,$data,'POST');
        if (isset($res['status'])) {
            if ($res['status']==1) {
                $msg = 1;
                Db::name('user_tran')->where('id',$tran_id)->setField('lxw_send',2) ;  //d1 完成  
            } else {
                $msg = 0;
                $this->error = $res['msg'];
                Db::name('user_tran')->where('id',$tran_id)->update(['remake' =>$res['msg'], 'lxw_send' =>3]) ;  //d1 异常  记录错误
            }
            return $msg;
        }elseif(is_html($res)){
            echo $res;
            die;
        }elseif(is_string($res)){
            $this->error = $res;
        }else{
            $this->error = '个人信息数据传输失败';
        }
        return 0;
       
    }

    public function send_pic_lwx($user_id='',$tran_id='',$call=''){   
        $findlist = Db::name('user_picoss')->where(['tran_id'=>$tran_id,'uid' =>$user_id])->find();
        if (!$findlist) {
            $this->error='图片上传进度为空';
            return 0;  //返回 0
        }
        $order = $this->orderModel->where(['user_id'=>$user_id,'app_id' =>3])->with('orderDetail') ->find();
        $order_detail = $order['order_detail'];
        $data=array();
        //$data['session_key'] = 'wse_lxw_connection_str';
        $data['numbers'] = $order_detail['id_no'];
        $data['xwcc'] =  $this->get_degree($order_detail['degree']); //+1参数
        $data['call'] = $call;  
        $data['imgss'] = $findlist['imgss'];  
        $data['sfz_img'] = $findlist['sfz_img'];    
        $data['cj_img'] = $findlist['cj_img']; 
        $data['zs_img'] = $findlist['zs_img'];  
        $data['cj_img_sc'] = $findlist['cj_img_sc'];  
        $data['zs_img_sc'] = $findlist['zs_img_sc']; 
        $url =  $this->sendurl.'/Open/Index/upload_wse_img';
        $res =  $this->send_query_lxw($url,$data,'POST');
        if ($res['status']==1) {
            $msg = 1;
            Db::name('user_tran')->where('id',$tran_id)->setField('lxw_send',4) ;  //d1 完成  
        } else {
            $msg = 0;
            $this->error=$res['msg'];
            Db::name('user_tran')->where('id',$tran_id)->update(['remake' =>$res['msg'], 'lxw_send' =>5]) ;  //d1 异常  记录错误
        }
        return $msg;
    }

    public function send_pic($data,$pic,$sid,$url){  //old send  弃用 
        $remake='';
        foreach ($pic as $field => $value) {
            if(is_string($value)){
                if(strpos($value,'.')===0){
                    $path=$value;
                }elseif(strpos($value,'/')===0){
                    $path='.'.$value;
                }else{
                    $path=$value;
                }
                $realpath=realpath($path);
                if($realpath&&is_file($realpath)){
                    $path_info=pathinfo($realpath);
                    $mine=mime_content_type($realpath);
                    $file = new \CURLFile($realpath,$mine,$path_info['basename']);
                    //$file = new \CURLFile($realpath,$mine,$path_info['filename']);
                    $data[$field]=$file;
                }
            }elseif(is_array($value)){
                foreach($value as $key=>$val){
                    $is_file_temp=false;
                    if(is_string($val)){
                        if(strpos($val,'.')===0){
                            $path=$val;
                        }elseif(strpos($val,'/')===0){
                            $path='.'.$val;
                        }else{
                            $path=$val;
                        }
                        $realpath=realpath($path);
                        if($realpath&&is_file($realpath)){
                            $is_file_temp=true;
                            $path_info=pathinfo($realpath);
                            $mine=mime_content_type($realpath);
                            $file = new \CURLFile($realpath,$mine,$path_info['basename']);
                            //$file = new \CURLFile($realpath,$mine,$path_info['filename']);
                            $data[$field.'['.$key.']']=$file;
                        }
                    }
                }
            }
            $res = $this->send_query_lxw($url,$data,'POST',true);            //dump($res);
            if ($res['status']==1) {
                Db::name('user_pic_lxw')->where('id', $sid)->setField($field, 1) ;
            } else{
                $remake.=$res['msg'];
                Db::name('user_pic_lxw')->where('id', $sid)->update([$field =>2]);  //2 失败
            }  
        }
        if ($remake!='') {
            Db::name('user_pic_lxw')->where('tran_id', $sid)->setField('remake',$remake); //失败提示  d2 异常
            Db::name('user_tran')->where('id', $sid)->setField('lxw_send',5);   
            return 0;
        }
        Db::name('user_pic_lxw')->where('tran_id', $sid)->setField('end_time',time()) ;  //d2 完成 失败不保存 endtime
        Db::name('user_tran')->where('id', $sid)->setField('lxw_send',4);   
        return 1;
    }
      
    public function send_lwx_stuinfo($user_id='',$tran_id='' ){   
        $this->userModel = new \app\common\model\User();
        //$user_id =33;
        $order = $this->orderModel->where(['user_id'=>$user_id,'app_id' => 3])->with('orderDetail') ->find();
        $user = $this->userModel->find($user_id);
        //$order_detail = model('common/OrderDetail')->where(['order_id' => $order['id']])->find();
        if (!$order or !$user) {
            Db::name('user_tran')->where('id',$tran_id)->update(['class_remake' =>'用户数据缺失', 'lxw_send' =>7]) ;   //d3 异常
            return ['code' => 10004,'msg' =>'用户数据缺失'];
        }
        $order_detail = $order['order_detail'];
        $courses = Db::name('user_courses')->where(['user_id' => $user_id , 'order_id' =>$order['id']])
        //->field('semester,group_concat(curriculum_name) as curriculum_names')->group('semester')
        ->select();
        if (empty($courses)) {
            Db::name('user_tran')->where('id',$tran_id)->update(['class_remake' =>'暂无课程数据', 'lxw_send' =>8]) ;     //d3  无数据
            $template = './static/14tran_send.html';
            $conent = file_get_contents($template);
            $result = \org\Email::SendEmail('WSE message', $conent,$user['email'] );
            if ($result)  {
                Db::name('user_tran')->where('id',$tran_id)->setField('send_mail',1);
            } 
            return ['code' => 10004,'msg' =>'暂无课程数据'];
        }
        $xueqi=[];
        $class= [];
        $class_cn= [];
        $class_code= [];
        $gpa= [];
        $credit= [];
        $score= [];
        $grade= [];
        $time_info_sort=[];
        $data=array();
        //$data['session_key'] = 'wse_lxw_connection_str';
        $data['numbers'] = $order_detail['id_no'];
        $data['xwcc'] =  $this->get_degree($order_detail['degree']); //+1参数
        foreach ($courses as $key => $value) {
            // array_push($xueqi,$value['semester']);
            // array_push($class,$value['curriculum_name']);
            // array_push($class_cn,$value['curriculum_name_cn']);
            // array_push($class_code,trim($value['curriculum_code']));
            // array_push($gpa,trim($value['gpa']));
            // array_push($credit,trim($value['credit']));
            // array_push($score,trim($value['grade']));
            // array_push($grade,trim($value['score_grade']));
            // 
            // $data['time_info['.$key.']'] =  $value['semester']; //4年  
            // $data['time_info_sort['.$key.']'] = $key+1;
            // $data['curriculum_name['.$key.']'] =  $value['curriculum_name'];           
            // $data['curriculum_name_en['.$key.']'] = $value['curriculum_name_cn']; 
            // $data['curriculum_code['.$key.']'] =   trim($value['curriculum_code']);
            // $data['gpa['.$key.']'] = trim($value['gpa']); 
            // $data['credit['.$key.']'] =trim($value['credit']);
            // $data['score['.$key.']'] = trim($value['grade']);
            // $data['grade['.$key.']'] =trim( $value['score_grade']);  
            // 
            $data['time_info'][$key] =  $value['semester']; //4年  
            $data['time_info_sort'][$key] = $key+1;
            $data['curriculum_name'][$key] =  diy_trim($value['curriculum_name_cn']);
            $data['curriculum_name_en'][$key] =diy_trim( $value['curriculum_name']);
            $data['curriculum_code'][$key] =  diy_trim($value['curriculum_code'],' ') ;
            $data['gpa'][$key] = trim($value['gpa']); 
            $data['credit'][$key] =trim($value['credit']);
            $data['score'][$key] = trim($value['grade']);
            $data['grade'][$key] =trim( $value['score_grade']);
        } 
        // $data= array_map(function($val){
        //     if(is_string($val)){
        //         $val=urlencode($val);
        //     }elseif(is_array($val)){
        //         $val= array_map(function($v){
        //             if(is_string($v)){
        //                 $v=urlencode($v);
        //             }
        //             return $v;
        //         }, $val);
        //     }
        //     return $val;
        // }, $data);
    
        $url =  $this->sendurl.'/Open/Index/transmission_curriculum_score';
        //$url =url('index/order/aaa');
        $res = $this->send_query_lxw($url,$data,'POST');    //  修改状态
        
        if ($res['status']==1) {
            Db::name('user_tran')->where('id',$tran_id)->setField('lxw_send',6) ;//d3 完成  
            $template = './static/14tran_send.html';
            $conent = file_get_contents($template);
            $result1 = \org\Email::SendEmail('WSE message', $conent,$user['email'] );
            if ($result1)  {
                Db::name('user_tran')->where('id',$tran_id)->setField('send_mail',1);
            } 
            return 1;
        } else {

            Db::name('user_tran')->where('id',$tran_id)->update(['class_remake' =>$res['msg'], 'lxw_send' =>7]) ;
            $this->error = $res['msg'];
            return 0;
        }
    } 

    public function getError() {
        return $this->error;
    }
}