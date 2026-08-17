<?php
namespace app\oauth\validate;

use think\Validate;

class OauthClients extends Validate
{
    protected $rule = [
        'client_id'   => 'require',
        'client_secret' => 'require',
        'session_key'  => 'require',
        'client_name'  => 'require',
        'allow_domain'  => 'require',
        'client_user_fields'  => 'require',
        'redirect_uri'  => 'require',
        'grant_types'  => 'require',
        'scope'  => 'require',
        'user_id'  => 'number',
        'is_default'  => 'in:1,2',
        'is_user'  => 'in:1,2',
        'need_authorize'  => 'in:1,2',
        'authorize_domain'  => 'require',
        'authorize_is_ssl'  => 'in:1,2',
    ];

    protected $message = [
        'client_id.require'   => '{%error_10000001}',
        'client_secret.require' => '{%error_10000002}',
        'session_key.require'  => '{%error_10000003}',
        'client_name.require'   => '{%error_10000004}',
        'allow_domain.require'   => '{%error_10000005}',
        'client_user_fields.require'   => '{%error_10000006}',
        'redirect_uri.require'   => '{%error_10000007}',
        'grant_types.require'   => '{%error_10000008}', 
        'scope.require'   => '{%error_10000009}',
        'user_id.number'   => '{%error_10000010}',
        'is_default.in'   => '{%error_10000011}',
        'is_user.in'   => '{%error_10000012}',
        'need_authorize.in'   => '{%error_10000050}',
        'authorize_domain.require'   => '{%error_10000051}',
        'authorize_is_ssl.in'   => '{%error_10000052}',
    ];
    protected $scene = [
        'add'  =>  ['client_id','client_secret','session_key','client_name','redirect_uri','user_id','is_default','is_user','need_authorize'],
        'edit_all'  =>  ['client_id','client_secret','client_name','redirect_uri','user_id','is_default','is_user','need_authorize'],
        'edit'  =>  ['client_name','redirect_uri','user_id','is_default','is_user','need_authorize'],
        'reset_appid'  =>  ['client_id','client_secret'],
        'reset_appsecret'  =>  ['client_secret'],
        'reset_session_key'  =>  ['session_key'],
    ];
}