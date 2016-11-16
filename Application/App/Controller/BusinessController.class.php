<?php

namespace App\Controller;


use Common\Model\BalancePayModel;
use Org\Net\IpLocation;

class BusinessController extends BaseController
{

    public function _initialize()
    {
        //你可以在此覆盖父类方法
        parent::_initialize();
    }

    /**
     * 选择支付方式页面
     */
    public function payView()
    {
        $id = intval(I("id"));
        $this->assign('id', $id);
        if ($id > 0) {
            $order = M("supplier_order")->where(array("id" => $id, "status" => 0))->find();
            $this->assign('order', $order);
            $money = self::$WAP["vip"]["money"] - $order["total_price"];
            $this->assign("money", $money);
            $this->assign('type', ($money >= 0 ? "money" : "wxpay"));
        }

        $this->display();
    }

    /**
     * 创建订单
     */
    public function orderCreate()
    {
        $id = I("id");
        $money = floatval(I("mn"));
        $memo = I("mm");
        if ($money <= 0) {
            $this->error('消费额必须大于0');
        }
        $data["creator_type"] = 0;
        $data["creator_id"] = self::$WAP["vipid"];
        $data["order_code"] = getOrderCode(self::$WAP["vipid"], 1);
        $data["vip_buyer_id"] = self::$WAP["vipid"];
        $store = M("supplier_store")->where(array("id" => $id))->find();
        $vip = M("vip")->where(array("supplier_id" => $store["supplier_id"]))->find();
        $data["vip_seller_id"] = $vip["id"];
        $data["supplier_id"] = $store["supplier_id"];
        $data["store_id"] = $id;
        $data["total_price"] = $money;
        $data["status"] = 0;
        $data["pay_type"] = "";
        $data["pay_price"] = $money;
        $data["is_pay"] = 0;
        $data["is_need_return"] = 1;
        $data["is_check_account"] = 0;
        $data["order_time"] = time();
        $data["is_payforother"] = 0;
        $data["memo"] = $memo;
        $data["vip_buyer_openid"] = self::$WAP["vip"]["openid"];
        $data["vip_buyer_name"] = empty(self::$WAP["vip"]["name"]) ? self::$WAP["vip"]["nickname"] : self::$WAP["vip"]["name"];
        $data["vip_buyer_mobile"] = empty(self::$WAP["vip"]["mobile"]) ? "" : self::$WAP["vip"]["mobile"];
        $rlt = M("supplier_order")->add($data);
        if ($rlt) {

            $log = M('Supplier_order_log');
            $llog['oid'] = $data["order_code"];
            $llog['msg'] = '订单生成';
            $llog['type'] = 1;
            $llog['ctime'] = time();
            $log->add($llog);

            $mslog = M('Supplier_order_syslog');
            $dlog['oid'] = $data["order_code"];
            $dlog['msg'] = '订单生成';
            $dlog['type'] = 1;
            $dlog['paytype'] = "offline";
            $dlog['ctime'] = time();
            $mslog->add($dlog);

            $this->redirect("App/Business/payView", array("id" => $rlt));
        } else {
            $this->redirect("App/Business/payView", array("id" => 0));
        }
    }

    /**
     * 消费后买单
     */
    public function order()
    {
        $bkurl = U('App/Business/order', array("id" => I("id")));
        $backurl = base64_encode($bkurl);
        $this->checkLogin($backurl);
        $store = M("supplier_store")->where(array("id" => I("id")))->find();
        if ($store) {
            $this->assign('store', $store);
        } else {
            $this->diemsg(0, '店铺不存在！');
        }
        $this->display();
    }

    /**
     * 订单支付
     */
    public function pay()
    {
        $sid = I('sid') <> '' ? I('sid') : $this->diemsg(0, '缺少SID参数');//sid可以为0
        $orderid = I('orderid') <> '' ? I('orderid') : $this->diemsg(0, '缺少ORDERID参数');
        $type = I('type');
        $bkurl = U('App/Business/payView', array('sid' => $sid, 'id' => $orderid, 'type' => $type));
        //$backurl=base64_encode($orderdetail);
        $backurl = base64_encode($bkurl);
        $loginurl = U('App/Vip/login', array('backurl' => $backurl));
        $re = $this->checkLogin($backurl);
        //已登陆
        $m = M('Supplier_order');
        $order = $m->where('id=' . $orderid)->find();
        if (!$order) {
            $this->error('此订单不存在！');
        }
        if ($order['status'] <> 0) {
            $this->error('此订单不可以支付！');
        }
        $paytype = I('type') ? I('type') : $order['pay_type'];
        switch ($paytype) {
            case 'money':
                $money_pay = D("BalancePay");
                $info = $money_pay->pay($order["order_code"], OT_SM);
                if ($info["status"] == 0) {
                    //
                    $this->success($info["msg"], U('App/Shop/orderList', array('sid' => $sid, 'type' => '3')));
                } else {
                    $this->error($info["msg"], U('App/Vip/store', array()));
                }
                break;
            case 'alipayApp':
                $this->redirect("App/Alipay/alipay", array('sid' => $sid, 'price' => $order['pay_price'], 'oid' => $order['order_code'], "cate" => 1));
                break;
            case 'wxpay':
                $_SESSION['wxpaysid'] = 0;
                $_SESSION['wxpayopenid'] = $_SESSION['WAP']['vip']['openid'];//追入会员openid
                $this->redirect('Home/Wxpay/pay', array('oid' => $order['order_code'], "cate" => 1));
                break;
            default:
                $this->error('支付方式未知！');
                break;
        }
    }

    //订单取消
    public function orderCancel()
    {
        $sid = I('sid') <> '' ? I('sid') : $this->diemsg(0, '缺少SID参数');//sid可以为0
        $orderid = I('orderid') <> '' ? I('orderid') : $this->diemsg(0, '缺少ORDERID参数');
        $bkurl = U('App/Business/orderDetail', array('sid' => $sid, 'orderid' => $orderid));
        $backurl = base64_encode($bkurl);
        $loginurl = U('App/Vip/login', array('backurl' => $backurl));
        $re = $this->checkLogin($backurl);
        //已登陆
        $m = M('Supplier_order');
        $map['id'] = $orderid;
        $cache = $m->where($map)->find();
        if (!$cache) {
            $this->diemsg(0, '此订单不存在!');
        }
        if ($cache['status'] <> 0 || $cache["is_pay"] == 1) {
            $this->error('只有未付款订单可以取消！');
        }
        $data["status"] = 3;
        $data["close_time"] = time();
        $re = $m->where($map)->save($data);
        if ($re) {

            $log = M('Supplier_order_log');
            $llog['oid'] = $data["order_code"];
            $llog['msg'] = '订单取消';
            $llog['type'] = -1;
            $llog['ctime'] = time();
            $log->add($llog);

            //订单取消只有后端日志
            $mslog = M('Supplier_order_syslog');
            $dlog['oid'] = $cache['id'];
            $dlog['msg'] = '订单取消';
            $dlog['type'] = -1;
            $dlog['ctime'] = time();
            $rlog = $mslog->add($dlog);
            $this->success('订单取消成功！');
        } else {
            $this->error('订单取消失败,请重新尝试！');
        }
    }

    public function storeView()
    {
        $data = M("supplier_store")->where(array("id" => I("id")))->find();
        if (!empty($data)) {
            $listpic = $this->getAlbum($data["pics"]);
            $data['pics'] = $listpic;
            $abc = $this->getPic($data["thumb"]);
            $data['thumb'] = $abc["imgurl"];
        }
        $this->assign('data', $data);
        $this->display();
    }

    public function storeItem()
    {
        $city = I("city");
        $lat = I("lat");
        $lng = I("lng");
        $cid = I("cid");
        $page = I("page"); //每页条数
        if ($page > 20) {
            $page = 20;
        }
        $cids = M("Shop_cate")->where(array("id" => $cid))->find();
        $num = I("num"); //第几页
        $map["category_id"] = array("in", $cids["soncate"] . $cid);
        $map["city"] = $city;
        $map["status"] = 0;
        $juli = 'FORMAT(ACOS(SIN((' . $lat . ' * 3.1415) / 180 ) *SIN((lat * 3.1415) / 180 ) +COS((' . $lat . ' * 3.1415) / 180 ) * COS((lat * 3.1415) / 180 ) *COS((' . $lng . ' * 3.1415) / 180 - (lng * 3.1415) / 180 ) ) * 6378.138,1)';
        $data = M("supplier_store")->field("*," . $juli . " as juli")
            ->order("juli desc")
            ->limit($page * $num, $page)
            ->where($map)
            ->select();
        if ($data == null) {
            $data = array();
        }
        for ($i = 0; $i < count($data); $i++) {
            $listpic = $this->getPic($data[$i]["thumb"]);
            $data[$i]['imgurl'] = $listpic['imgurl'];
        }
        $this->assign('data', $data);
        $this->display();
    }

    public function stores()
    {
        $cid = I("id");
        $cate = M("Shop_cate")->where(array("id" => $cid))->find();
        $this->assign('cate', $cate);
        $this->assign('cid', $cid);
        $this->display();
    }

    public function search()
    {
        $wh["is_enable"] = 1;
        $wh["id"] = array("in", in_parse_str(self::$WAP['shopset']['indexgroup_shangmeng']));
        $menu_sm = M("Shop_cate")->where($wh)->order("sorts desc")->select();
        foreach ($menu_sm as $k => $v) {
            $listpic = $this->getPic($v['icon']);
            $menu_sm[$k]['imgurl'] = $listpic['imgurl'];
        }
        $this->assign('menu_sm', $menu_sm);
        $this->display();
    }

    public function selectCity()
    {
        $wh["is_open"] = 1;
        $city = M("location_city")->where($wh)->select();
        $this->assign('city', $city);
        $this->display();
    }

    public function indexItem()
    {
        $page = intval(I("pg"));
        $page_count = intval(I("pc"));
        if ($page_count > 20) {
            $page_count = 20;
        }
        $city = I("city");
        $store = M("supplier_store")->where(array("status" => 0, "order_index" => array("gt", 0), "city" => $city))->order("order_index desc")->limit($page * $page_count, $page_count)->select();
        foreach ($store as $k => $v) {
            $listpic = $this->getPic($v['thumb']);
            $store[$k]['imgurl'] = $listpic['imgurl'];
        }
        $this->assign('store', $store);
        $this->display();
    }

    public function index()
    {
        $wh["is_enable"] = 1;
        $wh["id"] = array("in", in_parse_str(self::$WAP['shopset']['indexgroup_shangmeng']));
        $menu_sm = M("Shop_cate")->where($wh)->order("sorts desc")->select();
        foreach ($menu_sm as $k => $v) {
            $listpic = $this->getPic($v['icon']);
            $menu_sm[$k]['imgurl'] = $listpic['imgurl'];
        }
        $this->assign('menu_sm', $menu_sm);

        $map["id"] = array('in', in_parse_str(self::$WAP['shopset']['indexalbum_shangmeng']));
        $indexalbum_sm = M('Shop_ads')->where($map)->select();
        foreach ($indexalbum_sm as $k => $v) {
            $listpic = $this->getPic($v['pic']);
            $indexalbum_sm[$k]['imgurl'] = $listpic['imgurl'];
        }
        $this->assign('indexalbum_sm', $indexalbum_sm);

        $this->assign('ip', get_client_ip());

        $this->display();
    }
}