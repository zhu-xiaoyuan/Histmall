<?php
/**
 * Created by PhpStorm.
 * User: heqing
 * Date: 15/9/10
 * Time: 14:32
 */

namespace Admin\Controller;


class AlipayController extends BaseController
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
            $data['alipayname']=I('alipayname','');
            $data['partner']=I('partner','');
            $data['key']=I('key','');

            if (M("Alipay")->find()) {
                M("Alipay")->where(array("id" => "1"))->save($data);
            } else {
                M("Alipay")->add($data);
            }
            $this->ajaxReturn(array("status"=>"1","msg"=>"设置成功"));
        } else {
            $alipay = M("Alipay")->find();
            $this->assign("alipay", $alipay);
            $this->display();
        }
    }
}