<?php
namespace app\oauth\validate;

use think\Validate;

class OauthClientUsers extends Validate
{
    protected $rule = [
        'client_id'   => 'require',
        'openid' => 'require',
        'user'  => 'require',
        'userinfo'  => 'require',
        'status'  => 'in:1,2',
    ];

    protected $message = [
        'client_id.require'   => '{%error_10000001}',
        'openid.require' => '{%error_10000020}',
        'user.require'  => '{%error_10000021}',
        'userinfo.require'   => '{%error_10000022}',
        'status.in'   => '{%error_10000023}',
    ];
    protected $scene = [
        'add'  =>  ['client_id','openid','user','user','userinfo','status'],
        'edit'  =>  ['client_id','openid','user','user','userinfo','status'],
    ];
}