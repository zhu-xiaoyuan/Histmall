<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2016/8/10 0010
 * Time: 10:06
 */
namespace App\Model;

use Think\Model;

class VipModel extends Model{

    //初始化验证模块
    public function _initialize()
    {
        parent::_initialize();
    }

    //获取当前用户的 下级用户数量
    public function getSubNum($data){
        //$map['subscribe'] = 1;  //取消关注也统计
        if($data['role']==0){ //如果是  普通会员 获取直接下级用户
            $num = $this->where('pid='.$data['id'])->count();
        }else{  //总监、via、推广
            $map['role_1_id'] = $data['id'];
            $num = $this->where($map)->count();
        }
        return $num;
    }

    //获取当前用户的 下级人数列表
    public function getSubMember($data){    //把self::$WAP['vip'] 穿进去
        $page = intval(I("pg"));
        $page_count = intval(I("pc"));
        if ($page_count > 20) {
            $page_count = 20;
        }
        $m = M('vip');
        //$data = self::$WAP['vip'];
        //$map['subscribe'] = 1;  //取消关注也统计

        if($data['role']==0){ //如果是 推广人员 或者 普通会员 获取直接下级用户
            $map['pid'] = $data['id'];
            $data = $m->where($map)->order('ctime desc')->limit($page_count * $page, $page_count)->select();
            return $data;
        }else{  //总监、via、推广  分级
            switch($data['role']){
                case 1:
                    $map['role_1_id'] = $data['id'];
                    break;
                case 2:
                    $map['role_2_id'] = $data['id'];
                    break;
                case 3:
                    $map['role_3_id'] = $data['id'];
                    break;
            }
            $data = $m->where($map)->order('ctime desc')->limit($page_count * $page, $page_count)->select();
            return $data;
        }
    }
}