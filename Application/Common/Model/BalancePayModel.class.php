<?php
// +----------------------------------------------------------------------
// | 余额支付
// +----------------------------------------------------------------------
namespace Common\Model;

class BalancePayModel
{

    private $orderCode = null;
    private $orderType = null;

    /**
     * 现金支付
     * @param $orderCode
     * @param $orderType
     * @return mixed
     */
    public function pay($orderCode, $orderType)
    {
        $this->orderCode = $orderCode;
        $this->orderType = $orderType;
        if ($orderType == OT_SM) {
            return $this->paySM();
        } else {
            return $this->paySC();
        }
    }

    /**
     * @param $vipId
     * @param $payPrice
     * @return bool|int true:成功，false:失败，-1:余额不足
     */
    private function updateVipMoney($vipId, $payPrice)
    {
        $mvip = M('Vip');
        $vip = $mvip->where(['id' => $vipId])->find();

        $pp = $vip['money'] - $payPrice;

        if ($pp >= 0) {
            $re = $mvip->where(['id' => $vipId])->setDec('money', $payPrice);
            if ($re !== false) {
                return true;
            }
            return false;
        } else {
            return -1;
        }
    }

    private function paySM()
    {
        $order = M('Supplier_order')->where(['order_code' => $this->orderCode])->find();
        if (empty($order)) {
            $info['status'] = 1;
            $info['msg'] = '订单不存在';
            return $info;
        }
        $payPrice = $order['pay_price'];

        $vipId = $order['vip_buyer_id'];

        $re = $this->updateVipMoney($vipId, $payPrice);
        if ($re === true) {
            //支付成功
            $endpay = D('EndPay');
            $rlt = $endpay->endpay($this->orderCode, PT_MONEY);
            return $rlt;
        } else if ($re === -1) {
            $info['status'] = 4;
            $info['msg'] = '余额不足，请使用其它方式付款！';
        } else {
            //支付失败
            $mlog = M('Supplier_order_syslog');
            $dlog['oid'] = $this->orderCode;
            $dlog['msg'] = '余额付款失败';
            $dlog['type'] = -1;
            $dlog['ctime'] = time();
            $rlog = $mlog->add($dlog);

            $info['status'] = 3;
            $info['msg'] = '余额付款失败！请联系客服！';
        }
        return $info;
    }

    private function paySC()
    {
        $order = M('Shop_order')->where(['oid' => $this->orderCode])->find();
        if (empty($order)) {
            $info['status'] = 1;
            $info['msg'] = '订单不存在';
            return $info;
        }
        $payPrice = $order['payprice'];

        $vipId = $order['vipid'];

        $re = $this->updateVipMoney($vipId, $payPrice);
        if ($re === true) {
            //支付成功
            $endpay = D('EndPay');
            $rlt = $endpay->endpay($this->orderCode, PT_MONEY);
            return $rlt;
        } else if ($re === -1) {
            $info['status'] = 4;
            $info['msg'] = '余额不足，请使用其它方式付款！';
        } else {
            //支付失败
            $mlog = M('Shop_order_syslog');
            $dlog['oid'] = $this->orderCode;
            $dlog['msg'] = '余额付款失败';
            $dlog['type'] = -1;
            $dlog['ctime'] = time();
            $rlog = $mlog->add($dlog);

            $info['status'] = 3;
            $info['msg'] = '余额付款失败！请联系客服！';
        }

        return $info;
    }
}


?>
