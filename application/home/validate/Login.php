<?php
namespace app\home\validate;

use think\Validate;

class Login extends Validate{
	
	protected $rule=[
		//'name'=>'require|max:25',
		'email' => 'email|require',
        //'id_number'=>'require|max:12',
        'password'=>'require|min:6|max:30',
		'__token__' => 'token',
        //'passport'=>'require|max:12',
	];
	
	
	
}


?>