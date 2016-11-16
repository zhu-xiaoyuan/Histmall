<?php
// 抽奖
namespace App\Controller;

class LotteryController extends BaseController
{
    private $lotteryVip = null;
    private $lotteryConfig = null;

    public function _initialize()
    {
        parent::_initialize();
        $this->lotteryVip = self::$WAP['vip'];
    }

    public function index()
    {
        $this->display();
    }


//    public function myprize()
//    {
//        $query = M('lottery_list')->where(['id' => $this->lotteryVip['id'], 'prize_level' => ['gl', '0']])->find();
//        $this->assign('lottery', $query);
//        $this->display();
//    }

    /**
     * 微信摇一摇抽奖入口
     */
    public function lottery()
    {
        if (!$this->lotteryVip) {
            $this->ajaxReturn(['code' => 1, 'msg' => '未中奖', 'prize_level' => 0]);
        }
        $mLotteryConfig = M('lottery_config');
        $this->lotteryConfig = $mLotteryConfig->find();

        if (!$this->lotteryConfig) {
            $this->ajaxReturn(['code' => 2, 'msg' => '未中奖', 'prize_level' => 0]);
        }

        if (empty($this->lotteryConfig['is_enable'])) {
            $this->ajaxReturn(['code' => 3, 'msg' => '摇奖已暂停，请依据活动规则进行！', 'prize_level' => 0]);
        }

        $activePrizeLevel = $this->lotteryConfig['active_prize_level'];
        if (empty($activePrizeLevel)) {
            $this->ajaxReturn(['code' => 4, 'msg' => '摇奖已暂停，请依据活动规则进行！', 'prize_level' => 0]);
        }

        // 是否抽中
        $prize_level = $this->random_lottery();

        if ($prize_level <= 0) {
            $this->add_lottery_user($prize_level);
            $this->ajaxReturn(['code' => 5, 'msg' => '未中奖', 'prize_level' => 0]);
        }

        //更新抽奖配置数据，插入用户信息
        $mLotteryConfig->startTrans();
        $rlt = $mLotteryConfig->execute("UPDATE lottery_config SET used_prize_level_$prize_level=used_prize_level_$prize_level+1 WHERE used_prize_level_$prize_level<total_prize_level_$prize_level and is_enable=1 and active_prize_level=$prize_level");
        if ($rlt) {
            $rlt = $this->add_lottery_user($prize_level);
            if ($rlt) {
                $mLotteryConfig->commit();
                //TODO 发送中奖模板消息
                $this->sendTplMsg($prize_level);
                $this->ajaxReturn(['code' => 0, 'msg' => '中奖', 'prize_level' => $prize_level]);
            }
        }
        $mLotteryConfig->rollback();

        //如果抽中，但奖品不足，更改为未抽中
        $this->add_lottery_user(0);
        $this->ajaxReturn(['code' => 6, 'msg' => '未中奖', 'prize_level' => 0]);
    }

    /**
     * 抽奖
     * @return int
     */
    private function random_lottery()
    {
        /**
         * 规则
         * “一等奖和二等奖”只有充值消费的人才可抽中
         * 三等奖只要是会员都可抽中
         * 一人只能中一次
         */
        $return_prize_level = 0;//抽中几等奖,0...3

        //是否中过奖
        $lotteryVip = M('lottery_list')->where(['vip_id' => $this->lotteryVip['id'], 'prize_level' => ['gt', '0']])->find();
        if ($lotteryVip) {
            return 0;//已经中过奖，不能再中，只接返回
        }

        $onlyCharge = $this->lotteryConfig['only_charge'];
        $chargeMoney = $this->get_vip_charge_money($this->lotteryVip['id']);
        $activePrizeLevel = $this->lotteryConfig['active_prize_level'];//正在抽几等奖
        switch ($activePrizeLevel) {
            case 1:
                if ($onlyCharge) {
                    if ($chargeMoney > 0) {
                        //只有充值的才有可能中奖
                        if ($this->random_prize($this->lotteryConfig['total_prize_level_1'], $this->lotteryConfig['total_person'])) {
                            $return_prize_level = $activePrizeLevel;
                        }
                    }
                } else {
                    if ($this->random_prize($this->lotteryConfig['total_prize_level_1'], $this->lotteryConfig['total_person'])) {
                        $return_prize_level = $activePrizeLevel;
                    }
                }
                break;
            case 2:
                if ($onlyCharge) {
                    if ($chargeMoney > 0) {
                        if ($this->random_prize($this->lotteryConfig['total_prize_level_2'], $this->lotteryConfig['total_person'])) {
                            $return_prize_level = $activePrizeLevel;
                        }
                    }
                } else {
                    if ($this->random_prize($this->lotteryConfig['total_prize_level_2'], $this->lotteryConfig['total_person'])) {
                        $return_prize_level = $activePrizeLevel;
                    }
                }
                break;
            case 3:
                if ($this->random_prize($this->lotteryConfig['total_prize_level_3'], $this->lotteryConfig['total_person'])) {
                    $return_prize_level = $activePrizeLevel;
                }
                break;
            default:
                $return_prize_level = 0;
                break;
        }

        return $return_prize_level;
    }

    /**
     * 获取会员充值金额
     * @return int
     */
    private function get_vip_charge_money()
    {
        $lotteryCharge = M('lottery_charge')->where(['vip_id' => $this->lotteryVip['id']])->find();
        if ($lotteryCharge) {
            return $lotteryCharge['money'];
        }
        return 0;//此会员未充值
    }

    /**
     * 是否中奖
     * @param $total_prize
     * @param $total_person
     * @return bool
     */
    private function random_prize($total_prize, $total_person)
    {
        $rate = intval($total_prize / $total_person * 100);//0...100
        $rand_num = mt_rand(1, 99);
        if ($rate > 0 && $rand_num <= $rate) {
            return true;
        }
        return false;
    }

    /**
     * 添加抽奖用户，用户每抽一次插入一条记录
     * @param $prizeLevel
     * @return mixed
     */
    private function add_lottery_user($prizeLevel)
    {
        return M('lottery_list')->add(['vip_id' => $this->lotteryVip['id'], 'openid' => $this->lotteryVip['openid'], 'prize_level' => $prizeLevel, 'create_time' => time()]);
    }

    private function sendTplMsg($prizeLevel)
    {
        if ($prizeLevel == 1) {
            $prizeLevelStr = '一等奖';
        } else if ($prizeLevel == 2) {
            $prizeLevelStr = '二等奖';
        } else if ($prizeLevel == 3) {
            $prizeLevelStr = '三等奖';
        } else {
            //TODO 写日志
            return;//不符合要求，不能发通知
        }
        $wechatTemplate = D('WechatTemplate');
        $wechatTemplate->sendMessage_Lottery([
            'to_user' => $this->lotteryVip['openid'],
            'title' => '鱼友会活动大抽奖',
            'prize' => '会员ID：' . $this->lotteryVip['id'] . ' [' . $prizeLevelStr . ']'
        ]);
    }
}
