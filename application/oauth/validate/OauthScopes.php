<?php
namespace app\oauth\validate;

use think\Validate;

class OauthScopes extends Validate
{
    protected $rule = [
        'scope'   => 'require',
        'is_default' => 'in:0,1',
        'open_type'  => 'in:1,2,3',
        'model'  => 'require',
    ];

    protected $message = [
        'scope.require'   => '{%error_10000029}',
        'is_default.in'   => '{%error_10000030}',
        'open_type.in'   => '{%error_10000031}',
        'model.require' => '{%error_10000032}',
    ];
    protected $scene = [
        'edit'  =>  ['scope','is_default','open_type','model'],
    ];
}