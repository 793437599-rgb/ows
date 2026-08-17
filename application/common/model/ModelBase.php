<?php


namespace app\common\model;

use app\index\controller\Order;
use think\Model;

class ModelBase extends Model
{
    /**
     * 更新或添加数据
     * @param  array  $condition
     * @param  array  $data
     * @return bool|false|int
     */
    public function refresh($condition, $data)
    {
        $find = $this->findData($condition);
        if (empty($find)){
            $data = array_merge($data,$condition);
            $condition = [];
        }
        return $this->allowField(true)->save($data, $condition);
    }

    /**
     * 查询一条数据
     * @param  array $condition
     * @param  string  $field
     * @return array|bool|false|\PDOStatement|string|Model|null
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function findData($condition, $field = '*',$order = 'id')
    {
        return $this->where($condition)->field($field)->order($order)->find();
    }

    /** 查询多条数据
     * @param $condition  条件
     * @param string $field  查询字段
     * @param string $order  排序
     * @return bool|false|\PDOStatement|string|\think\Collection
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function selects($condition, $field = '*',$order = 'id asc')
    {
        return $this->where($condition)->field($field)->order($order)->select();
    }

    /**
     * 获取指定数据
     * @param $condition
     * @param $name
     * @return array|bool|string
     */
    public function columns($condition,$name)
    {
        return $this->where($condition)->column($name);
    }
}