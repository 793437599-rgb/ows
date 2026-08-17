<?php
namespace app\oauth\validate;

use think\Validate;

class OauthUsers extends Validate
{
    protected $rule = [
        'username'   => 'require',
        'password' => 'require',
        'first_name'  => 'require',
        'last_name'  => 'require',
        'email'  => 'email',
        'email_verified'  => 'in:1,2',
        'scope'  => 'require',
    ];

    protected $message = [
        'username.require'   => '{%error_10000042}',
        'password.require' => '{%error_10000043}',
        'first_name.require'  => '{%error_10000044}',
        'last_name.require'   => '{%error_10000045}',
        'email.email'   => '{%error_10000046}',
        'email_verified.in'   => '{%error_10000047}',
        'scope.require'   => '{%error_10000048}',
    ];
    protected $scene = [
        'edit'  =>  ['username','email','email_verified','scope'],
    ];
}