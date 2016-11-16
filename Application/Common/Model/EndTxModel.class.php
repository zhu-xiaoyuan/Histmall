<?php
// +----------------------------------------------------------------------
// | 封装 支付完成 后的操作处理逻辑
// +----------------------------------------------------------------------
namespace Common\Model;

class EndTxModel
{
    public function endwxtx($tx_id,$event)
    {
        $count = M('vip_log_tx')->where("tx_id=$tx_id and tocard=3")->count();
        if($count){
            return false;
        }

        //更新提现状态
        $old = M('vip_wxtx')->where('id=' . $tx_id)->find();
        $old['status'] = 2;
        $old['txtime'] = time();
        $rv = M('vip_wxtx')->save($old);
        if ($rv !== FALSE) {

            $vip = M('vip')->where(array('id' => $old['vip_id']))->find();

            //更新shopset提现总手续费
            M('shop_set')->where("id=1")->setInc('total_tx_fee_amount',$old['tx_fee']);

            //更新提现累计
            M('vip')->where(array('id' => $old['vipid']))->setInc('total_tx',$old['txprice']);

            //提现日志
            $log['ip']='';
            $log['vipid']=$vip['id'];
            $log['openid']=$vip['openid'];
            $log['nickname']=$vip['nickname'];
            $log['mobile']=$vip['mobile'];
            $log['event']=$event;
            $log['money']=$old['txprice'];
            $log['fee']=$old['tx_fee'];
            $log['tocard']= 3; //提现到哪里，1：提现到银行卡，2：提现到余额，3：提现到微信钱包，4：提现到支付宝
            $log['ctime']=time();
            $log['tx_id']=$old['id'];
            M('vip_log_tx')->add($log);

            // 发送信息===============
            $wechatTemplate = D('WechatTemplate');
            $wechatTemplate->sendMessage_WithdrawSuccess(['to_user'=>$vip['openid'],'money'=>$old['txprice'],'time'=>time(),'type'=>'wx/bank']);


//                    $customer = M('Wx_customer')->where(array('type' => 'tx2'))->find();
//                    $vip = $mvip->where(array('id' => $old['vipid']))->find();
//                    $msg = array();
//                    $msg['touser'] = $vip['openid'];
//                    $msg['msgtype'] = 'text';
//                    $str = $customer['value'];
//                    $msg['text'] = array('content' => $str);
//                    $ree = $wx->sendCustomMessage($msg);
            // 发送消息完成============

            $data_msg['pids'] = $old['vip_id'];
            $data_msg['title'] = "亲爱的用户，提现已完成！ " . $old['txprice'] . "已成功发放到您的提现帐户里面了！";
            $data_msg['content'] = "提现订单编号：" . $old['id'] . "<br><br>提现申请" . "：" . $old['txprice'] . "<br><br>提现完成时间：" . date('Y-m-d H:i', $old['txtime']) . "<br><br>您的提现申请已完成，如有异常请联系客服！";
            $data_msg['ctime'] = time();

            $rmsg = M('vip_message')->add($data_msg);
        } else {
            $err = FALSE;
        }

        return true;
    }

    public function endtx($tx_id,$event)
    {
        $count = M('vip_log_tx')->where("tx_id=$tx_id and tocard=1")->count();
        if($count){
            return false;
        }

        //更新提现状态
        $old = M('vip_tx')->where('id=' . $tx_id)->find();
        $old['status'] = 2;
        $old['txtime'] = time();
        $rv = M('vip_tx')->save($old);
        if ($rv !== FALSE) {

            $vip = M('vip')->where(array('id' => $old['vipid']))->find();

            //更新shopset提现总手续费
            M('shop_set')->where("id=1")->setInc('total_tx_fee_amount',$old['tx_fee']);

            //更新提现累计
            M('vip')->where(array('id' => $old['vipid']))->setInc('total_tx',$old['txprice']);

            //提现日志
            $log['ip']='';
            $log['vipid']=$vip['id'];
            $log['openid']=$vip['openid'];
            $log['nickname']=$vip['nickname'];
            $log['mobile']=$vip['mobile'];
            $log['event']=$event;
            $log['money']=$old['txprice'];
            $log['fee']=$old['tx_fee'];
            $log['tocard']= 1; //提现到哪里，1：提现到银行卡，2：提现到余额，3：提现到微信钱包，4：提现到支付宝
            $log['ctime']=time();
            $log['tx_id']=$old['id'];
            M('vip_log_tx')->add($log);

            // 发送信息===============
            $wechatTemplate = D('WechatTemplate');
            $wechatTemplate->sendMessage_WithdrawSuccess(['to_user'=>$vip['openid'],'money'=>$old['txprice'],'time'=>time(),'type'=>'wx/bank']);


//                    $customer = M('Wx_customer')->where(array('type' => 'tx2'))->find();
//                    $vip = $mvip->where(array('id' => $old['vipid']))->find();
//                    $msg = array();
//                    $msg['touser'] = $vip['openid'];
//                    $msg['msgtype'] = 'text';
//                    $str = $customer['value'];
//                    $msg['text'] = array('content' => $str);
//                    $ree = $wx->sendCustomMessage($msg);
            // 发送消息完成============

            $data_msg['pids'] = $old['vipid'];
            $data_msg['title'] = "亲爱的用户，提现已完成！金额 " . $old['txprice'] . "已成功发放到您的提现帐户里面了！";
            $data_msg['content'] = "提现订单编号：" . $old['id'] . "<br><br>提现申请" . "：金额 " . $old['txprice'] . "<br><br>提现完成时间：" . date('Y-m-d H:i', $old['txtime']) . "<br><br>您的提现申请已完成，如有异常请联系客服！";
            $data_msg['ctime'] = time();

            $rmsg = M('vip_message')->add($data_msg);
        } else {
            $err = FALSE;
        }

        return true;
    }


}

?>
