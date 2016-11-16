<?php
// +----------------------------------------------------------------------
// | 微信消息模板
// +----------------------------------------------------------------------
namespace Common\Model;

class WechatTemplateModel
{

    protected static $WECHAT = null;

    public function __construct()
    {
        if (empty(self::$WECHAT)) {
            $set = M('set')->find();
            $options['appid'] = $set['wxappid'];
            $options['appsecret'] = $set['wxappsecret'];
            $options['debug'] = true;
            $options['logcallback'] = 'wx_log_write';
            self::$WECHAT = new \Util\Wx\Wechat($options);
        }
    }

    // 获取模板ID
    public function getTemplateId($templateidshort)
    {
        $template = M('Wx_template')->where(array('templateidshort' => $templateidshort))->find();
        if ($template) {
            return $template['templateid'];
        } else {
            return false;
        }
    }


    /**
     * 成为分销商通知-分销商申请成功提醒
     * @template_id_short OPENTM401833772
     * @param $data ['to_user'=>'openid','vip'=>['id'=>0,'nickname'=>'','apply_time'=>0]]
     * @return boolean
     */
    public function sendMessage_BecomeFx($data)
    {
        return $this->sendMessage_BecomeFx_Template($data);
    }

    public function sendMessage_BecomeFx_Template($data)
    {
        $sendData['touser'] = $data['to_user'];
        $sendData['template_id'] = $this->getTemplateId('OPENTM401833772');

        $vip = $data['vip'];

        $sendData['url'] = "http://" . $_SERVER['HTTP_HOST'] . U('App/Vip/index');

        $sendData['data'] = [
            "first" => [
                "value" => "恭喜你成为分销商"
            ],
            'keyword1' => [
                'value' => $vip['nickname']
            ],
            'keyword2' => [
                'value' => date('Y-m-d H:i', $vip['apply_time'])
            ],
            'remark' => [
                'value' => '轻轻动动手指，躺着也能赚钱'
            ]
        ];

        return self::$WECHAT->sendTemplateMessage($sendData);
    }

    public function sendMessage_BecomeFx_Custom($data)
    {
        $sendData['touser'] = $data['touser'];
        $sendData['msgtype'] = 'text';

        $url = $data['url'];
        $title = $data['title'];
        $content = $data['content'];

        if (empty($title)) {
            $title = '恭喜您，晋升为【分销商】';
        }

        if (empty($url)) {
            $url = "http://" . $_SERVER['HTTP_HOST'] . U('App/Vip/index');
        }

        if (empty($content)) {
            $content = "把产品链接分享给好友或朋友圈，只要Ta点击链接就可成为你的下级，Ta和他的下级消费的任何产品都有你的一份哦！\n\n轻轻动动手指，躺着也能赚钱";
        }


        $sendData['text'] = [
            'content' => "$title\n\n$content\n\n<a href=\"$url\">我的推广中心</a>"
        ];

        return self::$WECHAT->sendCustomMessage($sendData);
    }

    /**
     * 成为团长通知-会员升级通知
     * @template_id_short OPENTM202674979
     * @param $data ['to_user'=>'openid','vip'=>['id'=>0,'nickname'=>'']]
     * @return boolean
     */
    public function sendMessage_BecomeTuanZhang($data)
    {
        return $this->sendMessage_BecomeTuanZhang_Template($data);
    }

    public function sendMessage_BecomeTuanZhang_Template($data)
    {
        $sendData['touser'] = $data['to_user'];
        $sendData['template_id'] = $this->getTemplateId('OPENTM202674979');

        $vip = $data['vip'];

        $sendData['url'] = "http://" . $_SERVER['HTTP_HOST'] . U('App/Vip/index');

        $sendData['data'] = [
            "first" => [
                "value" => "尊敬的【" . $vip['nickname'] . "】恭喜你成功升级为VIA"
            ],
            'keyword1' => [
                'value' => $vip['id']
            ],
            'keyword2' => [
                'value' => '长期有效'
            ],
            'remark' => [
                'value' => '点击查看贵宾礼遇，期待您再次光临'
            ]
        ];

        return self::$WECHAT->sendTemplateMessage($sendData);
    }

    public function sendMessage_BecomeTuanZhang_Custom($data)
    {
        $sendData['touser'] = $data['touser'];
        $sendData['msgtype'] = 'text';

        $url = $data['url'];
        $title = $data['title'];
        $content = $data['content'];

        if (empty($title)) {
            $title = '恭喜您，晋升为【团长】';
        }

        if (empty($url)) {
            $url = "http://" . $_SERVER['HTTP_HOST'] . U('App/Vip/index');
        }

        if (empty($content)) {
            $content = "推荐身边朋友开通线上店铺，开通之后，每成交一单，您均可享受提成！";
        }

        $sendData['text'] = [
            'content' => "$title\n\n$content\n\n<a href=\"$url\">我的招商中心</a>"
        ];

        return self::$WECHAT->sendCustomMessage($sendData);
    }

    /**
     * 成为商家通知
     * @template_id_short OPENTM207419103
     * @param $data ['to_user'=>'openid','supplier_name'=>'']
     * @return boolean
     */
    public function sendMessage_BecomeSupplier($data)
    {
        return $this->sendMessage_BecomeSupplier_Template($data);
    }

    public function sendMessage_BecomeSupplier_Template($data)
    {
        $sendData['touser'] = $data['to_user'];
        $sendData['template_id'] = $this->getTemplateId('OPENTM207419103');

        $supplierName = $data['supplier_name'];
        $sendData['url'] = "http://" . $_SERVER['HTTP_HOST'] . U('App/Vip/index');

        $shopSet = M('shop_set')->find();
        $sendData['data'] = [
            "first" => [
                "value" => "欢迎您入驻" . $shopSet['name']
            ],
            'keyword1' => [
                'value' => $supplierName
            ],
            'keyword2' => [
                'value' => '审核通过'
            ],
            'remark' => [
                'value' => '点击查看贵宾礼遇，期待您再次光临'
            ]
        ];

        return self::$WECHAT->sendTemplateMessage($sendData);
    }

    public function sendMessage_BecomeSupplier_Custom($data)
    {
        $sendData['touser'] = $data['touser'];
        $sendData['msgtype'] = 'text';

        $url = $data['url'];
        $title = $data['title'];
        $content = $data['content'];

        $supplier = $data['supplier'];
        if (empty($title)) {
            $title = "【" . $supplier['name'] . "】欢迎加入商盟";
        }

        if (empty($url)) {
            $url = "http://" . $_SERVER['HTTP_HOST'] . U('App/Vip/store');
        }

        if (empty($content)) {
            $content = "在这里，可以扩大您的客户群，宣传您的店铺，有大批量分销商帮您分销商品，同时还可享受消费全返！";
        }

        $sendData['text'] = [
            'content' => "$title\n\n$content\n\n<a href=\"$url\">我的店铺</a>"
        ];

        return self::$WECHAT->sendCustomMessage($sendData);
    }

    /**
     * 新增下级通知
     * @template_id_short OPENTM207679900
     * @param $data ['to_user'=>'openid','sub_vip'=>['id'=>0,'nickname'=>'','join_time'=>0]]
     * @return boolean
     */
    public function sendMessage_SubordinateInc($data)
    {
        return $this->sendMessage_SubordinateInc_Template($data);
    }

    public function sendMessage_SubordinateInc_Template($data)
    {
        $sendData['touser'] = $data['touser'];
        $sendData['template_id'] = $this->getTemplateId('OPENTM207679900');

        //$vip = $data['sub_vip'];

        //$sendData['url'] = "http://" . $_SERVER['HTTP_HOST'] . U('App/Shop/orderList?sid=0');
        $sendData['url'] = "http://" . $_SERVER['HTTP_HOST'] . $data['url'];

        $sendData['data'] = [
            "first" => [
                //"value" => "会员【" . $vip['nickname'] . "】 加入您的亲友团，他将和你一起与价格战斗 ^_^ "
                "value" => $data['content']
            ],
            'keyword1' => [
                'value' => $data['id']
            ],
            'keyword2' => [
                'value' => date('Y-m-d H:i', $data['time'])
            ],
            'remark' => [
                'value' => '感谢您对小猫飞购的支持'
            ]
        ];

        return self::$WECHAT->sendTemplateMessage($sendData);
    }

    public function sendMessage_SubordinateInc_Custom($data)
    {
        $sendData['touser'] = $data['touser'];
        $sendData['msgtype'] = 'text';

        $url = $data['url'];
        $title = $data['title'];
        $content = $data['content'];

        if (empty($title)) {
            $title = '恭喜您，喜得一员猛将';
        }

        if (empty($url)) {
            $url = "http://" . $_SERVER['HTTP_HOST'] . U('App/Fx/index');
        }

        $vip = $data['vip'];
        if (empty($content)) {
            $content = "会员【" . $vip['id'] . "-" . $vip['nickname'] . "】加入您的推广团队\n\n轻轻动动手指，躺着也能赚钱";
        }

        $sendData['text'] = [
            'content' => "$title\n\n$content\n\n<a href=\"$url\">查看详情</a>"
        ];

        return self::$WECHAT->sendCustomMessage($sendData);
    }

    /**
     * 简单消息通知
     */
    public function sendMessage_simple($data){
        $sendData['touser'] = $data['touser'];
        $sendData['msgtype'] = 'text';
        $sendData['text'] = [
            'content' => $data['content']."\n".'  '.date('Y年m月d日 H:i:s')
        ];
        return self::$WECHAT->sendCustomMessage($sendData);
    }

    /**
     * 支付订单通知 - 消费者
     * @template_id_short TM00015
     * @param $data ['to_user'=>'openid','pay_price'=>0,'order_product'=>'']
     * @return boolean
     */
    public function sendMessage_PayOrder($data)
    {
        return $this->sendMessage_PayOrder_Template($data);
    }

    public function sendMessage_PayOrder_Template($data)
    {
        $sendData['touser'] = $data['touser'];
        $sendData['template_id'] = $this->getTemplateId('TM00015');

        $orderProduct = $data['order_product'];
        $payPrice = number_format($data['pay_price'], 2);
        if($data['is_group_buy']) {
            $sendData['url'] = "http://" . $_SERVER['HTTP_HOST'] . U('App/Shop/groupOrderList?sid=0');
            $msg = '我们已收到您的货款，拼团成功后，我们将尽快为您发货。您可以将商品分享给小伙伴，来加速拼团成功哦！';
        }else{
            $sendData['url'] = "http://" . $_SERVER['HTTP_HOST'] . U('App/Shop/orderList?sid=0');
            $msg = "我们已收到您的货款，开始为您打包商品，请耐心等待 : )";
        }

        $sendData['data'] = [
            "first" => [
                "value" => $msg
            ],
            'orderMoneySum' => [
                'value' => $payPrice . '元'
            ],
            'orderProductName' => [
                'value' => $orderProduct
            ],
            'Remark' => [
                'value' => '感谢您对小猫的支持。如有疑问，请联系客服哦 ^_^'
            ]
        ];

        return self::$WECHAT->sendTemplateMessage($sendData);
    }

    public function sendMessage_PayOrder_Custom($data)
    {
        $sendData['touser'] = $data['touser'];
        $sendData['msgtype'] = 'text';

        $url = $data['url'];
        $title = $data['title'];
        $content = '';
        if (empty($title)) {
            $title = '订单支付通知';
        }

        if (empty($url)) {
            $url = "http://" . $_SERVER['HTTP_HOST'] . U('App/Vip/orderList');
        }

        $content .= "订单编号：" . $data['order_code'] . "\n";
        $content .= "商品描述：" . $data['order_product'] . "\n";
        $content .= "订单金额：" . $data['total_price'] . "\n";
        $content .= "支付金额：" . $data['pay_price'] . "\n";
        $content .= "支付时间：" . date('Y-m-d H:i:s', $data['pay_time']) . "\n";
        $content .= "支付方式：" . getPayTypeMsg($data['pay_type']) . "\n";
        $content .= "订单状态：" . "已支付\n";

        $sendData['text'] = [
            'content' => "$title\n\n$content\n\n<a href=\"$url\">查看详情</a>"
        ];

        return self::$WECHAT->sendCustomMessage($sendData);
    }

    /**
     * 订单发货通知
     * @template_id_short OPENTM202243318
     * @param $data ['to_user'=>'openid','order_product'=>'','order_id'=>0,'order_code'=>'','express_name'=>'','express_num'=>'','receiver'=>['name'=>'','mobile'=>'','address'=>'']]
     * @return boolean
     */
    public function sendMessage_DeliverGoods($data)
    {
        return $this->sendMessage_DeliverGoods_Template($data);
    }

    public function sendMessage_DeliverGoods_Template($data)
    {
        $sendData['touser'] = $data['to_user'];
        $sendData['template_id'] = $this->getTemplateId('OPENTM202243318');

        //['to_user'=>'openid','order_product'=>'','order_id'=>0,'order_code'=>'','express_name'=>'','express_num'=>'','receiver'=>['name'=>'','mobile','address'=>'']]
        $orderProduct = $data['order_product'];
        $orderCode = $data['order_code'];
        $endTime = date('Y-m-d H:i', $data['time']);
        $expressName = $data['express_name'];
        $expressNum = $data['express_num'];
        $receiver = $data['receiver'];

        if($data['url']){
            $sendData['url'] = "http://" . $_SERVER['HTTP_HOST'] . $data['url'];
        }else{
            $sendData['url'] = "http://" . $_SERVER['HTTP_HOST'] . U('App/Shop/orderList?sid=0&type=2');
        }

        $sendData['data'] = [
            "first" => [
                "value" => "嗖嗖嗖，您购买的物品已发货，我们正加速送到您的手上"
            ],
            'keyword1' => [
                'value' => $orderProduct
            ],
            'keyword2' => [
                'value' => $expressName
            ],
            'keyword3' => [
                'value' => $expressNum
            ],
            'keyword4' => [
                'value' => $receiver['name'] . ' ' . $receiver['address']
            ],
            'remark' => [
                'value' => '如有疑问，请联系客服'
            ]
        ];

        return self::$WECHAT->sendTemplateMessage($sendData);
    }

    public function sendMessage_DeliverGoods_Custom($data)
    {
        $sendData['touser'] = $data['touser'];
        $sendData['msgtype'] = 'text';

        $url = $data['url'];
        $title = $data['title'];
        $content = '';
        if (empty($title)) {
            $title = '订单发货通知';
        }

        if (empty($url)) {
            $url = "http://" . $_SERVER['HTTP_HOST'] . U('App/Vip/orderList');
        }

        $content .= "订单编号：" . $data['order_code'] . "\n";
        $content .= "商品描述：" . $data['order_product'] . "\n";
        $content .= "订单金额：" . $data['total_price'] . "\n";
        $content .= "支付金额：" . $data['pay_price'] . "\n";
        $content .= "支付时间：" . date('Y-m-d H:i:s', $data['pay_time']) . "\n";
        $content .= "支付方式：" . getPayTypeMsg($data['pay_price']) . "\n";
        $content .= "订单状态：" . "已发货\n";
        if (!empty($data['express_num'])) {
            $content .= "快递公司：" . $data['express_name'] . "\n";
            $content .= "快递单号：" . $data['express_num'] . "\n";
        }

        $sendData['text'] = [
            'content' => "$title\n\n$content\n\n<a href=\"$url\">查看详情</a>"
        ];

        return self::$WECHAT->sendCustomMessage($sendData);
    }


    /**
     * 订单交易成功通知 - 消费者
     * @template_id_short OPENTM202521011
     * @param $data ['to_user'=>'openid','order_id'=>0,'order_code'=>'','end_time'=>0]
     * @return boolean
     */
    public function sendMessage_OrderTradeSuccess($data)
    {
        return $this->sendMessage_OrderTradeSuccess_Template($data);
    }

    public function sendMessage_OrderTradeSuccess_Template($data)
    {
        $sendData['touser'] = $data['to_user'];
        $sendData['template_id'] = $this->getTemplateId('OPENTM202521011');

        $orderCode = $data['order_code'];
        $endTime = date('Y-m-d H:i', $data['end_time']);

        $sendData['url'] = "http://" . $_SERVER['HTTP_HOST'] . U('App/Vip/index');

        $sendData['data'] = [
            "first" => [
                "value" => "尊敬的用户您好，您的订单已完成。"
            ],
            'keyword1' => [
                'value' => $orderCode
            ],
            'keyword2' => [
                'value' => $endTime
            ],
            'remark' => [
                'value' => '如有疑问，请联系客服'
            ]
        ];

        return self::$WECHAT->sendTemplateMessage($sendData);
    }

    public function sendMessage_OrderTradeSuccess_Custom($data)
    {
        $sendData['touser'] = $data['touser'];
        $sendData['msgtype'] = 'text';

        $url = $data['url'];
        $title = $data['title'];
        $content = '';
        if (empty($title)) {
            $title = '订单交易完成通知';
        }

        if (empty($url)) {
            $url = "http://" . $_SERVER['HTTP_HOST'] . U('App/Vip/orderList');
        }

        $content .= "订单编号：" . $data['order_code'] . "\n";
        $content .= "商品描述：" . $data['order_product'] . "\n";
        $content .= "订单金额：" . $data['total_price'] . "\n";
        $content .= "支付金额：" . $data['pay_price'] . "\n";
        $content .= "订单状态：" . "交易完成" . "\n";
        $content .= "完成时间：" . date('Y-m-d H:i:s', $data['end_time']) . "\n";

        $sendData['text'] = [
            'content' => "$title\n\n$content\n\n<a href=\"$url\">查看详情</a>"
        ];

        return self::$WECHAT->sendCustomMessage($sendData);
    }

    /**
     * 提现申请通知
     * @template_id_short OPENTM400905483
     * @param $data ['to_user'=>'openid','money'=>0,'fee'=>0,'time'=>0,'id'=>0,'type'=>'wx/bank']
     * @return boolean
     */
    public function sendMessage_WithdrawApply($data)
    {
        return $this->sendMessage_WithdrawApply_Template($data);
    }

    private function sendMessage_WithdrawApply_Template($data)
    {

        $sendData['touser'] = $data['to_user'];
        $sendData['template_id'] = $this->getTemplateId('OPENTM400905483');


        $txPrice = $data['money'];
        $txFee = $data['fee'];
        $txApplyTime = $data['time'];

        $sendData['url'] = "http://" . $_SERVER['HTTP_HOST'] . U('App/Vip/txList', ['txid' => $data['id'], 'type' => $data['type'] == 'wx' ? 0 : 1]);

        $sendData['data'] = [
            "first" => [
                "value" => "尊敬的用户，您发起了一笔提现申请"
            ],
            'keyword1' => [
                'value' => date('Y-m-d H:i:s', $txApplyTime)
            ],
            'keyword2' => [
                'value' => $txPrice . '元'
            ],
            'keyword3' => [
                'value' => $txFee . '元'
            ],
            'remark' => [
                'value' => '如有疑问，请详询客服'
            ]
        ];

        return self::$WECHAT->sendTemplateMessage($sendData);
    }

    /**
     * 提现失败通知
     * @template_id_short TM00981
     * @param $data ['to_user'=>'openid','money'=>0,'time'=>0,'id'=>0,'type'=>'wx/bank']
     * @return boolean
     */
    public function sendMessage_WithdrawFailed($data)
    {
        return $this->sendMessage_WithdrawFailed_Template($data);
    }

    private function sendMessage_WithdrawFailed_Template($data)
    {
        $sendData['touser'] = $data['to_user'];
        $sendData['template_id'] = $this->getTemplateId('TM00981');

        $txPrice = $data['money'];
        $txTime = $data['time'];

        $sendData['url'] = "http://" . $_SERVER['HTTP_HOST'] . U('App/Vip/txList', ['txid' => $data['id'], 'type' => $data['type'] == 'wx' ? 0 : 1]);

        $sendData['data'] = [
            "first" => [
                "value" => "尊敬的用户，您有一笔提现申请失败"
            ],
            'time' => [
                'value' => date('Y-m-d H:i:s', $txTime)
            ],
            'money' => [
                'value' => $txPrice . '元'
            ],
            'remark' => [
                'value' => '如有疑问，请详询客服'
            ]
        ];

        return self::$WECHAT->sendTemplateMessage($sendData);
    }

    /**
     * 提现申请审核完成通知 - 提现申请结果通知
     * @template_id_short OPENTM400961687
     * withdraw_type:[wx,bank]
     * @param $data ['to_user'=>'openid','money'=>0,'time'=>0,'type'=>'wx/bank']
     * @return boolean
     */
    public function sendMessage_WithdrawSuccess($data)
    {
        return $this->sendMessage_WithdrawSuccess_Template($data);
    }

    public function sendMessage_WithdrawSuccess_Template($data)
    {
        $sendData['touser'] = $data['to_user'];
        $sendData['template_id'] = $this->getTemplateId('OPENTM400961687');

        $money = number_format($data['money'], 2);
        $time = date('Y-m-d', $data['time']);
        $type = $data['type'];

        $sendData['url'] = "http://" . $_SERVER['HTTP_HOST'] . U('App/Vip/txList', ['txid' => $data['id'], 'type' => $data['type'] == 'wx' ? 0 : 1]);

        $sendData['data'] = [
            "first" => [
                "value" => "您好，您的提现申请已通过"
            ],
            'keyword1' => [
                'value' => $time
            ],
            'keyword2' => [
                'value' => $money
            ],
            'keyword3' => [
                'value' => '申请成功，' . ($type == 'wx' ? '已打到您的微信账户' : '已打到您的银行账户')
            ],
            'remark' => [
                'value' => '如有疑问，请联系客服'
            ]
        ];

        return self::$WECHAT->sendTemplateMessage($sendData);
    }

    public function sendMessage_WithdrawSuccess_Custom($data)
    {
        $sendData['touser'] = $data['touser'];
        $sendData['msgtype'] = 'text';

        $url = $data['url'];
        $title = $data['title'];
        $content = '';
        if (empty($title)) {
            $title = '提现申请审核完成通知';
        }

        if (empty($url)) {
            $url = "http://" . $_SERVER['HTTP_HOST'] . U('App/Vip/index');
        }

        $content .= "提现单号：" . $data['order_code'] . "\n";
        if ($data['withdraw_type'] == 'wx') {
            $content .= "收款银行：" . $data['bank_name'] . "\n";
            $content .= "银行支行：" . $data['bank_sub_name'] . "\n";
            $content .= "收款人：" . $data['user_name'] . "\n";
            $content .= "联系电话：" . $data['user_phone'] . "\n";
            $content .= "银行卡号：" . $data['bank_card_num'] . "\n";
        } else {
            $content .= "收款人：" . $data['user_name'] . "\n";
            $content .= "联系电话：" . $data['user_phone'] . "\n";
            $content .= "微信号：" . $data['wx_code'] . "\n";
        }
        $content .= "提现金额：" . $data['withdraw_price'] . "元\n";
        $content .= "手续费：" . $data['withdraw_fee'] . "元\n";

        $sendData['text'] = [
            'content' => "$title\n\n$content\n\n<a href=\"$url\">查看详情</a>"
        ];

        return self::$WECHAT->sendCustomMessage($sendData);
    }

    /**
     * 分销商获取佣金通知 - 佣金提醒
     * @template_id_short OPENTM201812627
     * @param $data ['to_user'=>'openid','commission_price'=>0,'commission_time'=>0]
     * @return boolean
     */
    public function sendMessage_FxCommission($data)
    {
        return $this->sendMessage_FxCommission_Template($data);
    }

    public function sendMessage_FxCommission_Template($data)
    {
        $sendData['touser'] = $data['to_user'];
        $sendData['template_id'] = $this->getTemplateId('OPENTM201812627');

        $commissionPrice = number_format($data['commission_price'], 2);
        $commissionTime = $data['commission_time'];

        $sendData['url'] = "http://" . $_SERVER['HTTP_HOST'] . U('App/Fx/index');

        $sendData['data'] = [
            "first" => [
                "value" => "您获得了一笔新的佣金"
            ],
            'keyword1' => [
                'value' => $commissionPrice . '元'
            ],
            'keyword2' => [
                'value' => date('Y-m-d H:i', $commissionTime)
            ],
            'remark' => [
                'value' => '请进入分销中心查看详情'
            ]
        ];

        return self::$WECHAT->sendTemplateMessage($sendData);
    }

    private function sendMessage_FxCommission_Custom($data)
    {
        $sendData['touser'] = $data['touser'];
        $sendData['msgtype'] = 'text';

        $url = $data['url'];
        $title = $data['title'];
        $content = '';
        if (empty($title)) {
            $title = '营销团队分佣通知';
        }

        if (empty($url)) {
            $url = "http://" . $_SERVER['HTTP_HOST'] . U('App/Vip/index');
        }
        $content .= "您的团队又为您赚得一笔佣金~~\n\n";
        $content .= "消费金额：" . $data['total_price'] . "元\n";
        $content .= "分得佣金：" . $data['commission_price'] . "元\n";

        $sendData['text'] = [
            'content' => "$title\n\n$content\n\n<a href=\"$url\">查看详情</a>"
        ];

        return self::$WECHAT->sendCustomMessage($sendData);
    }


    /**
     * 会员充值通知
     * @template_id_short TM00009
     * @param $data ['to_user'=>'openid','vip_id'=>0,'money'=>0]
     * @return boolean
     */
    public function sendMessage_Charge($data)
    {
        return $this->sendMessage_Charge_Template($data);
    }

    public function sendMessage_Charge_Template($data)
    {
        $sendData['touser'] = $data['to_user'];
        $sendData['template_id'] = $this->getTemplateId('TM00009');

        $vipId = $data['vip_id'];
        $money = $data['money'];

        $sendData['url'] = "http://" . $_SERVER['HTTP_HOST'] . U('App/Vip/index');;

        $sendData['data'] = [
            "first" => [
                "value" => "您好，您已成功进行会员充值"
            ],
            'accountType' => [
                'value' => '会员ID'
            ],
            'account' => [
                'value' => $vipId
            ],
            'amount' => [
                'value' => $money
            ],
            'result' => [
                'value' => '充值成功'
            ],
            'remark' => [
                'value' => '如有疑问，请联系客服'
            ]
        ];

        return self::$WECHAT->sendTemplateMessage($sendData);
    }


    /**
     * 消费者返现 - 消费者
     * @template_id_short TM00335
     * @param $data ['to_user'=>'openid','vip_id'=>0,'bonus_time'=>'','bonus_money'=>0,'vip_money'=>0]
     * @return boolean
     */
    public function sendMessage_BuyerBonus($data)
    {
        return $this->sendMessage_BuyerBonus_Template($data);
    }


    public function sendMessage_BuyerBonus_Template($data)
    {
        $sendData['touser'] = $data['to_user'];
        $sendData['template_id'] = $this->getTemplateId('TM00335');

        $vipId = $data['vip_id'];
        $money = $data['bonus_money'];
        $bonusTime = $data['bonus_time'];

        $sendData['url'] = "http://" . $_SERVER['HTTP_HOST'] . U('App/Vip/index');;

        $sendData['data'] = [
            "first" => [
                "value" => "您好，您收到一笔系统分红"
            ],
            'account' => [
                'value' => '资金账户'
            ],
            'time' => [
                'value' => date('Y-m-d H:i:s', $bonusTime)
            ],
            'type' => [
                'value' => '消费返现'
            ],
            'creditChange' => [
                'value' => ''
            ],
            'number' => [
                'value' => number_format($money, 2)
            ],
            'amount' => [
                'value' => '点击查看'
            ],
            'creditName' => [
                'value' => '账户'
            ],
            'remark' => [
                'value' => '如有疑问，请联系客服'
            ]
        ];

        return self::$WECHAT->sendTemplateMessage($sendData);
    }

    /**
     * 中奖结果通知
     * @template_id_short OPENTM204632492
     * @param $data ['to_user'=>'openid','title'=>'','prize'=>'']
     * @return boolean
     */
    public function sendMessage_Lottery($data)
    {
        return $this->sendMessage_Lottery_Template($data);
    }

    public function sendMessage_Lottery_Template($data)
    {
        $sendData['touser'] = $data['to_user'];
        $sendData['template_id'] = $this->getTemplateId('OPENTM204632492');

        $title = $data['title'];
        $prize = $data['prize'];

        $sendData['url'] = "http://" . $_SERVER['HTTP_HOST'] . U('App/Vip/index');

        $sendData['data'] = [
            "first" => [
                "value" => "恭喜您参与的活动中奖了！"
            ],
            'keyword1' => [
                'value' => $title
            ],
            'keyword2' => [
                'value' => $prize
            ],
            'remark' => [
                'value' => '兑奖时请出示此通知'
            ]
        ];

        return self::$WECHAT->sendTemplateMessage($sendData);
    }

    /**
     * 申请成为推广人员通知
     * @template_id_short OPENTM207327054
     * @param $data ['to_user'=>'openid','name'=>'','apply_tg_id'=>'']
     * @return boolean
     */
    public function sendMessage_applyTg($data)
    {
        return $this->sendMessage_applyTg_Template($data);
    }

    public function sendMessage_applyTg_Template($data)
    {
        $sendData['template_id'] = $this->getTemplateId('OPENTM401202609');

        $name = $data['name'];
        $sendData['touser'] = $data['to_user'];

        $sendData['url'] = "http://" . $_SERVER['HTTP_HOST'] . U('App/Fx/confirmTg',array('id'=>$data['apply_tg_id']));

        $sendData['data'] = [
            "first" => [
                "value" => ""
            ],
            'keyword1' => [
                'value' => '推广人员'
            ],
            'keyword2' => [
                'value' => $name
            ],
            'keyword3' => [
                'value' => ''
            ],
            'keyword4' => [
                'value' => date('Y-m-d H:i:s',time())
            ],
            'remark' => [
                'value' => '点击查看详情，进行审核'
            ]
        ];

        return self::$WECHAT->sendTemplateMessage($sendData);
    }

    /**
     * 申请结果通知
     * @template_id_short OPENTM406038933
     * @param $data ['to_user'=>'openid','result'=>'' ,'time'=>'']
     * @return boolean
     */
    public function sendMessage_applyResult($data)
    {
        return $this->sendMessage_applyResult_Template($data);
    }

    public function sendMessage_applyResult_Template($data)
    {
        $sendData['template_id'] = $this->getTemplateId('OPENTM406038933');

        $sendData['touser'] = $data['to_user'];

        $sendData['url'] = isset($data['url']) ? $data['url'] : '';

        $sendData['data'] = [
            "first" => [
                "value" => "您的申请(成为推广人员)，已完成审核"
            ],
            'keyword1' => [
                'value' => '申请成为推广人员'
            ],
            'keyword2' => [
                'value' => $data['time']
            ],
            'keyword3' => [
                'value' => $data['result']
            ],

            'remark' => [
                'value' => isset($data['remark']) ? $data['remark'] : ''
            ]
        ];

        return self::$WECHAT->sendTemplateMessage($sendData);
    }

    /**
     * 红包抽奖活动通知
     * @template_id_short OPENTM207273205
     * @param $data ['to_user'=>'openid','result'=>'' ,'time'=>'']
     * @return boolean
     */
    public function sendMessage_lotteryNotify($data)
    {
        return $this->sendMessage_lotteryNotify_Template($data);
    }

    public function sendMessage_lotteryNotify_Template($data)
    {
        $sendData['template_id'] = $this->getTemplateId('OPENTM207273205');

        $sendData['touser'] = $data['to_user'];

        $sendData['url'] = $data['url'];

        $sendData['data'] = [
            "first" => [
                "value" => $data['first']
            ],
            'keyword1' => [
                'value' => $data['name']
            ],
            'keyword2' => [
                'value' => $data['location']
            ],
            'remark' => [
                'value' => $data['remark']
            ]
        ];

        return self::$WECHAT->sendTemplateMessage($sendData);
    }

}

?>
