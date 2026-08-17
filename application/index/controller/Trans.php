<?php


namespace app\index\controller;


use app\common\controller\Translate;
use think\Db;

class Trans extends \think\Controller
{
    public function schools()
    {
        $schools = Db::name('schools')->where('name_en','eq','')->select();
        $translate = new Translate();
        $total = count($schools);
        foreach ($schools as $key => $school) {
            if (empty($var))
            $result = $translate->translate($school['name_cn'], 'zh', 'en');
            if (!isset($result['trans_result'])) {
                continue;
            }
            $dst = $result['trans_result'][0]['dst'];
            $res = db('schools')->where('id', $school['id'])->setField('name_en', $dst);
            sleep(1);
            printf("翻译进度: [%-50s] %d%% Done\r", str_repeat('#', $key / $total * 50), $key / $total * 100);
        }
    }
}