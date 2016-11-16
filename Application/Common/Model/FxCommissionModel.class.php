<?php
// +----------------------------------------------------------------------
// | 交易完成-分销商佣金计算
// +----------------------------------------------------------------------
namespace Common\Model;

use Think\Model;

class FxCommissionModel
{
    private $SHOP_SET = null;
    private $orderId = null;
    private $isAdminOperate = null;//调用者标识，app：微信中用户调用，admin：后台管理员调用
    private $orderType = null;//商城订单：0，商盟订单：1

    /**
     * 处理&计算佣金
     * @param $orderId 订单ID
     * @param null $SHOP_SET 商城设置，如果为null，使用默认配置
     * @param null $invoker 调用者标识，app：微信中用户调用，admin：后台管理员调用
     * @param false $orderType 订单类型（0：商城订单，1：商盟订单）
     * @return mixed
     */
    public function process($orderId, $SHOP_SET = null, $invoker = null, $orderType = 0)
    {
        $this->orderId = $orderId;
        $this->isAdminOperate = (empty($invoker) || $invoker != 'app') ? true : false;
        $this->orderType = empty($orderType) ? 0 : 1;
        //TODO 获取佣金比例配置
        if (empty($SHOP_SET)) {
            $this->SHOP_SET = M('shop_set')->find();
        } else {
            $this->SHOP_SET = $SHOP_SET;
        }

        if ($orderType) {
            return $this->processShangMengOrder();
        } else {
            return $this->processShopOrder();
        }
    }

    /**
     * 新小猫飞购费用逻辑
     */
    private function _handle($order)
    {
        $mfxlog = M('fx_syslog');
        $fxsyslog = $mfxlog->where(['oid' => $this->orderId, 'order_type' => $this->orderType])->getField('id');
        if ($fxsyslog) {
            $info['status'] = 0;
            $info['msg'] = '已经计算过佣金，不能重复计算';
            return $info;
        }

        //会员信息
        $vip = M('Vip')->where('id=' . $order['buyer_id'])->find();
        if (!$vip) {
            $info['status'] = 0;
            $info['msg'] = '未正常获取此订单的会员信息！';
            return $info;
        }
        //分销佣金计算
        $fxprice = $this->convertPrice($order['total_price']);  //用来分佣的总金额（单个订单）
        $role_1_id = $vip['role_1_id'];
        $role_2_id = $vip['role_2_id'];
        $role_3_id = $vip['role_3_id'];

        $mvip = M('vip');
        $fxlog['oid'] = $this->orderId;
        $fxlog['fxprice'] = $order['total_price'];
        $fxlog['order_type'] = $this->orderType;
        $fxlog['ctime'] = time();
        $fxtmp = array(); //缓存3级数组

        $role1rate = $this->SHOP_SET['role1rate'] / 100;
        $role2rate = $this->SHOP_SET['role2rate'] / 100;
        $role3rate = $this->SHOP_SET['role3rate'] / 100;

        $totalFxPrice = 0;
        if ($role_1_id) {
            $wechatTemplate = D('WechatTemplate');

            //第一级  分佣
            $commission_price = money_floor($fxprice * $role1rate);
            $role1 = $mvip->where('id=' . $role_1_id)->find();
            $fxlog['fxyj'] = $commission_price;
            $role1_data['money'] = $role1['money'] + $commission_price;
            $role1_data['total_yj'] = $role1['total_yj'] + $commission_price;
            $role1_data['total_xxbuy'] = $role1['total_xxbuy'] + 1; //下线中购买产品总次数
            $role1_data['total_xxyj'] = $role1['total_xxyj'] + $commission_price; //下线贡献佣金

            $rfx = $mvip->where(['id' => $role1['id']])->save($role1_data);
            $fxlog['from'] = $vip['id'];
            $fxlog['fromname'] = $vip['nickname'];
            $fxlog['to'] = $role1['id'];
            $fxlog['toname'] = $role1['nickname'];
            if (FALSE !== $rfx) {
                //佣金发放成功
                $totalFxPrice += $commission_price;
                $fxlog['status'] = 1;
                /* 营销总监不发送佣金消息
                 * if($commission_price) { //佣金不为0，则发送模板消息
                    $wechatTemplate->sendMessage_FxCommission([
                        'to_user' => $role1['openid'],
                        'commission_price' => $commission_price,
                        'commission_time' => $fxlog['ctime']
                    ]);
                }*/

            } else {
                //佣金发放失败
                $fxlog['status'] = 0;
            }
            //单层逻辑
            array_push($fxtmp, $fxlog);

            //第二层分销
            if ($role_2_id) {
                $role2 = $mvip->where('id=' . $role_2_id)->find();

                    $commission_price = money_floor($fxprice * $role2rate);
                    $fxlog['fxyj'] = $commission_price;
                    $role2_data['money'] = $role2['money'] + $commission_price;
                    $role2_data['total_yj'] = $role2['total_yj'] + $commission_price;
                    $role2_data['total_xxbuy'] = $role2['total_xxbuy'] + 1; //下线中购买产品人数计数
                    $role2_data['total_xxyj'] = $role2['total_xxyj'] + $commission_price; //下线贡献佣金
                    $rfx = $mvip->where(['id' => $role2['id']])->save($role2_data);
                    $fxlog['from'] = $vip['id'];
                    $fxlog['fromname'] = $vip['nickname'];
                    $fxlog['to'] = $role2['id'];
                    $fxlog['toname'] = $role2['nickname'];
                    if (FALSE !== $rfx) {
                        //佣金发放成功
                        $totalFxPrice += $commission_price;
                        $fxlog['status'] = 1;
                        if($commission_price) { //分佣不为0，发送模板消息
                            $wechatTemplate->sendMessage_FxCommission([
                                'to_user' => $role2['openid'],
                                'commission_price' => $commission_price,
                                'commission_time' => $fxlog['ctime']
                            ]);
                        }
                    } else {
                        //佣金发放失败
                        $fxlog['status'] = 0;
                    }
                    //单层逻辑
                    array_push($fxtmp, $fxlog);

            }
            //第三层分销
            if ($role_3_id) {
                $role3 = $mvip->where('id=' . $role_3_id)->find();

                    $commission_price = money_floor($fxprice * $role3rate);
                    $fxlog['fxyj'] = $commission_price;
                    $role3_data['money'] = $role3['money'] + $commission_price;
                    $role3_data['total_yj'] = $role3['total_yj'] + $commission_price;
                    $role3_data['total_xxbuy'] = $role3['total_xxbuy'] + 1; //下线中购买产品人数计数
                    $role3_data['total_xxyj'] = $role3['total_xxyj'] + $commission_price; //下线贡献佣金
                    $rfx = $mvip->where(['id' => $role3['id']])->save($role3_data);
                    $fxlog['from'] = $vip['id'];
                    $fxlog['fromname'] = $vip['nickname'];
                    $fxlog['to'] = $role3['id'];
                    $fxlog['toname'] = $role3['nickname'];
                    if (FALSE !== $rfx) {
                        //佣金发放成功
                        $totalFxPrice += $commission_price;
                        $fxlog['status'] = 1;

                        $wechatTemplate->sendMessage_FxCommission([
                            'to_user' => $role3['openid'],
                            'commission_price' => $commission_price,
                            'commission_time' => $fxlog['ctime']
                        ]);
                    } else {
                        //佣金发放失败
                        $fxlog['status'] = 0;
                    }
                    //单层逻辑
                    array_push($fxtmp, $fxlog);

            }
            //多层分销
            if (count($fxtmp) >= 1) {
                $refxlog = $mfxlog->addAll($fxtmp);
                if (!$refxlog) {
                    file_put_contents('./Data/app_fx_error.txt', '错误日志时间:' . date('Y-m-d H:i:s') . PHP_EOL . '错误纪录信息:' . $refxlog . PHP_EOL . PHP_EOL . $mfxlog->getLastSql() . PHP_EOL . PHP_EOL, FILE_APPEND);
                }
            }
        }

        $info['status'] = 1;
        $info['msg'] = '分佣计算完成';
        $info['total_price'] = $totalFxPrice;

        return $info;
    }


    /**
     * 原美林易购分销佣金逻辑
     * @param $order ['total_price'=>0,'buyer_id'=>0]
     * @return mixed
     */
    /*private function _handle($order)
    {
        $mfxlog = M('fx_syslog');
        $fxsyslog = $mfxlog->where(['oid' => $this->orderId, 'order_type' => $this->orderType])->getField('id');
        if ($fxsyslog) {
            $info['status'] = 0;
            $info['msg'] = '已经计算过佣金，不能重复计算';
            return $info;
        }

        //会员信息
        $vip = M('Vip')->where('id=' . $order['buyer_id'])->find();
        if (!$vip) {
            $info['status'] = 0;
            $info['msg'] = '未正常获取此订单的会员信息！';
            return $info;
        }
        //分销佣金计算
        $fxprice = $this->convertPrice($order['total_price']);
        $pid = $vip['pid'];
        $mvip = M('vip');
        $fxlog['oid'] = $this->orderId;
        $fxlog['fxprice'] = $order['total_price'];
        $fxlog['order_type'] = $this->orderType;
        $fxlog['ctime'] = time();
        $fxtmp = array(); //缓存3级数组

        $fx1rate = $this->SHOP_SET['fx1rate'] / 100;
        $fx2rate = $this->SHOP_SET['fx2rate'] / 100;
        $fx3rate = $this->SHOP_SET['fx3rate'] / 100;

        $totalFxPrice = 0;
        if ($pid) {
            $wechatTemplate = D('WechatTemplate');

            //第一层分销
            $fx1 = $mvip->where('id=' . $pid)->find();
            if ($fx1['isfx']) {
                $commission_price = money_floor($fxprice * $fx1rate);
                $fxlog['fxyj'] = $commission_price;
                $fx1_data['money'] = $fx1['money'] + $commission_price;
                $fx1_data['total_yj'] = $fx1['total_yj'] + $commission_price;
                $fx1_data['total_xxbuy'] = $fx1['total_xxbuy'] + 1; //下线中购买产品总次数
                $fx1_data['total_xxyj'] = $fx1['total_xxyj'] + $commission_price; //下线贡献佣金

                $rfx = $mvip->where(['id' => $fx1['id']])->save($fx1_data);
                $fxlog['from'] = $vip['id'];
                $fxlog['fromname'] = $vip['nickname'];
                $fxlog['to'] = $fx1['id'];
                $fxlog['toname'] = $fx1['nickname'];
                if (FALSE !== $rfx) {
                    //佣金发放成功
                    $totalFxPrice += $commission_price;
                    $fxlog['status'] = 1;

                    $wechatTemplate->sendMessage_FxCommission([
                        'to_user' => $fx1['openid'],
                        'commission_price' => $commission_price,
                        'commission_time' => $fxlog['ctime']
                    ]);

                } else {
                    //佣金发放失败
                    $fxlog['status'] = 0;
                }
                //单层逻辑
                //$rfxlog=$mfxlog->add($fxlog);
                //file_put_contents('./Data/app_debug.txt','日志时间:'.date('Y-m-d H:i:s').PHP_EOL.'纪录信息:'.$rfxlog.PHP_EOL.PHP_EOL.$mfxlog->getLastSql().PHP_EOL.PHP_EOL,FILE_APPEND);
                array_push($fxtmp, $fxlog);
            }
            //第二层分销
            if ($fx1['pid']) {
                $fx2 = $mvip->where('id=' . $fx1['pid'])->find();
                if ($fx2['isfx']) {
                    $commission_price = money_floor($fxprice * $fx2rate);
                    $fxlog['fxyj'] = $commission_price;
                    $fx2_data['money'] = $fx2['money'] + $commission_price;
                    $fx2_data['total_yj'] = $fx2['total_yj'] + $commission_price;
                    $fx2_data['total_xxbuy'] = $fx2['total_xxbuy'] + 1; //下线中购买产品人数计数
                    $fx2_data['total_xxyj'] = $fx2['total_xxyj'] + $commission_price; //下线贡献佣金
                    $rfx = $mvip->where(['id' => $fx2['id']])->save($fx2_data);
                    $fxlog['from'] = $vip['id'];
                    $fxlog['fromname'] = $vip['nickname'];
                    $fxlog['to'] = $fx2['id'];
                    $fxlog['toname'] = $fx2['nickname'];
                    if (FALSE !== $rfx) {
                        //佣金发放成功
                        $totalFxPrice += $commission_price;
                        $fxlog['status'] = 1;

                        $wechatTemplate->sendMessage_FxCommission([
                            'to_user' => $fx2['openid'],
                            'commission_price' => $commission_price,
                            'commission_time' => $fxlog['ctime']
                        ]);
                    } else {
                        //佣金发放失败
                        $fxlog['status'] = 0;
                    }
                    //单层逻辑
                    //$rfxlog=$mfxlog->add($fxlog);
                    //file_put_contents('./Data/app_debug.txt','日志时间:'.date('Y-m-d H:i:s').PHP_EOL.'纪录信息:'.$rfxlog.PHP_EOL.PHP_EOL.$mfxlog->getLastSql().PHP_EOL.PHP_EOL,FILE_APPEND);
                    array_push($fxtmp, $fxlog);
                }
            }
            //第三层分销
            if ($fx2['pid']) {
                $fx3 = $mvip->where('id=' . $fx2['pid'])->find();
                if ($fx3['isfx']) {
                    $commission_price = money_floor($fxprice * $fx3rate);
                    $fxlog['fxyj'] = $commission_price;
                    $fx3_data['money'] = $fx3['money'] + $commission_price;
                    $fx3_data['total_yj'] = $fx3['total_yj'] + $commission_price;
                    $fx3_data['total_xxbuy'] = $fx3['total_xxbuy'] + 1; //下线中购买产品人数计数
                    $fx3_data['total_xxyj'] = $fx3['total_xxyj'] + $commission_price; //下线贡献佣金
                    $rfx = $mvip->where(['id' => $fx3['id']])->save($fx3_data);
                    $fxlog['from'] = $vip['id'];
                    $fxlog['fromname'] = $vip['nickname'];
                    $fxlog['to'] = $fx3['id'];
                    $fxlog['toname'] = $fx3['nickname'];
                    if (FALSE !== $rfx) {
                        //佣金发放成功
                        $totalFxPrice += $commission_price;
                        $fxlog['status'] = 1;

                        $wechatTemplate->sendMessage_FxCommission([
                            'to_user' => $fx3['openid'],
                            'commission_price' => $commission_price,
                            'commission_time' => $fxlog['ctime']
                        ]);
                    } else {
                        //佣金发放失败
                        $fxlog['status'] = 0;
                    }
                    //单层逻辑
                    //$rfxlog=$mfxlog->add($fxlog);
                    //file_put_contents('./Data/app_debug.txt','日志时间:'.date('Y-m-d H:i:s').PHP_EOL.'纪录信息:'.$rfxlog.PHP_EOL.PHP_EOL.$mfxlog->getLastSql().PHP_EOL.PHP_EOL,FILE_APPEND);
                    array_push($fxtmp, $fxlog);
                }
            }
            //多层分销
            if (count($fxtmp) >= 1) {
                $refxlog = $mfxlog->addAll($fxtmp);
                if (!$refxlog) {
                    file_put_contents('./Data/app_fx_error.txt', '错误日志时间:' . date('Y-m-d H:i:s') . PHP_EOL . '错误纪录信息:' . $refxlog . PHP_EOL . PHP_EOL . $mfxlog->getLastSql() . PHP_EOL . PHP_EOL, FILE_APPEND);
                }
            }
        }

        $info['status'] = 1;
        $info['msg'] = '分佣计算完成';
        $info['total_price'] = $totalFxPrice;

        return $info;
    }*/

    /**
     * 处理&计算 商盟订单 佣金
     */
    private function processShangMengOrder()
    {
        //获取订单
        $m = M('supplier_order');
        $map['id'] = $this->orderId;
        $order = $m->where($map)->find();
        if (!$order) {
            $info['status'] = 0;
            $info['msg'] = '操作失败！';
            return $info;
        }
        if ($order['status'] != 2) {
            $info['status'] = 0;
            $info['msg'] = '操作失败！';
            return $info;
        }
        $rlt = $this->_handle(['total_price' => $order['total_price'], 'buyer_id' => $order['vip_buyer_id']]);
        return $rlt;
    }

    /**
     * 处理&计算 商城订单 佣金
     */
    private function processShopOrder()
    {
        //获取订单
        $m = M('Shop_order');
        $map['id'] = $this->orderId;
        $order = $m->where($map)->find();
        if (!$order) {
            $info['status'] = 0;
            $info['msg'] = '操作失败！';
            return $info;
        }
        if ($order['status'] != 5) {
            $info['status'] = 0;
            $info['msg'] = '操作失败！';
            return $info;
        }
        //2016年8月14日更改 $rlt = $this->_handle(['total_price' => $order['totalprice'], 'buyer_id' => $order['vipid']]);
        $rlt = $this->_handle(['total_price' => $order['payprice'], 'buyer_id' => $order['vipid']]);
        return $rlt;
    }

    /**
     * 计算销售额中应该拿多少钱分红  小猫飞购为 10%  --2016年8月14日
     *
     */
    private function convertPrice($price)
    {
        //原美林比例return ($price * $this->SHOP_SET['total_commission_rate'] * 0.01) * ($this->SHOP_SET['fx_rate'] * 0.01);
        //现小猫比例
        return ($price * $this->SHOP_SET['total_commission_rate'] * 0.01);
    }

}

?>