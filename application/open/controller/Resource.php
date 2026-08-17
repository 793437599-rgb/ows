<?php

namespace app\open\controller;

use think\Db;

class Resource extends Common {

    public function __construct(\think\Request $request = null) {
        parent::__construct($request);
        $res = $this->param('', 'diy_trim', 'get');
    }

    public function show_img() {
        ini_set('memory_limit', '999M');
        $url = $this->param['image'];
        if ( !is_string($url) || empty($url)) {
            echo '';
            die;
        }
        if (is_url($url)) {
            $image = file_get_contents($url);
            echo $image;
            die;
        }
        $url      = ROOT_PATH . 'public' . $url;
        $realpath = realpath($url);
        if ( !is_file($realpath)) {
            echo '';
            die;
        }
        $path_info = pathinfo($realpath);
        $th        = ROOT_PATH . 'public/thumb/' . $path_info['basename'];//图片.jpg
        $is_exist  = is_file($th);                                        //存在缩略图
        if ( !$is_exist) {
            $size = filesize($realpath);
            //dump($size);die();
            if ($size > 40 * 1024) {
                $percent = $this->sumpic($size);
                ( new \Imgcompress($url, $percent) )->compressImg($th);  //bucunzai xuyaocbaocun
            } else {
                $image = file_get_contents($url);
                echo $image;
                die;
            }
        }
        $th    = realpath($th);
        $image = file_get_contents($th);
        echo $image;
        die;
    }

    public function show_img_new() {
        ini_set('memory_limit', '999M');
        $url = $this->param['image'];
        if ( !is_string($url) || empty($url)) {
            echo '';
            die;
        }
        if (is_url($url)) {
            $image = file_get_contents($url);
            echo $image;
            die;
        }
        $url   = trim($url, '.');
        $url   = ROOT_PATH . 'public' . $url;
        $image = file_get_contents($url);
        echo $image;
        die;
    }

    protected function sumpic($size) {
        if ($size < 80 * 1024) {
            return 0.2;
        }
        if ($size < 200 * 1024) {
            return 0.15;
        }
        if ($size < 900 * 1024) {
            return 0.13;
        }
        if ($size < 2000 * 1024) {
            return 0.12;
        }
        return 0.1;
    }

    public function secure_site($case) {

        if (empty($case)) {
            return $this->fetch('index/secure_site_no_trust');
        }
        $info =  Db::name('ssl')->where('case',$case)->find();
        if(empty($info)){
            return $this->fetch('index/secure_site_no_trust');
        }

        /*if ( !$this->check_example_host() ) {
            //$allow_domain = [
            //    'cscss.com.cn', 'www.cscss.com.cn', 'ku.cscss.com.cn', 'verification.cscss.com.cn', 'www.cscss.cn',
            //    'chu.edu.com.ve', 'edu.ch.university',
            //];
            $allow_domain = $info['url_only'];
            if(!empty($allow_domain)){
                $allow_domain = explode(',',$allow_domain);
            }
            if ( !$this->check_referrer_host($allow_domain)) {
                return $this->fetch('index/secure_site_no_trust');
            }
        }*/

        $info['content'] = htmlspecialchars_decode( $info['content']);
        $this->assign('new', $info);
        return $this->fetch('secure_site');
    }

}
