<?php
// 开始测试，待定使用
namespace App\Controller;

class IndexController extends BaseController
{
    public function index()
    {
        $this->redirect("Shop/index");
    }

    // 通用版帮助中心
    public function help()
    {
        $this->display();
    }



}