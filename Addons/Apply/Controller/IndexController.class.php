<?php
/**
 * Created by PhpStorm.
 * User: heqing
 * Date: 15/7/30
 * Time: 09:40
 */

namespace Addons\Apply\Controller;

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
                $url = $weObj->getOauthRedirect($this->appUrl . u_addons('Apply://App/Index/index'));
                header("location: $url");
                return;
            } else {
                $wxuser = $weObj->getOauthUserinfo($token["access_token"], $token["openid"]);
                session("sqopenid", $wxuser["openid"]);
                $this->oauthRegister($wxuser);
            }
        }

        $user = M("Vip")->where(array("openid" => session("sqopenid")))->find();

        $config = M("ApplyConfig")->where(array("status" => 1))->find();
        $this->assign("event", explode(',',$config["event"]));
        $this->assign("config", $config);

        $contact = M("VipAddress")->where(array("vipid" => $user["id"]))->find();
        if ($contact) {
            $this->assign("contact", $contact);
        }

        M("ApplyConfig")->where(array("id" => 1))->setInc("visiter");

        $this->assign('user', $user);// 赋值分页输出
        $this->display();
    }

    public function addConfig()
    {
        M("ApplyConfig")->where(array("id" => "1"))->save($_POST);
    }

    public function addOrder()
    {
        $userHas = M("VipAddress")->where(array("vipid" => session("userId")))->find();

        if ($userHas) {
            $contact ["id"] = $userHas ["id"];
            $contact ["name"] = $_POST ["name"];
            $contact ["mobile"] = $_POST ["phone"];
            $contact ["address"] = $_POST ["address"];
            M("VipAddress")->save($contact);
        } else {
            $contact ["vipid"] = session("userId");
            $contact ["name"] = $_POST ["name"];
            $contact ["mobile"] = $_POST ["phone"];
            $contact ["address"] = $_POST ["address"];
            M("VipAddress")->add($contact);
        }
        $userHas = M("VipAddress")->where(array("vipid" => session("userId")))->find();
        $contact_id = $userHas["id"];

        $data ["user_id"] = session("userId");
        $data ["contact_id"] = $contact_id;
        $data ["note"] = $_POST ["note"];
        $data ["event"] = $_POST["event"];
        $data ["time"] = date("Y-m-d H:i:s");
        $result = M("ApplyRecord")->add($data);

        M("ApplyConfig")->where(array("id" => 1))->setInc("apply");
        if ($result) {
            $this->ajaxReturn($result);
        }
    }


}