<?php

namespace app\common\controller;

use PhpOffice\PhpSpreadsheet\IOFactory;
use think\Exception;

class PHPExecl
{
    public static function readExecl($path)
    {
        if (!is_file($path)) {
            return new Exception('请传入正确的表格文件');
        }
        $reader = IOFactory::createReaderForFile($path);
        $reader->setReadDataOnly(TRUE);
        $spreadsheet = $reader->load($path); //载入excel表格Z                       
        $worksheets = $spreadsheet->getAllSheets();
        $sheet_data = [];
        foreach ($worksheets as $key=>$worksheet){
            $data = [];
            $highestRow = $worksheet->getHighestRow(); // 总行数
            $highestColumn = $worksheet->getHighestColumn(); // 总列数
            $lines = $highestRow - 1;
            if ($lines <= 0) {
                continue;
            }
            for ($row = 2; $row <= $highestRow; ++$row) {
                $is_null = 0;
                for ($col = 1; $col <= (ord($highestColumn) - (ord('A') - 1)); ++$col) {
                    $data[$row - 1][$col - 1] = $worksheet->getCellByColumnAndRow($col, $row)->getValue();
                    if ($data[$row - 1][$col - 1] === null){
                        $is_null++;
                    }
                }
                if ($is_null != count($data[$row - 1])){
                    array_push($sheet_data,$data[$row - 1]);
                }
            }
        }
        return $sheet_data;
    }

    public static function readExecl_er($path)
    {
        if (!is_file($path)) {
            return new Exception('请传入正确的表格文件');
        }
        $reader = IOFactory::createReaderForFile($path);
        $reader->setReadDataOnly(TRUE);
        $spreadsheet = $reader->load($path); //载入excel表格Z                       
        $worksheets = $spreadsheet->getAllSheets();
        $sheet_data = [];
        foreach ($worksheets as $key=>$worksheet){
            $data = [];
            $highestRow = $worksheet->getHighestRow(); // 总行数
            $highestColumn = $worksheet->getHighestColumn(); // 总列数
            $lines = $highestRow - 1;
            if ($lines <= 0) {
                continue;
            }
            for ($row = 1; $row <= $highestRow; ++$row) {
                $is_null = 0;
                for ($col = 1; $col <= (ord($highestColumn) - (ord('A') - 1)); ++$col) {
                    $data[$row - 1][$col - 1] = $worksheet->getCellByColumnAndRow($col, $row)->getValue();
                    if ($data[$row - 1][$col - 1] === null){
                        $is_null++;
                    }
                }
                if ($is_null != count($data[$row - 1])){
                    array_push($sheet_data,$data[$row - 1]);
                }
            }
        }
        return $sheet_data;
    }
}