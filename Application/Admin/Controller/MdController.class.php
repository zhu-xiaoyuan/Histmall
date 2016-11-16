<?php
// +----------------------------------------------------------------------
// | 用户后台基础类--CMS分组门店管理类
// +----------------------------------------------------------------------
namespace Admin\Controller;

class MdController extends BaseController
{

    public function _initialize()
    {
        //你可以在此覆盖父类方法
        parent::_initialize();
    }

    //CMS后台门店管理引导页
    public function index()
    {
        //设置面包导航，主加载器请配置
        $bread = array(
            '0' => array(
                'name' => '门店管理',
                'url' => U('Admin/Md/index'),
            ),
        );
        $this->display();
    }

    //CMS后台门店分组
    public function group()
    {
        //设置面包导航，主加载器请配置
        $bread = array(
            '0' => array(
                'name' => '门店管理',
                'url' => U('Admin/Md/index'),
            ),
            '1' => array(
                'name' => '门店分组',
                'url' => U('Admin/Md/group'),
            ),
        );
        $this->assign('breadhtml', $this->getBread($bread));
        //绑定搜索条件与分页
        $m = M('Md_group');
        $p = $_GET['p'] ? $_GET['p'] : 1;
        $name = I('name') ? I('name') : '';
        if ($name) {
            $map['name'] = array('like', "%$name%");
            $this->assign('name', $name);
        }
        $psize = self::$CMS['set']['pagesize'] ? self::$CMS['set']['pagesize'] : 20;
        $cache = $m->where($map)->page($p, $psize)->select();
        $count = $m->where($map)->count();
        $this->getPage($count, $psize, 'App-loader', '门店分组', 'App-search');
        $this->assign('cache', $cache);
        $this->display();
    }

    //CMS后台分组设置
    public function groupSet()
    {
        $id = I('id');
        $m = M('Md_group');
        //设置面包导航，主加载器请配置
        $bread = array(
            '0' => array(
                'name' => '门店管理',
                'url' => U('Admin/Md/index'),
            ),
            '1' => array(
                'name' => '门店分组',
                'url' => U('Admin/Md/group'),
            ),
            '2' => array(
                'name' => '分组设置',
                'url' => $id ? U('Admin/Md/groupSet', array('id' => $id)) : U('Admin/Md/groupSet'),
            ),
        );
        $this->assign('breadhtml', $this->getBread($bread));
        //处理POST提交
        if (IS_POST) {
            //die('aa');
            //$data = I('post.');
            $data['name']=I('name','');
            $data['summary']=I('summary','');

            if ($id) {
                $data['id']=I('id',0,'int');
                $re = $m->save($data);
                if (FALSE !== $re) {
                    $info['status'] = 1;
                    $info['msg'] = '设置成功！';
                } else {
                    $info['status'] = 0;
                    $info['msg'] = '设置失败！';
                }
            } else {
                $re = $m->add($data);
                if ($re) {
                    $info['status'] = 1;
                    $info['msg'] = '设置成功！';
                } else {
                    $info['status'] = 0;
                    $info['msg'] = '设置失败！';
                }
            }
            $this->ajaxReturn($info);
        }
        //处理编辑界面
        if ($id) {
            $cache = $m->where('id=' . $id)->find();
            $this->assign('cache', $cache);
        }
        $this->display();
    }

    public function groupDel()
    {
        $id = $_GET['id']; //必须使用get方法
        $m = M('Md_group');
        if (!$id) {
            $info['status'] = 0;
            $info['msg'] = 'ID不能为空!';
            $this->ajaxReturn($info);
        }
        $re = $m->delete($id);
        if ($re) {
            $info['status'] = 1;
            $info['msg'] = '删除成功!';
        } else {
            $info['status'] = 0;
            $info['msg'] = '删除失败!';
        }
        $this->ajaxReturn($info);
    }

    //CMS后台门店分组
    public function md()
    {
        //设置面包导航，主加载器请配置
        $bread = array(
            '0' => array(
                'name' => '门店管理',
                'url' => U('Admin/Md/index'),
            ),
            '1' => array(
                'name' => '门店列表',
                'url' => U('Admin/Md/md'),
            ),
        );
        $this->assign('breadhtml', $this->getBread($bread));
        //绑定搜索条件与分页
        $m = M('Md');
        $p = $_GET['p'] ? $_GET['p'] : 1;
        $name = I('name') ? I('name') : '';
        if ($name) {
            $map['name'] = array('like', "%$name%");
            $this->assign('name', $name);
        }
        $psize = self::$CMS['set']['pagesize'] ? self::$CMS['set']['pagesize'] : 20;
        $cache = $m->where($map)->page($p, $psize)->select();
        $count = $m->where($map)->count();
        $this->getPage($count, $psize, 'App-loader', '门店分组', 'App-search');
        $this->assign('cache', $cache);
        $this->display();
    }

    //CMS后台门店设置
    public function mdSet()
    {
        $id = I('id');
        $m = M('Md');
        //设置面包导航，主加载器请配置
        $bread = array(
            '0' => array(
                'name' => '门店管理',
                'url' => U('Admin/Md/index'),
            ),
            '1' => array(
                'name' => '门店列表',
                'url' => U('Admin/Md/md'),
            ),
            '2' => array(
                'name' => '门店设置',
                'url' => $id ? U('Admin/Md/mdSet', array('id' => $id)) : U('Admin/Md/mdSet'),
            ),
        );
        $this->assign('breadhtml', $this->getBread($bread));
        //处理POST提交
        if (IS_POST) {
            //die('aa');
            $data = I('post.');
            if ($id) {
                $re = $m->save($data);
                if (FALSE !== $re) {
                    $info['status'] = 1;
                    $info['msg'] = '设置成功！';
                } else {
                    $info['status'] = 0;
                    $info['msg'] = '设置失败！';
                }
            } else {
                $re = $m->add($data);
                if ($re) {
                    $info['status'] = 1;
                    $info['msg'] = '设置成功！';
                } else {
                    $info['status'] = 0;
                    $info['msg'] = '设置失败！';
                }
            }
            $this->ajaxReturn($info);
        }
        //处理门店分组
        $group = M('Md_group')->select();
        $this->assign('group', $group);
        //处理编辑界面
        if ($id) {
            $cache = $m->where('id=' . $id)->find();
            $this->assign('cache', $cache);
        }
        $this->display();
    }

    public function mdDel()
    {
        $id = $_GET['id']; //必须使用get方法
        $m = M('Md');
        if (!$id) {
            $info['status'] = 0;
            $info['msg'] = 'ID不能为空!';
            $this->ajaxReturn($info);
        }
        $re = $m->delete($id);
        if ($re) {
            $info['status'] = 1;
            $info['msg'] = '删除成功!';
        } else {
            $info['status'] = 0;
            $info['msg'] = '删除失败!';
        }
        $this->ajaxReturn($info);
    }

}