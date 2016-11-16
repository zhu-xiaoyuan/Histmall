<?php
/**
 * Created by PhpStorm.
 * User: heqing
 * Date: 15/9/1
 * Time: 09:17
 */

namespace Admin\Controller;


class BillController extends BaseController
{
    public function __construct()
    {
        parent::__construct();
    }

    public function history(){
        //设置面包导航，主加载器请配置
        $bread = array(
            '0' => array(
                'name' => '对账历史',
                'url' => U('Admin/Bill/apply'),
            ),
        );
        $this->assign('breadhtml', $this->getBread($bread));

        //绑定搜索条件与分页
        $m = M('supplier_bill');
        $p = $_GET['p'] ? $_GET['p'] : 1;

        //搜索条件
//        $s_id = I('s_id',0,'int');
//        if($s_id){
//            $supplier_id = M('vip')->where(array('id'=>$s_id))->setField('supplier_id');
//            $map['supplier_bill.id']=$supplier_id;
//            $this->assign('s_id', $s_id);
//        }

//        $s_tn = trim(I('s_tn'));
//        if($s_tn){
//            $map['supplier_bill.tn']=$s_tn;
//            $this->assign('s_tn', $s_tn);
//        }

        $search = I('s_name') ? I('s_name') : '';
        if ($search) {
            $map['vip_seller_id|supplier_name|supplier_phone'] = array('like', "%$search%"); //修改 2016年8月2日18:47:54。原来写的$where
            //$where['supplier_name'] = array('like', "%$search%");
            //$map['_string'] = '(supplier_phone like "%$search%") or (supplier_name like %$search%)';
            $this->assign('s_name', $search);
        }

//        $date = I('s_date') ? I('s_date') : '';
//        if($date){
//            $date_arr = explode("~",$date);
//            $begin_date = strtotime($date_arr[0]);
//            $end_date = strtotime($date_arr[1]);
//            $map['supplier_bill.complete_time'] = array('between',array($begin_date,$end_date));
//        }

        $map['supplier_bill.status']=array('gt',0);
        $psize = self::$CMS['set']['pagesize'] ? self::$CMS['set']['pagesize'] : 20;
        $count = $m->where($map)->count();
        $cache = $m
            ->join('left join vip on supplier_bill.vip_seller_id=vip.id')
            ->field('supplier_bill.*,vip.id as vip_id,vip.name as vip_name,vip.mobile as vip_mobile')
            //->join('inner join supplier as s on s.id=supplier_bill.supplier_id')
            //->field('supplier_bill.*,s.name,s.contact_phone')
            ->where($map)->page($p, $psize)->order("supplier_bill.id desc")->select();

        $this->getPage($count, $psize, 'App-loader', '对账历史', 'App-search');
        $this->assign('cache', $cache);
        $this->display();
    }

    public function apply()
    {
        //设置面包导航，主加载器请配置
        $bread = array(
            '0' => array(
                'name' => '对账申请',
                'url' => U('Admin/Bill/apply'),
            ),
        );
        $this->assign('breadhtml', $this->getBread($bread));

        //绑定搜索条件与分页
        $m = M('supplier_bill');
        $p = $_GET['p'] ? $_GET['p'] : 1;
        $search = I('search') ? I('search') : '';
        if ($search) {
            $map['supplier_bill.vip_seller_id|supplier_bill.supplier_phone'] = array('like', "$search%");
            $this->assign('search', $search);
        }
        $map['supplier_bill.status']=0;
        $psize = self::$CMS['set']['pagesize'] ? self::$CMS['set']['pagesize'] : 20;
        $count = $m->where($map)->count();
        $cache = $m
            ->join('left join vip on supplier_bill.vip_seller_id=vip.id')
            ->field('supplier_bill.*,vip.id as vip_id,vip.name as vip_name,vip.mobile as vip_mobile')
            ->where($map)->page($p, $psize)->order("supplier_bill.id desc")->select();

        $this->getPage($count, $psize, 'App-loader', '对账申请', 'App-search');
        $this->assign('cache', $cache);
        $this->display();
    }

    public function detail(){
        $id = I('id',0,'int');

        //设置面包导航，主加载器请配置
        $bread = array(
            '0' => array(
                'name' => '对账详细',
                'url' => U('Admin/Bill/detail?id='.$id),
            ),
        );
        $this->assign('breadhtml', $this->getBread($bread));

        $type = I('type',0,'int');
        $this->assign('type',$type);


        //对账单
        $bill = M('supplier_bill')->where(array('id'=>$id))->find();
        $this->assign('bill',$bill);

        //绑定搜索条件与分页
        $m = M('supplier_order');
        $p = $_GET['p'] ? $_GET['p'] : 1;

        $map['bill_id']=$id;
        $psize = self::$CMS['set']['pagesize'] ? self::$CMS['set']['pagesize'] : 20;
        $count = $m->where($map)->count();
        $cache = $m
            //->join('left join supplier on supplier.id=supplier_bill.supplier_id')
            //->field('supplier_bill.*,supplier.name,supplier.contact_phone')
            ->where($map)->page($p, $psize)->order("id desc")->select();

        $this->getPage($count, $psize, 'App-loader', '对账详细', 'App-search');
        $this->assign('cache', $cache);
        $this->display();

    }


    public function ok(){
        //状态（0：待处理，1：完成打款，2：取消申请）
        $id = I('id',0,'int');
        $data['tn'] = I('tn');
        $data['memo'] = I('memo');
        $data['status']= 1;
        $data['complete_time']=time();

        $end_time = time();
        M('supplier_bill')->where(array('id'=>$id))->save($data);
        M('supplier_order')->where(array('bill_id'=>$id))->save(['is_check_account'=>1,'check_account_time'=>time(),'status'=>2,'end_time'=>$end_time]);

        //分销商处理
        $orders = M('supplier_order')->where(array('bill_id'=>$id))->select();
        $commission = D('Commission');
        $wechatTemplate = D('WechatTemplate');
        foreach($orders as $order){
            $order_id = $order['id'];
            $rlt = $commission->process($order_id, self::$CMS['shopset'],'admin',OT_SM);
            //订单完成通知
//            $vip_id = $order['vip_buyer_id'];
//            $vip = M('vip')->where(array('id'=>$vip_id))->find();
//            $wechatTemplate->sendMessage_OrderTradeSuccess(['to_user'=>$vip['openid'],'order_id'=>$order_id,'order_code'=>'','end_time'=>$end_time]);

        }

        $this->ajaxReturn(array("status"=>"1","msg"=>"完成打款成功"));

    }

    public function cancel(){
        //状态（0：待处理，1：完成打款，2：取消申请）
        $id = $_GET['id'];
        $data['status']= 2;
        $data['complete_time']=time();

        M('supplier_bill')->where(array('id'=>$id))->save($data);
        M('supplier_order')->where(array('bill_id'=>$id))->save(['bill_id'=>0,'is_check_account'=>0,'check_account_time'=>0,'status'=>0,'cancel_time'=>time()]);

        $this->ajaxReturn(array("status"=>"1","msg"=>"取消申请成功"));

    }

}