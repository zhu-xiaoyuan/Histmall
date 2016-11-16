<?php
// 微信支付JSAPI版本
// 基于版本 V3
// By App 2015-1-20
namespace Home\Controller;

use Common\Model\EndPayModel;
use Think\Controller;
use Util\Wx\WxpayRefund;

class WxpayController extends Controller
{
    //App全局相关
    public static $_url; //动态刷新
    public static $_opt; //参数缓存
    public static $_logs = ''; //log地址
    //JOELCMS设置缓存
    protected static $SET;
    protected static $SHOP;

    //微信缓存
    protected static $_wx;
    protected static $_wxappid;
    protected static $_wxappsecret;

    public function __construct()
    {
        //App自定义全局
        parent::__construct();
        header("Content-type: text/html; charset=utf-8");
        //刷新全局地址
        self::$_url = "http://" . $_SERVER['HTTP_HOST'];
        //获取全局配置
        self::$SET = M('Set')->find();
        self::$SHOP = M('Shop_set')->find();

        if (!self::$SET) {
            die('系统未配置！');
        }
        //全局缓存微信
        self::$_wxappid = self::$SET['wxappid'];
        self::$_wxappsecret = self::$SET['wxappsecret'];
        $options['appid'] = self::$_wxappid;
        $options['appsecret'] = self::$_wxappsecret;
        self::$_wx = new \Util\Wx\Wechat($options);
    }

    //支付宝业务逻辑 By App.
    public function index()
    {

        echo "Hello World!";

    } //index类结束

    public function info()
    {

    }


    /**
     * 构造调用微信支付需要的订单信息
     * @param $orderCode
     * @param $orderType
     *
     * @return mixed ['body'=>'','detail'=>'','pay_price'=>0]
     */
    private function buildPayOrder($orderCode, $orderType)
    {
        $payOrderData = [];
        if ($orderType == 0) {
            $order = M('Shop_order')->where(array('oid' => $orderCode))->find();
            if (!$order) {
                $this->error('此订单不存在！', 'App/Shop/index');
            }
            if ($order['ispay']) {
                $this->error('此订单已支付！请勿重复支付！', 'App/Shop/index');
            }

            //解析商品
            $productItems = unserialize($order['items']);
            $payOrderData['pay_price'] = $order['payprice'];
            if (!empty($productItems)) {
                $payOrderData['body'] = $productItems[0]['name'];
                $payOrderData['detail'] = '';
            } else {
                $payOrderData['body'] = '购买商城产品';
                $payOrderData['detail'] = '';
            }

        } else {
            $order = M('Supplier_order')->where(array('order_code' => $orderCode))->find();
            if (!$order) {
                $this->error('此订单不存在！', 'App/Business/index');
            }
            if ($order['is_pay']) {
                $this->error('此订单已支付！请勿重复支付！', 'App/Business/index');
            }

            $payOrderData['pay_price'] = $order['pay_price'];

            $store = M('supplier_store')->where(['id' => $order['store_id']])->find();
            if ($store) {
                $payOrderData['body'] = $store['name'] . '[消费]';
                $payOrderData['detail'] = '';
            } else {
                $payOrderData['body'] = '商盟门店消费';
                $payOrderData['detail'] = '';
            }
        }

        //处理数据格式
        $wxBodyLengthLimit = 128;
        $body = $payOrderData['body'];
        if (strlen($body) > $wxBodyLengthLimit) {
            $payOrderData['body'] = substr($body, 0, $wxBodyLengthLimit);
        }
        $payOrderData['pay_price'] = intval($payOrderData['pay_price'] * 100);

        return $payOrderData;
    }
    //支付出口
    //App 2015.1.20
    //无返回值，接受订单参数并转向到微信支付接口
    public function pay()
    {
        //        $opt = I('get.');
        $orderType = intval($_GET["cate"]);//0:商城，1:商盟
        $is_group_buy = intval($_GET['is_group_buy']);  //是不是团购 0：不是团购
        self::$_opt['oid'] = $oid = $_GET['oid'];
        self::$_opt['openid'] = $openid = $_SESSION['wxpayopenid'];

        if (!$oid) {
            $this->diemsg(0, '订单参数不完整！请重新尝试！');
        }
        if (!$openid) {
            $this->diemsg(0, '未获取会员数据，请重新尝试！');
        }

        $order = $this->buildPayOrder($oid, $orderType);

        //微信支付封装
        $options['appid'] = self::$_wxappid;
        $options['appsecret'] = self::$_wxappsecret;
        $options['mchid'] = self::$SET['wxmchid'];
        $options['mchkey'] = self::$SET['wxmchkey'];

        $paysdk = new \Util\Wx\Wxpaysdk($options);

        $paysdk->setParameter("openid", $openid); //会员openid
        $paysdk->setParameter("body", $order['body']); //商品描述
        $paysdk->setParameter("detail", $order['detail']); //商品详情
//        $paysdk->setParameter('attach', json_encode(['order_type' => $orderType]));
        //自定义订单号，此处仅作举例
        $paysdk->setParameter("out_trade_no", $oid); //商户订单号
        $paysdk->setParameter("total_fee", $order['pay_price']); //总金额单位为分，不允许有小数
        $paysdk->setParameter("notify_url", 'http://' . $_SERVER['HTTP_HOST'] . __ROOT__ . '/Home/Wxpay/nd/'); //交易通知地址
        $paysdk->setParameter("trade_type", "JSAPI"); //交易类型
        $paysdk->setParameter('spbill_create_ip', get_client_ip());

        $prepayid = $paysdk->getPrepayId();

//        dump($oid);
//        dump($paysdk->result);

        if ($prepayid) {
            $paysdk->setPrepayId($prepayid);
        } else {
//            $this->diemsg(0, '未成功生成支付订单，请重新尝试！');
            if($is_group_buy){
                $backUrl = U('App/Shop/groupOrderList',array('sid'=>0,'type'=>5));
            }else{
                $backUrl = U('App/Shop/orderList',array('sid'=>0,'type'=>1));
            }
            $this->error('微信支付跳转失败',$backUrl);
        }

        //获取前端PAYAPI
        $wOpt['appId'] = self::$_wxappid;
        $timeStamp = time();
        $wOpt['timeStamp'] = "$timeStamp";
        $wOpt['nonceStr'] = $this->createNoncestr(8);
        $wOpt['package'] = 'prepay_id=' . $prepayid;
        //$wOpt['package'] = $prepayid;
        $wOpt['signType'] = 'MD5';
        ksort($wOpt, SORT_STRING);
        $string = "";
        foreach ($wOpt as $key => $v) {
            $string .= "{$key}={$v}&";
        }
        $string .= "key=" . self::$SET['wxmchkey'];
        $wOpt['paySign'] = strtoupper(md5($string));
        $wOpt['package'] = $prepayid;
        $str = "";
        foreach ($wOpt as $key => $v) {
            $str .= "{$key}={$v}&";
        }
        $url = "http://" . $_SERVER['HTTP_HOST'] . __ROOT__ . "/wxpay.php?$str&is_group_buy=$is_group_buy&oid=$oid";
        //$this->diemsg(0, $url);

//		$_SESSION['tmpwxpay']=$wOpt;
        header("Location:" . $url);
        //获取JSAPI
        //生成JSSDK实例
        //		$opt['appid']= self::$_wxappid;
        //		$opt['token']=self::$_wx->checkAuth();
        //		$opt['url']="http://$_SERVER[HTTP_HOST]$_SERVER[REQUEST_URI]";
        //		$jssdk=new \Util\Wx\Jssdk($opt);
        //		$jsapi=$jssdk->getSignPackage();
        //		if(!$jsapi){
        //			die('未正常获取数据！');
        //		}
        //		$this->assign('jsapi',$jsapi);
        //$this->display();
    }

    public function createNoncestr($length = 32)
    {
        $chars = "abcdefghijklmnopqrstuvwxyz0123456789";
        $str = "";
        for ($i = 0; $i < $length; $i++) {
            $str .= substr($chars, mt_rand(0, strlen($chars) - 1), 1);
        }
        return $str;
    }

    //停止不动的信息通知页面处理
    public function diemsg($status, $msg)
    {
        //成功为1，失败为0
        $status = $status ? $status : '0';
        $this->assign('status', $status);
        $this->assign('msg', $msg);
        $this->display('Base_diemsg');
        die();
    }

    //用户中断支付的跳转地址
    public function paycancel()
    {
        $url = self::$_url . '/App/Shop/orderList/sid/' . $_SESSION['wxpaysid'];
        header('Location:' . $url); //取消支付并跳转回商城
    }

    //当支付成功后的返回控制器
    public function payback()
    {
//        //$status=I('status');
//        $sta = '0';
//        $msg = '';
//        //dump($_GET);
//        $verify_result = $this->verifyReturn();
//        if ($verify_result) {
//            //验证成功
//            $out_trade_no = $_GET['out_trade_no']; //支付宝交易号
//            $trade_no = $_GET['trade_no']; //支付宝交易号
//            $result = $_GET['result']; //交易状态
//            if ($result == 'success') {
//                //判断该笔订单是否在商户网站中已经做过处理
//                //如果没有做过处理，根据订单号（out_trade_no）在商户网站的订单系统中查到该笔订单的详细，并执行商户的业务程序
//                //如果有做过处理，不执行商户的业务程序
//                $sta = '1';
//                $msg = '支付成功!';
//                //修改订单状态
//                $this->endpay($out_trade_no);
//                $url = self::$_url . '/App/Shop/orderList/sid/' . self::$_opt['sid'];
//                header('Location:' . $url);
//            } else {
//                echo "支付失败"; //这里永远不会调用
//                $url = self::$_url . '/App/Shop/orderList/sid/' . self::$_opt['sid'];
//                header('Location:' . $url);
//            }
//
//            //echo "验证成功<br />";
//
//            //——请根据您的业务逻辑来编写程序（以上代码仅作参考）——
//            //$this->info($sta,$msg,$uid);
//
//            //echo '支付状态：';
//            //dump($_GET);
//            /////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
//        } else {
//            //验证失败
//            //如要调试，请看alipay_notify.php页面的verifyReturn函数
//            //echo "验证失败";
//            die('验证失败');
//        }
        //$this->display();
    }

    //支付成功后后台接受方案
    public function nd()
    {
        $str = "";
        foreach ($_POST as $k => $v) {
            $str = $str . $k . "=>" . $v . '  ';
        }
        file_put_contents(self::$_logs . './Data/app_wxpaynd.txt', '响应参数:' . date('Y-m-d H:i:s') . PHP_EOL . '通知信息:' . $str . PHP_EOL . PHP_EOL . PHP_EOL, FILE_APPEND);
        //使用通用通知接口
        $notify = new \Util\Wx\Wxpayndsdk();

        //存储微信的回调
        $xml = $GLOBALS['HTTP_RAW_POST_DATA'];
        $notify->saveData($xml);
        //验证签名，并回应微信。
        //对后台通知交互时，如果微信收到商户的应答不是成功或超时，微信认为通知失败，
        //微信会通过一定的策略（如30分钟共8次）定期重新发起通知，
        //尽可能提高通知的成功率，但微信不保证通知最终能成功。
        if ($notify->checkSign() == FALSE) {
            $notify->setReturnParameter("return_code", "FAIL"); //返回状态码
            $notify->setReturnParameter("return_msg", "签名失败"); //返回信息
        } else {
            $notify->setReturnParameter("return_code", "SUCCESS"); //设置返回码
        }
        $returnXml = $notify->returnXml();
        echo $returnXml;

        //==商户根据实际情况设置相应的处理流程，此处仅作举例=======

        if ($notify->checkSign() == TRUE) {
            //获取订单号
            $out_trade_no = $notify->data["out_trade_no"];

            if ($notify->data["return_code"] == "FAIL") {
                //此处应该更新一下订单状态，商户自行增删操作
//                $log_->log_result($log_name,"【通信出错】:\n".$xml."\n");
                file_put_contents(self::$_logs . './Data/app_wxpayerr.txt', '通讯出错:' . date('Y-m-d H:i:s') . PHP_EOL . '通知信息:' . $str . PHP_EOL . '订单号:' . $out_trade_no . PHP_EOL . '交易结果:通讯出错' . PHP_EOL . PHP_EOL, FILE_APPEND);
            } elseif ($notify->data["result_code"] == "FAIL") {
                //此处应该更新一下订单状态，商户自行增删操作
                //$log_->log_result($log_name,"【业务出错】:\n".$xml."\n");
                file_put_contents(self::$_logs . './Data/app_wxpayerr.txt', '业务出错:' . date('Y-m-d H:i:s') . PHP_EOL . '通知信息:' . $str . PHP_EOL . '订单号:' . $out_trade_no . PHP_EOL . '交易结果:业务出错' . PHP_EOL . PHP_EOL, FILE_APPEND);
            } else {
                //此处应该更新一下订单状态，商户自行增删操作
                //$log_->log_result($log_name,"【支付成功】:\n".$xml."\n");
                $this->endpay($out_trade_no);
                file_put_contents(self::$_logs . './Data/app_wxpayok.txt', '支付成功:' . date('Y-m-d H:i:s') . PHP_EOL . '通知信息:' . $str . PHP_EOL . '订单号:' . $out_trade_no . PHP_EOL . '交易结果:交易成功' . PHP_EOL . PHP_EOL, FILE_APPEND);

            }

            //商户自行增加处理流程,
            //例如：更新订单状态
            //例如：数据库操作
            //例如：推送支付完成信息
        }
    }

    //支付成功后后台接受方案
    public function nderr()
    {
        $str = "";
        foreach ($_POST as $k => $v) {
            $str = $str . $k . "=>" . $v . '  ';
        }
        file_put_contents(self::$_logs . './Data/app_wxpay_nderr.txt', '响应参数:' . date('Y-m-d H:i:s') . PHP_EOL . '通知信息:' . $str . PHP_EOL . PHP_EOL . PHP_EOL, FILE_APPEND);

    }

    //付款成功后操作
    public function endpay($oid)
    {
        $endpay = D('EndPay');
        $rlt = $endpay->endpay($oid, PT_WXPAY);
        if ($rlt['status']) {
            $this->diemsg(0, $rlt['msg']);
        }

        //发送已付款订单模板消息给商家
        //$this -> sendMobanMsmToShop($order['id'],1);
        //发送支付成功莫办消息给会员
        //$this -> sendTemplateToVip($order['id']);
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

    /**
     * 退款操作
     *      需要参数
     *      out_trade_no = out_refund_no = ...
     *      total_fee   = ...
     *      refund_fee  = ...
     */
    Public function refund($data = array()){
        $params = [
            'appid'         => self::$_wxappid,
            'mch_id'        => self::$SET['wxmchid'],
            'nonce_str'      => $this->createNoncestr(),
            'out_trade_no'  => $data['out_trade_no'],
            'out_refund_no' => $data['out_trade_no'],   //退款id等于订单id，暂时没有单独的退款单号
            'total_fee'     => $data['total_fee'],
            'refund_fee'    => $data['refund_fee'],
            'op_user_id'    => self::$SET['wxmchid']
        ];

        $refund = new \Util\Wx\WxpayRefund();
        $refund->mchkey = self::$SET['wxmchkey'];

        $response = $refund->refund($params);
        if($response['status'] == 1){
            $rlt = M('shop_order')->where(['oid'=>$params['out_trade_no']])->save(['status'=>8]);    //status=>8 : 团购已退款
            if($rlt){
                return ['status'=>1,'msg'=>'退款成功'];
            }else{
                return ['status'=>0,'msg'=>'退款成功，但订单状态未改变成功，请手动改变'];
            }
            file_put_contents(self::$_logs . './Data/app_wxrefundok.txt', '退款成功:' . date('Y-m-d H:i:s') . PHP_EOL . '通知信息:' . $response['xml'] . PHP_EOL . '订单号:' . $params['out_trade_no'] . PHP_EOL . '交易结果:退款成功' . PHP_EOL . PHP_EOL, FILE_APPEND);
        }else{
            file_put_contents(self::$_logs . './Data/app_wxrefundfaile.txt', '退款失败:' . date('Y-m-d H:i:s') . PHP_EOL . '通知信息:' .$response['xml'] . PHP_EOL . '订单号:' . $params['out_trade_no'] . PHP_EOL . '交易结果:退款失败' . PHP_EOL . PHP_EOL, FILE_APPEND);
            return ['status'=>0,'msg'=>$response['msg']];
        }

    }

} //Wxpay类结束