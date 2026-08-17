<?php
namespace app\home\controller;
use think/App;
class new extends app{
	public function index(){
		return App::VERSION;
	}
}
?>