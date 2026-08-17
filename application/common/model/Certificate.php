<?php


namespace app\common\model;


class Certificate extends ModelBase
{
    protected $autoWriteTimestamp = 'datetime';

    public function getCertificatePngAttr($value)
    {
        if (!empty($value)) {
            $result = unserialize($value);
            if ($result === false) {
                return [];
            }
            return $result;
        }
        return $value;
    }

    public function getContentAttr($value)
    {
        return htmlspecialchars_decode($value);
    }
}