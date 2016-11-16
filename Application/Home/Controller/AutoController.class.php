<?php
// +----------------------------------------------------------------------
// | 单用户微信基础类
// +----------------------------------------------------------------------
namespace Home\Controller;

use Think\Controller;

class AutoController extends Controller
{
    //全局相关
    public static $_autoset; //缓存全局自动化配置

    public function __construct($options)
    {
        //读取用户配置存全局
        self::$_autoset = M('Auto_set')->find();
    }

    public function wc()
    {
        $code = I('code');

        $m = M('Shop_order');
        $map['ispay'] = 1;
        $map['status'] = 3;
        $allorder = $m->where($map)->select();
        dump($allorder);
    }

} //API类结束