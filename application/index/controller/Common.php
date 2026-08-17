<?php


namespace app\index\controller;


use app\common\controller\HomeBase;
use app\common\controller\WorldArea;
use think\Db;

/**
 * 通用接口
 * Class Common
 * @package app\index\controller
 */
class Common extends HomeBase
{
    public function getProvince()
    {
        $area = [];
        // 用户ID判断
        if ($this->request->isPost()) {
            $national = request()->param('national');
            $type = request()->param('type', 'CODE');
            $province = WorldArea::getProvinces($national, $type);
            if (empty($province)) {
                $state_code = WorldArea::getCodeByNmae($national,'country');
                $city = WorldArea::getCities($state_code);
                if (!empty($city)) {
                    $area['city'] = $city;
                } else {
                    $area['province'] = [];
                }
            } else {
                $area['province'] = $province;
            }
        }
        return json($area);
    }

    public function getCity()
    {
        $province = request()->param('province');
        $type = request()->param('type', 'CODE');
        $state_code = $province;
        if ($type == 'NAME') {
            $state_code = WorldArea::getCodeByNmae($province,'state');
        }
        $citys  = WorldArea::getCities($state_code,'province');
        return json($citys);
    }

    public function getCounty()
    {
        $data = $this->request->param();
        $city_code = $data['city'];
        $county = Db::name("cs_world_area_region")->where("city_code", $city_code)->select();
        foreach ($county as &$val) {
            $val['name'] = lang($val['name']);
        }
        return json_encode($county);
    }

    public function getDocumentType()
    {
        $lang = \cookie('think_var') ?: 'en-us';
        $document_types = config('documet_type');
        $default_document_group = $document_types[$lang];
        $document_types['default'] = $default_document_group;
        return json($document_types);
    }

    public function getSchools()
    {
        $country = input('post.country');
        if (empty($country)) {
            return status_code(10003, '', []);
        }
        $code = getCountryCode($country);
        $schools = Db::name('schools')->where(['country_code' => $code, 'status' => 1])->select();
        return status_code(20000, '', $schools);
    }

    public function getMajor()
    {
        $sub_title = getSubTitle($this->lang);
        $faculty = input('post.faculty', '', 'trim');
        $faculty_id = Db::name('major')->where('title|title_en', 'eq', $faculty)->value('id');
        if (empty($faculty) || empty($faculty_id)) {
            return status_code(10003, '', []);
        }
        $faculties = Db::name('major')->field("*,{$sub_title} as title")->where('sort', 'eq', $faculty_id)->select();
        return status_code(20000, '', $faculties);
    }

    /**
     * 获取相关专业列表
     * @return void
     */
    public function getMajors()
    {
        $university = input('post.university', '', 'trim');
        $school_id = Db::name('schools')->where(['name_en' => $university])->value('id');
        if (empty($university) || empty($school_id)) {
            return status_code(10003, '', []);
        }
        $majors = Db::name('schools_speciality')->where('schools_id',$school_id)->select();
        return status_code(20000, '', $majors);
    }

    public function getAllCity()
    {
        $national = request()->param('country');
        $type = request()->param('type', 'CODE');
        $code = $national;
        if ($type == 'NAME') {
            $code = WorldArea::getCodeByNmae($national, 'country');
        }
        $city = WorldArea::getCities($code, 'country');
        return status_code(20000, '', $city);
    }
}