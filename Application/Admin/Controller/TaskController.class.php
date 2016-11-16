<?php
/**
 * Created by PhpStorm.
 * User: wangzhiyuan
 * Date: 16/6/23
 * Time: 下午3:25
 */
namespace Admin\Controller;

use Think\Controller;

class TaskController extends Controller
{
    /**
     * 每日的销售额返现统计
     */
    public function back_money_stat()
    {
        //今日的商城订单销售额 + 今日的商盟订单销售额

        //获取日期(今日统计昨天的销售额)
        $goal_date = strtotime("-1 day");

        $year = date("Y", $goal_date);
        $month = date("m", $goal_date);
        $day = date("d", $goal_date);

        //判断是否已经统计过
        $count = M('bonus_record')->where(array('today_year' => $year, 'today_month' => $month, 'today_day' => $day))->count();
        if ($count) {
            bonus_log_write(['BONUS-STAT' => '销售额返现统计', 'today_time' => time()]);
            responseToJson(1, 'back_money_stat,已经统计过了');
        }

        $begin = mktime(0, 0, 0, $month, $day, $year);
        $end = mktime(23, 59, 59, $month, $day, $year);

        //shop_order 状态（0：交易取消，1：未支付，2：已付款，3：已发货，4：退货中，5：交易完成，6：交易关闭，7：退货完成）
        $where['etime'] = array(['egt', $begin], ['elt', $end]);
        $where['status'] = 5;//交易完成

        $shop_order_total = floatval(M('shop_order')->where($where)->sum('totalprice'));

        //supplier_order 订单状态（0：已下单，1：待核对，2：已完成，3：已关闭）
        $da['end_time'] = array(['egt', $begin], ['elt', $end]);
        $da['status'] = 2;

        $supplier_order_total = floatval(M('supplier_order')->where($da)->sum('total_price'));

        //字段列表
        $mShopSet = M('shop_set');
        $shop = $mShopSet->find();

        $today_sales_amount = $shop_order_total + $supplier_order_total;

        $capital_pool_remainder = floatval($shop['capital_pool_remainder']);
        $bonus_capital_pool_remainder = floatval($shop['bonus_capital_pool_remainder']);

        //平台提成金额
        $rate_money = money_floor($today_sales_amount * $shop['total_commission_rate'] / 100);

        $fx_amount = money_floor($rate_money * $shop['fx_rate'] / 100);
        $tuanzhang_amount = money_floor($rate_money * $shop['tuanzhang_rate'] / 100);

        $plan_money = money_floor($rate_money * $shop['buyer_rate'] / 100);

        //保存统计
        $data['plan_money'] = $plan_money;
        $data['today_sales_amount'] = $today_sales_amount;
        $data['capital_pool_remainder'] = $capital_pool_remainder;
        $data['bonus_capital_pool_remainder'] = $bonus_capital_pool_remainder;
        $data['create_time'] = time();
        $data['today_time'] = strtotime($year . '-' . $month . '-' . $day);
        $data['today_year'] = $year;
        $data['today_month'] = $month;
        $data['today_day'] = $day;
        $data['fx_amount'] = $fx_amount;
        $data['tuanzhang_amount'] = $tuanzhang_amount;

        $bonus_record_id = M('bonus_record')->add($data);

        //TODO 按规则把分红资金注入分红资金池，从当前资金池余额扣除分红资金
        //$mShopSet->where(['id'=>$shop['id']])->save();
        M()->execute("update shop_set set bonus_capital_pool_remainder=bonus_capital_pool_remainder+$plan_money,capital_pool_remainder=capital_pool_remainder-$plan_money");

        M('shop_bonus_pool_record')->add([
            'money' => $plan_money,
            'source' => 1,
            'ctime' => time(),
            'event' => "分红资金注入[ID:$bonus_record_id]"
        ]);

        bonus_log_write(['BONUS-STAT' => '销售额返现统计', 'plan_money' => $data['plan_money'], 'today_sales_amount' => $data['today_sales_amount'], 'today_time' => $data['today_time']]);

        responseToJson(0, 'success', [
            'shop_order_total' => $shop_order_total,
            'supplier_order_total' => $supplier_order_total,
            'today_sales_amount' => $today_sales_amount,
            'capital_pool_remainder' => $capital_pool_remainder,
            'bonus_capital_pool_remainder' => $bonus_capital_pool_remainder,
            'rate_money' => $rate_money,
            'fx_amount' => $fx_amount,
            'tuanzhang_amount' => $tuanzhang_amount,
            'plan_money' => $plan_money
        ]);
    }

    /**
     * 红包状态查询，如果用户已领取，更新状态
     */
    public function bag_query()
    {
        set_time_limit(0);

        $running = F('bag_query_running');
        if ($running) {
            return;
        }
        F('bag_query_running', true);

        //开始处理
        $map['status'] = 1;//待领取
        $wxtx = M('Vip_wxtx')->where($map)->select();
        $set = M('set')->find();
        Vendor("WxHongBao.WxHongBao");
        $WxHongBao = new \WxHb ($set['wxappid'], $set['wxappsecret'], $set['wxmchid'], $set['wxmchkey']);
        $WxHongBao->inited();
        $endTx = D('EndTx');
        foreach ($wxtx as $key => $value) {
            $responseXml = $WxHongBao->BagSelect($value['billno']);
            $responseObj = simplexml_load_string($responseXml, 'SimpleXMLElement', LIBXML_NOCDATA);
            //转换成数组
            $responseArr = ( array )$responseObj;
            //判断是否红包是否发送成功
            if ($responseArr["status"] == "RECEIVED") {
                $endTx->endwxtx($value['id'], '红包已领取，提现完成');
            }
        }

        //处理结束
        F('bag_query_running', false);
    }

    /**
     * 组团失败判断 检验
     */
    public function group_buy_check(){
        $m = M('group_buy');
        $map['group_buy.status'] = 0;   //只判断 正在组团 的团
        $fields = 'group_buy.id,shop_goods.group_time_end';
        $data = $m->field($fields)
            ->join('shop_goods on group_buy.goods_id=shop_goods.id')
            ->where($map)->select();
        $ids = [];
        foreach($data as $v){
            if($v['group_time_end'] != 0 && $v['group_time_end'] < time()) {    //检查是否已经超过最后限制时间
                array_push($ids,$v['id']);
            }
        }
        if($ids){
            $id_string = implode(',',$ids);
            //组团失败
            M()->execute('update group_buy set status=1 where id in ('.$id_string.')');
        }
    }

}