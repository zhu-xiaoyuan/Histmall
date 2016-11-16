<?php
namespace Admin\Controller;

class VipController extends BaseController
{

    public function _initialize()
    {
        //你可以在此覆盖父类方法
        parent::_initialize();
    }

    public function set()
    {
        $m = M('vip_set');
        $data = $m->find();
        if (IS_POST) {
            $post = I('post.');
            if ($post['isgift'] == 1) {
                $post['gift_detail'] = $post['gift_type'] . "," . $post['gift_money'] . "," . $post['gift_days'] . "," . $post['gift_usemoney'];
            }
            unset($post['gift_type']);
            unset($post['gift_money']);
            unset($post['gift_days']);
            unset($post['gift_usemoney']);
            $r = $data ? $m->where('id=' . $data['id'])->save($post) : $m->add($post);
            if (FALSE !== $r) {
                $info['status'] = 1;
                $info['msg'] = '设置成功！';
            } else {
                $info['status'] = 0;
                $info['msg'] = '设置失败！';
            }
            $this->ajaxReturn($info, "json");
        } else {
            //设置面包导航，主加载器请配置
            $bread = array(
                '0' => array(
                    'name' => '会员中心',
                    'url' => U('Admin/Vip/#'),
                ),
                '1' => array(
                    'name' => '会员设置',
                    'url' => U('Admin/Vip/set'),
                ),
            );
            $this->assign('breadhtml', $this->getBread($bread));
            $data = $m->find();
            if ($data['isgift'] == 1) {
                $gift = explode(",", $data['gift_detail']);
                $data['gift_type'] = $gift[0];
                $data['gift_money'] = $gift[1];
                $data['gift_days'] = $gift[2];
                $data['gift_usemoney'] = $gift[3];
            }
            $this->assign('data', $data);
            $this->display();
        }
    }

    // 获取层级
    public function vipTree()
    {
        $mvip = M('vip');
        $data = I('data');
        $id = I('id');
        $str = '<br>';
        $vipids = explode('-', $data);
        $vip = $mvip->where('id=' . $id)->find();
        if (count($vipids) <= 1) {
            $str .= "<div style='float:left;position:absolute'><img style='width:30px' src='" . $vip['headimgurl'] . "'/>" . "&nbsp&nbsp&nbsp&nbsp" . $vip['nickname'] . "(当前用户)" . "</div>";
        } else {
            foreach ($vipids as $k => $v) {
                # code...
                if ($k == 0) {
                } else {
                    $temp = $mvip->where('id=' . $v)->find();
                    $str .= "<div style='float:left;position:absolute'><img style='width:30px' src='" . $temp['headimgurl'] . "'/>" . "&nbsp&nbsp&nbsp&nbsp" . $temp['nickname'] . "<br>&nbsp&nbsp&nbsp&nbsp&nbsp&nbsp&nbsp&nbsp&nbsp↑<br></div>";
                    $str .= "<br><br><br>";
                }
            }
            $str .= "<div style='float:left;position:absolute'><img style='width:30px' src='" . $vip['headimgurl'] . "'/>" . "&nbsp&nbsp&nbsp&nbsp" . $vip['nickname'] . "(当前用户)" . "</div>";
        }

        $this->ajaxReturn(array('msg' => $str), "json");
    }

    // 层级树
    public function vipTrack()
    {
        // 获取模型
        $dvip = D('Vip');
        if (IS_POST) {
            $vipid = I('vipid');
            $cache = D('Vip')->getChildren($vipid);
            $str = '<ul>';
            // 组装返回数据
            if (count($cache) > 0) {
                $this->assign('cache', $cache);
                $str .= $this->fetch('Admin:Vip@vipTrackItem');
            }
            $str .= '</ul>';
            $this->ajaxReturn(array('msg' => $str, 'id' => $vipid), "json");
            exit();
        }

        $map['status'] = 1;
        $search = I('s_name') ? I('s_name') : '';
        if ($search) {
            $map['nickname|id|mobile'] = array('like', "%$search%");
            $this->assign('s_name', $search);
        }

        $p = $_GET['p'] ? $_GET['p'] : 1;
        $psize = self::$CMS['set']['pagesize'] ? self::$CMS['set']['pagesize'] : 20;
//        $count = M('vip')->where($map)->count();
        $page_array = ['p' => $p, 'psize' => $psize, 'map' => $map];
        $top = $dvip->getChildren(0, $page_array);

//        $this->getPage($count, $psize, 'App-loader', '对账历史', 'App-search');
        $this->assign('cache', $top);
        if ($p > 1) {
            if (empty($top)) {
                echo '';
            } else {
                $this->display('Vip_vipTrackItem');
            }
        } else {
            $this->display();
        }
    }

    // 获取个人信息
    public function vipInfo()
    {
        if (IS_AJAX) {
            $id = I('id');
            $mvip = D('Vip');
            $str = $mvip->getVipForMessage($id);
            if ($str) {
                $this->ajaxReturn(array('msg' => $str), "json");
            } else {
                $this->ajaxReturn(array('msg' => "通信失败"), "json");
            }
        }
    }

    // 设置
    public function vipReborn()
    {
        if (IS_AJAX) {
            $dvip = D('Vip');
            $id = I('id');
            $ppid = I('ppid');

            if ($ppid == $id) {
                $info['status'] = 0;
                $info['msg'] = "调配失败";
            }

            $re = $dvip->vipReborn($id, $ppid);
            if ($re) {
                $info['status'] = 1;
                $info['msg'] = "调配成功";
            } else {
                $info['status'] = 0;
                $info['msg'] = "调配失败";
            }
            $this->ajaxReturn($info);
        }
    }

    // Vip未分配会员列表
    public function vipRebornList()
    {
        //设置面包导航，主加载器请配置
        $bread = array(
            '0' => array(
                'name' => '可调配会员',
            ),
        );
        $this->assign('breadhtml', $this->getBread($bread));

        // 员工介入
        $temp = M('employee')->select();
        $employee = array();
        foreach ($temp as $k => $v) {
            $employee[$v['id']] = $v;
        }

        //绑定搜索条件与分页
        $m = M('vip');
        $p = $_GET['p'] ? $_GET['p'] : 1;
        $search = I('search') ? I('search') : '';
        if ($search) {
            $map['nickname|mobile'] = array('like', "%$search%");
            $this->assign('search', $search);
        }
        $psize = self::$CMS['set']['pagesize'] ? self::$CMS['set']['pagesize'] : 20;
        $map['plv'] = 1;
        $map['pid'] = 0;
        $map['isfx'] = 0;
        $map['total_xxlink'] = 0;
        //$map['employee']=0;
        $cache = $m->where($map)->page($p, $psize)->select();
        foreach ($cache as $k => $v) {
            $cache[$k]['levelname'] = M('vip_level')->where('id=' . $cache[$k]['levelid'])->getField('name');
            if ($v['isfxgd']) {
                $cache[$k]['fxname'] = '超级VIP';
            } else {
                if ($v['isfx']) {
                    $cache[$k]['fxname'] = $_SESSION['SHOP']['set']['fxname'];
                } else {
                    $cache[$k]['fxname'] = '会员';
                }
            }

            // 写入员工数据
            if ($v['employee']) {
                $cache[$k]['employee'] = $employee[$v['employee']]['nickname'];
            } else {
                $cache[$k]['employee'] = '无';
            }
        }
        $count = $m->where($map)->count();
        $this->getPage($count, $psize, 'App-loader', '会员列表', 'App-search');
        $this->assign('cache', $cache);
        $this->display();
    }


    // 设置
    public function vipAlloc()
    {
        if (IS_AJAX) {
            $dvip = D('Vip');
            $id = I('vipid');
            $eid = I('empid');
            $employee = M('employee')->where(array('id' => $eid))->find();
            $vip = M('vip')->where(array('id' => $id, 'plv' => 1))->find();

            if ($employee && $vip) {
                $re = $dvip->setEmployee($id, $eid);
                if ($re) {
                    $info['status'] = 1;
                    $info['msg'] = "员工账户绑定成功";
                } else {
                    $info['status'] = 0;
                    $info['msg'] = "员工账户绑定失败";
                }
                //$info['msg'] = json_encode($re);

            } else {
                $info['status'] = 0;
                $info['msg'] = "员工账户不存在";
            }
            $this->ajaxReturn($info);

        }
    }

    // Vip未分配会员列表
    public function vipAllocList()
    {
        //设置面包导航，主加载器请配置
        $bread = array(
            '0' => array(
                'name' => '会员分配中心',
                'url' => U('Admin/Vip/#'),
            ),
        );
        $this->assign('breadhtml', $this->getBread($bread));
        // 员工介入
        $temp = M('employee')->select();
        $employee = array();
        foreach ($temp as $k => $v) {
            $employee[$v['id']] = $v;
        }
        //绑定搜索条件与分页
        $m = M('vip');
        $p = $_GET['p'] ? $_GET['p'] : 1;
        $search = I('search') ? I('search') : '';
        if ($search) {
            $map['nickname|mobile'] = array('like', "%$search%");
            //$map['mobile'] = array('like', "%$search%");
            //$map['_logic'] = 'OR';
            $this->assign('search', $search);
        }
        $psize = self::$CMS['set']['pagesize'] ? self::$CMS['set']['pagesize'] : 20;
        $map['plv'] = 1;
        //$map['employee']=0;
        $cache = $m->where($map)->page($p, $psize)->select();
        foreach ($cache as $k => $v) {
            $cache[$k]['levelname'] = M('vip_level')->where('id=' . $cache[$k]['levelid'])->getField('name');
            if ($v['isfxgd']) {
                $cache[$k]['fxname'] = '超级VIP';
            } else {
                if ($v['isfx']) {
                    $cache[$k]['fxname'] = $_SESSION['SHOP']['set']['fxname'];
                } else {
                    $cache[$k]['fxname'] = '会员';
                }
            }

            // 写入员工数据
            if ($v['employee']) {
                $cache[$k]['employee'] = $employee[$v['employee']]['nickname'];
            } else {
                $cache[$k]['employee'] = '无';
            }
        }
        $count = $m->where($map)->count();
        $this->getPage($count, $psize, 'App-loader', '会员列表', 'App-search');
        $this->assign('cache', $cache);
        $this->display();
    }

    // VIP列表
    public function vipList()
    {
        //设置面包导航，主加载器请配置
        $bread = array(
            '0' => array(
                'name' => '会员中心',
                'url' => U('Admin/Vip/#'),
            ),
            '1' => array(
                'name' => '会员列表',
                'url' => U('Admin/Vip/vipList'),
            ),
        );
        $state = I('status');
        $this->assign('state',$state);
        $this->assign('breadhtml', $this->getBread($bread));
        // 员工介入
//        $temp = M('employee')->select();
//        $employee = array();
//        foreach ($temp as $k => $v) {
//            $employee[$v['id']] = $v;
//        }
        //绑定搜索条件与分页
        $m = M('vip');
        $p = $_GET['p'] ? $_GET['p'] : 1;
        $search = I('search') ? I('search') : '';
        $plv = I('plv') ? I('plv') : 0;
        if ($search) {
            $map['id|nickname|mobile'] = array('like', "%$search%");
            $this->assign('search', $search);
        }
        if ($plv) {
            $map['plv'] = $plv;
            $this->assign('plv', $plv);
        }
        if($state == '1'){
            $map['role'] = array('GT','0');
        }else{
            $map['role'] = array('EQ','0');
        }
        $psize = self::$CMS['set']['pagesize'] ? self::$CMS['set']['pagesize'] : 20;
        $cache = $m->where($map)->order('id desc')->page($p, $psize)->select();
        foreach ($cache as $k => $v) {
            //判断会员角色
            $cache[$k]['rolename'] = $this->isRole($cache[$k]['role']);
            //判断推广人员管理的学习
            if($cache[$k]['role'] > 0 && $cache[$k]['role'] < 3){
                $wehre['role_1_id|role_2_id'] = $cache[$k]['id'];
                $cache[$k]['manageschool'] = M('location_school')->field('school_name')->where($wehre)->find();
            }elseif($cache[$k]['role'] == 3){
                $wehre['role_2_id'] = $cache[$k]['role_2_id'];
                $cache[$k]['manageschool'] = M('location_school')->field('school_name')->where($wehre)->find();
            }
            //school_id 为零 备注内容为学校名称(此学校为开通)
            if($cache[$k]['school_id'] == 0){
                $cache[$k]['schoolname'] = $cache[$k]['remark_school'];
            }else{
                $cache[$k]['schoolname'] = M('location_school')->where('id=' . $cache[$k]['school_id'])->getField('school_name');
            }
        }
        $count = $m->where($map)->count();
        $this->getPage($count, $psize, 'App-loader', '会员列表', 'App-search');
        $this->assign('cache', $cache);
        $this->display();
    }

    protected function isRole($role){
        if($role == 1){
            return "总监";
        }else if($role == 2){
            return "VIA";
        }else if($role == 3){
            return "推广";
        }
    }

    //vip 编辑
    public function vipSet()
    {
        $id = intval(I('id'));
        $m = M('Vip');
        //dump($m);
        //设置面包导航，主加载器请配置
        $bread = array(
            '0' => array(
                'name' => '会员中心',
                'url' => U('Admin/Vip/#'),
            ),
            '1' => array(
                'name' => '会员列表',
                'url' => U('Admin/Vip/vipList'),
            ),
            '2' => array(
                'name' => '会员编辑',
                'url' => U('Admin/Vip/vipSet', array('id' => $id)),
            ),
        );
        $this->assign('breadhtml', $this->getBread($bread));
        //处理POST提交
        if (IS_POST) {
            //die('aa');
            $data = I('post.');
            if ($id) {
                //TODO 获取需要更新的字段
//                dump($data);exit;
                $mData = [];
                $mData['nickname'] = $data['nickname'];
                $mData['name'] = $data['name'];
                $mData['mobile'] = $data['mobile'];
                $mData['wx_code'] = $data['wx_code'];
                $mData['email'] = $data['email'];

                $mData['txname'] = $data['txname'];
                $mData['txmobile'] = $data['txmobile'];
                $mData['txyh'] = $data['txyh'];
                $mData['txfh'] = $data['txfh'];
                $mData['txszd'] = $data['txszd'];
                $mData['txcard'] = $data['txcard'];
                if($data['is_promotion'] == 1){
                    $mData['role'] = 3;
                }elseif($data['is_promotion'] == 0){
                    $mData['role'] = 0;
                }

                $oldVip = $m->where('id=' . $id)->find();

                $mData['isfx'] = intval($data['isfx']) == 0 ? 0 : 1;
                if ($oldVip) {
                    if ($data['isfx'] && empty($oldVip['reg_fx_time'])) {
                        //第一次成为团长
                        $mData['reg_fx_time'] = time();
                    }
                }

                $mData['is_tuanzhang'] = intval($data['is_tuanzhang']) == 0 ? 0 : 1;
                if ($oldVip) {
                    if ($data['is_tuanzhang'] && empty($oldVip['reg_tuanzhang_time'])) {
                        //第一次成为团长
                        $mData['reg_tuanzhang_time'] = time();
                    }
                }
                $re = $m->where('id=' . $id)->save($mData);
                if($data['is_promotion'] == 0){
                    $setP = $m->where(array('role_3_id'=>$id))->setField(array('role_3_id'=>'0'));
                }
                if (FALSE !== $re) {
                    $info['status'] = 1;
                    $info['msg'] = '设置成功！';
                } else {
                    $info['status'] = 0;
                    $info['msg'] = '设置失败！';
                }
            } else {
                $info['status'] = 0;
                $info['msg'] = '未获取会员ID！';
            }
            $this->ajaxReturn($info);
        }

        //处理编辑界面
        if ($id) {
            $cache = $m->where('id=' . $id)->find();
            $supplierId = $cache['supplier_id'];
            if ($supplierId > 0) {
                $cache['supplier'] = M('supplier')->where('id=' . $supplierId)->find();
            }
            if($cache['role'] > 0 && $cache['role'] < 3){
                $wehre['role_1_id|role_2_id'] = $cache['id'];
                $manageschool = M('location_school')->field('school_name')->where($wehre)->select();
            }elseif($cache['role'] == 3){
                $wehre['role_2_id'] = $cache['role_2_id'];
                $manageschool = M('location_school')->field('school_name')->where($wehre)->select();
            }
            //school_id 为零 备注内容为学校名称(此学校为开通)
            if($cache['school_id'] == 0){
                $cache['schoolname'] = $cache['remark_school'];
            }else{
                $cache['schoolname'] = M('location_school')->where('id=' . $cache['school_id'])->getField('school_name');
            }

            $this->assign('manageschool',$manageschool);
            $this->assign('status',I('status'));
            $this->assign('cache', $cache);
        } else {
            $info['status'] = 0;
            $info['msg'] = '未获取会员ID！';
            $this->ajaxReturn($info);
        }
        $this->display();
    }

    //CMS后台商品设置
    public function vipFxtj()
    {
        header("Content-type: text/html; charset=utf-8");
        $id = I('id');
        $mvip = M('Vip');
        //dump($m);
        //设置面包导航，主加载器请配置
        //		$bread=array(
        //			'0'=>array(
        //				'name'=>'会员中心',
        //				'url'=>U('Admin/Vip/#')
        //			),
        //			'1'=>array(
        //				'name'=>'会员列表',
        //				'url'=>U('Admin/Vip/vipList')
        //			),
        //			'1'=>array(
        //				'name'=>'会员编辑',
        //				'url'=>U('Admin/Vip/vipSet',array('id'=>$id))
        //			)
        //		);
        //		$this->assign('breadhtml',$this->getBread($bread));

        $vip = $mvip->where('id=' . $id)->find();
        if (!$vip) {
            $this->die('不存在此用户！');
        }
        echo '会员分销统计预估开始：<br><br>';
        echo '<br><br>*********************************************<br><br>';
        echo '会员名：' . $vip['nickname'] . '<br>';
        echo '会员层级：' . $vip['plv'] . '<br>';
        echo '会员路由：' . $vip['path'] . '<br>';
        echo '会员余额：' . $vip['money'] . '<br>';
        echo '<br><br>*********************************************<br><br>';
        echo '第一步：取出3层下线所有用户<br><br>';
        $maxlv = $vip['plv'] + 3;
        $likepath = $vip['path'] . '-' . $vip['id'];
        echo '层级条件：最大层级不超过' . $maxlv . '<br>';
        echo '路由条件：' . $likepath . '<br>';
        //两次模糊查询
        //1:取出第一层，2:取出其他层
        $firstlv = $vip['plv'] + 1;
        $firstpath = $likepath;
        $mapfirst['plv'] = $firstlv;
        $mapfirst['path'] = $firstpath;
        $firstsub = $mvip->field('id,plv,path,nickname')->where($mapfirst)->select();
        if ($firstsub) {
            //模糊查询第二层和第三层
            $maplike['plv'] = array('gt', $firstlv);
            $maplike['plv'] = array('elt', $maxlv);
            $maplike['path'] = array('like', $likepath . '-%');
            $sesendsub = $mvip->field('id,plv,path,nickname')->where($maplike)->select();
            //dump($firstsub);
            //dump($sesendsub);
            //合并两个数组
            if ($sesendsub) {
                $sub = array_merge($firstsub, $sesendsub);
            } else {
                $sub = $firstsub;
            }
            echo '3层下线总数：' . count($sub) . ' 人<br>';
            echo '列出所有下线会员：<br>';
            dump($sub);
            echo '将下线会员按照层级与会员ID重新整理：<br>';
            $subarr = array();
            foreach ($sub as $v) {
                //按层级分组
                $subarr[$v['plv']] = $subarr[$v['plv']] . $v['id'] . ',';
                //array_push($subarr[$v['plv']],$v['id']);
            }
            dump($subarr);
            echo '再次整理下线分层数组：<br>';
            $subarr = array_values($subarr);
            dump($subarr);
            echo '<br><br>*********************************************<br><br>';
            echo '第二步：取出系统佣金比例设置<br><br>';
            $shopset = M('Shop_set')->find();
            $morder = M('Shop_order');
            $fx1rate = $shopset['fx1rate'];
            $fx2rate = $shopset['fx2rate'];
            $fx3rate = $shopset['fx3rate'];
            echo '第一层分销比例：' . $fx1rate . '%<br>';
            echo '第二层分销比例：' . $fx2rate . '%<br>';
            echo '第三层分销比例：' . $fx3rate . '%<br>';
            echo '<br><br>*********************************************<br><br>';
            echo '第三步：逐级分析算出分销佣金<br><br>';
            if ($fx1rate && $subarr[0]) {
                $tmprate = $fx1rate;
                $tmplv = $data['plv'] + 1;
                $maporder['ispay'] = 1;
                $maporder['status'] = array('in', array('2', '3'));
                $maporder['vipid'] = array('in', in_parse_str($subarr[0]));
                echo '第一层分销佣金统计开始：<br>';
                echo '列出订单检索条件：<br>';
                echo '订单支付条件：已支付<br>';
                echo '订单状态条件：已支付或已发货<br>';
                echo '订单购买会员ID：' . $subarr[0] . '<br><br>';
                $tmpod = $morder->field('id,oid,vipid,vipname,payprice,paytime')->where($maporder)->select();
                if ($tmpod) {
                    $tmpodtotal = count($tmpod);
                    echo '根据条件检索出：' . $tmpodtotal . '个订单，列出所有结果<br>';
                    dump($tmpod);
                } else {
                    echo '没有第一层的订单，支付总额为0<br>';
                }

                $tmptotal = $morder->where($maporder)->sum('payprice');
                if (!$tmptotal) {
                    $tmptotal = 0;
                }
                echo '第一层会员所有订单合计支付总额：' . $tmptotal . '元<br>';
                $fx1total = $tmptotal * ($tmprate / 100);
                echo '第一层会员所有订单应贡献佣金[公式=支付总额*(第一层分销率/100)]：' . $fx1total . '元<br>';
                echo '第一层统计结束。<br><br>';
            } else {
                $fx1total = 0;
                echo '不存在第一层会员，该层分销佣金为0。<br><br>';
            }
            if ($fx2rate && $subarr[1]) {
                $tmprate = $fx2rate;
                $tmplv = $data['plv'] + 2;
                $maporder['ispay'] = 1;
                $maporder['status'] = array('in', array('2', '3'));
                $maporder['vipid'] = array('in', in_parse_str($subarr[1]));
                echo '第二层分销佣金统计开始：<br>';
                echo '列出订单检索条件：<br>';
                echo '订单支付条件：已支付<br>';
                echo '订单状态条件：已支付或已发货<br>';
                echo '订单购买会员ID：' . $subarr[1] . '<br><br>';
                $tmpod = $morder->field('id,oid,vipid,vipname,payprice,paytime')->where($maporder)->select();
                if ($tmpod) {
                    $tmpodtotal = count($tmpod);
                    echo '根据条件检索出：' . $tmpodtotal . '个订单，列出所有结果<br>';
                    dump($tmpod);
                } else {
                    echo '没有第二层的订单，支付总额为0<br>';
                }

                $tmptotal = $morder->where($maporder)->sum('payprice');
                if (!$tmptotal) {
                    $tmptotal = 0;
                }
                echo '第二层会员所有订单合计支付总额：' . $tmptotal . '元<br>';
                $fx2total = $tmptotal * ($tmprate / 100);
                echo '第二层会员所有订单应贡献佣金[公式=支付总额*(第二层分销率/100)]：' . $fx2total . '元<br>';
                echo '第二层统计结束。<br><br>';
            } else {
                $fx2total = 0;
                echo '不存在第二层会员，该层分销佣金为0。<br><br>';
            }
            if ($fx3rate && $subarr[2]) {
                $tmprate = $fx3rate;
                $tmplv = $data['plv'] + 3;
                $maporder['ispay'] = 1;
                $maporder['status'] = array('in', array('2', '3'));
                $maporder['vipid'] = array('in', in_parse_str($subarr[2]));
                echo '第三层分销佣金统计开始：<br>';
                echo '列出订单检索条件：<br>';
                echo '订单支付条件：已支付<br>';
                echo '订单状态条件：已支付或已发货<br>';
                echo '订单购买会员ID：' . $subarr[2] . '<br><br>';
                $tmpod = $morder->field('id,oid,vipid,vipname,payprice,paytime')->where($maporder)->select();
                if ($tmpod) {
                    $tmpodtotal = count($tmpod);
                    echo '根据条件检索出：' . $tmpodtotal . '个订单，列出所有结果<br>';
                    dump($tmpod);
                } else {
                    echo '没有第三层的订单，支付总额为0<br>';
                }

                $tmptotal = $morder->where($maporder)->sum('payprice');
                if (!$tmptotal) {
                    $tmptotal = 0;
                }
                echo '第三层会员所有订单合计支付总额：' . $tmptotal . '元<br>';
                $fx3total = $tmptotal * ($tmprate / 100);
                echo '第三层会员所有订单应贡献佣金[公式=支付总额*(第三层分销率/100)]：' . $fx3total . '元<br>';
                echo '第三层统计结束。<br><br>';
            } else {
                $fx3total = 0;
                echo '不存在第三层会员，该层分销佣金为0。<br><br>';
            }
            $totalfxmoney = number_format(($fx1total + $fx2total + $fx3total), 2);
            echo '当前会员的代收佣金预估值为[公式=第一层贡献佣金+第二层贡献佣金+第三层贡献佣金，保留2位小数格式化处理]：' . $totalfxmoney . '<br><br>';
            echo '**********************本次分析结束！*****************';

        } else {
            echo '此会员没有下线成员，代收佣金为0，直接结束统计分析！';
        }

    }

    public function vipExport()
    {
        $id = I('id');
        if ($id) {
            $map['id'] = array('in', in_parse_str($id));
        }

        $data = M('Vip')->where($map)->select();
        foreach ($data as $k => $v) {
            $data[$k]['ctime'] = $v['ctime'] ? date('Y-m-d H:i:s', $v['ctime']) : '';
            $data[$k]['cctime'] = $v['cctime'] ? date('Y-m-d H:i:s', $v['cctime']) : '';
            $data[$k]['sex'] = $v['sex'] == 1 ? '男' : $v['sex'] == 2 ? '女' : '';
            $data[$k]['subscribe'] = $v['subscribe'] == 1 ? '已关注' : '未关注';
            $data[$k]['buy_bonus_money'] = $v['total_got_bonus'] . '/' . $v['total_bonus_amount'];
        }
        $title = array('id' => '会员ID', 'nickname' => '微信昵称', 'sex' => '性别', 'name' => '真实姓名', 'mobile' => '真实电话', 'email' => 'E-mail', 'money' => '账户余额', 'total_yj' => '分销佣金', 'buy_bonus_money' => '消费全返', 'city' => '城市', 'province' => '省份', 'subscribe' => '关注情况');
        export_excel($data, $title, '会员数据' . date('Y-m-d H:i:s', time()));
    }

    public function message()
    {
        //设置面包导航，主加载器请配置
        $bread = array(
            '0' => array(
                'name' => '会员中心',
                'url' => U('Admin/Vip/#'),
            ),
            '1' => array(
                'name' => '消息管理',
                'url' => U('Admin/Vip/message'),
            ),
        );
        $this->assign('breadhtml', $this->getBread($bread));
        //绑定搜索条件与分页
        $m = M('vip_message');
        $p = $_GET['p'] ? $_GET['p'] : 1;
        $search = I('search') ? I('search') : '';
        if ($search) {
            $map['title'] = array('like', "%$search%");
            $this->assign('search', $search);
        }
        $psize = self::$CMS['set']['pagesize'] ? self::$CMS['set']['pagesize'] : 20;
        $cache = $m->where($map)->order('id desc')->page($p, $psize)->select();
        $count = $m->where($map)->count();
        $this->getPage($count, $psize, 'App-loader', '消息管理', 'App-search');
        $this->assign('cache', $cache);
        $this->display();
    }

    public function messageSet()
    {
        $id = I('id');
        $m = M('vip_message');
        //设置面包导航，主加载器请配置
        $bread = array(
            '0' => array(
                'name' => '会员中心',
                'url' => U('Admin/Vip/#'),
            ),
            '1' => array(
                'name' => '消息管理',
                'url' => U('Admin/Vip/message'),
            ),
            '2' => array(
                'name' => '消息设置',
                'url' => $id ? U('Admin/Vip/messageSet', array('id' => $id)) : U('Admin/Vip/messageSet'),
            ),
        );
        $this->assign('breadhtml', $this->getBread($bread));
        //处理POST提交
        if (IS_POST) {
            $data = I('post.');
            $data['ctime'] = time();
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
        if (I('pids')) {
            $cache['pids'] = I('pids');
            $this->assign('cache', $cache);
        }
        $this->display();
    }

    public function mailSet()
    {
        $pids = I('pids');
        //设置面包导航，主加载器请配置
        $bread = array(
            '0' => array(
                'name' => '会员中心',
                'url' => U('Admin/Vip/#'),
            ),
            '1' => array(
                'name' => '会员列表',
                'url' => U('Admin/Vip/viplist'),
            ),
            '2' => array(
                'name' => '发送邮件',
                'url' => U('Admin/Vip/messageSet'),
            ),
        );
        $this->assign('breadhtml', $this->getBread($bread));
        //处理POST提交
        if (IS_POST) {
            $m = M('vip');
            $data = I('post.');
            $id_arr = explode(',', $data['pids']);
            foreach ($id_arr as $k => $v) {
                $mail_addr = $m->where('id=' . $v)->getField('email');
                if ($mail_addr != '') {
                    think_send_mail($mail_addr, '系统会员', $data['title'], $data['content']);
                }
            }

            $info['status'] = 1;
            $info['msg'] = ' 发送成功！';

            $this->ajaxReturn($info);
        }
        $this->assign('pids', $pids);
        $this->display();
    }

    public function messageDel()
    {
        $id = trim($_GET['id'],','); //必须使用get方法
        $m = M('vip_message');
        if (!id) {
            $info['status'] = 0;
            $info['msg'] = 'ID不能为空!';
            $this->ajaxReturn($info);
        }
        $re = $m->where('id in ('.$id.')')->delete();
        if ($re) {
            //删除消息浏览记录
            M('vip_log')->where('type=5 and opid in (' . $id . ')')->delete();
            $info['status'] = 1;
            $info['msg'] = '删除成功!';
        } else {
            $info['status'] = 0;
            $info['msg'] = '删除失败!';
        }
        $this->ajaxReturn($info);
    }

    public function card()
    {
        //设置面包导航，主加载器请配置
        $bread = array(
            '0' => array(
                'name' => '会员中心',
                'url' => U('Admin/Vip/#'),
            ),
            '1' => array(
                'name' => '卡券列表',
                'url' => U('Admin/Vip/card'),
            ),
        );
        $this->assign('breadhtml', $this->getBread($bread));
        //绑定搜索条件与分页
        $this->assign('status', $status);
        $m = M('vip_card');
        $p = $_GET['p'] ? $_GET['p'] : 1;
        $search = I('search') ? I('search') : '';
        if ($search) {
            $map['cardno'] = array('like', "%$search%");
            $this->assign('search', $search);
        }
        $type = I('type');
        if ($type) {
            $map['type'] = $type;
            $this->assign('type', $type);
        }
        $psize = self::$CMS['set']['pagesize'] ? self::$CMS['set']['pagesize'] : 20;
        $cache = $m->where($map)->order('id desc')->page($p, $psize)->select();
        $count = $m->where($map)->count();
        $this->getPage($count, $psize, 'App-loader', '卡券列表', 'App-search');
        $this->assign('cache', $cache);
        $this->display();
    }

    public function cardSet()
    {
        $m = M('vip_card');
        //设置面包导航，主加载器请配置
        $bread = array(
            '0' => array(
                'name' => '会员中心',
                'url' => U('Admin/Vip/#'),
            ),
            '1' => array(
                'name' => '充值卡列表',
                'url' => U('Admin/Vip/card'),
            ),
            '2' => array(
                'name' => '充值卡设置',
                'url' => U('Admin/Vip/cardSet'),
            ),
        );
        $this->assign('breadhtml', $this->getBread($bread));
        //处理POST提交
        if (IS_POST) {
            $data = I('post.');
            $data['ctime'] = time();
            if ($data['usetime'] != '') {
                $timeArr = explode(" - ", $data['usetime']);
                $data['stime'] = strtotime($timeArr[0]);
                $data['etime'] = strtotime($timeArr[1]);
            }
            $num = $data['num'];
            unset($data['usetime']);
            unset($data['num']);
            for ($i = 0; $i < $num; $i++) {
                $cardnopwd = $this->getCardNoPwd();
                $data['cardno'] = $cardnopwd['no'];
                $data['cardpwd'] = $cardnopwd['pwd'];
                $r = $m->add($data);
            }
            if ($r) {
                $info['status'] = 1;
                $info['msg'] = '设置成功！';
            } else {
                $info['status'] = 0;
                $info['msg'] = '设置失败！';
            }
            $this->ajaxReturn($info);
        } else {
            $this->display();
        }

    }

    public function cardDel()
    {
        $id = $_GET['id']; //必须使用get方法
        $m = M('vip_card');
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

    private function getCardNoPwd()
    {
        $dict_no = "0123456789";
        $length_no = 10;
        $dict_pwd = "ABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789";
        $length_pwd = 10;
        $card['no'] = "";
        $card['pwd'] = "";
        for ($i = 0; $i < $length_no; $i++) {
            $card['no'] .= $dict_no[rand(0, (strlen($dict_no) - 1))];
        }
        for ($i = 0; $i < $length_pwd; $i++) {
            $card['pwd'] .= $dict_pwd[rand(0, (strlen($dict_pwd) - 1))];
        }
        return $card;
    }

    public function sendCard()
    {
        $post = I('post.');
        $m = M('vip_card');
        if ($post['vipid'] == '') {
            $info['status'] = 0;
            $info['msg'] = '请输入发送会员ID！';
            $this->ajaxReturn($info);
        }
        if (!M('vip')->where('id=' . $post['vipid'])->find()) {
            $info['status'] = 0;
            $info['msg'] = '该会员不存在！';
            $this->ajaxReturn($info);
        }
        $data['vipid'] = $post['vipid'];
        $data['status'] = 1;
        $re = $m->where('id=' . $post['cardid'])->save($data);
        if ($re) {
            $info['status'] = 1;
            $info['msg'] = '发送成功!';
        } else {
            $info['status'] = 0;
            $info['msg'] = '发送失败!';
        }
        $this->ajaxReturn($info);
    }

    //CMS后台会员等级列表
    public function level()
    {
        //设置面包导航，主加载器请配置
        $bread = array(
            '0' => array(
                'name' => '会员中心',
                'url' => U('Admin/Vip/#'),
            ),
            '1' => array(
                'name' => '分组列表',
                'url' => U('Admin/Vip/level'),
            ),
        );
        $this->assign('breadhtml', $this->getBread($bread));
        //绑定搜索条件与分页
        $m = M('Vip_level');
        $p = $_GET['p'] ? $_GET['p'] : 1;
        $name = I('name') ? I('name') : '';
        if ($name) {
            $map['name'] = array('like', "%$name%");
            $this->assign('name', $name);
        }
        $psize = self::$CMS['set']['pagesize'] ? self::$CMS['set']['pagesize'] : 20;
        $cache = $m->where($map)->order('exp')->page($p, $psize)->select();
        $count = $m->where($map)->count();
        $this->getPage($count, $psize, 'App-loader', '分组列表', 'App-search');
        $this->assign('cache', $cache);
        $this->display();
    }

    //CMS后台会员等级设置
    public function levelSet()
    {
        $id = I('id');
        $m = M('vip_level');
        //设置面包导航，主加载器请配置
        $bread = array(
            '0' => array(
                'name' => '会员中心',
                'url' => U('Admin/Vip/#'),
            ),
            '1' => array(
                'name' => '分组列表',
                'url' => U('Admin/Vip/level'),
            ),
            '2' => array(
                'name' => '分组设置',
                'url' => $id ? U('Admin/Vip/levelSet', array('id' => $id)) : U('Admin/Vip/levelSet'),
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

    public function levelDel()
    {
        $id = $_GET['id']; //必须使用get方法
        $m = M('Vip_level');
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

    public function cardExport()
    {
        $id = I('id');
        $type = I('type');
        if ($id) {
            $map['id'] = array('in', in_parse_str($id));
        } else {
            $map['type'] = $type;
        }
        $data = M('vip_card')->where($map)->field('id,type,cardno,cardpwd,status')->select();
        foreach ($data as $k => $v) {
            switch ($v['type']) {
                case 1:
                    $data[$k]['type'] = "充值卡";
                    break;
                case 2:
                    $data[$k]['type'] = "代金券";
                    break;
            }
            switch ($v['status']) {
                case 0:
                    $data[$k]['status'] = "可制作";
                    break;
                case 1:
                    $data[$k]['status'] = "已发放";
                    break;
                case 2:
                    $data[$k]['status'] = "已使用";
                    break;
            }
        }
        $title = array('id' => 'ID', 'type' => '类型', 'cardno' => '卡号', 'cardpwd' => '卡密', 'status' => '状态');
        export_excel($data, $title, '卡券数据');
    }

    //CMS后台Vip提现订单
    public function txorder()
    {
        //设置面包导航，主加载器请配置
        $bread = array(
            '0' => array(
                'name' => '会员中心',
                'url' => U('Admin/Vip/#'),
            ),
            '1' => array(
                'name' => '提现订单',
                'url' => U('Admin/Vip/txorder'),
            ),
        );
        $this->assign('breadhtml', $this->getBread($bread));
        $status = I('status');
        $this->assign('status', $status);
        if ($status || $status == '0') {
            $map['vip_tx.status'] = $status;
        }
        $this->assign('status', $status);
        //绑定搜索条件与分页
        $m = M('Vip_tx');
        $p = $_GET['p'] ? $_GET['p'] : 1;
        $name = I('name') ? I('name') : '';
        if ($name) {
            //提现人姓名
            $map['vip_tx.txname'] = array('like', "%$name%");
            $this->assign('name', $name);
        }
        $psize = self::$CMS['set']['pagesize'] ? self::$CMS['set']['pagesize'] : 20;
        $cache = $m->where($map)->page($p, $psize)
            ->join("left join vip on vip.id=vip_tx.vipid")
            ->field("vip.money,vip_tx.*")
            ->order('vip_tx.id desc')
            ->select();
        $count = $m->where($map)->count();
        $this->getPage($count, $psize, 'App-loader', '会员提现订单', 'App-search');
        $this->assign('cache', $cache);
        $this->display();
    }


    public function moneyDetail()
    {
        //设置面包导航，主加载器请配置
        $bread = array(
            '0' => array(
                'name' => '会员中心',
                'url' => U('Admin/Vip/#'),
            ),
            '1' => array(
                'name' => '提现订单',
                'url' => U('Admin/Vip/txorder'),
            ),
        );
        $this->assign('breadhtml', $this->getBread($bread));

        $vip_id = I('id', 0, 'int');
        //日志类型 1获取注册验证码，2会员签到，3会员登陆，4会员注册，5消息读取（每条消息只记录一次），6充值卡充值，7在线充值（status0请求1成功），8购买商品，9后台管理员充值
        //账户余额明细包括：提现（Vip_tx、Vip_wxtx）、余额支付（suplier_order、shop_order）、返现（bonus_detail_record）、退款（shop_order）、充值（Vip_log）。具体可以看App/VipController.class.php中的moneyItem方法
        $result = M('vip_tx')->field("'提现' as action,txprice-2*txprice as money,txtime as time")->where("status=2 and vipid=$vip_id")
            ->union("select '微信提现' as action,txprice-2*txprice as money,txtime as time from vip_wxtx where status=2 and vip_id=$vip_id ")
            ->union("select '商盟购物' as action,total_price-2*total_price as money,end_time as time from supplier_order where status=2 and vip_buyer_id=$vip_id")
            ->union("select '商盟销售' as action,total_price as money,end_time as time from supplier_order where status=2 and vip_seller_id=$vip_id")
            ->union("select '商城购物' as action,totalprice-2*totalprice as money,etime as time from shop_order where status=5 and vipid=$vip_id")
            ->union("select '返现' as action,money as money,create_time as time from bonus_detail_record where vip_id=$vip_id")
            ->union("select '充值' as action,money as money,ctime as time from vip_log where type=7 and vipid=$vip_id")
            ->union("select '提现' as action,money as money,ctime as time from vip_log_tx where vipid=$vip_id")
            ->select();
        usort($result, 'sort_by_time');
//        dump($result);
//        exit;


        $this->assign('cache', $result);
        $this->display();
    }

    public function txorderOk()
    {

        $options['appid'] = self::$SYS['set']['wxappid'];
        $options['appsecret'] = self::$SYS['set']['wxappsecret'];
        $wx = new \Util\Wx\Wechat($options);

        $arr = array_filter(explode(',', $_GET['id'])); //必须使用get方法
        $m = M('Vip_tx');
        $mlog = M('Vip_message');
        $mvip = M('Vip');

        $err = TRUE;
        foreach ($arr as $k => $v) {
            if ($v) {
                D('EndTx')->endtx($v, '银行卡提现');
//                $old = $m->where('id=' . $v)->find();
//                $old['status'] = 2;
//                $old['txtime'] = time();
//                $rv = $m->save($old);
//                if ($rv !== FALSE) {
//                    $data_msg['pids'] = $old['vipid'];
//                    $data_msg['title'] = "亲爱的用户，提现已完成！" . $old['txprice'] . self::$SHOP['set']['yjname'] . "已成功发放到您的提现帐户里面了！";
//                    $data_msg['content'] = "提现订单编号：" . $old['id'] . "<br><br>提现申请" . self::$SHOP['set']['yjname'] . "：" . $old['txprice'] . "<br><br>提现完成时间：" . date('Y-m-d H:i', $old['txtime']) . "<br><br>您的提现申请已完成，如有异常请联系客服！";
//                    $data_msg['ctime'] = time();
//
//                    $vip = $mvip->where(array('id' => $old['vipid']))->find();
//
//                    //提现日志
//                    $log['ip']='';
//                    $log['vipid']=$vip['id'];
//                    $log['openid']=$vip['openid'];
//                    $log['nickname']=$vip['nickname'];
//                    $log['mobile']=$vip['mobile'];
//                    $log['event']='银行卡提现';
//                    $log['money']=$old['txprice'];
//                    $log['fee']=$old['tx_fee'];
//                    $log['tocard']= 1; //提现到哪里，1：提现到银行卡，2：提现到余额，3：提现到微信钱包，4：提现到支付宝
//                    $log['ctime']=time();
//                    $log['tx_id']=$old['id'];
//                    M('vip_log_tx')->add($log);
//
//                    // 发送信息===============
//                    $wechatTemplate = D('WechatTemplate');
//                    $wechatTemplate->sendMessage_WithdrawSuccess(['to_user'=>$vip['openid'],'money'=>$old['txprice'],'time'=>time(),'type'=>'wx/bank']);
//
//
////                    $customer = M('Wx_customer')->where(array('type' => 'tx2'))->find();
////                    $vip = $mvip->where(array('id' => $old['vipid']))->find();
////                    $msg = array();
////                    $msg['touser'] = $vip['openid'];
////                    $msg['msgtype'] = 'text';
////                    $str = $customer['value'];
////                    $msg['text'] = array('content' => $str);
////                    $ree = $wx->sendCustomMessage($msg);
//                    // 发送消息完成============
//
//                    $rmsg = $mlog->add($data_msg);
//                } else {
//                    $err = FALSE;
//                }
            } else {
                $err = FALSE;
            }
        }
        if ($err) {
            $info['status'] = 1;
            $info['msg'] = '批量设置成功!';
        } else {
            $info['status'] = 0;
            $info['msg'] = '批量设置可能存在部分失败，请刷新后重新尝试!';
        }
        $this->ajaxReturn($info);
    }

    public function txorderCancel()
    {
        $id = I('id');
        if (!$id) {
            $info['status'] = 0;
            $info['msg'] = '未正常获取ID数据！';
            $this->ajaxReturn($info);
        }
        $m = M('Vip_tx');
        $mvip = M('Vip');
        $mlog = M('Shop_order_log');
        $old = $m->where('id=' . $id)->find();
        if (!$old) {
            $info['status'] = 0;
            $info['msg'] = '未正常获取提现订单数据！';
            $this->ajaxReturn($info);
        }
        if ($old['status'] != 1) {
            $info['status'] = 0;
            $info['msg'] = '只可以操作新申请订单！';
            $this->ajaxReturn($info);
        }
        $vip = $mvip->where('id=' . $old['vipid'])->find();
        if (!$vip) {
            $info['status'] = 0;
            $info['msg'] = '未正常获取相关会员信息！';
            $this->ajaxReturn($info);
        }
        $rold = $m->where('id=' . $id)->setField('status', 0);
        if ($rold !== FALSE) {
            //提现金额
            $rvip = $mvip->where('id=' . $old['vipid'])->setInc('money', $old['txprice']);
            //提现手续费
            $mvip->where('id=' . $old['vipid'])->setInc('money', $old['tx_fee']);


            if ($rvip) {
                $price = $old['txprice'] + $old['tx_fee'];
                $data_msg['pids'] = $vip['id'];
                $data_msg['title'] = "提现申请未通过审核！" . $price . "已成功退回您的帐户余额！";
                $data_msg['content'] = "提现订单编号：" . $old['id'] . "<br><br>提现申请" . "：" . $price . "<br><br>提现退回时间：" . date('Y-m-d H:i', time()) . "<br><br>您的提现申请未通过审核，如有疑问请联系客服！";
                $data_msg['ctime'] = time();
                $rmsg = M('Vip_message')->add($data_msg);
                $info['status'] = 1;
                $info['msg'] = '取消提现申请成功！提现' . '已自动退回用户帐户余额！';

                // 发送信息===============
                $wechatTemplate = D('WechatTemplate');
                $wechatTemplate->sendMessage_WithdrawFailed(['to_user' => $vip['openid'], 'money' => $old['txprice'] + $old['tx_fee'], 'time' => time(), 'id' => $old['id'], 'type' => 'bank']);


//                $customer = M('Wx_customer')->where(array('type' => 'tx3'))->find();
//                $options['appid'] = self::$SYS['set']['wxappid'];
//                $options['appsecret'] = self::$SYS['set']['wxappsecret'];
//                $wx = new \Util\Wx\Wechat($options);
//                $msg = array();
//                $msg['touser'] = $vip['openid'];
//                $msg['msgtype'] = 'text';
//                $str = $customer['value'];
//                $msg['text'] = array('content' => $str);
//                $ree = $wx->sendCustomMessage($msg);
                // 发送消息完成============

                $this->ajaxReturn($info);
            } else {
                $info['status'] = 0;
                $info['msg'] = '取消成功，但自动退还' . self::$SHOP['set']['yjname'] . '至用户余额失败，请联系此会员！';
                $this->ajaxReturn($info);
            }
        } else {
            $info['status'] = 0;
            $info['msg'] = '操作失败，请重新尝试！';
            $this->ajaxReturn($info);
        }
    }

    public function txorderExport()
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
                $tt = "提现失败";
                break;
            case 1:
                $tt = "新申请";
                break;
            case 2:
                $tt = "提现完成";
                break;
        }
        $data = M('Vip_tx')->where($map)->select();
        foreach ($data as $k => $v) {
            switch ($v['status']) {
                case 0:
                    $data[$k]['status'] = "提现失败";
                    break;
                case 1:
                    $data[$k]['status'] = "新申请";
                    break;
                case 2:
                    $data[$k]['status'] = "提现完成";
                    break;
            }
            $data[$k]['txsqtime'] = date('Y-m-d H:i:s', $v['txsqtime']);
            $data[$k]['txtime'] = $v['txtime'] ? date('Y-m-d H:i:s', $v['txtime']) : '未执行';
        }
        $title = array('id'=>'ID', 'vipid'=>'会员ID', 'txprice'=>'提现金额', 'txname'=>'提现姓名', 'txmobile'=>'提现电话','txyh'=>'提现银行','txfh'=>'提现分行','txszd'=>'提现银行所在地','txcard'=>'提现银行卡卡号','txsqtime'=>'提现申请时间','txsqtime'=>'提现完成时间','txtime'=>'订单状态');
        export_excel($data, $title, $tt . '订单' . date('Y-m-d H:i:s', time()));
    }

    //充值余额
    public function charge()
    {
        $id = intval(I('id'));
        $redirect = trim(I('redirect'));
        $m = M('Vip');
        //dump($m);
        //设置面包导航，主加载器请配置
        $bread = array(
            '0' => array(
                'name' => '会员中心',
                'url' => U('Admin/Vip/#'),
            ),
            '1' => array(
                'name' => '会员列表',
                'url' => U('Admin/Vip/vipList'),
            ),
            '2' => array(
                'name' => '会员编辑',
                'url' => U('Admin/Vip/vipSet', array('id' => $id)),
            ),
        );
        $this->assign('breadhtml', $this->getBread($bread));
        //处理POST提交
        if (IS_POST) {
            //die('aa');
            $data = I('post.');
            if ($id) {
                //TODO 获取需要更新的字段
//                dump($data);exit;
                $chargeMoney = floatval($data['charge_money']);
                if ($chargeMoney <= 0) {
                    $info['status'] = 0;
                    $info['msg'] = '充值金额必须大于0';
                    $this->ajaxReturn($info);
                }
                $re = $m->where('id=' . $id)->setInc('money', $chargeMoney);
                if (FALSE !== $re) {
                    $cache = $m->where('id=' . $id)->find();
                    $info['money'] = $cache['money'];
                    $info['status'] = 1;
                    $info['msg'] = '充值成功！';

                    //记录日志
                    $data_log['ip'] = get_client_ip();
                    $data_log['vipid'] = $cache['id'];
                    $data_log['ctime'] = time();
                    $data_log['openid'] = $cache['openid'];
                    $data_log['nickname'] = $cache['nickname'];
                    $data_log['event'] = "管理员充值";
                    $data_log['money'] = $chargeMoney;
                    $data_log['type'] = 9;
                    $data_log['opid'] = self::$CMS['uid'];
                    $rlog = M('Vip_log')->add($data_log);

                    $wechatTemplate = D('WechatTemplate');
                    $wechatTemplate->sendMessage_Charge([
                        'to_user' => $cache['openid'],
                        'vip_id' => $cache['id'],
                        'money' => $chargeMoney
                    ]);

                } else {
                    $info['status'] = 0;
                    $info['msg'] = '充值失败！';
                }
            } else {
                $info['status'] = 0;
                $info['msg'] = '未获取会员ID！';
            }
            $this->ajaxReturn($info);
        }


        //处理编辑界面
        if ($id) {
            $cache = $m->where('id=' . $id)->find();
            $this->assign('cache', $cache);
            if ($redirect) {
                $backurl = think_decrypt($redirect);
            } else {
                $backurl = U('Admin/Vip/vipSet', ['id' => $id]);
            }
            $this->assign('backurl', $backurl);
        } else {
            //录入
        }
        $this->display();
    }

    public function getChargeUser()
    {
        $vipId = intval(I('id'));
        $cache = M('vip')->where(['id|mobile' => $vipId])->find();
        if (!$cache) {
            $info['status'] = 0;
            $info['msg'] = '用户不存在';
            $this->ajaxReturn($info);
        }
        $info['status'] = 1;
        $info['msg'] = '成功';
        $info['data'] = [
            'id' => $cache['id'],
            'nickname' => $cache['nickname'],
            'name' => empty($cache['name']) ? '' : $cache['name'],
            'mobile' => empty($cache['mobile']) ? '' : $cache['mobile'],
            'money' => $cache['money']
        ];
        $this->ajaxReturn($info);
    }

    /**
     * 调整会员上下级关系
     * 规则：
     * 1）没有下线的会员
     */
    public function ajustVipLine()
    {
        if (IS_POST) {
            $dvip = D('Vip');
            $id = I('id');
            $ppid = I('ppid');

            if ($ppid == $id) {
                $info['status'] = 0;
                $info['msg'] = "调配失败，会员ID不能相同";
            }

            $pvip = $dvip->where(['id' => $ppid])->find();
            $vip = $dvip->where(['id' => $id])->find();

            if (!$pvip || !$vip) {
                $info['status'] = 0;
                $info['msg'] = "调配失败，无效会员ID";
                $this->ajaxReturn($info);
            }
            //TODO 调配

            $data['pid'] = $pvip['id'];
            $data['plv'] = $pvip['plv'] + 1;
            $data['path'] = $pvip['path'] . '-' . $pvip['id'];

            $re = $dvip->where(['id' => $vip['id']])->save($data);

            $dvip->where(['id' => $pvip['id']])->save(['total_xxlink' => $pvip['total_xxlink'] + 1]);
            if ($re) {
                $info['status'] = 1;
                $info['msg'] = "调配成功";
            } else {
                $info['status'] = 0;
                $info['msg'] = "调配失败";
            }
            $this->ajaxReturn($info);
        }
        //设置面包导航，主加载器请配置
        $bread = array(
            '0' => array(
                'name' => '可调配会员',
            ),
        );
        $this->assign('breadhtml', $this->getBread($bread));

        //绑定搜索条件与分页
        $m = M('vip');
        $p = $_GET['p'] ? $_GET['p'] : 1;
        $search = I('search') ? I('search') : '';
        if ($search) {
            $map['nickname|mobile'] = array('like', "%$search%");
            $this->assign('search', $search);
        }
        $psize = self::$CMS['set']['pagesize'] ? self::$CMS['set']['pagesize'] : 20;
        $map['plv'] = 1;
        $map['pid'] = 0;
//        $map['isfx'] = 0;
        $map['total_xxlink'] = 0;
        //$map['employee']=0;
        $cache = $m->where($map)->page($p, $psize)->select();
        foreach ($cache as $k => $v) {
            $cache[$k]['levelname'] = M('vip_level')->where('id=' . $cache[$k]['levelid'])->getField('name');
            if ($v['isfxgd']) {
                $cache[$k]['fxname'] = '超级VIP';
            } else {
                if ($v['isfx']) {
                    $cache[$k]['fxname'] = $_SESSION['SHOP']['set']['fxname'];
                } else {
                    $cache[$k]['fxname'] = '会员';
                }
            }
        }

        $count = $m->where($map)->count();
        $this->getPage($count, $psize, 'App-loader', '会员列表', 'App-search');
        $this->assign('cache', $cache);
        $this->display();
    }

}