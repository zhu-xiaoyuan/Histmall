<?php
// +----------------------------------------------------------------------
// | 交易完成-团长佣金计算
// +----------------------------------------------------------------------
namespace Common\Model;

use Think\Model;

class TuanZhangCommissionModel
{
    private $SHOP_SET = null;
    private $orderId = null;
    private $isAdminOperate = null;//调用者标识，app：微信中用户调用，admin：后台管理员调用
    private $orderType = null;//商城订单：0，商盟订单：1

    /**
     * 处理&计算佣金
     * @param $orderId 订单ID
     * @param null $SHOP_SET 商城设置，如果为null，使用默认配置
     * @param null $invoker 调用者标识，app：微信中用户调用，admin：后台管理员调用
     * @param false $orderType 订单类型（0：商城订单，1：商盟订单）
     * @return mixed
     */
    public function process($orderId, $SHOP_SET = null, $invoker = null, $orderType = 0)
    {
        //TODO 1）判断order中商品所属商家；2）查找店铺推荐人；3）计算提成

        $this->orderId = $orderId;
        $this->isAdminOperate = (empty($invoker) || $invoker != 'app') ? true : false;
        $this->orderType = empty($orderType) ? 0 : 1;
        //TODO 获取佣金比例配置
        if (empty($SHOP_SET)) {
            $this->SHOP_SET = M('shop_set')->find();
        } else {
            $this->SHOP_SET = $SHOP_SET;
        }

        if ($orderType) {
            return $this->processShangMengOrder();
        } else {
            return $this->processShopOrder();
        }
    }

    /**
     * 处理&计算 商盟订单 佣金
     */
    private function processShangMengOrder()
    {
        //获取订单
        $m = M('Supplier_order');
        $map['id'] = $this->orderId;
        $order = $m->where($map)->find();
        if (!$order) {
            $info['status'] = 0;
            $info['msg'] = '操作失败！';
            return $info;
        }
        if ($order['status'] != 2) {
            $info['status'] = 0;
            $info['msg'] = '操作失败！';
            return $info;
        }

        //检查是否已经拿过提成
        $mtzlog = M('tuanzhang_tc_log');
        $tzlog = $mtzlog->where(['oid' => $order['id'], 'order_type' => $this->orderType])->getField('id');
        if ($tzlog) {
            $info['status'] = 0;
            $info['msg'] = '已经计算过佣金，不能重复计算';
            return $info;
        }

        $mvip = M('vip');
        $vip = $mvip->where('id=' . $order['vip_buyer_id'])->find();
        if (!$vip) {
            $info['status'] = 0;
            $info['msg'] = '未正常获取此订单的会员信息！';
            return $info;
        }

        $msupplier = M('supplier');

        $tzlog = [];
        $tzlog['oid'] = $order['id'];
        $tzlog['order_price'] = $order['total_price'];
        $tzlog['ctime'] = time();
        $tzlog['order_type'] = $this->orderType;
        $tzlog['from'] = $vip['id'];
        $tzlog['fromname'] = $vip['nickname'];

        $totalTcPrice = 0;
        $supplier = $msupplier->where('id=' . $order['supplier_id'])->find();
        if ($supplier) {

            $inviterId = $supplier['inviter_id'];
            if (!empty($inviterId)) {

                $inviter = $mvip->where(['id' => $inviterId])->find();
                if ($inviter && $inviter['is_tuanzhang']) {
                    //TODO 只有团长才计算提成
                    $tc = $this->getTc($order['total_price']);
                    $tzlog['tc'] = $tc;

                    $tzvipdata['total_tuanzhang_tc'] = $inviter['total_tuanzhang_tc'] + $tc;
                    $tzvipdata['money'] = $inviter['money'] + $tc;
                    $rfx = $mvip->where(['id' => $inviter['id']])->save($tzvipdata);

                    $tzlog['goods_id'] = 0;
                    $tzlog['goods_total_price'] = 0;
                    $tzlog['to'] = $inviter['id'];
                    $tzlog['toname'] = $inviter['nickname'];
                    $tzlog['supplier_id'] = $order['supplier_id'];
                    $tzlog['store_id'] = $order['store_id'];

                    if (FALSE !== $rfx) {
                        //佣金发放成功
                        $totalTcPrice += $tc;
                        $tzlog['status'] = 1;

                        $wechatTemplate = D('WechatTemplate');
                        $wechatTemplate->sendMessage_FxCommission([
                            'to_user' => $inviter['openid'],
                            'commission_price' => $tc,
                            'commission_time' => $tzlog['ctime']
                        ]);

                    } else {
                        //佣金发放失败
                        $tzlog['status'] = 0;
                    }

                    $refxlog = $mtzlog->add($tzlog);
                    if (!$refxlog) {
                        file_put_contents('./Data/app_tuanzhang_tc_error.txt', '错误日志时间:' . date('Y-m-d H:i:s') . PHP_EOL . '错误纪录信息:' . $refxlog . PHP_EOL . PHP_EOL . $mtzlog->getLastSql() . PHP_EOL . PHP_EOL, FILE_APPEND);
                    }
                }
            }
        }
        $info['status'] = 1;
        $info['msg'] = '提成计算完毕';
        $info['total_price'] = $totalTcPrice;
        return $info;
    }

    /**
     * 处理&计算 商城订单 佣金
     */
    private function processShopOrder()
    {
        //获取订单
        $m = M('Shop_order');
        $map['id'] = $this->orderId;
        $order = $m->where($map)->find();
        if (!$order) {
            $info['status'] = 0;
            $info['msg'] = '操作失败！';
            return $info;
        }
        if ($order['status'] != 5) {
            $info['status'] = 0;
            $info['msg'] = '操作失败！';
            return $info;
        }

        //检查是否已经拿过提成
        $tzlog = M('tuanzhang_tc_log')->where(['oid' => $order['id'], 'order_type' => $this->orderType])->getField('id');
        if ($tzlog) {
            $info['status'] = 0;
            $info['msg'] = '已经计算过佣金，不能重复计算';
            return $info;
        }

        //追入会员信息
        $vip = M('Vip')->where('id=' . $order['vipid'])->find();
        if (!$vip) {
            $info['status'] = 0;
            $info['msg'] = '未正常获取此订单的会员信息！';
            return $info;
        }

        $mvip = M('vip');
        $msupplier = M('supplier');
        $mtzlog = M('tuanzhang_tc_log');
        $tzlog = [];
        $tzlog['oid'] = $order['id'];
        $tzlog['order_price'] = $order['totalprice'];
        $tzlog['ctime'] = time();
        $tzlog['order_type'] = $this->orderType;
        $tzlog['from'] = $vip['id'];
        $tzlog['fromname'] = $vip['nickname'];

        $totalTcPrice = 0;//总提成

        $viaTcMap = [];//openid=>tc

        $goodsItems = unserialize($order['items']);
        //循环每个产品团长应得提成
        foreach ($goodsItems as $k => $item) {

            $supplier = $msupplier->where('id=' . $item['supplier_id'])->find();
            if (!$supplier) {
                continue;
            }
            $inviterId = $supplier['inviter_id'];
            if (empty($inviterId)) {
                //TODO 没有指定商家店铺，默认平台所有
                continue;
            }
            $inviter = $mvip->where(['id' => $inviterId])->find();
            if (!$inviter || !$inviter['is_tuanzhang']) {
                //TODO 没有此人，或不是团长
                continue;
            }

            //团长提成计算
            $tc = money_floor($this->getTc($item['total']));
            $tzlog['tc'] = $tc;

            $tzvipdata['total_tuanzhang_tc'] = $inviter['total_tuanzhang_tc'] + $tc;
            $tzvipdata['money'] = $inviter['money'] + $tc;
            $rfx = $mvip->where(['id' => $inviter['id']])->save($tzvipdata);

            $tzlog['goods_id'] = $item['goodsid'];
            $tzlog['goods_total_price'] = $item['total'];
            $tzlog['supplier_id'] = $item['supplier_id'];
            $tzlog['store_id'] = $item['store_id'];
            $tzlog['to'] = $inviter['id'];
            $tzlog['toname'] = $inviter['nickname'];

            if (FALSE !== $rfx) {
                //佣金发放成功
                $tzlog['status'] = 1;

                $totalTcPrice += $tc;
                $tt_tc = $viaTcMap[$inviter['openid']];
                if ($tt_tc) {
                    $viaTcMap[$inviter['openid']] = $tt_tc + $tc;
                } else {
                    $viaTcMap[$inviter['openid']] = $tc;
                }

            } else {
                //佣金发放失败
                $tzlog['status'] = 0;
            }

            $refxlog = $mtzlog->add($tzlog);
            if (!$refxlog) {
                file_put_contents('./Data/app_tuanzhang_tc_error.txt', '错误日志时间:' . date('Y-m-d H:i:s') . PHP_EOL . '错误纪录信息:' . $refxlog . PHP_EOL . PHP_EOL . $mtzlog->getLastSql() . PHP_EOL . PHP_EOL, FILE_APPEND);
            }
        }
        
        if ($viaTcMap) {
            $wechatTemplate = D('WechatTemplate');
            $time = time();
            foreach ($viaTcMap as $k => $v) {
                $wechatTemplate->sendMessage_FxCommission([
                    'to_user' => $k,
                    'commission_price' => $v,
                    'commission_time' => $time
                ]);
            }
        }

        $info['status'] = 1;
        $info['msg'] = '提成计算完毕';
        $info['total_price'] = $totalTcPrice;
        return $info;
    }

    /**
     * 计算团长提成
     *
     */
    private function getTc($price)
    {
        return ($price * $this->SHOP_SET['total_commission_rate'] * 0.01) * ($this->SHOP_SET['tuanzhang_rate'] * 0.01);
    }

}

?>