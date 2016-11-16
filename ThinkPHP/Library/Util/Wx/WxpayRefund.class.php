<?php
/**
 * App微信退款 2016年8月31日16:52:54
 */

namespace Util\Wx;

class WxpayRefund
{
    static $sslCert;
    static $sslKey;

    static $url = "https://api.mch.weixin.qq.com/secapi/pay/refund";
    public $mchkey = '';
    public $values = array();

    public function __construct($params){
        self::$sslCert = DATA_PATH."cacert/apiclient_cert.pem";
        self::$sslKey  = DATA_PATH."cacert/apiclient_key.pem";
    }

    /**
     * 申请退款，WxPayRefund中（必填）
     *      appid
     *      mch_id
     *      nonce_str
     *      out_trade_no、transaction_id至少填一个且
     *      out_refund_no
     *      total_fee
     *      refund_fee
     *      op_user_id
     * @return 成功时返回，其他抛异常
     */
    public function refund($params, $timeOut = 6)
    {
        //检测必填参数
        if(!$params['out_trade_no'] && !$params['transaction_id']) {
            return ['status'=>0,'msg'=>"退款申请接口中，out_trade_no、transaction_id至少填一个！"];
        }else if(!$params['out_refund_no']){
            return ['status'=>0,'msg'=>"退款申请接口中，缺少必填参数out_refund_no！"];
        }else if(!$params['total_fee']){
            return ['status'=>0,'msg'=>"退款申请接口中，缺少必填参数total_fee！"];
        }else if(!$params['refund_fee']){
            return ['status'=>0,'msg'=>"退款申请接口中，缺少必填参数refund_fee！"];
        }else if(!$params['op_user_id']){
            return ['status'=>0,'msg'=>"退款申请接口中，缺少必填参数op_user_id！"];
        }

        $params['sign'] = $this->getSign($params);  //生成签名
        $xml = $this->ToXml($params);               //生成退款xml数据

        $response = $this->postXmlCurl($xml, self::$url, true, $timeOut);   //申请退款

        $resArr = $this->FromXml($response);    //解析微信 返回的 退款结果

        if($resArr['return_code'] != 'SUCCESS'){
            return ['status'=>0,'msg'=>$resArr['err_code'].':'.$resArr['err_code_des'],'xml'=>$response];
        }
        if($this->checkSign($resArr)){
            return ['status'=>1,'msg'=>'退款成功','array'=>$resArr,'xml'=>$response];    //返回成功status,和返回的信息
        }
    }

    /**
     * 以post方式提交xml到对应的接口url
     * @param string $xml  需要post的xml数据
     * @param string $url  url
     * @param bool $useCert 是否需要证书，默认不需要
     * @param int $second   url执行超时时间，默认30s
     * @throws Exception
     */
    private function postXmlCurl($xml, $url, $useCert = false, $second = 30)
    {
        $ch = curl_init();
        //设置超时
        curl_setopt($ch, CURLOPT_TIMEOUT, $second);
        
        curl_setopt($ch,CURLOPT_URL, $url);
        curl_setopt($ch,CURLOPT_SSL_VERIFYPEER,TRUE);
        curl_setopt($ch,CURLOPT_SSL_VERIFYHOST,2);//严格校验
        //设置header
        curl_setopt($ch, CURLOPT_HEADER, FALSE);
        //要求结果为字符串且输出到屏幕上
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);

        if($useCert == true){
            //设置证书
            //使用证书：cert 与 key 分别属于两个.pem文件
            curl_setopt($ch,CURLOPT_SSLCERTTYPE,'PEM');
            curl_setopt($ch,CURLOPT_SSLCERT, self::$sslCert);
            curl_setopt($ch,CURLOPT_SSLKEYTYPE,'PEM');
            curl_setopt($ch,CURLOPT_SSLKEY, self::$sslKey);
        }
        //post提交方式
        curl_setopt($ch, CURLOPT_POST, TRUE);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $xml);
        //运行curl
        $data = curl_exec($ch);
        //返回结果
        if($data){
            curl_close($ch);
            return $data;
        } else {
            $error = curl_errno($ch);
            curl_close($ch);
            throw new \Exception("curl出错，错误码:$error");
        }
    }

    /**
     * 作用：生成签名
     */
    public function getSign($Obj)
    {
        if(!$this->mchkey){
            return ['status'=>0,'msg'=>"mchkey未赋值"];
        }
        foreach ($Obj as $k => $v) {
            if (!empty($v) || $v ==='0')    //微信返回字段，代金券参数可能为0，（经转化为string(0)）,需要参与签名计算！！！所以加上$v==='0'
                $Parameters[$k] = $v;
        }
        //签名步骤一：按字典序排序参数
        ksort($Parameters);
        $String = $this->formatBizQueryParaMap($Parameters, false);
        //签名步骤二：在string后加入KEY
        $String = $String . "&key=" . $this->mchkey;
        //签名步骤三：MD5加密
        $String = md5($String);
        //签名步骤四：所有字符转为大写
        return strtoupper($String);
    }

    /**
     *    作用：格式化参数，签名过程需要使用
     */
    function formatBizQueryParaMap($paraMap, $urlencode)
    {
        $buff = "";
        ksort($paraMap);
        foreach ($paraMap as $k => $v) {
            if ($urlencode) {
                $v = urlencode($v);
            }
            $buff .= $k . "=" . $v . "&";
        }
        $reqPar = '';
        if (strlen($buff) > 0) {
            $reqPar = substr($buff, 0, strlen($buff) - 1);
        }
        return $reqPar;
    }

    /**
     * 获取毫秒级别的时间戳
     */
    private function getMillisecond()
    {
        //获取毫秒的时间戳
        $time = explode ( " ", microtime () );
        $time = $time[1] . ($time[0] * 1000);
        $time2 = explode( ".", $time );
        $time = $time2[0];
        return $time;
    }

    public function ToXml($data)
    {
        $xml = "<xml>";
        foreach ($data as $key=>$val)
        {
            if (is_numeric($val)){
                $xml.="<".$key.">".$val."</".$key.">";
            }else{
                $xml.="<".$key."><![CDATA[".$val."]]></".$key.">";
            }
        }
        $xml.="</xml>";
        return $xml;
    }

    /**
     *
     * 产生随机字符串，不长于32位
     * @param int $length
     * @return 产生的随机字符串
     */
    public static function getNonceStr($length = 32)
    {
        $chars = "abcdefghijklmnopqrstuvwxyz0123456789";
        $str ="";
        for ( $i = 0; $i < $length; $i++ )  {
            $str .= substr($chars, mt_rand(0, strlen($chars)-1), 1);
        }
        return $str;
    }

    /**
     * 将xml转为array
     * @param string $xml
     * @throws Exception
     */
    public function FromXml($xml)
    {
        if(!$xml){
            throw new \Exception("xml数据异常！");
        }
        //将XML转为array
        //禁止引用外部xml实体
        libxml_disable_entity_loader(true);
        $this->values = json_decode(json_encode(simplexml_load_string($xml, 'SimpleXMLElement', LIBXML_NOCDATA)), true);
        return $this->values;
    }

    /**
     * 检测签名
     */
    public function checkSign($params){
        if(array_key_exists('sign', $params)){
            $sign = $params['sign'];
            unset($params['sign']);
            if($sign == $this->getSign($params)){
                return true;
            }
        }
        throw new \Exception("签名错误！");
    }
}