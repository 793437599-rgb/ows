<?php
namespace app\oauth\validate;

use think\Validate;

class IpDomain extends Validate
{
    protected $rule = [
        'domain'   => 'require',
        'level' => 'require|number',
        'top_domain'  => 'require',
        'parent_domain'  => 'require',
        'domain_suffix'  => 'require',
        'manual_ip'  => 'require',
        'ip'  => 'require',
        'ip_long'  => 'number',
        'ipv6'  => 'require',
        'ipv6_long'  => 'number',
        'is_manual'  => 'in:1,2',
        'status'  => 'in:1,2',
    ];

    protected $message = [
        'domain.require'   => '{%error_10000101}',              //域名为空
        'level.require' => '{%error_10000102}',                 //域名级别为空
        'level.number'  => '{%error_10000103}',                 //域名级别必须为数字
        'top_domain.require'   => '{%error_10000104}',          //对应的顶级域名为空
        'parent_domain.require'   => '{%error_10000105}',       //对应的上级域名为空
        'domain_suffix.require'   => '{%error_10000106}',       //对应的域名后缀为空
        'manual_ip.require'   => '{%error_10000107}',           //手动设置的IP为空
        'ip.require'   => '{%error_10000108}',                  //IP为空
        'ip_long.number'   => '{%error_10000109}',              //数字IP不合法
        'ipv6.require'   => '{%error_10000110}',                //IPv6为空
        'ipv6_long.number'   => '{%error_10000111}',            //数字IP（IPv6）不合法
        'is_manual.in'   => '{%error_10000112}',                //域名ip手动状态不合法
        'status.in'   => '{%error_10000113}',                   //域名状态不合法
    ];
    protected $scene = [
        'add'  =>  ['domain','level','top_domain','domain_suffix','ipv6','ipv6_long','is_manual','status'],
        'edit' =>  ['domain','level','top_domain','domain_suffix','ipv6','ipv6_long','is_manual','status'],
    ];
}