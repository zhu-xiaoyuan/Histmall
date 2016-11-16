<?php
/**
 * Created by PhpStorm.
 * User: heqing
 * Date: 15/7/30
 * Time: 09:40
 */

namespace Addons\Vote\Controller;

class IndexController extends InitController
{
    public $appUrl = "";
    public function __construct()
    {
        parent::__construct();
        $this->appUrl = "http://" . I("server.HTTP_HOST");
    }

    public function init()
    {
        return R("App/Common/init");
    }

    public function oauthRegister($wxuser)
    {
        return R("App/Common/oauthRegister", array($wxuser));
    }

    public function index()
    {
        R("App/Common/oauthDebug");

        if (!session("sqopenid")) {
            $weObj = $this->init();
            $token = $weObj->getOauthAccessToken();
            if (!$token) {
                $weObj = $this->init();
                $url = $weObj->getOauthRedirect($this->appUrl . u_addons('Vote://App/Index/index'));
                header("location: $url");
                return;
            } else {
                $wxuser = $weObj->getOauthUserinfo($token["access_token"], $token["openid"]);
                session("sqopenid", $wxuser["openid"]);
                $this->oauthRegister($wxuser);
            }
        }

        $user = M("Vip")->where(array("openid" => session("sqopenid")))->find();

        $config = M("VoteConfig")->find();
        $this->assign("config", $config);
        $this->assign("user", $user);

        M("VoteConfig")->where(array("id"=>$config["id"]))->setInc("visiter_num");
        $this->display();
    }

    public function vote()
    {
        M("VoteRecord")->add(array("user_id"=>session("userId")));
        M("VoteConfig")->where(array("id"=>I("get.id")))->setInc("vote_num");
    }
}
