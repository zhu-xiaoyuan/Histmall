<?php
// 新 小猫飞购 红包抽奖      2016年9月3日18:08:32
namespace App\Controller;

class LuckyController extends BaseController
{
    public $config; //红包配置信息
    public $vipInfo;    //访问用户信息

    public function _initialize()
    {
        parent::_initialize();

        $this->vipInfo = self::$WAP['vip'];
        //开启抽奖的红包，同一时间，最多只有一个
        $map['is_enable'] = 1;
        $map['start_time'] = array('lt',time());
        $this->config = M('lucky_config')->where($map)->find();
    }

    /**
     * 抽红包入口界面（输入口令界面）
     */
    public function index(){
        $this->assign('config',$this->config);
        $this->display();
    }

    /**
     * 抽奖流程
     */
    public function prizeProcess(){
        if($this->config['id'] == I('get.config_id/id')){   //从口令页面带过来的参数
            if(!$this->isPrizedBefore()){
                //没有中过奖，走抽奖流程
                if($this->isGetPrize()){    //如果中奖了
                    $res = $this->distributePrize();    //分配奖项
                }
            }
            $this->redirect(U('App/Lucky/prizePage'));
        }
    }

    /**
     * 中奖详情页面
     */
    public function prizePage(){
        //获取是否中奖的信息，以及中奖名单
        $prizeList = M('lucky_plan')->field('vip.headimgurl,vip.nickname,lucky_plan.*')
            ->join('vip on lucky_plan.vipid = vip.id')->where('lucky_plan.vipid != 0 and lucky_plan.config_id='.$this->config['id'])
            ->order('money desc')->select();

        $vipPrize = '';
        $taken_money = 0;
        foreach($prizeList as $v){
            if($v['vipid'] == $this->vipInfo['id']){    //找到vip本人中奖信息
                $vipPrize = $v;
            }
            $taken_money += floatval($v['money']);  //统计已中奖金额
        }
        $this->config['taken_money'] = $taken_money;

        $this->assign('vipPrize',$vipPrize);
        $this->assign('prizeList',$prizeList);
        $this->assign('config',$this->config);
        $this->display();
    }

    /**
     * 验证口令是否输入正确
     */
    public function checkCommand(){
        if($this->config){  //如果有奖项处于开启状态
            $command = I('post.command');
            if($this->config['command'] == $command){
                $this->luckyJoinLog(['status'=>1]); //日志
                $this->ajaxReturn(['status'=>1,'msg'=>'输入正确','config_id'=>$this->config['id']]);
            }else{
                $this->luckyJoinLog(['status'=>2]); //日志
                $this->ajaxReturn(['status'=>0,'msg'=>'口令输错啦，赶紧再输一次吧']);
            }
        }else{
            $this->luckyJoinLog(['status'=>3]);
            //没有奖项处于开启状态
            $this->ajaxReturn(['status'=>0,'msg'=>'还没有开启抽奖活动哦，关注小猫微信公众账号，有活动小猫会第一时间通知你哦']);
        }
    }

    /**
     * 检测是否中奖 （目前的中奖规则：先到先得）
     */
    Private function isGetPrize(){
        if($this->config['total_num'] > $this->config['taken_num']){
            $res = M()->execute('update `lucky_config` set `taken_num` = `taken_num` + 1 where `is_enable`=1 and `start_time`<'.time().' and `taken_num`<`total_num`');
            return $res ? true : false;
        }
        return false;
    }

    /**
     * 分配奖项
     */
    public function distributePrize(){
        $m = M('lucky_plan');
        $tmp = $m->where(['vipid'=>0,'config_id'=>$this->config['id']])->field('id')->select();
        if($tmp){
            $prize_ids = array_column($tmp,'id');   //取出未分配出去的奖项的id
            $length = count($prize_ids);
            for($i=0; $i<$length; $i++){
                $key = array_rand($prize_ids,1); //随机返回一个键值
                $id = $prize_ids[$key];         //获取奖项id
                unset($prize_ids[$key]);
                echo $id.'<br/>';
                //$res = M()->execute('update `lucky_plan` set `vipid`='.$this->vipInfo['id'].' and `taken_time`='.time().' where id='.$id.' and vipid = 0');
                $res = M('lucky_plan')->where("id=$id and vipid=0")->save(['vipid'=>$this->vipInfo['id'],'taken_time'=>time()]);
                if($res){
                    return true;
                }
            }
        }

        return false;   //正常情况不会走这一步，因为只要进入分配奖项的人，都会分到奖
    }

    /**
     * 中过奖的不能再中奖
     */
    private function isPrizedBefore(){
        $isPrized = M()->query('select 1 from `lucky_plan` where `config_id`='.$this->config['id'].' and `vipid` = '.$this->vipInfo['id'].' limit 1');
        return false;
        return $isPrized ? true : false;
    }

    /**
     * 参与log -- 输入口令log
     */
    public function luckyJoinLog($d){
        $data['status'] = $d['status'];
        $data['ctime'] = time();
        $data['vipid'] = $this->vipInfo['id'];
        $data['config_id'] = $this->config['id'];
        M('lucky_join_log')->add($data);
    }
}