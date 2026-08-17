<?php
namespace app\home\controller;

use app\common\controller\NewBase;
use app\common\model\Ku as KuModel;
use app\common\model\Edu as EduModel;
use app\common\model\Score as ScoreModel;
use app\common\model\Category as CategoryModel;
use think\Db;
use think\Session;
use think\Request;
use think\Validate;

use aliphone\AliyunSmsDemo;
use aliphone\appfunction;

use phpmailer\PHPMailer;
use phpmailer\SMTP;


class Ku extends NewBase
{
    public function user_upload($uid='',$edu_id=''){
    	$ku = kuModel::get($uid)->toArray();
    	
    	$edu = EduModel::get($edu_id);
    	$user_upload = Db::name('user_upload')->where('uid',$uid)->find();
    	if(!empty($user_upload)){
        	$user_upload['sfz_img']=unserialize($user_upload['sfz_img']);
	        $user_upload['xwz_img']=unserialize($user_upload['xwz_img']);
	        $user_upload['sc_xwz_img']=unserialize($user_upload['sc_xwz_img']);
	        $user_upload['sm_img']=unserialize($user_upload['sm_img']);
	        $user_upload['hz_img']=unserialize($user_upload['hz_img']);
	        $user_upload['sc_hz_img']=unserialize($user_upload['sc_hz_img']);
	        $user_upload['sc_crj_img']=unserialize($user_upload['sc_crj_img']);
	        $user_upload['cj_img']=unserialize($user_upload['cj_img']);
	        $user_upload['sc_cj_img']=unserialize($user_upload['sc_cj_img']);
        }
    	$this->view->engine->layout('../themes/canada/empty.html');
    	
    	return $this->fetch('user_upload',[
    	'info'=>$ku,
    	'edu'=>$edu,
    	'user_upload'=>$user_upload,
    	]);
    	
    }
    public function add_upload(){
    	
    	if ($this->request->isPost()) {
    		$data = $this->request->param();
    		//去重
    		if(empty($data['sfz_img'])){
				echo "<script>alert('未上传身份证！');window.history.back(-1);</script>";
				exit;
			}else{
				$data['sfz_img']= serialize(array_unique($data['sfz_img'], SORT_REGULAR));
			}
			
			if(empty($data['xwz_img'])){
				echo "<script>alert('未上传学位证书！');window.history.back(-1);</script>";
				exit;
			}else{
				$data['xwz_img']= serialize(array_unique($data['xwz_img'], SORT_REGULAR));
			}
			if(empty($data['cj_img'])){
				echo "<script>alert('未上传成绩单！');window.history.back(-1);</script>";
				exit;
			}else{
				$data['cj_img']= serialize(array_unique($data['cj_img'], SORT_REGULAR));
			}
			
			if(empty($data['zj_img'])){
				echo "<script>alert('未上传护照签证！');window.history.back(-1);</script>";
				exit;
			}
			$user_id = Session::get('user_id');

            $request_up = Db::name('user_upload')->where('uid',$user_id)->find();

			unset($data['file']);

			//dump($data);exit;
			if($request_up){
                //非空更新
                $result = Db::name('user_upload')->where('uid',$user_id)->fetchSql()->update($data);
                if($result===false){
                    $this->error('save error');
                }
            }else{
            	//空则添加
                $data['uid'] = $user_id;
                $result = Db::name('user_upload')->insert($data);
                if(!$result){
                    $this->error('save error');
                }
            }
            $this->success('success','/user_sign');

    	}
    }
    

    public function index()
    {
        $this->view->engine->layout('../themes/canada/empty.html');
        return $this->fetch();
    }
	
	public function add(){
//		$code = rand(100000, 999999);
//		$this->assign('code',$code);
		$country = Db::name('country')->order('name','asc')->column('name');
		
		return $this->fetch('register',['country'=>$country]);
	}
	public function log(){
		
		return $this->fetch('login');
	}
	
	public function phone_code(){
		
		$post = Request::instance()->post();
	   	dump($post);
	   	$code=rand(100000,999999);
	   	
	   	Session::set('code',$code);
	   	
	   	$AliyunSmsDemo = new AliyunSmsDemo();
	   	
	   	$return = $AliyunSmsDemo->sendSms($post['phone'],array('code'=>$code),'SMS_163490781');
	   	
	   	dump($return);
	}
	
	public function upload(){
		$file=request()->file('file');
		
		$info = $file->move(ROOT_PATH . 'public' . DS . 'uploads');
		//$info = $file->move(ROOT_PATH . 'public' . DS . 'uploads'.DS.date("Ymd"), md5( date('YmdHis')));
		if($info){
            // 成功上传后 获取上传信息
            $url= DS . 'uploads'.DS.$info->getSaveName();
            
            $url=str_replace('\\','/',$url);
            
			$this->success($url);
        }else{
            // 上传失败获取错误信息
            echo $file->getError();
        }
	}
	public function uploads(){
		$files=request()->file('file');
			// 移动到框架应用根目录/public/uploads/ 目录下
			$info = $files->move(ROOT_PATH . 'public' . DS . 'uploads');
			if($info){
				// 成功上传后 获取上传信息
				$url= DS . 'uploads'.DS.$info->getSaveName();
            
	            $url=str_replace('\\','/',$url);
				$this->success($url);
			}else{
				// 上传失败获取错误信息
				echo $files->getError();
			}
		//}
	}

    public function unlink(){
        if($_POST){
            $del = unlink('../public'.$_POST['delete']);
            if ($del) {
                $this->success($_POST['delete']);
            }else{
                $this->error('error');
            }
        }
    }
	public function create(){
		if ($this->request->isPost()) {
            $data = $this->request->param();
            $data['create_time']=date('Y-m-d H:i:s');
            //dump($data);exit;
            $data['username']=$data['firstname'].' '.$data['middlename'].' '.$data['lastname'];
            //验证
            $result = $this->validate($data,'User');
            if($result!==true){
            	$this->error($result);
            	//return $result;
            }
            
            $data['create_ip']=$this->get_ip();
            $data['password']=md5($data['password']);
            //验证完成后，销毁额外数据
            unset($data['__token__'],$data['firstname'],$data['middlename'],$data['lastname'],$data['checkbox']);
            $result = Db::name('user')->where('email', $data['email'])->whereOr('username',$data['username'])->find();

            if($result){
            	$this->error('Failed to register, mailbox already occupied or username duplicate！');
            
            }else{
            	$create = Db::name('user')->insert($data);
                 $u_id =  Db::name('user')->getLastInsID();

	            if ($create) {
                    Session::set('email', $data['email']);
	            	Session::set('u_id', $u_id);
	            	$this->email();
	            	$this->success('success','/add',$u_id);
//	            	return 'success';
	            } else {
	            	$this->error('Failed to register, mailbox already occupied！');
	            	//return '注册失败,邮箱已被占用！';
	            }
            }
        }
	}
	//注册信息填错，需要修改
	public function update_create(){
		
		if($this->request->isPost()){
			
			$data = $this->request->param();
            $data['update_time']=date('Y-m-d H:i:s');
            $data['update_ip']=$this->get_ip();
            //dump($data);exit;
            $data['username']=$data['firstname'].' '.$data['middlename'].' '.$data['lastname'];
            //验证
            $result = $this->validate($data,'User');
            
            if($result!==true){
            	$this->error($result);
            }
            //$this->error('123！');exit;
            $data['password']=md5($data['password']);
            //验证完成后，销毁额外数据
            unset($data['__token__'],$data['firstname'],$data['middlename'],$data['lastname'],$data['checkbox']);
            //更新
            $result = Db::name('user')->where('email', $data['email'])->where('status',0)->update($data);
            
            //return dump($result);
            //dump($result);exit;
            if($result===false){
            	Session::set('email', $data['email']);
            	$this->email();
            	
            	$this->success('success');
            }else{
            	//return '更新失败！';
            	 $this->error('更新失败！');
            }
		}
		
		$email = Session::get('u_id');
		//echo $email;
		$result = Db::name('user')->where('id', $email)->find();
		if($result){
			$country = Db::name('country')->order('name','asc')->column('name');
			
			//拆分用户名
			$username = explode(" ",$result['username']);
			$result['firstname'] = $username[0];
			$result['middlename'] = $username[1];
			$result['lastname'] = $username[2];
			//dump($result);
			return $this -> fetch('up_user',[
				'user'=>$result,
				'country'=>$country
			]);
		}
	}
	//注册成功之后的跳转，提示邮箱激活
	public function register_verification(){
		return $this->fetch('register_verification');
	}
	//忘记密码
	public function forget_pass(){
        if($this->request->isPost()) {
            $email = input("post.email");
            $token = request()->session('__token__');
            if (empty($email)) {
                $this->error('邮箱不能为空');
            }
            if (!empty($email) && $token = $_POST['__token__']) {
//                $this->error($token . '----' . $_POST['__token__']);
//                die;
                $result = Db::name('user')->where('email', $email)->find();

                if ($result) {

                    Session::set('email', $email);
                    //先发送修改验证，跳转输入新密码页reset_pass
                    $this->email2();

                    $this->success('success', '/register_success');
                } else {
                    $this->error('该邮箱账号尚未被使用');
                }
            }
        }
		return $this->fetch();
	}
	
	//忘记密码后的验证
	public function register_success(){
			return $this->fetch();
	}
	
	//忘记密码后，更换密码
	public function reset_pass(){
		return $this->fetch();
	}
	
	//更换密码 0e7375d41646a8b44569f55256c35dd5
	public function up_pass(){
		$pass = $_POST['password'];
		$token = request()->session('__token__');
		if (empty($pass)){
		    $this->error('The password can not be empty');
        }
        if (!preg_match('/^[\S]{6,12}$/',$pass)){
            $this->error('密码必须6到12位，且不能出现空格');
        }
		if(!empty($pass) && $token=$_POST['__token__']){
		
			$email=Session::get('email');
            $this->error($email);
			//echo $email;exit;
			$data['password'] = md5($pass);
			$data['update_ip'] = $this->get_ip();
			$data['update_time']=date('Y-m-d H:i:s');
			
			$result = Db::name('user')->where('email',$email)->update($data);
			if($result){
				$this->success('success','/log');
			}else{
				$this->error('error!修改失败');
			}
		}
	}
	

    public function login()
    {
    	if($this->request->isPost()){
    		$data = $this ->request->param();
    		
    		$validate_result = $this->validate($data,'Login');

    		if($validate_result===true){
    			$data['password']=md5($data['password']);
    			$result=Db::name('user')->where('email',$data['email'])->where('password',$data['password'])->where('status_info','<',2)->find();
                //$this->error($data);
    			if($result){
    				Session::set('user_id',$result['id']);
    				
    				$login['last_login_time']=date('Y-m-d H:i:s');
    				$login['last_login_ip']=$this->get_ip();
    				//登录成功更新IP及时间
    				Db::name('user')->where('email',$data['email'])->where('password',$data['password'])->update($login);
    				$this->success('Login success','/user_index');
    				
    			}else{
    				$this->error('Login failed, the account password is incorrect');
    			}
    		}else{
    			$this->error($validate_result);
    		}
    	}
    }
    //登录后的页面
    public function user_index(){
    	//$this->view->engine->layout('../themes/canada/empty.html');
        $user_id = 39;
        Session::set('user_id',$user_id);
    	//$user_id = Session::get('user_id');
    	//echo $user_id;
    	$result = Db::name('user')->where('id',$user_id)->find();
    	
    	if($result){
    		//拆分用户名
//  		$username = explode(" ",$result['username']);
//			$result['firstname'] = $username[0];
//			$result['middlename'] = $username[1];
//			$result['lastname'] = $username[2];
			//若无申请信息
            $edu = Db::name('edu')->where('u_id',$user_id)->find();
			if($edu['status']==1){
                //若有申请信息，跳转审核结果
                $user_upload = Db::name('user_upload')->where('uid',$user_id)->find();
                return $this->fetch('apply_status',['user'=>$result,'edu'=>$edu,'user_upload'=>$user_upload]);
			}else{
                return $this->fetch('user_index',['user'=>$result]);
			}
    	}else{
    		$this->redirect('/log');
    	}
    }
    //用户申请，填写信息
    public function apply_info(){
    	$user_id = Session::get('user_id');
    	
    	$result = Db::name('user')->where('id',$user_id)->find();
    	
    	if($result){
			//若未审核申请信息
			if($result['status_info']==0){
				
				$schools = Db::name('cs_schools')->where('gj','加拿大')->order('name_en','asc')->column('name_en');
				
				$country = Db::name('country')->order('name','asc')->column('name');
				
				$edulist = Db::name('edu')->where('u_id',$user_id)->find();
				//dump($schools);exit;
				return $this->fetch('apply_info',['user'=>$result,'schools'=>$schools,'country'=>$country,'edu'=>$edulist]);
			}else{
				//若已审核申请信息，跳转审核结果
				return $this->redirect('/apply_status');
			}
    	}else{
    		$this->redirect('/log');
    	}
    }
    //用户申请信息提交
    public function apply_edu(){
    	$user_id = Session::get('user_id');
    	
    	if(empty($user_id)){
    		$this->redirect('/log');
    	}
    	
    	if($this->request->isPost()){
    		$data = $this->request->param();
    		
    		$edulist = Db::name('edu')->where('u_id',$user_id)->find();
    		
    		if(!empty($edulist)){
    			//$this->error('123');exit;
    			$data['update_time']=date('Y-m-d H:i:s');
    			$data['update_ip'] = $this->get_ip();
    			$result = Db::name('edu')->where('u_id',$user_id)->update($data);
    		}else{
    			//$this->error('456');exit;
    			$data['u_id']=$user_id;
    			$data['create_time']=date('Y-m-d H:i:s');
    			$data['create_ip'] = $this->get_ip();
    			$result = Db::name('edu')->insert($data);
    		}
    		
    		
    		if($result){
    			$this->success('save success','/apply_upload');
    		}else{
    			$this->error('save error','/apply_info');
    		}
    	}
    }
    //用户提交证件信息页面
    public function apply_upload(){
    	$user_id = Session::get('user_id');
    	
    	if(empty($user_id)){
    		$this->redirect('/log');
    	}
    	$user = Db::name('user')->where('id',$user_id)->find();
    	
    	$user_upload = Db::name('user_upload')->where('uid',$user_id)->find();
    	if(!empty($user_upload)){
        	$user_upload['sfz_img']=unserialize($user_upload['sfz_img']);
	        $user_upload['xwz_img']=unserialize($user_upload['xwz_img']);
	        $user_upload['sc_xwz_img']=unserialize($user_upload['sc_xwz_img']);
	        $user_upload['sm_img']=unserialize($user_upload['sm_img']);
	        $user_upload['hz_img']=unserialize($user_upload['hz_img']);
	        $user_upload['sc_hz_img']=unserialize($user_upload['sc_hz_img']);
	        $user_upload['sc_crj_img']=unserialize($user_upload['sc_crj_img']);
	        $user_upload['cj_img']=unserialize($user_upload['cj_img']);
	        $user_upload['sc_cj_img']=unserialize($user_upload['sc_cj_img']);
        }
    	return $this->fetch('apply_upload',['user'=>$user,'user_upload'=>$user_upload]);
    }
    
    //用户信息
    public function user_basic(){
    	$user_id = Session::get('user_id');
    	
    	//echo $user_id;
    	$result = Db::name('user')->where('id',$user_id)->find();
    	
    	if($result){
    		$country = Db::name('country')->order('name','asc')->column('name');
    		//拆分用户名
    		$username = explode(" ",$result['username']);
			$result['firstname'] = $username[0];
			$result['middlename'] = $username[1];
			$result['lastname'] = $username[2];
			
    		return $this->fetch('user_basic',['user'=>$result,'country'=>$country]);
    	}else{
    		$this->redirect('/log');
    	}
    	
    }
    public function user_sign(){
        $user_id = Session::get('user_id');
        $result = Db::name('user')->where('id',$user_id)->find();
        $user_upload = Db::name('user_upload')->where('uid',$user_id)->find();
        if($result&&$user_upload){

            if($this->request->isPost()){
                $data = $this->request->param();
                $up_dir = ROOT_PATH.'public' . DS . 'uploads'.DS.'sign'.DS;//存放在当前目录的upload文件夹下
                $base64_img = trim($data['sign_img']);
                $data_sign = time().'.'.'png';
                $new_file = $up_dir.$data_sign;

                $info = file_put_contents($new_file, base64_decode(str_replace('data:image/png;base64', '', $base64_img)));

                if ($info){
                    $data['sign_img'] =  '/uploads/sign/'.$data_sign;
                    $data['sign_time']=date('Y-m-d H:i:s');
                    $data['sign_ip'] = $this->get_ip();
                    unset($data['checkbox']);
                    $user_upload = Db::name('user_upload')->where('uid',$user_id)->update($data);

                    if ($user_upload){
                        $this->success('Sign success','/sign_doc');
                    }else{
                        $this->error('error');
                    }

                }else{
                    $this->error('error');
                }
            }else{
                return $this->fetch('apply_sign',['user'=>$result,'personal'=>$user_upload]);
            }
        }else{
            $this->redirect('/log');
        }
    }
    public  function  sign_doc(){
        $user_id = Session::get('user_id');
        $result = Db::name('user')->where('id',$user_id)->find();

        if($result){
            $edu = Db::name('edu')->where('u_id',$user_id)->find();
            $user_upload = Db::name('user_upload')->where('uid',$user_id)->find();
            if($edu&&$user_upload){
                $user_upload['sfz_img']=unserialize($user_upload['sfz_img']);
                $user_upload['xwz_img']=unserialize($user_upload['xwz_img']);

                $user_upload['cj_img']=unserialize($user_upload['cj_img']);
                return $this->fetch('sign_doc',['user'=>$result,'edu'=>$edu,'user_upload'=>$user_upload]);
            }else{
                $this->redirect('/user_index');
            }
        }else{
            $this->redirect('/log');
        }
    }
    public function apply_status(){
        $user_id = Session::get('user_id');
        $result = Db::name('user')->where('id',$user_id)->find();
        if($result){
            $edu = Db::name('edu')->where('u_id',$user_id)->find();
            $user_upload = Db::name('user_upload')->where('uid',$user_id)->find();
            return $this->fetch('apply_status',['user'=>$result,'edu'=>$edu,'user_upload'=>$user_upload]);
        }else{
            $this->redirect('/log');
        }
    }
    public function  verification_agency(){
        $user_id = Session::get('user_id');
        $result = Db::name('user')->where('id',$user_id)->find();
        if($result){
            $edu = Db::name('edu')->where('u_id',$user_id)->find();
            $user_upload = Db::name('user_upload')->where('uid',$user_id)->find();

            $user_upload['xwz_img']=unserialize($user_upload['xwz_img']);

            $user_upload['cj_img']=unserialize($user_upload['cj_img']);
            return $this->fetch('verification_agency',['user'=>$result,'edu'=>$edu,'user_upload'=>$user_upload]);
        }else{
            $this->redirect('/log');
        }
    }
    public function pay_details(){
        $user_id = Session::get('user_id');
        $result = Db::name('user')->where('id',$user_id)->find();
        if($result){
            $edu = Db::name('edu')->where('u_id',$user_id)->find();
            $user_upload = Db::name('user_upload')->where('uid',$user_id)->find();
            $user_upload['sfz_img']=unserialize($user_upload['sfz_img']);
            $user_upload['xwz_img']=unserialize($user_upload['xwz_img']);

            $user_upload['cj_img']=unserialize($user_upload['cj_img']);
            return $this->fetch('pay_details',['user'=>$result,'edu'=>$edu,'user_upload'=>$user_upload]);
        }else{
            $this->redirect('/log');
        }
    }
    public function verification_trans(){
        $user_id = Session::get('user_id');
        $result = Db::name('user')->where('id',$user_id)->find();
        if($result){
            $edu = Db::name('edu')->where('u_id',$user_id)->find();
            $user_upload = Db::name('user_upload')->where('uid',$user_id)->find();
            $user_upload['sfz_img']=unserialize($user_upload['sfz_img']);
            $user_upload['xwz_img']=unserialize($user_upload['xwz_img']);

            $user_upload['cj_img']=unserialize($user_upload['cj_img']);
            return $this->fetch('verification_trans',['user'=>$result,'edu'=>$edu,'user_upload'=>$user_upload]);
        }else{
            $this->redirect('/log');
        }
    }
    //登录后修改信息
    public function up_basic(){

        $user_id = Session::get('user_id');
    	if($this->request->isPost()){
			
			$data = $this->request->param();
            $data['update_time']=date('Y-m-d H:i:s');
            $data['update_ip']=$this->get_ip();
            //dump($data);exit;
            //验证
            if(!empty($data['firstname'])&&!empty($data['lastname'])){
            	$data['username']=$data['firstname'].' '.$data['middlename'].' '.$data['lastname'];
                //验证完成后，销毁额外数据
                unset($data['firstname'],$data['middlename'],$data['lastname']);
            }
            //$this->error('123！');exit;
            if(!empty($data['password'])){
            	$data['password']=md5($data['password']);
            }
            //更新
            $result = Db::name('user')->where('id',$user_id)->update($data);
            if($result){
            	$this->success('success');
            }else{
            	 $this->error($result);
            }
		}
    }
    
	//游客查询
    public function query($ku_id='',$edu_id = '')
    {
    	//查询操作
        if ($this->request->isPost()) {
            $data = $this->request->param();
            $data['firstname'] = trim($data['firstname']);
            $data['middlename'] = trim($data['middlename']);
            $data['lastname'] = trim($data['lastname']);
            $data['username']=$data['firstname'].' '.$data['middlename'].' '.$data['lastname'];
            if (!$data['firstname']) {
                $this->error('Please input firstname');
            }
            if (!$data['lastname']) {
                $this->error('Please input lastname');
            }
            //$info = Db::name('ku')->where('reference_id', $data['reference_id'])->where('name',$data['name'])->find();
            $info = Db::name('user')->where('username',$data['username'])
                ->where('search_id',$data['search_id'])
                ->where('status_info',1)
                ->find();
            //dump($info);exit;
            if ($info) {
                Session::set('user_id', $info['id']);

                $this->success('查询成功','/search_edu');
            } else {
                $this->error('查询失败');
            }
        }
    }
    public function search_edu(){
        $user_id=Session::get('user_id');
        if(empty($user_id)){
            $this->redirect('index');
        }
        //页面跳转
        $info = Db::name('user')->alias('u')
            ->join('country c','c.id=u.nationality')
            ->where('u.id', $user_id)
            ->field('u.*,c.name,c.cname')
            ->find();
        $edu = Db::name('edu')
            ->alias('e')
            ->join('cs_schools s','e.school=s.id')
            ->join('cs_subject s2','e.faculty=s2.id')
            ->where('e.u_id', $user_id)
            ->field('e.*,s.name_en,s.name_cn,s.clnf,s2.title_en')
            ->find();
        $edulist = EduModel::all(['u_id' => $info['id']]);
        $user_upload = Db::name('user_upload')->where('uid',$user_id)->find();
        //dump($edu);die;
        return $this->fetch('search_edu',[
            'info' => $info,
            'edu'=>$edu,
            'user_upload'=>$user_upload,
            'edulist'=>$edulist
        ]);
    }
    //游客查询成绩
    public function search_trans(){
        $user_id=Session::get('user_id');
    	if($user_id !=''){
    		
    		$info = Db::name('user')->where('id', $user_id)->find();
    		
	        $edu = Db::name('edu')->where('u_id', $user_id)->find();
	        
	        $edulist = EduModel::all(['ku_id' => $info['id']]);
	        
	        $scoreList = Db::name('score')->where('ku_id', $user_id)->where('edu_id', $edu['id'])->select();
            $user_upload = Db::name('user_upload')->where('uid',$user_id)->find();
	        //dump($scoreList);die;
	        $list = [];
	        if ($scoreList) {
	            foreach ($scoreList as $k => $v) {
	                $v['content'] = json_decode($v['content'], true);
	                $list[$v['years']][] = $v;
	            }
	        }
	        array_values($list); 
	        
	        return $this->fetch('search_trans',[
                'info' => $info,
                'edu'=>$edu,
                'user_upload'=>$user_upload,
                'edulist'=>$edulist,
                'scoreList' => $list
                ]);
    	}else{
    		$this->redirect('index');
    	}
    }
    //新闻专题列表查询
    public function search(){
    	$data=input('post.search');
    	$this->view->engine->layout('../themes/canada/empty.html');
    	$category = Db::name('category')->where('name','like','%'.$data.'%')->select();
    	//dump($category);exit;
    	if(empty($category)){
    		$this->error('Not searched!');
    	}else{
    		return $this->success('success','article_list',['category'=>$category]);
    	}
    }
    
    
    public function info($edu_id = '')
    {
        $id = Session::get('user_id');
        //echo $id . '<br/>';exit;
       $this -> assign('edu_id',$edu_id);
        if (!$id) {
            $this->redirect('/log');
        }
        
        $info = KuModel::get($id);
        
        if($info['status']==0){
        	
        	$result='资料已提交，请等待管理员审核...';
        	
        }elseif($info['pay_status']==0){
        	
        	$result='尚未付款，请先行付款...';
        	
        }elseif($info['pass_status']==0){
        	
        	$result = '很遗憾，您的申请被拒绝了。请更新资料后重新提交...';
        }else{
        	$result = '审核通过...';
        }
        $this->assign('result',$result);
        
        $edulist = EduModel::all(['ku_id' => $id]);
        
        if ($edulist) {
            foreach ($edulist as $k => $v) {
                $edulist[$k]['scorelist'] = ScoreModel::all(['edu_id' => $v['id']]);
            }
        }
        if ($edulist && !$edu_id) {
            $edu_id = $edulist[0]['id'];
        }
        $edu = EduModel::get($edu_id);
        
        $this->view->engine->layout('../themes/canada/empty.html');
        return $this->fetch('info', ['info' => $info, 'edulist' => $edulist, 'edu' => $edu]);
    }


    public function transfer()
    {
        $this->getInfo();
        $this->view->engine->layout('../themes/canada/empty.html');
        return $this->fetch('apply');
    }

    public function apply_select(){
        $this->getInfo();
        $this->view->engine->layout('../themes/canada/empty.html');
        return $this->fetch('apply_select');
    }

    public function apply_introduction(){
        //$this->getInfo();
        $this->view->engine->layout('../themes/canada/empty.html');
        return $this->fetch();
    }

    public function apply_how(){
        $this->view->engine->layout('../themes/canada/empty.html');
        return $this->fetch();
    }

    public function apply_college($school='cscss'){
        $this->getInfo();
        $this->view->engine->layout('../themes/canada/empty.html');
        return $this->fetch('apply_college_'.$school);
    }

    //退出登录
    public function logout()
    {
    	Session::clear('think');
        $this->redirect('/index');
    }

    protected function getInfo(){
        $id = Session::get('ku_id');
        if (!$id) {
        	//exit;
            $this->redirect('index');
        }
        $info = KuModel::get($id);
        $edulist = EduModel::all(['ku_id' => $id]);
        if ($edulist) {
            foreach ($edulist as $k => $v) {
                $edulist[$k]['scorelist'] = ScoreModel::all(['edu_id' => $v['id']]);
            }
        }
        $this->assign('info',$info);
        $this->assign('edulist',$edulist);
    }
    
	public function contract(){
		return $this->fetch('contract');
	}
	public function lang() {
	    switch ($_GET['lang']) {
	    	case 'en':
	            cookie('think_var', 'en-us');
	        break;
	        case 'cn':
	            cookie('think_var', 'zh-cn');
	        break;
	        case 'ja':
	            cookie('think_var', 'ja-jp');
	        break;
	        case 'de':
	            cookie('think_var', 'de-de');
	        break;
	        case 'ko':
	            cookie('think_var', 'ko-kr');
	        break;
	        case 'ru':
	            cookie('think_var', 'ru-ru');
	        break;
	        case 'fr':
	            cookie('think_var', 'fr-fa');
	        break;
	        //其它语言
	        default:
	        	cookie('think_var', 'en-us');
	    }
	}
	
	public function article_list() {
        $this->view->engine->layout('../themes/canada/empty.html');
        
        if(!empty($_GET['search'])){
        	$category = Db::name('category')->where('name','like','%'.$_GET['search'].'%')->select();
        	return $this ->fetch('article_list',['category'=>$category]);
        }
        return $this->fetch();
    }
	public function status($edu_id=''){
		$id = Session::get('user_id');
       $this -> assign('edu_id',$edu_id);
        if (!$id) {
            $this->redirect('index');
        }
        
        $info = KuModel::get($id);
        
        if($info['status']==0){
        	
        	$result='资料已提交，请等待管理员审核...';
        	
        }elseif($info['pay_status']==0){
        	
        	$result='尚未付款，请先行付款...';
        	
        }elseif($info['pass_status']==0){
        	
        	$result = '很遗憾，您的申请被拒绝了。请更新资料后重新提交...';
        }
        $this->assign('result',$result);
        return $this->fetch('status',['info'=>$info]);
	}
	//腾讯企业邮箱发送邮件验证码
    public function email(){
        //$email=input("post.email");//获取收件人邮箱
        $request = Request::instance();
        $domain=$request->domain();
        $email=Session::get('email');
       //edu@ca.university  data@ca.university
        //$sendmail = 'anna@cscss.com.cn'; //发件人邮箱
        $sendmail = 'edu@ca.university'; //发件人阿里云邮箱
        //$sendmailpswd = "VGQt8ekdApjv3fab"; //客户端授权密码,而不是邮箱的登录密码，就是手机发送短信之后弹出来的一长串的密码
        $sendmailpswd = "139490wX";
        $send_name = 'edu@ca.university';// 设置发件人信息，如邮件格式说明中的发件人，
        $toemail = $email;//定义收件人的邮箱
        //echo $toemail;exit;
        $to_name = 'visitor';//设置收件人信息，如邮件格式说明中的收件人
        $mail = new PHPMailer();
        $mail->isSMTP();// 使用SMTP服务
        $mail->CharSet = "utf8";// 编码格式为utf8，不设置编码的话，中文会出现乱码
        //$mail->Host = "smtp.exmail.qq.com";// 发送方的SMTP服务器地址
        $mail->Host = "smtp.qiye.aliyun.com";// 发送方阿里云的SMTP服务器地址
        $mail->SMTPAuth = true;// 是否使用身份验证
        $mail->isHTML=true; //是否为HTML格式
        $mail->Username = $sendmail;//// 发送方的
        $mail->Password = $sendmailpswd;//客户端授权密码,而不是邮箱的登录密码！
        $mail->SMTPSecure = "ssl";// 使用ssl协议方式
        $mail->Port = 465;//  qq端口465或587）
        $mail->setFrom($sendmail, $send_name);// 设置发件人信息，如邮件格式说明中的发件人，
        $mail->addAddress($toemail, $to_name);// 设置收件人信息，如邮件格式说明中的收件人，
        $mail->addReplyTo($sendmail, $send_name);// 设置回复人信息，指的是收件人收到邮件后，如果要回复，回复邮件将发送到的邮箱地址
        $mail->Subject = "账号激活";// 邮件标题

        $code=rand(100000,999999);
        //设置加密密令，以便显得高大上
        //echo $email.$code;exit;
        $key=md5($email.$code);
        //return $key;exit;
        Session::set('key',$key);
        //$mail->Body = "邮件内容是 <a href='https://www.baidu.com'>\n您的验证码是：$code</a>，如果非本人操作无需理会！";// 邮件正文
        $mail->Body = "欢迎加入WSE，请点击下方跳转链接，激活您的账号\n".$domain."/activate?key=$key&email=$email
        \n该链接60分钟内有效.\n如果您的邮箱不支持链接点击，请将以上链接地址拷贝到你的浏览器地址栏中认证";
        //$mail->AltBody = "This is the plain text纯文本";// 这个是设置纯文本方式显示的正文内容，如果不支持Html方式，就会用到这个，基本无用
        if (!$mail->send()) { // 发送邮件
            return "Mailer Error: " . $mail->ErrorInfo;// 输出错误信息
        } else {
        	//注册成功，跳转验证提示页面
        	return $this->fetch('register_verification');
            //return '注册成功，请前往邮箱激活';
        }
    }
    //重新设置密码
    public function email2(){
        //$email=input("post.email");//获取收件人邮箱
        
        $email=Session::get('email');
        $request = Request::instance();
        $domain=$request->domain();
        $sendmail = 'anna@cscss.com.cn'; //发件人邮箱
        $sendmail = 'edu@ca.university'; //发件人阿里云邮箱
        //$sendmailpswd = "VGQt8ekdApjv3fab"; //客户端授权密码,而不是邮箱的登录密码，就是手机发送短信之后弹出来的一长串的密码
        $sendmailpswd = "139490wX";
        $send_name = 'edu@ca.university';// 设置发件人信息，如邮件格式说明中的发件人，
        $toemail = $email;//定义收件人的邮箱
        //echo $toemail;exit;
        $to_name = 'visitor';//设置收件人信息，如邮件格式说明中的收件人
        $mail = new PHPMailer();
        $mail->isSMTP();// 使用SMTP服务
        $mail->CharSet = "utf8";// 编码格式为utf8，不设置编码的话，中文会出现乱码
        //$mail->Host = "smtp.exmail.qq.com";// 发送方的SMTP服务器地址
        $mail->Host = "smtp.qiye.aliyun.com";// 发送方阿里云的SMTP服务器地址
        $mail->SMTPAuth = true;// 是否使用身份验证
        $mail->isHTML=true; //是否为HTML格式
        $mail->Username = $sendmail;//// 发送方的
        $mail->Password = $sendmailpswd;//客户端授权密码,而不是邮箱的登录密码！
        $mail->SMTPSecure = "ssl";// 使用ssl协议方式
        $mail->Port = 465;//  qq端口465或587）
        $mail->setFrom($sendmail, $send_name);// 设置发件人信息，如邮件格式说明中的发件人，
        $mail->addAddress($toemail, $to_name);// 设置收件人信息，如邮件格式说明中的收件人，
        $mail->addReplyTo($sendmail, $send_name);// 设置回复人信息，指的是收件人收到邮件后，如果要回复，回复邮件将发送到的邮箱地址
        $mail->Subject = "密码重置";// 邮件标题

        $code=rand(100000,999999);
        //设置加密密令，以便显得高大上
        //echo $email.$code;exit;
        $key=md5($email.$code);
        //return $key;exit;
        Session::set('key',$key);
        //$mail->Body = "邮件内容是 <a href='https://www.baidu.com'>\n您的验证码是：$code</a>，如果非本人操作无需理会！";// 邮件正文
        $mail->Body = "欢迎回到WSE，请点击下方跳转链接，重置您的登录密码\n".$domain."/reset_pass?key=$key&email=$email
        \n该链接60分钟内有效.\n如果您的邮箱不支持链接点击，请将以上链接地址拷贝到你的浏览器地址栏中认证";
        //$mail->AltBody = "This is the plain text纯文本";// 这个是设置纯文本方式显示的正文内容，如果不支持Html方式，就会用到这个，基本无用
        if (!$mail->send()) { // 发送邮件
            return "Mailer Error: " . $mail->ErrorInfo;// 输出错误信息
        } else {
        	//注册成功，跳转验证提示页面
        	return $this->fetch('register_verification');
            //return '注册成功，请前往邮箱激活';
        }
    }
    
    //激活邮箱操作
    public function activate(){
    	
    	$key = input('get.key');
    	$email=input('get.email');
    	if($key==Session::get('key')){
    		//激活账号
    		 Db::name('user')->where('email',$email)->update(['status'=>1]);

    		    $init = 'Activate the success!';

                return $this->fetch('login',['init'=>$init]);

    	}else{
    		$this->error('激活失败,请重新激活','register_verification');
    	}
    }
    
    public function get_ip()
	{
	    if (isset($_SERVER["HTTP_X_REAL_IP"]))
	    {
	        return $_SERVER["HTTP_X_REAL_IP"];
	    }
	    else if (isset($_SERVER["HTTP_X_FORWARDED_FOR"]))
	    {
	        return preg_replace('/^.+,\s*/', '', $_SERVER["HTTP_X_FORWARDED_FOR"]);
	    }
	    else
	    {
	        return $_SERVER["REMOTE_ADDR"];
	    }
	}

//冲哲页面
    public function privacy_policy()
    {
        //$this->view->engine->layout('../themes/canada/ku/privacy_policy.html');
        return $this->fetch();
    }

    public function markets()
    {
        //$this->view->engine->layout('../themes/canada/ku/markets.html');
        return $this->fetch();
    }

    public function markets_k_12()
    {
        //$this->view->engine->layout('../themes/canada/ku/markets_k_12.html');
        return $this->fetch();
    }

    public function markets_k_12_send()
    {
        //$this->view->engine->layout('../themes/canada/ku/markets_k_12_send.html');
        return $this->fetch();
    }

    public function markets_k_12_scan()
    {
        //$this->view->engine->layout('../themes/canada/ku/markets_k_12_scan.html');
        return $this->fetch();
    }

    public function markets_higher_education()
    {
        //$this->view->engine->layout('../themes/canada/ku/markets_higher_education.html');
        return $this->fetch();
    }

    public function markets_higher_education_award()
    {
        //$this->view->engine->layout('../themes/canada/ku/markets_higher_education_award.html');
        return $this->fetch();
    }

    public function markets_higher_education_send()
    {
        //$this->view->engine->layout('../themes/canada/ku/markets_higher_education_send.html');
        return $this->fetch();
    }

    public function markets_higher_education_receive()
    {
        //$this->view->engine->layout('../themes/canada/ku/markets_higher_education_receive.html');
        return $this->fetch();
    }

    public function markets_other_organizations()
    {
        //$this->view->engine->layout('../themes/canada/ku/markets_other_organizations.html');
        return $this->fetch();
    }

    public function markets_other_organizations_receive()
    {
        //$this->view->engine->layout('../themes/canada/ku/markets_other_organizations_receive.html');
        return $this->fetch();
    }

    public function markets_other_organizations_award()
    {
        //$this->view->engine->layout('../themes/canada/ku/markets_other_organizations_award.html');
        return $this->fetch();
    }

    public function markets_state_agencies()
    {
        //$this->view->engine->layout('../themes/canada/ku/markets_state_agencies.html');
        return $this->fetch();
    }

    public function markets_state_agencies_ged()
    {
        //$this->view->engine->layout('../themes/canada/ku/markets_state_agencies_ged.html');
        return $this->fetch();
    }

    public function markets_learners()
    {
        //$this->view->engine->layout('../themes/canada/ku/markets_learners.html');
        return $this->fetch();
    }

    public function markets_training()
    {
        //$this->view->engine->layout('../themes/canada/ku/markets_training.html');
        return $this->fetch();
    }

    public function my_chances()
    {
        //$this->view->engine->layout('../themes/canada/ku/my_chances.html');
        return $this->fetch();
    }

    public function products()
    {
        //$this->view->engine->layout('../themes/canada/ku/products.html');
        return $this->fetch();
    }

    public function products_send()
    {
        //$this->view->engine->layout('../themes/canada/ku/products_send.html');
        return $this->fetch();
    }

    public function products_award()
    {
        //$this->view->engine->layout('../themes/canada/ku/products_award.html');
        return $this->fetch();
    }

    public function products_receive()
    {
        //$this->view->engine->layout('../themes/canada/ku/products_receive.html');
        return $this->fetch();
    }

    public function products_analyze()
    {
        //$this->view->engine->layout('../themes/canada/ku/products_analyze.html');
        return $this->fetch();
    }

    public function products_services()
    {
        //$this->view->engine->layout('../themes/canada/ku/products_services.html');
        return $this->fetch();
    }

    public function products_credential()
    {
        //$this->view->engine->layout('../themes/canada/ku/products_credential.html');
        return $this->fetch();
    }
}
