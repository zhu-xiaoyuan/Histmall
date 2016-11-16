<?php
/**
 * Created by PhpStorm.
 * User: heqing
 * Date: 15/7/31
 * Time: 09:32
 */

namespace Admin\Controller;

use Home\Controller\WxpayController;

class GroupController extends BaseController
{

    public function _initialize(){
        parent::_initialize();
    }

    /**
     * 订单列表
     */
    public function personList()
    {
        $backUrl = base64_encode(U('Admin/Group/personList',array('status'=>2)));
        $this->assign('backUrl',$backUrl);

        $bread = array(
            '0' => array('name' => '团购订单'),
            '1' => array('name' => '参团人员')
        );
        $this->assign('breadhtml', $this->getBread($bread));
        //绑定搜索条件与分页
        $m = M('Shop_order');
        $p = $_GET['p'] ? $_GET['p'] : 1;
        $name = I('name') ? I('name') : '';
        if ($name) {
            //订单号邦定
            $map['oid|vipid|id'] = array('like', "%$name%");
            $this->assign('name', $name);
        }
        $status = I('status');
        if($status == '1'){
            $today = strtotime(date("Y-m-d"));
            $map['ctime'] = array('egt', $today);
        }

        $this->assign('status',$status);
        $map['group_buy_id'] = array('neq','0');
        $psize = self::$CMS['set']['pagesize'] ? self::$CMS['set']['pagesize'] : 20;
        $cache = $m->where($map)->page($p, $psize)->order('ctime desc')->select();
        $count = $m->where($map)->count();
        $this->getPage($count, $psize, 'App-loader', '商城订单', 'App-search');
        $this->assign('cache', $cache);
        $this->display();
    }

    /*
     * 团购列表
     */
    public function groupList(){
        $bread = array(
            '0' => array(
                'name' => '团购订单',
                'url' => U('Admin/Shop/index'),
            ),
            '1' => array(
                'name' => '团购列表',
                'url' => U('Admin/Group/groupList'),
            ),
        );
        $this->assign('breadhtml', $this->getBread($bread));

        $status = I('status');
        if($status == '2'){
            $map['group_buy.status'] = 2;
        }elseif($status == '3'){
            $map['group_buy.status'] = 0;
        }elseif($status == '4'){
            $map['group_buy.status'] = 1;
        }elseif($status == '5'){ //交易完成
            $map['group_buy.status'] = 4;
        }elseif($status == '6'){//当天
            $today = strtotime(date("Y-m-d"));
            $map['create_time'] = array('egt', $today);
        }elseif($status == '7'){
            $map['group_buy.status'] = 3;
        }

        $this->assign('status', $status);
        $m = M('Group_buy');
        $p = $_GET['p'] ? $_GET['p'] : 1;
        $name = I('name') ? I('name') : '';
        if ($name){
            //订单号邦定
            $map['creator_id|name'] = array('like', "%$name%");
            $this->assign('name', $name);
        }
        $field = "group_buy.id as id,creator_id,name,group_buy_money,group_buy_num,group_time_start,create_time,group_time_end,people_num,group_buy.status as status";
        $psize = self::$CMS['set']['pagesize'] ? self::$CMS['set']['pagesize'] : 20;
        $join = 'shop_goods ON group_buy.goods_id = shop_goods.id';
        $cache = $m->join($join)->where($map)->field($field)->page($p, $psize)->order('group_buy.create_time desc')->select();
        $count = $m->join($join)->where($map)->count();
        $this->getPage($count, $psize, 'App-loader', '商城订单', 'App-search');
        $this->assign('cache', $cache);
        $this->display();
    }
    /**
     * 团购详情页
     */
    public function groupDetail(){
        $id = I('id');
        $Shop_order = M('shop_order');
        $group_buy =  M('group_buy');
        $shop_goods = M('shop_goods');
        $status = I('get.status');
        //设置面包导航，主加载器请配置
        $bread = array(
            '0' => array(
                'name' => '商城首页',
                'url' => U('Admin/Shop/index'),
            ),
            '1' => array(
                'name' => '团购订单',
                'url' => U('Admin/Group/groupList'),
            ),
        );
        $this->assign('status',$status);
        $this->assign('breadhtml', $this->getBread($bread));

        $goods_id = $group_buy->field('goods_id')->where(array('id'=>$id))->find();
        $goods_info = $shop_goods->field('name,price,group_buy_money,group_num,group_buy_num,group_time_start,group_time_end')->where(array('id'=>$goods_id['goods_id']))->find();
        $order_info = $Shop_order->field()->where(array('group_buy_id'=>$id))->select();
        $this->assign('goods_info', $goods_info);
        $this->assign('order_info', $order_info);
        $this->display();
    }

    /**
     * 退款
     */

    public function groupRefund(){
        $m = M('Shop_order');
        $mlog = M('Shop_order_log');
        $mslog = M('Shop_order_syslog');
        $mvip = M('Vip');
        $orderId = I('id');
        $cache = $m->where('id=' . $orderId)->find();
        $vip = $mvip->where('id=' . $cache['vipid'])->find();
        if (!$vip) {
            $info['status'] = 0;
            $info['msg'] = '未正常获取此订单的会员信息！';
            $this->ajaxReturn($info);
        }
        if($cache['payprice'] == ""){
            $info['status'] = 0;
            $info['msg'] = '退货金额不能为空！';
            $this->ajaxReturn($info);
        }

        //余额支付退余额
        if($cache['paytype'] == 'money'){
            $re = $m->where('id=' . $orderId)->setField(array('status'=>'7'));
            if (FALSE !== $re) {
                $vip['money'] = $vip['money'] + $cache['payprice'];
                $rvip = $mvip->save($vip);
                if ($rvip !== FALSE) {
                    //后端LOG
                    $log['oid'] = $cache['id'];
                    $log['msg'] = '自动退款' . $cache['payprice'] . '元至用户余额-成功';
                    $log['ctime'] = time();
                    $log['type'] = 7;
                    $rlog = $mlog->add($log);
                    $log['paytype'] = $cache['paytype'];
                    $rslog = $mslog->add($log);
                    //后端LOG
                    $info['status'] = 1;
                    $info['msg'] = '自动退款' . $cache['payprice'] . '元至用户余额成功!';
                } else {
                    //前端LOG
                    $log['oid'] = $cache['id'];
                    $log['msg'] = '自动退款' . $cache['payprice'] . '元至用户余额-失败!请联系客服!';
                    $log['ctime'] = time();
                    $log['type'] = 7;
                    $rlog = $mlog->add($log);
                    //后端LOG
                    $log['paytype'] = $cache['paytype'];
                    $rslog = $mslog->add($log);
                    $info['status'] = 0;
                    $info['msg'] = '自动退款' . $cache['payprice'] . '元至用户余额失败!';
                }

            } else {
                //后端LOG
                $log['oid'] = $cache['id'];
                $log['msg'] = '退款失败!';
                $log['ctime'] = time();
                $rlog = $mlog->add($log);
                //后端LOG
                $log['type'] = -1;
                $log['paytype'] = $cache['paytype'];
                $rslog = $mslog->add($log);
                $info['status'] = 0;
                $info['msg'] = '退款失败!';
            }
            $this->ajaxReturn($info);
        }else if($cache['paytype'] == 'wxpay'){
            $wxObj = new WxpayController();
            $refund['out_trade_no'] = $cache['oid'];
            $refund['total_fee']    = $cache['payprice']*100;   //单位转化为分
            $refund['refund_fee']   = $cache['payprice']*100;   //单位转化为分
            $res = $wxObj->refund($refund);
            if($res['status'] == 1){    //申请退款成功
                $res2 = $m->where('id=' . $orderId)->setField(array('status'=>'7'));  //订单设置为已退款
                if($res2){
                    $log = ['oid'=>$cache['id'],'msg'=>'自动退款' . $cache['payprice'] . '元至用户余额-成功','type'=>7,'ctime'=>time()];
                    $mlog->add($log);
                    $log['paytype'] = $cache['paytype'];
                    $mslog->add($log);
                    $this->ajaxReturn(['status'=>1,'msg'=>'退款成功']);
                }else{
                    $log = ['oid'=>$cache['id'],'msg'=>'自动退款' . $cache['payprice'] . '元至用户余额-成功','type'=>7,'ctime'=>time()];
                    $mlog->add($log);
                    $log['msg'] = $log['msg'].' 但订单状态改变失败，需要手动标记为已退款';
                    $log['paytype'] = $cache['paytype'];
                    $mslog->add($log);
                    $this->ajaxReturn(['status'=>0,'msg'=>'退款申请成功，但订单状态改变失败，需要手动标记为已退款']);
                }
            }else{
                $log = ['oid'=>$cache['id'],'msg'=>'微信申请退款失败：'.$res['msg'],'type'=>7,
                        'ctime'=>time(),'paytype'=>$cache['paytype']];
                $mslog->add($log);
                $this->ajaxReturn($res);
            }
        }else{
            $info['status'] = 0;
            $info['msg'] = '付款方式未知，无法退款';
            $this->ajaxReturn($info);
        }

    }

    /**
     * 标记为已退款--走线下打款
     */
    public function groupRefund_mark(){
        $m = M('Shop_order');
        $mlog = M('Shop_order_log');
        $mslog = M('Shop_order_syslog');
        $orderId = I('post.id/d');

        $res = $m->where(['id'=>$orderId])->save(['status'=>7]);    //7：shop中退货完成，group_buy中指代已退款

        $log['oid'] = $orderId;
        $log['ctime'] = time();
        if($res !== FALSE){
            $log['msg'] = '已退款';
            $rlog = $mlog->add($log);
            $log['type'] = 7;
            $log['msg'] = '后台“标记为已退款”--成功';
            $rslog = $mslog->add($log);

            $info['status'] = 1;
            $info['msg'] = '成功 标记为已退款!';
        }else{
            $log['msg'] = '标记为已退款--失败';
            $log['type'] = 0;
            $mslog->add($log);

            $info['status'] = 0;
            $info['msg'] = '标记为已退款--失败!';
        }
        $this->ajaxReturn($info);
    }

    /**
     *团购完成
     */
    public function groupOk(){
        $id = I('get.id/d');
        if($id){
            $rel = M('group_buy')->where('id='.$id)->setField('status','2');
            if(false != $rel){
                $info['status'] = 1;
                $info['msg'] = '此团购改为组团成功!';
                $this->ajaxReturn($info);
            }
        }
        $this->ajaxReturn(array('status'=>0,'msg'=>'设置失败，请重试!'));
    }
    /**
     * 导出团购列表
     */
    public function exportGroup(){

        $status = I('get.status');
        if($status == '2'){
            $map['group_buy.status'] = 2;
        }elseif($status == '3'){
            $map['group_buy.status'] = 0;
        }elseif($status == '4'){
            $map['group_buy.status'] = 1;
        }elseif($status == '5'){ //交易完成
            $map['group_buy.status'] = 4;
        }elseif($status == '6'){//当天
            $today = strtotime(date("Y-m-d"));
            $map['create_time'] = array('egt', $today);
        }elseif($status == '7'){
            $map['group_buy.status'] = 3;
        }

        $join = 'shop_goods ON group_buy.goods_id = shop_goods.id';
        $data = M('group_buy')->join($join)->where($map)->select();
        foreach ($data as $k => $v) {
            $data[$k]['create_time'] = $v['create_time'] ? date('Y-m-d H:i:s', $v['create_time']) : '';
            $data[$k]['group_time_start'] = $v['group_time_start'] ? date('Y-m-d H:i:s', $v['group_time_start']) : '';
            $data[$k]['group_time_end'] = $v['group_time_end'] ? date('Y-m-d H:i:s', $v['group_time_end']) : '';
        }
        $title = array('creator_id' => '团长ID', 'name' => '商品名称', 'price' => '商品价格','group_buy_money'=>'团购价格','group_time_start'=>'团购开始时间','create_time'=>'开团时间','group_time_end'=>'团购结束时间','people_num'=>'参团人数');
        export_excel($data, $title, '团购订单' . date('Y-m-d H:i:s', time()));

    }

    public function personExport(){
        $status = I('get.status');
        if($status == '1'){
            $today = strtotime(date("Y-m-d"));
            $map['ctime'] = array('egt', $today);
        }elseif($status == '2'){

        }
        $m = M('Shop_order');
        $map['group_buy_id'] = array('neq','0');
        $data = $m->where($map)->order('ctime desc')->select();
        foreach ($data as $k => $v) {

            switch($v['status']){
                case 0: $msg = '已取消';
                    break;
                case 1: $msg = '未付款';
                    break;
                case 2: $msg = '已付款';
                    break;
                case 3: $msg = '已发货';
                    break;
                case 4: $msg = '退货中';
                    break;
                case 5: $msg = '已完成';
                    break;
                case 6: $msg = '已关闭';
                    break;
                case 7: $msg = '已退货';
                    break;
            }
            $data[$k]['status'] = $msg;

            switch($v['paytype']){
                case 'money': $msg = '余额';
                    break;
                case 'alipaywap': $msg = '支付宝WAP';
                    break;
                case 'wxpay': $msg = '微信支付';
                    break;
            }
            $data[$k]['paytime'] = $msg;
            $data[$k]['paytime'] = $v['paytime'] ? date('Y-m-d H:i:s', $v['paytime']) : '';
            $data[$k]['ctime'] = $v['ctime'] ? date('Y-m-d H:i:s', $v['ctime']) : '';
        }
        $title = array('id' => '订单ID', 'vipid' => '会员ID', 'oid' => '订单号','status'=>'订单状态','totalprice'=>'订单总额','vipname'=>'收货姓名','vipmobile'=>'收货电话','vipaddress'=>'收货地址','yf'=>'邮费合计','payprice'=>'支付金额','paytype'=>'支付方式','paytime'=>'支付时间','ctime'=>'创建时间');
        export_excel($data, $title, '团购订单' . date('Y-m-d H:i:s', time()));
    }

}