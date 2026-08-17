<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2019/8/8 0008
 * Time: 11:40
 */

namespace app\common\model;

class UserOrderDetail extends ModelBase
{
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
                $type = 'Public University';
                break;
            case 2:
                $type = 'Private University';
                break;
            case 3:
                $type = 'Language School';
                break;
            case 4:
                $type = 'Preparatory School';
                break;
            default:
                $type = 'Other School';
        }
        return $type;
    }

    public function get_documet_type_key($title,$title2){
        $document_types = config('documet_type');  //获取id
        $title_list=array_column($document_types['en-us'], 'title');
        $title_key=array_keys($title_list,$title);  //1'Residency Documents'
        $title_key=$title_key[0];
        $title_id=$document_types['en-us'][$title_key]['id'];
        $title_name=$document_types['zh-cn'][$title_key]['title'];

        $title_key2=array_keys($document_types['en-us'][$title_key]['items'],$title2); //2   'Other'
        $title_key2=$title_key2[0];
        $title_id2=$document_types['en-us'][$title_key]['items_id'][$title_key2];
        $title_name2=$document_types['zh-cn'][$title_key]['items'][$title_key2];
        //dump($title);dump($title2);
        return  array('key1' => $title_id,'key2' => $title_id2,'name1' => $title_name,'name2' => $title_name2);
    }

    public function get_degree($degree){
        if ($degree=='Master') {
            $id=3;
        }elseif ($degree== 'Ph.D') {
            $id=4;
        }elseif ($degree== 'Diploma') {
            $id=1;
        }elseif ($degree== 'Bachelor') {
            $id=2;
        }else{
            $id=2;
        }
        return  $id;
    }

    public function get_university_type($order_detail){
        $type = $order_detail->getData('university_type');
        if ($type==1) {
            $id=5;
        }elseif ($type==2) {
            $id=1;
        }elseif ($type==3) {
            $id=3;
        }elseif ($type==4) {
            $id=4;
        } elseif ($type==5) {
            $id=2;
        }else{
            $id='';
        }
        return  $id;
    }
}