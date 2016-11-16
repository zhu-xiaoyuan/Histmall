<?php
// +----------------------------------------------------------------------
// | 封装 支付完成 后的操作处理逻辑
// +----------------------------------------------------------------------
namespace Common\Model;

class EndPayModel
{
    private $orderCode = null;
    private $payType = null;

    public function endpay($orderCode, $payType)
    {
        $this->orderCode = $orderCode;
        $this->payType = $payType;

        if (strlen($orderCode) < 2) {
            $info['status'] = 1;
            $info['msg'] = '订单参数不完整！请重新尝试！';
            return $info;
        }

        //提取订单类型（SM：商盟，SC：商城）
        $prefix = strtoupper(substr($orderCode, 0, 2));
        if ($prefix == 'SM') {
            //商盟订单
            return $this->endpay_sm();
        } else {
            //SC
            //商城订单
            return $this->endpay_sc();
        }
    }

    /**
     * 付款成功后 - 商盟订单处理
     */
    private function endpay_sm()
    {
        $m = M('Supplier_order');
        $order = $m->where(array('order_code' => $this->orderCode))->find();
        if ($order && $order['status'] == 0 && $order["is_pay"] == 0) {//已下单,未支付
            //修改状态
            $updateData['is_pay'] = 1;
            $updateData['pay_time'] = time();
            $updateData['pay_type'] = $this->payType;
            $re = $m->where(['id' => $order['id'], 'status' => 0, 'is_pay' => 0])->save($updateData);
            if (FALSE !== $re) {
                //记录日志
                $mlog = M('Supplier_order_log');
                $mslog = M('Supplier_order_syslog');
                $dlog['oid'] = $order['id'];
                $dlog['msg'] = getPayTypeMsg($this->payType) . '付款成功';
                $dlog['ctime'] = time();
                $dlog['type'] = 2;
                $mlog->add($dlog);

                $dlog['paytype'] = $order['pay_type'];
                $mslog->add($dlog);

                //TODO 发送模板消息，Redis队列

                $store = M('supplier_store')->where(['id' => $order['store_id']])->find();

                if ($store) {
                    $msgOrderProduct = $store['name'] . '-消费';
                } else {
                    $msgOrderProduct = '商盟-消费';
                }
                //TODO 给消费者发
                /* $wechatTemplate = D('WechatTemplate');
                 $msgData = [
                     'touser' => $order['vip_buyer_openid'],
                     'title' => '',
                     'url' => '',
                     'order_product' => $msgOrderProduct,
                     'order_id' => $order['id'],
                     'order_code' => $order['order_code'],
                     'vip_id' => $order['vip_buyer_id'],
                     'pay_price' => $order['pay_price'],
                     'total_price' => $order['total_price'],
                     'pay_type' => $order['pay_type'],
                     'pay_time' => $order['pay_time']
                 ];
                 $wechatTemplate->sendMessage_PayOrder($msgData);*/

                $wechatTemplate = D('WechatTemplate');
                $data_tpl = array("to_user" => $order["vip_buyer_openid"], "pay_price" => $order['pay_price'], "order_product" => $msgOrderProduct);
                $wechatTemplate->sendMessage_PayOrder($data_tpl);

                $info['status'] = 0;
                $info['msg'] = '订单支付成功';

                //TODO 用户在线支付，直接转为完成状态，并且开始走分佣逻辑
                if ($order['is_payforother'] == 0) {
                    $re = $m->where(['id' => $order['id'], 'is_pay' => 1])->save(['status' => 2, 'end_time' => time()]);
                    if (FALSE !== $re) {
                        $commission = D('Commission');
                        $rlt = $commission->process($order['id'], null, 'app', OT_SM);
                    } else {
                        //后端日志
                        $sdlog['oid'] = $order['id'];
                        $sdlog['msg'] = '确认完成失败';
                        $sdlog['type'] = -1;
                        $sdlog['paytype'] = $order['pay_type'];
                        $sdlog['ctime'] = time();
                        $rlog = $mslog->add($sdlog);
                    }
                }
            } else {
                //记录报警信息
                $str = "订单号：" . $this->orderCode . "支付成功但未更新订单状态！";
                file_put_contents('./Data/app_error.txt', '移动支付报警:' . date('Y-m-d H:i:s') . PHP_EOL . '通知信息:' . $str . PHP_EOL . PHP_EOL . '交易类型:TRADE_SUCCESS' . PHP_EOL . PHP_EOL, FILE_APPEND);
                $info['status'] = 0;
                $info['msg'] = '用户已支付，但修改订单状态失败';
            }
        } else {
            $info['status'] = 1;
            $info['msg'] = '订单不存在，或状态不正确';
        }
        return $info;
    }

    /**
     * 付款成功后 - 商城订单处理
     */
    private function endpay_sc()
    {
        $m = M('Shop_order');
        $order = $m->where(array('oid' => $this->orderCode))->find();   //要改变状态的订单
        if ($order && $order['status'] == 1) {//未支付
            //修改状态
            $updateData['ispay'] = 1;
            $updateData['status'] = 2;//已付款
            $updateData['paytime'] = time();
            $updateData['paytype'] = $this->payType;
            $re = $m->where(['id' => $order['id'], 'status' => 1])->save($updateData);
            //2016年8月19日13:39:32  增加团购订单的逻辑
            if($order['is_group_buy']){
                //如果是团购，则根据group_buy_id更新group_buy表信息，如果group_buy_id为0，则说明是开团，否则为拼团

                //如果是团购，则商品只有一件，所以现在的写法没有问题
                $goodsInfo = unserialize($order['items']);
                $goodsid = $goodsInfo[0]['goodsid'];    //获取商品id

                if($order['group_buy_id']){     //不为0
                    $group_buy_id = $order['group_buy_id'];
                    $group_buy_order = M('group_buy')->where('id='.$order['group_buy_id'])->find();
                    $group_need_people_num = M('shop_goods')->where('id='.$goodsid)->getField('group_buy_num');//组团所需人数
                    $groupData['update_time'] = time();
                    $groupData['people_num'] = $group_buy_order['people_num'] + 1;
                    if($groupData['people_num'] >= $group_need_people_num){
                        $groupData['status'] = 2;
                    }
                    $re2 = M('group_buy')->where(['id'=>$order['group_buy_id']])->save($groupData);
                }else{
                    //gorup_buy_id 为 0 则为开团
                    $groupData['goods_id'] = $goodsid;
                    $groupData['creator_id'] = $order['vipid'];
                    $groupData['create_time'] = time();
                    $groupData['people_num'] = 1;
                    $groupData['status'] = 0;
                    $re3 = $group_buy_id = M('group_buy')->add($groupData);
                    if($re3){
                        M('shop_order')->where('id='.$order['id'])->save(['group_buy_id'=>$re3]);
                    }
                }
            }


            if (FALSE !== $re) {
                //销量计算-只减不增
                $rsell = $this->doSells($order);
                //记录日志
                $mlog = M('Shop_order_log');
                $mslog = M('Shop_order_syslog');
                $dlog['oid'] = $order['id'];
                $dlog['msg'] = getPayTypeMsg($this->payType) . '付款成功';
                $dlog['ctime'] = time();
                $dlog['type'] = 2;
                $mlog->add($dlog);

                $dlog['paytype'] = $order['paytype'];
                $mslog->add($dlog);

                //TODO 发送模板消息，Redis队列

                $products = unserialize($order['items']);
                if (empty($products)) {
                    $msgOrderProduct = '商城-消费';
                } else {
                    $msgOrderProduct = $products[0]['name'];
                }
                // 给消费者发
                $wechatTemplate = D('WechatTemplate');
                $msgOrder = $m->where(['id' => $order['id']])->find();
                //TODO 给消费者发
                $msgData = [
                    'touser' => $order['vipopenid'],
                    'title' => '',
                    'url' => '',
                    'order_product' => $msgOrderProduct,
                    'order_id' => $msgOrder['id'],
                    'order_code' => $msgOrder['oid'],
                    'vip_id' => $msgOrder['vipid'],
                    'pay_price' => $msgOrder['payprice'],
                    'total_price' => $msgOrder['totalprice'],
                    'pay_type' => $msgOrder['paytype'],
                    'pay_time' => $msgOrder['paytime'],
                    'is_group_buy' => $order['is_group_buy']
                ];
                $wechatTemplate->sendMessage_PayOrder($msgData);

                $info['group_buy_id'] = $group_buy_id;
                $info['status'] = 0;
                $info['msg'] = '订单支付成功';
            } else {
                //记录报警信息
                $str = "订单号：" . $this->orderCode . "支付成功但未更新订单状态！";
                file_put_contents('./Data/app_error.txt', '移动支付报警:' . date('Y-m-d H:i:s') . PHP_EOL . '通知信息:' . $str . PHP_EOL . PHP_EOL . '交易类型:TRADE_SUCCESS' . PHP_EOL . PHP_EOL, FILE_APPEND);
                $info['status'] = 0;
                $info['msg'] = '用户已支付，但修改订单状态失败';
            }
        } else {
            $info['status'] = 1;
            $info['msg'] = '订单不存在，或状态不正确';
        }
        return $info;
    }

    //销量计算
    private function doSells($order)
    {
        $mgoods = M('Shop_goods');
        $msku = M('Shop_goods_sku');
        $mlogsell = M('Shop_syslog_sells');
        //封装dlog
        $dlog['oid'] = $order['id'];
        $dlog['vipid'] = $order['vipid'];
        $dlog['vipopenid'] = $order['vipopenid'];
        $dlog['vipname'] = $order['vipname'];
        $dlog['ctime'] = time();
        $items = unserialize($order['items']);
        $tmplog = array();
        foreach ($items as $k => $v) {
            //销售总量
            $dnum = $dlog['num'] = $v['num'];
            if ($v['skuid']) {
                $rg = $mgoods->where('id=' . $v['goodsid'])->setDec('num', $dnum);
                $rg = $mgoods->where('id=' . $v['goodsid'])->setInc('sells', $dnum);
                $rg = $mgoods->where('id=' . $v['goodsid'])->setInc('dissells', $dnum);
                $rs = $msku->where('id=' . $v['skuid'])->setDec('num', $dnum);
                $rs = $msku->where('id=' . $v['skuid'])->setInc('sells', $dnum);
                //sku模式
                $dlog['goodsid'] = $v['goodsid'];
                $dlog['goodsname'] = $v['name'];
                $dlog['skuid'] = $v['skuid'];
                $dlog['skuattr'] = $v['skuattr'];
                $dlog['price'] = $v['price'];
                $dlog['num'] = $v['num'];
                $dlog['total'] = $v['total'];
            } else {
                $rg = $mgoods->where('id=' . $v['goodsid'])->setDec('num', $dnum);
                $rg = $mgoods->where('id=' . $v['goodsid'])->setInc('sells', $dnum);
                $rg = $mgoods->where('id=' . $v['goodsid'])->setInc('dissells', $dnum);
                //纯goods模式
                $dlog['goodsid'] = $v['goodsid'];
                $dlog['goodsname'] = $v['name'];
                $dlog['skuid'] = 0;
                $dlog['skuattr'] = 0;
                $dlog['price'] = $v['price'];
                $dlog['num'] = $v['num'];
                $dlog['total'] = $v['total'];
            }
            array_push($tmplog, $dlog);
        }
        if (count($tmplog)) {
            $rlog = $mlogsell->addAll($tmplog);
        }
        return true;
    }

}

?>
