<?php
// +----------------------------------------------------------------------
// | 手机端提交类
// +----------------------------------------------------------------------
namespace Admin\Controller;

class SubmitController extends BaseController
{
    //列表页面
    public function showsubmit()
    {
        $type = I('type');
        if(empty($type)){
            $type = 2;
        }
        $map['status'] = $type;
        $m = M('Shop_order');
        $cache = $m->where($map)->order('ctime desc')->select();
        foreach ($cache as $k => $v) {
            if ($v['items']) {
                $cache[$k]['items'] = unserialize($v['items']);
            } else {
                $cache[$k]['items'] = array();
            }
        }
        $this->assign('cache', $cache);
        if (IS_POST) {
            $data = I('post.number');
            $type = I('post.type');
            //绑定搜索条件与分页
            $p = $_GET['p'] ? $_GET['p'] : 1;
            if ($data) {
                //订单号邦定
                $map['pickid|vipname|vipmobile'] = array('like', "%$data%");
            }
            $map['is_group_buy'] = '0';
            $map['status'] = $type;
            $cache = $m->where($map)->order('ctime desc')->select();
            foreach ($cache as $k => $v) {
                if ($v['items']) {
                    $cache[$k]['items'] = unserialize($v['items']);
                } else {
                    $cache[$k]['items'] = array();
                }
            }
            $this->assign('name',$data);
            $this->assign('cache', $cache);
        }
        $this->assign('type',$type);
        $this->display();
    }
    //交易完成
    public function completion(){
        if (IS_POST) {
            $id = I('post.id');
            $num = I('post.num');
            if (!$id) {
                $info['status'] = 0;
                $info['msg'] = '未正常获取ID数据！';
                $this->ajaxReturn($info);
            }

            //分销流程介入
            $m = M('shop_order');
            $cache = $m->where(array('id'=>$id,'pickid'=>$num))->find();
            $group_id = $cache['group_buy_id'];
            if (!$cache) {
                $info['status'] = 0;
                $info['msg'] = '信息有误，请核实！';
                $this->ajaxReturn($info);
            }
            if ($cache['status'] != 2) {
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
                $count = $m->where(array('group_buy_id' => $group_id,'status' => 2))->select();
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
    }
}