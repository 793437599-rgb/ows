<?php
namespace app\common\util;

use Think\Controller;
use Think\Log;
use Think\Db;
use app\common\model\UserOrder;
use app\common\controller\WorldArea;
use Think\Exception;

vendor('AntCloudSDKCore.Notary');

class AntChainContract extends Controller
{
    protected $error = '';
    protected $error_list = array();
    protected $url = '';
    protected $param = array();
    private $notary;
    private $UserChainApply;
    private $transaction_id;
    private $AntChainFileHash;
    private $userinfo;
    private $infomation_info;
    private $user_id;
    private $info_id;
    private $chain;
    private $user_seatext;
    private $logs_root = './Logs/';
    private $oosIntranetUrl = ' com';
//    private $oosIntranetUrl = 'img.cscss.com.cn';

    // 用户基本信息
    protected $user_txt_field = array(
        array('field' => 'cxbh', 'title' => '查询编号', 'table' => 'infomation',),
        array('field' => 'xingming', 'title' => '姓名', 'table' => 'infomation',),
        array('field' => 'xingbie', 'title' => '性别', 'table' => 'infomation', 'alias' => array(0 => '男', 1 => '女')),
        array('field' => 'csrq', 'title' => '出生日期', 'table' => 'infomation',),
        array('field' => 'sfzh', 'title' => '身份证号', 'table' => 'infomation',),
        array('field' => 'fzhm', 'title' => '护照号码', 'table' => 'infomation',),
        array('field' => 'country', 'title' => '国籍', 'table' => 'user',),
        array('field' => 'provite', 'title' => '籍贯：省', 'table' => 'user',),
        array('field' => 'city', 'title' => '籍贯：市', 'table' => 'user',),
        array('field' => 'area', 'title' => '籍贯：区', 'table' => 'user',),
        array('field' => 'reg_ip', 'title' => '注册ip', 'table' => 'infomation',),
        //        array('field' => '', 'title' => '支付账号', 'table' => '',),
        array('field' => 'paytime', 'title' => '支付时间', 'table' => 'order',),
        array('field' => 'country', 'title' => '留学国家', 'table' => 'infomation',),
        array('field' => 'haspay', 'title' => '费用', 'table' => 'order',),
        array('field' => 'xxxs', 'title' => '是否自费', 'table' => 'infomation',),
        array('field' => 'xwcc', 'title' => '学位层次', 'table' => 'infomation',),
        array('field' => 'xxsj', 'title' => '开始学习时间', 'table' => 'infomation',),
        array('field' => 'entry-exit', 'title' => '毕业时评价类型：是否常规', 'table' => 'infomation',),
        array('field' => 'xuehao', 'title' => '在校学生号', 'table' => 'infomation',),
        array('field' => 'yuyan', 'title' => '语言', 'table' => 'analysis'),
        array('field' => 'entry_exit_day', 'title' => '有效出入境天数', 'table' => 'infomation',),
        array('field' => 'rzrq', 'title' => '审核时间', 'table' => 'infomation',),
        array('field' => 'qzjy', 'title' => '前置教育', 'table' => 'transfer_credit',),
        array('field' => 'qzsm', 'title' => '前置教育说明', 'table' => 'transfer_credit',),
        array('field' => 'lfbh', 'title' => '留服编号', 'table' => 'infomation',),
        array('field' => 'status', 'title' => '人脸识别状态', 'table' => 'user_identification',),
        array('field' => 'scene', 'title' => '人脸识别场景标识', 'table' => 'user_identification',),
        array('field' => 'bizid', 'title' => '人脸识别认证ID', 'table' => 'user_identification',),
        array('field' => 'server', 'title' => '人脸识别服务接口提供者(如阿里/腾讯)', 'table' => 'user_identification',),
        array('field' => 'zyxf_mn', 'title' => '每年专业学费', 'table' => 'infomation',),
        array('field' => 'zyxf_xz', 'title' => '学制', 'table' => 'infomation',),
        array('field' => 'zyxf_zj', 'title' => '总学费', 'table' => 'infomation',),
        array('field' => 'yxlx', 'title' => '院校类型', 'table' => 'infomation',),
        array('field' => 'yxmc_en', 'title' => '留学院校：英文', 'table' => 'infomation',),
        array('field' => 'yxmc_cn', 'title' => '留学院校：中文', 'table' => 'infomation',),
        array('field' => 'zymc_cn', 'title' => '专业名称：中文', 'table' => 'infomation',),
        array('field' => 'zymc_en', 'title' => '专业名称：英文', 'table' => 'infomation',),

        array('field' => 'xiaoqu', 'title' => '校区', 'table' => 'analysis',),
        array('field' => 'cgsj', 'title' => '出国日期', 'table' => 'infomation',),
        array('field' => 'hgsj', 'title' => '回国日期', 'table' => 'infomation',),
        array('field' => 'yxpm_paper', 'title' => '院校排名', 'table' => 'infomation',),
        array('field' => 'email', 'title' => '邮箱', 'table' => 'infomation',),
        array('field' => 'yjdz', 'title' => '邮寄地址', 'table' => 'infomation',),
        array('field' => 'yjsf', 'title' => '邮寄：省', 'table' => 'infomation',),
        array('field' => 'yjdq', 'title' => '邮寄：市', 'table' => 'infomation',),
        array('field' => 'yjqx', 'title' => '邮寄：区', 'table' => 'infomation',),

        array('field' => 'xuefen', 'title' => '学分', 'table' => 'infomation',),
        array('field' => 'defen', 'title' => 'GPA', 'table' => 'infomation',),
        array('field' => 'pingji', 'title' => '留信评价等级', 'table' => 'infomation',),
        array('field' => 'kemu', 'title' => '专业类型', 'table' => 'infomation',),
        array('field' => 'yxlx', 'title' => '毕业时间', 'table' => 'infomation',),
        array('field' => 'lunwen', 'title' => '论文名称', 'table' => 'infomation',),
        array('field' => 'url', 'title' => '学校官网', 'table' => 'infomation',),
        array('field' => 'chengli', 'title' => '学校成立年份', 'table' => 'infomation',),
        array('field' => 'is_account', 'title' => '大学网站账号密码：是否确认', 'table' => 'infomation',),
        array('field' => 'univ_url', 'title' => '大学网站登录地址', 'table' => 'infomation',),
        array('field' => 'univ_username', 'title' => '大学网站账号', 'table' => 'infomation',),
        array('field' => 'univ_pwd', 'title' => '大学网站密码', 'table' => 'infomation',),
        array('field' => 'highschool', 'title' => '高中学校名称', 'table' => 'info_orther_school',),
        //课程信息
    );

    // 成绩信息
    protected $user_score_info = array(
        array('field' => 'time_info', 'title' => '起止时间', 'table' => 'curriculum_score',),
        array('field' => 'curriculum_name', 'title' => '课程：中文', 'table' => 'curriculum_score',),
        array('field' => 'curriculum_name_en', 'title' => '课程：英文', 'table' => 'curriculum_score',),
        array('field' => 'curriculum_code', 'title' => '课程代码', 'table' => 'curriculum_score',),
        array('field' => 'gpa', 'title' => '绩点', 'table' => 'curriculum_score',),
        array('field' => 'credit', 'title' => '学分', 'table' => 'curriculum_score',),
        array('field' => 'score', 'title' => '成绩', 'table' => 'curriculum_score',),
        array('field' => 'grade', 'title' => '等级', 'table' => 'curriculum_score',),
        array('field' => 'score_lxw', 'title' => '留信成绩', 'table' => 'curriculum_score',),
        array('field' => 'grade_lxw', 'title' => '留信等级', 'table' => 'curriculum_score',),
    );

    // 出入境信息
    protected $user_analysix_info = array(
        array('field' => 'status', 'title' => '出入境状态', 'table' => 'analysix',),
        array('field' => 'time', 'title' => '出入境日期', 'table' => 'analysix',),
        array('field' => 'credentials_type', 'title' => '证件类型', 'table' => 'analysix',),
        array('field' => 'credentials_code', 'title' => '证件号', 'table' => 'analysix',),
        array('field' => 'country', 'title' => '国家', 'table' => 'analysix',),
        array('field' => 'city', 'title' => '城市', 'table' => 'analysix',),
        array('field' => 'entry_exit_ports', 'title' => '出入境口岸', 'table' => 'analysix',),
        array('field' => 'flight_number', 'title' => '航班号', 'table' => 'analysix',),
        array('field' => 'gotime', 'title' => '入境时间', 'table' => 'analysix',),
        array('field' => 'backtime', 'title' => '出境时间', 'table' => 'analysix',),
        array('field' => 'start_country', 'title' => '起点(国家)', 'table' => 'analysix',),
        array('field' => 'termini_country', 'title' => '目的地(国家)', 'table' => 'analysix',),
    );

    //蚂蚁文件上链
    protected $user_file_filed = array(
        //        array('field' => 'cxbh', 'title' => '查询编号', 'table' => 'infomation',),
        array('field' => 'imgss', 'title' => '证件照', 'table' => 'infomation',),
        array('field' => 'sfz_img', 'title' => '身份证', 'table' => 'infomation',),
        array('field' => 'zs_img', 'title' => '学位证书', 'table' => 'infomation',),
        array('field' => 'zs_img_sc', 'title' => '手持学位证书', 'table' => 'infomation',),
        array('field' => 'lx_img', 'title' => '声明文件', 'table' => 'infomation',),
        array('field' => 'hz_img', 'title' => '护照首页和所有签证页', 'table' => 'infomation',),
        array('field' => 'hz_img_sc', 'title' => '手持出入境记录', 'table' => 'infomation',),
        array('field' => 'cj_img_sc', 'title' => '手持成绩单', 'table' => 'infomation',),
        array('field' => 'cj_img', 'title' => '成绩单', 'table' => 'infomation',),
        array('field' => 'fy_img', 'title' => '毕业证成绩单翻译件', 'table' => 'infomation',),
        array('field' => 'us_img', 'title' => '大学成绩单', 'table' => 'infomation',),
        array('field' => 'transcript', 'title' => '高中成绩单', 'table' => 'info_orther_school',),
        array('field' => 'replenish', 'title' => '补充资料', 'table' => 'infomation',),
    );

    // 查询记录和登录记录
    protected $user_query_and_login_record = array(
        array('field' => 'type', 'title' => '搜索查询|成绩查询', 'table' => 'infomation_ck',),
        array('field' => 'mobile_prefix', 'title' => '电话国际区号', 'table' => 'infomation_ck',),
        array('field' => 'mobile', 'title' => '查询手机号', 'table' => 'infomation_ck',),
        array('field' => 'ip', 'title' => '查询ip', 'table' => 'infomation_ck',),
        array('field' => 'created', 'title' => '查询时间', 'table' => 'infomation_ck',),
        array('field' => 'crate_time', 'title' => '登录时间', 'table' => 'user_login_ip',),
        array('field' => 'login_ip', 'title' => '登录ip', 'table' => 'user_login_ip',),
    );


    public function __construct()
    {
        parent::__construct();
        // $this->UserChainApply = D('Lxkus/UserChainApply');
        // $this->AntChainFileHash = D('Lxkus/AntFileHash');
    }

    /**
     * Notes: 存证调用初始化方法
     * @param $user_id
     * @param $infomation_id
     * @throws \Think\Exception
     * @throws \Client\Think\Exception
     */
    private function initialize($user_id, $infomation_id)
    {
        $this->notary = new \Notary(); 
      //  $infomation_info = get_user_infomation($infomation_id);
        $userinfo = get_user_info($user_id);
        $user =  $userinfo ;
        $orderModel = new UserOrder();
        $order = $orderModel->where(['user_id'=>$user_id,'app_id' =>3])->with('orderDetail') ->find();
        $infomation_info =  $order ;
        $order_detail = $order['order_detail'];
        $data=array();
        $data['numbers'] = $user['id_number'];
   
        $data['user_code'] =  $user['search_id'];
        $data['xingming'] = $user['chinaname']==''?$user['username']:$user['chinaname'];  
        $data['email'] =  $user['email'];
        $data['mobile_prefix'] = $px==''?$user['qujian']:$px;       
        $data['password'] =  $pwd==''?'123456a':$pwd; //传密码参数
        $data['mobile'] =  $call==''?$user['mobile']:$call; 
        $data['cxbh'] =  $user['search_id'];
        $data['lxgj'] =   WorldArea::getcountryname($order_detail['edu_nationality']) ;
        $data['sex'] = $user['sex']=='Female'?2:1;
        $data['yxmc_en'] = $order_detail['university'];
        $data['zymc_en'] = $order_detail['major'];
        $data['birthday'] = $user['birth_time']; 
        $data['bysj'] = $order_detail['completion_date'];
        $data['xxsj'] =  $order_detail['start_date'];
        $data['lx_province'] =$order_detail['edu_province']?:''; 
        $data['lx_city'] =$order_detail['edu_city']?:'';  
  $username = $data['xingming'];
         $chain_text = serialize($data);
          $this->user_seatext  =  serialize($data);
          $user_info_file = './ant/'.$user['search_id']. '.txt';
         dump($chain_text);die(); 
        if (file_put_contents($user_info_file, $chain_text ,FILE_APPEND) === false) {
            $msg = "用户({$username} user_id:{$user_id} info_id:{$infomation_id})文本信息文件创建文件失败";
            $info = sprintf("用户信息蚂蚁上链记录\r\nresult: %s\r\nmsg: %s\r\n", 'failed', $msg);
            file_put_contents( 'userant.log',print_r(  $info,'true') ,FILE_APPEND);  
            throw new \Exception($msg);
           
        }

 
        if (empty($userinfo)) {
            $msg = "用户(user_id:{$user_id} info_id:{$user_id})不存在";
            $info = sprintf("用户信息蚂蚁上链记录\r\nresult: %s\r\nmsg: %s\r\n", 'failed', $msg);
            file_put_contents( 'userant.log',print_r(  $info,'true') ,FILE_APPEND);       
            throw new \Exception($msg);
        }

        if (empty($infomation_info)) {
            $msg = "用户(user_id:{$user_id} info_id:{$infomation_id})info信息不存在";
            $info = sprintf("用户信息蚂蚁上链记录\r\nresult: %s\r\nmsg: %s\r\n", 'failed', $msg);
            file_put_contents( 'userant.log',print_r(  $info,'true') ,FILE_APPEND);  
            throw new \Exception($msg);
        }
  
        $userinfo['xingming'] =$data['xingming'] ;

        $infomation_info['xingming'] =$data['xingming'] ;
 
      
        $this->userinfo = $userinfo;
        $this->infomation_info = $infomation_info;
        $this->user_id = $user_id;
        $this->info_id = $infomation_id;
       
        $chain  = Db::name('user_chain')->where(['user_id' => $user_id, 'info_id' => $infomation_id]) ->find();
  
        if (empty($chain)) {
            $msg = "用户({$username} user_id:{$user_id} info_id:{$infomation_id}) 上链申请数据不存在";
            $info = sprintf("用户信息蚂蚁上链记录\r\nresult: %s\r\nmsg: %s\r\n", 'failed', $msg);
            file_put_contents( 'userant.log',print_r(  $info,'true') ,FILE_APPEND);  
            throw new \Think\Exception($msg);
        }
  dump($chain);die();
        $this->chain = $chain;
        $transaction_id = $chain['chain_transaction_id'];
        if (empty($chain['chain_transaction_id'])) {
            $cert_no = $userinfo['id_number'];
            $transaction_id = $this->notary->transCreate($data['xingming'], $cert_no);
            $res = Db::name('user_chain')->where(['id' => $chain['id']])->setField('chain_transaction_id','110');
            if (!$res) {
                $msg = "用户({$username} user_id:{$user_id} info_id:{$infomation_id})上链初始化失败:获取transaction_id失败";
                $info = sprintf("用户信息蚂蚁上链记录\r\nresult: %s\r\nmsg: %s\r\n", 'failed', $msg);
                file_put_contents( 'userant.log',print_r(  $info,'true') ,FILE_APPEND);  
                throw new \Think\Exception($msg);
            }
        }
        $this->transaction_id = $transaction_id;
    }

    /*获取错误提示*/
    public function getError($type = '')
    {
        if ($type == 'list') {
            return $this->error_list;
        } else {
            return $this->error;
        }
    }

    /*设置错误提示*/
    protected function setError($error = '')
    {
        $this->error = $error;
        $this->error_list[] = $error;
        return true;
    }


    /**
     * Notes: 用户首次数据打包上链
     * @return bool
     * @throws \Think\Exception
     * @throws \Client\Think\Exception
     */
    public function user_data_packaging_chain($user_id, $infomation_id)
    {
        // $this->initialize($user_id, $infomation_id);
        // $userInfo = $this->infomation_info;
        // $username = $userInfo['xingming'];
        // $cxbh = $this->userinfo['search_id'];
        // $user_chain_path = "./ant/{$cxbh}/";
  
 $this->notary = new \Notary();   //使用 1  先creat  chain id

        // $cert_no =  'EK5436089';
        //  $this->notary = new \Notary(); 
        //     $transaction_id = $this->notary->transCreate('杜文博', $cert_no);
        //      dump($transaction_id);die();
        //     $res = Db::name('user_chain')->where(['id' => $chain['id']])->setField('chain_transaction_id','110');
        //     if (!$res) {
        //         $msg = "用户({$username} user_id:{$user_id} info_id:{$infomation_id})上链初始化失败:获取transaction_id失败";
        //         $info = sprintf("用户信息蚂蚁上链记录\r\nresult: %s\r\nmsg: %s\r\n", 'failed', $msg);
        //         file_put_contents( 'userant.log',print_r(  $info,'true') ,FILE_APPEND);  
        //         throw new \Think\Exception($msg);
        //     }
        //     die();

 //使用 2   在文本打包 上传 返回数据
      
       $cxbh =  'WSE000000';

        $user_info_file = './ant/'.$cxbh. '.txt';
  $chain_text =  'a:32:{s:7:"numbers";s:9:"EK0000000";s:4:"xwcc";i:2;s:9:"user_code";s:9:"WSE000000";s:8:"xingming";s:6:"测试用户";s:5:"email";s:16:"test@example.com";s:13:"mobile_prefix";s:2:"86";s:6:"mobile";s:11:"13800138000";s:17:"document_group_id";i:1;s:14:"document_group";s:18:"Identity Documents";s:17:"document_group_cn";s:18:"身份证明文件";s:16:"document_type_id";i:1;s:13:"document_type";s:16:"ID Card/Passport";s:16:"document_type_cn";s:16:"身份证/护照";s:7:"country";s:6:"中国";s:8:"province";s:5:"Henan";s:4:"city";s:9:"Zhengzhou";s:4:"cxbh";s:9:"WSE000000";s:4:"lxgj";s:12:"澳大利亚";s:3:"sex";i:1;s:7:"yxmc_cn";s:0:"";s:7:"yxmc_en";s:24:"The University of Sydney";s:7:"zymc_cn";s:0:"";s:7:"zymc_en";s:18:"Business Analytics";s:4:"yxlx";i:5;s:8:"birthday";s:10:"2000-01-01";s:4:"bysj";s:10:"2026-12-14";s:4:"xxsj";s:10:"2024-02-19";s:4:"kemu";s:13:"工商管理 ";s:6:"xuehao";s:9:"540561548";s:11:"lx_province";s:15:"New South Wales";s:7:"lx_city";s:6:"Sydney";s:11:"invite_code";s:15:"OVG38QCP0W3TBOS";}';
 
         //数据打包zip 
        $zipped_file_name = "./ant/" . $cxbh . '.zip';


        $file_hash = hash_file('sha256', $zipped_file_name);

         dump($file_hash);die();
        $chain_data = [
            'chain_id' => '10003',
            'user_id' => $user_id,
            'info_id' => $infomation_id,
            'type' => 15,
            'file_hash' => $file_hash,
            'file_src' => trim($zipped_file_name, '.'),
            'chain_text' => $chain_text,
            'phase' => "杜文博的存证数据集合",
        ];
//        $chain_ant_hash = $this->AntChainFileHash->where($chain_data)->find();
//        $chain_data['chain_text'] = $chain_text;
        $tx_hash = $this->notary->fileNotaryCreate('f196c111-cfd6-4d33-8ee2-c28d48736f3a', $file_hash, $chain_data['phase']);
         dump($tx_hash); 
        if ($tx_hash === false) {
            $msg = "用户({$username} user_id:{$user_id} info_id:{$infomation_id})的存证数据集合上链失败";
            $info = sprintf("用户信息蚂蚁上链记录\r\nresult: %s\r\nmsg: %s\r\n", 'failed', $msg);
            file_put_contents( 'userant.log',print_r(  $info,'true') ,FILE_APPEND);  
            throw new \Exception($msg);
        }



        $chain_data['tx_hash'] = $tx_hash;
        $chain_data['file_up_time'] = time();
        $chain_data['status'] = 1;
        $result = $this->AntChainFileHash->add($chain_data);die();
        if (!$result) {
            $msg = "用户({$username} user_id:{$user_id} info_id:{$infomation_id})的存证数据数据库写入失败";
            $info = sprintf("用户信息蚂蚁上链记录\r\nresult: %s\r\nmsg: %s\r\n", 'failed', $msg);
            file_put_contents( 'userant.log',print_r(  $info,'true') ,FILE_APPEND);  
            throw new \Exception($msg);
        }
        // 更新用户蚂蚁上链状态
        $ant_status = ['chain_ant_status' => 3, 'chain_ant_time' => time()];
        $result = $this->UserChainApply->where('id=' . $this->chain['id'])->save($ant_status);
        if (false === $result) {
            $msg = "用户({$username} user_id:{$user_id} info_id:{$infomation_id})的存证状态更新失败";
            $info = sprintf("用户信息蚂蚁上链记录\r\nresult: %s\r\nmsg: %s\r\n", 'failed', $msg);
            file_put_contents( 'userant.log',print_r(  $info,'true') ,FILE_APPEND);  
            throw new \Exception($msg);
        }
        // 删除目录文件
        if (is_dir($user_chain_path)) {
            $this->deleteDir($user_chain_path);
        }
        $msg = "用户({$username} user_id:{$user_id} info_id:{$infomation_id})存证成功";
        $info = sprintf("用户信息蚂蚁上链记录\r\nresult: %s\r\nmsg: %s\r\n", 'success', $msg);
        file_put_contents( 'userant.log',print_r(  $info,'true') ,FILE_APPEND);  
        return true;
    }

    /**
     * 组装用户上链文本信息
     * @param  array  $userinfo
     * @param  array  $infomation_info
     * @return array|bool
     */
    private function user_text_info($userinfo = array(), $infomation_info = array())
    {

        dump($userinfo);  dump($infomation_info); die();
        
        $analysis_info = M('analysis')->where("infoid=%d", $infomation_info['id'])->find(); //分析报告
       
        if (empty($userinfo)) {
            $this->setError('用户信息为空');
            return false;
        }
        if (empty($infomation_info)) {
            $this->setError('人才库信息为空');
            return false;
        }
 
        $user_identification_info = M('user_identification')->where("user_id=%d", $userinfo['id'])->find(); //人脸识别信息
        $high_school_info = M('info_orther_school')->where("user_id=%d", $userinfo['id'])->find(); //高中信息
        $transfer_credit_info = D('Lxkus/TransferCredit')->get_info($infomation_info['id'], '', $analysis_info, true);

     

       
 
 
      

        $score_info = D('Lxkus/CurriculumScore')->get_list($infomation_info['id']); //用户成绩
        $score_list = array();
     
  
        if (!empty($score_list)) {
            $data['score_list'] = $score_list;
        }
        if (!empty($analysis_list)) {
            $data['analysis_list'] = $analysis_list;
        }
        if (!empty($search_data)) {
            $data['search_querys_list'] = $search_data;
        }
        if (!empty($login_data)) {
            $data['user_login_ip_list'] = $login_data;
        }
        return $data;
    }

    /**
     * Notes: 组装用户上链的文件集合
     * @param         $userinfo
     * @param  array  $infomation_info
     * @return array|false
     */
    private function user_chain_files($userinfo, $infomation_info = array())
    {
        $files = [];
        $temps = [];
        if (is_numeric($userinfo) && $userinfo > 0) {
            $userinfo = get_user_info($userinfo);
            if (empty($userinfo)) {
                $this->setError('该用户不存在');
                return false;
            }
            if (empty($infomation_info) || !is_array($infomation_info) || $infomation_info['uid'] != $userinfo['id']) {
                $infomation_info = M('infomation')->where("uid=%d", $userinfo['id'])->find();
                if (empty($infomation_info)) {
                    $this->setError('该人才库信息不存在');
                    return false;
                }
            }
        }
        $high_school_info = M('info_orther_school')->where("user_id=%d", $userinfo['id'])->find(); //高校信息
        foreach ($this->user_file_filed as $key => $val) {
            if ($val['table'] == 'infomation') {
                if ($infomation_info[$val['field']] == null) {
                    continue;
                }
                array_push($temps, $infomation_info[$val['field']]);
            } elseif ($val['table'] == 'info_orther_school') {
                if ($infomation_info[$val['field']] == null) {
                    continue;
                }
                array_push($temps, $high_school_info[$val['field']]);
            }
        }

        foreach ($temps as $temp) {
            $items = unserialize($temp);
            if (!is_array($items) || count($items) == 0) {
                continue;
            }
            foreach ($items as $item) {
                array_push($files, picKuUrl($item));
            }
        }
        return $files;
    }

    /**
     * Notes: oos 图片下载至本地
     * @param $image_url
     * @return false|string
     */
    private function oss_intranet_download($image_url)
    {
        $userInfo = $this->infomation_info;
        $cxbh = $userInfo['cxbh'];
        $imageInfo = pathinfo($image_url);
        $basename = $imageInfo['basename'];
        $ext = $imageInfo['extension'];
        $parse = parse_url($image_url);
        $path = $parse['path'];
        $url = $this->oosIntranetUrl . $path;
        $filepath = "./evidence/{$cxbh}/" . $basename;
        if (file_exists($filepath)) {
            return $filepath;
        }
        return $this->http_download($url, $filepath, $ext);
    }

//    /**
//     * 用户基础信息上链
//     * @param          $content
//     * @param  string  $phase
//     * @return bool
//     * @throws \Client\Think\Exception
//     */
//    private function text_info_chain($content, $phase = '用户基础信息上链')
//    {
//        $data = array();
//        if (!is_string($content)) {
//            $content = serialize($content);
//        }
//        $is_chain = $this->AntChainFileHash->where(['chain_text' => $content, 'chain_id' => $this->chain['id'], 'status' => 1])->find();
//        // 对于上过链的数据无需重新上链
//        if (!empty($is_chain)) {
//            return true;
//        }
//        $tx_hash = $this->notary->textNotaryCreate($this->transaction_id, $content, $phase);
//        if (!$tx_hash) {
//            $this->setError('请求上链失败');
//            return false;
//        }
//        $data['chain_ant_hash'] = $tx_hash;
//        $data['chain_ant_status'] = 2;
//        // 添加文本上链信息
//        $text_chain = array();
//        $text_chain['chain_id'] = $this->chain['id'];
//        $text_chain['user_id'] = $this->user_id;
//        $text_chain['info_id'] = $this->info_id;
//        $text_chain['type'] = 0;
//        $text_chain['tx_hash'] = $tx_hash;
//        $text_chain['status'] = 1;
//        $text_chain['phase'] = '用户文本信息';
//        $text_chain['file_up_time'] = time();
//        $text_chain['chain_text'] = $content;
//        $text_chain_res = D('ant_file_hash')->add($text_chain);
//        if (!$text_chain_res) {
//            $this->setError('文本上链记录添加失败');
//            return false;
//        }
//        // 更新上链结果
//        $res = $this->UserChainApply->where(['id' => $this->chain['id']])->save($data);
//        if ($res === false) {
//            $this->setError('上链结果更新失败');
//            return false;
//        }
//        return true;
//    }

    /**
     * 用户文件上链
     * @param $files
     * @return bool
     * @throws \Client\Think\Exception
     */
//    public function file_info_chain()
//    {
//        set_time_limit(0);
//        ignore_user_abort(true);
//        $ant_chain_status = $this->UserChainApply->where('id=' . $this->chain['id'])->getField('chain_ant_status');
//        $where = array();
//        $where['user_id'] = $this->user_id;
//        $where['status'] = 0;
//        $files = $this->AntChainFileHash->where($where)->select();
//        // 如果文件信息不存在这跳过文件上链
//        foreach ($files as $file) {
//            $map = [];
//            if ($file['tx_hash']) {
//                continue;
//            }
//            $hash = empty($file['file_hash']) ? hash_file('sha256', $file['file_src']) : $file['file_hash'];
//            // $hash = base64_encode(file_get_contents($file['file_src']));
//            $tx_hash = $this->notary->fileNotaryCreate($this->transaction_id, $hash, $file['phase']);
//            $map['file_hash'] = $hash;
//            $map['tx_hash'] = $tx_hash;
//            $map['file_up_time'] = time();
//            if ($tx_hash) {
//                $map['status'] = 1;
//                $this->AntChainFileHash->where('id=' . $file['id'])->save($map);
//            }
//        }
//        $not_chains = $this->AntChainFileHash->where(['status' => 0, 'chain_id' => $this->chain['id']])->select();
//        if ($ant_chain_status == 2 && count($not_chains) === 0) {
//            $chain_data = ['chain_ant_status' => 3, 'chain_ant_time' => time(), 'apply_status' => 3];
//            //修改上链状态
//            if ($this->chain['chain_contract_status'] == 3) {
//                $chain_data['apply_status'] = 5;
//            }
//            $res = $this->UserChainApply->where('id=' . $this->chain['id'])->save($chain_data);
//            if ($res) {
//                return true;
//            }
//            $this->setError('上链状态修改失败');
//        } else {
//            $this->setError('文本信息未上链');
//        }
//        return false;
//    }


    /**
     * Notes: 下载远程文件
     * @param  string  $url
     * @param  string  $filePath
     * @param  string  $return_ext
     * @return false|string
     */
    private function http_download($url, $filePath, &$return_ext = '')
    {
//        if (!is_string($filePath) || empty($filePath)) {
//            $filePath = './Uploads/PdfToExcel/excel_' . date('Y_m_d_H_i_s') . '_' . session_id() . '.xlsx';
//        }
        if (strpos($filePath, 'http://') === false && strpos($filePath, 'https://') === false) {
            if (strpos($filePath, './') !== 0) {
                if (strpos($filePath, '/') === 0) {
                    $filePath = '.' . $filePath;
                } else {
                    $filePath = './' . $filePath;
                }
            }
        }
        //初始化
        $curl = curl_init();
        //设置抓取的url
        curl_setopt($curl, CURLOPT_URL, $url);
        //打开文件描述符
        $fp = fopen($filePath, 'w');
        curl_setopt($curl, CURLOPT_FILE, $fp);
        //这个选项是意思是跳转，如果你访问的页面跳转到另一个页面，也会模拟访问。
        curl_setopt($curl, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($curl, CURLOPT_TIMEOUT, 50);
        curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, false);

        //执行命令
        curl_exec($curl);
//        echo 'Curl error: ' . curl_error($curl);
        //关闭URL请求
        curl_close($curl);
        //关闭文件描述符
        fclose($fp);
        if (strpos($filePath, '.') === 0) {
            $filePath = substr($filePath, 1);
        }
        $ext = explode('.', $filePath);
        unset($ext[count($ext) - 1]);
        $ext = $ext[count($ext) - 1];
        $return_ext = $ext;
        if (!file_exists('.' . $filePath)) {
            return false;
        }
        return $filePath;
    }

    /**
     * Notes: 文件压缩
     * @param $files 文件集合
     * @param $zipped_file_name 文件名
     * @return false|mixed|string
     */
    public function zip_file($files, $zipped_file_name)
    {
        if (strpos($zipped_file_name, ".zip") === false) {
            $zipped_file_name = $zipped_file_name . ".zip";
        }
        $zip = new \ZipArchive();
        if ($zip->open($zipped_file_name, \ZipArchive::CREATE) === TRUE) {
            foreach ($files as $key => $value) {
                // 添加文件到 ZipArchive
                $name = pathinfo($value, PATHINFO_BASENAME);
                $zip->addFile($value, $name);
            }
            // 关闭 ZipArchive
            $zip->close();
        }
        if (!file_exists($zipped_file_name)) {
            return false;
        }
        return $zipped_file_name;
    }

    /**
     * 读取压缩包目录
     */
    public function getDecompression($path)
    {
        $filesInside = [];
        $zipper = new \ZipArchive();
        $zipStatus = $zipper->open($path);
        if ($zipStatus !== true) {
//            throw new \Exception('Could not open ZIP file. Error code: ' . $zipStatus);
            return $filesInside;
        }
        for ($i = 0; $i < $zipper->count(); $i++) {
            array_push($filesInside, $zipper->getNameIndex($i));
        }
        $zipper->close();
        return $filesInside;
    }
    /**
     * 删除当前目录及其目录下的所有目录和文件
     * @param  string  $path  待删除的目录
     * @note  $path路径结尾不要有斜杠/(例如:正确[$path='./static/image'],错误[$path='./static/image/'])
     */
    private function deleteDir($path)
    {
        if (is_dir($path)) {
            //扫描一个目录内的所有目录和文件并返回数组
            $dirs = scandir($path);
            foreach ($dirs as $dir) {
                //排除目录中的当前目录(.)和上一级目录(..)
                if ($dir != '.' && $dir != '..') {
                    //如果是目录则递归子目录，继续操作
                    $sonDir = $path . '/' . $dir;
                    if (is_dir($sonDir)) {
                        //递归删除
                        deleteDir($sonDir);
                        //目录内的子目录和文件删除后删除空目录
                        @rmdir($sonDir);
                    } else {
                        //如果是文件直接删除
                        @unlink($sonDir);
                    }
                }
            }
            @rmdir($path);
        }
    }

}
