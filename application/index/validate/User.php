<?php

namespace app\index\validate;

use think\Validate;

class User extends Validate
{

    protected $rule = [
        //'name'=>'require|max:25',
        '__token__' => 'token',
        'email' => 'email|require|unique:user',
        'username' => 'require',
        'check' => 'require',
        //'id_number'=>'require|max:20',
        //'password'=>'require|max:18|alphaNum',
        //'reference_id'=>'number|require|length:11',
        //'code'=>'require|number|length:6',	//验证码
    ];

    protected $message = [
        'email.require' => "Mailbox can't be empty!",
        'email.email' => 'Mailbox format error!',
        'email.unique' => 'Mailbox already occupied!',
    ];
}