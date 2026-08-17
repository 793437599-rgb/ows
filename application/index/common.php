<?php
/**
 * 默认初始密码
 * @param int $length
 * @return false|string
 */
 function init_password($length = 6){
     $str = 'ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz1234567890';
     $randStr = str_shuffle($str);//打乱字符串
     $rands = substr($randStr, 0, $length);//substr(string,start,length);返回字符串的一部分
     return $rands;
 }
