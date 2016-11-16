<?php
namespace Admin\Controller;

use Think\Controller;

class ScoreController extends BaseController
{
    public function __construct()
    {
        parent::__construct();
    }

    public function index()
    {
        $product = M('Score'); // 实例化User对象
        $count = $product->count();// 查询满足要求的总记录数
        $Page = new \Think\Page($count, 12);// 实例化分页类 传入总记录数和每页显示的记录数(25)
        $Page->setConfig('theme', "<div class='widget-content padded text-center'><ul class='pagination'></li><li>%FIRST%</li><li>%UP_PAGE%</li><li>%LINK_PAGE%</li><li>%DOWN_PAGE%</li><li>%END%</li><li><a> %HEADER%  %NOW_PAGE%/%TOTAL_PAGE% 页</a></ul></div>");
        $show = $Page->show();// 分页显示输出
        // 进行分页数据查询 注意limit方法的参数要使用Page类的属性
        $product = $product->limit($Page->firstRow . ',' . $Page->listRows)->select();

        $bread = array(
            '0' => array(
                'name' => '积分管理',
                'url' => U('Admin/Score/index'),
            ),
        );
        $this->assign('breadhtml', $this->getBread($bread));

        $this->assign("product", $product);// 赋值数据集
        $this->assign('page', $show);// 赋值分页输出
        $this->display(); // 输出模板
    }

    public function add()
    {
        if ($_GET["id"]) {
            $product = M("Score")->where(array("id" => $_GET["id"]))->find();
            $this->assign("product", $product);
        }
        $this->display();
    }

    public function addProduct()
    {
        if ($_FILES["image"]["name"]) {
            $image = array();
            $image["image"] = $_FILES["image"];
            $image = $this->upload($image);
            $_POST["image"] = $image[0];
        } else {
            unset($_POST["image"]);
        }

        $data['name']=I('name','');
        $data['score']=I('score',0,'int');
        $data['pic']=I('pic','');
        $data['status']=I('status',0,'int');
        $data['recommend']=I('recommend',0,'int');
        $data['rank']=I('rank',0,'int');

        if ($_POST["id"] == 0) {
            M("Score")->add($_POST);
        } else {
            $data['id']=I('id',0,'int');
            M("Score")->save($_POST);
        }
        $this->ajaxReturn(array("status"=>"1","msg"=>"添加成功"));
    }

    public function del()
    {
        $id = $_GET["id"];
        M("Score")->where(array("id" => $id))->delete();
        $this->ajaxReturn(array("status"=>"1","msg"=>"删除成功"));
    }

    public function order()
    {
        $order = D('ScoreOrder'); // 实例化User对象
        $count = $order->count();// 查询满足要求的总记录数
        $Page = new \Think\Page($count, 12);// 实例化分页类 传入总记录数和每页显示的记录数(25)
        $Page->setConfig('theme', "<div class='widget-content padded text-center'><ul class='pagination'></li><li>%FIRST%</li><li>%UP_PAGE%</li><li>%LINK_PAGE%</li><li>%DOWN_PAGE%</li><li>%END%</li><li><a> %HEADER%  %NOW_PAGE%/%TOTAL_PAGE% 页</a></ul></div>");
        $show = $Page->show();// 分页显示输出
        // 进行分页数据查询 注意limit方法的参数要使用Page类的属性
        $order = $order->limit($Page->firstRow . ',' . $Page->listRows)->order("id desc")->relation(true)->select();

        $this->assign("order", $order);// 赋值数据集
        $this->assign('page', $show);// 赋值分页输出

        $this->display(); // 输出模板
    }

    public function cancel()
    {
        $data ["status"] = "-1";
        $data ["id"] = $_GET ["id"];
        M("ScoreOrder")->save($data);
        $this->ajaxReturn(array("status"=>"1","msg"=>"操作成功"));
    }

    public function delOrder()
    {
        M("ScoreOrder")->where(array("id" => $_GET["id"]))->delete();
        $this->ajaxReturn(array("status"=>"1","msg"=>"操作成功"));
    }

    public function publish()
    {
        $data ["status"] = "1";
        $data ["id"] = $_GET ["id"];
        M("ScoreOrder")->save($data);
        $this->ajaxReturn(array("status"=>"1","msg"=>"操作成功"));
    }

    public function payComplete()
    {
        $data ["pay_status"] = "1";
        $data ["id"] = $_GET ["id"];
        M("ScoreOrder")->save($data);
        $this->ajaxReturn(array("status"=>"1","msg"=>"操作成功"));
    }

    public function complete()
    {
        $data ["status"] = "2";
        $data ["id"] = $_GET ["id"];
        M("ScoreOrder")->save($data);
        $this->ajaxReturn(array("status"=>"1","msg"=>"操作成功"));
    }
}