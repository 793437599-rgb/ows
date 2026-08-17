<?php
namespace app\index\validate;
use think\Validate;

class Edu extends validate{
	protected $rules = [
		'school_year'=>'require|number|between:2,4',
	];
	protected $msg = [
		'school_year.require'=>'不能为空';
		'school_year.number'=>'只能为数字';
		'school_year.between'=>'不能小于2，不能大于4';
	];
}
?>