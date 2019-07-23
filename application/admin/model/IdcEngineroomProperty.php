<?php

namespace app\admin\model;

use think\Model;


class IdcEngineroomProperty extends Model
{

    

    //数据库
    protected $connection = 'database';
    // 表名
    protected $name = 'idc_engineroom_property';
    
    // 自动写入时间戳字段
    protected $autoWriteTimestamp = false;

    // 定义时间戳字段名
    protected $createTime = false;
    protected $updateTime = false;
    protected $deleteTime = false;

    // 追加属性
    protected $append = [
        'update_time_text'
    ];



    public function getStatusAttr($value, $data){
        $str=['0'=>'正常','1'=>'损坏','2'=>'作废','3'=>'遗失'];
        return $str[$value];
    }


    public function getUpdateTimeTextAttr($value, $data)
    {
        $value = $value ? $value : (isset($data['update_time']) ? $data['update_time'] : '');
        return is_numeric($value) ? date("Y-m-d H:i:s", $value) : $value;
    }

    protected function setUpdateTimeAttr($value)
    {
        return $value === '' ? null : ($value && !is_numeric($value) ? strtotime($value) : $value);
    }


}
