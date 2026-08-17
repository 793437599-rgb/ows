<?php

namespace app\common\controller;

use think\Db;
use think\db\exception\DataNotFoundException;
use think\db\exception\ModelNotFoundException;
use think\exception\DbException;

class WorldArea
{


    public static function getTypeList($where = [], $type = "country")
    {
        $where['name'] = ['neq','000'];
        $where['name_en'] = ['neq','000'];
        if (!in_array($type, ['country', 'state', 'city', 'region'])) {
            return [];
        }
        return Db::name("cs_world_area_{$type}")->order('alias')->where($where)->select();
    }

    /**
     * Notes:获取地区码
     * Date: 2020/9/18  18:51
     * @param $name
     * @param string $type
     * @return int|mixed|string|null
     */
    public static function getCodeByNmae($name,$type="country")
    {
        if (!in_array($type,['country','state','city','region'])){
            return '';
        }
        return Db::name("cs_world_area_{$type}")->where('name|name_en', 'eq', $name)->value('code');
    }

    /**
     * Notes: 根据 code 获取name

     * Date: 2020/9/18  18:53
     * @param $code
     * @param string $type
     * @return int|mixed|string|null
     */
    public static function getNameByCode($code,$type="country")
    {
        if (!in_array($type,['country','state','city','region'])){
            return '';
        }
        return Db::name("cs_world_area_{$type}")->where('code', 'eq', $code)->value('name_en');
    }

    public static function getProvinces($value,$type = 'CODE')
    {
        if ($type != 'CODE') {
            $value = self::getCodeByNmae($value);
        }
        $where = ['country_code' => $value];
        return self::getTypeList($where, 'state');
    }

    /**
     * Notes: 根据code 获取城市列表
     * Date: 2020/9/18  19:05
     * @param $code
     * @param string $type
     * @return bool|\PDOStatement|string|\think\Collection
     * @throws DataNotFoundException
     * @throws ModelNotFoundException
     * @throws DbException
     */
    public static function getCities($code, $type = 'country')
    {
        $where = [
            'name|name_en' => ['neq', '000'],
        ];
        if ($type == 'country') {
            $provinces = self::getProvinces($code);
            $province_codes = array_column($provinces, 'code');
            if (!empty($province_codes)){
                $where['state_code'] = ['in', $province_codes];
            }else {
                $where['state_code'] = $code . '000';
            }
        } else {
            $where['state_code'] = $code;
        }
        return self::getTypeList($where,'city');
    }

    public static function getNationality($id)
    {
        return Db::name('country')->where('id',$id)->value('name');
    }


    public static function getcountryname($name)
    {
        $res=Db::name('cs_world_area_country')->where('name|name_en',$name)->value('name');
        if ($res) {
           return $res;
        }
        return $name;
    }
}