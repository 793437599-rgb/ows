<?php
// +----------------------------------------------------------------------
// | ThinkPHP [ WE CAN DO IT JUST THINK ]
// +----------------------------------------------------------------------
// | Copyright (c) 2006-2016 http://thinkphp.cn All rights reserved.
// +----------------------------------------------------------------------
// | Licensed ( http://www.apache.org/licenses/LICENSE-2.0 )
// +----------------------------------------------------------------------
// | Author: liu21st <liu21st@gmail.com>
// +----------------------------------------------------------------------

// [ 应用入口文件 ]

// 定义应用目录

//ini_set('session.cookie_domain',".wse.org");//跨域访问Session
//
//ini_set('session.cookie_lifetime', 7200);/*设置session过期时间*/

define('APP_PATH', __DIR__ . '/../application/');
$runMode = @$_SERVER['LOCAL_MODE'];
if($runMode=='prod'){
    define('RUN_MODE','prod');
    define('DEBUG',true );
}else{
    define('RUN_MODE','dev');
    define('DEBUG',true );
}

require_once APP_PATH.'inits.php';
check_allow_domain();

/*include '../application/common.php';
if(is_mobile()){
	
	echo 'mobile';
    define('VIEW_PATH',__DIR__ .'/../application/mobile/index/index');
}else{
	echo 'pc';
	define('VIEW_PATH', __DIR__ . '/../application/index/index/');
}  */
// 加载框架引导文件
require __DIR__ . '/../thinkphp/start.php';
