<?php
// +----------------------------------------------------------------------
// | 用户后台基础类--CMS分组商城管理类
// +----------------------------------------------------------------------
namespace Admin\Controller;

class ShopController extends BaseController
{

    public function _initialize()
    {
        //你可以在此覆盖父类方法
        parent::_initialize();
        //初始化两个配置
        self::$CMS['shopset'] = M('Shop_set')->find();
        self::$CMS['vipset'] = M('Vip_set')->find();
    }

    //CMS后台商城管理引导页
    public function index()
    {
        //设置面包导航，主加载器请配置
        $bread = array(
            '0' => array(
                'name' => '商城首页',
                'url' => U('Admin/Shop/index'),
            ),
        );
        $this->display();
    }

    //CMS后台门店设置
    public function set()
    {
        $id = 1;//只有一条记录
        $m = M('Shop_set');
        //设置面包导航，主加载器请配置
        $bread = array(
            '0' => array(
                'name' => '商城管理',
                'url' => U('Admin/Shop/index'),
            ),
            '1' => array(
                'name' => '商城设置',
                'url' => U('Admin/Shop/set'),
            ),
        );
        $this->assign('breadhtml', $this->getBread($bread));
        //处理POST提交
        if (IS_POST) {
            //die('aa');
            $data = I('post.');
            $updateData['name'] = $data['name'];
            $updateData['pic'] = $data['pic'];
            $updateData['summary'] = $data['summary'];
            $updateData['url'] = $data['url'];
            $updateData['thtime'] = $data['thtime'];
            $updateData['fxname'] = $data['fxname'];
            $updateData['yjname'] = $data['yjname'];
            $updateData['tdname'] = $data['tdname'];
            $updateData['fx1name'] = $data['fx1name'];
            $updateData['fx2name'] = $data['fx2name'];
            $updateData['fx3name'] = $data['fx3name'];
            $updateData['isth'] = empty($data['isth']) ? 0 : 1;
            $updateData['isyf'] = empty($data['isyf']) ? 0 : 1;
            $updateData['yf'] = $data['yf'];
            $updateData['yftop'] = $data['yftop'];
            $updateData['settlement_rate'] = ($data['settlement_rate'] < 0 || $data['settlement_rate'] > 10 ? 10 : $data['settlement_rate']);
            $updateData['phone'] = $data['phone'];
            $updateData['address'] = $data['address'];
            $updateData['lng'] = $data['lng'];
            $updateData['lat'] = $data['lat'];
            $updateData['indexalbum'] = $data['indexalbum'];
            $updateData['indexgroup'] = $data['indexgroup'];
            $updateData['indexalbum_shangmeng'] = $data['indexalbum_shangmeng'];
            $updateData['indexgroup_shangmeng'] = $data['indexgroup_shangmeng'];
            $updateData['content'] = $data['content'];
            $updateData['vip_sub_num'] = $data['vip_sub_num'];
            $updateData['vip_discount'] = $data['vip_discount'];

            $old = $m->where('id=' . $id)->find();
            if ($old) {
                $re = $m->where(['id' => $id])->save($data);
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
        $cache = $m->where('id=' . $id)->find();
        $this->assign('cache', $cache);
        $this->display();
    }

    //CMS后台商城分组
    public function goods()
    {
        //设置面包导航，主加载器请配置
        $bread = array(
            '0' => array('name' => '商城首页'),
            '1' => array('name' => '商品管理'),
        );
        $this->assign('breadhtml', $this->getBread($bread));

        //获取店铺名称，供搜索使用
        $stores = M('supplier_store')->field('id,name')->where('status=0')->select();
        $this->assign('stores', $stores);

        //绑定搜索条件与分页
        $m = M('Shop_goods');
        $p = $_GET['p'] ? $_GET['p'] : 1;

        $name = I('name') ? I('name') : '';
        $type = I('type') ? I('type') : '';
        if ($name) {
            $map['name'] = array('like', "%$name%");
            $this->assign('name', $name);
        }
        if ($type) {
            $map['is_group_buy'] = '1';
        }


        $psize = self::$CMS['set']['pagesize'] ? self::$CMS['set']['pagesize'] : 20;
        $cache = $m->where($map)->order('sorts desc')->page($p, $psize)->select();

        $count = $m->where($map)->count();
        $this->getPage($count, $psize, 'App-loader', '商品管理', 'App-search');
        $this->assign('cache', $cache);
        $this->display();
    }

    //CMS后台商品设置
    public function goodsSet()
    {//todo:xxx
        $id = I('id');
        $m = M('Shop_goods');
        //dump($m);
        //设置面包导航，主加载器请配置
        $bread = array(
            '0' => array('name' => '商城首页'),
            '1' => array('name' => '商品管理'),
            '2' => array('name' => '商品设置'),
        );
        $this->assign('breadhtml', $this->getBread($bread));
        //处理POST提交
        if (IS_POST) {
            $data = I('post.');

            //2016年6月23日添加--处理store_id和supplier_id的问题
            //前台存储了vip_id和supplier_id;可以直接获取使用
            $data['supplier_id'] = I('post.su_id/d');       //必须默认为空，否则会更新为0
            if ($data['supplier_id']) {                       //若商家Id不为false，则获取store_id
                $data['store_id'] = M('supplier_store')->where('supplier_id=' . $data['supplier_id'])->getField('id');
            } else {
                $data['store_id'] = 0;
            }

            $data['content'] = trimUE($data['content']);
            $data['group_time_start'] = strtotime($data['group_time_start']);
            $data['group_time_end'] = strtotime($data['group_time_end']);
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

        //读取标签
        $label = M('Shop_label')->select();
        $this->assign('label', $label);
        //AppTree快速无限分类
        $field = array("id", "pid", "name", "sorts", "concat(path,'-',id) as bpath");
        $cate = appTree(M('Shop_cate'), 0, $field);
        $this->assign('cate', $cate);
        //处理编辑界面
        if ($id) {
            $cache = $m->field('shop_goods.*,supplier.name as su_name,supplier.id as su_id')->join('left join supplier on supplier.id=shop_goods.supplier_id')->where('shop_goods.id=' . $id)->find();
            $this->assign('cache', $cache);
        }
        $this->display();
    }

    /**
     * 商品设置中的自动补全功能
     */
    public function getSupplierName()
    {
        $query = I('get.query', '', 'htmlspecialchars');
        //$res = M()->query('select vip.id as vip_id, supplier.id as su_id,supplier.name as su_name from supplier join vip on supplier.id=vip.supplier_id where vip.id ='.$query.' ');
        $map['supplier.name'] = array('like', '%' . $query . '%');
        $map['vip.id'] = array('like', $query . '%');
        $map['_logic'] = 'or';
        $res = M('supplier')->field('vip.id as vip_id,supplier.id as su_id,supplier.name as su_name')
            ->join('vip on vip.supplier_id=supplier.id')->where($map)->select();

        $data['query'] = $query;
        foreach ($res as $v) {
            $data['suggestions'][] = array('value' => 'ID:' . $v['vip_id'] . ' ' . '商家名称：' . $v['su_name'], 'vip_id' => $v['vip_id'], 'su_id' => $v['su_id']);
        }
        echo json_encode($data);
        exit;
    }

    public function goodsDel()
    {
        $id = $_GET['id']; //必须使用get方法
        $m = M('Shop_goods');
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

    public function goodsStatus()
    {
        $m = M('Shop_goods');
        $now = I('status') ? 0 : 1;
        $map['id'] = I('id');
        $re = $m->where($map)->setField('status', $now);
        if ($re) {
            $info['status'] = 1;
            $info['msg'] = '设置成功!';
        } else {
            $info['status'] = 0;
            $info['msg'] = '设置失败!';
        }
        $this->ajaxReturn($info);
    }

    //CMS后台商城分类
    public function cate()
    {
        //设置面包导航，主加载器请配置
        $bread = array(
            '0' => array(
                'name' => '商城首页',
                'url' => U('Admin/Shop/index'),
            ),
            '1' => array(
                'name' => '商城分类',
                'url' => U('Admin/Shop/cate'),
            ),
        );
        $this->assign('breadhtml', $this->getBread($bread));
        //绑定搜索条件与分页
        $m = M('Shop_cate');
        $p = $_GET['p'] ? $_GET['p'] : 1;
        $name = I('name') ? I('name') : '';
        if ($name) {
            $map['name'] = array('like', "%$name%");
            $this->assign('name', $name);
        }
        //AppTree快速无限分类
        $field = array("id", "pid", "lv", "name", "summary", "soncate", "sorts", "is_enable", "concat(path,'-',id) as bpath");
        $cache = appTree($m, 0, $field);
        $this->assign('cache', $cache);
        $this->display();
    }

    //CMS后台商城分类设置
    public function cateSet()
    {
        $id = I('id');  //来自form表单，用于判断更新、新增
        $m = M('Shop_cate');
        //设置面包导航，主加载器请配置
        $bread = array(
            '0' => array('name' => '商城首页'),
            '1' => array('name' => '商城分类'),
            '2' => array('name' => '分类设置'),
        );
        $this->assign('breadhtml', $this->getBread($bread));
        //处理POST提交
        if (IS_POST) {
            //die('aa');
            $data = I('post.');
            if ($id) {
                //保存时判断
                $old = $m->where('id=' . $id)->limit(1)->find();
                if ($old['pid'] != $data['pid']) {
                    $hasson = $m->where('pid=' . $id)->limit(1)->find();
                    if ($hasson) {
                        $info['status'] = 0;
                        $info['msg'] = '此分类有子分类，不可以移动！';
                        $this->ajaxReturn($info);
                    }
                }
                if ($data['pid']) {
                    //更新Path，强制处理
                    $path = setPath($m, $data['pid']);
                    $data['path'] = $path['path'];
                    $data['lv'] = $path['lv'];
                } else {
                    $data['path'] = 0;
                    $data['lv'] = 1;
                }
                $re = $m->save($data);
                if (FALSE !== $re) {
                    //更新新老父级，暂不做错误处理
                    if ($old['pid'] != $data['pid']) {
                        $re = setSoncate($m, $data['pid']);
                        $rold = setSoncate($m, $old['pid']);
                        $info['status'] = 1;
                        $info['msg'] = $old['pid'];
                        $this->ajaxReturn($info);
                    } else {
                        $re = setSoncate($m, $data['pid']);
                    }
                    $info['status'] = 1;
                    $info['msg'] = '设置成功！';
                } else {
                    $info['status'] = 0;
                    $info['msg'] = '设置失败！';
                }
            } else {
                if ($data['pid']) {
                    //更新父级，强制处理
                    $path = setPath($m, $data['pid']);
                    $data['path'] = $path['path'];
                    $data['lv'] = $path['lv'];
                } else {
                    $data['path'] = 0;
                    $data['lv'] = 1;
                }
                $re = $m->add($data);
                if ($re) {
                    //更新父级，暂不做错误处理
                    if ($data['pid']) {
                        $re = setSoncate($m, $data['pid']);
                    }
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
        //AppTree快速无限分类
        $field = array("id", "pid", "name", "sorts", "concat(path,'-',id) as bpath");
        $map = array('lv' => 1);
        $cate = appTree($m, 0, $field, $map);
        $this->assign('cate', $cate);
        $this->display();
    }

    public function cateDel()
    {
        $id = $_GET['id']; //必须使用get方法
        $m = M('Shop_cate');
        if (!$id) {
            $info['status'] = 0;
            $info['msg'] = 'ID不能为空!';
            $this->ajaxReturn($info);
        }
        //删除时判断
        $self = $m->where('id=' . $id)->limit(1)->find();
        // 存在子类不删除
        // if($self['soncate']){
        // 	$info['status']=0;
        // 	$info['msg']='不能删除，存在子分类！';
        // 	$this->ajaxReturn($info);
        // }
        $re = $m->delete($id);
        // 删除所有子类
        $tempList = split(',', $self['soncate']);
        foreach ($tempList as $k => $v) {
            $res = $m->delete($v);
        }
        if ($re) {
            //更新上级soncate
            if ($self['pid']) {
                $re = setSoncate($m, $self['pid']);
            }
            $info['status'] = 1;
            $info['msg'] = '删除成功!';
        } else {
            $info['status'] = 0;
            $info['msg'] = '删除失败!';
            $this->ajaxReturn($info);
        }
        $this->ajaxReturn($info);
    }

    //CMS后台商城分组
    public function group()
    {
        //设置面包导航，主加载器请配置
        $bread = array(
            '0' => array('name' => '商城首页'),
            '1' => array('name' => '商城分组'),
        );
        $this->assign('breadhtml', $this->getBread($bread));
        //绑定搜索条件与分页
        $m = M('Shop_group');
        $p = $_GET['p'] ? $_GET['p'] : 1;
        $name = I('name') ? I('name') : '';
        if ($name) {
            $map['name'] = array('like', "%$name%");
            $this->assign('name', $name);
        }
        $psize = self::$CMS['set']['pagesize'] ? self::$CMS['set']['pagesize'] : 20;
        $cache = $m->where($map)->page($p, $psize)->select();
        $count = $m->where($map)->count();
        $this->getPage($count, $psize, 'App-loader', '商城分组', 'App-search');
        $this->assign('cache', $cache);
        $this->display();
    }

    //CMS后台分组设置
    public function groupSet()
    {
        $id = I('id');
        $m = M('Shop_group');
        //设置面包导航，主加载器请配置
        $bread = array(
            '0' => array(
                'name' => '商城首页',
                'url' => U('Admin/Shop/index'),
            ),
            '1' => array('name' => '商城分组'),
            '2' => array('name' => '分组设置'),
        );
        $this->assign('breadhtml', $this->getBread($bread));
        //处理POST提交
        if (IS_POST) {
            $data['name'] = I('name', '');
            $data['goods'] = I('goods', '');
            $data['summary'] = I('summary', '');
            $data['sorts'] = I('sorts', '');
            $data['icon'] = I('icon', '');


            if ($id) {
                $data['id'] = I('id', 0, 'int');
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

    // 设置分组显示
    public function setGroup()
    {
        $id = $_GET['id']; //必须使用get方法
        $type = $_GET['type'];  //1:显示 0：不显示
        $m = M('Shop_group');
        if (!$id) {
            $info['status'] = 0;
            $info['msg'] = 'ID不能为空!';
            $this->ajaxReturn($info);
        }
        // 撤销原有分组 2016年8月16日不再撤销原有分组
        //$ree = $m->where(array('status' => 1))->save(array('status' => 0));
        if ($type) {
            $data['status'] = 1;
        } else {
            $data['status'] = 0;
        }

        $re = $m->where(array('id' => $id))->save($data);
        if ($re) {
            $info['status'] = 1;
            $info['msg'] = '设置成功!';
        } else {
            $info['status'] = 0;
            $info['msg'] = '设置失败!';
        }
        $this->ajaxReturn($info);
    }

    public function groupDel()
    {
        $id = $_GET['id']; //必须使用get方法
        $m = M('Shop_group');
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

    //CMS后台SKU属性
    public function skuattr()
    {
        //设置面包导航，主加载器请配置
        $bread = array(
            '0' => array(
                'name' => '商城首页',
                'url' => U('Admin/Shop/index'),
            ),
            '1' => array(
                'name' => 'SKU属性',
                'url' => U('Admin/Shop/skuattr'),
            ),
        );
        $this->assign('breadhtml', $this->getBread($bread));
        //绑定搜索条件与分页
        $m = M('Shop_skuattr');
        $p = $_GET['p'] ? $_GET['p'] : 1;
        $name = I('name') ? I('name') : '';
        if ($name) {
            $map['name'] = array('like', "%$name%");
            $this->assign('name', $name);
        }
        $psize = self::$CMS['set']['pagesize'] ? self::$CMS['set']['pagesize'] : 20;
        $cache = $m->where($map)->page($p, $psize)->select();
        $count = $m->where($map)->count();
        $this->getPage($count, $psize, 'App-loader', 'SKU属性', 'App-search');
        $this->assign('cache', $cache);
        $this->display();
    }

    //CMS后台SKU属性设置
    public function skuattrSet()
    {
        $id = I('id');
        $m = M('Shop_skuattr');
        //设置面包导航，主加载器请配置
        $bread = array(
            '0' => array(
                'name' => '商城首页',
                'url' => U('Admin/Shop/index'),
            ),
            '1' => array(
                'name' => '商城分组',
                'url' => U('Admin/Shop/skuattr'),
            ),
            '2' => array(
                'name' => 'SKU属性设置',
                'url' => $id ? U('Admin/Shop/skuattrSet', array('id' => $id)) : U('Admin/Shop/skuattrSet'),
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
                    if ($data['newitem']) {
                        $mitem = M('Shop_skuattr_item');
                        $dit['pid'] = $id;
                        $items = array_filter(explode(',', $data['newitem']));
                        foreach ($items as $v) {
                            $dit['name'] = $v;
                            $rit = $mitem->add($dit);
                            if ($rit) {
                                $rr['path'] = $id . $rit;
                                $rerr = $mitem->where('id=' . $rit)->save($rr);
                            }
                        }
                        $son = $mitem->where('pid=' . $id)->field('name,path')->select();
                        $dson['items'] = "";
                        $dson['itemspath'] = "";
                        foreach ($son as $v) {
                            $dson['items'] = $dson['items'] . $v['name'] . ',';
                            $dson['itemspath'] = $dson['itemspath'] . $v['path'] . ',';
                        }
                        $rfather = $m->where('id=' . $id)->save($dson);
                    }
                    $info['status'] = 1;
                    $info['msg'] = '设置成功！';
                } else {
                    $info['status'] = 0;
                    $info['msg'] = '设置失败！';
                }
            } else {
                $dt['name'] = $data['name'];
                $dt['cctime'] = time();
                $re = $m->add($dt);
                if ($re) {
                    if ($data['newitem']) {
                        $mitem = M('Shop_skuattr_item');
                        $dit['pid'] = $re;
                        $items = array_filter(explode(',', $data['newitem']));
                        foreach ($items as $v) {
                            $dit['name'] = $v;
                            $rit = $mitem->add($dit);
                            if ($rit) {
                                $rr['path'] = $re . $rit;
                                $rerr = $mitem->where('id=' . $rit)->save($rr);
                            }
                        }
                        $son = $mitem->where('pid=' . $re)->field('name,path')->select();
                        $dson['items'] = "";
                        $dson['itemspath'] = "";
                        foreach ($son as $v) {
                            $dson['items'] = $dson['items'] . $v['name'] . ',';
                            $dson['itemspath'] = $dson['itemspath'] . $v['path'] . ',';
                        }
                        $rfather = $m->where('id=' . $re)->save($dson);
                    }
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

    public function skuattrDel()
    {
        $id = $_GET['id']; //必须使用get方法
        $m = M('Shop_skuattr');
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

    //用于SKUINFO保存
    public function skuattrSave()
    {
        $id = $_GET['id']; //必须使用get方法
        if (!$id) {
            $info['status'] = 0;
            $info['msg'] = '商品ID不能为空!';
            $this->ajaxReturn($info);
        }
        //处理skuattr
        $data = I('data');
        if (!$data) {
            $info['status'] = 0;
            $info['msg'] = "您还没有选择任何属性！";
            $this->ajaxReturn($info);
        }
        //$list;
        //$arr = array_filter(explode(';', $data));
        $arr = $data;
        foreach ($arr as $k => $v) {
            /*$arr2 = array_filter(explode('-', $v));
            $arrattr = explode(':', $arr2[0]);*/
            $arritem = array_filter(explode(',', $v['str']));
            $list[$k]['attrid'] = $v['id'];
            $list[$k]['attrlabel'] = $v['label'];
            $checked = "";
            //循环item
            foreach ($arritem as $kk => $vv) {
                $at = explode(':', $vv);
                $list[$k]['items'][$at[0]] = $at[1];
                $checked = $checked . $at[0] . ',';
            }
            $list[$k]['checked'] = $checked;
        }
        $list = list_sort_by($list, 'attrid', 'asc');
        //dump($list);
        //$info['status']=1;
        //$info['msg']=serialize($list);
        //$this->ajaxReturn($info);
        $m = M('Shop_goods');
        $skuinfo['skuinfo'] = serialize($list);
        $re = $m->where('id=' . $id)->save($skuinfo);
        if ($re !== FALSE) {
            $info['status'] = 1;
            $info['msg'] = 'SKU属性保存成功!如有变更请及时更新所有SKU!';
        } else {
            $info['status'] = 0;
            $info['msg'] = 'SKU属性保存失败!请重新尝试!';
        }
        $this->ajaxReturn($info);
    }

    //用于SKU生成
    public function skuattrMake()
    {
        $id = $_GET['id']; //必须使用get方法
        if (!$id) {
            $info['status'] = 0;
            $info['msg'] = '商品ID不能为空!';
            $this->ajaxReturn($info);
        }
        $m = M('Shop_goods');
        $goods = $m->where('id=' . $id)->find();
        //dump($goods);
        $skuinfo = unserialize($goods['skuinfo']);
        //dump($skuinfo);
        if (!$skuinfo) {
            $info['status'] = 0;
            $info['msg'] = '您还未设置或保存SKU属性!';
            $this->ajaxReturn($info);
        }
        $cacheattrs = array(); //缓存所有属性表
        $cache; //缓存skupath列表
        $tmpsku; //缓存零时sku
        $tmpskuattrs; //sku属性对照表
        foreach ($skuinfo as $k => $v) {
            $cacheattrs = $cacheattrs + $skuinfo[$k]['items'];
            $cache[$k] = array_filter(explode(',', $v['checked']));
        }

        if (count($cache) > 1) {
            //快速排列
            $tmp = Descartes($cache);
            foreach ($tmp as $k => $v) {
                $sttr;
                foreach ($v as $kk => $vv) {
                    $sttr[$kk] = $cacheattrs[$vv];
                }
                $sk = $id . '-' . implode('-', $v);
                $tmpsku[$k] = $sk;
                $tmpskuattrs[$sk] = implode(',', $sttr);

            }
        } else {
            foreach ($cache[0] as $k => $v) {
                $sk = $id . '-' . $v;
                $tmpsku[$k] = $sk;
                $tmpskuattrs[$sk] = $cacheattrs[$v];
            }
        }
        //dump($tmpskuattrs);
        //dump($tmpsku);

        $fftmpsku = array_flip($tmpsku);
        //处理原始sku
        $msku = M('Shop_goods_sku');
        $oldsku = $msku->where('goodsid=' . $id)->select();
        if ($oldsku) {
            foreach ($oldsku as $k => $v) {
                //如果已经建立,判断状态
                if (!in_array($v['sku'], $tmpsku)) {
                    //如果不存在，禁用该sku
                    $v['status'] = 0;
                    $ro = $msku->save($v);
                } else {
                    //如果已经存在，开启该sku
                    $v['status'] = 1;
                    $ro = $msku->save($v);
                    //移除fftmpsku对应项目
                    unset($fftmpsku[$v['sku']]);
                }

            }
        }
        //最后需要添加的新sku
        $finaltmpsku = array_flip($fftmpsku);
        //dump($finaltmpsku);
        //die();
        if ($finaltmpsku) {
            $dsku;
            foreach ($finaltmpsku as $k => $v) {
                $dsku[$k]['goodsid'] = $id;
                $dsku[$k]['sku'] = $v;
                $dsku[$k]['skuattr'] = $tmpskuattrs[$v];
                $dsku[$k]['group_buy_price'] = $dsku[$k]['price'] = floatval($goods['group_buy_money']); //初始团购价格等于商品售价 2016年8月11日20:53:18更改
                $dsku[$k]['num'] = $goods['num'];
                $dsku[$k]['status'] = 1;
            }
            //强制重新排序
            sort($dsku);
            //计算总库存
            $re = $msku->addAll($dsku);
            if ($re) {
                $totalnum = $msku->where(array('goodsid' => $id, 'status' => 1))->sum('num');
                if ($totalnum) {
                    $rgg = $m->where('id=' . $id)->setField('num', $totalnum);
                }
                //计算总库存
                $info['status'] = 1;
                $info['msg'] = 'SKU更新成功!';
            } else {
                $info['status'] = 0;
                $info['msg'] = 'SKU更新失败!请重新尝试!';
            }
        } else {
            $totalnum = $msku->where(array('goodsid' => $id, 'status' => 1))->sum('num');
            if ($totalnum) {
                $rgg = $m->where('id=' . $id)->setField('num', $totalnum);
            }
            $info['status'] = 1;
            $info['msg'] = 'SKU更新成功!没有新增SKU!';
        }
        $this->ajaxReturn($info);
    }

    //CMS后台SKU管理
    public function sku()
    {
        //设置面包导航，主加载器请配置
        $bread = array(
            '0' => array('name' => '商城首页'),
            '1' => array('name' => '商品管理'),
            '1' => array('name' => '商品SKU管理'),
        );
        $this->assign('breadhtml', $this->getBread($bread));
        $goodsid = I('id');
        $this->assign('goodsid', $goodsid);
        //绑定商品和skuinfo
        $goods = M('Shop_goods')->where('id=' . $goodsid)->find();
        $this->assign('goods', $goods);
        if ($goods['skuinfo']) {
            $skuinfo = unserialize($goods['skuinfo']);
            $skm = M('Shop_skuattr_item');
            foreach ($skuinfo as $k => $v) {
                $checked = explode(',', $v['checked']);
                $attr = $skm->field('path,name')->where('pid=' . $v['attrid'])->select();
                foreach ($attr as $kk => $vv) {
                    $attr[$kk]['checked'] = in_array($vv['path'], $checked) ? 1 : '';
                }
                $skuinfo[$k]['allitems'] = $attr;
            }
        }
        //dump($skuinfo);

        $this->assign('skuinfo', $skuinfo);
        //绑定搜索条件与分页
        $m = M('Shop_goods_sku');
        //追入商品条件
        $map['goodsid'] = $goodsid;
        $p = $_GET['p'] ? $_GET['p'] : 1;
        $name = I('name') ? I('name') : '';
        $map['status'] = 1;
        if ($name) {
            $map['skuattr'] = array('like', "%$name%");
            $this->assign('name', $name);
        }
        //$psize=self::$CMS['set']['pagesize']?self::$CMS['set']['pagesize']:20;
        $psize = 50;
        $cache = $m->where($map)->page($p, $psize)->select();
        $count = $m->where($map)->count();
        $this->getPage($count, $psize, 'App-loader', '商品SKU管理', 'App-search');
        $this->assign('cache', $cache);
        $this->display();
    }

    //CMS后台sku设置
    public function skuSet()
    {
        $id = I('id');
        $m = M('Shop_goods_sku');
        //处理编辑界面
        $cache = $m->where('id=' . $id)->find();
        $this->assign('cache', $cache);
        //设置面包导航，主加载器请配置
        $bread = array(
            '0' => array(
                'name' => '商城首页',
                'url' => U('Admin/Shop/index'),
            ),
            '1' => array(
                'name' => '商品SKU管理',
                'url' => U('Admin/Shop/sku', array('id' => $cache['goodsid'])),
            ),
            '2' => array(
                'name' => '商品SKU设置',
                'url' => U('Admin/Shop/skuSet', array('id' => $id)),
            ),
        );
        $this->assign('breadhtml', $this->getBread($bread));
        //处理POST提交
        if (IS_POST) {
            //只有保存模式
            $data = I('post.');
            $re = $m->where('id=' . $id)->save($data);
            if (FALSE !== $re) {
                //重新计算总库存
                $totalnum = $m->where(array('goodsid' => $cache['goodsid'], 'status' => 1))->sum('num');
                if ($totalnum) {
                    $min = M('Shop_goods_sku')->where(array('goodsid' => $cache['goodsid'], 'status' => '1'))->min('group_buy_price');
                    $rgg = M('Shop_goods')->where('id=' . $cache['goodsid'])->setField(array('group_buy_money' => $min, 'num' => $totalnum));
                }
                $info['status'] = 1;
                $info['msg'] = '设置成功！';
            } else {
                $info['status'] = 0;
                $info['msg'] = '设置失败！';
            }
            $this->ajaxReturn($info);
        }

        $this->display();
    }

    //CMS后台SKU查找带回管理器
    public function skuLoader()
    {
        $m = M('Shop_skuattr');
        $findback = I('fbid');
        $this->assign('findback', $findback);
        $map['id'] = array('not in', I('ids'));
        $cache = $m->where($map)->select();
        $this->assign('cache', $cache);
        $this->ajaxReturn($this->fetch());
    }

    //CMS后台SKU查找带回模板
    public function skuFindback()
    {
        if (IS_AJAX) {
            $m = M('Shop_skuattr');
            $id = I('id');
            $map['id'] = $id;
            $cache = $m->where($map)->limit(1)->find();
            $this->assign('cache', $cache);
            $items = M('Shop_skuattr_item')->where('pid=' . $id)->select();
            $this->assign('items', $items);
            $this->ajaxReturn($this->fetch());
        } else {
            utf8error('非法访问！');
        }
    }

    //CMS后台广告分组
    public function ads()
    {
        //设置面包导航，主加载器请配置
        $bread = array(
            '0' => array(
                'name' => '商城首页',
                'url' => U('Admin/Shop/index'),
            ),
            '1' => array(
                'name' => '商城广告',
                'url' => U('Admin/Shop/ads'),
            ),
        );
        $this->assign('breadhtml', $this->getBread($bread));
        //绑定搜索条件与分页
        $m = M('Shop_ads');
        $p = $_GET['p'] ? $_GET['p'] : 1;
        $name = I('name') ? I('name') : '';
        if ($name) {
            $map['name'] = array('like', "%$name%");
            $this->assign('name', $name);
        }
        $psize = self::$CMS['set']['pagesize'] ? self::$CMS['set']['pagesize'] : 20;
        $cache = $m->where($map)->page($p, $psize)->select();
//        foreach ($cache as $k => $v) {
//            $listpic = $this->getPic($v['pic']);
//            $cache[$k]['imgurl'] = $listpic['imgurl'];
//        }
        $count = $m->where($map)->count();
        $this->getPage($count, $psize, 'App-loader', '商城广告', 'App-search');
        $this->assign('cache', $cache);
        $this->display();
    }

    //CMS后台广告设置
    public function adsSet()
    {
        $id = I('id');
        $m = M('Shop_ads');
        //设置面包导航，主加载器请配置
        $bread = array(
            '0' => array(
                'name' => '商城首页',
                'url' => U('Admin/Shop/index'),
            ),
            '1' => array(
                'name' => '商城广告',
                'url' => U('Admin/Shop/ads'),
            ),
            '2' => array(
                'name' => '广告设置',
                'url' => $id ? U('Admin/Shop/adsSet', array('id' => $id)) : U('Admin/Shop/adsSet'),
            ),
        );
        $this->assign('breadhtml', $this->getBread($bread));
        //处理POST提交
        if (IS_POST) {
            //die('aa');
            //$data = I('post.');
            $data['name'] = I('name', '');
            $data['summary'] = I('summary', '');
            $data['pic'] = I('pic', '');
            $data['url'] = I('url', '');
            $data['mark'] = I('mark', '');


            if ($id) {
                $data['id'] = I('id', 0, 'int');
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

    public function adsDel()
    {
        $id = $_GET['id']; //必须使用get方法
        $m = M('Shop_ads');
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

    //CMS后台商城订单
    public function order()
    {
        //设置面包导航，主加载器请配置
        $bread = array(
            '0' => array('name' => '商城首页'),
            '1' => array('name' => '订单管理')
        );
        $this->assign('breadhtml', $this->getBread($bread));
        $status = I('status');
        if ($status || $status == '0') {
            $map['status'] = $status;
            //交易满7天
            if ($status == 8) {
                $map['status'] = 3;
                $seven = time() - 604800;
                $map['ctime'] = array('elt', $seven);
            }
            // 当天所有订单，零点算起
            if ($status == 9) {
                unset($map['status']);
                $today = strtotime(date("Y-m-d"));
                $map['ctime'] = array('egt', $today);
                //echo $today;
            }
        }
        $this->assign('status', $status);
        //绑定搜索条件与分页
        $m = M('Shop_order');
        $p = $_GET['p'] ? $_GET['p'] : 1;
        $name = I('name') ? I('name') : '';
        if ($name) {
            //订单号邦定
            $map['vipid|oid|vipmobile'] = array('like', "%$name%");
            $this->assign('name', $name);
        }
        $map['is_group_buy'] = '0';
        $psize = self::$CMS['set']['pagesize'] ? self::$CMS['set']['pagesize'] : 20;
        $cache = $m->where($map)->page($p, $psize)->order('ctime desc')->select();
        $count = $m->where($map)->count();
        $this->getPage($count, $psize, 'App-loader', '商城订单', 'App-search');
        $this->assign('cache', $cache);
        $this->display();
    }

    // Admin后台订单当天报表
    public function orderReport()
    {
        //设置面包导航，主加载器请配置
        $bread = array(
            '0' => array('name' => '商城首页'),
            '1' => array('name' => '订单管理'),
        );
        $this->assign('breadhtml', $this->getBread($bread));
        // Prepare Data
        $mgoods = M('Shop_goods');
        $msku = M('Shop_goods_sku');
        $morder = D('shop_order');
        $data = $morder->today();

        $goods = array();
        $sku = array();
        $temp = $mgoods->select();
        foreach ($temp as $k => $v) {
            $goods[$v['id']] = $v;
        }
        $temp = $msku->select();
        foreach ($temp as $k => $v) {
            $sku[$v['id']] = $v;
        }
        $this->assign('goods', $goods);
        $this->assign('sku', $sku);
        $this->assign('cache', $data);
        $this->display();
    }

    //CMS后台Order详情
    public function orderDetail()
    {
        $id = I('id');
        $backUrl = base64_decode(I('backUrl'));
        $m = M('Shop_order');
        $mlog = M('Shop_order_log');
        //设置面包导航，主加载器请配置
        $bread = array(
            '0' => array('name' => '商城首页'),
            '1' => array('name' => '商城订单'),
            '2' => array('name' => '订单详情')
        );
        $this->assign('breadhtml', $this->getBread($bread));
        $cache = $m->where('id=' . $id)->find();
        //坠入vip
        $vip = M('vip')->where('id=' . $cache['vipid'])->find();
        $this->assign('vip', $vip);
        $cache['items'] = unserialize($cache['items']);

        $log = $mlog->where('oid=' . $cache['id'])->select();
        $fxlog = M('Fx_syslog')->where(['oid' => $cache['id'], 'order_type' => OT_SC])->select();
        $this->assign('log', $log);
        $this->assign('fxlog', $fxlog);
        $this->assign('cache', $cache);
        $this->assign('backUrl', $backUrl);

        $this->display();
    }

    //发货快递
    public function orderFhkd()
    {
        $map['id'] = I('id');
        $cache = M('Shop_order')->where($map)->find();
        $this->assign('cache', $cache);
        $mb = $this->fetch();
        $this->ajaxReturn($mb);
    }

    public function orderFhkdSave()
    {
        //$data = I('post.');
        $data['id'] = I('id', 0, 'int');
        $data['fahuokd'] = I('fahuokd', '');
        $data['fahuokdnum'] = I('fahuokdnum', '');

        if (!$data) {
            $info['status'] = 0;
            $info['msg'] = '未正常获取数据！';
        }
        $data['changetime'] = time();
        $re = M('Shop_order')->where('id=' . $data['id'])->save($data);
        if (FALSE !== $re) {
            $info['status'] = 1;
            $info['msg'] = '操作成功！';
        } else {
            $info['status'] = 0;
            $info['msg'] = '操作失败！';
        }
        $this->ajaxReturn($info);
    }

    //订单改价
    public function orderChange()
    {
        $map['id'] = I('id');
        $cache = M('Shop_order')->where($map)->find();
        $this->assign('cache', $cache);
        $mb = $this->fetch();
        $this->ajaxReturn($mb);
    }

    public function orderChangeSave()
    {
        //$data = I('post.');
        $data['id'] = I('id', 0, 'int');
        $data['payprice'] = I('payprice', 0, 'float');
        $data['changeadmin'] = I('changeadmin', '');
        $data['changemsg'] = I('changemsg', '');

        if (!$data) {
            $info['status'] = 0;
            $info['msg'] = '未正常获取数据！';
        }
        $data['changetime'] = time();
        $data['oid'] = date('YmdHis') . '-' . $data['id'];
        $re = M('Shop_order')->where('id=' . $data['id'])->save($data);
        $mlog = M('Shop_order_log');
        if (FALSE !== $re) {
            $log['oid'] = $data['oid'];
            $log['msg'] = '订单价格改为' . $data['payprice'] . '-成功';
            $log['ctime'] = time();
            $rlog = $mlog->add($log);
            $info['status'] = 1;
            $info['msg'] = '操作成功！';
        } else {
            $info['status'] = 0;
            $info['msg'] = '操作失败！';
        }
        $this->ajaxReturn($info);
    }

    //订单关闭
    public function orderClose()
    {
        $map['id'] = I('id');
        $cache = M('Shop_order')->where($map)->find();
        $this->assign('cache', $cache);
        $mb = $this->fetch();
        $this->ajaxReturn($mb);
    }

    public function orderCloseSave()
    {
        //$data = I('post.');
        $data['id'] = I('id', 0, 'int');
        $data['closeadmin'] = I('closeadmin', '');
        $data['closemsg'] = I('closemsg', '');

        if (!$data) {
            $info['status'] = 0;
            $info['msg'] = '未正常获取数据！';
        }
        $m = M('Shop_order');
        $mlog = M('Shop_order_log');
        $mslog = M('Shop_order_syslog');
        $cache = $m->where('id=' . $data['id'])->find();
        switch ($cache['status']) {
            case '1':
                $data['status'] = 6;
                $data['closetime'] = time();
                $re = $m->where('id=' . $data['id'])->save($data);
                if (FALSE !== $re) {
                    //前端LOG
                    $log['oid'] = $cache['id'];
                    $log['msg'] = '未支付订单关闭成功';
                    $log['ctime'] = time();
                    $rlog = $mlog->add($log);
                    //后端LOG
                    $log['type'] = 6;
                    $log['paytype'] = $cache['paytype'];
                    $rslog = $mslog->add($log);

                    $info['status'] = 1;
                    $info['msg'] = '关闭未支付订单成功！';
                } else {
                    //前端LOG
                    $log['oid'] = $cache['id'];
                    $log['msg'] = '未支付订单关闭失败';
                    $log['ctime'] = time();
                    $rlog = $mlog->add($log);
                    //后端LOG
                    $log['type'] = -1;
                    $log['paytype'] = $cache['paytype'];
                    $rslog = $mslog->add($log);
                    $info['status'] = 0;
                    $info['msg'] = '关闭未支付订单失败！';
                }
                $this->ajaxReturn($info);
                break;
            case '2':
                //已支付订单跳转到这里处理
                $this->orderClosePay($cache, $data);
                break;
            default:
                $info['status'] = 0;
                $info['msg'] = '只有未付款和已付款订单可以关闭!';
                $this->ajaxReturn($info);
                break;
        }

    }

    //已支付订单退款
    public function orderClosePay($cache, $data)
    {
        //关闭订单时不再处理库存
        $m = M('Shop_order');
        $mvip = M('Vip');
        $mlog = M('Shop_order_log');
        $mslog = M('Shop_order_syslog');
        if (!$cache['ispay']) {
            $info['status'] = 0;
            $info['msg'] = '订单支付状态异常！请重试或联系技术！';
            $this->ajaxReturn($info);
        }
        //抓取会员数据
        $vip = $mvip->where('id=' . $cache['vipid'])->find();
        if (!$vip) {
            $info['status'] = 0;
            $info['msg'] = '会员数据获取异常！请重试或联系技术！';
            $this->ajaxReturn($info);
        }
        //支付金额
        $payprice = $cache['payprice'];
        //全部退款至余额
        $data['status'] = 6;
        $data['closetime'] = time();
        $re = $m->where('id=' . $cache['id'])->save($data);
        if (FALSE !== $re) {
            $log['oid'] = $cache['id'];
            $log['msg'] = '订单关闭-成功';
            $log['ctime'] = time();
            $rlog = $mlog->add($log);
            $info['status'] = 1;
            $info['msg'] = '关闭订单成功！';
            if ($cache['ispay']) {
                $mm = $vip['money'] + $payprice;
                $rvip = $mvip->where('id=' . $cache['vipid'])->setField('money', $mm);
                if ($rvip) {
                    //前端LOG
                    $log['oid'] = $cache['id'];
                    $log['msg'] = '自动退款' . $payprice . '元至用户余额-成功';
                    $log['ctime'] = time();
                    $rlog = $mlog->add($log);
                    $log['type'] = 6;
                    $log['paytype'] = $cache['paytype'];
                    $rslog = $mslog->add($log);
                    //后端LOG
                    $info['status'] = 1;
                    $info['msg'] = '关闭订单成功！自动退款' . $payprice . '元至用户余额成功!';
                } else {
                    //前端LOG
                    $log['oid'] = $cache['id'];
                    $log['msg'] = '自动退款' . $payprice . '元至用户余额-失败!请联系客服!';
                    $log['ctime'] = time();
                    $rlog = $mlog->add($log);
                    //后端LOG
                    $log['type'] = -1;
                    $log['paytype'] = $cache['paytype'];
                    $rslog = $mslog->add($log);
                    $info['status'] = 1;
                    $info['msg'] = '关闭订单成功！自动退款' . $payprice . '元至用户余额失败!请联系技术！';
                }
            }

        } else {
            $info['status'] = 0;
            $info['msg'] = '关闭订单失败！请重新尝试!';
        }
        $this->ajaxReturn($info);
    }

    //订单发货
    public function orderDeliver()
    {
        $id = I('id');
        $group_num = I('post.group_num');
        $group_id = I('post.group_id');
        if (!$id) {
            $info['status'] = 0;
            $info['msg'] = '未正常获取ID数据！';
        }
        $m = M('Shop_order');
        $re = $m->where(array('id' => $id, 'status' => 2))->setField('status', 3);
        $mlog = M('Shop_order_log');
        $mslog = M('Shop_order_syslog');
        $dwechat = D('Wechat');
        if (FALSE !== $re) {
            $count = $m->where(array('group_buy_id' => $group_id, 'status' => '2'))->select();
            if (empty($count)) {
                $rel = M('group_buy')->where(array('id' => $group_id))->setField('status', 3);
            }
            $order = M('Shop_order')->where('id=' . $id)->find();

            $log['oid'] = $id;
            $log['msg'] = '订单已发货';
            $log['ctime'] = time();
            $rlog = $mlog->add($log);
            //后端LOG
            $log['type'] = 3;
            $log['paytype'] = $order['paytype'];
            $rslog = $mslog->add($log);

            // 插入订单发货模板消息=====================
            $vip = M('vip')->where(array('id' => $order['vipid']))->find();
            if ($order['is_group_buy']) {
                $backUrl = U('App/Shop/groupOrderList', array('sid' => 0, 'type' => 2));
            } else {
                $backUrl = null;
            }
            $order['items'] = unserialize($order['items']);
            $wechatTemplate = D('WechatTemplate');
            $wechatTemplate->sendMessage_DeliverGoods([
                'to_user' => $vip['openid'],
                'order_product' => $order['items'][0]['name'],
                'order_id' => $order['id'],
                'order_code' => $order['oid'],
                'express_name' => $order['fahuokd'],
                'express_num' => $order['fahuokdnum'],
                'receiver' => ['name' => $order['vipname'], 'mobile' => $order['vipmobile'], 'address' => $order['vipaddress']],
                'url' => $backUrl,
            ]);


//            $templateidshort = 'OPENTM201541214';
//            $templateid = $dwechat->getTemplateId($templateidshort);
//
//            if ($templateid) { // 存在才可以发送模板消息
//                $data = array();
//                $data['touser'] = $vip['openid'];
//                $data['template_id'] = $templateid;
//                $data['topcolor'] = "#0000FF";
//                $data['data'] = array(
//                    'first' => array('value' => '您好，您的订单已发货'),
//                    'keyword1' => array('value' => $order['oid']),
//                    'keyword2' => array('value' => $order['fahuokd']),
//                    'keyword3' => array('value' => $order['fahuokdnum']),
//                    'remark' => array('value' => '')
//                );
//                $options['appid'] = self::$SYS['set']['wxappid'];
//                $options['appsecret'] = self::$SYS['set']['wxappsecret'];
//
//                $wx = new \Util\Wx\Wechat($options);
//                $rere = $wx->sendTemplateMessage($data);
//
//            }
            // 插入订单发货模板消息结束=================
            $info['status'] = 1;
            $info['msg'] = '操作成功！';
        } else {
            $info['status'] = 0;
            $info['msg'] = '操作失败！';
        }
        $this->ajaxReturn($info);
    }

    //订单批量发货
    public function orderDeliverAll()
    {
        $arr = array_filter(explode(',', $_GET['id'])); //必须使用get方法
        if (!$arr) {
            $info['status'] = 0;
            $info['msg'] = '未正常获取ID数据！';
            $this->ajaxReturn($info);
        }
        $m = M('Shop_order');
        $mlog = M('Shop_order_log');
        $mslog = M('Shop_order_syslog');
        // ==========================================================
        $dwechat = D('Wechat');
        $options['appid'] = self::$SYS['set']['wxappid'];
        $options['appsecret'] = self::$SYS['set']['wxappsecret'];
        $wx = new \Util\Wx\Wechat($options);
        // ==========================================================
        $err = TRUE;
        foreach ($arr as $k => $v) {
            $order = $m->where('id=' . $v)->find();
            if ($order['status'] == 2) {
                $re = $m->where('id=' . $order['id'])->setField('status', 3);
                if (FALSE !== $re) {
                    $log['oid'] = $order['id'];
                    $log['msg'] = '订单已发货';
                    $log['ctime'] = time();
                    $rlog = $mlog->add($log);
                    //后端LOG
                    $log['type'] = 3;
                    $log['paytype'] = $order['paytype'];
                    $rslog = $mslog->add($log);
                    // 插入订单发货模板消息=====================
                    $vip = M('vip')->where(array('id' => $order['vipid']))->find();

                    $order['items'] = unserialize($order['items']);
                    $wechatTemplate = D('WechatTemplate');
                    $wechatTemplate->sendMessage_DeliverGoods([
                        'to_user' => $vip['openid'],
                        'order_product' => $order['items'][0]['name'],
                        'order_id' => $order['id'],
                        'order_code' => $order['oid'],
                        'express_name' => $order['fahuokd'],
                        'express_num' => $order['fahuokdnum'],
                        'receiver' => ['name' => $order['vipname'], 'mobile' => $order['vipmobile'], 'address' => $order['vipaddress']]
                    ]);


//                    $templateidshort = 'OPENTM201541214';
//                    $templateid = $dwechat->getTemplateId($templateidshort);
//                    if ($templateid) { // 存在才可以发送模板消息
//                        $data = array();
//                        $data['touser'] = $vip['openid'];
//                        $data['template_id'] = $templateid;
//                        $data['topcolor'] = "#0000FF";
//                        $data['data'] = array(
//                            'first' => array('value' => '您好，您的订单已发货'),
//                            'keyword1' => array('value' => $old['oid']),
//                            'keyword2' => array('value' => $old['fahuokd']),
//                            'keyword3' => array('value' => $old['fahuokdnum']),
//                            'remark' => array('value' => '')
//                        );
//                        $re = $wx->sendTemplateMessage($data);
//                    }
                    // 插入订单发货模板消息结束=================
                } else {
                    $err = FALSE;
                }
            }
        }
        if ($err) {
            $info['status'] = 1;
            $info['msg'] = '批量发货成功！';
        } else {
            $info['status'] = 0;
            $info['msg'] = '批量发货可能有部分失败，请刷新后重新尝试！';
        }

        $this->ajaxReturn($info);
    }

    //完成订单
    public function orderSuccess()
    {

        $id = I('id');
        if (!$id) {
            $info['status'] = 0;
            $info['msg'] = '未正常获取ID数据！';
            $this->ajaxReturn($info);
        }
        //判断商城配置
        if (!self::$CMS['shopset']) {
            $info['status'] = 0;
            $info['msg'] = '未正常获取商城配置信息！';
            $this->ajaxReturn($info);
        }
        //判断会员配置
        if (!self::$CMS['vipset']) {
            $info['status'] = 0;
            $info['msg'] = '未正常获取会员配置信息！';
            $this->ajaxReturn($info);
        }
        $group_num = I('post.group_num');
        $group_id = I('post.group_id');
        //分销流程介入
        $m = M('shop_order');
        $map['id'] = $id;
        $cache = $m->where($map)->find();
        if (!$cache) {
            $info['status'] = 0;
            $info['msg'] = '操作失败！';
            $this->ajaxReturn($info);
        }
        if ($cache['status'] != 3) {
            $info['status'] = 0;
            $info['msg'] = '操作失败！';
            $this->ajaxReturn($info);
        }
        //追入会员信息
        $vip = M('Vip')->where('id=' . $cache['vipid'])->find();
        if (!$vip) {
            $info['status'] = 0;
            $info['msg'] = '未正常获取此订单的会员信息！';
            $this->ajaxReturn($info);
        }
        $cache['etime'] = time(); //交易完成时间
        $cache['status'] = 5;
        $rod = $m->save($cache);
        if (FALSE !== $rod) {
            //状态（0：交易取消，1：未支付，2：已付款，3：已发货，4：退货中，5：交易完成，6：交易关闭，7：退货完成）
            //TODO 检查逻辑是否正确
            $count = $m->where(array('group_buy_id' => $group_id, 'status' => 3))->select();
            if (empty($count)) {
                $rel = M('group_buy')->where(array('id' => $group_id))->setField('status', 4);
                if ($rel) {
                    $info['status'] = 1;
                    $info['msg'] = '此团购所有成员交易完成！';
                    //$this->ajaxReturn($info);
                }
            }
            $commission = D('Commission');
            $rlt = $commission->process($id, self::$CMS['shopset']);
            $this->ajaxReturn($rlt);
        } else {
            //后端日志
            $mlog = M('Shop_order_syslog');
            $dlog['oid'] = $cache['id'];
            $dlog['msg'] = '确认收货失败';
            $dlog['type'] = -1;
            $dlog['paytype'] = $cache['paytype'];
            $dlog['ctime'] = time();
            $rlog = $mlog->add($dlog);
            //$this->error('确认收货失败，请重新尝试！');
            $info['status'] = 0;
            $info['msg'] = '后台确认收货操作失败，请重新尝试！';
        }
        $this->ajaxReturn($info);
    }

    //订单退货
    public function orderTuihuo()
    {
        $map['id'] = I('id');
        $cache = M('Shop_order')->where($map)->find();
        $this->assign('cache', $cache);
        $mb = $this->fetch();
        $this->ajaxReturn($mb);
    }

    public function orderTuihuoSave()
    {
        //$data = I('post.');
        $data['id'] = I('id', 0, 'int');
        $data['tuihuoprice'] = I('tuihuoprice', 0, 'float');
        $data['tuihuokd'] = I('tuihuokd', '');
        $data['tuihuokdnum'] = I('tuihuokdnum', '');
        $data['tuihuoadmin'] = I('tuihuoadmin', '');
        $data['tuihuomsg'] = I('tuihuomsg', '');

        if (!$data) {
            $info['status'] = 0;
            $info['msg'] = '未正常获取数据！';
            $this->ajaxReturn($info);
        }
        $m = M('Shop_order');
        $mlog = M('Shop_order_log');
        $mslog = M('Shop_order_syslog');
        $mvip = M('Vip');
        $cache = $m->where('id=' . $data['id'])->find();
        if (!$cache) {
            $info['status'] = 0;
            $info['msg'] = '未正常获取订单数据！';
            $this->ajaxReturn($info);
        }
        if (!$cache) {
            $info['status'] = 0;
            $info['msg'] = '未正常获取此订单数据！';
            $this->ajaxReturn($info);
        }
        //追入会员信息
        $vip = $mvip->where('id=' . $cache['vipid'])->find();
        if (!$vip) {
            $info['status'] = 0;
            $info['msg'] = '未正常获取此订单的会员信息！';
            $this->ajaxReturn($info);
        }
        switch ($cache['status']) {
            case '4':
                $data['status'] = 7;
                $data['tuihuotime'] = time();
                if (!$data['tuihuoprice']) {
                    $info['status'] = 0;
                    $info['msg'] = '退货金额不能为空！';
                    $this->ajaxReturn($info);
                }
                $re = $m->where('id=' . $data['id'])->save($data);
                if (FALSE !== $re) {
                    $vip['money'] = $vip['money'] + $data['tuihuoprice'];
                    $rvip = $mvip->save($vip);
                    if ($rvip !== FALSE) {
                        //前端LOG
                        $log['oid'] = $cache['id'];
                        $log['msg'] = '成功退货，自动退款' . $data['tuihuoprice'] . '元至用户余额-成功';
                        $log['ctime'] = time();
                        $rlog = $mlog->add($log);
                        $log['type'] = 6;
                        $log['paytype'] = $cache['paytype'];
                        $rslog = $mslog->add($log);
                        //后端LOG
                        $info['status'] = 1;
                        $info['msg'] = '关闭订单成功！自动退款' . $data['tuihuoprice'] . '元至用户余额成功!';
                    } else {
                        //前端LOG
                        $log['oid'] = $cache['id'];
                        $log['msg'] = '成功退货，自动退款' . $data['tuihuoprice'] . '元至用户余额-失败!请联系客服!';
                        $log['ctime'] = time();
                        $rlog = $mlog->add($log);
                        //后端LOG
                        $log['type'] = -1;
                        $log['paytype'] = $cache['paytype'];
                        $rslog = $mslog->add($log);
                        $info['status'] = 1;
                        $info['msg'] = '成功退货，自动退款' . $data['tuihuoprice'] . '元至用户余额失败!请联系技术！';
                    }

                } else {
                    //前端LOG
                    $log['oid'] = $cache['id'];
                    $log['msg'] = '订单退货失败';
                    $log['ctime'] = time();
                    $rlog = $mlog->add($log);
                    //后端LOG
                    $log['type'] = -1;
                    $log['paytype'] = $cache['paytype'];
                    $rslog = $mslog->add($log);
                    $info['status'] = 0;
                    $info['msg'] = '订单退货失败！';
                }
                $this->ajaxReturn($info);
                break;
            default:
                $info['status'] = 0;
                $info['msg'] = '只有未付款和已付款订单可以关闭!';
                $this->ajaxReturn($info);
                break;
        }
        //$info['status']=0;
        //$info['msg']='通讯失败，请重新尝试!';
        //$this->ajaxReturn($info);

    }

    public function orderExport()
    {
        $id = I('id');
        $status = I('status');
        if ($id) {
            $map['id'] = array('in', in_parse_str($id));
        } else {
            $map['status'] = $status;
        }
        switch ($status) {
            case 0:
                $tt = "交易取消";
                break;
            case 1:
                $tt = "未付款";
                break;
            case 2:
                $tt = "已付款";
                break;
            case 3:
                $tt = "已发货";
                break;
            case 4:
                $tt = "退货中";
                break;
            case 7:
                $tt = "退货完成";
                break;
            case 5:
                $tt = "交易成功";
                break;
            case 6:
                $tt = "交易关闭";
                break;
            default:
                $tt = '';
                break;
        }
        $data = M('Shop_order')->where($map)->select();
        //dump($data);
        //die();
        foreach ($data as $k => $v) {
            //过滤字段
            switch ($v['status']) {
                case 0:
                    $data[$k]['status'] = "交易取消";
                    break;
                case 1:
                    $data[$k]['status'] = "未付款";
                    break;
                case 2:
                    $data[$k]['status'] = "已付款";
                    break;
                case 3:
                    $data[$k]['status'] = "已发货";
                    break;
                case 4:
                    $data[$k]['status'] = "退货中";
                    break;
                case 7:
                    $data[$k]['status'] = "退货完成";
                    break;
                case 5:
                    $data[$k]['status'] = "交易成功";
                    break;
                case 6:
                    $data[$k]['status'] = "交易关闭";
                    break;
            }
            $data[$k]['ctime'] = date('Y-m-d H:i:s', $v['ctime']);
            $data[$k]['paytime'] = $v['paytime'] ? date('Y-m-d H:i:s', $v['paytime']) : '';
            $data[$k]['changetime'] = $v['changetime'] ? date('Y-m-d H:i:s', $v['changetime']) : '';
            $data[$k]['closetime'] = $v['closetime'] ? date('Y-m-d H:i:s', $v['closetime']) : '';
            $data[$k]['tuihuosqtime'] = $v['tuihuosqtime'] ? date('Y-m-d H:i:s', $v['tuihuosqtime']) : '';
            $data[$k]['tuihuotime'] = $v['tuihuotime'] ? date('Y-m-d H:i:s', $v['tuihuotime']) : '';
            $tmpitems = unserialize($v['items']);
            $str = "";
            foreach ($tmpitems as $vv) {
                $vt = '品名：' . $vv['name'] . ' 属性：' . $vv['skuattr'] . '数量：' . $vv['num'] . '单价：' . $vv['price'];
                $str = $str . $vt . '/***/';
            }
            $data[$k]['items'] = $str;
        }
        //dump($data);
        //die();
        $title = array('id' => 'ID',
            'oid' => '订单编号',
            'totalprice' => '订单总价',
            'totalnum' => '商品总数',
            'payprice' => '支付价格',
            'paytype' => '支付类型',
            'paytime' => '支付时间',
            'yf' => '邮费',
            'vipid' => '会员ID',
            'vipname' => '收货姓名',
            'vipmobile' => '收货电话',
            'vipaddress' => '收货地址',
            'msg' => '购买留言',
            'ctime' => '订单创建时间',
            'changetime' => '改价时间',
            'changemsg' => '改价原因',
            'changeadmin' => '改价操作员',
            'closetime' => '关闭时间',
            'closemsg' => '关闭原因',
            'closeadmin' => '关闭操作员',
            'tuihuoprice' => '退货退款金额',
            'tuihuosqtime' => '退货退款申请时间',
            'tuihuotime' => '退货退款完成时间',
            'tuihuokd' => '退货快递公司',
            'tuihuokdnum' => '退货快递单号',
            'tuihuomsg' => '退货原因',
            'tuihuoadmin' => '退货操作员',
            'status' => '订单状态',
            'fahuokd' => '发货快递',
            'fahuokdnum' => '发货快递号',
            'items' => '订单商品详情'
        );
        export_excel($data, $title, $tt . '订单' . date('Y-m-d H:i:s', time()));
    }

    //CMS后台标签列表
    public function label()
    {
        //设置面包导航，主加载器请配置
        $bread = array(
            '0' => array(
                'name' => '商城首页',
                'url' => U('Admin/Shop/index'),
            ),
            '1' => array(
                'name' => '标签列表',
                'url' => U('Admin/Shop/label'),
            ),
        );
        $this->assign('breadhtml', $this->getBread($bread));
        //绑定搜索条件与分页
        $m = M('Shop_label');
        $p = $_GET['p'] ? $_GET['p'] : 1;
        $name = I('name') ? I('name') : '';
        if ($name) {
            $map['name'] = array('like', "%$name%");
            $this->assign("name", $name);
        }
        $psize = self::$CMS['set']['pagesize'] ? self::$CMS['set']['pagesize'] : 20;
        $cache = $m->where($map)->page($p, $psize)->select();
        $count = $m->where($map)->count();
        $this->getPage($count, $psize, 'App-loader', '标签列表', 'App-search');
        $this->assign('cache', $cache);
        $this->display();
    }

    //CMS后台标签设置
    public function labelSet()
    {
        $id = I('id');
        $m = M('Shop_label');
        //设置面包导航，主加载器请配置
        $bread = array(
            '0' => array(
                'name' => '商城首页',
                'url' => U('Admin/Shop/index'),
            ),
            '1' => array(
                'name' => '标签列表',
                'url' => U('Admin/Shop/label'),
            ),
            '2' => array(
                'name' => '标签设置',
                'url' => $id ? U('Admin/Shop/lebelSet', array('id' => $id)) : U('Admin/Shop/lebelSet'),
            ),
        );
        $this->assign('breadhtml', $this->getBread($bread));
        //处理POST提交
        if (IS_POST) {
            $data = I('post.');
            $re = $id ? $m->save($data) : $m->add($data);
            if (FALSE !== $re) {
                $info['status'] = 1;
                $info['msg'] = '设置成功！';
            } else {
                $info['status'] = 0;
                $info['msg'] = '设置失败！';
            }
            $this->ajaxReturn($info);
        } else {
            if ($id) {
                $cache = $m->where('id=' . $id)->find();
                $this->assign('cache', $cache);
            }
            $this->display();
        }
    }

    public function labelDel()
    {
        $id = $_GET['id']; //必须使用get方法
        $m = M('Shop_label');
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

    //返现/佣金设置
    public function bonus()
    {
        $id = 1;
        $m = M('Shop_set');
        //设置面包导航，主加载器请配置
        $bread = array(
            '0' => array(
                'name' => '系统设置',
                'url' => U('Admin/Shop/index'),
            ),
            '1' => array(
                'name' => '返现佣金设置',
                'url' => U('Admin/Shop/bonus'),
            ),
        );
        $this->assign('breadhtml', $this->getBread($bread));
        //处理POST提交
        if (IS_POST) {
            //$data = I('post.');
            $data['total_commission_rate'] = I('total_commission_rate', 0, 'float');

            //一二三级佣金比例
            $data['role1rate'] = I('role1rate', 0, 'float');
            $data['role2rate'] = I('role2rate', 0, 'float');
            $data['role3rate'] = I('role3rate', 0, 'float');

            /* 2016年8月14日注释，小猫飞购不再使用
             * $data['tuanzhang_rate'] = I('tuanzhang_rate', 0, 'float');
            $data['supplier_rate'] = I('supplier_rate', 0, 'float');
            $data['buyer_rate'] = I('buyer_rate', 0, 'float');
            $data['fx_rate'] = I('fx_rate', 0, 'float');
            $data['fx1rate'] = I('fx1rate', 0, 'float');
            $data['fx2rate'] = I('fx2rate', 0, 'float');
            $data['fx3rate'] = I('fx3rate', 0, 'float');*/

            $data['tx_fee_rate'] = I('tx_fee_rate', 0, 'float');
            $data['tx_min_money'] = I('tx_min_money', 0, 'float');

            $old = $m->where('id=' . $id)->find();
            if ($old) {
                $re = $m->where('id=' . $id)->save($data);
                if (FALSE !== $re) {
                    $info['status'] = 1;
                    $info['msg'] = '设置成功！';
                } else {
                    $info['status'] = 0;
                    $info['msg'] = '设置失败！';
                }
            } else {
                $data['id'] = 1;
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
        $cache = $m->where('id=' . $id)->find();
        $this->assign('cache', $cache);
        $this->display();
    }
}