<?php
/**
 * 微信红包类
 * @author 浩哥
 */

class WxHb {
    //证书
	public $apiclient_cert = '';
	public $apiclient_key = '';
    public $appid;//微信AppID
	public $appsecret;//微信AppSecret
	public $mchid;//商户号
	public $apikey;//商户支付密钥Key

	function __construct($appid , $appsecret , $mchid , $apikey) 
	{
		$this->appid = $appid;
		$this->appsecret = $appsecret;
		$this->mchid = $mchid;
		$this->apikey = $apikey;
	}
    /**
	*支付前准备
	*@return boolean
	*/
	function inited(){
		$inited = true;

// 		if(!is_numeric($amount)){
// 			$this->error = "金额参数错误";
// 			$inited = false;
// 		}elseif($amount<100){
// 			$this->error = "金额太小";
// 			$inited = false;
// 		}elseif($amount>20000){
// 			$this->error = "金额太大";
// 			$inited = false;
// 		}
		
		$this->license();
		return $inited;
	}
	/**
	*完成支付操作
	*@url string
	*@obj array
	*@return boolean
	*/
	function Pay($url,$obj){	
		$obj['nonce_str'] = $this->create_noncestr();
		$sign = $this->getSign($obj);
		$obj['sign'] = $sign;
		$postXml = $this->arrayToXml($obj);
		$responseXml = $this->CurlPostSsl($url,$postXml);
		return $responseXml;
	}
	/**
	*红包查询
	*@return array
	*/
	public function BagSelect($partnertradeno){
		$this->license();
		$obj = array();
		$obj['appid'] = $this->appid;
		$obj['mch_id'] = $this->mchid;
		$obj['mch_billno'] = $partnertradeno;
		$obj['bill_type'] = 'MCHT';
		$url = "https://api.mch.weixin.qq.com/mmpaymkttransfers/gethbinfo";
		return $this->Pay($url,$obj);
	}
	/**
	*创建随机字串
	*@return string
	*/
	private function create_noncestr($length = 32){
		$chars = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789';
		$str = '';
		for ($i = 0; $i <$length; $i++){
			$str .= substr($chars,mt_rand(0,strlen($chars)-1),1);
		}
		return $str;
	}
		/**
	*创建签名
	*@return string
	*/
	private function getSign($arr){
		ksort($arr); //按照键名排序
		$sign_raw = '';
		foreach($arr as $k => $v){
			$sign_raw .= $k.'='.$v.'&';
		}
		$sign_raw .= 'key='.$this->apikey;

		return strtoupper(md5($sign_raw));
	}
		/**
     * WXHongBao::genXMLParam()
     * 生成post的参数xml数据包
     * @return $xml
     */
	private function arrayToXml($arr){
		$xml ="<xml>";
		foreach ($arr as $key => $val) {
			if (is_numeric($val)) {
				$xml .= "<".$key.">".$val."</".$key.">";
			}else{
				$xml .= "<".$key."><![CDATA[".$val."]]></".$key.">";
			}
		}
		$xml .= "</xml>";
		return $xml;		
	}
	
	/**
	*证书初始化
	*放在同目录 cacert/文件夹下
	*/
	private function license(){

		if(!$this->apiclient_cert){
			$this->apiclient_cert = DATA_PATH."/cacert/apiclient_cert.pem";	    
		}
		if(!$this->apiclient_key){
			$this->apiclient_key = DATA_PATH."/cacert/apiclient_key.pem";		    
		} 

	}	
	
	/**
     * curl提交
     * @return $boolean
     */
	private function CurlPostSsl($url,$xml,$second = 10){
		$ch = curl_init();   	
		curl_setopt($ch,CURLOPT_TIMEOUT,$second);
		curl_setopt($ch,CURLOPT_RETURNTRANSFER, 1);    	
		curl_setopt($ch,CURLOPT_URL,$url);
		curl_setopt($ch,CURLOPT_SSL_VERIFYPEER,false);
		curl_setopt($ch,CURLOPT_SSL_VERIFYHOST,false);

		curl_setopt($ch,CURLOPT_SSLCERTTYPE,'PEM');
		curl_setopt($ch,CURLOPT_SSLCERT,$this->apiclient_cert);    	
		curl_setopt($ch,CURLOPT_SSLKEYTYPE,'PEM');
		curl_setopt($ch,CURLOPT_SSLKEY,$this->apiclient_key);
		curl_setopt($ch,CURLOPT_POST, 1);
		curl_setopt($ch,CURLOPT_POSTFIELDS,$xml);
		$data = curl_exec($ch);
		if($data){
			curl_close($ch);            
			$rsxml = simplexml_load_string($data);
			if($rsxml->return_code == 'SUCCESS' ){
				return $data;
			}else{
				$this->error = $rsxml->return_msg;
				return false;    
			}
		}else{ 
			$this->error = curl_errno($ch);
			curl_close($ch);
			return false;
		}
	}	
	





	/**
	*企业支付
	*@return boolean
	*/
	public function ComPay(){
		if(!$this->inited()) return;
		$obj = array();
		$obj['openid'] = $this->openid;
		$obj['amount'] = $this->amount;
		$obj['desc'] = $this->remark;
		$obj['mch_appid'] = $this->mchappid;
		$obj['mchid'] = $this->mchid;
		$obj['partner_trade_no'] = $this->partnertradeno;
		$obj['spbill_create_ip'] = $this->spbillcreateip;
		$obj['check_name'] = $this->checkname;
		$url = $this->api_compay;
		return $this->Pay($url,$obj);
	}	
	/**
	*企业支付查询
	*@return array
	*/
	public function ComPaySelect(){
		$this->license();
		$obj = array();
		$obj['appid'] = $this->mchappid;
		$obj['mch_id'] = $this->mchid;
		$obj['partner_trade_no'] = $this->partnertradeno;
		$url = $this->api_compay_select;
		return $this->Pay($url,$obj);
	}




	
	
	
	
	
}