<?php
/**
 * Created by PhpStorm.
 * User: heqing
 * Date: 15/7/30
 * Time: 09:40
 */

namespace Addons\SystemInfo\Controller;

class IndexController extends InitController
{
    public function index()
    {
        $order = D('Addons://SystemInfo/Order')->relation(true)->select();
        dump($order);
        $this->assign("a", "1");
        $this->display();
    }


}