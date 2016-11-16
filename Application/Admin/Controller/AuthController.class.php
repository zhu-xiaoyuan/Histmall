<?php

namespace Admin\Controller;
/**
 * 权限管理
 */

class AuthController extends BaseController{

    //角色列表页
    public function role(){
        $search = I('get.name','','htmlspecialchars');

        //设置面包导航，主加载器请配置
        $bread = array(
            '0' => array('name' => '权限管理'),
            '1' => array('name' => '角色管理'),
        );
        $breadhtml = $this->getBread($bread);
        $this->assign('breadhtml', $breadhtml);

        $p = $_GET['p'] ? $_GET['p'] : 1;
        $psize = self::$CMS['set']['pagesize'] ? self::$CMS['set']['pagesize'] : 20;

        $model = M('role');

        if($search){
            $roles = $model->where('name like "%'.$search.'%"')->page($p,$psize)->select();
            $count = $model->where('name like "%'.$search.'%"')->count();
        }else{
            $roles = $model->page($p,$psize)->select();
            $count = $model->count();
        }

        $this->getPage($count, $psize, 'App-loader', '商品管理', 'App-search');

        $this->assign('roles',$roles);
        $this->display();
    }

    //添加、编辑角色页面
    public function addRole(){
        $id = I('get.id/d');
        //设置面包导航，主加载器请配置
        $bread = array(
            '0' => array('name' => '权限管理'),
            '1' => array('name' => '角色管理'),
        );
        $breadhtml = $this->getBread($bread);
        $this->assign('breadhtml', $breadhtml);

        //权限（菜单）列表
        if($id){    //编辑
            $role = M('role')->where('id='.$id)->find();
            $menus = M('role_menu')->field('menu_id')->where('role_id='.$id)->select();

            foreach($menus as $v){
                $menu_ids[] = $v['menu_id'];
            }

            $this->assign('role',$role);
            $this->assign('menu_ids',$menu_ids);
        }

        $menus = D('menu')->getMenu();
        $this->assign('menus',$menus);
        $this->display();
    }

    //保存角色信息
    public function addRoleSave(){
        $id = I('post.id/d');
        $name = I('post.name','','htmlspecialchars');
        $menus = I('post.menu');
        if(!$name && !$menus){
            $this->ajaxReturn(['status'=>0,'msg'=>'填写信息不完整，请检查后重新提交']);
        }

        $role_model = M('role');
        $role_model->startTrans();

        if($id){    //编辑
            $data['name'] = $name;
            $data['updator_id'] = $_SESSION['CMS']['uid'];
            $data['update_time'] = time();
            //更新role表
            $res1 = $role_model->where('id='.$id)->save($data);
            unset($data);
            //更新role_menu表
            M('role_menu')->where('role_id='.$id)->delete();
            foreach($menus as $menu){
                $data[] = ['role_id'=>$id,'menu_id'=>intval($menu)];
            }
            $res2 = M('role_menu')->addAll($data);

        }else{  //新增
            $data['name'] = $name;
            $data['creator_id'] = $_SESSION['CMS']['uid'];
            $data['create_time'] = time();
            $res1 = $role_model->add($data);    //$res1即为新增id
            unset($data);
            foreach($menus as $menu){
                $data[] = ['role_id'=>$res1,'menu_id'=>intval($menu)];
            }
            $res2 = M('role_menu')->addAll($data);
        }

        if($res1 && $res2){
            $role_model->commit();
            $this->ajaxReturn(['status'=>1,'msg'=>'添加成功']);
        }else{
            $role_model->rollback();
            $this->ajaxReturn(['status'=>0,'msg'=>'添加失败，请重试']);
        }
    }

    //禁用角色
    public function roleDisabled(){
        $id = I('get.id/d');
        $res = M('role')->where('id='.$id)->save(['status'=>0]);
        if($res){
            $this->ajaxReturn(['status'=>1,'msg'=>'禁用成功']);
        }
        $this->ajaxReturn(['status'=>0,'msg'=>'禁用失败']);
    }

    //删除角色(包含/兼容 批量删除)
    public function roleDelete(){
        $id_string = I('get.id');
        $id_string = trim($id_string,',');

        $map1['id'] = ['in',$id_string];
        $res = M('role')->where($map1)->delete();

        if($res){
            $map2['role_id'] = ['in',$id_string];
            //删除觉得对应的权限
            M('role_menu')->where($map2)->delete();
            $this->ajaxReturn(['status'=>1,'msg'=>'删除成功']);
        }
        $this->ajaxReturn(['status'=>0,'msg'=>'删除失败']);
    }
}