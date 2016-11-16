<?php
/**
 * Created by PhpStorm.
 * User: heqing
 * Date: 15/9/1
 * Time: 09:17
 */

namespace Admin\Controller;


class MoneyController extends BaseController
{
    public function __construct()
    {
        parent::__construct();
    }

    public function stat()
    {
        //设置面包导航，主加载器请配置
        $bread = array(
            '0' => array(
                'name' => '返现统计',
                'url' => U('Admin/Money/stat'),
            ),
        );
        $this->assign('breadhtml', $this->getBread($bread));

//        //绑定搜索条件与分页
//        $m = M('bonus_record');
//        $p = $_GET['p'] ? $_GET['p'] : 1;
//
//        //搜索条件
//        $begin = I('begin',0,'int');
//        $end = I('end',0,'int');
//
//        if($end == 0){
//            $end = time();
//        }
//
//        if($begin==0){
//            $begin = strtotime("-1 week Monday");
//        }
//
//        $map['bonus_operate_time'] = array('between',[$begin,$end]);
//
//
//        $map['status']=array('gt',0);
//        $psize = self::$CMS['set']['pagesize'] ? self::$CMS['set']['pagesize'] : 20;
//        $count = $m->where($map)->count();
//        $cache = $m
//            //->join('inner join supplier as s on s.id=supplier_bill.supplier_id')
//            //->field('supplier_bill.*,s.name,s.contact_phone')
//            ->where($map)->page($p, $psize)->select();
//
//        foreach($cache as $key=>$value){
//            $cache[$key]['create_time']= date('Y-m-d H:i:s',$value['create_time']);
//            $cache[$key]['bonus_operate_time']= date('Y-m-d H:i:s',$value['bonus_operate_time']);
//            $cache[$key]['current_time']= $value['today_year'].'-'.$value['today_month'].'-'.$value['today_day'];
//
//            switch($value['status']){
//                case 0:
//                    $cache[$key]['status']='未分红';
//                    break;
//                case 1:
//                    $cache[$key]['status']='已分红';
//                    break;
//
//                    break;
//                default:
//                    $cache[$key]['status']='未知';
//
//                    break;
//            }
//        }
//
//        $this->getPage($count, $psize, 'App-loader', '返现统计', 'App-search');
//        $this->assign('cache', $cache);
        $this->display();
    }

    public function query()
    {
        //搜索条件
        $begin = I('begin', 0, 'int');
        $end = I('end', 0, 'int');

        if ($end == 0) {
            $end = time();
        }

        if ($begin == 0) {
            $begin = strtotime("-1 week Monday");
        }

        $map['bonus_operate_time'] = array('between', [$begin, $end]);
        $map['status'] = array('gt', 0);
        $m = M('bonus_record');
        $list = $m->where($map)->order('bonus_operate_time')->select();

        $date_list = [];
        $money_list = [];
        $sales_list = [];

        foreach ($list as $key => $value) {
            $date_list[] = $value['today_year'] . '-' . $value['today_month'] . '-' . $value['today_day'];
            $money_list[] = $value['real_money'];
            $sales_list[] = $value['today_sales_amount'];
        }

        $result = [
            'date_list' => $date_list,
            'money_list' => $money_list,
            'sales_list' => $sales_list
        ];

        $this->ajaxReturn($result);

    }

    public function manage()
    {
        //设置面包导航，主加载器请配置
        $bread = array(
            '0' => array(
                'name' => '返现管理',
                'url' => U('Admin/Money/manage'),
            ),
        );
        $this->assign('breadhtml', $this->getBread($bread));

        //获取昨天的统计
        $year = date("Y", strtotime("-1 day"));
        $month = date("m", strtotime("-1 day"));
        $day = date("d", strtotime("-1 day"));
//todo:test
//        $year = date("Y", time());
//        $month = date("m", time());
//        $day = date("d", time());


        $data['today_year'] = $year;
        $data['today_month'] = $month;
        $data['today_day'] = $day;


        $bonus = M('bonus_record')
            ->where($data)
            ->order('id desc')
            ->find();

        //资金池余额、分红资金池余额以平台当前数据为准，即以shop_set表为准
        $bonus['capital_pool_remainder'] = self::$SHOP['set']['capital_pool_remainder'];
        $bonus['bonus_capital_pool_remainder'] = self::$SHOP['set']['bonus_capital_pool_remainder'];

        $this->assign('bonus', $bonus);

        $bonus_id = $bonus['id'];


        //绑定搜索条件与分页
        $m = M('bonus_detail_record');
        $p = $_GET['p'] ? $_GET['p'] : 1;
        $search = I('search') ? I('search') : '';
        if ($search) {
            $map['vip_id|vip_name|vip_phone'] = array('like', "$search%");
            $this->assign('search', $search);
        }
        $map['bouns_record_id'] = $bonus_id;

        $psize = self::$CMS['set']['pagesize'] ? self::$CMS['set']['pagesize'] : 20;
        $count = $m->where($map)->count();
        $cache = $m
            //->join('left join supplier on supplier.id=supplier_bill.supplier_id')
            //->field('supplier_bill.*,supplier.name,supplier.contact_phone')
            ->where($map)->page($p, $psize)->select();


        $this->getPage($count, $psize, 'App-loader', '返现管理', 'App-search');
        $this->assign('cache', $cache);
        $this->display();
    }


    public function back_money()
    {
        //返现配置
        $bonus_record_id = I('bonus_record_id', 0, 'int');
        $money = I('money', 0, 'float');
        $bonus = M('bonus_record')->where(array('id' => $bonus_record_id))->find();
        if (!$bonus) {
            $this->ajaxReturn(array("status" => "0", "msg" => "参数错误"));
        }
        //判断是否已分红过
        if (intval($bonus['status']) > 0) {
            $this->ajaxReturn(array("status" => "0", "msg" => "今日返现已经完成,无法重复返现"));
            exit;
        }

        if ($money <= 0) {
            $this->ajaxReturn(array("status" => "0", "msg" => "分红资金不能小于0，请重新调整"));
            exit;
        }
        $shop = M('shop_set')->find();//todo:session没有记录shop信息,目前只有一个商城

        $count_money = $shop['bonus_capital_pool_remainder'];//当前分红资金池余额

        //检查分红资金池是否够分
        if ($money > $count_money) {
            $this->ajaxReturn(array("status" => "0", "msg" => "分红资金超出分红资金池余额，请重新调整"));
            exit;
        }

        //总销售额
        $total_sales_amount = floatval($shop['total_sales_amount']);

        //TODO 选取待分红用户,此处可优化--用户过多会导致响应很慢，甚至超时，考虑Redis等队列处理
        $vip_list = M('vip')->where('total_bonus_amount>total_got_bonus')->select();

        //用户分了多少
        $total_bonus_money = 0;//总返现金额
        $total_vip = 0;//共多少个用户需要分红
        foreach ($vip_list as $key => $vip) {
            $tmp = $this->vip_back_process($vip, $total_sales_amount, $money, $bonus_record_id);
            $total_bonus_money += $tmp['bonus_money'];
            $total_vip++;
        }

        //更新已分红总额、分红资金池余额
        M()->execute("update shop_set set total_bonus_amount=total_bonus_amount+$total_bonus_money,bonus_capital_pool_remainder=bonus_capital_pool_remainder-$total_bonus_money");

        //
        $shop = M('shop_set')->find();//注意，此处需要重新从数据库查询
        M('shop_bonus_pool_record')->add([
            'money' => $total_bonus_money,
            'source' => 2,
            'ctime' => time(),
            'event' => "分红[ID:$bonus_record_id]"
        ]);

        //返现状态更改
        M('bonus_record')->where(array('id' => $bonus_record_id))->save([
            'status' => 1,
            'real_money' => $total_bonus_money,
            'plan_money' => $money,
            'capital_pool_remainder' => $shop['capital_pool_remainder'],
            'bonus_capital_pool_remainder' => $shop['bonus_capital_pool_remainder'],
            'bonus_operate_time' => time(),
            'bonus_operator' => self::$CMS['uid']
        ]);

        bonus_log_write(['BONUS', 'plan_bonus_money' => $money, 'real_bonus_money' => $total_bonus_money, 'bonus_record_id' => $bonus_record_id]);

        $this->ajaxReturn(array("status" => "1", "msg" => "分红/返现成功", 'data' => ['bonus_vip' => $total_vip, 'real_money' => $total_bonus_money, 'plan_money' => $money]));

    }

    function vip_back_process($vip, $total_sales_amount, $money, $bonus_record_id)
    {
        //如果销售额total_sales_amount太大，个人应返total_bonus_amount偏小，此次分红money不多，可能得到vip_money为0.00000x，此时保留2位小数，结果可能会为0
        $vip_money = (floatval($vip['total_bonus_amount']) / $total_sales_amount) * $money;
        $vip_money = money_floor($vip_money);//保留两位小数，舍弃多余位数
        $balance = floatval($vip['total_bonus_amount']) - floatval($vip['total_got_bonus']);
        $balance = money_floor($balance);//保留两位小数，舍弃多余位数

        $vip_id = $vip['id'];

        //记录最多能分多少钱
        if ($vip_money > $balance) {
            $vip_money = $balance;
        }

        //返现
        //M('vip')->where(array('id'=>$vip_id))->setInc('money',$vip_money);
        //M('vip')->where(array('id'=>$vip_id))->setInc('total_got_bonus',$vip_money);
        M()->execute("update vip set money=money+$vip_money where id=$vip_id");
        M()->execute("update vip set total_got_bonus=total_got_bonus+$vip_money where id=$vip_id");


        $order_code = 'BN' . $vip_id . time();//BN：分红，消费全返
        //返现记录
        $bonus_detail_record_id = M('bonus_detail_record')->add([
            'bonus_record_id' => $bonus_record_id,
            'vip_id' => $vip_id,
            'vip_name' => empty($vip['name']) ? $vip['nickname'] : $vip['name'],
            'vip_phone' => $vip['mobile'],
            'money' => $vip_money,
            'create_time' => time(),
            'vip_amount' => $vip['money'] + $vip_money,
            'order_code' => $order_code
        ]);

        if ($vip_money > 0) {
            //发送返现通知
            $wechatTemplate = D('WechatTemplate');
            $wechatTemplate->sendMessage_BuyerBonus(['to_user' => $vip['openid'], 'vip_id' => $vip_id, 'bonus_time' => time(), 'bonus_money' => $vip_money, 'vip_money' => $vip['money'] + $vip_money]);
        }

        //为每一次用户分红记录一条日志
        bonus_log_write(['FX', 'vip_id' => $vip_id, 'bonus_money' => $vip_money, 'bonus_record_detail_id' => $bonus_detail_record_id]);

        return ['bonus_money' => $vip_money];

    }


}