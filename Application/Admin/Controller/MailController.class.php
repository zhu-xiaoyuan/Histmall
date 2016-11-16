<?php
// +----------------------------------------------------------------------
// | OneThink [ WE CAN DO IT JUST THINK IT ]
// +----------------------------------------------------------------------
// | Copyright (c) 2013 http://www.onethink.cn All rights reserved.
// +----------------------------------------------------------------------
// | Author: 麦当苗儿 <zuojiazi@vip.qq.com> <http://www.zjzit.cn>
// +----------------------------------------------------------------------

namespace Admin\Controller;

class MailController extends BaseController
{

    public function __construct()
    {
        parent::__construct();
    }

    public function index()
    {
        $config = M("MailConfig")->find();
        $this->assign("config", $config);

        $receiver = D('MailReceiver'); // 实例化User对象
        $count = $receiver->count();// 查询满足要求的总记录数
        $Page = new \Think\Page($count, 12);// 实例化分页类 传入总记录数和每页显示的记录数(25)
        $Page->setConfig('theme', "<div class='widget-content padded text-center'><ul class='pagination'></li><li>%FIRST%</li><li>%UP_PAGE%</li><li>%LINK_PAGE%</li><li>%DOWN_PAGE%</li><li>%END%</li><li><a> %HEADER%  %NOW_PAGE%/%TOTAL_PAGE% 页</a></ul></div>");
        $show = $Page->show();// 分页显示输出
        // 进行分页数据查询 注意limit方法的参数要使用Page类的属性
        $receiver = $receiver->limit($Page->firstRow . ',' . $Page->listRows)->order("id desc")->select();

        $this->assign("receiver", $receiver);// 赋值数据集
        $this->assign('page', $show);// 赋值分页输出

        $this->display();
    }

    public function addConfig()
    {
        $data['smtp']=I('smtp','');
        $data['mail']=I('mail','');
        $data['password']=I('password','');
        M("MailConfig")->where(array("id" => "1"))->save($data);
        $this->ajaxReturn(array("status"=>"1","msg"=>"设置成功"));
    }

    public function addMail()
    {
        $data['receiver']=I('receiver','');
        if ($_POST["id"] == 0) {
            M("MailReceiver")->add($data);
        } else {
            $data['id']=I('id',0,'int');
            M("MailReceiver")->save($data);
        }
        $this->ajaxReturn(array("status"=>"1","msg"=>"设置成功"));
    }

    public function del()
    {
        $id = $_GET["id"];
        M("MailReceiver")->where(array("id" => $id))->delete();
        $this->ajaxReturn(array("status"=>"1","msg"=>"删除成功"));
    }

    public function getMail()
    {
        $id = $_POST["id"];
        $receiver = M("MailReceiver")->where(array("id" => $id))->find();
        if ($receiver) {
            $this->ajaxReturn($receiver);
        }
    }
}
