<?php
// +----------------------------------------------------------------------
// | 手机端提交类
// +----------------------------------------------------------------------
namespace Admin\Controller;

class SubmitController extends BaseController
{
	//查询页面
    public function submit(){
        if (IS_POST) {
            $data = I('post.');
            //  
        }
        if (!$_SESSION['CMS']['uid']) {
            $this->redirect('Admin/App/applogin');
        }
        $this->display();
    }
}