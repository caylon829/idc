<?php

namespace app\admin\model;

use think\Model;


class IdcEngineroomlogvisitlog extends Model
{

    

    //数据库
    protected $connection = 'database';
    // 表名
    protected $name = 'idc_engineroomvisitlog';
    
    // 自动写入时间戳字段
    protected $autoWriteTimestamp = false;

    // 定义时间戳字段名
    protected $createTime = false;
    protected $updateTime = false;
    protected $deleteTime = false;

    // 追加属性
    protected $append = [
        'finish_time_text',
        'check_time_text'
    ];

    public function getStatusAttr($value, $data){
        $str=['0'=>'申请','1'=>'申请通过','2'=>'已进入','3'=>'已离开','4'=>'登记完成','5'=>'申请已撤销'];
        return $str[$value];
    }
    public function getTimesAttr($value, $data){
        if($data['times']>0) {
            $data['times'] = '已发送';
        }else{
            $data['times'] = '未发送';
        }
        return $data['times'];
    }

    public function getFinishTimeAttr($value, $data){
        if($data['finish_time']=='2012-12-01 00:00:00')
            $data['finish_time']='';
        return $data['finish_time'];
    }
    

    



    public function getFinishTimeTextAttr($value, $data)
    {
        $value = $value ? $value : (isset($data['finish_time']) ? $data['finish_time'] : '');
        return is_numeric($value) ? date("Y-m-d H:i:s", $value) : $value;
    }


    public function getCheckTimeTextAttr($value, $data)
    {
        $value = $value ? $value : (isset($data['check_time']) ? $data['check_time'] : '');
        return is_numeric($value) ? date("Y-m-d H:i:s", $value) : $value;
    }

    protected function setFinishTimeAttr($value)
    {
        return $value === '' ? null : ($value && !is_numeric($value) ? strtotime($value) : $value);
    }

    protected function setCheckTimeAttr($value)
    {
        return $value === '' ? null : ($value && !is_numeric($value) ? strtotime($value) : $value);
    }


}
