<?php

function return_code($code = 10000, $msg = '', $data = [])
{
     //如果消息提示为空，但是业务状态码定义了，那么就显示默认定义的消息提示
    if (empty($msg) && config('?state_code.' . $code)) {
        $msg = config('state_code.' . $code);
        $msg = lang($msg);
    }
    $data = empty($data)?[]:$data;
    $result = [
        'code' => $code,
        'msg' => $msg,
        'data' => $data
    ];
    json($result)->send();
    die;
}
