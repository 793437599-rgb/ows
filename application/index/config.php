<?php
return [
    // +----------------------------------------------------------------------
    // | 模板设置
    // +----------------------------------------------------------------------

    'template' => [
        // 模板路径
        'view_path' => '../themes/canada/',
        'layout_on'     =>  true,
        'layout_name'   =>  'base',
    ],
    //分页配置
    'paginate'               => [
        'type'     => '\org\Page',
        'var_page' => 'page',
    ],

    'http_exception_template' => [
        404 =>  APP_PATH . 'index/view/404.html',
    ]


];