<?php
/**
 * Created by PhpStorm.
 * User: heqing
 * Date: 15/7/7
 * Time: 10:37
 */

namespace App\Controller;


use Think\Controller;

class CommonController extends Controller
{
    public $appUrl = "";
    public static $WAP;//CMS全局静态变量

    public function __construct()
    {
        parent::__construct();
        $this->appUrl = "http://" . I("server.HTTP_HOST");

        $_GET = I("get.");
        $_POST = I("post.");

        self::$WAP['vipset'] = $_SESSION['WAP']['vipset'] = $this->checkVipSet();
    }

    //返回VIP配置
    public function checkVipSet()
    {
        $set = M('vip_set')->find();
        return $set ? $set : utf8error('会员设置未定义！');
    }

    public function oauthDebug()
    {
        $config = M("Set")->find();
        if ($config['wxdebug']) {
            $_SESSION['sqmode'] = 'wecha';
            $_SESSION['sqopenid'] = 'owv5gsyXxBMYOjeM56e5vK33it0Y';
            $_SESSION['userId'] = 162;
        }
    }

    public function init()
    {
        $config = M("Set")->find();

        $options = array(
            'token' => $config ["wxtoken"], //填写你设定的key
            'encodingaeskey' => "", //填写加密用的EncodingAESKey
            'appid' => $config ["wxappid"], //填写高级调用功能的app id
            'appsecret' => $config ["wxappsecret"] //填写高级调用功能的密钥
        );
        $weObj = new \Util\Wx\Wechat ($options);
        return $weObj;
    }

    public function oauthRegister($wxuser)
    {
        $user = M("Vip")->where(array("openid" => $wxuser["openid"]))->find();
        $userId = 0;
        if($user){
            $userId = $user['id'];

            $data['id'] = $user['id'];
            $data['nickname'] = $wxuser['nickname'];
            $data['sex'] = $wxuser['sex'];
            $data['city'] = $wxuser['city'];
            $data['province'] = $wxuser['province'];
            $data['country'] = $wxuser['country'];
            $data['headimgurl'] = $wxuser['headimgurl'];
            M("Vip")->save($data);
        }else{
            $data['pid'] = 0;
            $data['openid'] = $wxuser['openid'];
            $data['nickname'] = $wxuser['nickname'];
            $data['sex'] = $wxuser['sex'];
            $data['city'] = $wxuser['city'];
            $data['province'] = $wxuser['province'];
            $data['country'] = $wxuser['country'];
            $data['headimgurl'] = $wxuser['headimgurl'];
            $data['score'] = self::$WAP['vipset']['reg_score'];
            $data['exp'] = self::$WAP['vipset']['reg_exp'];
            $data['cur_exp'] = self::$WAP['vipset']['reg_exp'];
            $level = $this->getLevel($data['exp']);
            $data['levelid'] = $level['levelid'];
            $data['ctime'] = time();
            $data['cctime'] = time();
            $userId = M("Vip")->add($data);
        }

        session("sqopenid", $wxuser["openid"]);
        session("userId", $userId);
    }

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
            return false;
        }
        return $level;
    }

    //判断微信浏览器
    function is_weixin()
    {
        if (strpos($_SERVER['HTTP_USER_AGENT'], 'MicroMessenger') !== false) {
            return true;
        }
        return false;
    }

    public function http_get($url)
    {
        $oCurl = curl_init();
        if (stripos($url, "https://") !== FALSE) {
            curl_setopt($oCurl, CURLOPT_SSL_VERIFYPEER, FALSE);
            curl_setopt($oCurl, CURLOPT_SSL_VERIFYHOST, FALSE);
            curl_setopt($oCurl, CURLOPT_SSLVERSION, 1); //CURL_SSLVERSION_TLSv1
        }
        curl_setopt($oCurl, CURLOPT_URL, $url);
        curl_setopt($oCurl, CURLOPT_RETURNTRANSFER, 1);
        $sContent = curl_exec($oCurl);
        $aStatus = curl_getinfo($oCurl);
        curl_close($oCurl);
        if (intval($aStatus["http_code"]) == 200) {
            return $sContent;
        } else {
            return false;
        }
    }
}