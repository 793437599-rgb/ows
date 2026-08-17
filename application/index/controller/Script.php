<?php


namespace app\index\controller;

use think\Db;

class Script extends \think\Controller
{
    public function schoolSettle()
    {
        $countries = Db::name('cs_world_area_country')->select();
        $names = array_column($countries, 'name');
        $schools = Db::name('schools')->where('gj','eq','台湾')->limit(1000)->select();
        foreach ($schools as $val) {
            $key = array_search($val['gj'], $names);
            if ($key === false) {
                continue;
            }
            dump($key);
            $country_code = $countries[$key]['code'];
            $country_name = $countries[$key]['name_en'];
            $res = Db::name('schools')->where('id', $val['id'])->update([
                'country_code' => $country_code,
                'country_name' => $country_name
            ]);
            dump($res);                                                                                          
        }
    }


    public function disposal()
    {
        $str = 'CES ';
        $categories = Db::name('category')->column('id,name,content');
        foreach ($categories as $val){
//            dump($val['name']);
//            $val['name'] = str_replace('Educational Credential Assessment','Degree Assessment Education',$val['name']);
//            $val['name'] = str_replace('Education Credential Assessment','Education Credential Assessment',$val['name']);
//            $val['name'] = str_replace('Comparative Education Service','Degree Assessment Education',$val['name']);
//            $val['name'] = str_replace('ECA','ECA',$val['name']);
//            $val['name'] = str_replace('CES','DAE',$val['name']);
//            $val['name'] = str_replace('EDECC','WSE',$val['name']);
//            $val['name'] = str_replace('EDEC','WSE',$val['name']);
//            dump($val['name']);
//            echo '<hr>';
//            dump($val['content']);
//            $val['content'] = str_replace('Educational Credential Assessment','Degree Assessment Education',$val['content'],$i);

            $val['content'] = str_replace('Education Credential Assessment','Education Credential Assessment',$val['content'],$i);
              echo $i;
            $val['content'] = str_replace('Comparative Education Service','Degree Assessment Education',$val['content'],$i);
            echo $i;
            $val['content'] = str_replace(' ECA','ECA',$val['content'],$i);
            echo $i;
            $val['content'] = str_replace(' CES ','DAE',$val['content'],$i);
            echo $i;
            if ($i > 0){
                dump($val['content']);
            }
            $val['content'] = str_replace('EDECC','WSE',$val['content'],$i);
            echo $i;
            $val['content'] = str_replace('EDEC','WSE',$val['content'],$i);
            echo $i;
            $val['content'] = str_replace('edu@ca.university','DATA@WSE.ORG',$val['content'],$i);
            echo $i;
            $val['content'] = str_replace('edu@wse.org','DATA@WSE.ORG',$val['content'],$i);
            echo $i;
            $val['content'] = str_replace('Word Service Education','World Storage Education',$val['content'],$i);
            echo $i;
            $val['content'] = str_replace('World Service Education','World Storage Education',$val['content'],$i);
            echo $i;
//            dump($val['content']);
            echo '<hr>';
        }
    }
}