<?php
// 本类由系统自动生成，仅供测试用途
namespace App\Controller;

class VipController extends BaseController
{
    public function _initialize()
    {
        //你可以在此覆盖父类方法
        parent::_initialize();
    }

    /**
     * 取消对账单
     */
    public function checkCancel()
    {
        $id = I('id') <> '' ? I('id') : $this->diemsg(0, '缺少ORDERID参数');
        $bkurl = U('App/Vip/checkOrder', array());
        $backurl = base64_encode($bkurl);
        $loginurl = U('App/Vip/login', array('backurl' => $backurl));
        $re = $this->checkLogin($backurl);
        //已登陆
        $m = M('Supplier_bill');
        $map['id'] = $id;
        $map["status"] = 0;
        $cache = $m->where($map)->find();
        if (!$cache) {
            $this->diemsg(0, '此订单不存在!');
        }
        if ($cache['status'] <> 0) {
            $this->error('只有待处理订单可以取消！');
        }
        $data["status"] = 2;
        $re = $m->where($map)->save($data);
        if ($re) {
            $data["bill_id"] = 0;
            $data["is_check_account"] = 0;
            $data["check_account_time"] = 0;
            $data["status"] = 0;
            M("supplier_order")->where(array("bill_id" => $id))->save($data);
            $this->success('订单取消成功！');
        } else {
            $this->error('订单取消失败,请重新尝试！');
        }
    }

    /**
     * 未结算明细
     */
    public function unsettledItem()
    {
        $page = intval(I("pg"));
        $page_count = intval(I("pc"));
        if ($page_count > 20) {
            $page_count = 20;
        }
        $data = M("supplier_order")
            ->where(array("status" => 0, "is_need_return" => 1, "is_check_account" => 0, "is_pay" => 1, "supplier_id" => self::$WAP["vip"]["supplier_id"]))
            ->order("order_time desc")
            ->limit($page * $page_count, $page_count)
            ->select();
        $this->assign('cache', $data);
        $this->display();
    }

    /**
     * 余额详情
     */
    public function moneyItem()
    {
        $time = array(array('egt', strtotime("-1 month")), array('lt', strtotime("+1 day")), 'and');

        $data = array();
        $vipid = self::$WAP["vipid"];
        $tx = M("vip_tx")->where(array("vipid" => $vipid, "status" => array("in", "1,2"), "txtime" => $time))->select();
        foreach ($tx as $k => $v) {
            array_push($data, array("code" => $v["txcard"], "name" => "银行提现", "fee" => 0, "price" => round($v["txprice"] + $v["tx_fee"], 2), "time" => $v["txtime"], "type" => 1, "status" => $v["status"] + 10));
        }
        $wxtx = M("vip_wxtx")->where(array("vip_id" => $vipid, "status" => array("in", "0,1,2"), "txtime" => $time))->select();
        foreach ($wxtx as $k => $v) {
            array_push($data, array("code" => $v["billno"], "name" => "微信提现", "fee" => 0, "price" => round($v["txprice"] + $v["tx_fee"], 2), "time" => $v["txtime"], "type" => 1, "status" => $v["status"] + 20));
        }
        $supplier_order = M("supplier_order")->where(array("vip_buyer_id" => $vipid, "status" => array("in", "0,1,2"), "pay_type" => "money", "is_pay" => 1, "pay_time" => $time))->select();
        foreach ($supplier_order as $k => $v) {
            array_push($data, array("code" => $v["order_code"], "name" => "商盟余额支付", "fee" => 0, "price" => round($v["pay_price"], 2), "time" => $v["pay_time"], "type" => 1, "status" => $v["status"] + 30));
        }
        $shop_order = M("shop_order")->where(array("vipid" => $vipid, "status" => array("in", "2,3,4,5"), "paytype" => "money", "ispay" => 1, "paytime" => $time))->select();
        foreach ($shop_order as $k => $v) {
            array_push($data, array("code" => $v["oid"], "name" => "商城余额支付", "fee" => 0, "price" => round($v["payprice"], 2), "time" => $v["paytime"], "type" => 1, "status" => $v["status"] + 40));
        }
        $fx = M("bonus_detail_record")->where(array("vip_id" => $vipid, "create_time" => $time))->select();
        foreach ($fx as $k => $v) {
            array_push($data, array("code" => $v["order_code"], "name" => "消费分红", "fee" => 0, "price" => round($v["money"], 2), "time" => $v["create_time"], "type" => 0, "status" => 50));
        }
        $chzh = M("vip_log")->where(array("vipid" => $vipid, "type" => array("in", "6,7,9"), "ctime" => $time))->select();
        foreach ($chzh as $k => $v) {
            $code = "";
            if ($v["type"] == 6) {
                $code = "充值卡充值";
            }
            if ($v["type"] == 7 && $v["status"] = 1) {
                $code = "在线充值";
            }
            if ($v["type"] == 9) {
                $code = "管理员代充";
            }
            if ($code != "") {
                array_push($data, array("code" => $code, "name" => "账户充值", "fee" => 0, "price" => round($v["money"], 2), "time" => $v["ctime"], "type" => 0, "status" => 60));
            }
        }
        $tk = M("shop_order")->where(array("vipid" => $vipid, "status" => 7, "tuihuotime" => $time))->select();
        foreach ($tk as $k => $v) {
            array_push($data, array("code" => $v["oid"], "name" => "退款", "fee" => 0, "price" => round($v["tuihuoprice"], 2), "time" => $v["tuihuotime"], "type" => 0, "status" => 70));
        }
        $sort = array();
        foreach ($data as $v) {
            $sort[] = $v['time'];
        }

        array_multisort($sort, SORT_DESC, $data);
        $this->assign("cache", $data);
        $this->display();
    }

    /**
     * 商盟订单详情
     */
    public function orderDetail()
    {
        $this->display();
    }

    /**
     * 对账明细分页
     */
    public function checkDetailItem()
    {
        $id = I("bid");
        $page = intval(I("pg"));
        $page_count = intval(I("pc"));
        if ($page_count > 20) {
            $page_count = 20;
        }
        $data = M("supplier_order")
            ->where(array("bill_id" => $id))
            ->order("order_time desc")
            ->limit($page * $page_count, $page_count)
            ->select();
        foreach ($data as $k => $v) {
            if ($data[$k]["is_payforother"] == 0) {
                $data[$k]["pay_price"] = round($data[$k]["pay_price"] * self::$WAP["shopset"]["settlement_rate"] / 10, 2);
            } else {
                $data[$k]["pay_price"] = round($data[$k]["pay_price"], 2);
            }
        }
        $this->assign("cache", $data);
        $this->display();
    }

    /**
     * 对账明细页
     */
    public function  checkDetail()
    {
        $data = M("supplier_bill")->where(array("id" => I("id")))->find();
        $this->assign('cache', $data);
        $this->display();
    }

    /**
     * 消费返现明细分页
     */
    public function cashBackItem()
    {
        $page = intval(I("pg"));
        $page_count = intval(I("pc"));
        if ($page_count > 20) {
            $page_count = 20;
        }
        $data = M("bonus_detail_record")->where(array("vip_id" => self::$WAP["vipid"]))->order("create_time desc")->limit($page * $page_count, $page_count)->select();
        foreach ($data as $k => $v) {
            $data[$k]["money"] = round($data[$k]["money"], 2);
        }
        $this->assign("cache", $data);
        $this->display();
    }

    /**
     * 消费返现
     */
    public function cashBack()
    {
        $this->assign("vip", self::$WAP["vip"]);
        $this->display();
    }

    /**
     * 提现分页
     */
    public function txItem()
    {
        $page = intval(I("pg"));
        $page_count = intval(I("pc"));
        if ($page_count > 20) {
            $page_count = 20;
        }
        $type = intval(I("type"));
        if ($type == 0) {
            $data = M("vip_wxtx")->where(array("vip_id" => self::$WAP["vipid"]))->order("txtime desc")->limit($page * $page_count, $page_count)->select();
        } else {
            $data = M("vip_tx")->where(array("vipid" => self::$WAP["vipid"]))->order("txsqtime desc")->limit($page * $page_count, $page_count)->select();
            foreach ($data as $k => $v) {
                if ($data[$k]["status"] == 0) {
                    $data[$k]["status"] = 3;
                }
                $data[$k]["txtime"] = $data[$k]["txsqtime"];
            }
        }

        $this->assign("cache", $data);
        $this->display();
    }

    /**
     * 提现列表
     */
    public function txList()
    {
        $type = intval(I("type"));
        $this->assign("type", $type);
        $this->display();
    }

    /**
     * 招商中心
     */
    public function merchantItem()
    {
        $page = intval(I("pg"));
        $page_count = intval(I("pc"));
        if ($page_count > 20) {
            $page_count = 20;
        }
        $cache = M("supplier")
            ->where(array("inviter_id" => self::$WAP["vipid"]))
            ->order("create_time desc")
            ->limit($page * $page_count, $page_count)
            ->select();
        foreach ($cache as $k => $v) {
            $tc = M("tuanzhang_tc_log")->where(array("to" => $cache[$k]["inviter_id"], "supplier_id" => $v["id"]))->sum("tc");
            $cache[$k]["tc"] = $tc;
            $cache[$k]["total_money"] = $cache[$k]["total_money"];
        }
        $this->assign('cache', $cache);
        $this->display();
    }

    /**
     * 招商中心
     */
    public function merchant()
    {
        if (intval(self::$WAP["vip"]["is_tuanzhang"]) == 0) {
//            $this->success('您还不是团长，马上去开通团长吧！', U('App/Vip/index', array()), 5);
            $this->assign('shop', self::$SHOP['set']);
            $this->display('merchantRegister');
            exit();
        }
        //招商总数
        $this->assign('count', self::$WAP["vip"]["total_supplier_number"]);
        //奖金总额
        $this->assign('money', round(self::$WAP["vip"]["total_tuanzhang_tc"], 2));
        $this->display('merchant');
//        $this->display();
    }

    /**
     * 确认对帐单
     */
    public function checkOrderDo()
    {
        $id = I("id");
        $tn = I("tn");
        $dd = I("dd");
        $where["id"] = $id;
        $where["status"] = 0;
        $where["supplier_id"] = self::$WAP["vip"]["supplier_id"];
        $m = M("supplier_bill")->where($where)->find();
        if ($m) {
            $data["tn"] = $tn;
            $data["trade_complete_time"] = strtotime($dd);
            $data["status"] = 1;
            $data["complete_time"] = time();
            $rlt = M("supplier_bill")->where($where)->save($data);
            if ($rlt) {
                M('supplier_order')->where(array('bill_id' => $id))->save(['is_check_account' => 1, 'check_account_time' => time(), 'status' => 2, 'end_time' => time()]);
                $order_codes = M("supplier_order")->where(array("bill_id" => $id))->getField("order_code", true);
                $commission = D('Commission');
                foreach ($order_codes as $k => $v) {
                    $commission->process($v, self::$WAP['shopset'], 'app', OT_SM);
                }
                responseToJson(0, "确认成功");
            } else {
                responseToJson(1, "确认失败");
            }
        } else {
            responseToJson(2, "对帐单不存在");
        }
    }

    /**
     * 对帐单分页
     */
    public function checkOrderItem()
    {
        $status = intval(I("type"));
        $page = intval(I("pg"));
        $page_count = intval(I("pc"));
        if ($page_count > 20) {
            $page_count = 20;
        }
        $where["status"] = $status;
        $where["supplier_id"] = self::$WAP["vip"]["supplier_id"];
        $data = M("supplier_bill")->where($where)->order("apply_time desc")->limit($page * $page_count, $page_count)->select();
        if ($data == null) {
            $data = array();
        }
        $this->assign('data', $data);
        $this->display();
    }

    /**
     * 对帐单列表
     */
    public function checkOrder()
    {
        $backurl = base64_encode(U('App/Vip/checkOrder', array("type" => I("type"))));
        $this->checkLogin($backurl);
        $status = intval(I("type"));
        $this->assign('type', $status);
        $this->display();
    }

    /**
     * 提交对帐申请
     */
    public function checkDo()
    {
        $mystore = M("supplier_store")->where(array("supplier_id" => self::$WAP["vip"]["supplier_id"]))->find();

        $onLineWhere["vip_seller_id"] = self::$WAP["vip"]["id"];
        $onLineWhere["supplier_id"] = self::$WAP["vip"]["supplier_id"];
        $onLineWhere["store_id"] = $mystore["id"];
        $onLineWhere["is_payforother"] = 0;
        $onLineWhere["is_pay"] = 1;
        $onLineWhere["is_need_return"] = 1;
        $onLineWhere["is_check_account"] = 0;
        $onLineWhere["status"] = 2;
        $onLineSum = M("supplier_order")->where($onLineWhere)->sum("total_price");
        $onLineSum = $onLineSum * (self::$WAP['shopset']["settlement_rate"]) / 10;

        $offLineWhere["vip_seller_id"] = self::$WAP["vip"]["id"];
        $offLineWhere["supplier_id"] = self::$WAP["vip"]["supplier_id"];
        $offLineWhere["store_id"] = $mystore["id"];
        $offLineWhere["is_payforother"] = 1;
        $offLineWhere["is_pay"] = 1;
        $offLineWhere["is_need_return"] = 1;
        $offLineWhere["is_check_account"] = 0;
        $offLineWhere["status"] = 0;
        $offLineSum = M("supplier_order")->where($offLineWhere)->sum("pay_price");
        $doSum = $onLineSum - $offLineSum;
        if ($doSum > 0) {
            $data["is_pay"] = 1;//平台给商家打款
        } else {
            $data["is_pay"] = 0;//商家给平台打款
            $doSum = abs($doSum);
        }

        $data["bill_code"] = getOrderCode(self::$WAP["vip"]["id"], 2);
        $data["vip_seller_id"] = self::$WAP["vip"]["id"];
        $data["supplier_id"] = self::$WAP["vip"]["supplier_id"];
        $data["store_id"] = $mystore["id"];
        $supplier = M("supplier")->where(array("id" => self::$WAP["vip"]["supplier_id"]))->find();
        $data["money"] = round($doSum, 2);
        $data["apply_time"] = time();
        $data["status"] = 0;
        $data["bank_name"] = $supplier["bank_name"];
        $data["sub_bank_name"] = $supplier["sub_bank_name"];
        $data["cardholder"] = $supplier["cardholder"];
        $data["bank_code"] = $supplier["bank_code"];
        $data["supplier_name"] = $supplier["name"];
        $data["supplier_phone"] = $supplier["contact_phone"];
        $data["tx_fee"] = round(self::$WAP["shopset"]["tx_fee_rate"] / 100 * $doSum, 2);
        $data["memo"] = I("mm") == "" ? date("Y-m-d") . "的对账单申请" : I("mm");
        $M = M("supplier_bill");

        $M->startTrans();
        $rlt = M("supplier_bill")->add($data);
        if ($rlt) {
            $map["vip_seller_id"] = self::$WAP["vip"]["id"];
            $map["supplier_id"] = self::$WAP["vip"]["supplier_id"];
            $map["store_id"] = $mystore["id"];
            $map["is_pay"] = 1;
            $map["is_need_return"] = 1;
            $map["is_check_account"] = 0;
            $map["status"] = 0;
            $temp_data["status"] = 1;
            $temp_data["bill_id"] = $rlt;
            $temp_data["is_check_account"] = 1;
            $temp_data["check_account_time"] = time();
            $rlt0 = M("supplier_order")->where($map)->save($temp_data);

            $map1["vip_seller_id"] = self::$WAP["vip"]["id"];
            $map1["supplier_id"] = self::$WAP["vip"]["supplier_id"];
            $map1["store_id"] = $mystore["id"];
            $map1["is_pay"] = 1;
            $map1["is_need_return"] = 1;
            $map1["is_check_account"] = 0;
            $map1["status"] = 2;
            $temp_data1["bill_id"] = $rlt;
            $temp_data1["is_check_account"] = 1;
            $temp_data1["check_account_time"] = time();

            $rlt1 = M("supplier_order")->where($map1)->save($temp_data1);

            if ($rlt0 !== false && $rlt1 !== false) {
                $M->commit();
                responseToJson(0, "申请成功");
            } else {
                $M->rollback();
                responseToJson(2, "申请失败，请联系管理员");
            }
        } else {
            $M->rollback();
            responseToJson(1, "申请失败，请联系管理员");
        }
    }

    /**
     * 提交对帐申请页面
     */
    public function check()
    {
        $backurl = base64_encode(U('App/Vip/check'));
        $this->checkLogin($backurl);
        $mystore = M("supplier_store")->where(array("supplier_id" => self::$WAP["vip"]["supplier_id"]))->find();

        $onLineWhere["vip_seller_id"] = self::$WAP["vip"]["id"];
        $onLineWhere["supplier_id"] = self::$WAP["vip"]["supplier_id"];
        $onLineWhere["store_id"] = $mystore["id"];
        $onLineWhere["is_pay"] = 1;
        $onLineWhere["is_need_return"] = 1;
        $onLineWhere["is_check_account"] = 0;
        $onLineWhere["is_payforother"] = 0;
        $onLineWhere["status"] = 2;
        $onLineSum = M("supplier_order")->where($onLineWhere)->sum("total_price");
        $onLineSum = $onLineSum * (self::$WAP['shopset']["settlement_rate"]) / 10;
        $this->assign('online', round($onLineSum, 2));

        $offLineWhere["vip_seller_id"] = self::$WAP["vip"]["id"];
        $offLineWhere["supplier_id"] = self::$WAP["vip"]["supplier_id"];
        $offLineWhere["store_id"] = $mystore["id"];
        $offLineWhere["is_pay"] = 1;
        $offLineWhere["is_need_return"] = 1;
        $offLineWhere["is_check_account"] = 0;
        $offLineWhere["is_payforother"] = 1;
        $offLineWhere["status"] = 0;
        $offLineSum = M("supplier_order")->where($offLineWhere)->sum("pay_price");
        $this->assign('offline', round($offLineSum, 2));
        $doSum = $onLineSum - $offLineSum;
        $this->assign('doSum', round($doSum, 2));

        $this->display();
    }

    /**
     * 商家创建订单
     */
    public function createOrderDo()
    {
        $money = I("n");
        if ($money <= 0) {
            $this->error("消费金额必须大于0");
        }
        $vip = M("vip")->where(array("id" => I("v")))->find();
        if ($vip) {
            //$dk = I("r") == "true" ? 1 : 0;
            $dk = 1;
            $vipId = $vip["id"];
            $data["creator_type"] = 1;
            $data["creator_id"] = self::$WAP["vip"]["id"];
            $data["order_code"] = getOrderCode($vipId, 1);
            $data["vip_buyer_id"] = $vipId;
            $data["total_price"] = $money;
            $data["pay_type"] = "offline";
            $data["pay_time"] = time();
            $data["pay_price"] = ($dk == 0 ? $money : (10 - self::$WAP["shopset"]["settlement_rate"]) * $money / 10);
            $data["is_pay"] = 1;
            $data["is_need_return"] = $dk;
            $data["is_check_account"] = 0;
            $data["is_payforother"] = 1;
            $data["status"] = 0;
            $data["order_time"] = time();
            $data["vip_seller_id"] = self::$WAP["vip"]["id"];
            $data["supplier_id"] = self::$WAP["vip"]["supplier_id"];
            $m = M("supplier_store")->where(array("supplier_id" => $data["supplier_id"]))->find();
            $data["store_id"] = $m["id"];
            $data["memo"] = I("m");
            $data["vip_buyer_openid"] = $vip["openid"];
            $data["vip_buyer_mobile"] = empty($vip["mobile"]) ? "" : $vip["mobile"];
            $data["vip_buyer_name"] = (empty($vip["name"]) ? $vip["nickname"] : $vip["name"]);
            $M = M("supplier_order");
            $rlt = $M->add($data);
            if ($rlt) {
                $M->commit();

                $log = M('Supplier_order_log');
                $llog['oid'] = $data["order_code"];
                $llog['msg'] = '订单生成';
                $llog['type'] = 1;
                $llog['ctime'] = time();
                $log->add($llog);

                $log = M('Supplier_order_log');
                $llog['oid'] = $data["order_code"];
                $llog['msg'] = '订单已支付';
                $llog['type'] = 2;
                $llog['ctime'] = time();
                $log->add($llog);

                $mslog = M('Supplier_order_syslog');
                $dlog['oid'] = $data["order_code"];
                $dlog['msg'] = '订单生成';
                $dlog['type'] = 1;
                $dlog['paytype'] = "offline";
                $dlog['ctime'] = time();
                $mslog->add($dlog);

                $mslog = M('Supplier_order_syslog');
                $dlog['oid'] = $data["order_code"];
                $dlog['msg'] = '订单已支付';
                $dlog['type'] = 2;
                $dlog['paytype'] = "offline";
                $dlog['ctime'] = time();
                $mslog->add($dlog);

                responseToJson(0, "操作成功");
            } else {
                $M->rollback();
                responseToJson(1, "操作失败");
            }
        } else {
            responseToJson(2, "VIP的ID不正确");
        }
    }

    /**
     * 商盟订单代支付
     */
    public function orderDo()
    {
        $id = I("id");
        $order = M("supplier_order")->where(array("id" => $id))->find();
        if ($order) {
            $wh["id"] = $id;
            $wh["status"] = 0;
            $data["is_payforother"] = 1;
            $data["is_pay"] = 1;
            $data["pay_time"] = time();
            $data["pay_type"] = "offline";
            $data["pay_price"] = $order["total_price"];
            $data["is_need_return"] = 0;
            if (I("r") == "true") {
                $data["is_need_return"] = 1;
                $data["pay_price"] = $order["total_price"] * (10 - self::$WAP["shopset"]["settlement_rate"]) / 10;
            }
            $rlt = M("supplier_order")->where($wh)->save($data);
            if ($rlt) {

                $log = M('Supplier_order_log');
                $llog['oid'] = $order["order_code"];
                $llog['msg'] = '订单已支付';
                $llog['type'] = 2;
                $llog['ctime'] = time();
                $log->add($llog);


                $mslog = M('Supplier_order_syslog');
                $dlog['oid'] = $order["order_code"];
                $dlog['msg'] = '订单已支付-商家代支付';
                $dlog['type'] = 2;
                $dlog['paytype'] = "offline";
                $dlog['ctime'] = time();
                $mslog->add($dlog);

                responseToJson(0, "操作成功");
            } else {
                responseToJson(1, "操作失败");
            }
        } else {
            responseToJson(2, "订单不存在");
        }
    }

    /**
     * 商盟订单分页
     */
    public function dpOrderItem()
    {
        $page = intval(I("pg"));
        $page_count = intval(I("pc"));
        if ($page_count > 20) {
            $page_count = 20;
        }
        $id = self::$WAP["vip"]["supplier_id"];
        $type = intval(I("type"));
        switch ($type) {
            case 0:
                $wh["supplier_id"] = $id;
                $wh["status"] = array("in", "0,1");
                //$wh["is_payforother"] = 1;
                break;
            case 1:
                $wh["supplier_id"] = $id;
                $wh["status"] = 2;
                break;
        }
        $data = M("supplier_order")->where($wh)->order("order_time desc")->limit($page_count * $page, $page_count)->select();
        foreach ($data as $k => $v) {
            if ($v["status"] == 0) {
                if ($v["is_need_return"] == 1) {
                    $data[$k]["status"] = 0;
                } else {
                    $data[$k]["status"] = 10;
                }
            }
            $data[$k]["total_num"] = 1;
            $store = M("supplier_store")->where(array("id" => $data[$k]["store_id"]))->find();
            $listpic = $this->getPic($store["thumb"]);
            $data[$k]["items"] = array(array("name" => $store["name"], "buyer" => $data[$k]["vip_buyer_name"], "skuattr" => $data[$k]["memo"], "pic" => ($listpic == "" ? "" : $listpic["imgurl"]), "price" => round($data[$k]["total_price"], 2), "num" => 1));
        }
        $this->assign('cache', $data);
        $this->display();
    }

    /**
     * 商盟订单
     */
    public function dpOrder()
    {
        $type = intval(I("type"));
        $this->assign('type', $type);
        $backurl = base64_encode(U('App/Vip/dpOrder', array("type" => $type)));
        $this->checkLogin($backurl);
        $this->display();
    }

    /**
     * 我的店铺
     */
    public function store()
    {
        $id = intval(self::$WAP["vip"]["supplier_id"]);
        $backurl = base64_encode(U('App/Vip/store', array("id" => $id)));
        $this->checkLogin($backurl);
        if ($id == 0) {
//            $this->success('您还没有开通店铺，马上去开通店铺吧！', U('App/Vip/index', array()), 5);
            $this->assign('shop', self::$SHOP['set']);
            $this->display('storeRegister');
            exit();
        } else {
            $supplier = M("supplier")->where(array("id" => $id))->find();
            $store = M("supplier_store")->where(array("supplier_id" => $supplier["id"]))->find();
            $supplier["name"] = $store["name"];
            $listpic = $this->getPic($store["thumb"]);
            $supplier["imgurl"] = $listpic == "" ? "" : $listpic["imgurl"];
            $supplier["total_offline_money"] = number_format($supplier["total_offline_money"], 2);
            $daijiesuan = M("supplier_order")->where(array("status" => 0, "is_need_return" => 1, "is_check_account" => 0, "is_pay" => 1, "supplier_id" => self::$WAP["vip"]["supplier_id"]))->sum("pay_price");
            $supplier["total_bonus_amount"] = number_format($daijiesuan, 2);
            $this->assign('data', $supplier);
            $url = "http://" . $_SERVER['HTTP_HOST'] . U("App/Business/storeView", array("id" => $store["id"]));
            $this->assign('url', $url);
            $this->display();
        }
    }

    public function index()
    {
        $backurl = base64_encode(U('App/Vip/index'));
        $this->checkLogin($backurl);
        $vipid = self::$WAP['vipid'];
        $data = self::$WAP['vip'];
        //判断身份
        if($data['role']==0){
            $data['roleName'] = $data['is_vip']?'<span class="home-label role_4">VIP</span>':'<span class="home-label role_5">普通会员</span>';
            //普通会员直接获取“我的亲友团信息”
            $data['xx_total'] = D('vip')->getSubNum($data);
        }else{
            $data['roleName'] = $this->getRoleName($data['role']);
        }

        //判断签到状态
        /*$d1 = date('Y-m-d', time());
        $d2 = date('Y-m-d', $data['signtime']);
        $data['issign'] = ($d1 == $d2) ? 1 : 0;*/
        //计算未读消息
        $msglist = M('vip_message')->select();
        $msg_pids = '';
        foreach ($msglist as $k => $v) {
            if ($v['pids'] == '') {
                $msg_pids = $msg_pids . ',' . $v['id'];
            } else {
                if (in_array($vipid, explode(',', $v['pids']))) {
                    $msg_pids = $msg_pids . ',' . $v['id'];
                }
            }
        }
        if ($msg_pids) {
            $map['id'] = array('in', in_parse_str($msg_pids));
            $msg = M('vip_message')->where($map)->select();
            $msgread = M('vip_log')->where('vipid=' . $vipid . ' and type=5')->select();
            $data['unread'] = count($msg) - count($msgread);
        } else {
            $data['unread'] = 0;
        }
        //计算未使用卡券
        /*$today = strtotime(date('Y-m-d'));
        $map_card['etime'] = array('EGT', $today);
        $map_card['vipid'] = $vipid;
        $map_card['status'] = 1;
        $data['cardnum'] = M('vip_card')->where($map_card)->count();
        if ($data['isfxgd']) {
            $data['fxname'] = '花股';
        } else {
            if ($data['isfx']) {
                $data['fxname'] = $_SESSION['SHOP']['set']['fxname'];
            } else {
                $data['fxname'] = '非' . $_SESSION['SHOP']['set']['fxname'];
            }
        }*/

        /*$father = M('Vip')->where('id=' . self::$WAP['vip']['pid'])->find();
        if ($father) {
            $this->assign('showfather', 1);
            $this->assign('father', $father);
        }*/

        $data["money"] = number_format($data["money"], 2);
        $data["total_got_bonus"] = number_format($data["total_got_bonus"], 2);
        $data["total_tx"] = number_format($data["total_tx"], 2);
        $this->assign('data', $data);
        $this->assign('actname', 'ftvip');
        $this->assign('qiniu_domain',self::$SET['qiniu_domain']);
        /*$this->assign('isqiandao', $_SESSION['WAP']['vipset']['isqiandao']);
        $this->assign('ispaihang', $_SESSION['WAP']['vipset']['ispaihang']);*/
        $this->display();
    }

    public function sign()
    {
        $backurl = base64_encode(U('App/Vip/index'));
        $this->checkLogin($backurl);
        $vipid = self::$WAP['vipid'];

        $sign_score = explode(',', self::$WAP['vipset']['sign_score']);
        $sign_exp = explode(',', self::$WAP['vipset']['sign_exp']);
        $vip = self::$WAP['vip'];
        $d1 = date_create(date('Y-m-d', $vip['signtime']));
        $d2 = date_create(date('Y-m-d', time()));
        $diff = date_diff($d1, $d2);
        $late = $diff->format("%a");
        //判断是否签到过
        if ($late < 1) {
            $info['status'] = 0;
            $info['msg'] = "您今日已经签过到了！";
            $this->ajaxReturn($info);
        }
        //正常签到累计流程
        if ($late >= 1 && $late < 2) {
            $vip['sign'] = $vip['sign'] ? $vip['sign'] : 0; //防止空值

            $data_vip['sign'] = $vip['sign'] + 1; //签到次数+1
            //积分
            if ($data_vip['sign'] >= count($sign_score)) {
                $score = $sign_score[count($sign_score) - 1];
            } else {
                $score = $sign_score[$data_vip['sign']];
            }
            //经验
            if ($data_vip['sign'] >= count($sign_exp)) {
                $exp = $sign_exp[count($sign_exp) - 1];
            } else {
                $exp = $sign_exp[$data_vip['sign']];
            }
        } else {
            $data_vip['sign'] = 0; //签到次数置零
            $score = $sign_score[0];
            $exp = $sign_exp[0];
        }
        $data_vip['score'] = array('exp', 'score+' . $score);
        $data_vip['exp'] = array('exp', 'exp+' . $exp);
        $data_vip['signtime'] = time();
        $data_vip['cur_exp'] = array('exp', 'cur_exp+' . $exp);
        $level = $this->getlevel(self::$WAP['vip']['cur_exp'] + $exp);
        $data_vip['levelid'] = $level['levelid'];
        $m = M('Vip');
        $r = $m->where(array('id' => $vipid))->save($data_vip);

        if ($r) {
            //增加签到日志
            $data_log['ip'] = get_client_ip();
            $data_log['vipid'] = $vipid;
            $data_log['event'] = '会员签到-连续' . $data_vip['sign'] . '天';
            $data_log['score'] = $score;
            $data_log['exp'] = $exp;
            $data_log['type'] = 2;
            $data_log['ctime'] = time();
            M('vip_log')->add($data_log);
            $info['status'] = 1;
            $info['msg'] = "签到成功！";
            $data_log['levelname'] = $level['levelname'];
            $info['data'] = $data_log;
        } else {
            $info['status'] = 0;
            $info['msg'] = "签到失败！" . $r;
        }
        $this->ajaxReturn($info);
    }

    public function reg()
    {
        if (IS_POST) {
            $m = M('vip');
            $post = I('post.');
            //判断重复注册
            if ($m->where('mobile=' . $post['mobile'])->find()) {
                $info['status'] = 0;
                $info['msg'] = '此手机号已注册过！';
                $this->ajaxReturn($info, "json");
            }
            //判断验证码
            if (self::$WAP['vipset']['isverify'] == 1) {
                $last_ver = M('vip_log')->where('mobile=' . $post['mobile'] . ' and type=1')->order('ctime desc')->find();
                if ($last_ver['code'] != $post['code']) {
                    $info['status'] = 0;
                    $info['msg'] = '验证码错误！';
                    $this->ajaxReturn($info, "json");
                }
            }
            $post['password'] = md5($post['password']);
            $post['score'] = self::$WAP['vipset']['reg_score'];
            $post['exp'] = self::$WAP['vipset']['reg_exp'];
            $post['cur_exp'] = self::$WAP['vipset']['reg_exp'];
            $level = $this->getLevel($post['exp']);
            $post['levelid'] = $level['levelid'];
            $post['ctime'] = time();
            unset($post['code']);
            $r = $m->add($post);
            if ($r) {
                //赠送操作
                if (self::$WAP['vipset']['isgift']) {
                    $gift = explode(",", self::$WAP['vipset']['gift_detail']);
                    $cardnopwd = $this->getCardNoPwd();
                    $data_card['type'] = $gift[0];
                    $data_card['vipid'] = $r;
                    $data_card['money'] = $gift[1];
                    $data_card['usemoney'] = $gift[3];
                    $data_card['cardno'] = $cardnopwd['no'];
                    $data_card['cardpwd'] = $cardnopwd['pwd'];
                    $data_card['status'] = 1;
                    $data_card['stime'] = $data_card['ctime'] = time();
                    $data_card['etime'] = time() + $gift[2] * 24 * 60 * 60;
                    M('vip_card')->add($data_card);

                    //发送赠送通知消息
                    //					$data_msg['pids']=$r;
                    //					$data_msg['title']="新人礼包";
                    //					$data_msg['content']="新用户注册赠送新人礼包，内含代金券，请至个人中心查收！";
                    //					$data_msg['ctime']=time();
                    //					M('vip_message')->add($data_msg);
                }
                //记录日志
                $data_log['ip'] = get_client_ip();
                $data_log['vipid'] = $r['id'];
                $data_log['ctime'] = time();
                $data_log['event'] = "会员注册";
                $data_log['score'] = $post['score'];
                $data_log['exp'] = $post['exp'];
                $data_log['type'] = 4;
                M('vip_log')->add($data_log);

                $info['status'] = 1;
                $info['msg'] = '注册成功！马上去登陆';
                $info['mobile'] = $post['mobile'];
            } else {
                $info['status'] = 0;
                $info['msg'] = '注册失败！';
            }
            $this->ajaxReturn($info, "json");
        } else {
            if (self::$WAP['vipset']['isverify'] == 1) {
                if ($_SESSION['mobile_tmp']) {
                    $mobile = $_SESSION['mobile_tmp'];
                    $last_ver = M('vip_log')->where('mobile=' . $mobile)->order('ctime desc')->find();
                    $times = $last_ver['ctime'] + self::$WAP['vipset']['ver_interval'] * 60 - time();
                }
            }
            $status = $times > 0 ? 0 : 1;
            $times = $times > 0 ? $times : 0;
            $this->assign('status', $status);
            $this->assign('times', $times);
            $this->assign('isverify', self::$WAP['vipset']['isverify']);
            $this->display();
        }
    }

    private function getCardNoPwd()
    {
        $dict_no = "0123456789";
        $length_no = 10;
        $dict_pwd = "ABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789";
        $length_pwd = 10;
        $card['no'] = "";
        $card['pwd'] = "";
        for ($i = 0; $i < $length_no; $i++) {
            $card['no'] .= $dict_no[rand(0, (strlen($dict_no) - 1))];
        }
        for ($i = 0; $i < $length_pwd; $i++) {
            $card['pwd'] .= $dict_pwd[rand(0, (strlen($dict_pwd) - 1))];
        }
        return $card;
    }

    public function sendCode()
    {
        $m = M('vip_log');
        $post = I('get.');

        //已验证次数
        $counts = $m->where('mobile=' . $post['mobile'])->count();
        if ($counts >= self::$WAP['vipset']['ver_times']) {
            $info['status'] = 0;
            $info['msg'] = "超出验证次数！";
            $this->ajaxReturn($info);
        }
        $data_log['ip'] = get_client_ip();
        $post['code'] = rand(1000, 9999);
        $post['ctime'] = time();
        $post['event'] = "注册获取验证码";
        $post['type'] = 1;
        $r = $m->add($post);

        if ($r) {
            $info['status'] = 1;
            $info['msg'] = "验证码发送成功！";
            $info['times'] = self::$WAP['vipset']['ver_interval'] * 60;
            $_SESSION['mobile_tmp'] = $post['mobile'];
        } else {
            $info['status'] = 0;
            $info['msg'] = "发送失败！";
        }
        $this->ajaxReturn($info);
    }

    public function login()
    {
        if (IS_POST) {
            $m = M('vip');
            $post = I('post.');
            $r = $m->where("mobile='" . $post['mobile'] . "' and password='" . md5($post['password']) . "'")->find();
            if ($r) {
                //记录日志
                $data_log['ip'] = get_client_ip();
                $data_log['vipid'] = $r['id'];
                $data_log['ctime'] = time();
                $data_log['event'] = "会员登陆";
                $data_log['type'] = 3;
                M('vip_log')->add($data_log);
                //记录最后登陆
                $data_vip['cctime'] = time();
                $m->where('id=' . $r['id'])->save($data_vip);

                $info['status'] = 1;
                $info['msg'] = "登陆成功！";

                $_SESSION['WAP']['vipid'] = $r['id'];
                $_SESSION['WAP']['vip'] = $r;
            } else {
                $info['status'] = 0;
                $info['msg'] = "账号密码错误！";
            }
            $this->ajaxReturn($info);
        } else {
            $this->assign('mobile', I('mobile'));
            $this->assign('backurl', base64_decode(I('backurl')));
            $this->display();
        }
    }

    public function logout()
    {
        session(null);
        $this->redirect('App/Vip/login');
    }

    public function messageItem()
    {
        $page = intval(I("pg"));
        $page_count = intval(I("pc"));
        if ($page_count > 40) {
            $page_count = 40;
        }
        $vipid = self::$WAP['vipid'];
        $m = M('vip_message');

        $msglist = $m->order("ctime desc")->limit($page_count * $page, $page_count)->select();
        $msg_pids = '';
        foreach ($msglist as $k => $v) {
            if ($v['pids'] == '') {
                $msg_pids = $msg_pids . ',' . $v['id'];
            } else {
                if (in_array($vipid, explode(',', $v['pids']))) {
                    $msg_pids = $msg_pids . ',' . $v['id'];
                }
            }
        }
        $map['id'] = array('in', in_parse_str($msg_pids));
        $data = $m->where($map)->order('ctime desc')->select();
        foreach ($data as $k => $val) {
            $read = M('vip_log')->where('vipid=' . $vipid . ' and opid=' . $val['id'] . ' and type=5')->find();
            $data[$k]['read'] = $read ? 1 : 0;
        }
        $this->assign('data', $data);
        $this->display();
    }

    public function message()
    {
        $backurl = base64_encode(U('App/Vip/message'));
        $this->checkLogin($backurl);

        $this->assign('actname', 'ftvip');
        $this->display();
    }

    public function msgRead()
    {
        $backurl = base64_encode(U('App/Vip/message'));
        $this->checkLogin($backurl);
        $vipid = self::$WAP['vipid'];

        $m = M('vip_message');
        $id = I('id');

        $msgread = M('vip_log')->where('opid=' . $id . ' and vipid=' . $vipid)->find();

        if ($msgread) {
            $info['status'] = 0;
        } else {
            $data_log['ip'] = get_client_ip();
            $data_log['event'] = "会员浏览消息";
            $data_log['type'] = 5;
            $data_log['vipid'] = $vipid;
            $data_log['opid'] = $id;
            $data_log['ctime'] = time();
            M('vip_log')->add($data_log);
            $info['status'] = 1;
        }
        $data = $m->where('id=' . $id)->find();
        $info['data'] = $data;
        $this->ajaxReturn($info);

    }

    public function info()
    {
        $backurl = base64_encode(U('App/Vip/info'));
        $this->checkLogin($backurl);
        $vipid = self::$WAP['vipid'];

        if (IS_POST) {
            $m = M('vip');
            $post = I('post.');
            $data["mobile"] = intval($post["mobile"]);
            $data["email"] = $post["email"];
            $data["name"] = htmlspecialchars($post["name"]);

            $r = $m->where("id=" . $vipid)->save($data);
            if (false !== $r) {
                $info['status'] = 1;
                $info['msg'] = "资料修改成功！";
            } else {
                $info['status'] = 0;
                $info['msg'] = "资料修改失败！";
            }
            $this->ajaxReturn($info);
        } else {
            $array['data'] = self::$WAP['vip'];
            $school_id = self::$WAP['vip']['school_id'];
            $array['school_name'] = self::$WAP['vip']['school_name'];
            $this->assign($array);
            $this->display();
        }
    }

    public function tx()
    {
        $backurl = base64_encode(U('App/Vip/tx'));
        $this->checkLogin($backurl);
        $vipid = self::$WAP['vipid'];

        if (IS_POST) {
            $m = M('vip');
            $post = I('post.');
            $data["txname"] = $post["txname"];
            $data["txmobile"] = $post["txmobile"];
            $data["txyh"] = $post["txyh"];
            $data["txfh"] = $post["txfh"];
            $data["txszd"] = $post["txszd"];
            $data["txcard"] = $post["txcard"];
            $r = $m->where("id=" . $vipid)->save($data);
            //dump($m->getLastSql());
            //die('ok');
            if ($r !== FALSE) {

                $this->success('提现资料修改成功！');
            } else {
                $this->error('提现资料修改失败！');
            }
        } else {
            $data = self::$WAP['vip'];
            $this->assign('data', $data);
            $this->display();
        }
    }

    public function txOrder()
    {
        $backurl = base64_encode(U('App/Vip/txOrder'));
        $this->checkLogin($backurl);
        $vipid = self::$WAP['vipid'];
        $m = M('vip');
        $vip = $m->where('id=' . $vipid)->find();
        $this->assign('vip', $vip);
        if (IS_POST) {
            $mtx = M('vip_tx');
            $mwxtx = M('vip_wxtx');
            $post = I('post.');
            //2016年8月5日09:13:00添加：微信和银行卡的提现金额限制判断
            if($post['txtype'] == "wx"){
                if($post['txprice'] > 200)
                    $this->error('微信提现金额不能多于200');
            }else{
                if($post['txprice'] > 1000)
                    $this->error('银行卡提现金额不能多于1000');
            }
            $tx_fee = intval($post['txprice']) * self::$WAP["shopset"]["tx_fee_rate"] / 100;
            $vip['money'] = $vip['money'] - $post['txprice'] - $tx_fee;

            if (!$post['txprice'] || intval($post['txprice']) == 0) {
                $this->error('提现金额不能为空或0');
            }
            if ($post['txprice'] < self::$WAP['shopset']['tx_min_money']) {
                $this->error('提现金额不得少于' . self::$WAP['shopset']['tx_min_money'] . '！');
            }
            if ($vip['money'] < 0) {
                $this->error('您的账户余额不足！');
            }
            $vip["txname"] = $post['txname'];
            $vip["txmobile"] = $post['txmobile'];
            $vip["txyh"] = $post['txyh'];
            $vip["txfh"] = $post['txfh'];
            $vip["txszd"] = $post['txszd'];
            $vip["txcard"] = $post['txcard'];
            $rvip = $m->save($vip);
            $time = time();
            if (FALSE !== $rvip) {
                $post['vipid'] = $vipid;
                $post['txsqtime'] = $time;
                $post['status'] = 1;
                if ($post['txtype'] == "wx") {
                    $wxtx["billno"] = $this->GenBillNo();
                    $wxtx["vip_id"] = $vipid;
                    $wxtx["txprice"] = $post['txprice'];
                    $wxtx["txname"] = $post['txname'];
                    $wxtx["txmobile"] = $post['txmobile'];
                    $wxtx["txcard"] = $vip['nickname'];
                    $wxtx["status"] = 0;
                    $wxtx["txtime"] = $time;
                    $wxtx["tx_fee"] = $tx_fee;
                    $r = $mwxtx->add($wxtx);
                } else {
                    $post['tx_fee'] = $tx_fee;
                    $r = $mtx->add($post);
                }

                if ($r) {
                    $data_msg['pids'] = $vipid;
                    $data_msg['title'] = "您的提现申请已成功提交！";
                    $data_msg['content'] = "提现订单编号：" . $r . "<br><br>提现申请数量：" . $post['txprice'] . "<br><br>提现申请时间：" . date('Y-m-d H:i', time()) . "<br><br>提现申请将在三个工作日内审核完成，如有问题，请联系客服！";
                    $data_msg['ctime'] = $time;
                    $rmsg = M('vip_message')->add($data_msg);

                    $wechatTemplate = D('WechatTemplate');
                    $data_tpl = array("to_user" => self::$WAP["vip"]["openid"], "money" => $post['txprice'], "fee" => $tx_fee, "time" => $time, "id" => $r, "type" => $post["txtype"]);
                    $wechatTemplate->sendMessage_WithdrawApply($data_tpl);

                    // 发送消息完成============

                    $this->success('提现申请成功！');
                } else {
                    $data_msg['pids'] = $vipid;
                    $data_msg['title'] = "您的提现申请已成功提交！会在三个工作日内审核完毕并发放！";
                    $data_msg['content'] = "提现订单编号：" . $r . "<br><br>提现申请数量：" . $post['txprice'] . "<br><br>提现申请时间：" . date('Y-m-d H:i', time()) . "<br><br>" . $_SESSION['SHOP']['set']['yjname'] . "余额已扣除，但未成功生成提现订单，凭此信息联系客服补偿损失！";
                    $data_msg['ctime'] = time();
                    $rmsg = M('vip_message')->add($data_msg);
                    $this->error('余额扣除成功，但未成功生成提现申请，请联系客服！');
                }
            } else {
                $this->error('提现申请失败！请重新尝试！');
            }

        } else {
            $data = self::$WAP['vip'];
            $this->assign('data', $data);
            $this->display();
        }
    }

    public function address()
    {
        $backurl = base64_encode(U('App/Vip/address'));
        $this->checkLogin($backurl);
        $vipid = self::$WAP['vipid'];
        $m = M('VipAddress');
        $data = $m->where('vipid=' . $vipid)->select();
        $this->assign('data', $data);
        $this->display();
    }

    public function addressSet()
    {
        $backurl = base64_encode(U('App/Vip/address'));
        $this->checkLogin($backurl);
        $vipid = self::$WAP['vipid'];
        $schoolList = M('location_school')->where('is_open=1')->select();


        $m = M('VipAddress');
        if (IS_POST) {
            $post = I('post.');
            $post['vipid'] = $vipid;
            $tempData["vipid"] = $vipid;
            $tempData["address"] = $post["address"];
            $tempData["mobile"] = $post["mobile"];
            $tempData["name"] = $post["name"];
            $tempData["xqid"] = $post["xqid"];
            //更新用户信息
            $vipInfo["school_id"] = $post["school_id"];
            $vipInfo["remark_school"] = $post["school_name"];
            
            $r = $post['id'] ? $m->where(array("id" => $post['id']))->save($tempData) : $m->add($post);

            //更新vip 学校信息
             M('vip')->data($vipInfo)->where('id='.$vipid)->save();

            if (FALSE !== $r) {
                $info['status'] = 1;
                $info['msg'] = "信息保存成功！";
            } else {
                $info['status'] = 0;
                $info['msg'] = "信息保存失败！";
            }
            $this->ajaxReturn($info);
        } else {
            $data['mobile'] = self::$WAP['vip']['mobile'];
            $data['name'] = self::$WAP['vip']['name'];
            $data['school_id'] = self::$WAP['vip']['school_id'];
            $data['remark_school'] = self::$WAP['vip']['remark_school'];
            $data['schoolList'] = $schoolList;
            if($data['school_id'] == "-1"){
                $address = self::$WAP['vip']['remark_school'];
            }else{
                $addrD = M('location_school')->join('location_city on location_city.id = location_school.city_id')->where(array('location_school.id'=>$data['school_id']))->find();
                $address = $addrD['name'].$addrD['school_name'];
            }

            if (I('id')) {
                $data = $m->where('id=' . I('id'))->find();
            }
            $this->assign('data', $data);
            $this->assign('address',$address);
            $this->display();
        }
    }

    public function addressDel()
    {
        $backurl = base64_encode(U('App/Vip/address'));
        $this->checkLogin($backurl);
        $vipid = self::$WAP['vipid'];
        $m = M('VipAddress');
        if (IS_POST) {
            $r = $m->where('id=' . I('id') . ' and vipid=' . $vipid)->delete();
            if ($r) {
                $info['status'] = 1;
                $info['msg'] = "信息删除成功！";
            } else {
                $info['status'] = 0;
                $info['msg'] = "信息删除失败！";
            }
            $this->ajaxReturn($info);
        }
    }

    /**
     * 选择小区，暂时不用
     */
    public function xqChoose()
    {
        $m = M('xq');
        if (IS_POST) {
            $post = I('post.');
            $post['vipid'] = self::$WAP["vipid"];
            $post['xqgroupid'] = M('xq')->where('id=' . $post['xqid'])->getField('groupid');
            $r = $post['id'] ? $m->save($post) : $m->add($post);
            if ($r) {
                $info['status'] = 1;
                $info['msg'] = "地址保存成功！";
            } else {
                $info['status'] = 0;
                $info['msg'] = "地址保存失败！";
            }
            $this->ajaxReturn($info);
        } else {
            $data = $m->ORDER("convert(name USING gbk)")->select();
            foreach ($data as $k => $v) {
                $data[$k]['char'] = $this->getfirstchar($v['name']);
                if ($data[$k]['char'] == $data[$k - 1]['char']) {
                    $data[$k]['charshow'] = 0;
                } else {
                    $data[$k]['charshow'] = 1;
                }
            }
            if (I('addressid')) {
                $this->assign('addressid', I('addressid'));
            }
            $this->assign('data', $data);
            $this->display();
        }
    }

    //获取中文首字拼音字母
    public function getfirstchar($s0)
    {
        //手动添加未识别记录
        if (mb_substr($s0, 0, 1, 'utf-8') == "怡") {
            return "Y";
        }

        if (mb_substr($s0, 0, 1, 'utf-8') == "泗") {
            return "S";
        }

        $fchar = ord(substr($s0, 0, 1));
        if (($fchar >= ord("a") and $fchar <= ord("z")) or ($fchar >= ord("A") and $fchar <= ord("Z"))) {
            return strtoupper(chr($fchar));
        }

        $s = iconv("UTF-8", "GBK", $s0);
        $asc = ord($s{0}) * 256 + ord($s{1}) - 65536;
        //dump($s0.':'.$asc);
        if ($asc >= -20319 and $asc <= -20284) {
            return "A";
        }

        if ($asc >= -20283 and $asc <= -19776) {
            return "B";
        }

        if ($asc >= -19775 and $asc <= -19219) {
            return "C";
        }

        if ($asc >= -19218 and $asc <= -18711) {
            return "D";
        }

        if ($asc >= -18710 and $asc <= -18527) {
            return "E";
        }

        if ($asc >= -18526 and $asc <= -18240) {
            return "F";
        }

        if ($asc >= -18239 and $asc <= -17923) {
            return "G";
        }

        if ($asc >= -17922 and $asc <= -17418) {
            return "H";
        }

        if ($asc >= -17417 and $asc <= -16475) {
            return "J";
        }

        if ($asc >= -16474 and $asc <= -16213) {
            return "K";
        }

        if ($asc >= -16212 and $asc <= -15641) {
            return "L";
        }

        if ($asc >= -15640 and $asc <= -15166) {
            return "M";
        }

        if ($asc >= -15165 and $asc <= -14923) {
            return "N";
        }

        if ($asc >= -14922 and $asc <= -14915) {
            return "O";
        }

        if ($asc >= -14914 and $asc <= -14631) {
            return "P";
        }

        if ($asc >= -14630 and $asc <= -14150) {
            return "Q";
        }

        if ($asc >= -14149 and $asc <= -14091) {
            return "R";
        }

        if ($asc >= -14090 and $asc <= -13319) {
            return "S";
        }

        if ($asc >= -13318 and $asc <= -12839) {
            return "T";
        }

        if ($asc >= -12838 and $asc <= -12557) {
            return "W";
        }

        if ($asc >= -12556 and $asc <= -11848) {
            return "X";
        }

        if ($asc >= -11847 and $asc <= -11056) {
            return "Y";
        }

        if ($asc >= -11055 and $asc <= -10247) {
            return "Z";
        }

        return "?";
    }

    public function about()
    {
        $this->assign('shop', self::$SHOP['set']);
        $this->display();
    }

    public function intro()
    {
        $this->display();
    }

    public function cz()
    {
        $this->display();
    }

    public function zxczSet()
    {
        $backurl = base64_encode(U('App/Vip/cz'));
        $this->checkLogin($backurl);
        $vipid = self::$WAP['vipid'];
        $money = I('money');
        $type = I('type');
        //记录充值log，同时作为充值返回数据调用
        $data_log['ip'] = get_client_ip();
        $data_log['vipid'] = $vipid;
        $data_log['ctime'] = time();
        $data_log['event'] = "会员在线充值";
        $data_log['money'] = $money;
        $data_log['score'] = round($money * self::$WAP['vipset']['cz_score'] / 100);
        $data_log['exp'] = round($money * self::$WAP['vipset']['cz_exp'] / 100);
        $data_log['opid'] = date('Ymd') . substr(implode(NULL, array_map('ord', str_split(substr(uniqid(), 7, 13), 1))), 0, 8);
        $data_log['status'] = 1;
        $data_log['type'] = 7;
        $re = M('vip_log')->add($data_log);
        //跳转充值页面
        if ($re) {
            switch ($type) {
                case '1':
                    $this->redirect('App/Alipay/alipay', array('price' => $money, 'oid' => $data_log['opid']));
                    break;
                case '2':
                    $this->redirect('Home/Wxpay/pay', array('price' => $money, 'oid' => $data_log['opid']));
                    break;
                default:
                    $this->error('支付方式未知！');
                    break;
            }
        } else {
            $this->error('出错啦！');
        }

    }

    public function card()
    {
        $backurl = base64_encode(U('App/Vip/card'));
        $this->checkLogin($backurl);
        $vipid = self::$WAP['vipid'];
        $m = M('vip_card');
        $status = I('status') ? intval(I('status')) : 1;
        $map['status'] = $status;
        $today = strtotime(date('Y-m-d'));
        if ($status == 3) {
            $map['etime'] = array('LT', $today);
            $map['status'] = 1;
        } else if ($status == 1) {
            $map['etime'] = array('EGT', $today);
        }
        $map['vipid'] = $vipid;
        $map['type'] = 2; //代金券

        $data = $m->where($map)->select();

        $this->assign('data', $data);
        $this->assign('status', $status);
        $this->display();
    }

    /**
     * 添加卡券，暂时不用
     */
    public function addCard()
    {
        $backurl = base64_encode(U('App/Vip/card'));
        $this->checkLogin($backurl);
        $vipid = self::$WAP['vipid'];
        $m = M('VipCard');
        $map = I('post.');
//        $map['type'] = 2; //充值卡充值
        $card = $m->where($map)->find();
        if ($card) {
            if ($card['status'] == 0) {
                //未发卡
                $info['status'] = 0;
                $info['msg'] = '此卡尚未激活，请重试或联系管理员！';
            } else if ($card['status'] == 2) {
                //已使用
                $info['status'] = 0;
                $info['msg'] = '此卡已使用过了哦！';
            } else if ($card['status'] == 1) {
                //修改会员信息：账户金额、积分、经验、等级
                $data_vip['money'] = array('exp', 'money+' . $card['money']);
                $data_vip['score'] = array('exp', 'score+' . round($card['money'] * self::$WAP['vipset']['cz_score'] / 100));
                if (round($card['money'] * self::$WAP['vipset']['cz_exp'] / 100) > 0) {
                    $data_vip['exp'] = array('exp', 'exp+' . round($card['money'] * self::$WAP['vipset']['cz_exp'] / 100));
                    $data_vip['cur_exp'] = array('exp', 'cur_exp+' . round($card['money'] * self::$WAP['vipset']['cz_exp'] / 100));
                    $level = $this->getLevel(self::$WAP['vip']['cur_exp'] + round($card['money'] * self::$WAP['vipset']['cz_exp'] / 100));
                    $data_vip['levelid'] = $level['levelid'];
                }
                $re = M('vip')->where('id=' . $vipid)->save($data_vip);
                if ($re) {
                    //修改卡状态
                    $card['status'] = 2;
                    $card['vipid'] = $vipid;
                    $card['usetime'] = time();
                    $m->save($card);
                    //记录日志
                    $data_log['ip'] = get_client_ip();
                    $data_log['vipid'] = $vipid;
                    $data_log['ctime'] = time();
                    $data_log['event'] = "会员充值卡充值";
                    $data_log['money'] = $card['money'];
                    $data_log['score'] = round($card['money'] * self::$WAP['vipset']['cz_score'] / 100);
                    $data_log['exp'] = round($card['money'] * self::$WAP['vipset']['cz_exp'] / 100);
                    $data_log['opid'] = $card['id'];
                    $data_log['type'] = 6;
                    M('vip_log')->add($data_log);

                    $info['status'] = 1;
                    $info['msg'] = '充值成功！前往会员中心查看？';
                } else {
                    $info['status'] = 0;
                    $info['msg'] = '充值失败，请重试或联系管理员！';
                }
            } else {
                $info['status'] = 0;
                $info['msg'] = '此卡状态异常，请重试或联系管理员！';
            }
        } else {
            $info['status'] = 0;
            $info['msg'] = '卡号密码有误，请核对后重试！';
        }
        $this->ajaxReturn($info);
    }

    /**
     * 添加代金券，暂时不用
     */
    public function addVipCard()
    {
        $backurl = base64_encode(U('App/Vip/card'));
        $this->checkLogin($backurl);
        $vipid = self::$WAP['vipid'];
        $m = M('VipCard');
        $map = I('post.');
        $map['type'] = 2; //代金券
        $card = $m->where($map)->find();
        if ($card) {
            if ($card['status'] == 0) {
                $m->where(array("id" => $card["id"]))->save(array("vipid" => $vipid, "status" => 1));
                $this->ajaxReturn(array("info" => "充值成功"));
            } else {
                $this->ajaxReturn(array("info" => "充值失败"));
            }
        }
        $this->ajaxReturn(array("info" => "充值失败"));

    }

    //生在订单号
    public function GenBillNo()
    {
        $rnd_num = array('0', '1', '2', '3', '4', '5', '6', '7', '8', '9');
        $rndstr = "";
        while (strlen($rndstr) < 10) {
            $rndstr .= $rnd_num[array_rand($rnd_num)];
        }

        return $this->mchid . date("Ymd") . $rndstr;
    }

    /**
     *  申请成为推广人员 -- 此页面只能通过via生成的二维码进入
     *  $param vaiId 链接附带via的id
     */
    public function applyTg(){
        //只有普通会员可以申请成为推广人员
       /* if(self::$WAP['vip']['role'] != 0){
            $this->diemsg(0,'只有普通会员可以申请成为推广人员');
        }*/
        if(IS_AJAX){
            $data['apply_id'] = I('post.apply_id/d');
            $data['apply_nickname'] = I('post.apply_nickname');
            $data['via_id'] = I('post.via_id/d');
            $data['via_nickname'] = I('post.via_nickname');
            $data['create_time'] = time();
            $data['status'] = 0;    //申请中
            //检查是否已经申请过了
            $m = M('apply_tg');
            $count = $m->where(['apply_id'=>$data['apply_id'],'status'=>0])->count();
            if($count>0){
                $this->ajaxReturn(['status'=>0,'msg'=>'请勿重复申请']);
            }else{
                $id = M('apply_tg')->add($data);
                if($id){
                    //给via发送模板消息
                    $data['to_user'] = M('vip')->where('id='.$data['via_id'])->getField('openid');
                    $data['name'] = $data['apply_nickname'];
                    $data['apply_tg_id'] = $id;
                    $wechatTemplate = D('WechatTemplate');
                    $wechatTemplate->sendMessage_applyTg($data);

                    $this->ajaxReturn(['status'=>1,'msg'=>'申请成功，请等待VIA审核']);
                }else{
                    $this->ajaxReturn(['status'=>0,'msg'=>'数据保存失败，请重试']);
                }
            }
        }else{
            $viaId = I('get.viaId/d');
            $expireTime = I('get.expireTime/d');
            if($expireTime < time()){   //如果时间比现在小，说明过期了
                $this->diemsg(0,'二维码已过期，请重新联系VIA生成二维码');
            }
            if($viaId){
                $viaInfo = M('vip')->field('role,nickname')->where('id='.$viaId)->find();
                if($viaInfo['role'] == 2){  //被扫的儿二维码只能是via的
                    $data['apply_id'] = self::$WAP['vip']['id'];
                    $data['apply_nickname'] = self::$WAP['vip']['nickname'];
                    $data['via_id'] = $viaId;
                    $data['via_nickname'] = $viaInfo['nickname'];
                    $this->assign('data',$data);
                    $this->display();
                    exit;
                }
            }
            $this->diemsg(0,'参数错误，请联系VIA更新二维码');
        }
    }
}
