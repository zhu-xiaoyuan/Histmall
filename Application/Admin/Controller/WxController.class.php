<?php
// +----------------------------------------------------------------------
// | 用户后台基础类--CMS分组魔法关键词类
// +----------------------------------------------------------------------
namespace Admin\Controller;

class WxController extends BaseController
{

    public function _initialize()
    {
        //你可以在此覆盖父类方法
        parent::_initialize();
    }

    //CMS后台魔法关键词引导页
    public function index()
    {
        //设置面包导航，主加载器请配置
        $bread = array(
            '0' => array(
                'name' => '微信管理',
                'url' => U('Admin/Wx/index')
            )
        );
        $this->display();
    }

    //CMS后台微信设置
    public function set()
    {
        $m = M('Set');
        //设置面包导航，主加载器请配置
        $bread = array(
            '0' => array(
                'name' => '微信管理',
                'url' => U('Admin/Wx/index')
            ),
            '1' => array(
                'name' => '微信设置',
                'url' => U('Admin/Wx/set')
            )
        );
        $this->assign('breadhtml', $this->getBread($bread));
        //处理POST提交
        if (IS_POST) {
            //die('aa');
            $data = I('post.');
            $old = $m->find();
            if ($old) {
                $re = $m->save($data);
                if (FALSE !== $re) {
                    $info['status'] = 1;
                    $info['msg'] = '设置成功！';
                } else {
                    $info['status'] = 0;
                    $info['msg'] = '设置失败！';
                }
            } else {
                $info['status'] = 0;
                $info['msg'] = '设置失败！系统配置表不存在！';
            }
            $this->ajaxReturn($info);
        }
        $cache = $m->find();
        $this->assign('cache', $cache);
        $this->display();
    }


    //CMS后台关键词分组
    public function keyword()
    {
        //设置面包导航，主加载器请配置
        $bread = array(
            '0' => array(
                'name' => '魔法关键词',
                'url' => U('Admin/Wx/index')
            ),
            '1' => array(
                'name' => '关键词列表',
                'url' => U('Admin/Wx/Keyword')
            )
        );
        $this->assign('breadhtml', $this->getBread($bread));
        //绑定搜索条件与分页
        $m = M('Wx_keyword');
        $p = $_GET['p'] ? $_GET['p'] : 1;
        $name = I('name') ? I('name') : '';
        if ($name) {
            $map['name'] = array('like', "%$name%");
            $this->assign('name', $name);
        }
        $psize = self::$CMS['set']['pagesize'] ? self::$CMS['set']['pagesize'] : 20;
        $cache = $m->where($map)->page($p, $psize)->select();
        $count = $m->where($map)->count();
        $this->getPage($count, $psize, 'App-loader', '关键词分组', 'App-search');
        $this->assign('cache', $cache);
        $this->display();
    }

    //CMS后台关键词设置
    public function keywordSet()
    {
        $id = I('id');
        $m = M('Wx_keyword');
        //设置面包导航，主加载器请配置
        $bread = array(
            '0' => array(
                'name' => '魔法关键词',
                'url' => U('Admin/Wx/index')
            ),
            '1' => array(
                'name' => '关键词列表',
                'url' => U('Admin/Wx/keyword')
            ),
            '2' => array(
                'name' => '关键词设置',
                'url' => $id ? U('Admin/Wx/keywordSet', array('id' => $id)) : U('Admin/Wx/keywordSet')
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

    public function keywordDel()
    {
        $id = $_GET['id'];//必须使用get方法
        $m = M('Wx_keyword');
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

    //CMS后台关键词分组
    public function img()
    {
        $kid = I('kid') ? I('kid') : die('缺少KID参数！');
        //绑定keyword
        $keyword = M('Wx_keyword')->where('id=' . $kid)->find();
        $this->assign('keyword', $keyword);
        //设置面包导航，主加载器请配置
        $bread = array(
            '0' => array(
                'name' => '魔法关键词',
                'url' => U('Admin/Wx/keyword')
            ),
            '1' => array(
                'name' => '关键词图文列表',
                'url' => U('Admin/Wx/img', array('kid' => $kid))
            )
        );
        $this->assign('breadhtml', $this->getBread($bread));
        //绑定搜索条件与分页
        $m = M('Wx_keyword_img');
        $p = $_GET['p'] ? $_GET['p'] : 1;
        $name = I('name') ? I('name') : '';
        if ($name) {
            $map['name'] = array('like', "%$name%");
            $this->assign('name', $name);
        }
        $map['kid'] = $kid;
        $psize = self::$CMS['set']['pagesize'] ? self::$CMS['set']['pagesize'] : 20;
        $cache = $m->where($map)->page($p, $psize)->select();
        $count = $m->where($map)->count();
        $this->getPage($count, $psize, 'App-loader', '关键词图文列表', 'App-search');
        $this->assign('cache', $cache);
        $this->display();
    }

    //CMS后台关键词设置
    public function imgSet()
    {
        $kid = I('kid');
        $id = I('id');
        $m = M('Wx_keyword_img');
        //设置面包导航，主加载器请配置
        $bread = array(
            '0' => array(
                'name' => '魔法关键词',
                'url' => U('Admin/Wx/keyword')
            ),
            '1' => array(
                'name' => '关键词图文列表',
                'url' => U('Admin/Wx/img', array('kid' => $kid))
            ),
            '2' => array(
                'name' => '关键词图文设置',
                'url' => $id ? U('Admin/Wx/imgSet', array('id' => $id)) : U('Admin/Wx/imgSet')
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
        //绑定keyword
        $keyword = M('Wx_keyword')->where('id=' . $kid)->find();
        $this->assign('keyword', $keyword);
        //处理编辑界面
        if ($id) {
            $cache = $m->where('id=' . $id)->find();
            $this->assign('cache', $cache);
        }
        $this->display();
    }

    public function imgDel()
    {
        $id = $_GET['id'];//必须使用get方法
        $m = M('Wx_keyword_img');
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

    // 微信端推广二维码背景设置
    public function qrcodeBgSet()
    {
        //设置面包导航，主加载器请配置
        $bread = array(
            '0' => array(
                'name' => '二维码背景设置',
            ),
        );
        $this->assign('breadhtml', $this->getBread($bread));
        if (IS_POST) {
            $name = 'QRcode/background/bg_' . time() . '.jpg';
            if ($_FILES['qrcode']['error'] > 0) {
                echo $name;
                echo "<script>parent.replaceFuck();</script>";
            } else {
                if (move_uploaded_file($_FILES['qrcode']['tmp_name'], $name)) {
                    M('autoset')->save(array('id' => 1, 'qrcode_background' => $name));
                    echo "<script>parent.replaceok();</script>";
                } else {
                    echo $name . "asd";
                    echo "<script>parent.replaceFuck();</script>";
                }
            }
            exit();
        }
        $autoset = M('autoset')->find();
        if (!$autoset) {
            echo "系统未配置";
        }
        $this->assign('img', __ROOT__ . '/' . $autoset['qrcode_background']);
        $this->display();
    }


    // 微信端推广二维码背景设置
    public function qrcodeBgEmpSet()
    {
        //设置面包导航，主加载器请配置
        $bread = array(
            '0' => array(
                'name' => '员工二维码背景设置',
            ),
        );
        $this->assign('breadhtml', $this->getBread($bread));
        if (IS_POST) {
            $name = 'QRcode/background/bg_' . time() . '.jpg';
            if ($_FILES['qrcode']['error'] > 0) {
                echo $name;
                echo "<script>parent.replaceFuck();</script>";
            } else {
                if (move_uploaded_file($_FILES['qrcode']['tmp_name'], $name)) {
                    M('autoset')->save(array('id' => 1, 'qrcode_emp_background' => $name));
                    echo "<script>parent.replaceok();</script>";
                } else {
                    echo $name . "asd";
                    echo "<script>parent.replaceFuck();</script>";
                }
            }
            exit();
        }
        $autoset = M('autoset')->find();
        if (!$autoset) {
            echo "系统未配置";
        }
        $this->assign('img', __ROOT__ . '/' . $autoset['qrcode_emp_background']);
        $this->display();
    }

    // Admin后台微信自定义菜单设置
    public function menu()
    {
        //设置面包导航，主加载器请配置
        $bread = array(
            '0' => array(
                'name' => '自定义菜单',
                'url' => U('Admin/Wx/menu')
            ),
            '1' => array(
                'name' => '菜单详情'
            )
        );

        // 生成Wechat对象
        $config['appid'] = self::$SYS['set']['wxappid'];
        $config['appsecret'] = self::$SYS['set']['wxappsecret'];
        $config['token'] = self::$SYS['set']['wxtoken'];
        $wechat = new \Util\Wx\Wechat($config);
        // 提交包括两种，一是删除，一是上传，两种执行完毕后可以继续运行
        if (IS_POST) {
            if ($_POST['do'] == 'remove') {
                // 删除菜单
                $re = $wechat->deleteMenu();
                if ($re) {
                    // 删除成功
                    echo "<script>parent.replaceok('删除成功！');</script>";
                } else {
                    // 删除失败
                    echo "<script>parent.replaceFuck('删除失败！');</script>";
                }
            } else {
                $menu = urldecode($_POST['do']);
                $menu = json_decode($menu, true);
                $button['button'] = $menu;
                $re = $wechat->createMenu($button);
                if ($re) {
                    echo "<script>parent.replaceok('更新菜单成功！');</script>";
                } else {
                    echo "<script>parent.replaceFuck('更新菜单失败！');</script>";
                }
            }
            exit();
        }
        $this->assign('breadhtml', $this->getBread($bread));
        // 获取Menu
        $menu = $wechat->getMenu();
        $this->assign('menu', $menu['menu']);
        $this->display();
    }

    // 自定义客服消息
    public function customerSet()
    {
        if (IS_POST) {
            $mcustomer = M('Wx_customer');
            // 获取那个ShortID
            $id = I('id');
            $value = I('value');
            $customer = $mcustomer->where(array('id' => $id))->find();
            if (!$customer) {
                $info['status'] = 0;
                $info['msg'] = '数据缺失';
                $this->ajaxReturn($info);
            }
            // 获取templateID
            $customer['value'] = $value;
            $re = $mcustomer->save($customer);
            if ($re) {
                $info['status'] = 1;
                $info['msg'] = '更新成功';
                // 更新数据库
            } else {
                $info['status'] = 0;
                $info['msg'] = '更新失败';
            }
            $this->ajaxReturn($info);
        }
    }

    // 自定义客服接口
    public function customer()
    {
        $bread = array(
            '0' => array(
                'name' => '客服消息',
                'url' => U('Admin/Wx/template')
            ),
            '1' => array(
                'name' => '客服消息列表'
            )
        );
        $this->assign('breadhtml', $this->getBread($bread));
        $cache = M('Wx_customer')->select();
        $this->assign('cache', $cache);
        $this->display();
    }

    // 自定义模版消息
    public function templateRemoteSet()
    {
        if (IS_POST) {
            $mtemplate = M('Wx_template');
            // 获取那个ShortID
            $shortid = I('shortid');
            $template = $mtemplate->where(array('templateidshort' => $shortid))->find();
            if (!$template) {
                $info['shortid'] = $shortid;
                $info['status'] = 0;
                $info['msg'] = '数据缺失';
                $this->ajaxReturn($info);
            }
            // 获取templateID
            $options['appid'] = self::$SYS['set']['wxappid'];
            $options['appsecret'] = self::$SYS['set']['wxappsecret'];
            $options['debug'] = true;
            $options['logcallback'] = 'wx_log_write';
            $wx = new \Util\Wx\Wechat($options);
            $re = $wx->addTemplateMessage($shortid);
            if ($re) {
                $template['templateid'] = $re;
                $mtemplate->save($template);
                $info['shortid'] = $shortid;
                $info['templateid'] = $re;
                $info['status'] = 1;
                $info['msg'] = '更新成功，不需要再次更新';
                // 更新数据库
            } else {
                $info['shortid'] = $shortid;
                $info['status'] = 0;
                $info['msg'] = '更新失败';
            }
            $this->ajaxReturn($info);
        }
    }

    // 自定义模版消息
    public function templateSet()
    {
        if (IS_POST) {
            $mtemplate = M('Wx_template');
            // 获取那个ShortID
            $shortid = I('shortid');
            $templateid = I('templateid');
            $template = $mtemplate->where(array('templateidshort' => $shortid))->find();
            if (!$template) {
                $info['shortid'] = $shortid;
                $info['status'] = 0;
                $info['msg'] = '数据缺失';
                $this->ajaxReturn($info);
            }
            // 获取templateID
            $template['templateid'] = $templateid;
            $re = $mtemplate->save($template);
            if ($re) {
                $info['status'] = 1;
                $info['msg'] = '更新成功';
                // 更新数据库
            } else {
                $info['status'] = 0;
                $info['msg'] = '更新失败';
            }
            $this->ajaxReturn($info);
        }
    }

    // 自定义模版消息
    public function template()
    {
        $bread = array(
            '0' => array(
                'name' => '模板消息',
                'url' => U('Admin/Wx/template')
            ),
            '1' => array(
                'name' => '模板消息列表'
            )
        );
        $this->assign('breadhtml', $this->getBread($bread));
        $cache = M('Wx_template')->select();
        $this->assign('cache', $cache);
        $this->display();
    }


    /**
     * 设置行业
     * @url https://mp.weixin.qq.com/wiki?t=resource/res_main&id=mp1433751277&token=&lang=zh_CN
     */
    public function setIndustry()
    {
        if (IS_POST) {
            $options['appid'] = self::$SYS['set']['wxappid'];
            $options['appsecret'] = self::$SYS['set']['wxappsecret'];
            $options['debug'] = true;
            $options['logcallback'] = 'wx_log_write';

            $wx = new \Util\Wx\Wechat($options);
            $rlt = $wx->setTMIndustry("1","8");//互联网/电子商务
            if ($rlt) {
                $this->ajaxReturn(['status' => 0, 'msg' => '设置成功']);
            }
        }
        $this->ajaxReturn(['status' => 1, 'msg' => '设置失败，请检查是否已开通此行业']);
    }

}