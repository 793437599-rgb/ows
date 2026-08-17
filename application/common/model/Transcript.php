<?php
namespace app\common\model;

use think\Model;

class Transcript extends Model
{
    protected $autoWriteTimestamp = 'datetime';
    protected $table = 'think_transcript_credential';
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
}