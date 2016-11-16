<?php
/**
 * Created by PhpStorm.
 * User: heqing
 * Date: 15/9/10
 * Time: 14:32
 */

namespace Admin\Controller;


class WxprintController extends BaseController
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
            if (M("Wxprint")->find()) {
                M("Wxprint")->where(array("id" => "1"))->save($_POST);
            } else {
                M("Wxprint")->add($_POST);
            }
            $this->ajaxReturn(array("status"=>"1","msg"=>"设置成功"));
        } else {
            $wxprint = M("Wxprint")->find();
            $this->assign("wxprint", $wxprint);
            $this->display();
        }
    }
}