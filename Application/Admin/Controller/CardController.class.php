<?php
/**
 * Created by PhpStorm.
 * User: heqing
 * Date: 15/7/7
 * Time: 14:36
 */

namespace Admin\Controller;

class CardController extends BaseController
{
    public function __construct()
    {
        parent::__construct();
    }

    public function index()
    {
        $rules = M("PayRule")->select();
        $this->assign("rules", $rules);
        $this->display();
    }

    public function getRules()
    {
        $rules = M("PayRule")->where(array("id" => $_POST["id"]))->find();
        $this->ajaxReturn($rules);
    }

    public function delRules()
    {
        M("PayRule")->where(array("id" => $_GET["id"]))->delete();
        $this->ajaxReturn(array("status"=>"1","msg"=>"删除成功"));
    }

    public function addRules()
    {
        $data['money']=I('money',0,'float');
        $data['handsel']=I('handsel',0,'float');

        if ($_POST["id"] == 0) {
            M("PayRule")->add($_POST);
        } else {
            $data['id']=I('id',0,'int');
            M("PayRule")->save($_POST);
        }
        $this->ajaxReturn(array("status"=>"1","msg"=>"设置成功"));
    }
}