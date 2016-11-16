<?php
define('IS_CGI', (0 === strpos(PHP_SAPI, 'cgi') || false !== strpos(PHP_SAPI, 'fcgi')) ? 1 : 0);
define('IS_WIN', strstr(PHP_OS, 'WIN') ? 1 : 0);
define('IS_CLI', PHP_SAPI == 'cli' ? 1 : 0);

if (!IS_CLI) {
    // 当前文件名
    if (!defined('_PHP_FILE_')) {
        if (IS_CGI) {
            //CGI/FASTCGI模式下
            $_temp = explode('.php', $_SERVER['PHP_SELF']);
            define('_PHP_FILE_', rtrim(str_replace($_SERVER['HTTP_HOST'], '', $_temp[0] . '.php'), '/'));
        } else {
            define('_PHP_FILE_', rtrim($_SERVER['SCRIPT_NAME'], '/'));
        }
    }
    if (!defined('__ROOT__')) {
        $_root = rtrim(dirname(_PHP_FILE_), '/');
        define('__ROOT__', (($_root == '/' || $_root == '\\') ? '' : $_root));
    }
}

$wOpt = $_GET;
$wOpt['package'] = 'prepay_id=' . $wOpt['package'];
//var_dump("http://$_SERVER[HTTP_HOST]$_SERVER[REQUEST_URI]");
//var_dump($wOpt);
?>
<html>
<head>
    <title>微信支付</title>
    <meta charset="utf-8"/>
    <!--页面优化-->
    <meta name="MobileOptimized" content="320">
    <!--默认宽度320-->
    <meta name="viewport" content="width=device-width,minimum-scale=1.0,maximum-scale=1.0,user-scalable=no"/>
    <!--viewport 等比 不缩放-->
    <meta http-equiv="cleartype" content="on">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <!--删除苹果菜单-->
    <meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
    <!--默认颜色-->
    <meta name="apple-mobile-web-app-title" content="yes">
    <meta name="apple-touch-fullscreen" content="yes">
</head>
<body>
<div style="position: fixed;top:45%;left:50%;width:200px;text-align: center;margin-left:-100px;" id="tips">正在启动微信支付...</div>
</body>
</html>
<script type="text/javascript">
    function onBridgeReady() {
        WeixinJSBridge.invoke('getBrandWCPayRequest', {
            'appId': '<?php echo $wOpt['appId'];?>',
            'timeStamp': '<?php echo $wOpt['timeStamp'];?>',
            'nonceStr': '<?php echo $wOpt['nonceStr'];?>',
            'package': '<?php echo $wOpt['package'];?>',
            'signType': '<?php echo $wOpt['signType'];?>',
            'paySign': '<?php echo $wOpt['paySign'];?>'
        }, function (res) {
            var tipsDom = document.getElementById('tips');
            var is_group_buy = <?php echo $wOpt['is_group_buy']; ?>;
            var oid = "<?php echo $wOpt['oid']; ?>";

            if (res.err_msg == 'get_brand_wcpay_request:ok') {
                tipsDom.innerHTML = '<h3 style="color:#53a93f;">支付成功！</h3><p>即将跳转(<span id="cuntdown-redirect"></span>)</p>';
                var cdDom = document.getElementById('cuntdown-redirect');
                cdDom.innerHTML = cnt;
                var cnt = 2;
                var cc = setInterval(function () {
                    cdDom.innerHTML = cnt;
                    if (--cnt <= 0) {
                        clearInterval(cc);
                        if(is_group_buy){
                            window.location.href = 'http://<?php echo $_SERVER['HTTP_HOST'].__ROOT__;?>/App/Shop/groupBuyJudge/oid/'+oid;
                            //window.location.href = 'http://<?php echo $_SERVER['HTTP_HOST'].__ROOT__;?>/App/Shop/groupOrderList/sid/0';
                        }else{
                            window.location.href = 'http://<?php echo $_SERVER['HTTP_HOST'].__ROOT__;?>/App/Shop/orderList/sid/0';
                        }
                    }
                }, 1000);

            } else if (res.err_msg == 'get_brand_wcpay_request:cancel') {
                tipsDom.innerHTML = '<h3 style="color:#981b48;">您取消了支付！</h3><p>即将跳转(<span id="cuntdown-redirect"></span>)</p>';
                var cdDom = document.getElementById('cuntdown-redirect');
                var cnt = 2;
                cdDom.innerHTML = cnt;
                var cc = setInterval(function () {
                    cdDom.innerHTML = cnt;
                    if (--cnt <= 0) {
                        clearInterval(cc);
                        if(is_group_buy){
                            window.location.href = 'http://<?php echo $_SERVER['HTTP_HOST'].__ROOT__;?>/App/Shop/groupOrderList?sid=0';
                        }else{
                            window.location.href = 'http://<?php echo $_SERVER['HTTP_HOST'].__ROOT__;?>/App/Shop/orderList/sid/0';
                        }
                    }
                }, 1000);
            } else {
                alert('启动微信支付失败，请重试！');
                tipsDom.innerHTML = '<h3>启动微信支付失败</h3>' + '<p style="color:#999;">' + res.err_msg + '</p>';
            }
        });
    }
    if (typeof WeixinJSBridge == "undefined") {
        if (document.addEventListener) {
            document.addEventListener('WeixinJSBridgeReady', onBridgeReady, false);
        } else if (document.attachEvent) {
            document.attachEvent('WeixinJSBridgeReady', onBridgeReady);
            document.attachEvent('onWeixinJSBridgeReady', onBridgeReady);
        }
    } else {
        onBridgeReady();
    }

</script>