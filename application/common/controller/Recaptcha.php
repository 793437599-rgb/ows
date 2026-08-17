<?php


namespace app\common\controller;


use think\Controller;

class Recaptcha
{
    private $response;
    private $remoteip;
    private $secret = '  ';
    private $url = ' ';

    public function __construct($response, $remoteip = '')
    {
        $this->response = $response;
        $this->remoteip = $remoteip;
    }

    public function siteverify()
    {
        $param = [
            'secret' => $this->secret,
            'response' => $this->response,
        ];
        if (!empty($this->remoteip)) {
            $param['remoteip'] = $this->remoteip;
        }
        $response = http_query($this->url,null,$param);
        return json_decode($response,true);
    }
}