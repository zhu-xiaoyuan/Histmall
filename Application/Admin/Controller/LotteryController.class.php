<?php

namespace Admin\Controller;

class LotteryController extends BaseController
{

    public function __construct()
    {
        parent::__construct();
    }

    /**
     * 充值名单页
     * $search 搜索条件
     */
    public function index()
    {
        $search = I('search') ? I('search') : '';
        if ($search) {
            $where['vip_id|name|mobile'] = array('like', "%$search%");
            $this->assign('search', $search);
        }
        $p = $_GET['p'] ? $_GET['p'] : 1;
        $psize = self::$CMS['set']['pagesize'] ? self::$CMS['set']['pagesize'] : 20;
        $data = M('lottery_charge')->where($where)->page($p,$psize)->select();
        $count =  M('lottery_charge')->where($where)->count();
        $this->getPage($count, $psize, 'App-loader', '充值名单', 'App-search');
        $this->assign('data',$data);
        $this->display(); // 输出模板
    }

    /**
     * 删除充值会员
     */
    public function delLottery(){
        $id = $_GET['id']; //必须使用get方法
        $m = M('lottery_charge');
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
            $this->ajaxReturn($info);
        }
        $this->ajaxReturn($info);
    }

    /**
     * 配置抽奖
     */
    public function setPrize()
    {
        $getData = M('lottery_config')->find();
        if(IS_POST){
            $data['id'] = $getData['id'];
            $data['total_person'] = I('post.total_person/d');
            $data['total_prize_level_1'] = I('post.total_prize_level_1/d');
            $data['total_prize_level_2'] = I('post.total_prize_level_2/d');
            $data['total_prize_level_3'] = I('post.total_prize_level_3/d');
            $data['used_prize_level_1'] = I('post.used_prize_level_1/d');
            $data['used_prize_level_2'] = I('post.used_prize_level_2/d');
            $data['used_prize_level_3'] = I('post.used_prize_level_3/d');
            $data['active_prize_level'] = I('post.active_prize_level/d');
            $data['only_charge'] = I('post.only_charge/d');
            if($data['used_prize_level_1'] >  $data['total_prize_level_1'] ||
               $data['used_prize_level_2'] >  $data['total_prize_level_2'] ||
               $data['used_prize_level_3'] >  $data['total_prize_level_3'] ){
                $this->ajaxReturn(array('status'=>0,'msg'=>'您设置的数据不合理,请检查后再设置!'));
            }
            $data['is_enable'] = I('post.is_enable/d');
            $data['create_time'] = time();
            $rel = M('lottery_config')->save($data);
            if(false === $rel){
                $this->ajaxReturn(array('status'=>0,'msg'=>'保存失败,请重试!'));
            }else{
                $this->ajaxReturn(array('status'=>1,'msg'=>'保存成功!'));
            }
        }
        $this->assign('data',$getData);
        $this->display(); // 输出模板
    }
    //中奖名单
    public function lotteryList()
    {
        $search = I('search') ? I('search') : '';
        $type  = I('s_type') ? I('s_type') : 0;
        if ($search) {
            $where['vip_id|name|mobile'] = array('like', "%$search%");
            $this->assign('search', $search);
        }
        if($type){
            $where['prize_level'] = array('EGT','0');
        }else{
            $where['prize_level'] = array('GT','0');
        }
        $this->assign('s_type', $type);
        $p = $_GET['p'] ? $_GET['p'] : 1;
        $psize = self::$CMS['set']['pagesize'] ? self::$CMS['set']['pagesize'] : 20;
        $data = M('lottery_list')->join('vip ON vip.id = lottery_list.vip_id')->where($where)->page($p,$psize)->field('lottery_list.id,vip_id,name,mobile,prize_level,create_time,got_time')->select();
        $count =  M('lottery_list')->where($where)->join('vip ON vip.id = lottery_list.vip_id')->count();
        $this->getPage($count, $psize, 'App-loader', '中奖名单', 'App-search');
        $this->assign('data',$data);
        $this->display(); // 输出模板
    }

    public function save(){
        $data['vip_id'] = I('post.su_id/d');
        $data['name'] = I('post.su_name/s');
        $data['mobile'] = I('post.su_mobile/d');
        $data['money'] = I('post.su_money/f');
        $data['create_time'] = time();
        $data['operator_id'] = $_SESSION['CMS']['uid'];
        $rel = M('vip')->where(array('id'=>$data['vip_id']))->find();
        if($rel){
            $savename = M('vip')->where(array('id'=>$data['vip_id']))->save(array('name'=>$data['name'],'mobile'=>$data['mobile']));
            $chargerel = M('lottery_charge')->add($data);
            if($chargerel !== false){
                $this->ajaxReturn(array('status'=>1,'msg'=>'添加成功'));
            }else{
                $this->ajaxReturn(array('status'=>0,'msg'=>'添加失败，请重试'));
            }
        }
        $this->ajaxReturn(array('status'=>0,'msg'=>'此会员ID不存在'));

    }

    //导出中奖情况
    public function LotteryExport()
    {
        $data = M('lottery_list')->join('vip ON vip.id = lottery_list.vip_id')->where(array('prize_level'=>array('GT','0')))->select();
        foreach ($data as $k => $v) {
            $data[$k]['create_time'] = $v['create_time'] ? date('Y-m-d H:i:s', $v['create_time']) : '';
            $data[$k]['got_time'] = $v['got_time'] ? date('Y-m-d H:i:s', $v['got_time']) : '';
            if($v['prize_level'] == 3){
                $data[$k]['prize_level'] = '三等奖';
            }elseif($v['prize_level'] == 2){
                $data[$k]['prize_level'] = '二等奖';
            }else{
                $data[$k]['prize_level'] = '一等奖';
            }
        }
        $title = array('vip_id' => '会员ID', 'name' => '姓名', 'mobile' => '手机号','prize_level'=>'中奖情况','create_time'=>'中奖时间','got_time'=>'领奖时间');
        export_excel($data, $title, '中奖情况' . date('Y-m-d H:i:s', time()));
    }
    //兑奖
    public function setLottery(){
        $id = $_GET['id'];
        if($id){
            $rel = M('lottery_list')->where(array('id'=>$id))->save(array('got_time'=>time()));
            if(false === $rel){
                $this->ajaxReturn(array('status'=>0,'msg'=>'领取失败，请重试'));
            }else{
                $this->ajaxReturn(array('status'=>1,'msg'=>'领取成功!'));
            }
        }
        $this->ajaxReturn(array('status'=>0,'msg'=>$id));
    }
    //删除已领取
    public function delAlready(){
        $id = $_GET['id']; //必须使用get方法
        $m = M('lottery_list');
        $all = $_GET['all'];
        if($all){
            /*$ids = $m->field('id')->select();
            $ids = array_column($ids, 'id');
            $where = 'id in('.implode(',',$ids).')';
            $res = $m->where($where)->delete();*/
            $res = $m->execute('delete from __TABLE__ ');
            if ($res) {
                $info['status'] = 1;
                $info['msg'] = '删除成功!';
            } else {
                $info['status'] = 0;
                $info['msg'] = '删除失败!';
                $this->ajaxReturn($info);
            }
            $this->ajaxReturn($info);
        }
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
            $this->ajaxReturn($info);
        }
        $this->ajaxReturn($info);
    }

    //导出会员充值表
    public function ListExport(){
        $data = M('lottery_charge')->select();
        $title = array('vip_id' => '会员ID', 'name' => '会员名称', 'mobile' => '手机号','money'=>'充值金额');
        export_excel($data, $title, '会员充值' . date('Y-m-d H:i:s', time()));
    }

}
