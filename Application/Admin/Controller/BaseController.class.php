<?php
// +----------------------------------------------------------------------
// | 用户后台基础类--CMS分组基础类
// +----------------------------------------------------------------------
namespace Admin\Controller;

use Think\Controller;

class BaseController extends Controller
{
    protected static $SYS; //系统级全局静态变量
    protected static $CMS; //CMS全局静态变量
    protected static $SHOP; //Shop变量全局设置

    //初始化验证模块
    protected function _initialize()
    {
        //预留检测
        $this->checkApp();
        //刷新系统全局配置
        self::$SYS['set'] = $_SESSION['SYS']['set'] = $this->checkSysSet();
        //刷新CMS全局配置
        self::$CMS['set'] = $_SESSION['CMS']['set'] = $this->checkSet();
        //刷新SHOP全局配置
        self::$SHOP['set'] = $_SESSION['SHOP']['set'] = $this->checkShopSet();

        //检测登陆状态
        $check = $this->checkLogin();
        if ($check) {
            if(!$this->hasPermission()){
                $this->redirect(U('Admin/Public/permission'));
            }
        }
    }

    //CMS总入口
    public function index()
    {
        $this->display();
    }

    //全局App预留方法
    public function checkApp()
    {
        return TRUE;
    }

    //返回系统全局配置
    public function checkSysSet()
    {
        $set = M('Set')->find();
        return $set ? $set : utf8error('系统还未配置！');
    }

    //返回CMS全局配置
    public function checkSet()
    {
        $set = M('Cms_set')->find();
        return $set ? $set : utf8error('系统还未配置！');
    }

    // 返回Shop商城名称
    public function checkShopSet()
    {
        $set = M('Shop_set')->find();
        $_SESSION['CMS']['set']['name'] = $set['name'];
        return $set ? $set : utf8error('系统还未配置！');
    }

    //检查用户是否登陆,返回TRUE或跳转登陆
    public function checkLogin()
    {
        $passlist = array('submit','applogin','login', 'logout', 'reg', 'verify', 'expires'); //不检测登陆状态的操作
        $pass = in_array(ACTION_NAME, $passlist);
        if (!$pass) {
            if (!isset($_SESSION['CMS']['uid'])) {
                if(ACTION_NAME == 'showsubmit'){
                    $this->redirect(U('Admin/App/applogin'));
                }
                if (IS_AJAX) {
                    $this->redirect(U('Admin/Public/expires'));
                } else {
                    $this->redirect(U('Admin/Public/login'));
                }
                return FALSE;
            }
        }
        return TRUE;
    }

    //拼装面包导航
    public function getBread($bread)
    {
        if ($bread) {
            $this->assign('bread', $bread);
            return $this->fetch('Base_bread');
        } else {
            $this->error('请传入面包导航！');
        }
    }

    //封装分页类
    public function getPage($count, $psize, $loader, $loadername, $searchname, $map)
    {
        if (!$count && !$psize || !$loader || !$loadername) {
            die('缺少分页参数!');
        }
        $page = new \Util\Pagecms($count, $psize); // 实例化分页类 传入总记录数和每页显示的记录数
        $page->setConfig('loader', $loader);
        $page->setConfig('loadername', $loadername);
        //绑定前端form搜索表单ID,默认为#App-search
        if ($searchname) {
            $page->setConfig('searchname', $searchname);
        }
        if ($map) {
            foreach ($map as $key => $val) {
                $page->parameter[$key] = urlencode($val);
            }
        }
        $show = $page->show(); // 分页显示输出
        $this->assign('page', $show);
        return true;
    }

    //获取单张图片
    public function getPic($id)
    {
        $m = M('Upload_img');
        $map['id'] = $id;
        $list = $m->where($map)->find();
        if ($list) {
            $list['imgurl'] = $list['savepath'] . '/' . $list['savename'];
        }
        return $list ? $list : "";
    }

    //获取图集合
    public function getAlbum($ids)
    {
        $m = M('Upload_img');
        $map['id'] = array('in', in_parse_str($ids));
        $list = $m->where($map)->select();
        foreach ($list as $k => $v) {
            $list[$k]['imgurl'] = $list[$k]['savepath'] . '/' . $list[$k]['savename'];
        }
        return $list ? $list : "";
    }

    //获取会员等级经验对称数据
    public function getlevel($exp)
    {
        $data = M('Vip_level')->order('exp')->select();
        if ($data) {
            $level = array();
            foreach ($data as $k => $v) {
                if ($k + 1 == count($data)) {
                    if ($exp >= $data[$k]['exp']) {
                        $level['levelid'] = $data[$k]['id'];
                        $level['levelname'] = $data[$k]['name'];
                    }
                } else {
                    if ($exp >= $data[$k]['exp'] && $exp < $data[$k + 1]['exp']) {
                        $level['levelid'] = $data[$k]['id'];
                        $level['levelname'] = $data[$k]['name'];
                    }
                }
            }
        } else {
            return utf8error('会员等级未定义！');
        }
        return $level;
    }

    public function checkPermission($action = null)
    {
        if ($this->hasPermission()) {
            return true;
        } else {
            $this->redirect(U('Admin/Public/permission'));
        }
    }

    //判断用户是否有某个action的权限
    public function hasPermission($action = null)
    {
        if (empty($action)) {
            $action = __ACTION__;
        }
        $action = trim($action,'/');
        //TODO 判断逻辑
        // 1. 超级管理员 ， 返回true
        if($_SESSION['CMS']['user']['role_id'] == 0){   //角色为超级管理员
            return true;
        }
        // 2. 在白名单中，返回true
        $urlWhiteList = $this->urlWhiteList();
        if(in_array($action,$urlWhiteList)){
            return true;
        }
        // 3. 权限判断
        $auth = $this->getAuthUrl();    //获取权限路由
        $param = $_GET ? http_build_query($_GET) : null;   //个别路由需要使用
        if(in_array($action,$auth)) {   //不包含在权限路由内不能访问
            return true;
        }else{
            //如果不包含在权限中，带上参数再查一遍
            //$action = $action.'?'.$param;
            if($param){
                if(in_array($action.'?'.$param,$auth)){
                    return true;
                }
            }
            return false;
        }
    }

    //获取允许访问的路由
    protected function getAuthUrl(){
        unset($_SESSION['auth']);
        if(isset($_SESSION['auth'])){
            return $_SESSION['auth'];
        }else{
            $auth = D('menu')->getAuthUrl();
            $_SESSION['auth'] = $auth;
            return $auth;
        }
    }

    //路由白名单
    private function urlWhiteList(){
        return [
            'Admin/Public/permission',  //401页面
            'Admin/Public/login',  //登录
            'Admin/Public/logout',  //登出
            'Admin/Index/index',  //入口
            'Admin/Index/test',  //入口(测试)
            'Admin/Index/indexO',
        ];
    }
}