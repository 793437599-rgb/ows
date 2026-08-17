<?php


namespace app\common\controller;


use think\Cache;

class Translation extends \think\Controller
{
    private $handle;
    private $translate;

    public function __construct()
    {
        $this->handle = db('translation');
        $this->translate = new Translate();
    }

    /**
     * Notes: 查询指定语言的翻译结果
     * @param $key 查询key
     * @param $to 指定语言
     * @param false $flag 是否直接从数据库查询
     * @return string|null
     */
    public function get($key, $form, $to, $flag = false)
    {
        // $key = gzcompress($key, 9);
        $key = trim($key);
        $form =  baidu_languages_transform($form);
        $to =  baidu_languages_transform($to);
        $value = Cache::get($key . $form . '_' . $to);
        if (!empty($value) && $flag === false) {
            return $value;
        }
        $value = $this->handle->where(['key' => $key, 'to' => $to])->value('value');
        if (empty($value)) {
            $result = $this->set($key, $form, $to);
            if ($result) {
                $value = Cache::get($key . $form . '_' . $to);
            }
        }
        // 查询结果写入缓存
        Cache::set($key . '_' . $to, $value, 12 * 60 * 60);
        return $value;
    }

    /**
     * Notes: 翻译结果写入
     * @param $query '待翻译内容'
     * @param $form '原文语言'
     * @param $to '翻译后语言'
     * @return bool 翻译结果
     */
    public function set($query, $form, $to)
    {
        $result = $this->translate->translate($query, $form, $to);
        if (!isset($result['trans_result'])) {
            return false;
        }
        $dst = $result['trans_result'][0]['dst'];
        //        $key = gzcompress($query, 9);
        $data = [
            'key' => $query,
            'value' => $dst,
            'form' => $form,
            'to' => $to
        ];
        $find = $this->handle->where($data)->find();
        $result = true;
        if (!$find) {
            $result = $this->handle->insert($data);
        }
        sleep(1);
        Cache::set($query . $form . '_' . $to, $dst, 12 * 60 * 60);
        if ($result) {
            return true;
        }
        return false;
    }
}
