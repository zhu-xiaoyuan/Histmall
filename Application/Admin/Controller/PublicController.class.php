<?php
// +----------------------------------------------------------------------
// | 用户后台基础类--CMS分组PUBLIC公共类
// +----------------------------------------------------------------------
namespace Admin\Controller;

class PublicController extends BaseController
{

    //默认跳转至登陆页面
    public function index()
    {
        $this->redirect('Admin/Public/applogin');
    }

    //通用注册页面
    public function reg()
    {
        $this->display();
    }

    //手机登陆页面
    public function applogin(){
        // echo "string"; 
        if (IS_POST) {
            $data = I('post.');
            $verify = new \Think\Verify();
            if (!$verify->check($data['verify'])) {
                $this->error('请正确填写验证码！');
            }
            $user = M('User')->where(array('username' => $data['username'], 'userpass' => md5($data['userpass'])))->find();
            $employee = M('employee')->where(array('username' => $data['username'], 'userpass' => md5($data['userpass'])))->find();
            if ($user) {
                self::$CMS['uid'] = $_SESSION['CMS']['uid'] = $user['id'];
                self::$CMS['user'] = $_SESSION['CMS']['user'] = $user;
                self::$CMS['homeurl'] = $_SESSION['CMS']['homeurl'] = U('Admin/Index/index');
                self::$CMS['backurl'] = $_SESSION['CMS']['backurl'] = FALSE;
                $this->redirect('Admin/Public/submit');
            } else if ($employee) {
                self::$CMS['uid'] = $_SESSION['CMS']['uid'] = $employee['id'];
                self::$CMS['user'] = $_SESSION['CMS']['user'] = $employee;
                self::$CMS['homeurl'] = $_SESSION['CMS']['homeurl'] = U('Admin/Index/index');
                self::$CMS['backurl'] = $_SESSION['CMS']['backurl'] = FALSE;
                $this->redirect('Admin/Public/applogin');
            } else {
                $this->error('用户不存在，或密码错误！');
            }
        }
        if ($_SESSION['CMS']['uid']) {
            $this->redirect('Admin/Public/submit');
        }
        $this->display();
    }

    //查询页面
    public function submit(){
        if (IS_POST) {
            $data = I('post.');
            //  
        }
        if (!$_SESSION['CMS']['uid']) {
            $this->redirect('Admin/Public/applogin');
        }
        $this->display();
    }

    //通用登陆页面
    public function login()
    {
        if (IS_POST) {
            $data = I('post.');
            $verify = new \Think\Verify();
            if (!$verify->check($data['verify'])) {
                $this->error('请正确填写验证码！');
            }
            $user = M('User')->where(array('username' => $data['username'], 'userpass' => md5($data['userpass'])))->find();
            $employee = M('employee')->where(array('username' => $data['username'], 'userpass' => md5($data['userpass'])))->find();
            if ($user) {
                self::$CMS['uid'] = $_SESSION['CMS']['uid'] = $user['id'];
                self::$CMS['user'] = $_SESSION['CMS']['user'] = $user;
                self::$CMS['homeurl'] = $_SESSION['CMS']['homeurl'] = U('Admin/Index/index');
                self::$CMS['backurl'] = $_SESSION['CMS']['backurl'] = FALSE;
                $this->redirect('Admin/Index/index');
            } else if ($employee) {
                self::$CMS['uid'] = $_SESSION['CMS']['uid'] = $employee['id'];
                self::$CMS['user'] = $_SESSION['CMS']['user'] = $employee;
                self::$CMS['homeurl'] = $_SESSION['CMS']['homeurl'] = U('Admin/Index/index');
                self::$CMS['backurl'] = $_SESSION['CMS']['backurl'] = FALSE;
                $this->redirect('Admin/Index/index');
            } else {
                $this->error('用户不存在，或密码错误！');
            }
        }
        if ($_SESSION['CMS']['uid']) {
            $this->redirect('Admin/Index/index');
        }
        $arr = array(4, 5, 7, 7, 7, 10, 11, 12);
        $get = $arr[mt_rand(0, count($arr) - 1)];
        $wallpaper = ROOT_URL . "Public/WallPage/" . $get . ".jpg";
        $this->assign('wallpaper', $wallpaper);
        $this->display();
    }

    public function logout()
    {
        session(null);
        $this->redirect('Admin/Public/login');
    }

    //通用验证码
    public function verify()
    {
        $Verify = new \Think\Verify();
        $Verify->codeSet = '0123456789';
        $Verify->length = 4;
        $Verify->imageH = 0;
        $Verify->entry();
    }

    //百度地图
    public function baiduDitu()
    {
        $map['address'] = I('address');
        $map['lng'] = I('lng');
        $map['lat'] = I('lat');
        $this->assign('map', $map);
        $mb = $this->fetch();
        $this->ajaxReturn($mb);
    }

    //没有权限
    public function permission()
    {
//        session(null);
        $this->display();
    }

    public function expires()
    {
        $this->display();
    }


}