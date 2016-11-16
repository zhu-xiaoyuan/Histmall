<?php
//
namespace Admin\Controller;

class UploadController extends BaseController
{
    public function index()
    {
        $this->display();
    }

    public function indeximg()
    {
        //查找带回字段
        $fbid = I('fbid');
        $isall = I('isall');
        $this->assign('fbid', $fbid);
        $this->assign('isall', $isall);
        $page = '1,8';
        $m = M('Upload_img');
        $cache = $m->page($page)->order('id desc')->select();
        $this->assign('cache', $cache);
        $this->ajaxReturn($this->fetch());
    }

    public function doupimg()
    {

//        $config = array(
//            'mimes' => array(), //允许上传的文件MiMe类型
//            'maxSize' => 0, //上传的文件大小限制 (0-不做限制)
//            'exts' => array('jpg', 'gif', 'png', 'jpeg'), //允许上传的文件后缀
//            'autoSub' => true, //自动子目录保存文件
//            'subName' => array('date', 'Ymd'), //子目录创建方式，[0]-函数名，[1]-参数，多个参数使用数组
//            'rootPath' => './Upload/', //保存根路径
//            'savePath' => 'img/', //保存路径
//            'saveName' => array('uniqid', ''), //上传文件命名规则，[0]-函数名，[1]-参数，多个参数使用数组
//            'saveExt' => '', //文件保存后缀，空则使用原后缀
//            'replace' => false, //存在同名是否覆盖
//            'hash' => true, //是否生成hash编码
//            'callback' => false, //检测文件是否存在回调，如果存在返回文件信息数组
//            'driver' => '', // 文件上传驱动
//            'driverConfig' => array(), // 上传驱动配置
//        );
        $qiniuConfig = self::$SYS['set'];
        $config = array(
            'maxSize' => 0,//文件大小
            'replace' => false, //存在同名是否覆盖
            'hash' => true, //是否生成hash编码
            'exts' => array('jpg', 'gif', 'png', 'jpeg'),
            'autoSub' => false,
            'subName' => array('date', 'Ymd'), //子目录创建方式，[0]-函数名，[1]-参数，多个参数使用数组
            'saveName' => array('uniqid', ''),
            'driver' => 'Qiniu',
            'driverConfig' => array(
                'secretKey' => $qiniuConfig['qiniu_ak'],
                'accessKey' => $qiniuConfig['qiniu_sk'],
                'domain' => $qiniuConfig['qiniu_domain'],
                'bucket' => $qiniuConfig['qiniu_bucket'],
            )
        );
        //var_dump($_FILES);
        $up = new \Util\Upload($config);
        if ($list = $up->upload($_FILES)) {
            $pic = M('Upload_img');
            $count = 0;
            $arr = array();
            foreach ($list as $k => $v) {
                //$arr['uid']=$uid;
                $size = getimagesize($_FILES['appfile']['tmp_name'][0]);
                $arr['width'] = $size[0];
                $arr['height'] = $size[1];
                $arr['name'] = $_FILES['appfile']['name'][$k];
                $arr['ext'] = $list[$k]['ext'];
                $arr['type'] = 'img';
                $arr['savename'] = $list[$k]['savename'];
                $arr['savepath'] = $qiniuConfig['qiniu_domain'];
                $re = $pic->add($arr);
                if ($re) {
                    $count += 1;
                }
            }

            if ($count) {
                $backstr = "'" . $count . "张图片上传成功！'" . ',' . "true";
                echo "<script>parent.doupimgcallback(" . $backstr . ")</script>";
            } else {
                echo "<script>parent.doupimgcallback('图片保存时失败！',false)</script>";
            };

        } else {
            echo "<script>parent.doupimgcallback('" . $up->getError() . "',false)</script>";
        };

    }

    public function delimgs()
    {
        if (IS_POST) {
            $m = M('Upload_img');
            $ids = I('ids');
            $list = $m->where(array('id'=>array('in',$ids)))->delete();
            if ($list == true) {
                $data['status'] = 1;
                $data['msg'] = '成功删除' . $list . '张图片！';
            } else {
                $data['status'] = 0;
                $data['msg'] = '删除失败，请重试或联系管理员！';
            }
            $this->ajaxReturn($data);
        } else {
            $this->error('系统错误请重试！');
        }
    }

    public function getmoreimg()
    {
        $page = I('p') . ',8';
        $m = M('Upload_img');
        $cache = $m->page($page)->order('id desc')->select();
        if ($cache) {
            $this->assign('cache', $cache);
            $this->ajaxReturn($this->fetch());//封装模板fetch并返回
        } else {
            $this->ajaxReturn("");
        }

    }

}