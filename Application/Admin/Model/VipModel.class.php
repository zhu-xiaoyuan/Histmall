<?php
// +----------------------------------------------------------------------
// | 自定义用户模型
// +----------------------------------------------------------------------
namespace Admin\Model;

use Think\Model;

class VipModel extends Model
{

    // 排序方式
    public function compare($a, $b)
    {
        if ($a['osum'] < $b['osum']) {
            return 1;
        } else if ($a['osum'] == $b['osum']) {
            if ($a['count1'] < $b['count1']) {
                return 1;
            } else if ($a['count1'] == $b['count1']) {
                return 0;
            } else {
                return -1;
            }
            return 0;
        } else {
            return -1;
        }
    }

    // 排序方式2
    public function compare2($a, $b)
    {
        if ($a['success_order_payprice'] < $b['success_order_payprice']) {
            return 1;
        } else if ($a['success_order_payprice'] == $b['success_order_payprice']) {
            return 0;
        } else {
            return -1;
        }
    }

    // 获取用户详情
    public function getVipForMessage($id = 0)
    {
        // 容错
        if ($id == 0) {
            return false;
        }
        $vip = $this->where(array('id' => $id))->find();
        // 容错
        if (!$vip) {
            return false;
        }
        $str = '';
        $str .= "<div style='width:600px;overflow:hidden;*zoom:1;'>";
        $str .= "<img style='width:20%;float:left;margin-top:1%' src='" . $vip['headimgurl'] . "'/>";
        $str .= "<div style='float: left;margin-left: 3%'>";
        $str .= "<div style='margin-top: 2%;'>ID：" . $vip['id'] . "</div>";
        $str .= "<div style='margin-top: 2%;'>昵称：" . $vip['nickname'] . "</div>";
        if ($vip['name']) {
            $str .= "<div style='margin-top: 2%;'>姓名：" . $vip['name'] . "</div>";
        }

        if ($vip['mobile']) {
            $str .= "<div style='margin-top: 2%;'>手机：" . $vip['mobile'] . "</div>";
        }

        if ($vip['email']) {
            $str .= "<div style='margin-top: 2%;'>邮箱：" . $vip['email'] . "</div>";
        }

        $str .= "<div style='margin-top: 2%;'>消费全返：" . number_format($vip['total_got_bonus'],2).'/'.number_format($vip['total_bonus_amount'],2) . "</div>";
        $str .= "<div style='margin-top: 2%;'>佣金：" . number_format($vip['total_yj'],2) . "</div>";
        $str .= "<div style='margin-top: 2%;'>VIA提成：" . number_format($vip['total_tuanzhang_tc'],2) . "</div>";

        $str .= "<div style='margin-top: 2%;'>创建：" . date('Y-m-d H:i:s', $vip['cctime']) . "</div>";
        $str .= "<div style='margin-top: 2%;'>OPENID：" . $vip['openid'] . "</div>";
        $str .= "</div>";
        $str .= "</div>";
        return $str;
        // 填充Html
    }

    // 获取子用户
    public function getChildren($id = 0,$page_array=null)
    {
        if($id == 0){
            $p = $page_array['p'];
            $psize = $page_array['psize'];
            $map = $page_array['map'];
            $vips = $this->where($map)->page($p, $psize)->order("total_yj desc")->select();
        }
        else {
            $vips = $this->where(array('pid' => $id))->select();
        }
        $morder = M('Shop_order');
        // 获取vips
        foreach ($vips as $k => $vip) {
            $count = $this->where(array('pid' => $vip['id']))->count();
            if ($count > 0) {
                $vips[$k]['type'] = 1;
                // 显示一级 数量
                $count = $this->where(array('pid' => $vip['id']))->count();
                if ($count <= 0) {
                    $count = 0;
                }
                $vips[$k]['count1'] = $count; // 一级会员数量
                // 显示二级 数量
                $arr = array();
                $tmp = $this->field('id')->where(array('pid' => $vip['id']))->select();
                foreach ($tmp as $v) {
                    array_push($arr, $v['id']);
                }
                $count = $this->where(array('pid' => array('in', in_parse_str($arr))))->count();
                if ($count <= 0) {
                    $count = 0;
                }
                $vips[$k]['count2'] = $count;
                // 显示三级 数量
                $arr = array();
                $tmp = $this->field('id')->where(array('pid' => $vip['id']))->select(); // 一级
                foreach ($tmp as $v) {
                    array_push($arr, $v['id']);
                }
                $tmp2 = $this->field('id')->where(array('pid' => array('in', in_parse_str($arr))))->select(); // 二级
                $arr2 = array();
                foreach ($tmp2 as $v) {
                    array_push($arr2, $v['id']);
                }
                $count = $this->where(array('pid' => array('in', in_parse_str($arr2))))->count(); // 三级
                if ($count <= 0) {
                    $count = 0;
                }
                $vips[$k]['count3'] = $count;
            } else {
                $vips[$k]['type'] = 0;
            }
            // 显示三级 数量
//            // 自己的
//            $vips[$k]['ocount'] = $morder->where(array('vipid' => $vip['id'], 'ispay' => 1))->count(); // 一级已支付
//            $vips[$k]['osum'] = $morder->where(array('vipid' => $vip['id'], 'ispay' => 1, 'payprice' => array('gt', 0)))->sum('payprice'); // 一级支付总金额
//            // 一级
//            $arr = array();
//            $tmp = $this->field('id')->where(array('pid' => $vip['id']))->select(); // 一级会员ID
//            foreach ($tmp as $v) {
//                array_push($arr, $v['id']);
//            }
//            $ocount = $morder->where(array('vipid' => array('in', in_parse_str($arr)), 'ispay' => 1))->count(); // 一级已支付
//            $osum = $morder->where(array('vipid' => array('in', in_parse_str($arr)), 'ispay' => 1, 'payprice' => array('gt', 0)))->sum('payprice'); // 一级支付总金额
//            $vips[$k]['ocount1'] = $ocount;
//            $vips[$k]['osum1'] = $osum;
//            // 二级
//            $tmp2 = $this->field('id')->where(array('pid' => array('in', in_parse_str($arr))))->select();
//            $arr2 = array();
//            foreach ($tmp2 as $v) {
//                array_push($arr2, $v['id']);
//            }
//            $ocount = $morder->where(array('vipid' => array('in', in_parse_str($arr2)), 'ispay' => 1))->count();
//            $osum = $morder->where(array('vipid' => array('in', in_parse_str($arr2)), 'ispay' => 1, 'payprice' => array('gt', 0)))->sum('payprice');
//            $vips[$k]['ocount2'] = $ocount;
//            $vips[$k]['osum2'] = $osum;
//            // 三级
//            $tmp3 = $this->field('id')->where(array('pid' => array('in', in_parse_str($arr2))))->select();
//            $arr3 = array();
//            foreach ($tmp3 as $v) {
//                array_push($arr3, $v['id']);
//            }
//            $ocount = $morder->where(array('vipid' => array('in', in_parse_str($arr3)), 'ispay' => 1))->count();
//            $osum = $morder->where(array('vipid' => array('in', in_parse_str($arr3)), 'ispay' => 1, 'payprice' => array('gt', 0)))->sum('payprice');
//            $vips[$k]['ocount3'] = $ocount;
//            $vips[$k]['osum3'] = $osum;
//            // 计算总金额
//            $vips[$k]['ocount'] = $vips[$k]['ocount'] + $vips[$k]['ocount1'] + $vips[$k]['ocount2'] + $vips[$k]['ocount3'];
//            $vips[$k]['osum'] = $vips[$k]['osum'] + $vips[$k]['osum1'] + $vips[$k]['osum2'] + $vips[$k]['osum3'];
//            $vips[$k]['osum'] = round($vips[$k]['osum'], 2);
        }
       // uasort($vips, array($this, 'compare'));
        return $vips ? $vips : array();
    }

    // 设置导师，只能设置一级用户
    public function setEmployee($id, $eid)
    {
        $vip = $this->where(array('id' => $id))->find();
        $employee = M('employee')->where(array('id' => $eid))->find();
        // 容错
        if (!($vip && $employee)) {
            return false;
        }
        // 一定要第一级并且没有上级员工
        if ($vip['old'] != 0) {
            return false;
        }
        $target = array();
        $tempp = array();
        $temps = array();
        $tempp[] = $id + 0;
        $mark = true;
        do {
            $temp = $this->field('id')->where(array('id' => array('in', in_parse_str($tempp))))->select();
            foreach ($temp as $v) {
                array_push($tempp, $v['id'] + 0);
            }
            $temp = $this->field('id')->where(array('pid' => array('in', in_parse_str($tempp))))->select();
            foreach ($temp as $v) {
                array_push($temps, $v['id'] + 0);
            }
            $target = array_merge($target, $tempp, $temps);
            if (count($temps) == 0) {
                $mark = false;
            } else {
                //$target = array_merge($target,$tempp,$temps);
                $tempp = $temps;
                $temps = array();
            }
        } while ($mark);
        $map = array();
        $map['employee'] = $eid;
        //return $target;
        $re = $this->where(array('id' => array('in', in_parse_str($target))))->save($map);
        // 发送信息给员工========================
        $empvip = $this->where(array('id' => $employee['vipid']))->find();
        if ($employee['vipid'] && $empvip) {
            $customeremp = M('Wx_customer')->where(array('type' => 'emp'))->find();
            $set = M('Set')->find();
            $options['appid'] = $set['wxappid'];
            $options['appsecret'] = $set['wxappsecret'];
            $wx = new \Util\Wx\Wechat($options);
            $shopset = M('Shop_set')->find();
            $cache = $this->where(array('id' => array('in', in_parse_str($target))))->select();
            foreach ($cache as $k => $v) {
                $msg = array();
                $msg['touser'] = $empvip['openid'];
                $msg['msgtype'] = 'text';
                $str = "[" . $v['nickname'] . "]通过您的推广，成为了您的[" . $shopset['fxname'] . "]，" . $customeremp['value'];
                $msg['text'] = array('content' => $str);
                //$ree = $wx->sendCustomMessage($msg);
            }
        }
        // 发送信息给员工========================
        if ($re) {
            return true;
        } else {
            return false;
        }
    }

    public function caculateVipAchievement($vips, $fxname)
    {
        $morder = M('Shop_order');
        // 循环统计
        $omap['status'] = array('in', array('2', '3', '5'));
        foreach ($vips as $k => $v) {
            $omap['vipid'] = $v['id'];
            $vips[$k]['success_order_payprice'] = round($morder->where($omap)->sum('payprice'), 2);
            if ($vips[$k]['isfx']) {
                $vips[$k]['fxname'] = $fxname;
            } else {
                $vips[$k]['fxname'] = '普通会员';
            }

        }
        uasort($vips, array($this, 'compare2'));
        return $vips;
    }

    public function vipReborn($vipid, $oldid)
    {
        $vip = $this->where(array('id' => $vipid))->find();
        $old = $this->where(array('id' => $oldid))->find();
        $vipset = M('Vip_set')->find();
        $shopset = M('Shop_set')->find();
        $set = M('Set')->find();

        if ($vip && $old) {

            $tj_score = $vipset['tj_score'];
            $tj_exp = $vipset['tj_exp'];
            $tj_money = $vipset['tj_money'];
            if ($tj_score || $tj_exp || $tj_money) {
                $msg = "推荐新用户奖励：<br>新用户：" . $vip['nickname'] . "<br>奖励内容：<br>";
                $mglog = "获得新用户注册奖励:";
                if ($tj_score) {
                    $old['score'] = $old['score'] + $tj_score;
                    $msg = $msg . $tj_score . "个积分<br>";
                    $mglog = $mglog . $tj_score . "个积分；";
                }
                if ($tj_exp) {
                    $old['exp'] = $old['exp'] + $tj_exp;
                    $msg = $msg . $tj_exp . "点经验<br>";
                    $mglog = $mglog . $tj_exp . "点经验；";
                }
                if ($tj_money) {
                    $old['money'] = $old['money'] + $tj_money;
                    $msg = $msg . $tj_money . "元余额<br>";
                    $mglog = $mglog . $tj_money . "元余额；";
                }
                $msg = $msg . "此奖励已自动打入您的帐户！感谢您的支持！";
                $rold = $this->save($old);
                if (FALSE !== $rold) {
                    $data_msg['pids'] = $old['id'];
                    $data_msg['title'] = "你获得一份推荐奖励！";
                    $data_msg['content'] = $msg;
                    $data_msg['ctime'] = time();
                    $rmsg = M('vip_message')->add($data_msg);
                    $data_mglog['vipid'] = $old['id'];
                    $data_mglog['nickname'] = $old['nickname'];
                    $data_mglog['xxnickname'] = $old['nickname'];
                    $data_mglog['msg'] = $mglog;
                    $data_mglog['ctime'] = time();
                    $rmglog = M('fx_log_tj')->add($data_mglog);
                }
            }
            //三层上线追溯统计
            // 三层上线追溯客服接口
            $old['total_xxlink'] = $old['total_xxlink'] + 1;
            $r1 = $this->save($old);
            // 上下级自定义及Wechat配置
            // $customerdown = M('Wx_customer')->where(array('type'=>'down'))->find();
            $customerup = M('Wx_customer')->where(array('type' => 'down'))->find();
            $options['appid'] = $set['wxappid'];
            $options['appsecret'] = $set['wxappsecret'];
            $wx = new \Util\Wx\Wechat($options);
            // 发送信息给自己===============
            //$msg = array();
            //$msg['touser'] = $vip['openid'];
            //$msg['msgtype'] = 'text';
            //$str = $customerdown['value'];
            //$msg['text'] = array('content'=>$str);
            //$ree = $wx->sendCustomMessage($msg);
            // 发送消息完成============
            // 发送信息给父级===============
            $msg = array();
            $msg['touser'] = $old['openid'];
            $msg['msgtype'] = 'text';
            $str = "[" . $vip['nickname'] . "]通过您的推广，成为了您的[" . $shopset['fx1name'] . "]，" . $customerup['value'];
            $msg['text'] = array('content' => $str);
            //$ree = $wx->sendCustomMessage($msg);
            // 发送消息完成============
            if ($old['pid']) {
                $oldold = $this->where('id=' . $old['pid'])->find();
                $oldold['total_xxlink'] = $oldold['total_xxlink'] + 1;
                $r2 = $this->save($oldold);
                // 发送信息给父级的父级===============
                $msg = array();
                $msg['touser'] = $oldold['openid'];
                $msg['msgtype'] = 'text';
                $str = "[" . $vip['nickname'] . "]通过您的推广，成为了您的[" . $shopset['fx2name'] . "]，" . $customerup['value'];
                $msg['text'] = array('content' => $str);
                //$ree = $wx->sendCustomMessage($msg);
                // 发送消息完成============
                if ($oldold['pid']) {
                    $oldoldold = $this->where('id=' . $oldold['pid'])->find();
                    $oldoldold['total_xxlink'] = $oldoldold['total_xxlink'] + 1;
                    $r3 = $this->save($oldoldold);
                    // 发送信息给父级的父级的父级===============
                    $msg = array();
                    $msg['touser'] = $oldoldold['openid'];
                    $msg['msgtype'] = 'text';
                    $str = "[" . $vip['nickname'] . "]通过您的推广，成为了您的[" . $shopset['fx3name'] . "]，" . $customerup['value'];
                    $msg['text'] = array('content' => $str);
                    //$ree = $wx->sendCustomMessage($msg);
                    // 发送消息完成============
                }
            }
            $vip['pid'] = $old['id'];
            $vip['path'] = $old['path'] . '-' . $old['id'];
            $vip['plv'] = $old['plv'] + 1;
            $vip['employee'] = $old['employee'];
            $rvip = $this->save($vip);
            if ($rvip) {
                return true;
            } else {
                return false;
            }
        } else {
            return false;
        }
    }

}

?>