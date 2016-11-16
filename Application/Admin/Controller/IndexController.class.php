<?php
// +----------------------------------------------------------------------
// | 用户后台基础类--CMS分组主入口类Index
// +----------------------------------------------------------------------
namespace Admin\Controller;

class IndexController extends BaseController
{

    public function _initialize()
    {
        //你可以在此覆盖父类方法
        parent::_initialize();
    }

    // 帮住中心
    public function help()
    {
        $this->display();
    }

    //CMS后台框架入口
    public function indexO()
    {
        //权限处理
        $this->assign('useroath', $_SESSION['CMS']['user']['oath']);
        $module = M('user_oath')->select();
        foreach ($module as $k => $v) {
            $this->assign($v['name'], $v['name']);
        }

        $this->display();
    }

    public function index(){
        $menu = D('menu')->getUserMenu();    //获取对应权限目录

        $this->assign('menu',$menu);
        $this->display();
    }

    //CMS后台统计页面
    public function main()
    {
        //设置面包导航，主加载器请配置
        $bread = array(
            '0' => array(
                'name' => '主控面板',
                'url' => U('Admin/Index/main'),
            ),
        );
        $breadhtml = $this->getBread($bread);
        $this->assign('breadhtml', $breadhtml);

        //今日起始
        $beginToday = mktime(0, 0, 0, date('m'), date('d'), date('Y'));
        $endToday = mktime(0, 0, 0, date('m'), date('d') + 1, date('Y')) - 1;
        $mapToday['ctime'] = array('between', array($beginToday, $endToday));
        $orderToday['end_time'] = array('between', array($beginToday, $endToday));
        $sToday['create_time'] = array('between', array($beginToday, $endToday));
        $viaToday['reg_tuanzhang_time'] = array('between', array($beginToday, $endToday));


        //昨日起始
        $beginYesterday = mktime(0, 0, 0, date('m'), date('d') - 1, date('Y'));
        $endYesterday = mktime(0, 0, 0, date('m'), date('d'), date('Y')) - 1;
        $mapYesterday['ctime'] = array('between', array($beginYesterday, $endYesterday));
        $orderYesterday['end_time'] = array('between', array($beginYesterday, $endYesterday));
        $sYesterday['create_time'] = array('between', array($beginYesterday, $endYesterday));
        $viaYesterday['reg_tuanzhang_time'] = array('between', array($beginYesterday, $endYesterday));

        //上周起始
        $beginLastweek = mktime(0, 0, 0, date('m'), date('d') - date('w') + 1 - 7, date('Y'));
        $endLastweek = mktime(23, 59, 59, date('m'), date('d') - date('w') + 7 - 7, date('Y'));
        $mapLastweek['ctime'] = array('between', array($beginLastweek, $endLastweek));
        //本月起始
        $beginThismonth = mktime(0, 0, 0, date('m'), 1, date('Y'));
        $endThismonth = mktime(23, 59, 59, date('m'), date('t'), date('Y'));
        $mapThismonth['ctime'] = array('between', array($beginThismonth, $endThismonth));

        //会员分布
        $mvip = M('Vip');
        $viptotal = $mvip->count();
        $vipsub = $mvip->where('subscribe=1')->count();
        $vipdissub = $viptotal - $vipsub;
        $this->assign('viptotal', $viptotal);
        $this->assign('vipsub', $vipsub);
        $this->assign('vipdissub', $vipdissub);
        //新会员
        $newvipToday = $mvip->where($mapToday)->count();
        $newvipYesterday = $mvip->where($mapYesterday)->count();
        //环比
        if ($newvipYesterday) {
            $newviprate = intval(($newvipToday - $newvipYesterday) / $newvipYesterday * 100);
        } else {
            $newviprate = $newvipToday * 100;
        }
        //总共
        if ($viptotal) {
            $newviptotalrate = intval($newvipToday / $viptotal * 100);
        } else {
            $newviptotalrate = $newvipToday * 100;
        }

        $this->assign('newvipToday', $newvipToday);
        $this->assign('newvipYesterday', $newvipYesterday);
        $this->assign('newviprate', $newviprate);
        $this->assign('newviptotalrate', $newviptotalrate);
        //dump($mapToday);
        //订单分布
        $morder = M('Shop_order');
        $ordertotal = $morder->count() + M('supplier_order')->count();
        $this->assign('ordertotal', $ordertotal);
        for ($i = 0; $i < 7; $i++) {
            $name = 'order' . $i;
            if($i == 1){
                $num = $morder->where('status=' . $i)->count() + M('supplier_order')->where("status=0")->count();
            }
            else if($i==2){
                $num = $morder->where('status=' . $i)->count() + M('supplier_order')->where("status=1")->count();
            }
            else if($i==5){
                $num = $morder->where('status=' . $i)->count() + M('supplier_order')->where("status=2")->count();
            }
            else if($i==6){
                $num = $morder->where('status=' . $i)->count() + M('supplier_order')->where("status=3")->count();
            }
            else {
                $num = $morder->where('status=' . $i)->count();
            }


            $this->assign($name, $num);
        }
        //订单状态（0：已下单，1：待核对，2：已完成，3：已关闭）
        //状态（0：交易取消，1：未支付，2：已付款，3：已发货，4：退货中，5：交易完成，6：交易关闭，7：退货完成）

        //订单
        $neworderToday = $morder->where($mapToday)->count() + M('supplier_order')->where($orderToday)->count();
        $neworderYesterday = $morder->where($mapYesterday)->count() + M('supplier_order')->where($orderYesterday)->count();
        //环比
        if ($neworderYesterday) {
            $neworderrate = intval(($neworderToday - $neworderYesterday) / $neworderYesterday * 100);
        } else {
            $neworderrate = $neworderToday * 100;
        }
        //总共
        if ($ordertotal) {
            $newordertotalrate = intval($neworderToday / $ordertotal * 100);
        } else {
            $newordertotalrate = $neworderToday * 100;
        }

        $this->assign('neworderToday', $neworderToday);
        $this->assign('neworderYesterday', $neworderYesterday);
        $this->assign('neworderrate', $neworderrate);
        $this->assign('newordertotalrate', $newordertotalrate);

        //商家分析
        $newSupplierToday = M('supplier')->where($sToday)->count();
        $newSupplierYesterday = M('supplier')->where($sYesterday)->count();
        //环比
        if ($newSupplierYesterday) {
            $new_supplier_rate = intval(($newSupplierToday - $newSupplierYesterday) / $newSupplierYesterday * 100);
        } else {
            $new_supplier_rate = $newSupplierToday * 100;
        }
        //总共
        $supplier_total = M('supplier')->count();
        if ($supplier_total) {
            $new_supplier_total_rate = intval($newSupplierToday / $supplier_total * 100);
        } else {
            $new_supplier_total_rate = $newSupplierToday * 100;
        }

        $this->assign('new_supplier_today', $newSupplierToday);
        $this->assign('new_supplier_yesterday', $newSupplierYesterday);
        $this->assign('new_supplier_rate', $new_supplier_rate);
        $this->assign('new_supplier_total_rate', $new_supplier_total_rate);
        $this->assign('supplier_total',$supplier_total);
        //团长分析
        $viaToday['is_tuanzhang']=1;
        $viaYesterday['is_tuanzhang']=1;
        $newViaToday = M('vip')->where($viaToday)->count();
        $newViaYesterday = M('vip')->where($viaYesterday)->count();
        //环比
        if ($newViaYesterday) {
            $new_via_rate = intval(($newViaToday - $newViaYesterday) / $newViaYesterday * 100);
        } else {
            $new_via_rate = $newViaToday * 100;
        }
        //总共
        $via_total = M('vip')->where("is_tuanzhang=1")->count();
        if ($via_total) {
            $new_via_total_rate = intval($newViaToday / $via_total * 100);
        } else {
            $new_via_total_rate = $newViaToday * 100;
        }

        $this->assign('new_via_today', $newViaToday);
        $this->assign('new_via_yesterday', $newViaYesterday);
        $this->assign('new_via_rate', $new_via_rate);
        $this->assign('new_via_total_rate', $new_via_total_rate);
        $this->assign('via_total',$via_total);

        //佣金分析
        //订单分布
        $myj = M('Fx_syslog');
        $yjtotal = $myj->sum('fxyj');
        //今日佣金
        $yjToday = number_format($myj->where($mapToday)->sum('fxyj'), 2);
        $yjYesterday = number_format($myj->where($mapYesterday)->sum('fxyj'), 2);
        if (!$yjToday) {
            $yjToday = 0;
        }
        if (!$yjYesterday) {
            $yjYesterday = 0;
        }
        //环比
        if ($yjYesterday) {
            $yjrate = intval(($yjToday - $yjYesterday) / $yjYesterday * 100);
        } else {
            $yjrate = $yjrate * 100;
        }
        //总共
        if ($yjtotal) {
            $yjtotalrate = intval($yjToday / $yjtotal * 100);
        } else {
            $yjtotalrate = $yjToday * 100;
        }
        $this->assign('yjtotal', $yjtotal);
        $this->assign('yjToday', $yjToday);
        $this->assign('yjYesterday', $yjYesterday);
        $this->assign('yjrate', $yjrate);
        $this->assign('yjtotalrate', $yjtotalrate);
        //分销分布
        //普通会员
        $fx1 = $mvip->where(array('isfx' => 0, 'isfxgd' => 0))->count();
        //分销商
        $fx2 = $mvip->where(array('isfx' => 1, 'isfxgd' => 0))->count();
        //超级VIP
        $fx3 = $mvip->where('isfxgd=1')->count();
        $this->assign('fx1', $fx1);
        $this->assign('fx2', $fx2);
        $this->assign('fx3', $fx3);

        //会员关注日志
        $viplog = M('Vip_log_sub')->limit(5)->order('ctime desc')->select();
        foreach ($viplog as $k => $v) {
            $tmpvip = $mvip->where('id=' . $v['from'])->find();
            $viplog[$k]['headimgurl'] = $tmpvip['headimgurl'];
            $viplog[$k]['sex'] = $tmpvip['sex'] == 1 ? '男' : '女';
            $viplog[$k]['country'] = $tmpvip['country'];
            $viplog[$k]['province'] = $tmpvip['province'];
            $viplog[$k]['city'] = $tmpvip['city'];
            $viplog[$k]['event'] = $v['issub'] ? '关注' : '取消关注';
        }
        $this->assign('viplog', $viplog);
        //会员分销日志
        $fxlog = M('Fx_syslog')->limit(5)->order('ctime desc')->select();
        foreach ($fxlog as $k => $v) {
            $tmpvip = $mvip->where('id=' . $v['from'])->find();
            $fxlog[$k]['headimgurl'] = $tmpvip['headimgurl'];
            $fxlog[$k]['sex'] = $tmpvip['sex'] == 1 ? '男' : '女';
            $fxlog[$k]['event'] = $v['status'] ? '派发成功' : '派发失败';
        }
        $this->assign('fxlog', $fxlog);
        //会员推广日志
        $tjlog = M('Fx_log_tj')->limit(5)->order('ctime desc')->select();
        foreach ($tjlog as $k => $v) {
            $tmpvip = $mvip->where('id=' . $v['vipid'])->find();
            $tjlog[$k]['headimgurl'] = $tmpvip['headimgurl'];
            $tjlog[$k]['sex'] = $tmpvip['sex'] == 1 ? '男' : '女';
            $tjlog[$k]['event'] = '下线推广';
        }
        $this->assign('tjlog', $tjlog);
        //订单支付日志
        $orderlog = M('Shop_order')->where(array('ispay' => 1, 'status' => 2))->limit(5)->order('ctime desc')->select();
        foreach ($orderlog as $k => $v) {
            $tmpvip = $mvip->where('id=' . $v['vipid'])->find();
            $orderlog[$k]['headimgurl'] = $tmpvip['headimgurl'];
            $orderlog[$k]['sex'] = $tmpvip['sex'] == 1 ? '男' : '女';
        }
        $this->assign('orderlog', $orderlog);
        //汇总统计
        $shop_set = M('shop_set')->find();
        //总销售额
        $zxse = number_format($morder->where(array('ispay' => 1))->sum('totalprice'), 2) + number_format(M('supplier_order')->where("status>0")->sum('total_price'),2);
        //总成交额
        $zcje = $shop_set['total_sales_amount'];//number_format($morder->where(array('status' => 5))->sum('totalprice'), 2) + number_format(M('supplier_order')->where("status=2")->sum('total_price'),2);
        //总销量
        $zxl = $morder->where(array('ispay' => 1))->sum('totalnum') + M('supplier_order')->where("status>0")->count();
        //总佣金
        $zyj = number_format($yjtotal, 2);
        $ztx = M('Vip_tx')->where(array('status' => 2))->sum('txprice') + M('vip_wxtx')->where("status=2")->sum('txprice');
        $this->assign('zxse', $zxse);
        $this->assign('zcje', $zcje);
        $this->assign('zxl', $zxl);
        $this->assign('zyj', $zyj);
        $this->assign('ztx', $ztx);
        //计算系统运行时间
        $datetime1 = date_create('2016-1-25');
        $datetime2 = date_create(date('Y-m-d', time()));
        $interval = date_diff($datetime1, $datetime2);
        $remaindays = $interval->format('%a');
        $this->assign('remaindays', $remaindays);
        $this->display();
    }

    //CMS后台全局配置
    public function set()
    {
        //设置面包导航，主加载器请配置
        $bread = array(
            '0' => array(
                'name' => '全局配置',
                'url' => U('Admin/Index/set'),
            ),
        );
        $breadhtml = $this->getBread($bread);
        $this->assign('breadhtml', $breadhtml);
        $this->display();
    }

    //CMS后台微信配置
    public function setWx()
    {
        //设置面包导航，主加载器请配置
        $bread = array(
            '0' => array(
                'name' => '微信配置',
                'url' => U('Admin/Index/setWx'),
            ),
        );
        $breadhtml = $this->getBread($bread);
        $this->assign('breadhtml', $breadhtml);
        $this->display();
    }

    //CMS后台邮件设置
    public function setMail()
    {
        //设置面包导航，主加载器请配置
        $bread = array(
            '0' => array(
                'name' => '邮件配置',
                'url' => U('Admin/Index/setMail'),
            ),
        );
        $breadhtml = $this->getBread($bread);
        $this->assign('breadhtml', $breadhtml);
        $this->display();
    }

    //CMS后台邮件设置
    public function setPay()
    {
        //设置面包导航，主加载器请配置
        $bread = array(
            '0' => array(
                'name' => '支付配置',
                'url' => U('Admin/Index/setPay'),
            ),
        );
        $breadhtml = $this->getBread($bread);
        $this->assign('breadhtml', $breadhtml);
        $this->display();
    }

    //CMS后台短信设置
    public function setSms()
    {
        //设置面包导航，主加载器请配置
        $bread = array(
            '0' => array(
                'name' => '短信配置',
                'url' => U('Admin/Index/setSms'),
            ),
        );
        $breadhtml = $this->getBread($bread);
        $this->assign('breadhtml', $breadhtml);
        $this->display();
    }

    //CMS后台图片浏览器
    public function appImgviewer()
    {
        $ids = I('ids');
        //dump($ids);
        $m = M('UploadImg');
        $map['id'] = array('in', in_parse_str($ids));
        $cache = $m->where($map)->select();
        $this->assign('cache', $cache);
        $this->ajaxReturn($this->fetch());
    }

    //CMS后台区域设置
    public function Location()
    {
        //设置面包导航，主加载器请配置
        $bread = array(
            '0' => array(
                'name' => '系统设置',
                'url' => U('Admin/Index/#'),
            ),
            '1' => array(
                'name' => '区域配置',
                'url' => U('Admin/Index/Location'),
            ),
        );
        $this->assign('breadhtml', $this->getBread($bread));

        $m = M('location_province');
        $province = $m->select();
        $this->assign('province', $province);

        $this->display();
    }

    public function getLocation()
    {
        $post = I('get.');
        $m = M('location_' . $post['method']);
        $data = $m->where('pid=' . $post['pid'])->select();

        if ($data) {
            $info['status'] = 1;
            $info['data'] = $data;

        } else {
            $info['status'] = 0;
        }
        $this->ajaxReturn($info);
    }
}