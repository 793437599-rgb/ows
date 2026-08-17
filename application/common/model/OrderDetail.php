<?php


namespace app\common\model;


class OrderDetail extends ModelBase
{
    protected $table = 'think_user_order_detail';

    public function getProveFileAttr($value)
    {
        $data = [];
        if (!empty($value)) {
            $data = unserialize($value);
        }
        !is_array($data) ? $data = [] : $data;
        return $data;
    }

    public function getDiplomaAttr($value)
    {
        $data = [];
        if (!empty($value)) {
            $data = unserialize($value);
        }
        !is_array($data) ? $data = [] : $data;
        return $data;
    }

    public function getTranscriptAttr($value)
    {
        $data = [];
        if (!empty($value)) {
            $data = unserialize($value);
        }
        !is_array($data) ? $data = [] : $data;
        return $data;
    }

    public function getHandDiplomaAttr($value)
    {
        $data = [];
        if (!empty($value)) {
            $data = unserialize($value);
        }
        !is_array($data) ? $data = [] : $data;
        return $data;
    }

    public function getHandTranscriptAttr($value)
    {
        $data = [];
        if (!empty($value)) {
            $data = unserialize($value);
        }
        !is_array($data) ? $data = [] : $data;
        return $data;
    }

    public function getCertificateImgAttr($value)
    {
        $data = [];
        if (!empty($value)) {
            $data = unserialize($value);
        }
        !is_array($data) ? $data = [] : $data;
        return $data;
    }


    public function getUniversityTypeAttr($value)
    {
        switch ($value) {
            case 1:
                $type = 'public university';
                break;
            case 2:
                $type = 'private university';
                break;
            case 3:
                $type = 'language school';
                break;
            case 4:
                $type = 'preparatory school';
                break;
            default:
                $type = 'other school';
        }
        return $type;
    }
}