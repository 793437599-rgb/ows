<?php 
/**
 * CURL操作类
 * @todo hoff 构造函数新增$option，丰富配置 2016-4-1
 */
namespace Api\Curl;

class Curl
{
    public $ch;
    public function __construct($option=array())
    {
        $this->ch = curl_init();
        if(!empty($option)){
            array_walk($option, array($this,'setopt'));
        }
        //dump(curl_getinfo($this->ch));
    }
    
    private function setopt($v,$k) {
        curl_setopt($this->ch, $k, $v);
    }

    //请求服务器
    public function get($url)
    {
        $ch = $this->ch;
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);        
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
        if ( ! curl_exec($ch))
        {
//            Log::write(curl_errno($ch));
            $data = '';
        }
        else
        {
            $data = curl_multi_getcontent($ch);
        }
        curl_close($ch);

        return $data;
    }

    //提交POST数据
    public function post($url, $postData)
    {
        $ch = $this->ch;
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
        curl_setopt($ch, CURLOPT_TIMEOUT, 30);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $postData);

        if ( ! curl_exec($ch))
        {
//            Log::write(curl_errno($ch));
            $data = '';
        }
        else
        {
            $data = curl_multi_getcontent($ch);
        }
        curl_close($ch);
        return $data;
    }
    
    
    
    //带微信证书提交的数据
    public function post_ssl($url, $vars, $second=30,$aHeader=array()){
        
        $ch = $this->ch;
	//超时时间
	curl_setopt($ch,CURLOPT_TIMEOUT,$second);
	curl_setopt($ch,CURLOPT_RETURNTRANSFER, 1);
	curl_setopt($ch,CURLOPT_URL,$url);
	curl_setopt($ch,CURLOPT_SSL_VERIFYPEER,false);
	curl_setopt($ch,CURLOPT_SSL_VERIFYHOST,false);

	curl_setopt($ch,CURLOPT_SSLCERTTYPE,'PEM');
	curl_setopt($ch,CURLOPT_SSLCERT,getcwd().'/cert.pem');
	//默认格式为PEM，可以注释
	curl_setopt($ch,CURLOPT_SSLKEYTYPE,'PEM');
	curl_setopt($ch,CURLOPT_SSLKEY,getcwd().'/key.pem');
	
	if( count($aHeader) >= 1 ){
            curl_setopt($ch, CURLOPT_HTTPHEADER, $aHeader);
	}
 
	curl_setopt($ch,CURLOPT_POST, 1);
	curl_setopt($ch,CURLOPT_POSTFIELDS,$vars);
	$data = curl_exec($ch);
        p($data);
        exit;
	if($data){
            curl_close($ch);
            return $data;
	}
	else {
            $error = curl_errno($ch);
            echo "call faild, errorCode:$error\n"; 
            curl_close($ch);
            return false;
	}
    }
    
    
    
}