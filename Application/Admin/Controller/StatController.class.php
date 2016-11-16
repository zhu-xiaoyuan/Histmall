<?php
/**
 * Created by PhpStorm.
 * User: heqing
 * Date: 15/9/1
 * Time: 09:17
 */

namespace Admin\Controller;


class StatController extends BaseController
{
    public function __construct()
    {
        parent::__construct();
    }

    public function business()
    {
        //设置面包导航，主加载器请配置
        $bread = array(
            '0' => array(
                'name' => '商家统计',
                'url' => U('Admin/Stat/business'),
            ),
        );
        $this->assign('breadhtml', $this->getBread($bread));

        //绑定搜索条件与分页
        $m = M('supplier');
        $p = $_GET['p'] ? $_GET['p'] : 1;

        //搜索条件
        $s_keyword = trim(I('s_keyword'));
        if ($s_keyword) {
            $map['vip.id|vip.mobile'] = array('like', "$s_keyword%");
            $this->assign('s_keyword', $s_keyword);
        }

        $date = I('s_date') ? I('s_date') : '';
        if ($date) {
            $date_arr = explode("~", $date);
            $begin_date = strtotime($date_arr[0]);
            $end_date = strtotime($date_arr[1]);
            $map['supplier.create_time'] = array('between', array($begin_date, $end_date));

            $this->assign('s_date', $date);
        }

        $begin_money = I('s_begin_money', 0, 'float');
        $end_money = I('s_end_money', 0, 'float');
        if ($begin_money && $end_money) {
            $map['supplier.total_money'] = [array('egt', $begin_money), array('elt', $end_money)];
            $this->assign('s_begin_money', $begin_money);
            $this->assign('s_end_money', $end_money);
        } else if ($begin_money) {
            $map['supplier.total_money'] = array('egt', $begin_money);
            $this->assign('s_begin_money', $begin_money);
        } else if ($end_money) {
            $map['supplier.total_money'] = array('elt', $end_money);
            $this->assign('s_end_money', $end_money);
        }

        $map['supplier.status'] = 0;
        $psize = self::$CMS['set']['pagesize'] ? self::$CMS['set']['pagesize'] : 20;
        $count = $m
            ->join('inner join vip on vip.supplier_id=supplier.id')
            ->where($map)->count();
        $cache = $m
            ->join('inner join vip on vip.supplier_id=supplier.id')
            ->field('supplier.*,vip.id as vip_id,vip.name as vip_name,vip.mobile as vip_mobile')
            ->where($map)->page($p, $psize)->order("total_money desc")->select();

        $this->getPage($count, $psize, 'App-loader', '商家统计', 'App-search');
        $this->assign('cache', $cache);
        $this->display();
    }


    public function supplierExport()
    {
        $m = M('supplier');

        $search = I('s_keyword') ? I('s_keyword') : '';
        if ($search) {
            $map['vip.id|vip.mobile'] = array('like', "$search%");
            //$map['_string'] = '(supplier_phone like "%$search%") or (supplier_name like %$search%)';
            $this->assign('s_keyword', $search);
        }

        $date = I('s_date') ? I('s_date') : '';
        if ($date) {
            $date_arr = explode("~", $date);
            $begin_date = strtotime($date_arr[0]);
            $end_date = strtotime($date_arr[1]);
            $map['create_time'] = array('between', array($begin_date, $end_date));

            $this->assign('s_date', $date);

        }

        $s_type = I('s_type', 0, 'int');
        switch ($s_type) {
            case 1:
                $total_text = 'total_online_money';
                break;
            case 2:
                $total_text = 'total_offline_money';
                break;
            default:
                $total_text = 'total_money';
                break;
        }

        $begin_money = I('s_begin_money', 0, 'float');
        if ($begin_money) {
            $map[$total_text] = array('egt', $begin_money);
        }
        $end_money = I('s_end_money', 0, 'float');
        if ($end_money) {
            $map[$total_text] = array('elt', $end_money);
        }

        $map['supplier.status'] = 0;
        $data = $m
            ->join('inner join vip on vip.supplier_id=supplier.id')
            ->field('supplier.*,vip.id as vip_id,vip.name as vip_name,vip.mobile as vip_mobile')
            ->where($map)->order("total_money desc")->select();
        $title = array('vip_id' => '会员ID','vip_name'=>'会员姓名','vip_mobile'=>'会员手机号', 'name' => '商家名称', 'total_money' => '总营业额', 'total_online_money' => '商城营业额', 'total_offline_money' => '商盟营业额');
        export_excel($data, $title, '商家统计' . date('Y-m-d H:i:s', time()));
    }

}