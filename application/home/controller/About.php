<?php
namespace app\home\controller;

use app\common\controller\NewBase;
use app\common\model\User as UserModel;

class About extends NewBase
{
    public function index()
    {
        return $this->fetch();
    }

    public function questionnaire($id)
    {
        $userModel = new UserModel();
        $user = $userModel->hidden(['password'])->find($id);
        $nationality = Db::name('country')->where('id',$user->nationality)->value('cname');
        $edu = Db::name('edu')->where('u_id',$id)->find();
        $messages = Db::name('message')->where('user_id',$id)->select();
        $major = Db::name('major')->where('id',$edu['major'])->value('title');
        foreach ($messages as $key => &$val){
            if ($val['is_reply'] == 1){
                $val['head_img'] = $user['head_img'];
            }else {
                $val['head_img'] = '/canada/images/admin_head_img.png';
            }
            $val['message'] = htmlspecialchars_decode($val['message']);
        }
        $this->assign(compact('id','user','nationality','edu','messages','major'));
        return $this->fetch('questionnaire');
    }
}
