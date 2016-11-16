<?php
/**
 * 用于选择地址，省市区三级联动标签
 */

namespace Admin\Model;
use Think\Model;

class LocationModel extends Model
{
    //表命，默认为省
    protected $tableName = 'location_province';

    //获取省
    public function getProvinces($fields = '*'){
        //$this->tableName = 'location_province';
        //var_dump($this->getLastSql());
        return $this->field($fields)->select();
    }

    //根据pid获取市
    public function getCities($pid){
        $pid = intval(trim($pid));
        return $this->table('location_city')->where('pid='.$pid)->select();
    }

    //根据pid获取区
    public function getAreas($pid){
        $pid = intval(trim($pid));
        return $this->table('location_area')->where('pid='.$pid)->select();
    }


}