<?php
namespace Home\Controller;

use Think\Controller;

class IndexController extends Controller
{
    public function index()
    {
        $this->display();
    }

    public function help()
    {
        $this->display();
    }

    public function about()
    {
        $temp = M('Shop_set')->find();
        $this->assign('shop', $temp);
        $this->display();
    }

    public function clear()
    {
        session(null);
        $set = M('set')->find();
        $url = $set['wxurl'] . '/App';
        header("Location:" . $url);
    }
}