<?php
// +----------------------------------------------------------------------
// | 交易完成-消费者消费额、应返金额计算
// +----------------------------------------------------------------------
namespace Common\Model;

use Think\Model;

class BuyerCommissionModel
{
    private $SHOP_SET = null;
    private $orderId = null;
    private $isAdminOperate = null;//调用者标识，app：微信中用户调用，admin：后台管理员调用
    private $orderType = null;//商城订单：0，商盟订单：1

    /**
     * 消费者交易完成后，修改消费者消费金额、应返金额等数据
     * @param $orderId 订单ID
     * @param null $SHOP_SET 商城设置，如果为null，使用默认配置
     * @param null $invoker 调用者标识，app：微信中用户调用，admin：后台管理员调用
     * @param false $orderType 订单类型（0：商城订单，1：商盟订单）
     * @return mixed
     */
    public function process($orderId, $SHOP_SET = null, $invoker = null, $orderType = 0)
    {
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
     * 更新消费者数据
     *
     * @param $vipId
     * @param $data ['order_id'=>0,'total_price'=>0,'pay_price'=>0]
     * @return mixed
     */
    private function updateVipInfo($vipId, $data)
    {
        $mviptradelog = M('vip_trade_success_log');
        $tradelogrlt = $mviptradelog->where(['oid' => $data['order_id'], 'order_type' => $this->orderType])->getField('id');
        if ($tradelogrlt) {
            $info['status'] = 0;
            $info['msg'] = '已经计算过，不能重复计算';
            return $info;
        }
        //追入会员信息
        $vip = M('Vip')->where('id=' . $vipId)->find();
        if (!$vip) {
            $info['status'] = 0;
            $info['msg'] = '未正常获取此订单的会员信息！';
            return $info;
        }

        //TODO 修改会员账户金额、经验、积分、等级
//            $data_vip['score'] = array('exp', 'score+' . round($order['payprice'] * self::$CMS['vipset']['cz_score'] / 100));
//            if (self::$CMS['vipset']['cz_exp'] > 0) {
//                $data_vip['exp'] = array('exp', 'exp+' . round($order['payprice'] * self::$CMS['vipset']['cz_exp'] / 100));
//                $data_vip['cur_exp'] = array('exp', 'cur_exp+' . round($order['payprice'] * self::$CMS['vipset']['cz_exp'] / 100));
//                $level = $this->getLevel($vip['cur_exp'] + round($order['payprice'] * self::$CMS['vipset']['cz_exp'] / 100));
//                $data_vip['levelid'] = $level['levelid'];
//                //TODO 会员分销统计字段
//                //会员购买一次变成分销商
//                $data_vip['isfx'] = 1;
//            }
        $viplog['total_bonus_amount'] = $vip['total_bonus_amount'];
        $viplog['total_buy'] = $vip['total_buy'];
        $viplog['total_price'] = $data['total_price'];
        $viplog['pay_price'] = $data['pay_price'];
        $viplog['order_type'] = $this->orderType;


        //消费金额累计
        $data_vip['total_buy'] = $viplog['total_buy'] + $viplog['total_price'];
        //应该金额累计
        $data_vip['total_bonus_amount'] = $viplog['total_bonus_amount'] + $viplog['total_price'];

        $re = M('vip')->where(['id' => $vip['id']])->save($data_vip);

        if (FALSE !== $re) {
            //更新资料成功
            $viplog['status'] = 1;
            $info['status'] = 1;
            $info['total_price'] = $viplog['total_price'];
            $info['msg'] = '更新会员资料成功';
        } else {
            //更新资料失败
            $viplog['status'] = 0;
            $info['status'] = 2;
            $info['msg'] = '更新会员资料失败';
        }

        $viplog['oid'] = $data['order_id'];
        $viplog['vip_id'] = $vip['id'];
        $viplog['vip_nickname'] = $vip['nickname'];
        $viplog['ctime'] = time();

        $refxlog = $mviptradelog->add($viplog);
        if (!$refxlog) {
            file_put_contents('./Data/app_buyer_error.txt', '错误日志时间:' . date('Y-m-d H:i:s') . PHP_EOL . '错误纪录信息:' . $refxlog . PHP_EOL . PHP_EOL . $mviptradelog->getLastSql() . PHP_EOL . PHP_EOL, FILE_APPEND);
        }

        return $info;
    }

    /**
     * 商盟订单
     */
    private function processShangMengOrder()
    {
        //获取订单
        $m = M('supplier_order');
        $map['id'] = $this->orderId;
        $order = $m->where($map)->find();
        if (!$order) {
            $info['status'] = 0;
            $info['msg'] = '操作失败！';
            return $info;
        }
        //已完成的订单进行操作
        if ($order['status'] != 2) {
            $info['status'] = 0;
            $info['msg'] = '操作失败！';
            return $info;
        }

        $rlt = $this->updateVipInfo($order['vip_buyer_id'], ['order_id' => $order['id'], 'total_price' => $order['total_price'], 'pay_price' => $order['pay_price']]);
        if (empty($rlt['status'])) {
            //不存在此用户
            return $rlt;
        }

        $wechatTemplate = D('WechatTemplate');
        $data_tpl = array("to_user" => $order["vip_buyer_openid"], "order_id" => $order["id"], "order_code" => $order["order_code"], "end_time" => $order["end_time"]);
        $wechatTemplate->sendMessage_OrderTradeSuccess($data_tpl);

        $mlog = M('supplier_order_log');
        $dlog['oid'] = $order['id'];
        $dlog['msg'] = '交易完成';
        $dlog['type'] = 5;
        $dlog['ctime'] = time();
        $rlog = $mlog->add($dlog);

        //支付类型（money:余额支付，alipayApp:支付宝手机客户端，wxpay:微信支付，offline:线下打款）
        //后端日志
        $mlog = M('supplier_order_syslog');
        $dlog['oid'] = $order['id'];
        $dlog['msg'] = $this->isAdminOperate ? '交易完成' : '交易完成';
        $dlog['paytype'] = $order['pay_type'];
        $dlog['ctime'] = time();
        $rlog = $mlog->add($dlog);


        $info['status'] = 1;
        $info['msg'] = $this->isAdminOperate ? '确认操作完成！' : '交易已完成，感谢您的支持！';
        $info['total_price'] = $rlt['total_price'];
        return $info;
    }

    /**
     * 商城订单
     */
    private function processShopOrder()
    {
        //获取订单
        $m = M('shop_order');
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

        $rlt = $this->updateVipInfo($order['vipid'], ['order_id' => $order['id'], 'total_price' => $order['totalprice'], 'pay_price' => $order['payprice']]);
        if (empty($rlt['status'])) {
            //不存在此用户
            return $rlt;
        }
        $wechatTemplate = D('WechatTemplate');
        $data_tpl = array("to_user" => $order["vipopenid"], "order_id" => $order["id"], "order_code" => $order["oid"], "end_time" => $order["etime"]);
        $wechatTemplate->sendMessage_OrderTradeSuccess($data_tpl);

        $mlog = M('shop_order_log');
        $dlog['oid'] = $order['id'];
        $dlog['type'] = 5;
        $dlog['msg'] = '确认收货,交易完成。';
        $dlog['ctime'] = time();
        $rlog = $mlog->add($dlog);

        //后端日志
        $mlog = M('shop_order_syslog');
        $dlog['oid'] = $order['id'];
        $dlog['msg'] = $this->isAdminOperate ? '交易完成-后台点击' : '交易完成-会员点击';
        $dlog['paytype'] = $order['paytype'];
        $dlog['ctime'] = time();
        $rlog = $mlog->add($dlog);


        $info['status'] = 1;
        $info['msg'] = $this->isAdminOperate ? '确认收货操作完成！' : '交易已完成，感谢您的支持！';
        $info['total_price'] = $rlt['total_price'];
        return $info;
    }

}

?>