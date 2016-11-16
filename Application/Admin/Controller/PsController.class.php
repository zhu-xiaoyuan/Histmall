<?php
namespace Admin\Controller;

class PsController extends BaseController
{

    public function _initialize()
    {
        //你可以在此覆盖父类方法
        parent::_initialize();
    }

    //CMS后台配送管理引导页
    public function index()
    {
        //设置面包导航，主加载器请配置
        $bread = array(
            '0' => array(
                'name' => '配送管理',
                'url' => U('Admin/Ps/index')
            )
        );
        $this->display();
    }


    //CMS后台配送站管理
    public function group()
    {
        //设置面包导航，主加载器请配置
        $bread = array(
            '0' => array(
                'name' => '配送管理',
                'url' => U('Admin/Ps/index')
            ),
            '1' => array(
                'name' => '配送站列表',
                'url' => U('Admin/Ps/group')
            )
        );
        $this->assign('breadhtml', $this->getBread($bread));
        //绑定搜索条件与分页
        $m = M('xq_group');
        $p = $_GET['p'] ? $_GET['p'] : 1;
        $name = I('name') ? I('name') : '';
        if ($name) {
            $map['name'] = array('like', "%$name%");
            $this->assign('name', $name);
        }
        $psize = self::$CMS['set']['pagesize'] ? self::$CMS['set']['pagesize'] : 20;
        $cache = $m->where($map)->page($p, $psize)->select();
        $count = $m->where($map)->count();
        $this->getPage($count, $psize, 'App-loader', '配送站管理', 'App-search');
        $this->assign('cache', $cache);
        $this->display();
    }

    //CMS后台分组设置
    public function groupSet()
    {
        $id = I('id');
        $m = M('xq_group');
        //设置面包导航，主加载器请配置
        $bread = array(
            '0' => array(
                'name' => '配送管理',
                'url' => U('Admin/Ps/index')
            ),
            '1' => array(
                'name' => '配送站列表',
                'url' => U('Admin/Ps/group')
            ),
            '2' => array(
                'name' => '配送站设置',
                'url' => $id ? U('Admin/Ps/groupSet', array('id' => $id)) : U('Admin/Ps/groupSet')
            )
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
        //处理编辑界面
        if ($id) {
            $cache = $m->where('id=' . $id)->find();
            $this->assign('cache', $cache);
        }
        $this->display();
    }

    public function groupDel()
    {
        $id = $_GET['id'];//必须使用get方法
        $m = M('xq_group');
        if (!id) {
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

    //CMS后台配送站管理
    public function xq()
    {
        //设置面包导航，主加载器请配置
        $bread = array(
            '0' => array(
                'name' => '配送管理',
                'url' => U('Admin/Ps/index')
            ),
            '1' => array(
                'name' => '小区列表',
                'url' => U('Admin/Ps/xq')
            )
        );
        $this->assign('breadhtml', $this->getBread($bread));
        //绑定搜索条件与分页
        $m = M('xq');
        $p = $_GET['p'] ? $_GET['p'] : 1;
        $name = I('name') ? I('name') : '';
        if ($name) {
            $groupid = '0';
            $group = M('xq_group')->where("name like '%" . $name . "%'")->select();
            for ($i = 0; $i < count($group); $i++) {
                $groupid = $groupid . "," . $group[$i]['id'];
            }

            $map['name'] = array('like', "%$name%");
            $map['groupid'] = array('in', in_parse_str($groupid));
            $map['_logic'] = 'OR';
            $this->assign('name', $name);
        }
        $psize = self::$CMS['set']['pagesize'] ? self::$CMS['set']['pagesize'] : 20;
        $cache = $m->where($map)->page($p, $psize)->select();
        foreach ($cache as $k => $v) {
            $cache[$k]['groupname'] = M('xq_group')->where('id=' . $v['groupid'])->getField('name');
        }
        $count = $m->where($map)->count();
        $this->getPage($count, $psize, 'App-loader', '配送站管理', 'App-search');
        $this->assign('cache', $cache);
        $this->display();
    }

    //CMS后台小区设置
    public function xqSet()
    {
        $id = I('id');
        $m = M('xq');
        //设置面包导航，主加载器请配置
        $bread = array(
            '0' => array(
                'name' => '配送管理',
                'url' => U('Admin/Ps/index')
            ),
            '1' => array(
                'name' => '小区列表',
                'url' => U('Admin/Ps/xq')
            ),
            '2' => array(
                'name' => '小区设置',
                'url' => $id ? U('Admin/Ps/xqSet', array('id' => $id)) : U('Admin/Ps/xqSet')
            )
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
        //处理配送站管理
        $group = M('xq_group')->select();
        $this->assign('group', $group);
        //处理编辑界面
        if ($id) {
            $cache = $m->where('id=' . $id)->find();
            $this->assign('cache', $cache);
        }
        $this->display();
    }

    public function xqDel()
    {
        $id = $_GET['id'];//必须使用get方法
        $m = M('xq');
        if (!id) {
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