<?php
/**
 * Created by PhpStorm.
 * User: heqing
 * Date: 15/9/1
 * Time: 09:17
 */

namespace App\Controller;

use Think\Controller;

class ArticalController extends Controller
{
    public function index()
    {
        if(!I("get.id")){
            return;
        }
        $artical = M("Artical")->where(array("id" => I("get.id")))->find();
        $this->assign("artical", $artical);

        M("Artical")->where(array("id" => I("get.id")))->setInc("visiter");
        $this->display();
    }
}