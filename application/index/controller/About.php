<?php

namespace app\index\controller;

use app\common\controller\HomeBase;
use app\common\controller\UploadFile;
use app\common\model\User as UserModel;
use think\Db;
use think\Session;

class About extends HomeBase
{
    public function index()
    {
        return $this->fetch();
    }

    public function questionnaire()
    {
        if (!Session::has('user_id')) {
            $this->redirect('/index/user/log');
        }
        $id = Session::get('user_id');

        $userModel = new UserModel();
        $user = $userModel->hidden(['password'])->find($id);
        $messages = Db::name('message')->where('user_id', $id)->select();
     
        Db::name('message')->where(['user_id' => $id, 'send_id' => 0])->setField('is_read', 2);
        foreach ($messages as $key => &$val) {
            if ($val['is_reply'] == 1) {
                $val['head_img'] = $user['head_img'];
            } else {
                $val['head_img'] = '/canada/images/admin_head_img.png';
            }
            $val['message'] = htmlspecialchars_decode($val['message']);
        }
        $name = getNameLang($this->lang);
        $applications = Db::name('applications')->field("*, {$name} as name")->where('status', 1)->select(); // 所有应用数据
        $this->applications = $applications;
        $this->assign(compact('id', 'user',   'messages', 'applications'));
        return $this->fetch('quests');
    }

    public function sendMessage()
    {
        $data = input('post.');
        $message_main = [];
        $message_main['user_id'] = $data['user_id'];
        $message_main['send_id'] = $data['user_id'];
        $userinfo = Db::name('user')->where('id', $data['user_id'])->find();
        $message_main['receive_id'] = 0;
        $message_main['sender'] = $userinfo['username'];
        $message_main['is_reply'] = 1;
        $message_main['message'] = $data['content'];
        $message_main['receiver'] = 'WSE';
        $message_main['reply_id'] = 0;
        $message_main['create_time'] = time();
        $result = Db::name('message')->insert($message_main);
        if ($result) {
            $list = Db::name('message')->where('user_id', $data['user_id'])->select();
            return json(['code' => 1, 'msg' => '发送成功', 'data' => $list]);
        }
        return json(['code' => 2, 'msg' => '发送失败']);
    }

    public function localUpload()
    {
        $fileUpload = new UploadFile($ext = 'jpg,png,gif', $field = 'file', $path = './uploads/images');
        $result = $fileUpload->localUpload();
        if ($result['result_code'] == false) {
            return json(['code' => 4001, 'msg' => $result['msg']]);
        }
        return json(['code' => 0, 'msg' => '上传成功', 'data' => ['src' => trim($result['path'], '.'), 'title' => '聊天图片']]);
    }

    /**
     *  关于WSE页面
     */
    public function AboutWSE()
    {
        return $this->fetch('AboutWSE');
    }

    public function CredentialEvaluation()
    {
        return $this->fetch('CredentialEvaluation');
    }

    public function Plans()
    {
        return $this->fetch('Plans');
    }

    public function StandardsProfession()
    {
        return $this->fetch('StandardsProfession');
    }

    public function PastLeadership()
    {
        return $this->fetch('PastLeadership');
    }

    public function BoardOfDirectors()
    {
        return $this->fetch('BoardOfDirectors');
    }

    public function CharterMembers()
    {
        return $this->fetch('CharterMembers');
    }

    public function PressReleases()
    {
        return $this->fetch('PressReleases');
    }

    public function FAQ()
    {
        return $this->fetch('FAQ');
    }

    public function assessment()
    {
        return $this->fetch();
    }

    public function dae()
    {
        return $this->fetch('DAE');
    }

    public function degree()
    {
        return $this->fetch();
    }

    public function requestadditional()
    {
        return $this->fetch('RequestAdditional');
    }

    public function servicefees()
    {
        return $this->fetch('ServiceFees');
    }

    public function institutional()
    {
        return $this->fetch();
    }

    public function blockchain()
    {   
        return $this->fetch('blockchain');
    }

    public function newsDetails()
    {
        return $this->fetch('newsDetails');
    }

    public function newsDetails2()
    {
        return $this->fetch('newsDetails2');
    }

    public function newsDetails3()
    {
        return $this->fetch('newsDetails3');
    }

    public function newsDetails4()
    {
        return $this->fetch('newsDetails4');
    }

    public function newsDetails5()
    {
        return $this->fetch('newsDetails5');
    }
    
    public function newsDetails6()
    {
        return $this->fetch('newsDetails6');
    }

    public function newsinfo($id=1)
    {
        $nav =  Db::name('news')->where('id',$id)->find($id);
        if ($nav) {
            //$nav['content'] = htmlspecialchars( $nav['content']);
        }
        return $this->fetch('newsinfo', ['nav' => $nav]);
    }

    public function problemcenter()
    {
        $questionGroups =  model('common/QuestionCategoty')->with(['questions'=>function($query){
            return $query->where('status',1);
        }])->where('status',1)->order('sort desc')->select();
        $this->assign(['questionGroups'=>$questionGroups]);
        return $this->fetch('problemcenter');
    }

    public function blockchainstorage()
    {
        return $this->fetch('blockchainstorage');
    }

    public function payinfo()
    {
        $name = getNameLang($this->lang);
        $user_id = Session::get('user_id');
        $user = UserModel::get($user_id);
        $order = model('UserOrder')->where(['user_id' => $user_id, 'status' => ['gt', 2]])->select();
        $app = Db::name('applications')->field("*,{$name} as name")->where('status', 1)->select();
        $apply_ins = model('Applyins')->where(['user_id' => $user_id])->select();
        $apply_apis = model('Apiapply')->where(['user_id' => $user_id])->select();
        return $this->fetch('payinfo', ['applications' => $app, 'user' => $user, 'order' => $order, 'apply_ins' => $apply_ins, 'apply_apis' => $apply_apis]);
    }


    public function helpcenter()
    {
        $name = getNameLang($this->lang);
        $app = Db::name('applications')->field("*,{$name} as name")->where('status', 1)->select();
        $user_id = Session::get('user_id');
        if (!empty($user_id)) {
            $result = UserModel::get($user_id);
        } else {
            $this->redirect('/index/user/log');
        }
        $questionGroups =  model('common/QuestionCategoty')->with(['questions'=>function($query){
            return $query->where('status',1);
        }])->where('status',1)->order('sort desc')->select();
        return $this->fetch('helpcenter', ['applications' => $app, 'user' => $result,'questionGroups'=>$questionGroups]);
    }

    public function report()
    {
        if ($this->request->isPost()) {
            $data = $this->request->param();
            $data['create_time'] = date('Y-m-d H:i:s');  //date('Y-m-d H:i:s')
            $data['evidence']  = empty($data['evidence']) ? '' : serialize($data['evidence']);
            $data['ip'] = $this->get_ip();
            $result = model('Report')->allowField(true)->save($data);
            if (!$result) {
                return status_code(10005, 'Submitted failed');
            }
            return status_code(20000, 'Submitted successfully');
        }
        return $this->fetch('report');
    }

    public function secure($case)
    { 
        $nav =  Db::name('ssl')->where('case',$case)->find( );

        if ($nav) {
            $nav['content'] = htmlspecialchars_decode( $nav['content']);
        }
        
        return $this->fetch('secure', ['new' => $nav]);
    }
    
}
