<?php
/**
 * Created by PhpStorm.
 * User: heqing
 * Date: 15/8/2
 * Time: 10:36
 */

namespace App\Controller;

use Think\Controller;

class AlipayController extends Controller
{
    public $appUrl = "";
    // 缓存全局商城配置对象
    public static $_shop;
    //App全局相关
    public static $_logs = './logs/alipaywap/';//log地址
    public static $_opt;//参数缓存


    public function __construct()
    {
        parent::__construct();
        $this->appUrl = "http://" . I("server.HTTP_HOST");
    }

    public function init()
    {
        $alipay_config = M("Alipay")->find();
        $config = array(
            // 即时到账方式
            'payment_type' => 1,
            // 传输协议
            'transport' => 'http',
            // 编码方式
            'input_charset' => 'utf-8',
            // 签名方法
            'sign_type' => 'MD5',
            // 支付完成异步通知调用地址
            'notify_url' => $this->appUrl . U('App/Alipay/notify_url'),
            // 支付完成同步返回地址
            'return_url' => $this->appUrl . U('App/Alipay/return_url'),
            // 证书路径
            'cacert' => DATA_PATH . 'Alipay/cacert.pem',
            // 支付宝商家 ID
            'partner' => $alipay_config['partner'],
            // 支付宝商家 KEY
            'key' => $alipay_config['key'],
            // 支付宝商家注册邮箱
            'seller_email' => $alipay_config['alipayname']
        );
        return $config;
    }

    public function alipay()
    {
//        $is_weixin = $this->is_weixin();
//        if (!$is_weixin) {
//            $this->redirect("Empty/index");
//            return;
//        }
        $type = intval($_GET("cate"));//0:商城，1：商盟
        self::$_opt['oid'] = $oid = $_GET['oid'];
        self::$_opt['price'] = $price = $_GET['price'];
        self::$_opt['sid'] = $sid = $_GET['sid'];

        if ($oid == '' || $price == '' || $sid == '') {
            $msg = '订单参数不完整！';
            die($msg);
        }

        Vendor("Alipay.Alipay#class");
        $config = $this->init();
        $alipay = new \Alipay($config, TRUE);

        $params = $alipay->prepareMobileTradeData(array(
            'out_trade_no' => $oid,
            'subject' => $oid,
            'body' => $oid,
            'total_fee' => floatval($price),
            'merchant_url' => $this->appUrl . U('App/Shop/orderList', array('sid' => 0)),
            'req_id' => date('Ymdhis')
        ));

        // 移动网页版接口只支持 GET 方式提交
        $url = $alipay->buildRequestFormHTML($params, 'get');

        $this->assign("url", $url);
        $this->display();
    }

    public function simplest_xml_to_array($xmlstring)
    {
        return json_decode(json_encode((array)simplexml_load_string($xmlstring)), true);
    }

    public function notify_url()
    {
        Vendor("Alipay.Alipay#class");
        $config = $this->init();
        //计算得出通知验证结果
        $alipay = new \Alipay($config, TRUE);
        $verify_result = $alipay->verifyCallback(TRUE);

        if ($verify_result) {//验证成功
            /////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
            //请在这里加上商户的业务逻辑程序代


            //——请根据您的业务逻辑来编写程序（以下代码仅作参考）——

            //获取支付宝的通知返回参数，可参考技术文档中服务器异步通知参数列表
            //获取支付宝的通知返回参数，可参考技术文档中服务器异步通知参数列表
            $_POST = $this->simplest_xml_to_array($_POST['notify_data']);

            //商户订单号

            $out_trade_no = $_POST['out_trade_no'];

            //支付宝交易号

            $trade_no = $_POST['trade_no'];

            //交易状态
            $trade_status = $_POST['trade_status'];


            if ($_POST['trade_status'] == 'TRADE_FINISHED') {
                //判断该笔订单是否在商户网站中已经做过处理
                //如果没有做过处理，根据订单号（out_trade_no）在商户网站的订单系统中查到该笔订单的详细，并执行商户的业务程序
                //如果有做过处理，不执行商户的业务程序

                //注意：
                //该种交易状态只在两种情况下出现
                //1、开通了普通即时到账，买家付款成功后。
                //2、开通了高级即时到账，从该笔交易成功时间算起，过了签约时的可退款时限（如：三个月以内可退款、一年以内可退款等）后。

                //调试用，写文本函数记录程序运行情况是否正常
                //logResult("这里写入想要调试的代码变量值，或其他运行的结果记录");
            } else if ($_POST['trade_status'] == 'TRADE_SUCCESS') {
                //判断该笔订单是否在商户网站中已经做过处理
                //如果没有做过处理，根据订单号（out_trade_no）在商户网站的订单系统中查到该笔订单的详细，并执行商户的业务程序
                //如果有做过处理，不执行商户的业务程序

                //注意：
                //该种交易状态只在一种情况下出现——开通了高级即时到账，买家付款成功后。

                //调试用，写文本函数记录程序运行情况是否正常
                //logResult("这里写入想要调试的代码变量值，或其他运行的结果记录");
                $this->endpay($out_trade_no);
            }


            //——请根据您的业务逻辑来编写程序（以上代码仅作参考）——

            echo "success";        //请不要修改或删除

            /////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
        } else {
            //验证失败
            echo "fail";

            //调试用，写文本函数记录程序运行情况是否正常
            //logResult("这里写入想要调试的代码变量值，或其他运行的结果记录");
        }
    }

    public function return_url()
    {
        $this->redirect("App/Shop/orderList", array("sid" => 0));
    }


    //充值成功后操作
    public function endcz($oid)
    {
        $m = M('vip_log');
        $order = $m->where(array('opid' => $oid))->find();
        if ($order) {
            if ($order['status'] == 1) {
                //修改状态
                $order['status'] = 2;
                $order['ctime'] = time();
                $re = $m->save($order);
                if (FALSE !== $re) {
                    //修改会员账户金额、经验、积分、等级
                    $zsmoney = $this->getZsmoney($order['money']);//充值活动赠送
                    $addmoney = $order['money'] + $zsmoney;
                    $data_vip['id'] = $order['vipid'];
                    $data_vip['money'] = array('exp', 'money+' . $addmoney);
                    $data_vip['score'] = array('exp', 'score+' . $order['score']);
                    if ($order['exp'] > 0) {
                        $vip = M('vip')->where('id=' . $order['vipid'])->find();
                        $vipset = M('vip_set')->find();
                        $data_vip['exp'] = array('exp', 'exp+' . $order['exp']);
                        $data_vip['cur_exp'] = array('exp', 'cur_exp+' . $order['exp']);
                        $level = $this->getLevel($vip['cur_exp'] + $order['exp']);
                        $data_vip['levelid'] = $level['levelid'];
                    }
                    if (FALSE === M('vip')->save($data_vip)) {
                        //记录报警信息
                        $str = "订单号：" . $oid . "充值成功但更新会员信息失败！";
                        file_put_contents(self::$_logs . 'App_error.txt', '支付宝移动支付报警:' . date('Y-m-d H:i:s') . PHP_EOL . '通知信息:' . $str . PHP_EOL . PHP_EOL . '交易类型:TRADE_SUCCESS' . PHP_EOL . PHP_EOL, FILE_APPEND);
                    }
                } else {
                    //记录报警信息
                    $str = "订单号：" . $oid . "充值成功但未更新日志信息！";
                    file_put_contents(self::$_logs . 'App_error.txt', '支付宝移动支付报警:' . date('Y-m-d H:i:s') . PHP_EOL . '通知信息:' . $str . PHP_EOL . PHP_EOL . '交易类型:TRADE_SUCCESS' . PHP_EOL . PHP_EOL, FILE_APPEND);
                }
            }
        }
    }

    //付款成功后操作
    public function endpay($oid, $buyer_email)
    {
        $m = M('Shop_order');
        $order = $m->where(array('oid' => $oid))->find();
        if ($order) {
            if ($order['status'] == 1) {
                //修改状态
                $order['ispay'] = 1;
                $order['status'] = 2;
                $order['paytime'] = time();
                $order['aliaccount'] = $buyer_email;
                $re = $m->save($order);
                if (FALSE !== $re) {
                    //销量计算-只减不增
                    $rsell = $this->doSells($order);
                    //记录日志
                    $mlog = M('Shop_order_log');
                    $mslog = M('Shop_order_syslog');
                    $dlog['oid'] = $order['id'];
                    $dlog['msg'] = '支付宝付款成功';
                    $dlog['ctime'] = time();
                    $mlog->add($dlog);
                    $dlog['type'] = 2;
                    $dlog['paytype'] = $cache['paytype'];
                    $mslog->add($dlog);

                    //支付成功后设置为花蜜
                    $mvip = M('Vip');
                    $vip = $mvip->where('id=' . $order['vipid'])->find();
                    if ($vip && !$vip['isfx']) {
                        $rvip = $mvip->where('id=' . $order['vipid'])->setField('isfx', 1);
                        $data_msg['pids'] = $order['vipid'];
                        $data_msg['title'] = "您成功升级为花漾兰嘉的花蜜！";
                        $data_msg['content'] = "欢迎成为花漾兰嘉的花蜜，开启一个新的旅程！";
                        $data_msg['ctime'] = time();
                        $rmsg = M('vip_message')->add($data_msg);
                    }

                } else {
                    //记录报警信息
                    $str = "订单号：" . $oid . "支付成功但未更新订单状态！" . "买家帐号：" . $buyer_email;
                    file_put_contents(self::$_logs . 'App_error.txt', '支付宝移动支付报警:' . date('Y-m-d H:i:s') . PHP_EOL . '通知信息:' . $str . PHP_EOL . PHP_EOL . '交易类型:TRADE_SUCCESS' . PHP_EOL . PHP_EOL, FILE_APPEND);
                }
                //发送已付款订单模板消息给商家
                //$this -> sendMobanMsmToShop($order['id'],1);
                //发送支付成功莫办消息给会员
                // $this -> sendMobanMsmToVip1($order['id']);


                // 插入订单支付成功模板消息=====================
                $templateidshort = 'OPENTM200444326';
                $dwechat = D('Wechat');
                $templateid = $dwechat->getTemplateId($templateidshort);
                $mvip = M('Vip');
                $vip = $mvip->where('id=' . $order['vipid'])->find();

                if ($templateid) { // 存在才可以发送模板消息
                    $data = array();
                    $data['touser'] = $vip['openid'];
                    $data['template_id'] = $templateid;
                    $data['topcolor'] = "#00FF00";
                    $data['data'] = array(
                        'first' => array('value' => '您好，您的订单已付款成功'),
                        'keyword1' => array('value' => $order['oid']),
                        'keyword2' => array('value' => date("Y-m-d h:i:sa", $order['paytime'])),
                        'keyword3' => array('value' => $order['payprice']),
                        'keyword4' => array('value' => $order['paytype']),
                        'remark' => array('value' => '')
                    );

                    $wxset = D('Set')->find();
                    // 调试file_put_contents("appid.txt",$wxset['wxappid']);
                    $options['appid'] = $wxset['wxappid'];
                    $options['appsecret'] = $wxset['wxappsecret'];

                    $wx = new \Util\Wx\Wechat($options);
                    $re = $wx->sendTemplateMessage($data);
                }
                // 插入订单支付成功模板消息结束=================          


            }
        }
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

    //根据当前经验计算等级信息
    public function getlevel($exp)
    {
        $data = M('vip_level')->order('exp')->select();
        if ($data) {
            $level = array();
            foreach ($data as $k => $v) {
                if ($k + 1 == count($data)) {
                    if ($exp >= $data[$k]['exp']) {
                        $level['levelid'] = $data[$k]['id'];
                        $level['levelname'] = $data[$k]['name'];
                    }
                } else {
                    if ($exp >= $data[$k]['exp'] && $exp < $data[$k + 1]['exp']) {
                        $level['levelid'] = $data[$k]['id'];
                        $level['levelname'] = $data[$k]['name'];
                    }
                }
            }
        } else {
            return utf8error('会员等级未定义！');
        }
        return $level;
    }

    //根据充值金额计算赠送金额
    public function getZsmoney($money)
    {
        $vipset = M('vip_set')->find();
        $cz_rule = explode(",", $vipset['cz_rule']);
        $zsmoney = 0;
        foreach ($cz_rule as $k => $v) {
            $cz_rule[$k] = explode(":", $v);
        }
        foreach ($cz_rule as $k => $v) {
            if ($k + 1 == count($cz_rule)) {
                if ($money >= $cz_rule[$k][0]) {
                    $zsmoney = intval($cz_rule[$k][1]);
                }
            } else {
                if ($money >= $cz_rule[$k][0] && $money < $cz_rule[$k + 1][0]) {
                    $zsmoney = intval($cz_rule[$k][1]);
                }
            }
        }
        return $zsmoney;
    }


    //发送商家模板消息
    //type=0:订单新生成（未付款）
    //type=1:订单已付款
    function sendMobanMsmToShop($oid, $type, $flag = FALSE)
    {
        //构造消息体
        $order = M('wds_order')->where('id=' . $oid)->find();
        $shop = M('Shop')->where('id=' . $order['sid'])->find();
        $ppid = $order['ppid'];
        if ((($type == 0 && $shop['newmsg'] == 1) || ($type == 1 && $shop['paymsg'] == 1)) && $shop['kfids'] != '') {
            if ($order['mobile'] == '') {
                $addressinfo = M('vip_address')->where('ppid=' . $ppid)->order('isdefault asc')->select();
                if ($addressinfo) {
                    $customerInfo = $addressinfo[0]['name'] . ' ' . $addressinfo[0]['mobile'];
                } else {
                    $customerInfo = '暂未登记';
                }
            } else {
                $customerInfo = $order['name'] . ' ' . $order['mobile'];
            }

            if ($type == 0) {
                $first = "新订单通知（暂未付款）";
            } else {
                $first = $flag ? "订单通知（货到付款）" : "订单通知（已付款）";
            }
            $first = $first . "\\n订单编号：" . $order['oid'];

            $arr = explode('|', $order['goods']);
            $money = 0;
            $remark = "\\n";
            foreach ($arr as $k => $val) {
                $a = explode(',', $val);
                $money = $money + $a['5'] * $a['3'];
                $remark = $remark . $a['1'] . "：" . $a['3'] . $a['4'] . "\\n";
            }

            $template = array(
                'touser' => "",
                'template_id' => "Gk4Olsma5qlneSsdAWK3J9w1t7eRxVkNRGFcxiHfk6g",
                'url' => "",
                'topcolor' => "#70c02f",
                'data' => array(
                    //标题
                    'first' => array(
                        'value' => urlencode($first),
                        'color' => "#FF0000",
                    ),
                    //提交时间
                    'tradeDateTime' => array(
                        'value' => urlencode(date('Y-m-d H:i:s', time())),
                        //'color' => "#0000FF",
                    ),
                    //订单类型
                    'orderType' => array(
                        'value' => urlencode($shop['name']),
                        //'color' => "#0000FF",
                    ),
                    //顾客信息
                    'customerInfo' => array(
                        'value' => urlencode($customerInfo),
                        //'color' => "#0000FF",
                    ),
                    //商品名称
                    'orderItemName' => array(
                        'value' => urlencode("订单总价"),
                        //'color' => "#0000FF",
                    ),
                    //商品规格及数量
                    'orderItemData' => array(
                        'value' => urlencode($order['money'] . '元'),
                        'color' => "#006cff",
                    ),
                    //备注
                    'remark' => array(
                        'value' => urlencode($remark),
                        'color' => "#565656",
                    ),
                )
            );
            //发送消息
            $options['appid'] = self::$_wxappid;
            $options['appsecret'] = self::$_wxappsecret;
            $mx = new \Util\Wx\Wechat($options);

            $shop['kfids'] = $shop['kfids'] == '' ? '10' : $shop['kfids'] . ',10';//发送给指定客服id：10

            $kfidArr = explode(",", $shop['kfids']);
            foreach ($kfidArr as $k => $val) {
                $openid = M('vip')->where('id=' . $val)->getField('openid');
                $template['touser'] = $openid;
                $rtn = $mx->sendMobanMessage($template);
                file_put_contents('./logs/message/msg.txt', PHP_EOL . '发送商家模板消息成功:' . date('Y-m-d H:i:s') . $rtn, FILE_APPEND);
            }
        }
    }

    //发送会员模板消息
    function sendMobanMsmToVip($oid)
    {
        //构造消息体
        $order = M('wds_order')->where('id=' . $oid)->find();
        $shop = M('Shop')->where('id=' . $order['sid'])->find();
        $ppid = $order['ppid'];

        $openid = M('vip')->where('id=' . $ppid)->getField('openid');

        $arr = explode('|', $order['goods']);
        $money = 0;
        $goodsinfo = $shop['name'] . "\\n\\n";
        foreach ($arr as $k => $val) {
            $a = explode(',', $val);
            $money = $money + $a['5'] * $a['3'];
            $goodsinfo = $goodsinfo . $a['1'] . "：" . $a['3'] . $a['4'] . "\\n";
        }

        $template = array(
            'touser' => $openid,
            'template_id' => "5qJqoRxrgO8W9amLF6aejYMag4mAxSUh3OpMrgnJ2cw",
            'url' => "http://" . $_SERVER['HTTP_HOST'] . __ROOT__ . "/App/Wds/orderdetail/id/" . $oid,
            'topcolor' => "#70c02f",
            'data' => array(
                //标题
                'first' => array(
                    'value' => urlencode(date('Y-m-d H:i:s', time()) . "\\n您的订单已完成付款"),
                    'color' => "#FF0000",
                ),
                //订单金额
                'orderProductPrice' => array(
                    'value' => urlencode($order['money'] . "元"),
                    'color' => "#006cff",
                ),
                //商品详情
                'orderProductName' => array(
                    'value' => urlencode($goodsinfo),
                    'color' => "#565656",
                ),
                //收货信息
                'orderAddress' => array(
                    'value' => urlencode($order['address'] . "  " . $order['name']),
                    //'color' => "#0000FF",
                ),
                //订单编号
                'orderName' => array(
                    'value' => urlencode($order['oid'] . "\\n验证码：" . $order['pin']),
                    //'color' => "#0000FF",
                ),
                //备注
                'remark' => array(
                    'value' => urlencode(""),
                    //'color' => "#006cff",
                ),
            )
        );
        //发送消息
        $options['appid'] = self::$_wxappid;
        $options['appsecret'] = self::$_wxappsecret;
        $mx = new \Util\Wx\Wechat($options);
        $rtn = $mx->sendTemplateMessage($template);
        file_put_contents('./logs/message/msg.txt', PHP_EOL . '发送会员模板消息成功:' . date('Y-m-d H:i:s') . $rtn, FILE_APPEND);

    }


}