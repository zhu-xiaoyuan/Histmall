<?php

namespace Admin\Controller;

class LuckyController extends BaseController
{

    public function __construct()
    {
        parent::__construct();
    }

    /**
     * 红包配置页面
     */
    public function set(){
        if(IS_POST){
            $data['command'] = htmlspecialchars(trim(I('post.command')));    //口令
            $data['total_money'] = round(abs(I('post.total_money/d')),2);    //总金额，两位小数
            $data['total_num'] = intval(abs(I('post.total_num')));           //红包数，正整数
            $data['start_time'] = strtotime(I('post.start_time'));          //开始抽奖时间戳
            $data['is_enable'] = I('post.is_enable/d');                     //是否开启
            $id = I('post.id/d');   //以此判断是添加、编辑

            //总红包金额需大于等于1元，且平均每个红包的金额也需大于等于一元
            if($data['total_money']>=1 && $data['total_money']/$data['total_num']>=1){

                $m = M('lucky_config');

                //TODO 如果是开启，不论是新增还是编辑，则都需要关闭其他所有红包
                if($data['is_enable']){
                    M()->execute('update `lucky_config` set `is_enable`=0 ');
                }

                if ($id) {        //编辑
                    $data['edit_time'] = time();
                    $res = $m->where('id='.$id)->save($data);
                    if($res){
                        $this->ajaxReturn(['status' => 1, 'msg' => '编辑成功']);
                    }else {
                        $this->ajaxReturn(['status' => 0, 'msg' => '编辑失败，请重试']);
                    }
                } else {
                    //添加数据
                    $m->startTrans();
                    $data['create_time'] = time();
                    $id = M('lucky_config')->add($data);

                    $plan['lucky_id'] = $id;
                    $plan['money'] = $this->getAllocationPlan($data['total_money'], $data['total_num']);   //获取红包分配方案
                    $complete_data = $this->getCompletePlanData($plan); //组装数据
                    $res2 = M('lucky_plan')->addAll($complete_data);
                    if ($id && $res2) {
                        $m->commit();
                        $this->ajaxReturn(['status' => 1, 'msg' => '保存成功，可以前去查看分配方案，并发送模板消息']);
                    } else {
                        $m->rollback();
                        $this->ajaxReturn(['status' => 0, 'msg' => '数据存储失败，请重试']);
                    }
                }
            }
            $this->ajaxReturn(['info'=>0,'msg'=>'总红包金额需大于等于1元，且平均每个红包的金额也需大于等于一元']);
        }else{
            $id = I('get.id/d');
            if($id){    //编辑/详情页面
                $this->detail($id);
                $this->assign('is_edit',1); //是编辑页面
            }
            $bread = array(
                '0' => array('name' => '红包管理'),
                '1' => array('name' => '红包配置'),
            );
            $this->assign('breadhtml', $this->getBread($bread));
            $this->display();
        }
    }

    /**
     * 红包列表
     */
    public function luckyList(){
        $bread = array(
            '0' => array('name' => '红包管理'),
            '1' => array('name' => '红包详情')
        );
        $this->assign('breadhtml', $this->getBread($bread));

        $p = $_GET['p'] ? $_GET['p'] : 1;
        $psize = self::$CMS['set']['pagesize'] ? self::$CMS['set']['pagesize'] : 20;

        $cache = M('lucky_config')->page($p,$psize)->order('start_time desc')->select();
        $count =  M('lucky_config')->count();
        $this->getPage($count, $psize, 'App-loader', '中奖名单', 'App-search',false);

        $this->assign('cache',$cache);
        $this->display();
    }

    /**
     * 单个红包详情
     */
    public function detail($id){
        $config = M('lucky_config')->where('id='.$id)->find();
        $plan   = M('lucky_plan')->where('lucky_id='.$id)->select();

        //前端echar数据
        $chart['x'] = array_column($plan,'id');
        $chart['y'] = array_column($plan,'money');
        $chart['num'] = count($chart['y']);
        $chart['total_money'] = array_sum($chart['y']);

        $this->assign('chart',$chart);
        $this->assign('config',$config);
        $this->assign('plan',$plan);
    }

    /**
     * 总金额分配方案  (防止计算误差，数据均*100，后除以100)
     * @param $total    float(7,2) 总金额
     * @param $num      int        份数
     * @return array
     */
    private function getAllocationPlan($total,$num){
        $plan = [];     //存储分配方案
        // 0. 金额 乘以 100
        $rest = $total * 100; //初始化剩余金额
        $min = 1 * 100;       //最低一元

        // 1. 计算前$num-1个数额
        for($i=$num; $i>1; $i--){
            $max = round(min($rest/$i * 2,$rest-$min*($i-1)));  //最高为平均值的2倍, 但必须保证剩余金额的平均值大于 $min 元
            $tmp = mt_rand($min,$max);           //随机生成金额
            $rest -= $tmp;
            $plan[] = $tmp;
        }
        // 2. 计算最后一个数额
        $plan[] = $rest;
        // 3. 所有金额除以100
        foreach($plan as $v){
            $data[] = $v/100;
        }
        shuffle($data);
        return $data;  //打乱后返回
    }

    /**
     * 组装 红包分配方案数据  准备插入数据库的数据
     */
    private function getCompletePlanData($data){
        foreach($data['money'] as $v){
            $tmp[] = ['lucky_id'=>$data['lucky_id'],'money'=>$v];
        }
        return $tmp;
    }

    /**
     * 点击“重新生成红包分配方案按钮”
     */
    public function getAllocationPlanAgain(){
        $id = I('post.id/d');
        if(!$id){
            $this->ajaxReturn(['status'=>0,'msg'=>'参数错误']);
        }
        //查找红包配置
        $config = M('lucky_config')->where('id='.$id)->find();
        if($config){
            //删除之前的数据
            M('lucky_plan')->where('lucky_id='.$id)->delete();
            //开始重新生成红包分配方案
            $plan['lucky_id'] = $id;
            $plan['money'] = $this->getAllocationPlan($config['total_money'],$config['total_num']);   //获取红包分配方案
            $complete_data = $this->getCompletePlanData($plan); //组装数据
            $res = M('lucky_plan')->addAll($complete_data);
            if($res){
                $this->ajaxReturn(['status'=>1,'msg'=>'方案生成成功']);
            }else{
                $this->ajaxReturn(['status'=>0,'msg'=>'生成失败，请重试']);
            }
        }

    }

    /**
     * 抽奖通知
     */
    Public function lotteryNotify(){

        $this->ajaxReturn(['status'=>0,'msg'=>'暂未找到合适模板消息，未配置完成']);


        $id = I('post.id/d');
        if(!$id){
            $this->ajaxReturn(['status'=>0,'msg'=>'参数错误']);
        }
        $m = M('lucky_config');
        $template = D('WechatTemplate');
        $config = $m->where('id='.$id)->find();
        //TODO 没有找到合适的模板消息
        $msg = [
            'touser'    => '',
            'first'     => "小猫飞购红包疯抢快要开始了\n输入口令'...'",
            //还有时间  口令等参数
            'url'       => '',  //模板消息的跳转url
            'name'      => '小猫飞购口令红包', //活动名称
            'location'  => '小猫飞购商城',
            'remark'    => ''

        ];
        $template->sendMessage_lotteryNotify($msg);

        //标记为已发送
        $m->where('id='.$id)->save(['is_wxtemplate'=>1]);
        $this->ajaxReturn(['status'=>1,'msg'=>'发送成功']);
    }
}
