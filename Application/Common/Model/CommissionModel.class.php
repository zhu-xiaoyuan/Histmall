<?php
// +----------------------------------------------------------------------
// | 佣金计算：消费者、分销商、商家、团长
// +----------------------------------------------------------------------
namespace Common\Model;

use Think\Model;

class CommissionModel
{
    private $SHOP_SET = null;
    private $isAdminOperate = null;//调用者标识，app：微信中用户调用，admin：后台管理员调用
    private $orderType = null;//商城订单：0，商盟订单：1
    private $orderId = null;

    /**
     * 交易完成后的处理，包含消费者返现、分销商佣金计算、团长提成计算
     * @param $orderId 订单ID
     * @param null $SHOP_SET 商城设置，如果为null，使用默认配置
     * @param null $invoker 调用者标识，app：微信中用户调用，admin：后台管理员调用
     * @param false $orderType 订单类型（0：商城订单，1：商盟订单）
     * @return mixed
     */
    public function process($orderId, $SHOP_SET = null, $invoker = null, $orderType = 0)
    {
        $this->orderId = $orderId;
        if (empty($SHOP_SET)) {
            $this->SHOP_SET = M('shop_set')->find();
        } else {
            $this->SHOP_SET = $SHOP_SET;
        }
        $this->isAdminOperate = (empty($invoker) || $invoker != 'app') ? true : false;
        $this->orderType = empty($orderType) ? 0 : 1;
        //消费者计算
        $buyerCommission = D('BuyerCommission');    //消费者交易完成后，修改消费者消费金额、应返金额等数据
        $rltBuyer = $buyerCommission->process($orderId, $this->SHOP_SET, $this->isAdminOperate, $this->orderType);
        if (empty($rltBuyer['status'])) {
            //有错误
        } else {
            //正常执行
        }
        $totalPrice = floatval($rltBuyer['total_price']);

        //分销商佣金计算
        $fxCommission = D('FxCommission');
        $rltFx = $fxCommission->process($orderId, $this->SHOP_SET, $this->isAdminOperate, $this->orderType);
        if (empty($rltFx['status'])) {
            //有错误
        } else {
            //正常执行
        }
        $totalFxPrice = floatval($rltFx['total_price']);

        //团长佣金计算
        /*$tuanZhangCommission = D('TuanZhangCommission');
        $rltTuanZhang = $tuanZhangCommission->process($orderId, $this->SHOP_SET, $this->isAdminOperate, $this->orderType);
        if (empty($rltTuanZhang['status'])) {
            //有错误
        } else {
            //正常执行
        }
        $totalTcPrice = floatval($rltTuanZhang['total_price']);*/

        /*//商家
        $supplierCommission = D('SupplierCommission');
        $rltSupplier = $supplierCommission->process($orderId, $this->SHOP_SET, $this->isAdminOperate, $this->orderType);
        if (empty($rltSupplier['status'])) {
            //有错误
        } else {
            //正常执行
        }*/

        //更新平台数据
        //$this->processShop($totalPrice, $totalFxPrice + $totalTcPrice);
        $this->processShop($totalPrice, $totalFxPrice);

        //TODO 并发量增大时，可把分销商、团长佣金计算剥离并发执行
        return $rltBuyer;
    }

    private function processShop($totalPrice, $deductPrice)
    {
        $mshoplog = M('shop_set_syslog');
        $rltlog = $mshoplog->where(['oid' => $this->orderId, 'order_type' => $this->orderType])->getField('id');
        if ($rltlog) {
            $info['status'] = 0;
            $info['msg'] = '已经计算过，不能重复计算';
            return $info;
        }
        //TODO 累计平台销售额

        $shopsetlog['oid'] = $this->orderId;
        $shopsetlog['order_type'] = $this->orderType;
        $shopsetlog['total_sales_amount_inc'] = $totalPrice;
        $shopsetlog['total_retained_funds_inc'] = ($totalPrice * $this->SHOP_SET['total_commission_rate'] * 0.01);
        $shopsetlog['capital_pool_remainder_inc'] = ($shopsetlog['total_retained_funds_inc'] - $deductPrice);

        $shopData['total_sales_amount'] = array('exp', 'total_sales_amount+' . $shopsetlog['total_sales_amount_inc']);
        $shopData['total_retained_funds'] = array('exp', 'total_retained_funds+' . $shopsetlog['total_retained_funds_inc']);
        $shopData['capital_pool_remainder'] = array('exp', 'capital_pool_remainder+' . $shopsetlog['capital_pool_remainder_inc']);
        $shopsetlog['ctime'] = time();
        //计算沉淀资金：销售额-(分销商佣金-商家提成-消费者返现-团长提成)
        $mshop = M('shop_set');
        $rltshop = $mshop->where('id=' . $this->SHOP_SET['id'])->save($shopData);

        if ($rltshop !== FALSE) {
            //正常执行
            $shopsetlog['status'] = 1;
        } else {
            //有错误
            $shopsetlog['status'] = 0;
        }
        $refxlog = $mshoplog->add($shopsetlog);
        if (!$refxlog) {
            file_put_contents('./Data/app_shop_set_error.txt', '错误日志时间:' . date('Y-m-d H:i:s') . PHP_EOL . '错误纪录信息:' . $refxlog . PHP_EOL . PHP_EOL . $mshop->getLastSql() . PHP_EOL . PHP_EOL, FILE_APPEND);
        }
    }
}

?>