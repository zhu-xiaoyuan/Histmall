<?php
// +----------------------------------------------------------------------
// | 手机端基础类
// +----------------------------------------------------------------------
namespace Admin\Controller;

class AppController extends BaseController
{
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
                $this->redirect('Admin/Submit/showsubmit');
            } else if ($employee) {
                self::$CMS['uid'] = $_SESSION['CMS']['uid'] = $employee['id'];
                self::$CMS['user'] = $_SESSION['CMS']['user'] = $employee;
                self::$CMS['homeurl'] = $_SESSION['CMS']['homeurl'] = U('Admin/Index/index');
                self::$CMS['backurl'] = $_SESSION['CMS']['backurl'] = FALSE;
                $this->redirect('Admin/App/applogin');
            } else {
                $this->error('用户不存在，或密码错误！');
            }
        }
        if ($_SESSION['CMS']['uid']) {
            $this->redirect('Admin/Submit/showsubmit');
        }
        $this->display();
    }
}