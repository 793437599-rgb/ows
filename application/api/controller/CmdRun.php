<?php


namespace app\api\controller;


use app\common\controller\Translation;
use think\Controller;

class CmdRun extends Controller
{
    public function trans()
    {
        $tables = [
            [
                'name' => 'question_categoty',
                'field' => [
                    'category_name'
                ],
            ],
        ];
        $langs = ['zh-cn', 'fr-fa', 'ja-jp', 'ko-kr', 'ru-ru'];
        $values = [];
        foreach ($tables as $table) {
            $name = $table['name'];
            $tableHandle = db($name);
            foreach ($table['field'] as $val) {
                $value = $tableHandle->column($val);
                $values = array_merge($values, $value);
            }
        }
        $values = array_unique(array_filter($values));
        $translation = new Translation();
        foreach ($langs as $lang) {
            $total = count($values);
            foreach ($values as $key => $value) {
                $to = baidu_languages_transform($lang);
                $result = $translation->set($value, 'en', $to);
                printf("{$lang} 翻译进度: [%-50s] %d%% Done\r", str_repeat('#', $key / $total * 50), $key / $total * 100);
            }
        }
    }
}
