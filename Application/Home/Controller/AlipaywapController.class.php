<?php
// 支付宝（及时收款接口）改写 
// 基于版本 V4.3
// By App 2014-6-10
namespace Home\Controller;

use Think\Controller;

class AlipaywapController extends Controller
{

    // 缓存全局商城配置对象
    public static $_shop;
    //App全局相关
    public static $_url;//动态刷新
    public static $_logs = './logs/alipaywap/';//log地址
    public static $_opt;//参数缓存
    //支付宝全局相关
    var $alipay_config;//支付宝基本配置
    var $alipay_gateway_new = 'http://wappaygw.alipay.com/service/rest.htm?';//支付宝Wap网关
    var $https_verify_url = 'https://mapi.alipay.com/gateway.do?service=notify_verify&';//HTTPS形式消息验证地址
    var $http_verify_url = 'http://notify.alipay.com/trade/notify_query.do?';//HTTP形式消息验证地址

    public function __construct($options)
    {
        //App自定义全局
        parent::__construct();
        header("Content-type: text/html; charset=utf-8");
        //App处理多用户代码

        //App配置支付宝基本参数
        //合作身份者id，以2088开头的16位纯数字
        $this->alipay_config['partner'] = '2088811011954002';
        //安全检验码，以数字和字母组成的32位字符
        $this->alipay_config['key'] = '608wlylmfx9q08z734gwtix6ntfmbw5a';
        //商户的私钥（后缀是.pen）文件相对路径
        //如果签名方式设置为“0001”时，请设置该参数
        $this->alipay_config['private_key_path'] = 'public/alipaywap/key/rsa_private_key.pem';
        //支付宝公钥（后缀是.pen）文件相对路径
        //如果签名方式设置为“0001”时，请设置该参数
        $this->alipay_config['ali_public_key_path'] = 'public/alipaywap/key/alipay_public_key.pem';

        //签名方式 不需修改
        //$this->alipay_config['sign_type']    = '0001';
        $this->alipay_config['sign_type'] = 'MD5';//App修改为MD5验证模式
        //字符编码格式 目前支持 gbk 或 utf-8
        $this->alipay_config['input_charset'] = strtolower('utf-8');
        //ca证书路径地址，用于curl中ssl校验
        //请保证cacert.pem文件在当前文件夹目录中
        $this->alipay_config['cacert'] = getcwd() . '\\alipaywapcacert.pem';

        //访问模式,根据自己的服务器是否支持ssl访问，若支持请选择https；若不支持请选择http
        $this->alipay_config['transport'] = 'http';

        //刷新全局地址
        self::$_url = "http://" . $_SERVER['HTTP_HOST'];

        // 刷新商城配置
        self::$_shop = M('Shop_set')->find();
    }

    //支付宝业务逻辑.
    public function index()
    {

        echo "haha";

    }//index类结束

    public function info()
    {

    }
    //支付出口
    //App 2014.6.9
    //无返回值，接受订单参数并转向到支付宝支付接口
    public function pay()
    {
        $opt = I('get.');

        self::$_opt['oid'] = $oid = $_GET['oid'];
        self::$_opt['price'] = $price = $_GET['price'];
        self::$_opt['sid'] = $sid = $_GET['sid'];

        if ($oid == '' || $price == '' || $sid == '') {
            $msg = '订单参数不完整！';
            die($msg);
        }
        $_SESSION['alipaysid'] = $sid;//将支付宝跳转SID缓存到跳转
        /************************************************************/
        /**************************调用授权接口alipay.wap.trade.create.direct获取授权码token**************************/

        //返回格式
        $format = "xml";//必填，不需要修改//返回格式
        $v = "2.0";//必填，不需要修改
        $req_id = date('Ymdhis');//请求号//必填，须保证每次请求都是唯一
        //**req_data详细信息**
        //服务器异步通知页面路径
        $notify_url = self::$_url . '/Home/Alipaywap/nd/';//需http://格式的完整路径，不允许加?id=123这类自定义参数
        //页面跳转同步通知页面路径
        $call_back_url = self::$_url . "/Home/Alipaywap/payback/";//需http://格式的完整路径，不允许加?id=123这类自定义参数
        //操作中断返回地址
        $merchant_url = self::$_url . '/Home/Alipaywap/paycancel/';
        //用户付款中途退出返回商户的地址。需http://格式的完整路径，不允许加?id=123这类自定义参数
        //卖家支付宝帐户
        $seller_email = '2368830920@qq.com';//$_POST['WIDseller_email'];

        //必填
        //商户订单号
        $out_trade_no = $oid;//$_POST['WIDout_trade_no'];
        //商户网站订单系统中唯一订单号，必填
        //订单名称
        $subject = $oid;//$_POST['WIDsubject'];
        //必填
        //付款金额
        $total_fee = $price;//$_POST['WIDtotal_fee'];
        //必填
        //请求业务参数详细
        $req_data = '<direct_trade_create_req><notify_url>' . $notify_url . '</notify_url><call_back_url>' . $call_back_url . '</call_back_url><seller_account_name>' . $seller_email . '</seller_account_name><out_trade_no>' . $out_trade_no . '</out_trade_no><subject>' . $subject . '</subject><total_fee>' . $total_fee . '</total_fee><merchant_url>' . $merchant_url . '</merchant_url></direct_trade_create_req>';
        //必填

        /************************************************************/
        //dump($this->alipay_config);
        //构造要请求的参数数组，无需改动
        $para_token = array(
            "service" => "alipay.wap.trade.create.direct",
            "partner" => trim($this->alipay_config['partner']),
            "sec_id" => trim($this->alipay_config['sign_type']),
            "format" => $format,
            "v" => $v,
            "req_id" => $req_id,
            "req_data" => $req_data,
            "_input_charset" => trim(strtolower($this->alipay_config['input_charset']))
        );

        //建立请求
        $html_text = $this->buildRequestHttp($para_token);
        //dump($html_text);
        //die();
        //URLDECODE返回的信息
        $html_text = urldecode($html_text);

        //解析远程模拟提交后返回的信息
        $para_html_text = $this->parseResponse($html_text);
        //dump($para_html_text);
        //die();
        //获取request_token
        $request_token = $para_html_text['request_token'];


        /**************************根据授权码token调用交易接口alipay.wap.auth.authAndExecute**************************/

        //业务详细
        $req_data = '<auth_and_execute_req><request_token>' . $request_token . '</request_token></auth_and_execute_req>';
        //必填

        //构造要请求的参数数组，无需改动
        $parameter = array(
            "service" => "alipay.wap.auth.authAndExecute",
            "partner" => trim($this->alipay_config['partner']),
            "sec_id" => trim($this->alipay_config['sign_type']),
            "format" => $format,
            "v" => $v,
            "req_id" => $req_id,
            "req_data" => $req_data,
            "_input_charset" => trim(strtolower($this->alipay_config['input_charset']))
        );
        echo '支付宝正在努力为您生成支付订单!请稍候!...<br>';
        //建立请求
        $html_text = $this->buildRequestForm($parameter, 'get', '确认');
        //dump($html_text);
        //die();
        echo $html_text;

    }

    //用户中断支付的跳转地址
    public function paycancel()
    {
        //$url=self::$_url.'/App/Shop/orderList/sid/'.$_SESSION['alipaysid'];
        $url = self::$_url . '/App/Shop/orderList/sid/0';
        header('Location:' . $url);//取消支付并跳转回商城
    }

    //当支付成功后的返回控制器
    public function payback()
    {
        //$status=I('status');
        $sta = '0';
        $msg = '';
        //dump($_GET);
        $verify_result = $this->verifyReturn();
        if ($verify_result) {
            //验证成功
            $out_trade_no = $_GET['out_trade_no'];//支付宝交易号
            $trade_no = $_GET['trade_no'];//支付宝交易号
            $result = $_GET['result'];//交易状态
            if ($result == 'success') {
                //判断该笔订单是否在商户网站中已经做过处理
                //如果没有做过处理，根据订单号（out_trade_no）在商户网站的订单系统中查到该笔订单的详细，并执行商户的业务程序
                //如果有做过处理，不执行商户的业务程序
                $sta = '1';
                $msg = '支付成功!';
                //修改订单状态
                $this->endpay($out_trade_no);
                //$url=self::$_url.'/App/Shop/orderList/sid/'.self::$_opt['sid'];
                $url = self::$_url . '/App/Shop/orderList/sid/0';
                header('Location:' . $url);
            } else {
                echo "支付失败";//这里永远不会调用
                //$url=self::$_url.'/App/Shop/orderList/sid/'.self::$_opt['sid'];
                $url = self::$_url . '/App/Shop/orderList/sid/0';
                header('Location:' . $url);
            }

            //echo "验证成功<br />";

            //——请根据您的业务逻辑来编写程序（以上代码仅作参考）——
            //$this->info($sta,$msg,$uid);

            //echo '支付状态：';
            //dump($_GET);
            /////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
        } else {
            //验证失败
            //如要调试，请看alipay_notify.php页面的verifyReturn函数
            //echo "验证失败";
            die('验证失败');
        }
        //$this->display();
    }

    //支付成功后后台接受方案
    public function nd()
    {
        foreach ($_POST as $k => $v) {
            $str = $str . $k . "=>" . $v . '  ';
        }
        //file_put_contents(self::$_logs.'App_nd.txt','响应参数:'.date('Y-m-d H:i:s').PHP_EOL.'通知信息:'.$str.PHP_EOL.PHP_EOL.PHP_EOL,FILE_APPEND);
        $verify_result = $this->verifyNotify();
        if ($verify_result) {
            //验证成功解析$notify_data
            $doc = new \DOMDocument();
            $doc->loadXML($_POST['notify_data']);
            //商户订单号
            $out_trade_no = $doc->getElementsByTagName("out_trade_no")->item(0)->nodeValue;
            //支付宝交易号
            $trade_no = $doc->getElementsByTagName("trade_no")->item(0)->nodeValue;
            //交易状态
            $trade_status = $doc->getElementsByTagName("trade_status")->item(0)->nodeValue;
            //买家账号
            $buyer_email = $doc->getElementsByTagName("buyer_email")->item(0)->nodeValue;
            //测试模式下打印响应信息
            file_put_contents(self::$_logs . 'App_nd.txt', '签名验证成功:' . date('Y-m-d H:i:s') . $trade_status . PHP_EOL . '通知信息:' . $str . PHP_EOL . PHP_EOL . PHP_EOL, FILE_APPEND);

            if ($trade_status == 'TRADE_FINISHED') {
                //判断该笔订单是否在商户网站中已经做过处理
                //如果没有做过处理，根据订单号（out_trade_no）在商户网站的订单系统中查到该笔订单的详细，并执行商户的业务程序
                //如果有做过处理，不执行商户的业务程序

                //注意：
                //该种交易状态只在两种情况下出现
                //1、开通了普通即时到账，买家付款成功后。
                //2、开通了高级即时到账，从该笔交易成功时间算起，过了签约时的可退款时限（如：三个月以内可退款、一年以内可退款等）后。

                //调试用，写文本函数记录程序运行情况是否正常
                //logResult("这里写入想要调试的代码变量值，或其他运行的结果记录");

                // 交易处理成功--此处可以对交易做分润等处理

                $this->endpay($out_trade_no, $buyer_email);
                file_put_contents(self::$_logs . 'App_nd.txt', '支付成功:' . date('Y-m-d H:i:s') . PHP_EOL . '通知信息:' . $str . PHP_EOL . '订单状态:' . $order . PHP_EOL . '交易类型:TRADE_FINISHED' . PHP_EOL . PHP_EOL, FILE_APPEND);

            } else if ($trade_status == 'TRADE_SUCCESS') {
                //判断该笔订单是否在商户网站中已经做过处理
                //如果没有做过处理，根据订单号（out_trade_no）在商户网站的订单系统中查到该笔订单的详细，并执行商户的业务程序
                //如果有做过处理，不执行商户的业务程序

                //注意：
                //该种交易状态只在一种情况下出现——开通了高级即时到账，买家付款成功后。

                //调试用，写文本函数记录程序运行情况是否正常
                //logResult("这里写入想要调试的代码变量值，或其他运行的结果记录");
                // 交易处理成功--此处可以对交易做分润等处理
                $this->endpay($out_trade_no, $buyer_email);
                file_put_contents(self::$_logs . 'App_nd.txt', '支付成功:' . date('Y-m-d H:i:s') . PHP_EOL . '通知信息:' . $str . PHP_EOL . '订单状态:' . $order . PHP_EOL . '交易类型:TRADE_SUCCESS' . PHP_EOL . PHP_EOL, FILE_APPEND);
            }

            //——请根据您的业务逻辑来编写程序（以上代码仅作参考）——

            echo "success";        //请不要修改或删除

            /////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
        } else {
            //验证失败
            echo "fail";
            file_put_contents(self::$_logs . 'App_nd.txt', '签名验证失败:' . date('Y-m-d H:i:s') . PHP_EOL . '通知信息:' . $str . PHP_EOL . PHP_EOL . PHP_EOL, FILE_APPEND);
            //调试用，写文本函数记录程序运行情况是否正常
            //logResult("这里写入想要调试的代码变量值，或其他运行的结果记录");
        }
    }

    //在线充值
    //无返回值，接受订单参数并转向到支付宝支付接口
    public function cz()
    {
        $oid = I('oid');
        $price = I('price');
        if ($oid == '' || $price == '') {
            $msg = '订单参数不完整！';
            die($msg);
        }
        /************************************************************/
        /**************************调用授权接口alipay.wap.trade.create.direct获取授权码token**************************/

        //返回格式
        $format = "xml";//必填，不需要修改//返回格式
        $v = "2.0";//必填，不需要修改
        $req_id = date('Ymdhis');//请求号//必填，须保证每次请求都是唯一
        //**req_data详细信息**
        //服务器异步通知页面路径
        $notify_url = self::$_url . '/Home/Alipaywap/cznd/';//需http://格式的完整路径，不允许加?id=123这类自定义参数
        //页面跳转同步通知页面路径
        $call_back_url = self::$_url . "/Home/Alipaywap/czback/";//需http://格式的完整路径，不允许加?id=123这类自定义参数
        //操作中断返回地址
        $merchant_url = self::$_url . '/Home/Alipaywap/czcancel/';
        //用户付款中途退出返回商户的地址。需http://格式的完整路径，不允许加?id=123这类自定义参数
        //卖家支付宝帐户
        $seller_email = 'jszwwhcm@qq.com';//$_POST['WIDseller_email'];

        //必填
        //商户订单号
        $out_trade_no = $oid;//$_POST['WIDout_trade_no'];
        //商户网站订单系统中唯一订单号，必填
        //订单名称
        $subject = $oid;//$_POST['WIDsubject'];
        //必填
        //付款金额
        $total_fee = $price;//$_POST['WIDtotal_fee'];
        //必填
        //请求业务参数详细
        $req_data = '<direct_trade_create_req><notify_url>' . $notify_url . '</notify_url><call_back_url>' . $call_back_url . '</call_back_url><seller_account_name>' . $seller_email . '</seller_account_name><out_trade_no>' . $out_trade_no . '</out_trade_no><subject>' . $subject . '</subject><total_fee>' . $total_fee . '</total_fee><merchant_url>' . $merchant_url . '</merchant_url></direct_trade_create_req>';
        //必填

        /************************************************************/
        //dump($this->alipay_config);
        //构造要请求的参数数组，无需改动
        $para_token = array(
            "service" => "alipay.wap.trade.create.direct",
            "partner" => trim($this->alipay_config['partner']),
            "sec_id" => trim($this->alipay_config['sign_type']),
            "format" => $format,
            "v" => $v,
            "req_id" => $req_id,
            "req_data" => $req_data,
            "_input_charset" => trim(strtolower($this->alipay_config['input_charset']))
        );

        //建立请求
        $html_text = $this->buildRequestHttp($para_token);
        //dump($html_text);
        //die();
        //URLDECODE返回的信息
        $html_text = urldecode($html_text);

        //解析远程模拟提交后返回的信息
        $para_html_text = $this->parseResponse($html_text);
        //dump($para_html_text);
        //die();
        //获取request_token
        $request_token = $para_html_text['request_token'];


        /**************************根据授权码token调用交易接口alipay.wap.auth.authAndExecute**************************/

        //业务详细
        $req_data = '<auth_and_execute_req><request_token>' . $request_token . '</request_token></auth_and_execute_req>';
        //必填

        //构造要请求的参数数组，无需改动
        $parameter = array(
            "service" => "alipay.wap.auth.authAndExecute",
            "partner" => trim($this->alipay_config['partner']),
            "sec_id" => trim($this->alipay_config['sign_type']),
            "format" => $format,
            "v" => $v,
            "req_id" => $req_id,
            "req_data" => $req_data,
            "_input_charset" => trim(strtolower($this->alipay_config['input_charset']))
        );
        echo '支付宝正在努力为您生成支付订单!请稍候!...<br>';
        //建立请求
        $html_text = $this->buildRequestForm($parameter, 'get', '确认');
        //dump($html_text);
        //die();
        echo $html_text;

    }

    //用户中断充值的跳转地址
    public function czcancel()
    {
        $url = self::$_url . '/App/vip/cz';
        header('Location:' . $url);//取消支付并跳转回商城
    }

    //当支付成功后的返回控制器
    public function czback()
    {
        //$status=I('status');
        $sta = '0';
        $msg = '';
        //dump($_GET);
        $verify_result = $this->verifyReturn();
        if ($verify_result) {
            //验证成功
            $out_trade_no = $_GET['out_trade_no'];//支付宝交易号
            $trade_no = $_GET['trade_no'];//支付宝交易号
            $result = $_GET['result'];//交易状态
            if ($result == 'success') {
                //判断该笔订单是否在商户网站中已经做过处理
                //如果没有做过处理，根据订单号（out_trade_no）在商户网站的订单系统中查到该笔订单的详细，并执行商户的业务程序
                //如果有做过处理，不执行商户的业务程序
                $sta = '1';
                $msg = '支付成功!';
                //修改订单状态
                $this->endcz($out_trade_no);
                $url = self::$_url . '/App/vip';
                header('Location:' . $url);
            } else {
                echo "支付失败";//这里永远不会调用
                $url = self::$_url . '/App/vip';
                header('Location:' . $url);
            }

            //echo "验证成功<br />";

            //——请根据您的业务逻辑来编写程序（以上代码仅作参考）——
            //$this->info($sta,$msg,$uid);

            //echo '支付状态：';
            //dump($_GET);
            /////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
        } else {
            //验证失败
            //如要调试，请看alipay_notify.php页面的verifyReturn函数
            //echo "验证失败";
            die('验证失败');
        }
        //$this->display();
    }

    //支付成功后后台接受方案
    public function cznd()
    {
        foreach ($_POST as $k => $v) {
            $str = $str . $k . "=>" . $v . '  ';
        }
        //file_put_contents(self::$_logs.'App_nd.txt','响应参数:'.date('Y-m-d H:i:s').PHP_EOL.'通知信息:'.$str.PHP_EOL.PHP_EOL.PHP_EOL,FILE_APPEND);
        $verify_result = $this->verifyNotify();
        if ($verify_result) {
            //验证成功解析$notify_data
            $doc = new \DOMDocument();
            $doc->loadXML($_POST['notify_data']);
            //商户订单号
            $out_trade_no = $doc->getElementsByTagName("out_trade_no")->item(0)->nodeValue;
            //支付宝交易号
            $trade_no = $doc->getElementsByTagName("trade_no")->item(0)->nodeValue;
            //交易状态
            $trade_status = $doc->getElementsByTagName("trade_status")->item(0)->nodeValue;
            //买家账号
            $buyer_email = $doc->getElementsByTagName("buyer_email")->item(0)->nodeValue;
            //测试模式下打印响应信息
            file_put_contents(self::$_logs . 'App_nd.txt', '签名验证成功:' . date('Y-m-d H:i:s') . $trade_status . PHP_EOL . '通知信息:' . $str . PHP_EOL . PHP_EOL . PHP_EOL, FILE_APPEND);

            if ($trade_status == 'TRADE_FINISHED') {
                //判断该笔订单是否在商户网站中已经做过处理
                //如果没有做过处理，根据订单号（out_trade_no）在商户网站的订单系统中查到该笔订单的详细，并执行商户的业务程序
                //如果有做过处理，不执行商户的业务程序

                //注意：
                //该种交易状态只在两种情况下出现
                //1、开通了普通即时到账，买家付款成功后。
                //2、开通了高级即时到账，从该笔交易成功时间算起，过了签约时的可退款时限（如：三个月以内可退款、一年以内可退款等）后。

                //调试用，写文本函数记录程序运行情况是否正常
                //logResult("这里写入想要调试的代码变量值，或其他运行的结果记录");

                // 交易处理成功--此处可以对交易做分润等处理

                $this->endcz($out_trade_no);
                file_put_contents(self::$_logs . 'App_nd.txt', '会员充值成功:' . date('Y-m-d H:i:s') . PHP_EOL . '通知信息:' . $str . PHP_EOL . '订单状态:' . $order . PHP_EOL . '交易类型:TRADE_FINISHED' . PHP_EOL . PHP_EOL, FILE_APPEND);

            } else if ($trade_status == 'TRADE_SUCCESS') {
                //判断该笔订单是否在商户网站中已经做过处理
                //如果没有做过处理，根据订单号（out_trade_no）在商户网站的订单系统中查到该笔订单的详细，并执行商户的业务程序
                //如果有做过处理，不执行商户的业务程序

                //注意：
                //该种交易状态只在一种情况下出现——开通了高级即时到账，买家付款成功后。

                //调试用，写文本函数记录程序运行情况是否正常
                //logResult("这里写入想要调试的代码变量值，或其他运行的结果记录");
                // 交易处理成功--此处可以对交易做分润等处理
                $this->endcz($out_trade_no);
                file_put_contents(self::$_logs . 'App_nd.txt', '会员充值成功:' . date('Y-m-d H:i:s') . PHP_EOL . '通知信息:' . $str . PHP_EOL . '订单状态:' . $order . PHP_EOL . '交易类型:TRADE_SUCCESS' . PHP_EOL . PHP_EOL, FILE_APPEND);
            }

            //——请根据您的业务逻辑来编写程序（以上代码仅作参考）——

            echo "success";        //请不要修改或删除

            /////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
        } else {
            //验证失败
            echo "fail";
            file_put_contents(self::$_logs . 'App_nd.txt', '签名验证失败:' . date('Y-m-d H:i:s') . PHP_EOL . '通知信息:' . $str . PHP_EOL . PHP_EOL . PHP_EOL, FILE_APPEND);
            //调试用，写文本函数记录程序运行情况是否正常
            //logResult("这里写入想要调试的代码变量值，或其他运行的结果记录");
        }
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
                //$this -> sendMobanMsmToVip($order['id']);
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
            $level;
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
            $options['appid'] = "wxbe940343e78068cf";
            $options['appsecret'] = "c0fd637146298928406365ec3a08ce27";
            $mx = new \Zw\Wx\Wechat($options);

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
            'url' => "http://www.zwwsq.com/wap/wds/orderdetail/id/" . $oid,
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
        $options['appid'] = "wxbe940343e78068cf";
        $options['appsecret'] = "c0fd637146298928406365ec3a08ce27";
        $mx = new \Zw\Wx\Wechat($options);
        $rtn = $mx->sendMobanMessage($template);
        file_put_contents('./logs/message/msg.txt', PHP_EOL . '发送会员模板消息成功:' . date('Y-m-d H:i:s') . $rtn, FILE_APPEND);

    }

    //支付宝业务逻辑结束
    /////////////////////////////////////////////////////////
    //支付宝RSA加解密请求类 by App
    //基于Alipay_rsa
    /**
     * RSA签名
     * @param $data 待签名数据
     * @param $private_key_path 商户私钥文件路径
     * return 签名结果
     */
    function rsaSign($data, $private_key_path)
    {
        //$private_key_path="public/alipaywap/key/rsa_private_key.pem";
        //$priKey = file_get_contents($private_key_path);
        //$priKey = file_get_contents($_SERVER['DOCUMENT_ROOT'] . '/public/alipaywap/key/rsa_private_key.pem');
        //dump($priKey);
        $res = openssl_get_privatekey($private_key_path);
        //dump($res);
        openssl_sign($data, $sign, $res);
        openssl_free_key($res);
        //base64编码
        $sign = base64_encode($sign);
        //dump($sign);
        //die();
        return $sign;
    }

    /**
     * RSA验签
     * @param $data 待签名数据
     * @param $ali_public_key_path 支付宝的公钥文件路径
     * @param $sign 要校对的的签名结果
     * return 验证结果
     */
    function rsaVerify($data, $ali_public_key_path, $sign)
    {
        $pubKey = file_get_contents($ali_public_key_path);
        $res = openssl_get_publickey($pubKey);
        $result = (bool)openssl_verify($data, base64_decode($sign), $res);
        openssl_free_key($res);
        return $result;
    }

    /**
     * RSA解密
     * @param $content 需要解密的内容，密文
     * @param $private_key_path 商户私钥文件路径
     * return 解密后内容，明文
     */
    function rsaDecrypt($content, $private_key_path)
    {
        $priKey = file_get_contents($private_key_path);
        $res = openssl_get_privatekey($priKey);
        //用base64将内容还原成二进制
        $content = base64_decode($content);
        //把需要解密的内容，按128位拆开解密
        $result = '';
        for ($i = 0; $i < strlen($content) / 128; $i++) {
            $data = substr($content, $i * 128, 128);
            openssl_private_decrypt($data, $decrypt, $res);
            $result .= $decrypt;
        }
        openssl_free_key($res);
        return $result;
    }

    /////////////////////////////////////////////////////////
    //支付宝接口提交请求类 by App
    //基于Alipay_submit
    /**
     * 生成签名结果
     * @param $para_sort 已排序要签名的数组
     * return 签名结果字符串
     */
    function buildRequestMysign($para_sort)
    {
        //把数组所有元素，按照“参数=参数值”的模式用“&”字符拼接成字符串
        $prestr = $this->createLinkstring($para_sort);

        $mysign = "";
        switch (strtoupper(trim($this->alipay_config['sign_type']))) {
            case "MD5" :
                $mysign = $this->md5Sign($prestr, $this->alipay_config['key']);
                break;
            case "RSA" :
                $mysign = $this->rsaSign($prestr, $this->alipay_config['private_key_path']);
                break;
            case "0001" :
                $mysign = $this->rsaSign($prestr, $this->alipay_config['private_key_path']);
                break;
            default :
                $mysign = "";
        }

        return $mysign;
    }

    /**
     * 生成要请求给支付宝的参数数组
     * @param $para_temp 请求前的参数数组
     * @return 要请求的参数数组
     */
    function buildRequestPara($para_temp)
    {
        //除去待签名参数数组中的空值和签名参数
        $para_filter = $this->paraFilter($para_temp);

        //对待签名参数数组排序
        $para_sort = $this->argSort($para_filter);

        //生成签名结果
        $mysign = $this->buildRequestMysign($para_sort);

        //签名结果与签名方式加入请求提交参数组中
        $para_sort['sign'] = $mysign;
        if ($para_sort['service'] != 'alipay.wap.trade.create.direct' && $para_sort['service'] != 'alipay.wap.auth.authAndExecute') {
            $para_sort['sign_type'] = strtoupper(trim($this->alipay_config['sign_type']));
        }

        return $para_sort;
    }

    /**
     * 生成要请求给支付宝的参数数组
     * @param $para_temp 请求前的参数数组
     * @return 要请求的参数数组字符串
     */
    function buildRequestParaToString($para_temp)
    {
        //待请求参数数组
        $para = $this->buildRequestPara($para_temp);

        //把参数组中所有元素，按照“参数=参数值”的模式用“&”字符拼接成字符串，并对字符串做urlencode编码
        $request_data = $this->createLinkstringUrlencode($para);

        return $request_data;
    }

    /**
     * 建立请求，以表单HTML形式构造（默认）
     * @param $para_temp 请求参数数组
     * @param $method 提交方式。两个值可选：post、get
     * @param $button_name 确认按钮显示文字
     * @return 提交表单HTML文本
     */
    function buildRequestForm($para_temp, $method, $button_name)
    {
        //待请求参数数组
        $para = $this->buildRequestPara($para_temp);

        $sHtml = "<form id='alipaysubmit' name='alipaysubmit' action='" . $this->alipay_gateway_new . "_input_charset=" . trim(strtolower($this->alipay_config['input_charset'])) . "' method='" . $method . "'>";
        while (list ($key, $val) = each($para)) {
            $sHtml .= "<input type='hidden' name='" . $key . "' value='" . $val . "'/>";
        }

        //submit按钮控件请不要含有name属性
        $sHtml = $sHtml . "<input type='submit' value='" . $button_name . "'></form>";

        $sHtml = $sHtml . "<script>document.forms['alipaysubmit'].submit();</script>";

        return $sHtml;
    }

    /**
     * 建立请求，以模拟远程HTTP的POST请求方式构造并获取支付宝的处理结果
     * @param $para_temp 请求参数数组
     * @return 支付宝处理结果
     */
    function buildRequestHttp($para_temp)
    {
        $sResult = '';
        //dump($para_temp);
        //die();
        //待请求参数数组字符串
        $request_data = $this->buildRequestPara($para_temp);
        //dump($request_data);

        //远程获取数据
        $sResult = $this->getHttpResponsePOST($this->alipay_gateway_new, $this->alipay_config['cacert'], $request_data, trim(strtolower($this->alipay_config['input_charset'])));
        //die();
        return $sResult;
    }

    /**
     * 建立请求，以模拟远程HTTP的POST请求方式构造并获取支付宝的处理结果，带文件上传功能
     * @param $para_temp 请求参数数组
     * @param $file_para_name 文件类型的参数名
     * @param $file_name 文件完整绝对路径
     * @return 支付宝返回处理结果
     */
    function buildRequestHttpInFile($para_temp, $file_para_name, $file_name)
    {

        //待请求参数数组
        $para = $this->buildRequestPara($para_temp);
        $para[$file_para_name] = "@" . $file_name;

        //远程获取数据
        $sResult = $this->getHttpResponsePOST($this->alipay_gateway_new, $this->alipay_config['cacert'], $para, trim(strtolower($this->alipay_config['input_charset'])));

        return $sResult;
    }

    /**
     * 解析远程模拟提交后返回的信息
     * @param $str_text 要解析的字符串
     * @return 解析结果
     */
    function parseResponse($str_text)
    {
        //以“&”字符切割字符串
        $para_split = explode('&', $str_text);
        //把切割后的字符串数组变成变量与数值组合的数组
        foreach ($para_split as $item) {
            //获得第一个=字符的位置
            $nPos = strpos($item, '=');
            //获得字符串长度
            $nLen = strlen($item);
            //获得变量名
            $key = substr($item, 0, $nPos);
            //获得数值
            $value = substr($item, $nPos + 1, $nLen - $nPos - 1);
            //放入数组中
            $para_text[$key] = $value;
        }

        if (!empty ($para_text['res_data'])) {
            //解析加密部分字符串
            if ($this->alipay_config['sign_type'] == '0001') {
                $para_text['res_data'] = $this->rsaDecrypt($para_text['res_data'], $this->alipay_config['private_key_path']);
            }

            //token从res_data中解析出来（也就是说res_data中已经包含token的内容）
            $doc = new \DOMDocument();
            $doc->loadXML($para_text['res_data']);
            $para_text['request_token'] = $doc->getElementsByTagName("request_token")->item(0)->nodeValue;
        }

        return $para_text;
    }

    /**
     * 用于防钓鱼，调用接口query_timestamp来获取时间戳的处理函数
     * 注意：该功能PHP5环境及以上支持，因此必须服务器、本地电脑中装有支持DOMDocument、SSL的PHP配置环境。建议本地调试时使用PHP开发软件
     * return 时间戳字符串
     */
    function query_timestamp()
    {
        $url = $this->alipay_gateway_new . "service=query_timestamp&partner=" . trim(strtolower($this->alipay_config['partner'])) . "&_input_charset=" . trim(strtolower($this->alipay_config['input_charset']));
        $encrypt_key = "";

        $doc = new \DOMDocument();
        $doc->load($url);
        $itemEncrypt_key = $doc->getElementsByTagName("encrypt_key");
        $encrypt_key = $itemEncrypt_key->item(0)->nodeValue;

        return $encrypt_key;
    }

    /////////////////////////////////////////////////////////
    //支付宝通知接口方法 by App
    //基于Alipay_nodify
    /**
     * 针对notify_url验证消息是否是支付宝发出的合法消息
     * @return 验证结果
     */
    function verifyNotify()
    {
        if (empty($_POST)) {//判断POST来的数组是否为空
            return false;
        } else {

            //对notify_data解密
            $decrypt_post_para = $_POST;
            if ($this->alipay_config['sign_type'] == '0001') {
                $decrypt_post_para['notify_data'] = rsaDecrypt($decrypt_post_para['notify_data'], $this->alipay_config['private_key_path']);
            }

            //notify_id从decrypt_post_para中解析出来（也就是说decrypt_post_para中已经包含notify_id的内容）
            $doc = new \DOMDocument();
            $doc->loadXML($decrypt_post_para['notify_data']);
            $notify_id = $doc->getElementsByTagName("notify_id")->item(0)->nodeValue;

            //获取支付宝远程服务器ATN结果（验证是否是支付宝发来的消息）
            $responseTxt = 'true';
            if (!empty($notify_id)) {
                $responseTxt = $this->getResponse($notify_id);
            }

            //生成签名结果
            $isSign = $this->getSignVeryfy($decrypt_post_para, $_POST["sign"], false);

            //写日志记录
            //if ($isSign) {
            //	$isSignStr = 'true';
            //}
            //else {
            //	$isSignStr = 'false';
            //}
            //$log_text = "responseTxt=".$responseTxt."\n notify_url_log:isSign=".$isSignStr.",";
            //$log_text = $log_text.createLinkString($_POST);
            //logResult($log_text);

            //验证
            //$responsetTxt的结果不是true，与服务器设置问题、合作身份者ID、notify_id一分钟失效有关
            //isSign的结果不是true，与安全校验码、请求时的参数格式（如：带自定义参数等）、编码格式有关
            if (preg_match("/true$/i", $responseTxt) && $isSign) {
                return true;
            } else {
                return false;
            }
        }
    }

    /**
     * 针对return_url验证消息是否是支付宝发出的合法消息
     * @return 验证结果
     */
    function verifyReturn()
    {
        if (empty($_GET)) {//判断GET来的数组是否为空
            return false;
        } else {
            //生成签名结果
            $isSign = $this->getSignVeryfy($_GET, $_GET["sign"], true);

            //写日志记录
            //if ($isSign) {
            //	$isSignStr = 'true';
            //}
            //else {
            //	$isSignStr = 'false';
            //}
            //$log_text = "return_url_log:isSign=".$isSignStr.",";
            //$log_text = $log_text.createLinkString($_GET);
            //logResult($log_text);

            //验证
            //$responsetTxt的结果不是true，与服务器设置问题、合作身份者ID、notify_id一分钟失效有关
            //isSign的结果不是true，与安全校验码、请求时的参数格式（如：带自定义参数等）、编码格式有关
            if ($isSign) {
                return true;
            } else {
                return false;
            }
        }
    }

    /**
     * 解密
     * @param $input_para 要解密数据
     * @return 解密后结果
     */
    function decrypt($prestr)
    {
        return rsaDecrypt($prestr, trim($this->alipay_config['private_key_path']));
    }

    /**
     * 异步通知时，对参数做固定排序
     * @param $para 排序前的参数组
     * @return 排序后的参数组
     */
    function sortNotifyPara($para)
    {
        $para_sort['service'] = $para['service'];
        $para_sort['v'] = $para['v'];
        $para_sort['sec_id'] = $para['sec_id'];
        $para_sort['notify_data'] = $para['notify_data'];
        return $para_sort;
    }

    /**
     * 获取返回时的签名验证结果
     * @param $para_temp 通知返回来的参数数组
     * @param $sign 返回的签名结果
     * @param $isSort 是否对待签名数组排序
     * @return 签名验证结果
     */
    function getSignVeryfy($para_temp, $sign, $isSort)
    {
        //除去待签名参数数组中的空值和签名参数
        $para = $this->paraFilter($para_temp);
        //dump($para);
        //对待签名参数数组排序
        if ($isSort) {
            $para = $this->argSort($para);
        } else {
            $para = $this->sortNotifyPara($para);
        }
        //dump($para);
        //把数组所有元素，按照“参数=参数值”的模式用“&”字符拼接成字符串
        $prestr = $this->createLinkstring($para);

        //dump($prestr);

        $isSgin = false;
        switch (strtoupper(trim($this->alipay_config['sign_type']))) {
            case "MD5" :
                $isSgin = $this->md5Verify($prestr, $sign, $this->alipay_config['key']);
                //dump($isSgin);
                break;
            case "RSA" :
                $isSgin = $this->rsaVerify($prestr, trim($this->alipay_config['ali_public_key_path']), $sign);
                break;
            case "0001" :
                $isSgin = $this->rsaVerify($prestr, trim($this->alipay_config['ali_public_key_path']), $sign);
                break;
            default :
                $isSgin = false;
        }

        return $isSgin;
    }

    /**
     * 获取远程服务器ATN结果,验证返回URL
     * @param $notify_id 通知校验ID
     * @return 服务器ATN结果
     * 验证结果集：
     * invalid命令参数不对 出现这个错误，请检测返回处理中partner和key是否为空
     * true 返回正确信息
     * false 请检查防火墙或者是服务器阻止端口问题以及验证时间是否超过一分钟
     */
    function getResponse($notify_id)
    {
        $transport = strtolower(trim($this->alipay_config['transport']));
        $partner = trim($this->alipay_config['partner']);
        $veryfy_url = '';
        if ($transport == 'https') {
            $veryfy_url = $this->https_verify_url;
        } else {
            $veryfy_url = $this->http_verify_url;
        }
        $veryfy_url = $veryfy_url . "partner=" . $partner . "&notify_id=" . $notify_id;
        $responseTxt = $this->getHttpResponseGET($veryfy_url, $this->alipay_config['cacert']);

        return $responseTxt;
    }

    /////////////////////////////////////////////////////////

    //支付宝MD5通用方法接口修改，By App
    //基于Alipay_md5
    /**
     * 签名字符串
     * @param $prestr 需要签名的字符串
     * @param $key 私钥
     * return 签名结果
     */
    function md5Sign($prestr, $key)
    {
        $prestr = $prestr . $key;
        return md5($prestr);
    }

    /**
     * 验证签名
     * @param $prestr 需要签名的字符串
     * @param $sign 签名结果
     * @param $key 私钥
     * return 签名结果
     */
    function md5Verify($prestr, $sign, $key)
    {
        $prestr = $prestr . $key;
        $mysgin = md5($prestr);

        if ($mysgin == $sign) {
            return true;
        } else {
            return false;
        }
    }

    ///////////////////////////////////////////////////

    //支付宝支付系统核心方法修改，By App
    //基于Alipay_core
    /**
     * 把数组所有元素，按照“参数=参数值”的模式用“&”字符拼接成字符串
     * @param $para 需要拼接的数组
     * return 拼接完成以后的字符串
     */
    function createLinkstring($para)
    {
        $arg = "";
        while (list ($key, $val) = each($para)) {
            $arg .= $key . "=" . $val . "&";
        }
        //去掉最后一个&字符
        $arg = substr($arg, 0, count($arg) - 2);

        //如果存在转义字符，那么去掉转义
        if (get_magic_quotes_gpc()) {
            $arg = stripslashes($arg);
        }

        return $arg;
    }

    /**
     * 把数组所有元素，按照“参数=参数值”的模式用“&”字符拼接成字符串，并对字符串做urlencode编码
     * @param $para 需要拼接的数组
     * return 拼接完成以后的字符串
     */
    function createLinkstringUrlencode($para)
    {
        $arg = "";
        while (list ($key, $val) = each($para)) {
            $arg .= $key . "=" . urlencode($val) . "&";
        }
        //去掉最后一个&字符
        $arg = substr($arg, 0, count($arg) - 2);

        //如果存在转义字符，那么去掉转义
        if (get_magic_quotes_gpc()) {
            $arg = stripslashes($arg);
        }

        return $arg;
    }

    /**
     * 除去数组中的空值和签名参数
     * @param $para 签名参数组
     * return 去掉空值与签名参数后的新签名参数组
     */
    function paraFilter($para)
    {
        $para_filter = array();
        while (list ($key, $val) = each($para)) {
            if ($key == "sign" || $key == "sign_type" || $val == "") continue;
            else    $para_filter[$key] = $para[$key];
        }
        return $para_filter;
    }

    /**
     * 对数组排序
     * @param $para 排序前的数组
     * return 排序后的数组
     */
    function argSort($para)
    {
        ksort($para);
        reset($para);
        return $para;
    }

    /**
     * 写日志，方便测试（看网站需求，也可以改成把记录存入数据库）
     * 注意：服务器需要开通fopen配置
     * @param $word 要写入日志里的文本内容 默认值：空值
     */
    function logResult($word = '')
    {
        $fp = fopen("log.txt", "a");
        flock($fp, LOCK_EX);
        fwrite($fp, "执行日期：" . strftime("%Y%m%d%H%M%S", time()) . "\n" . $word . "\n");
        flock($fp, LOCK_UN);
        fclose($fp);
    }

    /**
     * 远程获取数据，POST模式
     * 注意：
     * 1.使用Crul需要修改服务器中php.ini文件的设置，找到php_curl.dll去掉前面的";"就行了
     * 2.文件夹中cacert.pem是SSL证书请保证其路径有效，目前默认路径是：getcwd().'\\cacert.pem'
     * @param $url 指定URL完整路径地址
     * @param $cacert_url 指定当前工作目录绝对路径
     * @param $para 请求的数据
     * @param $input_charset 编码格式。默认值：空值
     * return 远程输出的数据
     */
    function getHttpResponsePOST($url, $cacert_url, $para, $input_charset = '')
    {

        if (trim($input_charset) != '') {
            $url = $url . "_input_charset=" . $input_charset;
        }
        $curl = curl_init($url);
        curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, true);//SSL证书认证
        curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, 2);//严格认证
        curl_setopt($curl, CURLOPT_CAINFO, $cacert_url);//证书地址
        curl_setopt($curl, CURLOPT_HEADER, 0); // 过滤HTTP头
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);// 显示输出结果
        curl_setopt($curl, CURLOPT_POST, true); // post传输数据
        curl_setopt($curl, CURLOPT_POSTFIELDS, $para);// post传输数据
        $responseText = curl_exec($curl);
        //var_dump( curl_error($curl) );//如果执行curl过程中出现异常，可打开此开关，以便查看异常内容
        curl_close($curl);

        return $responseText;
    }

    /**
     * 远程获取数据，GET模式
     * 注意：
     * 1.使用Crul需要修改服务器中php.ini文件的设置，找到php_curl.dll去掉前面的";"就行了
     * 2.文件夹中cacert.pem是SSL证书请保证其路径有效，目前默认路径是：getcwd().'\\cacert.pem'
     * @param $url 指定URL完整路径地址
     * @param $cacert_url 指定当前工作目录绝对路径
     * return 远程输出的数据
     */
    function getHttpResponseGET($url, $cacert_url)
    {
        $curl = curl_init($url);
        curl_setopt($curl, CURLOPT_HEADER, 0); // 过滤HTTP头
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);// 显示输出结果
        curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, true);//SSL证书认证
        curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, 2);//严格认证
        curl_setopt($curl, CURLOPT_CAINFO, $cacert_url);//证书地址
        $responseText = curl_exec($curl);
        //var_dump( curl_error($curl) );//如果执行curl过程中出现异常，可打开此开关，以便查看异常内容
        curl_close($curl);

        return $responseText;
    }

    /**
     * 实现多种字符编码方式
     * @param $input 需要编码的字符串
     * @param $_output_charset 输出的编码格式
     * @param $_input_charset 输入的编码格式
     * return 编码后的字符串
     */
    function charsetEncode($input, $_output_charset, $_input_charset)
    {
        $output = "";
        if (!isset($_output_charset)) $_output_charset = $_input_charset;
        if ($_input_charset == $_output_charset || $input == null) {
            $output = $input;
        } elseif (function_exists("mb_convert_encoding")) {
            $output = mb_convert_encoding($input, $_output_charset, $_input_charset);
        } elseif (function_exists("iconv")) {
            $output = iconv($_input_charset, $_output_charset, $input);
        } else die("sorry, you have no libs support for charset change.");
        return $output;
    }

    /**
     * 实现多种字符解码方式
     * @param $input 需要解码的字符串
     * @param $_output_charset 输出的解码格式
     * @param $_input_charset 输入的解码格式
     * return 解码后的字符串
     */
    function charsetDecode($input, $_input_charset, $_output_charset)
    {
        $output = "";
        if (!isset($_input_charset)) $_input_charset = $_input_charset;
        if ($_input_charset == $_output_charset || $input == null) {
            $output = $input;
        } elseif (function_exists("mb_convert_encoding")) {
            $output = mb_convert_encoding($input, $_output_charset, $_input_charset);
        } elseif (function_exists("iconv")) {
            $output = iconv($_input_charset, $_output_charset, $input);
        } else die("sorry, you have no libs support for charset changes.");
        return $output;
    }


}//Alipay类结束