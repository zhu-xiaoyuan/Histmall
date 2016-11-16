<?php
namespace Admin\Controller;

class UserController extends BaseController
{
    public function _initialize()
    {
        //你可以在此覆盖父类方法
        parent::_initialize();
    }


    public function userList()
    {
        //设置面包导航，主加载器请配置
        $bread = array(
            '0' => array('name' => '管理员中心',),
            '1' => array('name' => '管理员列表',)
        );
        $this->assign('breadhtml', $this->getBread($bread));
        //绑定搜索条件与分页
        $m = M('user');
        $p = $_GET['p'] ? $_GET['p'] : 1;
        $search = I('get.name') ? I('get.name') : '';
        if ($search) {
            $map['username'] = array('like', "%$search%");
            $map['nickname'] = array('like', "%$search%");
            $map['_logic'] = 'or';
            $this->assign('search', $search);
        }
        $psize = self::$CMS['set']['pagesize'] ? self::$CMS['set']['pagesize'] : 20;
        $cache = $m->where($map)->page($p, $psize)->select();
        $roles = M('role')->field('id,name')->select();
        $count = $m->where($map)->count();
        $this->getPage($count, $psize, 'App-loader', '管理员列表', 'App-search');
        $this->assign('cache', $cache);
        $this->assign('roles',$roles);
        $this->display();
    }

    //CMS后台商品设置
    public function userSet()
    {
        $id = I('id');
        $m = M('user');
        //dump($m);
        //设置面包导航，主加载器请配置
        $bread = array(
            '0' => array(
                'name' => '管理员中心',
                'url' => U('Admin/User/#')
            ),
            '1' => array(
                'name' => '管理员列表',
                'url' => U('Admin/User/userList')
            ),
            '1' => array(
                'name' => '管理员编辑',
                'url' => U('Admin/User/userSet', array('id' => $id))
            )
        );
        $this->assign('breadhtml', $this->getBread($bread));
        //处理POST提交
        if (IS_POST) {
            $data['id']=I('id',0,'int');
            $data['nickname']=I('nickname','');
            $info = $this->isUnique($data['nickname'],true);
            if($info['status'] == 0){
                $this->ajaxReturn($info);
                exit;
            }

            $data['username']=I('username','');
            $data['userpass']=I('userpass','');
            $data['role_id']=I('role_id','');
            $data['cctime'] = time();
//            $data['oath']=I('oath','');

            if ($id) {
                if ($data['userpass']) {
                    $data['userpass'] = md5($data['userpass']);
                } else {
                    unset($data['userpass']);
                }
                $re = $m->save($data);
                if (FALSE !== $re) {
                    $info['status'] = 1;
                    $info['msg'] = '设置成功！';
                } else {
                    $info['status'] = 0;
                    $info['msg'] = '设置失败！';
                }
            } else {
                $data['userpass'] = md5($data['userpass']);
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
        $oath = M('User_oath')->where(array('status' => 1))->select();
        $role = M('role')->field('id,name')->select();
        $this->assign('oath', $oath);
        $this->assign('role', $role);
        //处理编辑界面
        if ($id) {
            $cache = $m->where('id=' . $id)->find();
            $this->assign('cache', $cache);
        }
        $this->display();
    }

    /**
     * 管理员用户名是不是唯一的
     */
    public function isUnique($username ,$return = false){
        $username = $username ? $username : htmlspecialchars(trim(I('post.username')));

        $res = M()->query('select 1 from `user` where `username` = "'.$username.'"');
        if(!$return) {
            if ($res) {
                echo json_encode(array('valid' => false));
                exit;
            } else {
                echo json_encode(array('valid' => true));
                exit;
            }
        }else{
            if ($res) {
                return ['status' => 0, 'msg' => '有重复，不可使用'];
            } else {
                return ['status' => 1, 'msg' => '可用'];
            }
        }
    }
    public function userDel()
    {
        $id = $_GET['id'];//必须使用get方法
        $m = M('User');
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