<?php
// +----------------------------------------------------------------------
// | 交易完成-商家佣金计算
// +----------------------------------------------------------------------
namespace Common\Model;

use Think\Model;

class SupplierCommissionModel
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
        $mtzlog = M('supplier_trade_syslog');
        $tzlog = $mtzlog->where(['oid' => $order['id'], 'order_type' => $this->orderType])->getField('id');
        if ($tzlog) {
            $info['status'] = 0;
            $info['msg'] = '已经计算过，不能重复计算';
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

        $supplier = $msupplier->where('id=' . $order['supplier_id'])->find();
        if ($supplier) {
            //
            $seller = $mvip->where(['id' => $order['vip_seller_id']])->find();
            if ($seller) {
                $tzlog['to'] = $seller['id'];
                $tzlog['toname'] = $seller['nickname'];
            } else {
                $tzlog['to'] = $order['vip_seller_id'];
            }

            $tzlog['goods_id'] = 0;
            $tzlog['goods_total_price'] = 0;
            $tzlog['supplier_id'] = $order['supplier_id'];
            $tzlog['store_id'] = $order['store_id'];

            $supplierData['total_order'] = array('exp', 'total_order+1');
            $supplierData['total_money'] = array('exp', 'total_money+' . $tzlog['order_price']);
            //商盟订单
            $supplierData['total_offline_order'] = array('exp', 'total_offline_order+1');
            $supplierData['total_offline_money'] = array('exp', 'total_offline_money+' . $tzlog['order_price']);


            $rfx = $msupplier->where(['id' => $supplier['id']])->save($supplierData);

            if (FALSE !== $rfx) {
                //佣金发放成功
                $tzlog['status'] = 1;
            } else {
                //佣金发放失败
                $tzlog['status'] = 0;
            }

            $refxlog = $mtzlog->add($tzlog);
            if (!$refxlog) {
                file_put_contents('./Data/app_supplier_trade_error.txt', '错误日志时间:' . date('Y-m-d H:i:s') . PHP_EOL . '错误纪录信息:' . $refxlog . PHP_EOL . PHP_EOL . $mtzlog->getLastSql() . PHP_EOL . PHP_EOL, FILE_APPEND);
            }
        }
        $info['status'] = 1;
        $info['msg'] = '计算完毕';
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
        $tzlog = M('supplier_trade_syslog')->where(['oid' => $order['id'], 'order_type' => $this->orderType])->getField('id');
        if ($tzlog) {
            $info['status'] = 0;
            $info['msg'] = '已经计算过，不能重复计算';
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
        $mtzlog = M('supplier_trade_syslog');
        $tzlog = [];
        $tzlog['oid'] = $order['id'];
        $tzlog['order_price'] = $order['totalprice'];
        $tzlog['ctime'] = time();
        $tzlog['order_type'] = $this->orderType;
        $tzlog['from'] = $vip['id'];
        $tzlog['fromname'] = $vip['nickname'];

        $goodsItems = unserialize($order['items']);
        //循环每个产品
        foreach ($goodsItems as $k => $item) {

            if (empty($item['supplier_id'])) {
                continue;
            }

            $supplier = $msupplier->where('id=' . $item['supplier_id'])->find();
            if (!$supplier) {
                continue;
            }

            $seller = $mvip->where(['supplier_id' => $item['supplier_id']])->find();
            if ($seller) {
                $tzlog['to'] = $seller['id'];
                $tzlog['toname'] = $seller['nickname'];
            }

            $tzlog['goods_id'] = $item['goodsid'];
            $tzlog['goods_total_price'] = $item['total'];
            $tzlog['supplier_id'] = $item['supplier_id'];
            $tzlog['store_id'] = $item['store_id'];

            $supplierData['total_order'] = array('exp', 'total_order+1');
            $supplierData['total_money'] = array('exp', 'total_money+' . $tzlog['goods_total_price']);
            //商城订单
            $supplierData['total_online_order'] = array('exp', 'total_online_order+1');
            $supplierData['total_online_money'] = array('exp', 'total_online_money+' . $tzlog['goods_total_price']);

            $rfx = $msupplier->where(['id' => $supplier['id']])->save($supplierData);

            if (FALSE !== $rfx) {
                //成功
                $tzlog['status'] = 1;
            } else {
                //失败
                $tzlog['status'] = 0;
            }

            $refxlog = $mtzlog->add($tzlog);
            if (!$refxlog) {
                file_put_contents('./Data/app_supplier_trade_error.txt', '错误日志时间:' . date('Y-m-d H:i:s') . PHP_EOL . '错误纪录信息:' . $refxlog . PHP_EOL . PHP_EOL . $mtzlog->getLastSql() . PHP_EOL . PHP_EOL, FILE_APPEND);
            }
        }

        $info['status'] = 1;
        $info['msg'] = '计算完毕';
        return $info;
    }
}

?>