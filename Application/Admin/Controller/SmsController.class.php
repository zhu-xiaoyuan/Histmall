<?php
/**
 * Created by PhpStorm.
 * User: heqing
 * Date: 15/9/10
 * Time: 14:32
 */

namespace Admin\Controller;


class SmsController extends BaseController
{

    public function __construct()
    {
        parent::__construct();
    }

    public function index()
    {

    }

    public function set()
    {
        if (IS_POST) {
            $data['user'] = I('user','');
            $data['pass'] = I('pass','');
            if (M("Sms")->find()) {
                M("Sms")->where(array("id" => "1"))->save($_POST);
            } else {
                M("Sms")->add($_POST);
            }
            $this->ajaxReturn(array("status"=>"1","msg"=>"设置成功"));
        } else {
            $sms = M("Sms")->find();
            $this->assign("sms", $sms);
            $this->display();
        }
    }
}