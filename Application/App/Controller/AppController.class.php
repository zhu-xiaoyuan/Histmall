<?php
namespace App\Controller;

use Think\Controller;

class AppController extends Controller
{

    public function log()
    {
        $data = M("sys_log_me")->order("ctime desc")->find();
        dump(json_decode($data["content"]));
    }
}