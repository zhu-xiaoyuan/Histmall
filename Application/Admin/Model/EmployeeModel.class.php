<?php
// +----------------------------------------------------------------------
// | 自定义员工模型
// +----------------------------------------------------------------------
namespace Admin\Model;
use Think\Model;

class EmployeeModel extends Model
{

    // 登陆操作
    public function getLoginEmp($data = array())
    {
        $employee = $this->where(array('username' => $data['username'], 'userpass' => md5($data['userpass'])))->find();
        return $employee ? $employee : false;
    }

    // 获取所有用户
    public function randomEmployee()
    {
        $employees = $this->where(array('weight' => array('gt', 0)))->select();
        $sum = $this->where(array('weight' => array('gt', 0)))->sum('weight');
        if (!$employees) {
            return 0;
        }
        $result = 0;
        $total = 100;
        $random = mt_rand(1, 100);
        if ($random > $sum) {
            return $result;
        }
        $random = $sum - $random;
        foreach ($employees as $v) {
            if ($random > $v['weight']) {
                $random = $random - $v['weight'];
            } else {
                $result = $v['id'];
                break;
            }
        }
        return $result;
    }

    public function caculateAchievement($employees)
    {

        $beginToday = mktime(0, 0, 0, date('m'), date('d'), date('Y'));
        $beginMonth = mktime(0, 0, 0, date('m'), 1, date('Y'));

        $morder = M('Shop_order');
        $mvip = M('vip');

        // 遍历所有员工
        foreach ($employees as $k => $v) {
            // 提取所有下线
            $map = array();
            $temparr = array();
            $temp = $mvip->field('id')->where(array('employee' => $v['id']))->select();
            foreach ($temp as $vv) {
                array_push($temparr, $vv['id']);
            }

            // 所有会员总数
            $count = $mvip->where(array('id' => array('in', in_parse_str($temparr))))->count();
            $employees[$k]['vip_number'] = $count ? $count : 0;

            // 基本条件
            $map['vipid'] = array('in', in_parse_str($temparr));

            // 所有订单量
            $count = $morder->where($map)->count();
            $employees[$k]['all_order_number'] = $count ? $count : 0;

            // 失败订单量
            $map['status'] = array('in', array('4', '7', '6', '0'));// 包括退货中、退货完成、已关闭、已取消
            $count = $morder->where($map)->count();
            $employees[$k]['failure_order_number'] = $count ? $count : 0;

            // 成功订单量
            $map['status'] = array('in', array('2', '3', '5'));// 包括已付款、已发货、已完成
            $count = $morder->where($map)->count();
            $employees[$k]['success_order_number'] = $count ? $count : 0;
            $employees[$k]['success_order_payprice'] = round($morder->where($map)->sum('payprice'), 2);

            // 当月成交量
            $map['ctime'] = array('egt', $beginMonth);
            $count = $morder->where($map)->count();
            $employees[$k]['month_order_number'] = $count ? $count : 0;
            $employees[$k]['month_order_payprice'] = round($morder->where($map)->sum('payprice'), 2);

            // 当天成交量
            $map['ctime'] = array('egt', $beginToday);
            $count = $morder->where($map)->count();
            $employees[$k]['today_order_number'] = $count ? $count : 0;
            $employees[$k]['today_order_payprice'] = round($morder->where($map)->sum('payprice'), 2);
        }

        return $employees;
    }

    // public function orderDetials($employeeid){

    // 	$morder = M('Shop_order');
    // 	$mvip = M('vip');

    // 	// 用户的临时
    // 	$temparr = array();
    // 	$temp = $mvip->field('id')->where(array('employee'=>$employeeid))->select();
    // 	foreach($temp as $v){
    // 		array_push($temparr,$vv['id']);
    // 	}
    // 	$map['vipid'] = array('in',$temparr);
    // 	$detials = round($morder->where($map)->sum('payprice'),2);
    // }

}

?>
