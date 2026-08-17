<?php

/**
 * 从二维数组中取出某个字段的值列表
 * @param array $array
 * @param string $field
 * @param string $error_msg
 * @return array|bool
 */

namespace aliphone;

class appfunction
{
	function array_column_diy($array=array(),$field='',&$error_msg=''){
	    $result=array();
	    foreach($array as $val){
	        if(!isset($val[$field])){
	            $error_msg='该数组中不存在所要获得的字段';
	            return false;
	        }
	        break;
	    }
	    if(function_exists('array_column')){
	        $result=array_column($array,$field);
	    }else{
	        foreach($array as $key=>$val){
	            $result[$key]=$val[$field];
	        }
	    }
	    return $result;
	}
	
	/**
	 * 获取url
	 */
	function geturl($path='',$param=array()){
	    $APP_SUB_DOMAIN_DEPLOY=C('APP_SUB_DOMAIN_DEPLOY');
	    $APP_DOMAIN_SUFFIX=C('APP_DOMAIN_SUFFIX');
	    $APP_SUB_DOMAIN_RULES=C('APP_SUB_DOMAIN_RULES');
	    $url='';
	    if($APP_SUB_DOMAIN_DEPLOY){
	
	    }
	    if(empty($url)){
	        $url=U($path,$param);
	    }
	    return $url;
	}
	/**
	 * Created by PhpStorm.
	 * User: Administrator
	 * Date: 2019/4/2 0002
	 * Time: 09:59
	 */
	//获取唯一的session_key
	function get_unique_session_key(){
	    $session_key=get_random_string(12);
	    $session_key.=time();
	    $session_key.=get_random_string(9);
	    $map=array();
	    $map['session_key']=$session_key;
	    $id=M('user_login')->where($map)->getField('id');
	    if($id&&!empty($id)){
	        $session_key=get_unique_session_key();
	    }
	    return $session_key;
	}
	
	/**
	 * 生成随机字符串
	 * @param int $len
	 * @param bool $has_number
	 * @param bool $has_underline
	 * @return string
	 */
	function get_random_string($len=12,$has_number=true,$has_underline=true){
	    $len=intval($len)>0?intval($len):12;
	    $has_number=$has_number===false?false:true;
	    $has_underline=$has_underline===false?false:true;
	    $string='abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
	    if($has_number){
	        $string.='0123456789';
	    }
	    if($has_underline){
	        $string.='_';
	    }
	    $length=strlen($string)-1;
	    $new_string='';
	    for($i=0;$i<$len;$i++){
	        $index=mt_rand(0,$length);
	        $new_string.=$string[$index];
	    }
	    while(strlen($new_string)<$len){
	        $left=$len-strlen($new_string);
	        $new_string.=get_random_string($left);
	    }
	    return $new_string;
	}
	/**
	 * 判断是否微信浏览器
	 * @return bool
	 */
	function is_weixin() {
	    if (strpos($_SERVER['HTTP_USER_AGENT'], 'MicroMessenger') !== false) {
	        return true;
	    } return false;
	}
	/**
	 * 判断是否手机访问
	 */
	function WSTIsMobile() {
	    $_SERVER['ALL_HTTP'] = isset($_SERVER['ALL_HTTP']) ? $_SERVER['ALL_HTTP'] : '';
	    $mobile_browser = '0';
	    if(preg_match('/(up.browser|up.link|mmp|symbian|smartphone|midp|wap|phone|iphone|ipad|ipod|android|xoom)/i', strtolower($_SERVER['HTTP_USER_AGENT'])))
	        $mobile_browser++;
	    if((isset($_SERVER['HTTP_ACCEPT'])) and (strpos(strtolower($_SERVER['HTTP_ACCEPT']),'application/vnd.wap.xhtml+xml') !== false))
	        $mobile_browser++;
	    if(isset($_SERVER['HTTP_X_WAP_PROFILE']))
	        $mobile_browser++;
	    if(isset($_SERVER['HTTP_PROFILE']))
	        $mobile_browser++;
	    $mobile_ua = strtolower(substr($_SERVER['HTTP_USER_AGENT'],0,4));
	    $mobile_agents = array(
	        'w3c ','acs-','alav','alca','amoi','audi','avan','benq','bird','blac',
	        'blaz','brew','cell','cldc','cmd-','dang','doco','eric','hipt','inno',
	        'ipaq','java','jigs','kddi','keji','leno','lg-c','lg-d','lg-g','lge-',
	        'maui','maxo','midp','mits','mmef','mobi','mot-','moto','mwbp','nec-',
	        'newt','noki','oper','palm','pana','pant','phil','play','port','prox',
	        'qwap','sage','sams','sany','sch-','sec-','send','seri','sgh-','shar',
	        'sie-','siem','smal','smar','sony','sph-','symb','t-mo','teli','tim-',
	        'tosh','tsm-','upg1','upsi','vk-v','voda','wap-','wapa','wapi','wapp',
	        'wapr','webc','winw','winw','xda','xda-'
	    );
	    if(in_array($mobile_ua, $mobile_agents))$mobile_browser++;
	    if(strpos(strtolower($_SERVER['ALL_HTTP']), 'operamini') !== false)$mobile_browser++;
	    if(strpos(strtolower($_SERVER['HTTP_USER_AGENT']), 'windows') !== false)$mobile_browser=0;
	    if(strpos(strtolower($_SERVER['HTTP_USER_AGENT']), 'windows phone') !== false)$mobile_browser++;
	    if(strpos(strtolower($_SERVER['HTTP_USER_AGENT']), 'MicroMessenger') !== false)$mobile_browser++;
	    if($mobile_browser>0){
	        return true;
	    }else{
	        return false;
	    }
	}
	//获取客户端操作系统信息包括win10
	function get_os(){
	    $agent = $_SERVER['HTTP_USER_AGENT'];
	    $os = '';
	    if(strpos($agent, 'Android') !== false){
	        $os = 'Android ';
	        preg_match("/(?<=Android )[\d\.]{1,}/", $agent, $version);
	        $os .= $version[0];
	    }elseif (strpos($agent, 'iPhone') !== false) {
	        $os = 'iPhone ';
	        preg_match("/(?<=CPU iPhone OS )[\d\_]{1,}/", $agent, $version);
	        $os .= str_replace('_', '.', $version[0]);
	    } elseif (strpos($agent, 'iPad') !== false) {
	        $os = 'iPad ';
	        preg_match("/(?<=CPU OS )[\d\_]{1,}/", $agent, $version);
	        $os .= str_replace('_', '.', $version[0]);
	    }else if (preg_match('/win/i', $agent) && strpos($agent, '95')){
	        $os = 'Windows 95';
	    }else if (preg_match('/win 9x/i', $agent) && strpos($agent, '4.90')){
	        $os = 'Windows ME';
	    }else if (preg_match('/win/i', $agent) && preg_match('/98/i', $agent)){
	        $os = 'Windows 98';
	    }else if (preg_match('/win/i', $agent) && preg_match('/nt 6.0/i', $agent)) {
	        $os = 'Windows Vista';
	    } else if (preg_match('/win/i', $agent) && preg_match('/nt 6.1/i', $agent)){
	        $os = 'Windows 7';
	    } else if (preg_match('/win/i', $agent) && preg_match('/nt 6.2/i', $agent)){
	        $os = 'Windows 8';
	    }else if(preg_match('/win/i', $agent) && preg_match('/nt 10.0/i', $agent)){
	        $os = 'Windows 10';#添加win10判断
	    }else if (preg_match('/win/i', $agent) && preg_match('/nt 5.1/i', $agent)){
	        $os = 'Windows XP';
	    }else if (preg_match('/win/i', $agent) && preg_match('/nt 5/i', $agent)){
	        $os = 'Windows 2000';
	    }else if (preg_match('/win/i', $agent) && preg_match('/nt/i', $agent)){
	        $os = 'Windows NT';
	    } else if (preg_match('/win/i', $agent) && preg_match('/32/i', $agent)){
	        $os = 'Windows 32';
	    }else if (preg_match('/linux/i', $agent)) {
	        $os = 'Linux';
	    }else if (preg_match('/unix/i', $agent)){
	        $os = 'Unix';
	    } else if (preg_match('/sun/i', $agent) && preg_match('/os/i', $agent)){
	        $os = 'SunOS';
	    } else if (preg_match('/ibm/i', $agent) && preg_match('/os/i', $agent)) {
	        $os = 'IBM OS/2';
	    }else if (preg_match('/Mac/i', $agent) && preg_match('/PC/i', $agent)){
	        $os = 'Macintosh';
	    } else if (preg_match('/PowerPC/i', $agent)) {
	        $os = 'PowerPC';
	    }else if (preg_match('/AIX/i', $agent)) {
	        $os = 'AIX';
	    }else if (preg_match('/HPUX/i', $agent)) {
	        $os = 'HPUX';
	    } else if (preg_match('/NetBSD/i', $agent)) {
	        $os = 'NetBSD';
	    } else if (preg_match('/BSD/i', $agent)) {
	        $os = 'BSD';
	    } else if (preg_match('/OSF1/i', $agent)){
	        $os = 'OSF1';
	    } else if (preg_match('/IRIX/i', $agent)) {
	        $os = 'IRIX';
	    } else if (preg_match('/FreeBSD/i', $agent)) {
	        $os = 'FreeBSD';
	    } else if (preg_match('/teleport/i', $agent)){
	        $os = 'teleport';
	    } else if (preg_match('/flashget/i', $agent)){
	        $os = 'flashget';
	    } else if (preg_match('/webzip/i', $agent)) {
	        $os = 'webzip';
	    }else if (preg_match('/offline/i', $agent)){
	        $os = 'offline';
	    }  else{
	        $os = '未知操作系统';
	    }
	    return trim($os);
	}
	//判断浏览器
	function get_broswer(){
	    $sys = $_SERVER['HTTP_USER_AGENT'];  //获取用户代理字符串
	    if (stripos($sys, "Firefox/") > 0) {
	        preg_match("/Firefox/([^;)]+)+/i", $sys, $b);
	        $exp[0] = "Firefox";
	        $exp[1] = $b[1];  //获取火狐浏览器的版本号
	    } elseif (stripos($sys, "Maxthon") > 0) {
	        preg_match("/Maxthon/([d.]+)/", $sys, $aoyou);
	        $exp[0] = "傲游";
	        $exp[1] = $aoyou[1];
	    } elseif (stripos($sys, "Baiduspider") > 0) {
	        $exp[0] = "百度";
	        $exp[1] = '蜘蛛';
	    }elseif (stripos($sys, "YisouSpider") > 0) {
	        $exp[0] = "一搜";
	        $exp[1] = '蜘蛛';
	    }elseif (stripos($sys, "Googlebot") > 0) {
	        $exp[0] = "谷歌";
	        $exp[1] = '蜘蛛';
	    }elseif (stripos($sys, "Android 4.3") > 0) {
	        $exp[0] = "安卓";
	        $exp[1] = '4.3';
	    }elseif (stripos($sys, "MSIE") > 0) {
	        preg_match("/MSIEs+([^;)]+)+/i", $sys, $ie);
	        $exp[0] = "IE";
	        $exp[1] = $ie[1];  //获取IE的版本号
	    } elseif (stripos($sys, "OPR") > 0) {
	        preg_match("/OPR/([d.]+)/", $sys, $opera);
	        $exp[0] = "Opera";
	        $exp[1] = $opera[1];
	    } elseif(stripos($sys, "Edge") > 0) {
	        //win10 Edge浏览器 添加了chrome内核标记 在判断Chrome之前匹配
	        preg_match("/Edge/([d.]+)/", $sys, $Edge);
	        $exp[0] = "Edge";
	        $exp[1] = $Edge[1];
	    } elseif (stripos($sys, "Chrome") > 0) {
	        preg_match("/Chrome/([d.]+)/", $sys, $google);
	        $exp[0] = "Chrome";
	        $exp[1] = $google[1];  //获取google chrome的版本号
	    } elseif(stripos($sys,'rv:')>0 && stripos($sys,'Gecko')>0){
	        preg_match("/rv:([d.]+)/", $sys, $IE);
	        $exp[0] = "IE";
	        $exp[1] = $IE[1];
	    }else if(stripos($sys,'AhrefsBot')>0){
	        $exp[0] = "AhrefsBot";
	        $exp[1] = '蜘蛛';
	    }else if(stripos($sys,'Safari')>0){
	        preg_match("/([d.]+)/", $sys, $safari);
	        $exp[0] = "Safari";
	        $exp[1] = $safari[1];
	    }else if(stripos($sys,'bingbot')>0){
	        $exp[0] = "必应";
	        $exp[1] = '蜘蛛';
	    }else if(stripos($sys,'WinHttp')>0){
	        $exp[0] = "windows";
	        $exp[1] = 'WinHttp 请求接口工具';
	    }else if(stripos($sys,'iPhone OS 10')>0){
	        $exp[0] = "iPhone";
	        $exp[1] = 'OS 10';
	    }else if(stripos($sys,'Sogou')>0){
	        $exp[0] = "搜狗";
	        $exp[1] = '蜘蛛';
	    }else if(stripos($sys,'HUAWEIM')>0){
	        $exp[0] = "华为";
	        $exp[1] = '手机端';
	    }else if(stripos($sys,'Dalvik')>0){
	        $exp[0] = "安卓";
	        $exp[1] = 'Dalvik虚拟机';
	    }else if(stripos($sys,'Mac OS X 10')>0){
	        $exp[0] = "MAC";
	        $exp[1] = 'OS X10';
	    }else if(stripos($sys,'Opera/9.8')>0){
	        $exp[0] = "Opera";
	        $exp[1] = '9.8';
	    }else if(stripos($sys,'JikeSpider')>0){
	        $exp[0] = "即刻";
	        $exp[1] = '蜘蛛';
	    }else if(stripos($sys,'Baiduspider')>0){
	        $exp[0] = "百度";
	        $exp[1] = '蜘蛛';
	    }else {
	        $exp[0] = $sys;
	        $exp[1] = "";
	    }
	    return trim($exp[0].' '.$exp[1]);
	}
	
	/**
	 * 获得访客真实ip
	 * @return mixed
	 */
	function get_ip(){
	    if (!empty($_SERVER["HTTP_CLIENT_IP"])) {
	        $ip = $_SERVER["HTTP_CLIENT_IP"];
	    }
	    if (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) { // 获取代理ip
	        $ips = explode(',', $_SERVER['HTTP_X_FORWARDED_FOR']);
	    }
	    if ($ip) {
	        $ips = array_unshift($ips, $ip);
	    }
	    $count = count($ips);
	    for ($i = 0; $i < $count; $i++) {
	        if (!preg_match("/^(10|172\.16|192\.168)\./i", $ips[$i])) { // 排除局域网ip
	            $ip = $ips[$i];
	            break;
	        }
	    }
	    $tip = empty($_SERVER['REMOTE_ADDR']) ? $ip : $_SERVER['REMOTE_ADDR'];
	    if(empty($tip)||$tip == "127.0.0.1"){
	        $tip=get_client_ip();
	    }
	    return $tip;
	}
	function is_https()
	{
	    if ( ! empty($_SERVER['HTTPS']) && strtolower($_SERVER['HTTPS']) !== 'off')
	    {
	        return true;
	    }
	    elseif (isset($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] === 'https')
	    {
	        return true;
	    }
	    elseif ( ! empty($_SERVER['HTTP_FRONT_END_HTTPS']) && strtolower($_SERVER['HTTP_FRONT_END_HTTPS']) !== 'off')
	    {
	        return true;
	    }
	
	    return false;
	}
	function get_http(){
	    if(is_https()){
	        return 'https://';
	    }else{
	        return 'http://';
	    }
	}
	/**
	 * 获取系统配置信息
	 * @param string $config_name
	 */
	function get_system_config($config_name=''){
	    $result=array();
	    if($config_name=='has_cxbh'){
	        $result=2;
	    }
	    return $result;
	}
	/**
	 * 创建支付订单（加入人才库）订单编号
	 * order表order_no
	 * @param string $pay
	 * @return mixed
	 */
	function get_order_no($pay=''){
	    if(empty($pay)){
	        $pay = new \Think\Pay('alipay', C('payment.alipay'));
	    }
	    $order_no = $pay->createOrderNo();
	    $orderinfo=M('order')->where("order_no='".$order_no."'")->field('oid')->find();
	    $payinfo=M('pay')->where("out_trade_no='".$order_no."'")->field('out_trade_no')->find();
	    if(!empty($orderinfo)||!empty($payinfo)){
	        return get_order_no($pay);
	    }
	    return $order_no;
	}
	
	/**
	 * 根据付款项目名称列表计算付款总数量
	 */
	function get_total_money($info=array(),$project=array(),$is_dump=false){
	    $is_dump=$is_dump===true?true:false;
	    if(empty($project)){
	        $project=M('project')->where("is_show=1")->field('title,money,type')->select();
	    }
	    if(!(!empty($info)&&is_array($info)&&isset($info['desc'])&&isset($info['haspay']))){
	        return 0;
	    }
	    $info['desc']=preg_replace('/(、){1,}/','、',$info['desc']);
	    $info['desc']=explode('、',$info['desc']);
	    $info['haspay']=floatval($info['haspay']);
	    $money=0;
	    $price=0;
	    foreach($project as $key=>$val){
	        if($val['type']!=1&&($key==0||in_array($val['title'],$info['desc']))){
	            $money+=$val['money'];
	        }elseif($val['type']==1){
	            $price=$val['money'];
	        }
	    }
	    if($is_dump){
	        dump('--------------');
	        dump('haspay：'.$info['haspay']);
	        dump('money：'.$money);
	        dump('price：'.$price);
	        dump('--------------');
	    }
	    if($price<=0){
	        return 0;
	    }
	//    $num=floor(($info['haspay']-$money)/$price);
	    $num=($info['haspay']-$money)/$price;
	    return $num;
	}
	/**
	 * 发送手机短信（国内短信）
	 * 短信验证码/提示信息
	 * @param  [type] $mobile [用户的手机号]
	 * @return [type]         [description]
	 */
	function send_domestic_phone_code($config=array(),&$mobile_code='',&$msg='',$is_example=false) {
	    $allowed_type=\think\Confit::get('phone_code.allowed_type'); //允许的短信类型
	    $config['type']=in_array($config['type'],$allowed_type['list'])?$config['type']:1;
	
	    $mobile_code=is_string($mobile_code)&&!empty($mobile_code)?$mobile_code:'';
	    $TemplateParam=array();
	    if(in_array($config['type'],$allowed_type['code'])){
	        //短信验证
	        if(empty($mobile_code)||!is_string($mobile_code)){
	            //生成验证码
	            $mobile_code = \Org\Util\String::randString(6, 1);
	        }
	
	        $TemplateParam['code']=$mobile_code;
	        $TemplateParam['type']=$allowed_type['info'][$config['type']];
	        $TemplateParam['endtime']='本日';
	
	//    $contens='亲爱的用户，您的验证码是'.$mobile_code.',有疑问请致电4006610606';
	//    $contens = '您的验证码是：' . $mobile_code . '。请不要把验证码泄露给其他人。';
	
	//        $contens = '您的验证码是：' . $mobile_code . '。请不要把验证码泄露给其他人。';
	//        $type=$allowed_type['info'][$config['type']];
	        $contens = "您的验证码是：".$TemplateParam['code']." 。该验证码仅用于".$TemplateParam['type']."，".$TemplateParam['endtime']."内有效，请不要把验证码泄露给其他人。";;
	    }elseif(in_array($config['type'],$allowed_type['single'])){
	        $config['info_id']=intval($config['info_id']);
	        if(empty($config['info_id'])||$config['info_id']<=0){
	            $msg='该人才库信息不存在';
	            return false;
	        }
	        $map=array();
	        $map['id']=$config['info_id'];
	        
	        //查询-----------------
	        $infomation = M('infomation')->field('id,uid,xingming,country,cxbh,yxmc_cn,yxmc_en,zymc_cn,zymc_en')->where($map)->find();
	        if(empty($infomation)){
	            $msg='该人才库信息不存在';
	            return false;
	        }
	        
	        //查询------------------------
	        $config['phone'] = M('user')->where('id='.$infomation['uid'])->getField('mobile');
	
	        $bianhao = $infomation['xingming'].'-'.$infomation['country'].'-'.$infomation['cxbh'];
	//        $guojia = $infomation['country'].'-'.$infomation['yxmc_cn'];
	        $TemplateParam['name']=$infomation['xingming'];
	        $sex = $infomation['xingbie']==0?'先生':'女士';
	        $TemplateParam['name'].= ' '.$sex;
	
	        if($config['type']==7){
	            //审核成功
	            $TemplateParam['discipline']=$infomation['yxmc_cn'].'（'.$infomation['yxmc_en'].'）'.$infomation['zymc_cn'].'专业';
	            $TemplateParam['number']=$bianhao;
	
	            // 	恭喜您，【变量】，您的【变量】留学生专业人才库已审核通过，查询编号：【变量】，网址：https://ku.cscss.com.cn/search
	//            $contens = '尊敬的留学生'.$infomation['xingming'].'&nbsp;'.'您好，您的留学生专业人才入库信息已经审核通过。入库审核编号为：'.$bianhao.'，请登录http://ku.cscss.com.cn/search查询验证信息。';
	
	            $config['has_cxbh']=in_array($config['has_cxbh'],array(1,2))?$config['has_cxbh']:2;
	            if($config['has_cxbh']==1){/*有查询编号*/
	                $contens="恭喜您，".$TemplateParam['name']."，您的".$TemplateParam['discipline']."留学生专业人才库已审核通过，查询编号：".$TemplateParam['number']."，网址：https://ku.cscss.com.cn/search";
	            }else{/*无查询编号*/
	                $contens="恭喜您，".$TemplateParam['name']."，您的".$TemplateParam['discipline']."留学生专业人才库已审核通过，网址：https://ku.cscss.com.cn/search";
	            }
	        }elseif($config['type']==8){
	            //审核失败
	            $TemplateParam['discipline']=$infomation['yxmc_cn'].'（'.$infomation['yxmc_en'].'）'.$infomation['zymc_cn'];
	
	            //尊敬的【变量】，您的【变量】留学生专业人才库审核暂未通过，详细原因请登录留信网专业人才库（https://ku.cscss.com.cn）站内短信查看具体原因。
	//            $contens = '尊敬的留学生'.$infomation['xingming'].'&nbsp;'.$sex.'您好，您申请的全国留学生信息服务网留学生专业人才库，因资料不齐，真实有待确认，请提供您所在（'.$guojia.'）网站学生用户名密码，发送至邮箱anna@cscss.com.cn；便于我们进一步调研核实。';
	            $contens="尊敬的".$TemplateParam['name']."，您的".$TemplateParam['discipline']."留学生专业人才库审核暂未通过，详细原因请登录留信网专业人才库（https://ku.cscss.com.cn）站内短信查看具体原因。";
	        }elseif($config['type']==9){
	            //缴费提醒
	            //	尊敬的留学生【变量】，您申请的留学生专业人才库就差一步付款了，审核通过以后您将获得留学生专业人才入库证书以及精美文凭外壳一份，还有机会获得留学生岗位就业推荐名额，彻底解决工作问题。 http://ku.cscss.com.cn
	            $contens = '尊敬的留学生'.$TemplateParam['name'].'，您好，您申请的留学生专业人才库就差一步付款了，审核通过以后您将获得留学生专业人才入库证书以及精美文凭外壳一份，还有机会获得留学生岗位就业推荐名额，彻底解决工作问题。 http://ku.cscss.com.cn';
	        }elseif($config['type']==10){
	            //注册支付完成，进入审核状态
	            $contens = '留学生'.$TemplateParam['name'].'您好。您申请的留学生专业人库已进入审核状态，预计8个工作日完成；';
	        }
	    }else{
	        $msg='不支持的短信类型';
	        return false;
	    }
	    if(empty($config['phone'])||!is_string($config['phone'])){
	        $msg='手机号码不能为空';
	        return false;
	    }
	    $is_example=$is_example===true?true:false;
	    if($is_example){
	        $gets['SubmitResult']['smsid']='ceshi_in';
	    }else{
	        $target = "http://106.ihuyi.cn/webservice/sms.php?method=Submit";
	        $user = "C24797798";
	        $pwd = "872f400cb7eb7c496062a462c3096219";
	        $post_data = "account=" . $user . "&password=" . $pwd . "&mobile=" . $config['phone'] . "&content=" . rawurlencode($contens);
	        //密码可以使用明文密码或使用32位MD5加密
	        $gets = xml_to_array(Post($post_data, $target));
	        if ($gets['SubmitResult']['code'] != 2) {
	            $msg='互亿无线：'.$gets['SubmitResult']['msg'];
	            $msg=$msg?$msg:'发送失败';
	            return false;
	        }
	    }
	    if(in_array($config['type'],$allowed_type['code'])){
	        $_SESSION['mobile_code'] = $mobile_code;
	    }
	    $data=array();
	    $data['mobile_prefix']=$config['mobile_prefix'];
	    $data['phone']=$config['phone'];
	    $data['type']=$config['type'];
	    $data['code']=$mobile_code;
	    $data['info']=$contens;
	    $data['bizid']=$gets['SubmitResult']['smsid'];/*消息id*/
	    $data['status']=1;
	    $data['ctime']=time();
	    $phone_code_id=M('phone_code')->add($data);
	    if(!$phone_code_id){
	        error_log(json_encode(array('data'=>$data,'status'=>$phone_code_id,'error_msg'=>M()->getError())),3,'add_phone_code_error.log');
	    }
	    if(in_array($config['type'],$allowed_type['code'])){
	        $msg='验证码发送成功，请注意查收您手机';
	    }else{
	        $msg='发送成功';
	    }
	    return true;
	}
	
	/**
	 * 发送手机短信（港澳台及国际短信）
	 * 短信验证码/提示信息
	 */
	function send_international_phone_code($config=array(),&$mobile_code='',&$info=array(),$is_example=false){
	    $allowed_type=C('phone_code.allowed_type'); //允许的短信类型
	    $config['type']=in_array($config['type'],$allowed_type['list'])?$config['type']:1;
	
	    $aliyun_config=C('phone_code.aliyun');
	
	    $mobile_code=is_string($mobile_code)&&!empty($mobile_code)?$mobile_code:'';
	    $TemplateParam=array();
	    $TemplateCode='';
	    if(in_array($config['type'],$allowed_type['code'])){
	        //短信验证
	        if(empty($mobile_code)||!is_string($mobile_code)){
	            //生成验证码
	            $mobile_code = \Org\Util\String::randString(6, 1);
	        }
	
	        //您的验证码是：${code} 。该验证码仅用于${type}，${endtime}内有效，请不要把验证码泄露给其他人。
	
	        $TemplateParam['code']=$mobile_code;
	        $TemplateParam['type']=$allowed_type['info'][$config['type']];
	        $TemplateParam['endtime']='本日';
	        $TemplateCode=$aliyun_config['TemplateCode_international']['code'];
	        $msg_info="您的验证码是：".$TemplateParam['code']." 。该验证码仅用于".$TemplateParam['type']."，".$TemplateParam['endtime']."内有效，请不要把验证码泄露给其他人。";
	    }elseif(in_array($config['type'],$allowed_type['single'])){
	        $config['info_id']=intval($config['info_id']);
	        if(empty($config['info_id'])||$config['info_id']<=0){
	            $info['Message']='该人才库信息不存在';
	            return false;
	        }
	        $map=array();
	        $map['id']=$config['info_id'];
	        $infomation = M('infomation')->field('id,uid,xingming,country,cxbh,yxmc_cn,yxmc_en,zymc_cn,zymc_en')->where($map)->find();
	        if(empty($infomation)){
	            $info['Message']='该人才库信息不存在';
	            return false;
	        }
	        $userinfo = M('user')->where('id='.$infomation['uid'])->field('mobile_prefix,mobile')->find();
	        $config['mobile_prefix'] = $userinfo['mobile_prefix'];
	        $config['phone'] = $userinfo['mobile'];
	
	        $bianhao = $infomation['xingming'].'-'.$infomation['country'].'-'.$infomation['cxbh'];
	        $guojia = $infomation['country'].'-'.$infomation['yxmc_cn'];
	
	        $TemplateParam['name']=$infomation['xingming'];
	        $sex = $infomation['xingbie']==0?'先生':'女士';
	        $TemplateParam['name'].= ' '.$sex;
	
	        if($config['type']==7){
	            //审核成功
	//            $contens = '尊敬的留学生'.$infomation['xingming'].'&nbsp;'.$infomation['xingbie']==0 ? '先生':'女士';'您好，您的留学生专业人才入库信息已经审核通过。入库审核编号为：'.$bianhao.'，请登录http://ku.cscss.com.cn/search查询验证信息。';
	            //尊敬的留学生${name}您好，您的留学生专业人才入库信息已经审核通过。入库审核编号为：${number}，请登录http://ku.cscss.com.cn/${param}查询验证信息。
	            //恭喜您，${name}，您的${discipline}留学生专业人才库已审核通过，查询编号：${number}，网址：https://ku.cscss.com.cn/${param}（微信搜索公众账号：留信网）
	
	            $TemplateParam['discipline']=$infomation['yxmc_cn'].'（'.$infomation['yxmc_en'].'）'.$infomation['zymc_cn'].'专业';
	            $TemplateParam['param']='search';
	
	            /*$msg_info="尊敬的留学生".$TemplateParam['name']."您好，您的留学生专业人才入库信息已经审核通过。入库审核编号为：".$TemplateParam['number']."，请登录http://ku.cscss.com.cn/".$TemplateParam['param']."查询验证信息。";*/
	            $config['has_cxbh']=in_array($config['has_cxbh'],array(1,2))?$config['has_cxbh']:2;
	            if($config['has_cxbh']==1){/*有查询编号*/
	                $TemplateCode=$aliyun_config['TemplateCode_international']['ku_success'];
	                $TemplateParam['number']=$bianhao;
	                $msg_info="恭喜您，".$TemplateParam['name']."，您的".$TemplateParam['discipline']."留学生专业人才库已审核通过，查询编号：".$TemplateParam['number']."，网址：https://ku.cscss.com.cn/".$TemplateParam['param']."（微信搜索公众账号：留信网）";
	            }else{/*无查询编号*/
	                $TemplateCode=$aliyun_config['TemplateCode_international']['ku_success2'];
	                $msg_info="恭喜您，".$TemplateParam['name']."，您的".$TemplateParam['discipline']."留学生专业人才库已审核通过，网址：https://ku.cscss.com.cn/".$TemplateParam['param']."（微信搜索公众账号：留信网）";
	            }
	        }elseif($config['type']==8){
	            //审核失败
	//            $contens = '尊敬的留学生'.$infomation['xingming'].'&nbsp;'.$infomation['xingbie']==0 ? '先生':'女士';'您好，您申请的全国留学生信息服务网留学生专业人才库，因资料不齐，真实有待确认，请提供您所在（'.$guojia.'）网站学生用户名密码，发送至邮箱anna@cscss.com.cn；便于我们进一步调研核实。';
	            //尊敬的留学生${name}您好，您申请的全国留学生信息服务网留学生专业人才库，因资料不齐，真实有待确认，请提供您所在（${country}）网站学生用户名密码，发送至邮箱${mail}；便于我们进一步调研核实。
	            //尊敬的${name}，您的${discipline}留学生专业人才库审核暂未通过，详细原因请登录留信网专业人才库（https://ku.cscss.com.cn）站内短信查看具体原因。
	
	            $TemplateParam['discipline']=$infomation['yxmc_cn'].'（'.$infomation['yxmc_en'].'）'.$infomation['zymc_cn'].'专业';
	//            $TemplateParam['country']=$guojia;
	//            $TemplateParam['mail']='anna@cscss.com.cn';
	            $TemplateParam['name']="尊敬的".$TemplateParam['name'];
	            $TemplateCode=$aliyun_config['TemplateCode_international']['ku_fail'];
	            /*$msg_info="尊敬的留学生".$TemplateParam['name']."您好，您申请的全国留学生信息服务网留学生专业人才库，因资料不齐，真实有待确认，请提供您所在（".$TemplateParam['country']."）网站学生用户名密码，发送至邮箱".$TemplateParam['mail']."；便于我们进一步调研核实。";*/
	            $msg_info=$TemplateParam['name']."，您的".$TemplateParam['discipline']."留学生专业人才库审核暂未通过，详细原因请登录留信网专业人才库（https://ku.cscss.com.cn）站内短信查看具体原因。";
	        }elseif($config['type']==9){
	            //缴费提醒
	//            $contens = '尊敬的留学生'.$infomation['xingming'].'&nbsp;'.$infomation['xingbie']==0 ? '先生':'女士';'您好，尊敬的留学生名字性别，您申请的留学生专业人才库就差一步付款了，审核通过以后您将获得留学生专业人才入库证书以及精美文凭外壳一份，还有机会获得留学生岗位就业内部推荐名额，彻底解决工作问题。  http://ku.cscss.com.cn';
	            //尊敬的留学生${name}您好，您申请的留学生专业人才库就差一步付款了，审核通过以后您将获得留学生专业人才入库证书以及精美文凭外壳一份，还有机会获得留学生岗位就业内部推荐名额，彻底解决工作问题。  http://ku.cscss.com.cn
	
	            $TemplateCode=$aliyun_config['TemplateCode_international']['ku_need_pay'];
	            $msg_info="尊敬的留学生".$TemplateParam['name']."您好，您申请的留学生专业人才库就差一步付款了，审核通过以后您将获得留学生专业人才入库证书以及精美文凭外壳一份，还有机会获得留学生岗位就业内部推荐名额，彻底解决工作问题。  http://ku.cscss.com.cn";
	        }elseif($config['type']==10){
	            //注册支付完成，进入审核状态
	            $TemplateCode=$aliyun_config['TemplateCode_international']['ku_has_pay'];
	            $msg_info = '尊敬的留学生'.$TemplateParam['name'].'您好。您申请的留学生专业人库已进入审核状态，预计8个工作日完成；';
	        }
	    }else{
	        $info['Message']='不支持的短信类型';
	        return false;
	    }
	
	    if(!is_array($config)||empty($config['mobile_prefix'])||!is_string($config['mobile_prefix'])){
	        $info['Message']='国际电话区号不能为空';
	        return false;
	    }
	    if(empty($config['phone'])||!is_string($config['phone'])){
	        $info['Message']='手机号码不能为空';
	        return false;
	    }
	    if(empty($TemplateParam)||empty($TemplateCode)){
	        $info['Message']='参数错误';
	        return false;
	    }
	
	//    vendor("AlibabaCloud.Client.AlibabaCloud");
	//    vendor("AlibabaCloud.Client.Exception.ClientException");
	//    vendor("AlibabaCloud.Client.Exception.ServerException");
	////    include_once VENDOR_PATH.'AlibabaCloud/AlibabaCloud.php';
	////    $AlibabaCloud= new \AlibabaCloud\Client\AlibabaCloud();
	//    \AlibabaCloud::accessKeyClient($aliyun_config['AccessKeyID'], $aliyun_config['AccessKeySecret'])
	//        ->regionId('cn-hangzhou') // replace regionId as you need
	//        ->asGlobalClient();
	//    try {
	//        $result = \AlibabaCloud::rpcRequest()
	//            ->product('Dysmsapi')
	//            // ->scheme('https') // https | http
	//            ->version('2017-05-25')
	//            ->action('SendSms')
	//            ->method('POST')
	//            ->options([
	//                'query' => [
	//                    'PhoneNumbers'=>  $config['mobile_prefix'] . $config['phone'] ,             //手机号
	//                    'SignName'=>$aliyun_config['SignName'],                                     //短信签名
	//                    'TemplateCode'=>$TemplateCode,                                              //短信模板id
	//                    'TemplateParam'=>$TemplateParam,                                            //模板变量
	//                ],
	//            ])
	//            ->request();
	//        $info=$result->toArray();
	//    } catch (\AlibabaCloud\Client\Exception\ClientException $e) {
	//        $info['Message']='发送失败：'.$e->getErrorMessage() . PHP_EOL;
	//        return false;
	//    } catch (\AlibabaCloud\Client\Exception\ServerException $e) {
	//        $info['Message']='发送失败：'.$e->getErrorMessage() . PHP_EOL;
	//        return false;
	//    }
	    $is_example=$is_example===true?true:false;
	    if($is_example){
	        $info['BizId']='ceshi_out';
	    }else{
	        require_once(APP_PATH . '/Common/Common/aliyun_dysms.php'); //加载 阿里云通信短信服务 旧版api调用
	        $info=AliyunSmsDemo::sendSms($config['mobile_prefix'] . $config['phone'],$TemplateParam,$TemplateCode,$aliyun_config);
	        $info=json_decode(json_encode($info),true);
	        if($info['Code']!='OK'){
	            $info['Message']='阿里云通信|短信服务：'.$info['Message'];
	            return false;
	        }
	    }
	
	    if(in_array($config['type'],$allowed_type['code'])){
	        $_SESSION['mobile_code'] = $mobile_code;
	    }
	    $data=array();
	    $data['mobile_prefix']=$config['mobile_prefix'];
	    $data['phone']=$config['phone'];
	    $data['type']=$config['type'];
	    $data['code']=$mobile_code;
	    $data['info']=$msg_info;
	    $data['bizid']=$info['BizId'];
	    $data['status']=1;
	    $data['ctime']=time();
	    $phone_code_id=M('phone_code')->add($data);
	    if(!$phone_code_id){
	        error_log(json_encode(array('data'=>$data,'status'=>$phone_code_id,'error_msg'=>M()->getError())),3,'add_phone_code_error.log');
	    }
	    $info['Message']='验证码发送成功，请注意查收您手机';
	    return true;
	}
	
	//获取国际电话区号
	function get_country_phone_area_code($keyword='',&$error=''){
	    if(is_numeric($keyword)&&$keyword<0){
	        $id=abs(intval($keyword));
	        $id=$id>0?$id:0;
	    }else{
	        $id=-1;
	    }
	    $result='';
	    if($id>0){
	        $result=F('phone/lxw_get_country_phone_area_code_'.$id);
	        if(empty($result)){
	            $result=M('country_mobile_prefix')->where("id=".$id)->find();
	            if(empty($result)){
	                $error='不支持该国家';
	                return false;
	            }
	            F('phone/lxw_get_country_phone_area_code_'.$id,$result);
	        }
	        if(!empty($error)){
	            if(!isset($result[$error])){
	                $error=$error.'字段不存在';
	                return false;
	            }
	            $result=$result[$error];
	        }else{
	            $result=$result['mobile_prefix'];
	        }
	    }elseif($id==0){
	        $result='86';
	    }else{
	        $map=array();
	        $map['country']=$keyword;
	        $map['mobile_prefix']=$keyword;
	        $map['_logic']='or';
	        $result=M('country_mobile_prefix')->where($map)->find();
	        if(empty($result)){
	            $error='不支持该国家';
	            return false;
	        }
	//        if($result['mobile_prefix']==$keyword){
	//            $result=$result['country'];
	//        }else{
	//            $result=$result['mobile_prefix'];
	//        }
	        $result=$result['mobile_prefix'];
	    }
	    return $result;
	}
	
	/**
	 * 发送短信验证码
	 * 本方法不应当放在事务中（因为本方法已经开启了事务）
	 */
	function send_code($param=array(),&$error_msg='',$is_example=false){
	    $phone=$param['phone'];
	    $type=$param['type'];
	    if((!isset($param['mobile_prefix'])||empty($param['mobile_prefix']))&&(!isset($param['country'])||empty($param['country']))){
	        $param['mobile_prefix']='86';
	    }
	    if(isset($param['mobile_prefix'])){
	        if(!isset($param['mobile_prefix'])||empty($param['mobile_prefix'])){
	            $error_msg='请选择合适的国际电话区号';
	            return false;
	        }
	        $country=get_country_phone_area_code($param['mobile_prefix'],$error_msg);
	        if($country===false){
	            return false;
	        }
	        $mobile_prefix=$param['mobile_prefix'];
	    }else{
	        if(!isset($param['country'])||empty($param['country'])){
	            $error_msg='请选择合适的国际电话区号';
	            return false;
	        }
	        $mobile_prefix=get_country_phone_area_code($param['country'],$error_msg);
	        if($mobile_prefix===false){
	            return false;
	        }
	    }
	    if($mobile_prefix=='86'){
	        if(!preg_match('/^1[3-9]{1}[0-9]{9}$/',$phone)){
	            $error_msg='请填写合法的手机号';
	            return false;
	        }
	    }else{
	        if(empty($phone)||(!is_string($phone)&&!is_numeric($phone))){
	            $error_msg='请填写合法的手机号';
	            return false;
	        }
	    }
	
	    $allowed_type=C('phone_code.allowed_type');/*允许的短信类型*/
	    if(!in_array($type,$allowed_type['list'])){
	        $error_msg='不支持的短信类型';
	        return false;
	    }
	    if(in_array($type,$allowed_type['no_register'])){
	        //判断是否在黑名单
	        $map=array();
	        $map['mobile']  = $phone;
	        $map['mobile_prefix']  = $mobile_prefix;
	        $result = M('blacklist')->where($map)->field('id')->find();
	        if(!empty($result)){
	            $this->apiReturn(0,"用户已注册！");
	        }
	        $map=array();
	        $map['mobile_prefix']=$mobile_prefix;
	        $map['mobile']=$phone;
	        $userinfo=M('user')->where($map)->field('id')->find();
	        if(!empty($userinfo)){
	            $error_msg='该手机号已注册';
	            return false;
	        }
	    }elseif(in_array($type,$allowed_type['already_register'])){
	        //判断是否在黑名单
	        $map=array();
	        $map['mobile']  = $phone;
	        $map['mobile_prefix']  = $mobile_prefix;
	        $result = M('blacklist')->where($map)->field('id')->find();
	        if(!empty($result)){
	            $this->apiReturn(0,"用户不存在或已被禁用！");
	        }
	        $map=array();
	        $map['mobile_prefix']=$mobile_prefix;
	        $map['mobile']=$phone;
	        $map['status']=1;
	        $userinfo=M('user')->where($map)->field('id')->find();
	        if(empty($userinfo)){
	            $error_msg='该手机号尚未注册或已被禁用';
	            return false;
	        }
	    }
	    $map=array();
	    $map['mobile_prefix']=$mobile_prefix;
	    $map['phone']=$phone;
	    $map['ctime']=array('egt',mktime(0,0,0,date('m'),date('d'),date('Y')));
	    $count=M('phone_code')->where($map)->count();
	    $text_num_limit=intval(C('text_num_limit'));
	    $text_num_limit=$text_num_limit>0?$text_num_limit:10;
	    if($count&&$count>=$text_num_limit){
	        $error_msg='您今天已经发送了'.$count.'条短信，已达到今日的发送上限';
	        return false;
	    }
	    M()->startTrans();
	    $config=array();
	    $config['info_id']=$param['info_id'];
	    $config['type']=$type;
	    $config['mobile_prefix']=$mobile_prefix;
	    $config['phone']=$phone;
	    $has_cxbh=get_system_config('has_cxbh');
	    $config['has_cxbh']=in_array($param['has_cxbh'],array(1,2))?$param['has_cxbh']:(in_array($has_cxbh,array(1,2))?$has_cxbh:2);
	    $is_example=$is_example===true?true:false;
	    if(isset($param['mobile_code'])&&!empty($param['mobile_code'])){
	        $mobile_code=(string)$param['mobile_code'];
	    }
	    if($mobile_prefix=='86'){//国内短信
	        $status=send_domestic_phone_code($config,$mobile_code,$error_msg,$is_example);
	        if(!$status){
	            M()->rollback();
	            $error_msg=$error_msg?$error_msg:'发送失败.';
	            return false;
	        }
	    }else{//港澳台及国际短信
	        $status=send_international_phone_code($config,$mobile_code,$info,$is_example);
	        if(!$status){
	            M()->rollback();
	            $error_msg=$info['Message']?$info['Message']:'发送失败..';
	            return false;
	        }
	    }
	    M()->commit();
	    $error_msg='发送成功';
	    return true;
	}
	/**
	 * 检查短信验证码
	 * @param string $mobile
	 * @param string $code
	 * @return bool|string
	 */
	function check_phone_code($mobile_prefix='',$mobile='',$code='',$type=0){
	    $result=array();
	    $result['status']=false;
	    $result['msg']='';
	    if(empty($mobile_prefix)||!is_string($mobile_prefix)){
	        $result['msg']='国际电话区号为空';
	        return $result;
	    }
	    //允许的短信类型：1 注册，2 登录，3 忘记密码（找回密码），4 重置密码，5 入库申请，6 分析报告查看鉴权，7 人才库信息审核通过短信提醒，8 人才库信息审核失败短信提醒，9 人才库信息缴费短信提醒，10 人才库信息移入问题库短信提醒
	    $allowed_type=C('phone_code.allowed_type');
	    if(!in_array($type,$allowed_type['list'])){
	        $result['msg']='不支持的短信类型';
	        return $result;
	    }
	    $map=array();
	    $map['mobile_prefix']=$mobile_prefix;
	    $map['phone']=$mobile;
	    if(in_array($type,$allowed_type['no_register'])){
	        //判断是否在黑名单
	        $map=array();
	        $map['mobile_prefix']  = $mobile_prefix;
	        $map['mobile']  = $mobile;
	        $result = M('blacklist')->where($map)->field('id')->find();
	        if(!empty($result)){
	            $this->apiReturn(0,"用户已注册！");
	        }
	    }elseif(in_array($type,$allowed_type['already_register'])){
	        //判断是否在黑名单
	        $map=array();
	        $map['mobile_prefix']  = $mobile_prefix;
	        $map['mobile']  = $mobile;
	        $result = M('blacklist')->where($map)->field('id')->find();
	        if(!empty($result)){
	            $this->apiReturn(0,"用户不存在或已被禁用！");
	        }
	    }
	    $map['type']=$type;
	    $day_start=mktime(0,0,0,date('m'),date('d'),date('Y'));
	    $map['ctime']=array('egt',$day_start);//当天有效
	//        $map['ctime']=array('egt',time()-24*60*60);//24小时内有效
	    $code_info=M('phone_code')->where($map)->field('id,code,status')->order('ctime desc')->find();
	    if(empty($code_info)){
	        $result['msg']='未发送验证码';
	        return $result;
	    }elseif($code_info['status']!=1){
	        $result['msg']='验证码已失效';
	        return $result;
	    }
	    if(empty($code)||$code!=$code_info['code']){
	        $result['msg']='验证码错误';
	        $result['mobile_prefix']=$mobile_prefix;
	        $result['mobile']=$mobile;
	        $result['type']=$type;
	        $result['code']=$code;
	        $result['info_map']=$map;
	        $result['info']=$code_info;
	        return $result;
	    }
	    $result['status']=true;
	    $result['msg']='验证码正确';
	    $result['info']=$code_info;
	    return $result;
	}
	/**
	 * 发送电子邮件
	 * @param string $mail_data 邮件信息结构
	 * @$mail_data['receiver'] 收件人
	 * @$mail_data['subject'] 邮件主题
	 * @$mail_data['content']邮件内容
	 * @$mail_data['attachment'] 附件列表
	 * @return boolean
	 */
	
	function send_email($param=array(),&$error_msg=''){
	
	    //生成验证码
	
	    $reg_verify = \Org\Util\String::randString(6,1);
	
	    //构造邮件数据
	    $mail_data['subject'][1]  = '全国留学生信息服务网留学生专业人才邮箱验证码';
	
	    $mail_data['subject'][2]  = '全国留学生信息服务网留学生专业人才审核通知';
	
	    $mail_data['subject'][3]  = '全国留学生信息服务网留学生专业人才审核通过';
	
	    $mail_data['subject'][4]  = '全国留学生信息服务网留学生专业人才审核失败';
	
	    $mail_data['subject'][5]  = '全国留学生信息服务网留学生专业人才付款提醒';
	
	    $mail_data['subject'][6]  = '全国留学生信息服务网留学生专业人才支付通知信息';
	
	    $mail_data['content'][1] = '先生/女士您好：<br>您正使用该邮箱'.$param['email'].'【注册/修改密码】，请在验证码输入框中输入：
	
	    <span style="color:red;font-weight:bold;">'.$reg_verify.'</span>，以完成操作。<br>
	
	    注意：此操作可能会修改您的密码、登录邮箱或绑定手机。如非本人操作，请及时登录并修改
	
	    密码以保证帐户安全 （工作人员不会向您索取此验证码，请勿泄漏！)';
	
	    $mail_data['content'][2] = '<div><span style="line-height: 1.5;">尊敬的留学生'.$param['name'].' 您好。</span></div><div><div>&nbsp; &nbsp;留信网提醒：您的留学生专业人才入库储备信息已经提交，进入审核阶段<span style="line-height: 1.5;">。审核结果会邮箱通知您。</span></div><div><br><br>&nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp;留信网留学生专业人才库<br>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp; &nbsp; &nbsp; '.date('Y-m-d').'<br></div>';
	
	    //"恭喜您，".$infomation['xingming']." ".$sex."，您的".$infomation['yxmc_cn'].'（'.$infomation['yxmc_en'].'）'.$infomation['zymc_cn']."留学生专业人才库已审核通过，查询编号：".$bianhao."，网址：https://ku.cscss.com.cn/search"
	    $has_cxbh=get_system_config('has_cxbh');
	    $param['has_cxbh']=in_array($param['has_cxbh'],array(1,2))?$param['has_cxbh']:(in_array($has_cxbh,array(1,2))?$has_cxbh:2);
	    if($param['has_cxbh']==1){/*有查询编号*/
	        $mail_data['content'][3] = '<div><div style="line-height: 21px;"><span style="line-height: 1.5;">尊敬的留学生'.$param['name'].'您好。</span></div><div style="line-height: 21px;"><div>&nbsp; &nbsp;恭喜您，您的'.$param['yxmc_cn'].'（'.$param['yxmc_en'].'）'.$param['zymc_cn'].'留学生专业人才库已审核通过，查询编号：'.$param['bianhao'].'，网址：https://ku.cscss.com.cn/search</div><div><br><br>&nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp;留信网留学生专业人才库<br>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp; &nbsp; &nbsp; '.date('Y-m-d').'</div>';
	    }else{/*无查询编号*/
	        $mail_data['content'][3] = '<div><div style="line-height: 21px;"><span style="line-height: 1.5;">尊敬的留学生'.$param['name'].'您好。</span></div><div style="line-height: 21px;"><div>&nbsp; &nbsp;恭喜您，您的'.$param['yxmc_cn'].'（'.$param['yxmc_en'].'）'.$param['zymc_cn'].'留学生专业人才库已审核通过，网址：https://ku.cscss.com.cn/search</div><div><br><br>&nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp;留信网留学生专业人才库<br>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp; &nbsp; &nbsp; '.date('Y-m-d').'</div>';
	    }
	
	    //"尊敬的".$infomation['xingming']." ".$sex."，您的".$infomation['yxmc_cn'].'（'.$infomation['yxmc_en'].'）'.$infomation['zymc_cn']."留学生专业人才库审核暂未通过，详细原因请登录留信网专业人才库（https://ku.cscss.com.cn）站内短信查看具体原因。"
	    $mail_data['content'][4] = '<div><div style="line-height: 21px;"><span style="line-height: 1.5;">尊敬的留学生'.$param['name'].'您好。</span></div><div style="line-height: 21px;"><div>&nbsp; &nbsp;您的'.$param['yxmc_cn'].'（'.$param['yxmc_en'].'）'.$param['zymc_cn'].'留学生专业人才库审核暂未通过，详细原因请登录留信网专业人才库（https://ku.cscss.com.cn）站内短信查看具体原因。</div><div><br><br>&nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp;留信网留学生专业人才库<br>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp; &nbsp; &nbsp; '.date('Y-m-d').'</div>';
	
	    $mail_data['content'][5] = '<div><div style="line-height: 21px;"><span style="line-height: 1.5;">尊敬的留学生'.$param['name'].'您好。</span></div><div style="line-height: 21px;"><div>&nbsp; &nbsp;您申请的留学生专业人才库就差一步付款了，审核通过以后您将获得留学生专业人才入库证书以及精美文凭外壳一份，还有机会获得留学生岗位就业内部推荐名额，彻底解决工作问题；  http://ku.cscss.com.cn</div><div><br><br>&nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp;留信网留学生专业人才库<br>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp; &nbsp; &nbsp; '.date('Y-m-d').'</div>';
	
	    $mail_data['content'][6] = '<div><div style="line-height: 21px;"><span style="line-height: 1.5;">尊敬的留学生'.$param['name'].'您好。</span></div><div style="line-height: 21px;"><div>&nbsp; &nbsp;您申请的留学生专业人库已进入审核状态，预计8个工作日完成；  http://ku.cscss.com.cn</div><div><br><br>&nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp;留信网留学生专业人才库<br>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp; &nbsp; &nbsp; '.date('Y-m-d').'</div>';
	
	    //$mail_body_template = $addon_config['default']; //获取邮件模版配置
	
	    //$mail_body = str_replace("[MAILBODY]", $mail_data['content'], $mail_body_template); //使用邮件模版
	
	    $mail_body = $mail_data['content'][$param['type']]; //使用邮件模版
	
	    $mail = new \Api\PHPMailer();
	
	    $mail->IsSMTP(); // 使用SMTP方式发送
	
	    $mail->CharSet='UTF-8';// 设置邮件的字符编码
	
	//    $mail->Host = 'smtp.qq.com'; // 您的企业邮局服务器
	    $mail->Host = 'smtp.exmail.qq.com'; // 您的企业邮局服务器
	
	    $mail->Port = 25; // 设置端口
	
	    $mail->SMTPAuth = true; // 启用SMTP验证功能
	
	    $mail->Username = 'admin@cscss.com.cn'; // 邮局用户名(请填写完整的email地址)
	
	    $mail->Password = '139490wx'; // 邮局密码
	
	    $mail->From = 'admin@cscss.com.cn'; //邮件发送者email地址
	
	    $mail->FromName = "留信网";
	
	    $mail->AddAddress($param['email'], '留信网');//收件人地址，可以替换成任何想要接收邮件的email信箱,格式是AddAddress("收件人email","收件人姓名")
	
	    $mail->IsHTML(true); // set email format to HTML //是否使用HTML格式
	
	    $mail->Subject = $mail_data['subject'][$param['type']];//"PHPMailer测试邮件"; //邮件标题
	
	    $mail->Body = $mail_body; //邮件内容
	
	    if(!$mail->Send())
	    {
	        $error_msg="Mailer Error: " . $mail->ErrorInfo;
	        return false;
	    }else{
	        $_SESSION['mobile_code'] = $reg_verify;
	        if($param['type']<2){
	            $error_msg='验证码发送成功，请注意查收您手机/邮箱';
	        }else{
	            $error_msg='发送成功';
	        }
	        return true;
	    }
	}
	
	/**
	 * 获取管理员信息
	 * @param int $member_id
	 * @param array $param
	 * @return array|mixed
	 */
	function get_administrators($member_id=0,$param=array()){
	    $member_id=intval($member_id);
	    $member_id=$member_id>0?$member_id:0;
	    $group_id=0;
	    $option=array();
	    $option['type_list']=array('group','member');
	    $option['field_list']=array('id','password','salt','truename','managename','alias_name','group_id','email','mobile','status','login_num','last_login_time','last_login_ip','access','created');
	    $option['status_list']=array(0,1,2);
	    $option['type']='group';
	    $option['status']=1;
	    $option['field']='';
	    if(!empty($param)){
	        if(is_string($param)){
	            if($member_id>0){
	                $option['type']='member';
	            }
	            $option['field']=in_array($param,$option['field_list'])?$param:$option['field'];
	        }elseif(is_array($param)){
	            $option['type']=isset($param['type'])&&in_array($param['type'],$option['type_list'])?$param['type']:$option['type'];
	            $option['field']=isset($param['field'])&&in_array($param['field'],$option['field_list'])?$param['field']:$option['field'];
	            $option['status']=isset($param['status'])&&in_array($param['status'],$option['status_list'])?$param['status']:$option['status'];
	        }
	    }
	    if($option['type']=='group'){
	        $group_id=$member_id;
	        $member_id=0;
	    }
	    $result=S('lxw_get_administrators_'.$group_id.'_'.$option['status']);
	    if(empty($result)){
	        $map=array();
	        if($group_id>0){
	            $map['group_id']=$group_id;
	        }
	        $map['status']=$option['status'];
	        $list=M('member')->where($map)->field(implode(',',$option['field_list']))->select();
	        $result=array();
	        if(!empty($list)){
	            foreach($list as $val){
	                $result[$val['id']]=$val;
	            }
	            unset($list);
	            S('lxw_get_administrators_'.$group_id.'_'.$option['status'],$result,30*24*60*60);
	        }
	    }
	    if($option['type']=='member'){
	        if($member_id>0&&isset($result[$member_id])){
	            $result=$result[$member_id];
	            if(!empty($option['field'])){
	                $result=$result[$option['field']];
	            }
	        }
	    }elseif($option['type']=='group'){
	        if(!empty($option['field'])){
	            if(function_exists('array_column')){
	                $result=array_column($result,$option['field']);
	            }else{
	                $temp=array();
	                foreach($result as $val){
	                    $temp[]=$val[$option['field']];
	                }
	                $result=$temp;
	                unset($temp);
	            }
	        }
	    }
	    return $result;
	}
	
	/**
	 * 获取用户组信息
	 * @param int $group_id
	 * @param string $field
	 * @return array|mixed
	 */
	function get_group_info($group_id=0,$field=''){
	    $info=S('lxw_get_user_group_info_'.$group_id);
	    if(empty($info)){
	        $map=array();
	        $map['id']=$group_id;
	        $info=M('group')->where($map)->find();
	        if(!empty($info)){
	            $map=array();
	            $map['group_id']=$group_id;
	            $map['status']=1;
	            $info['group_rule']=M('group_rule')->where($map)->getField('rule_id',true);
	            $group_rule=$info['group_rule'];
	            if(empty($info['group_rule'])){
	                $info['group_rule']=array();
	                $group_rule=array(-1);
	            }
	            $map=array();
	            $map['status']=1;
	            $map['id']=array('in',$group_rule);
	            $info['group_access']=D('Lxkus/Rule')->where($map)->getField('bm',true);
	            if(empty($info['group_access'])){
	                $info['group_access']=array();
	            }
	            S('lxw_get_user_group_info_'.$group_id,$info,30*24*60*60);
	        }else{
	            $info=array();
	        }
	    }
	    if(!empty($field)&&isset($info[$field])){
	        $info=$info[$field];
	    }
	    return $info;
	}
	/**
	 * 获取任务详情
	 */
	function get_task_info($task_id=0,$field=''){
	    $info=S('lxw_get_task_info_'.$task_id);
	    if(empty($info)){
	        $map=array();
	        $map['id']=$task_id;
	        $info=M('task')->where($map)->find();
	        if(!empty($info)){
	            $map=array();
	            $map['task_id']=$task_id;
	            $map['status']=1;
	            $info['task_rule']=M('task_rule')->where($map)->getField('rule_id',true);
	            if(empty($info['task_rule'])){
	                $info['task_rule']=array();
	            }
	            $map=array();
	            $map['status']=1;
	            $map['id']=array('in',$info['task_rule']);
	            $info['task_access']=D('Lxkus/Rule')->where($map)->getField('bm',true);
	            if(empty($info['task_access'])){
	                $info['task_access']=array();
	            }
	            S('lxw_get_task_info_'.$task_id,$info,30*24*60*60);
	        }else{
	            $info=array();
	        }
	    }
	    if(!empty($field)&&isset($info[$field])){
	        $info=$info[$field];
	    }
	    return $info;
	}
	
	/**
	 * 获取某个管理员的任务权限对应权限id列表
	 */
	function get_task_rule_list($member_id=0){
	    $member_id=intval($member_id);
	    if($member_id<=0){
	        return array();
	    }
	//    $result=S('lxw_get_task_rule_list_'.$member_id);
	    if(empty($result)){
	        $task_rule=D('Lxkus/Task')->get_list(1);
	        $map=array();
	        $map['executor_id']=$member_id;
	        $task_id_list=M('task_target')->where($map)->getField('task_id',true);
	        $task_id_list=empty($task_id_list)?array():$task_id_list;
	        $task_rule_list=array();
	        foreach($task_rule as $i){
	            if(in_array($i['id'],$task_id_list)){
	                $task_rule_list=array_merge($task_rule_list,$i['task_rule']);
	            }
	        }
	        $task_rule_list=array_unique($task_rule_list);
	        $result=array();
	        $result['rule_id']=array();
	        $result['access']=array();
	        if(!empty($task_rule_list)){
	            $result['rule_id']=$task_rule_list;
	            $map=array();
	            $map['status']=1;
	            $map['id']=array('in',$task_rule_list);
	            $result['access']=D('Lxkus/Rule')->where($map)->getField('bm',true);
	            if(empty($result['access'])){
	                $result['access']=array();
	            }
	            S('lxw_get_task_rule_list_'.$member_id,$result,30*24*60*60);
	        }
	    }
	    return $result;
	}
	/**
	 * 获取权限详情
	 * @param int $rule_id
	 * @return array|mixed
	 */
	function get_rule_info($rule_id=0,$field=''){
	    $info=S('lxw_get_rule_info_'.$rule_id);
	    if(empty($info)){
	        $map=array();
	        $map['id']=$rule_id;
	        $info=D('Lxkus/Rule')->where($map)->find();
	        if(!empty($info)){
	            $info['module']=strtolower($info['module']);
	            $info['controller']=strtolower($info['controller']);
	            $info['action']=strtolower($info['action']);
	            $info['action']=$info['action']&&$info['action']!='#'?$info['action']:'index';
	            if(!empty($info['param'])){
	                $info['param']=json_decode($info['param'],true);
	            }
	            if(!empty($info['param_rule'])){
	                $info['param_rule']=json_decode($info['param_rule'],true);
	            }
	            if(!empty($info['param_rule_post'])){
	                $info['param_rule_post']=json_decode($info['param_rule_post'],true);
	            }
	            S('lxw_get_rule_info_'.$rule_id,$info,30*24*60*60);
	        }else{
	            $info=array();
	        }
	    }
	    if(!empty($field)&&isset($info[$field])){
	        $info=$info[$field];
	    }
	    return $info;
	}
	/**
	 * 获取有任务指定权限的infomation表where条件
	 */
	function get_has_task_rule_option($member_id=0,$task_id=1,$info_id_list=array()){
	    $task_id=intval($task_id);
	    $task_info=get_task_info($task_id);
	    if($task_id<=0||empty($task_info)){
	        return '';
	    }
	    $member_id=intval($member_id);
	    $member_info=get_administrators($member_id,array('type'=>'member'));
	    if($member_id<=0||empty($member_info)){
	        $member_id=session('manager_id');
	        $member_info=get_administrators($member_id,array('type'=>'member'));
	    }
	    $group_info=get_group_info($member_info['group_id']);
	    $map=array();
	    $map['status']=1;
	    if($group_info['type']!=1&&$group_info['rule_type']!=1){
	        $map['executor_id']=$member_info['id'];
	    }else{
	        $map['executor_id']=array('gt',0);
	    }
	    $map['task_id']=$task_id;
	    if(is_array($info_id_list)&&!empty($info_id_list)){
	        $map['target_id']=array('in',$info_id_list);
	    }
	    $list=M('task_target')->where($map)->getField('target_id',true);
	    $list=!empty($list)?$list:array(-1);
	    return $list;
	}
	
	/**
	 * 获取infomation表任务指派情况
	 */
	function get_task_point_status_by_target_id($target_id=0){
	    $target_id=intval($target_id);
	    if($target_id<=0){
	        return array();
	    }
	//    $result=S('lxw_get_task_point_status_by_target_id_'.$target_id);
	    if(empty($result)){
	        $map=array();
	        $map['task_id']=1;
	        $map['target_id']=$target_id;
	        $map['executor_id']=array('gt',0);
	        $result=M('task_target')->where($map)->getField('executor_id',true);
	        if(!empty($result)){
	            S('lxw_get_task_point_status_by_target_id_'.$target_id,$result,30*24*60*60);
	        }
	    }
	    return $result;
	}
	/**
	 * 获取单页分类（sincat）
	 */
	function get_sincat_list(){
	    $list=S('lxw_get_sincat_list');
	    if(empty($list)){
	        $list=M('sincat')->where(1)->order('cat_sort asc')->select();
	        if(!empty($list)){
	            S('lxw_get_sincat_list',$list,30*24*60*60);
	        }
	    }
	    return $list;
	}
	/**
	 * 查看分析报告完成状态
	 */
	function get_analysis_completion_status($infoid=0,$analysis_info=array(),&$error_msg=true,&$analysis_completion_status=0){
	    $is_show=$error_msg===false?false:true;
	    $error_msg='';
	    if(is_numeric($infoid)){
	        $infoid=intval($infoid);
	        $infomation=D('Lxkus/Infomation')->get_info($infoid);
	        if(empty($infomation)){
	            $error_msg='该人才库信息不存在';
	            if($is_show){
	                return '';
	            }else{
	                return false;
	            }
	        }
	    }elseif(is_array($infoid)){
	        $infomation=$infoid;
	        $infoid=$infomation['id'];
	    }else{
	        $error_msg='非法参数';
	        if($is_show){
	            return '';
	        }else{
	            return false;
	        }
	    }
	    if(!isset($infomation['id'])||!isset($infomation['check_analysis_completion'])){
	        $error_msg='参数：人才库id 或 检查分析报告完成状态要求(id或check_analysis_completion) 为空';
	        if($is_show){
	            return '';
	        }else{
	            return false;
	        }
	    }
	    if(!empty($infomation['check_analysis_completion'])){
	        if(is_array($infomation['check_analysis_completion'])){
	            //todo
	        }elseif(is_string($infomation['check_analysis_completion'])&&strpos($infomation['check_analysis_completion'],'{')!==false&&strpos($infomation['check_analysis_completion'],'}')!==false){
	            $infomation['check_analysis_completion']=json_decode($infomation['check_analysis_completion'],true);
	        }else{
	            $infomation['check_analysis_completion']=array();
	        }
	    }else{
	        $infomation['check_analysis_completion']=array();
	    }
	    /*给 验证规则  赋予 默认值*/
	    if(empty($infomation['check_analysis_completion'])){
	        $infomation['check_analysis_completion']['analysis_check']=1;
	        $infomation['check_analysis_completion']['analysis_field_check']=array('yuyan');
	        $infomation['check_analysis_completion']['analysiy_check']=1;
	        $infomation['check_analysis_completion']['analysix_check']=1;
	        $infomation['check_analysis_completion']['is_transfer_school']=0;
	    }
	    $analysis_completion_status=0;
	    $all_status=0;
	    if($infomation['check_analysis_completion']['analysis_check']==1){
	        /*分析报告内容字段验证*/
	        /*  代表数字：1 */
	        if(empty($analysis_info)){
	            $analysis_info=M("analysis")->where("infoid=".$infoid)->find();
	        }
	        if(!empty($infomation['check_analysis_completion']['analysis_field_check'])){
	            if(!empty($analysis_info)){
	                $all_status+=1;
	                $count=0;
	                foreach($infomation['check_analysis_completion']['analysis_field_check'] as $val){
	                    if(isset($analysis_info[$val])&&!empty($analysis_info)){
	                        $count++;
	                    }
	                }
	                if($count==count($infomation['check_analysis_completion']['analysis_field_check'])){
	                    $analysis_completion_status+=1;
	                }
	            }
	        }
	    }
	    if($infomation['check_analysis_completion']['analysiy_check']==1||$infomation['check_analysis_completion']['analysix_check']==1){
	        /*  成绩课程得分  或  出入境记录    */
	        /*  代表数字：2   或  代表数字：4   */
	        $limit=array();
	        if($infomation['check_analysis_completion']['is_transfer_school']==1){
	            $limit['analysiy_check']=1;
	            $limit['analysix_check']=1;
	        }else{
	            if(in_array($infomation['xwcc'],array(2,3))){
	                $limit['analysiy_check']=1;
	                $limit['analysix_check']=1;
	            }else{
	                $limit['analysiy_check']=3;
	                $limit['analysix_check']=3;
	            }
	        }
	        if($infomation['check_analysis_completion']['analysiy_check']==1){
	            /*  成绩课程得分  */
	            /*  代表数字：2   */
	            $all_status+=2;
	            $map=array();
	            $map['infoid']=$infoid;
	            $count=M('analysiy')->where($map)->count();
	            if($count>=$limit['analysiy_check']){
	                $analysis_completion_status+=2;
	            }
	        }
	        if($infomation['check_analysis_completion']['analysix_check']==1){
	            /*   留学出入境记录    */
	            /*   代表数字：4   */
	            $all_status+=4;
	            $analysix_list=D('Lxkus/Analysix')->get_list($infoid,2);
	            if(empty($analysix_list['list'])){
	                $count=0;
	            }else{
	                $count=count($analysix_list['list']);
	            }
	            if($count>=$limit['analysix_check']){
	                $analysis_completion_status+=4;
	            }
	        }
	    }
	    $error_msg=$all_status;
	    if($analysis_completion_status==$all_status){
	        if($is_show){
	            $result = "style='color:#0dec0d;'";
	        }else{
	            $result = true;
	        }
	    }elseif($analysis_completion_status==0){
	        if($is_show){
	            $result = "style='color:red;'";
	        }else{
	            $result = false;
	        }
	    }else{
	        if($is_show){
	            $result = "style='color:#ecec10;'";
	        }else{
	            $result = true;
	        }
	    }
	    return $result;
	}
	
	/**
	 * 获取已完成分析报告infoid列表
	 */
	function get_completed_analysis_list($infoid_list=array(),$analysis_status=array()){
	    if(empty($infoid_list)){
	        return array(-1);
	    }
	    if(!is_array($analysis_status)){
	        $analysis_status=array(1,1,1);
	    }else{
	        $analysis_status[0]=in_array($analysis_status[0],array(1,2))?$analysis_status[0]:1;
	        $analysis_status[1]=in_array($analysis_status[1],array(1,2))?$analysis_status[1]:1;
	        $analysis_status[2]=in_array($analysis_status[2],array(1,2))?$analysis_status[2]:1;
	    }
	    if($analysis_status[1]==2&&$analysis_status[2]==2){
	        return $infoid_list;
	    }
	    if($analysis_status[1]==1){
	        $map=array();
	        /*$map['a.infoid']=array('in',$infoid_list);*/
	        $map['b.count']=array('egt',3);
	        $sql="select infoid,count(id) count from __ANALYSIY__ where infoid in (".implode(',',$infoid_list).") group by infoid";
	        $infoid_list_1=M('analysiy')->alias('a')->join(' LEFT JOIN ('.$sql.') b ON a.infoid=b.infoid')->where($map)->group('a.infoid')->field('a.infoid')->select();
	    }
	    if($analysis_status[2]==1){
	        $map=array();
	        /*$map['a.infoid']=array('in',$infoid_list);*/
	        $map['b.count']=array('egt',5);
	        $sql="select infoid,count(id) count from __ANALYSIX__ where infoid in (".implode(',',$infoid_list).") and is_del=1 and cat_id=1 and ((termini_country !='' and termini_country=country and status=2) or (termini_country='')) group by infoid";
	        $infoid_list_2=M('analysix')->alias('a')->join('  LEFT JOIN  ('.$sql.') b ON a.infoid=b.infoid')->where($map)->group('a.infoid')->field('a.infoid')->select();
	    }
	    $infoid_list_1=array_column_diy($infoid_list_1,'infoid');
	    $infoid_list_2=array_column_diy($infoid_list_2,'infoid');
	    if($analysis_status[1]==1&&$analysis_status[2]==1){
	        $infoid_list=array_intersect($infoid_list_1,$infoid_list_2);/*获取两个数组的交集*/
	        if(empty($infoid_list)||count($infoid_list)==0){
	            $infoid_list=array(-1);
	        }
	        /*sort($infoid_list);*/
	    }elseif($analysis_status[1]==1){
	        $infoid_list=$infoid_list_1;
	    }elseif($analysis_status[2]==1){
	        $infoid_list=$infoid_list_2;
	    }
	
	    return $infoid_list;
	}
	
	/**
	 * 自动删除超时infomation表数据（超出3个月）
	 */
	function auto_delete_overtime_infomation(){
	    $map=array();
	    $map['is_del'] = 1;
	    $map['is_pass'] = 1;
	    $map['rzrq'] = array(array('gt',mktime(0,0,0,5,1,2019)),array('lt',mktime(0,0,0,date('m')-3,date('d')+1,date('Y'))));
	    $data=array();
	    $data['is_del']=0;
	    $data['is_show']=1;
	    $data['delete_time']=time();
	    $status = M('infomation')->where($map)->save($data);
	    if($status===false){
	        return false;
	    }
	    return true;
	}
	
	/**
	 * 获取任务数量
	 */
	function get_task_tips($rule_id=0,$is_normal=1){
	    $is_normal=in_array($is_normal,array(1,2))?$is_normal:1;
	    $rule_id=is_int($rule_id)?$rule_id:intval($rule_id);
	    if($rule_id<=0){
	        return 0;
	    }
	    $member_id=session('manager_id');
	    $result=S('lxw_get_task_tips_'.$member_id.'_'.$rule_id.'_'.$is_normal);
	    if(empty($result)){
	        $result=array();
	        $memberinfo=get_administrators($member_id,array('type'=>'member'));
	        if(!empty($memberinfo['access'])){
	            if(strpos($memberinfo['access'],',')===false){
	                $memberinfo['access']=array($memberinfo['access']);
	            }else{
	                $memberinfo['access']=array();
	            }
	        }else{
	            $memberinfo['access']=array();
	        }
	        $memberinfo['access']=explode(',',$memberinfo['access']);
	        $groupinfo=get_group_info($memberinfo['group_id']);
	        $ruleinfo=get_rule_info($rule_id);
	        if(!empty($memberinfo)&&!empty($groupinfo)&&!empty($ruleinfo)){
	            if(in_array($rule_id,$groupinfo['group_rule'])||in_array($ruleinfo['bm'],$memberinfo['access'])){
	                $map=array();
	                $map['status']=1;
	                $map['rule_id']=$rule_id;
	                $taskid=M('task_rule')->where($map)->getField('task_id');
	                $taskinfo=get_task_info($taskid);
	                if($taskid>0&&!empty($taskinfo)){
	                    if($taskid==1&&$taskinfo['target_table']=='infomation'){
	                        $num2=get_infomation_tips_by_task($member_id,$rule_id,'num');
	                        $result['num']=$num2;
	                        S('lxw_get_task_tips_'.$member_id.'_'.$rule_id.'_'.$is_normal,$result,30*24*60*60);
	                    }
	                }
	            }
	        }
	    }
	    $result=is_numeric($result['num'])&&$result['num']>0?$result['num']:0;
	    return $result;
	}
	
	/**
	 * 获取当前管理员操作的infomaition数量（不包括任务指定的）
	 */
	function get_infomation_tips_by_editor($member_id=0,$rule_id=0){
	    $rule_id=intval($rule_id);
	    $memberinfo=get_administrators($member_id,array('type'=>'member'));
	    if(empty($memberinfo)||$rule_id<=0){
	        return 0;
	    }
	    $result=S('lxw_get_infomation_tips_by_editor_'.$member_id.'_'.$rule_id);
	    if(empty($result)){
	        $result=array();
	        $member_name=array($memberinfo['managename']);
	        if(!empty($memberinfo['alias_name'])){
	            if(strpos($memberinfo['alias_name'],',')===false){
	                if($memberinfo['managename']!=$memberinfo['alias_name']){
	                    $member_name[]=$memberinfo['alias_name'];
	                }
	            }else{
	                $memberinfo['alias_name']=explode(',',$memberinfo['alias_name']);
	                $member_name=array_merge($member_name,$memberinfo['alias_name']);
	                $member_name=array_unique($member_name);
	            }
	        }
	
	    }
	    return $result['num'];
	}
	/**
	 * 获取当前管理员需要操作的infomaition数量（仅包括任务指定的）（尚未操作）
	 */
	function get_infomation_tips_by_task($member_id=0,$rule_id=0,$field=''){
	    $rule_id=intval($rule_id);
	    $memberinfo=get_administrators($member_id,array('type'=>'member'));
	    if(empty($memberinfo)||$rule_id<=0){
	        return 0;
	    }
	    $result=S('lxw_get_infomation_tips_by_task_'.$member_id.'_'.$rule_id);
	    if(empty($result)){
	        $result=array();
	        $member_name=array($memberinfo['managename']);
	        if(!empty($memberinfo['alias_name'])){
	            if(strpos($memberinfo['alias_name'],',')===false){
	                if($memberinfo['managename']!=$memberinfo['alias_name']){
	                    $member_name[]=$memberinfo['alias_name'];
	                }
	            }else{
	                $memberinfo['alias_name']=explode(',',$memberinfo['alias_name']);
	                $member_name=array_merge($member_name,$memberinfo['alias_name']);
	                $member_name=array_unique($member_name);
	            }
	        }
	        $rule_info=get_rule_info($rule_id);
	        $where=array();
	        $where['_string'] .= " (shenhe REGEXP '";
	        foreach($member_name as $val){
	            $where['_string'] .= "($val)|(^$val,)|(.*,$val$)|(.*,$val,.*)|";
	        }
	        $where['_string']=rtrim(trim($where['_string']),'|');
	        $where['_string'] .= "' ) ";
	        $info_id_list=M('infomation')->where($where)->getField('id',true);
	        $info_id_list=empty($info_id_list)?array(-1):$info_id_list;
	        $sql_order="(select userid,sum(haspay) haspay,sum(status) status,group_concat(`desc` order by create_time asc separator '、') `desc`,create_time from __ORDER__ where status>0 group by userid order by create_time desc)";
	        $map=array();
	        $map['a.executor_id']=$member_id;
	        $map['a.task_id']=1;
	        $map['a.target_id']=array('not in',$info_id_list);
	        if($rule_info['param']['status']==1){
	            $map['oi.is_pass'] = 0;
	            $map['os.status'] = array('gt',0);
	        }elseif($rule_info['param']['status']==2){
	            $map['oi.is_pass'] = 1;
	            $map['os.status'] = array('gt',0);
	        }else{
	            $map['oi.is_pass'] = 0;
	            $map['_string'] = " (os.status =0 or os.status ='' or os.status IS NULL) " ;
	        }
	        $result['num']=M('task_target')->alias('a')
	            ->join('LEFT JOIN __INFOMATION__ oi ON a.target_id = oi.id')
	            ->join('LEFT JOIN '.$sql_order.' os ON oi.uid = os.userid')
	            ->where($map)->count();
	        $result['info_id']=M('task_target')->alias('a')
	            ->join('LEFT JOIN __INFOMATION__ oi ON a.target_id = oi.id')
	            ->join('LEFT JOIN '.$sql_order.' os ON oi.uid = os.userid')
	            ->where($map)->field('oi.id')->select();
	        if(!(is_numeric($result['num'])&&$result['num']>0)){
	            $result['num']=0;
	            $result['info_id']=array();
	        }else{
	            $result['info_id']=array_column_diy($result['info_id'],'id');
	        }
	        S('lxw_get_infomation_tips_by_task_'.$member_id.'_'.$rule_id,$result,30*24*60*60);
	    }
	    if(!empty($field)&&in_array($field,array('num','info_id'))){
	        $result=$result[$field];
	    }
	    return $result;
	}
	
	/**
	 * 获取广告位列表 / 获取广告位信息
	 * @param int $ap_id
	 * @param int $type
	 * @return array|mixed
	 */
	function get_adv_position($ap_id=0,$type=1){
	    $type_list=array(1,2);
	    if($type=='all'||(is_numeric($ap_id)&&$ap_id>0)){
	        $list=array();
	        foreach($type_list as $val){
	            $temp=get_adv_position(0,$val);
	            if(empty($list)){
	                $list=$temp;
	            }else{
	                foreach($temp as $key=>$val){
	                    if(!isset($list[$key])){
	                        $list[$key]=$val;
	                    }
	                }
	            }
	            unset($temp);
	        }
	        $ap_id=intval($ap_id);
	        if($ap_id>0){
	            $list=$list[$ap_id];
	            if(!empty($type)&&is_string($type)&&isset($list[$type])){
	                $list=$list[$type];
	            }
	        }
	        return $list;
	    }
	    $type=in_array($type,$type_list)?$type:1;
	    $list=F('lxw_get_adv_position_by_type_'.$type);
	    if(empty($list)){
	        $map=array();
	        $map['status']=1;
	        $map['type']=$type;
	        $list=M('adv_position')->where($map)->select();
	        if(!empty($list)){
	            $temp=array();
	            foreach($list as $val){
	                $temp[$val['ap_id']]=$val;
	            }
	            $list=$temp;
	            unset($temp);
	            F('lxw_get_adv_position_by_type_'.$type,$list);
	        }
	    }
	    if(!empty($ap_id)&&!is_numeric($ap_id)&&is_string($ap_id)){
	        $status=false;
	        foreach($list as $val){
	            if(isset($val[$ap_id])){
	                $status=true;
	            }
	            break;
	        }
	        if($status){
	            $list=array_column_diy($list,$ap_id);
	        }
	    }
	    return $list;
	}
	/**
	 * 获取省市区信息
	 * @param int $region_id
	 * @param string $field
	 * @return array|bool|mixed
	 */
	function get_region_info($region_id=0,$field='',$parent_id=0){
	    if(!empty($region_id)&&((!is_numeric($region_id)&&is_string($region_id))||is_array($region_id))){
	        if(strpos($region_id,'$')!==false||strpos($region_id,'[')!==false||strpos($region_id,"'")!==false||strpos($region_id,'-')!==false){
	            $region_id=999999999999;
	            $result=array();
	        }elseif(!is_numeric($region_id)&&is_string($region_id)){
	            $name_list=array(2=>['省'],3=>['市'],4=>['区','县']);
	            $name=array();
	            foreach($name_list as $key=>$val){
	                $temp=array();
	                $temp['level']=$key;
	                $temp['name']=array($region_id);
	                foreach($val as $v){
	                    if(strpos($region_id,$v)!==false){
	                        $temp['name'][]=mb_substr($region_id,0,mb_strlen($region_id,'utf-8')-1,'utf-8');
	                    }else{
	                        $temp['name'][]=$region_id.$v;
	                    }
	                }
	                $name[]=$temp;
	            }
	            $map=array();
	            if($parent_id>0){
	                $map['parent_id']=$parent_id;
	            }
	            $map['_string']='';
	            foreach($name as $val){
	                $map['_string'].=' (level='.$val['level'].' and (';
	                foreach($val['name'] as $v){
	                    $map['_string'].="  name='".$v."' or ";
	                }
	                $map['_string']=rtrim(trim($map['_string']),'or');
	                $map['_string'].=')) or ';
	            }
	            $map['_string']=rtrim(trim($map['_string']),'or');
	            $result=M('region')->where($map)->field('region_id')->find();
	            if(!empty($result)){
	                $region_id=$result['region_id'];
	                $result=get_region_info($region_id);
	            }else{
	                if(!empty($field)&&$field=='name'){
	                    return $region_id;
	                }
	                $result=array();
	                $result['region_id']=$region_id;
	                $result['name']=$region_id;
	                $result['parent_id']=-1;
	                $result['level']=-1;
	            }
	        }elseif(is_array($region_id)){
	            $result=array();
	            foreach($region_id as $val){
	                $temp=get_region_info($val);
	                if(!empty($temp)){
	                    $result[0]=$temp;
	                    break;
	                }
	            }
	            if(empty($result)){
	                if($field=='name'){
	                    return implode('',$region_id);
	                }else{
	                    return $region_id;
	                }
	            }
	            foreach($region_id as $val){
	                $temp=get_region_info($val,'',$result[0]['region_id']);
	                if(!empty($temp)){
	                    $result[1]=$temp;
	                    break;
	                }
	            }
	            if(!empty($result[1])){
	                foreach($region_id as $val){
	                    $temp=get_region_info($val,'',$result[1]['region_id']);
	                    if(!empty($temp)){
	                        $result[2]=$temp;
	                        break;
	                    }
	                }
	            }
	            return $result;
	        }
	        if(!isset($result)||empty($result)){
	            $region_id=999999999999;
	            $result=array();
	        }
	    }else{
	        $region_id=intval($region_id);
	        $result=S('lxw_get_region_info_'.$region_id);
	        if(empty($result)){
	            if($region_id==0){
	                $map=array();
	                $map['parent_id']=$region_id;
	                $result=M('region')->where($map)->select();
	                if(!empty($result)){
	                    S('lxw_get_region_info_'.$region_id,$result,30*24*60*60);
	                }
	            }elseif($region_id>0){
	                $map=array();
	                $map['region_id']=$region_id;
	                $result=M('region')->where($map)->find();
	                if(!empty($result)){
	                    if($result['level']>2){
	                        $temp_parent_id=$result['parent_id'];
	                        for($i=$result['level'];$i>=2;$i--){
	                            if($temp_parent_id>0){
	                                $temp=get_region_info($temp_parent_id);
	                                if($temp['level']==2){
	                                    $result['province_id']=$temp['region_id'];
	                                    $result['province']=$temp['name'];
	                                }elseif($temp['level']==3){
	                                    $result['city_id']=$temp['region_id'];
	                                    $result['city']=$temp['name'];
	                                }elseif($temp['level']==4){
	                                    $result['area_id']=$temp['region_id'];
	                                    $result['area']=$temp['name'];
	                                }
	                                $temp_parent_id=$temp['parent_id'];
	                            }
	                        }
	                    }
	                    if($result['level']==2){
	                        $result['province_id']=$result['region_id'];
	                        $result['province']=$result['name'];
	                    }elseif($result['level']==3){
	                        $result['city_id']=$result['region_id'];
	                        $result['city']=$result['name'];
	                    }elseif($result['level']==4){
	                        $result['area_id']=$result['region_id'];
	                        $result['area']=$result['name'];
	                    }
	                    if(empty($result['city'])){
	                        $result['city_id']='';
	                        $result['city']='';
	                    }
	                    if(empty($result['area'])){
	                        $result['area_id']='';
	                        $result['area']='';
	                    }
	                    if($result['level']==4){
	                        $result['child']=array();
	                    }elseif(in_array($result['level'],array(2,3))){
	                        $map=array();
	                        $map['parent_id']=$region_id;
	                        $result['child']=M('region')->where($map)->select();
	                        if(empty($result['child'])){
	                            $result['child']=array();
	                        }
	                    }
	                    if(in_array($result['level'],array(2,3,4))){
	                        S('lxw_get_region_info_'.$region_id,$result,30*24*60*60);
	                    }
	                }
	            }else{
	                $region_id=999999999999;
	                $result=array();
	            }
	        }
	    }
	    if($region_id>0){
	        if(!empty($field)){
	            if(isset($result[$field])){
	                $result=$result[$field];
	            }else{
	                $result='';
	            }
	        }
	    }else{
	        if(!empty($field)&&isset($result[0][$field])){
	            $status=false;
	            foreach($result as $val){
	                if(isset($val[$field])){
	                    $status=true;
	                }
	                break;
	            }
	            if($status){
	                $result=array_column_diy($result,$field);
	            }
	        }
	    }
	    return $result;
	}
	
	/**
	 * 获取人才库信息（infomation表）
	 * @param int $id
	 * @param string $field
	 * @return array|mixed|string
	 */
	function get_user_infomation($id=0,$field=''){
	    $id=intval($id);
	    $id=$id>0?$id:0;
	    $result=S('lxw_get_user_infomation_'.$id);
	    if(empty($result)){
	        $map=array();
	        $map['id']=$id;
	        $result=M('infomation')->where($map)->find();
	        if(!empty($result)){
	            S('lxw_get_user_infomation_'.$id,$result,30*24*60*60);
	        }else{
	            $result=array();
	        }
	    }
	    if(!empty($field)){
	        if(isset($result[$field])){
	            $result=$result[$field]?$result[$field]:'';
	        }else{
	            $result='';
	        }
	    }
	    return $result;
	}
	/**
	 * 获取用户信息
	 */
	function get_user_info($id=0,$field=''){
	    $id=intval($id);
	    $id=$id>0?$id:0;
	    $result=S('lxw_get_user_info_'.$id);
	    if(empty($result)){
	        $map=array();
	        $map['id']=$id;
	        $result=M('user')->where($map)->find();
	        if(!empty($result)){
	            S('lxw_get_user_info_'.$id,$result,30*24*60*60);
	        }else{
	            $result=array();
	        }
	    }
	    if(!empty($field)){
	        if(isset($result[$field])){
	            $result=$result[$field]?$result[$field]:'';
	        }else{
	            $result='';
	        }
	    }
	    return $result;
	}
	/**
	 * 获取用户代理信息
	 * @param int $status
	 * @param int $id
	 * @param string $field
	 * @return array|mixed
	 */
	function get_user_agent($status=1,$id=0,$field=''){
	    if(!empty($id)&&!is_numeric($id)&&is_string($id)){
	        $name=trim($id);
	    }else{
	        $name='';
	    }
	    $id=intval($id);
	    if($id>0){
	        $status=in_array($status,array(1,2,'all'))?$status:'all';
	    }else{
	        $status=in_array($status,array(1,2,'all'))?$status:1;
	    }
	    if($status=='all'){
	        $list1=get_user_agent(1);
	        $list2=get_user_agent(2);
	        $result=array_merge($list1,$list2);
	        unset($list1);
	        unset($list2);
	    }else{
	        $status=in_array($status,array(1,2))?$status:1;
	        $result=S('lxw_get_user_agent_list_'.$status);
	        if(empty($result)){
	            $map=array();
	            $map['status']=$status;
	            $result=M('user_agent')->where($map)->order('name asc,alias_name asc,id asc')->select();
	            if(!empty($result)&&!empty($result[0])){
	                foreach($result as $key=>$val){
	                    if(!empty($val['alias_name'])){
	                        if(strpos($val['alias_name'],',')!==false){
	                            $result[$key]['alias_name']=explode(',',$val['alias_name']);
	                        }else{
	                            $result[$key]['alias_name']=array($val['alias_name']);
	                        }
	                    }else{
	                        $result[$key]['alias_name']=array();
	                    }
	                    if(!in_array($val['name'],$result[$key]['alias_name'])){
	                        $result[$key]['alias_name'][]=$val['name'];
	                    }
	                }
	                S('lxw_get_user_agent_list_'.$status,$result,30*24*60*60);
	            }else{
	                $result=array();
	            }
	        }
	    }
	    if($id>0){
	        foreach($result as $val){
	            if($id==$val['id']){
	                $result=$val;
	                break;
	            }
	        }
	        if(!empty($field)&&isset($result[$field])){
	            $result=$result[$field];
	        }
	    }elseif(!empty($name)){
	        foreach($result as $val){
	            if(in_array($name,$val['alias_name'])){
	                $result=$val;
	                break;
	            }
	        }
	        if(!empty($field)&&isset($result[$field])){
	            $result=$result[$field];
	        }
	    }
	    return $result;
	}
	
	/**
	 * 获取用户代理规定时间内的已注册人数
	 */
	function get_user_angent_count($angent='',$time_limit=1){
	    $time_limit=in_array($time_limit,array(1,2,3,4))?$time_limit:1;
	    $angent_info=get_user_agent('all',$angent);
	    if(empty($angent)||empty($angent_info)){
	        return '';
	    }
	    $result=S('lxw_get_user_angent_count_'.$angent_info['id'].'_'.$time_limit);
	    if(empty($result)){
	        $result=array();
	        $map=array();
	        $map['_string']="zerenren !='' AND zerenren IS NOT NULL ";
	        if(mb_strpos(trim($angent_info['name']),' ',0,'utf-8')!==false){
	            $val['name']=explode(' ',trim($angent_info['name']));
	            $temp='';
	            foreach($angent_info['name'] as $k=>$i){
	                if($k==0){
	                    $temp=$angent_info['name'][0];
	                }else{
	                    $temp.='[ ]{1,}'.$angent_info['name'][$k];
	                }
	            }
	            $angent_info['name']=$temp;
	        }
	        $map['_string'].=" and ( BINARY zerenren REGEXP '^[ ]{0,}".$angent_info['name']."[ ]{0,}$' or zerenren=".$angent_info['id']."  or ";
	        if(!empty($angent_info['alias_name'])){
	            foreach($angent_info['alias_name'] as $v){
	                if(mb_strpos(trim($v),' ',0,'utf-8')!==false){
	                    $v=explode(' ',trim($v));
	                    $temp='';
	                    foreach($v as $k=>$i){
	                        if($k==0){
	                            $temp=$v[0];
	                        }else{
	                            $temp.='[ ]{1,}'.$v[$k];
	                        }
	                    }
	                    $v=$temp;
	                }
	                $map['_string'].=" BINARY zerenren REGEXP '^[ ]{0,}".$v."[ ]{0,}$' or ";
	            }
	        }
	        $map['_string']=rtrim(trim($map['_string']),'or');
	        $map['_string'].=" ) ";
	        $day=date('d');
	        $month=date('m');
	        $year=date('Y');
	        $map_pay=$map;
	        if($time_limit==1){
	            $map['tjsj']=array(array('egt',mktime(0,0,0,$month,$day,$year)),array('elt',mktime(0,0,0,$month,$day+1,$year)-1));
	            $map_pay['os.create_time']=array(array('egt',mktime(0,0,0,$month,$day,$year)),array('elt',mktime(0,0,0,$month,$day+1,$year)-1));
	        }elseif($time_limit==2){
	            $map['tjsj']=array(array('egt',mktime(0,0,0,$month,1,$year)),array('elt',mktime(0,0,0,$month+1,$day,$year)-1));
	            $map_pay['os.create_time']=array(array('egt',mktime(0,0,0,$month,1,$year)),array('elt',mktime(0,0,0,$month+1,$day,$year)-1));
	        }elseif($time_limit==3){
	            $map['tjsj']=array(array('egt',mktime(0,0,0,1,1,$year)),array('elt',mktime(0,0,0,$month,$day,$year+1)-1));
	            $map_pay['os.create_time']=array(array('egt',mktime(0,0,0,1,1,$year)),array('elt',mktime(0,0,0,$month,$day,$year+1)-1));
	        }
	        $title='审核费';
	        $map_pay['_string'] .=" and (`desc`='".$title."' or `desc` like '".$title."、%' or `desc` like '%、".$title."' or `desc` like '%、".$title."、%') ";
	        $map_pay['os.status']=array('gt',0);
	        $sql_order="(select userid,sum(haspay) haspay,sum(status) status,group_concat(`desc` order by create_time asc separator '、') `desc`,create_time from __ORDER__ where status>0  group by userid order by create_time desc)";
	        $result['count']=M('infomation')->where($map)->count();
	        $result['pay_count']=M('infomation')->alias('oi')->join('LEFT JOIN '.$sql_order.' os ON oi.uid = os.userid')->where($map_pay)->count();
	        $result['count']=!empty($result['count'])&&$result['count']>0?$result['count']:0;
	        $result['pay_count']=!empty($result['pay_count'])&&$result['pay_count']>0?$result['pay_count']:0;
	        S('lxw_get_user_angent_count_'.$angent_info['id'].'_'.$time_limit,$result,30*24*60*60);
	    }
	    return $result;
	}
	
	/**
	 * 获取专业列表/学科列表
	 */
	function get_subject_info($id=0,$field=''){
	    $id=intval($id);
	    $id=$id>0?$id:0;
	    $result=S('lxw_get_subject_list');
	    if(empty($result)){
	        $map=array();
	        $map['sort']=0;
	        $result=M('subject')->where($map)->select();
	        if(!empty($result)){
	            foreach($result as $key=>$val){
	                $result[$key]['title']=trim($val['title']);
	                $map['sort']=$val['id'];
	                $result[$key]['child']=M('subject')->where($map)->select();
	                if(empty($result[$key]['child'])){
	                    $result[$key]['child']=array();
	                }
	                foreach($result[$key]['child'] as $k=>$v){
	                    $title=trim($v['title']);
	                    if(strpos($title,' ')!==false){
	                        $title=explode(' ',$title);
	                        unset($title[0]);
	                        $title=implode(' ',$title);
	                    }
	                    $result[$key]['child'][$k]['title']=$title;
	                }
	            }
	            S('lxw_get_subject_list',$result,30*24*60*60);
	        }else{
	            $result=array();
	        }
	    }
	    if($id>0){
	        foreach($result as $val){
	            if($val['id']==$id){
	                $result=$val;
	                break;
	            }
	            foreach($val['child'] as $v){
	                if($v['id']==$id){
	                    $result=$v;
	                    break 2;
	                }
	            }
	        }
	        if(!empty($result)&&isset($result[$field])){
	            $result=$result[$field]?$result[$field]:'';
	        }
	    }
	    return $result;
	}
	function get_country_info($id=0,$field=''){
	    $id=intval($id);
	    $id=$id>0?$id:0;
	    $result=S('lxw_get_country_list');
	    if(empty($result)){
	        $result=M('country')->where(1)->order('sort asc')->select();
	        if(!empty($result)){
	            $temp=array();
	            foreach($result as $val){
	                $val['title']=trim($val['title']);
	                $temp[$val['id']]=$val;
	            }
	            $result=$temp;
	            S('lxw_get_country_list',$result,30*24*60*60);
	        }else{
	            $result=array();
	        }
	    }
	    if($id>0){
	        if(isset($result[$id])){
	            $result=$result[$id];
	        }else{
	            $result=array();
	        }
	        $result['analysiy_template']=D('Lxkus/AnalysiyTemplateContent')->get_list($result['analysiy_template_id']);
	        if(!empty($field)){
	            if(!empty($result)&&isset($result[$field])){
	                $result=$result[$field]?$result[$field]:'';
	            }
	        }
	    }else{
	        if(!empty($field)){
	            foreach($result as $val){
	                if(isset($val[$field])){
	                    $result=array_column_diy($result,$field);
	                }
	                break;
	            }
	        }
	    }
	    return $result;
	}
	
	/**
	 * 获取评分标准模板信息
	 * @param int $id
	 * @param string $field
	 * @return array|mixed|string
	 */
	function get_analysiytemplate_info($id=0,$field=''){
	    $id=intval($id);
	    $id=$id>0?$id:0;
	    $result=S('lxw_get_user_analysiy_template_info_'.$id);
	    if(empty($result)){
	        $map=array();
	        $map['id']=$id;
	        $result=M('analysiy_template')->where($map)->find();
	        if(!empty($result)){
	            S('lxw_get_user_analysiy_template_info_'.$id,$result,30*24*60*60);
	        }else{
	            $result=array();
	        }
	    }
	    if(!empty($result)&&isset($result[$field])){
	        $result=$result[$field]?$result[$field]:'';
	    }
	    return $result;
	}
	/**
	 *获取学校信息
	 */
	function get_schools_info($id=0,$field=''){
	    $field_list='id,analysiy_template_id,name_cn,name_en,country,auth,lat,lon,gw_url,student_num,std_state_num,std_state_ratio,gjhcd,status';
	    if(!empty($id)&&is_numeric($id)&&$id>0){
	        $id=intval($id);
	        $result=S('lxw_get_schools_info_'.$id);
	        if(empty($result)){
	            $map=array();
	            $map['status']=1;
	            $map['id']=$id;
	            $result=M('schools')->where($map)->field($field_list)->find();
	            if(!empty($result)){
	                S('lxw_get_schools_info_'.$id,$result,30*24*60*60);
	            }else{
	                $result=array();
	            }
	        }
	    }elseif(!empty($id)&&!is_numeric($id)&&is_string($id)){
	        $id=trim($id);
	        $map=array();
	        $map['name_en']=$id;
	        $where['_string']="name_en_alias='".$id."' or name_en_alias like '%,".$id."' or name_en_alias like '".$id.",%' or name_en_alias like '%,".$id.",%'";
	        $result=M('schools')->where($map)->field($field_list)->find();
	        if(empty($result)){
	            $result=array();
	        }
	    }else{
	        if(!empty($field)){
	            return '';
	        }else{
	            return array();
	        }
	    }
	    if(!empty($result)){
	        $analysiy_template_id=get_country_info($result['country'],'analysiy_template_id');
	        if($result['analysiy_template_id']>0&&$result['analysiy_template_id']!=$analysiy_template_id){
	            $analysiy_template_id=$result['analysiy_template_id'];
	        }
	        $result['analysiy_template']=D('Lxkus/AnalysiyTemplateContent')->get_list($analysiy_template_id,1,'');
	    }
	    if(!empty($result)&&!empty($field)){
	        if(isset($result[$field])){
	            $result=$result[$field];
	        }else{
	            $result='';
	        }
	    }
	    return $result;
	}
	/**
	 * 获取课程信息
	 * @param int $id
	 * @param string $field
	 * @return array|mixed|string
	 */
	function get_curriculum_info($id=0,$field=''){
	    $id=intval($id);
	    $id=$id>0?$id:0;
	    $result=S('lxw_get_curriculum_info_'.$id);
	    if(empty($result)){
	        $map=array();
	        $map['id']=$id;
	        $result=M('curriculum')->where($map)->find();
	        if(!empty($result)){
	            S('lxw_get_curriculum_info_'.$id,$result,30*24*60*60);
	        }else{
	            $result=array();
	        }
	    }
	    if(!empty($result)){
	        if(isset($result[$field])){
	            $result=$result[$field]?$result[$field]:'';
	        }else{
	            $result='';
	        }
	    }
	    return $result;
	}

}
