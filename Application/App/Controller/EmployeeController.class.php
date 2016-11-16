<?php
// 员工管理的Controller App模块
namespace App\Controller;

class EmployeeController extends BaseController
{
    public function _initialize()
    {
        //你可以在此覆盖父类方法
        parent::_initialize();
    }

    public function index()
    {
        echo "非法操作";
        exit();
    }


    // 绑定员工和会员
    public function bindVip()
    {
        $emp = I('employee');
        $eid = I('eid');
        $employee = M('employee')->where(array('userpass' => $emp, 'id' => $eid))->find();
        $vip = M('vip')->where(array('openid' => $_SESSION['sqopenid']))->find();

        if (!$employee) {
            $this->redirect('App/Employee/index');
        } else if (!$vip) {
            echo "用户信息不存在";
            exit();
        }

        $temp = M('employee')->where(array('vipid' => $vip['id']))->find();

        if ($temp) {
            $this->assign('img', __ROOT__."/Public/App/img/binded.jpg");
            // echo "该账号已绑定，无法再进行绑定操作！请先到管理员处解除绑定再重新绑定";
            // exit();
        } else {
            $employee['vipid'] = $vip['id'];
            $re = M('employee')->save($employee);
            if ($re) {
                $this->assign('img', __ROOT__."/Public/App/img/bindsuccess.jpg");
                // echo "绑定成功";
                // exit();
            } else {
                $this->assign('img', __ROOT__."/Public/App/img/bindfailure.jpg");
                // echo "绑定失败";
                // exit();
            }
        }
        $this->display();
    }

}
