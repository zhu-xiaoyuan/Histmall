<?php
/**
 * Created by PhpStorm.
 * User: heqing
 * Date: 15/7/30
 * Time: 09:40
 */

namespace Addons\Sign\Controller;

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
                $url = $weObj->getOauthRedirect($this->appUrl . u_addons('Sign://App/Index/index'));
                header("location: $url");
                return;
            } else {
                $wxuser = $weObj->getOauthUserinfo($token["access_token"], $token["openid"]);
                session("sqopenid", $wxuser["openid"]);
                $this->oauthRegister($wxuser);
            }
        }

        $user = M("Vip")->where(array("openid" => session("sqopenid")))->find();
        $contact = M("VipAddress")->where(array("user_id" => $user["id"]))->find();
        if ($contact) {
            $this->assign("contact", $contact);
        }
        $this->assign("user", $user);

        $product = M("Score")->where(array("status"=>1))->select();
        foreach ($product as $k => $v) {
            $listpic = $this->getPic($v['pic']);
            $product[$k]['imgurl'] = $listpic['imgurl'];
        }

        $this->assign("product", $product);
        $this->display();
    }

    public function addOrder()
    {
        $user = M("Vip")->where(array("openid" => session("sqopenid")))->find();
        $score = floatval($user["score"]) - floatval($_POST["score"]);
        if ($score >= 0) {
            M("Vip")->where(array("id" => $user["id"]))->save(array("score" => $score));
        } else {
            return;
        }

        $userHas = M("VipAddress")->where(array("user_id" => session("userId")))->find();
        if ($userHas) {
            $contact ["id"] = $userHas ["id"];
            $contact ["user_id"] = session("userId");
            $contact ["name"] = $_POST ["name"];
            $contact ["mobile"] = $_POST ["mobile"];
            $contact ["address"] = $_POST ["address"];
            M("VipAddress")->save($contact);
        } else {
            $contact ["user_id"] = session("userId");
            $contact ["name"] = $_POST ["name"];
            $contact ["city"] = "";
            $contact ["area"] = "";
            $contact ["mobile"] = $_POST ["mobile"];
            $contact ["address"] = $_POST ["address"];
            M("VipAddress")->add($contact);
        }
        $userHas = M("VipAddress")->where(array("user_id" => session("userId")))->find();
        $contact_id = $userHas["id"];

        $data ["user_id"] = session("userId");
        $data ["address_id"] = $contact_id;
        $data ["orderid"] = date("ymdhis") . mt_rand(1, 9);
        $data ["totalscore"] = $_POST["score"];
        $data ["status"] = 0;
        $data ["note"] = $_POST ["note"];
        $data ["time"] = date("Y-m-d H:i:s");
        $data ["score_id"] = $_POST ["id"];
        $result = M("ScoreOrder")->add($data);
        if ($result) {
            $this->ajaxReturn($result);
        }
    }

    public function sign()
    {
        $today = date("Y-m-d");
        $where["time"] = array("like", $today . "%");
        $where["user_id"] = session("userId");
        $record = D("Addons://Sign/SignRecord")->where($where)->find();
        if ($record) {
            $this->ajaxReturn(array("status" => 0));
            return;
        }

        $user = M("Vip")->where(array("id" => session("userId")))->find();
        $continue_sign = $user["continue_sign"];

        $yesterday = date("Y-m-d", strtotime('-1 day'));
        $where["time"] = array("like", $yesterday . "%");
        $record = D("SignRecord")->where($where)->find();

        if ($record) {
            $continue_sign++;
        } else {
            $continue_sign = 0;
        }

        if ($continue_sign > 30) {
            $continue_sign = 0;
        }
        $config = M("SignConfig")->find();
        if ($config) {
            $addScore = floatval($continue_sign) * floatval($config["continue_sign"]) + floatval($config["first_sign"]);
            M("SignRecord")->add(array("user_id" => session("userId"), "score" => $addScore));

            $score = floatval($user["score"]) + $addScore;
            M("Vip")->where(array("id" => $user["id"]))->save(array("score" => $score, "continue_sign" => $continue_sign));
            $this->ajaxReturn(array("status" => 1, "score" => $addScore));
        }

    }

    //获取单张图片
    public function getPic($id)
    {
        $m = M('Upload_img');
        $map['id'] = $id;
        $list = $m->where($map)->find();
        if ($list) {
            $list['imgurl'] = __ROOT__ . "/Upload/" . $list['savepath'] . $list['savename'];
        }
        return $list ? $list : "";
    }
}
