<?php
// +----------------------------------------------------------------------
// | 单用户微信基础类
// +----------------------------------------------------------------------
namespace Home\Controller;

use App\QRcode;
use Think\Controller;

class WxController extends Controller
{
    //全局相关
    public static $_set; //缓存全局配置
    public static $_shop; //缓存全局配置

    public static $_wx; //缓存微信对象
    public static $_ppvip; //缓存会员通信证模型
    public static $_ppvipmessage; //缓存会员消息模型
    public static $_fx; //缓存分销模型
    public static $_fxlog; //缓存分销新用户推广模型	qd(渠道)=1为朋友圈，2为渠道场景二微码
    public static $_token;
    public static $_location; //用户地理信息
    //信息接收相关
    public static $_revtype; //微信发来的信息类型
    public static $_revdata; //微信发来的信息内容
    //信息推送相关
    //public static $_url='http://shop.hylanca.com/';//推送地址前缀
    public static $_url;
    public static $_wecha_id;
    public static $_actopen;

    public static $WAP;//CMS全局静态变量

    // 自动计算模型
    public static $_demployee;

    public function __construct($options)
    {
        // 读取商城全局配置
        self::$_shop = M('Shop_set')->find();
        //读取用户配置存全局
        self::$_set = M('Set')->find();
        self::$_url = self::$_set['wxurl'];
        self::$_token = self::$_set['wxtoken'];
        //检测token是否合法
        /*$tk = $_GET['token'];
        if ($tk != self::$_token) {
            die('token error');
        }*/
        //缓存微信API模型类
        $options['token'] = self::$_token;
        $options['appid'] = self::$_set['wxappid'];
        $options['appsecret'] = self::$_set['wxappsecret'];
        $options['debug'] = true;
        $options['logcallback'] = 'wx_log_write';
        self::$_wx = new \Util\Wx\Wechat($options);
        //缓存通行证数据模型
        self::$_ppvip = M('Vip');
        self::$_ppvipmessage = M('Vip_message');
        self::$_fx = M('Vip');
        self::$_fxlog = M('Vip_log_sub');
        self::$_demployee = D('Employee');

        self::$WAP['vipset'] = $this->checkVipSet();
        //判断验证模式
        if (IS_GET) {
            self::$_wx->valid();
        } else {
            if (!self::$_wx->valid(true)) {
                die('no access!!!');
            }
            //读取微信平台推送来的信息类型存全局
            self::$_revtype = self::$_wx->getRev()->getRevType();
            //读取微型平台推送来的信息存全局
            self::$_revdata = self::$_wx->getRevData();
            self::$_wecha_id = self::$_wx->getRevFrom();
            //读取用户地理信息
            //self::$_location=self::$_wx->getRevData();
//            $str = "";
//            foreach (self::$_revdata as $k => $v) {
//                $str = $str . $k . "=>" . $v . '  ';
//            }
//            file_put_contents('./Data/app_rev.txt', '收到请求:' . date('Y-m-d H:i:s') . PHP_EOL . '通知信息:' . $str . PHP_EOL . PHP_EOL . PHP_EOL, FILE_APPEND);

        }

    }

    //返回VIP配置
    public function checkVipSet()
    {
        $set = M('Vip_set')->find();
        return $set ? $set : utf8error('会员设置未定义！');
    }

    public function index()
    {

        $this->go();

    } //index类结束

    /*微信访问判断主路由控制器by App
    return
     */
    public function go()
    {

        switch (self::$_revtype) {
            case \Util\Wx\Wechat::MSGTYPE_TEXT:
                $this->checkKeyword(self::$_revdata['Content']);
                //self::$_wx->text(self::$_revdata['Content'])->reply();
                break;
            case \Util\Wx\Wechat::MSGTYPE_EVENT:
                $this->checkEvent(self::$_revdata['Event']);
                break;
            case \Util\Wx\Wechat::MSGTYPE_IMAGE:
                //$this -> checkImg();
                self::$_wx->text('本系统暂不支持图片信息！')->reply();
                break;
            default:
                self::$_wx->text("本系统暂时无法识别您的指令！")->reply();
        }

    } //end go

    /*关键词指引
    return
     */
    public function checkKeyword($key)
    {

        //更新认证服务号的微信用户表信息（24小时内）
        $reUP = $this->updateUser(self::$_wecha_id);

        //App调试模式
        if (substr($key, 0, 5) == 'App-') {
            $this->toApp(substr($key, 5));
        }

        //强制关键词匹配
        //*********************************************************************
        if ($key == '操作指导') {
            $msg = '未配置';
            self::$_wx->text($msg)->reply();
        }
        if ($key == "员工二维码") {

            // 获取用户信息
            $map['openid'] = self::$_revdata['FromUserName'];
            $vip = self::$_ppvip->where($map)->find();

            // 用户校正
            if (!$vip) {
                $msg = "用户信息缺失，请重新关注公众号";
                self::$_wx->text($msg)->reply();
                exit();
            }

            // 获取员工信息
            $employee = M('Employee')->where(array('vipid' => $vip['id']))->find();

            // 员工校正
            if (!$employee) {
                $msg = "抱歉，您不是员工，请先联系系统管理员！";
                self::$_wx->text($msg)->reply();
                exit();
            }

            // 过滤连续请求-打开
            if (F("employee" . $vip['openid']) != null) {
                $msg = "员工二维码正在生成，请稍等！";
                self::$_wx->text($msg)->reply();
                exit();
            } else {
                F("employee" . $vip['openid'], $vip['openid']);
            }

            // 生产二维码基本信息，存入本地文档，获取背景
            $background = $this->createQrcodeBgEmp();
            //$qrcode = $this->createQrcode($vip['id'],$vip['openid']);
            $qrcode = $this->createEmployeeQrcode($employee['id'], $vip['openid']);
            if (!$qrcode) {
                $msg = "员工二维码 生成失败";
                self::$_wx->text($msg)->reply();
                F("employee" . $vip['openid'], null);
                exit();
            }
            // 生产二维码基本信息，存入本地文档，获取背景 结束

            // 获取头像信息
            $mark = false; // 是否需要写入将图片写入文件
            $headimg = $this->getRemoteHeadImage($vip['headimgurl']);
            if (!$headimg) {// 没有头像先从头像库查找，再没有就选择默认头像
                if (file_exists('./QRcode/headimg/' . $vip['openid'] . '.jpg')) { // 获取不到远程头像，但存在本地头像，需要更新
                    $headimg = file_get_contents('./QRcode/headimg/' . $vip['openid'] . '.jpg');
                } else {
                    $headimg = file_get_contents('./QRcode/headimg/' . 'default' . '.jpg');
                }
                $mark = true;
            }
            $headimg = imagecreatefromstring($headimg);
            // 获取头像信息 结束

            // 生成二维码推广图片=======================

            // Combine QRcode and background and HeadImg
            $b_width = imagesx($background);
            $b_height = imagesy($background);
            $q_width = imagesx($qrcode);
            $q_height = imagesy($qrcode);
            $h_width = imagesx($headimg);
            $h_height = imagesy($headimg);
            imagecopyresampled($background, $qrcode, $b_width * 0.24, $b_height * 0.5, 0, 0, $q_width * 1.5, $q_height * 1.5, $q_width, $q_height);
            imagecopyresampled($background, $headimg, $b_width * 0.10, 12, 0, 0, 120, 120, $h_width, $h_height);

            // Set Font Type And Color
            $fonttype = './Public/Common/fonts/wqy-microhei.ttc';
            $fontcolor = imagecolorallocate($background, 0x00, 0x00, 0x00);

            // Combine All And Text, Then store in local
            imagettftext($background, 18, 0, 280, 100, $fontcolor, $fonttype, $vip['nickname']);
            imagejpeg($background, './QRcode/promotion/' . "employee" . $vip['openid'] . '.jpg');

            // 生成二维码推广图片 结束==================

            // 上传下载相应
            if (file_exists(getcwd() . "/QRcode/promotion/" . "employee" . $vip['openid'] . '.jpg')) {
                $data = array('media' => '@' . getcwd() . "/QRcode/promotion/" . "employee" . $vip['openid'] . '.jpg');
                $uploadresult = self::$_wx->uploadMedia($data, 'image');
                self::$_wx->image($uploadresult['media_id'])->reply();
            } else {
                $msg = "员工二维码生成失败";
                self::$_wx->text($msg)->reply();
            }
            // 上传下载相应 结束

            // 过滤连续请求-关闭
            F("employee" . $vip['openid'], null);

            // 后续数据操作（写入头像到本地，更新个人信息）
            if ($mark) {
                $tempvip = $this->apiClient(self::$_revdata['FromUserName']);
                $vip['nickname'] = $tempvip['nickname'];
                $vip['headimgurl'] = $tempvip['headimgurl'];
            } else {
                // 将头像文件写入
                imagejpeg($headimg, './QRcode/headimg/' . $vip['openid'] . '.jpg');
            }
        }


        if ($key == "推广二维码") {

            // 获取用户信息
            $map['openid'] = self::$_revdata['FromUserName'];
            $vip = self::$_ppvip->where($map)->find();

            // 用户校正
            if (!$vip) {
                $msg = "用户信息缺失，请重新关注公众号";
                self::$_wx->text($msg)->reply();
                exit();
            } /*else if ($vip['isfx'] == 0) {
                $msg = "您还未成为" . self::$_shop['fxname'] . "，请先购买成为" . self::$_shop['fxname'] . "！";
                self::$_wx->text($msg)->reply();
                exit();
            }*/

            // 过滤连续请求-打开
            // if (F($vip['openid']) != null) {
            //     $msg = "推广二维码正在生成，请稍等！";
            //     self::$_wx->text($msg)->reply();
            //     exit();
            // } else {
            //     F($vip['openid'], $vip['openid']);
            // }

            // 生产二维码基本信息，存入本地文档，获取背景
            $background = $this->createQrcodeBg();
            $qrcode = $this->createQrcode($vip['id'], $vip['openid']);      //生成二维码
            if (!$qrcode) {
                $msg = "专属二维码 生成失败";
                self::$_wx->text($msg)->reply();
                F($vip['openid'], null);
                exit();
            }
            // 生产二维码基本信息，存入本地文档，获取背景 结束

            // 获取头像信息
            $mark == false; // 是否需要写入将图片写入文件
            $headimg = $this->getRemoteHeadImage($vip['headimgurl']);
            if (!$headimg) {// 没有头像先从头像库查找，再没有就选择默认头像
                if (file_exists('./QRcode/headimg/' . $vip['openid'] . '.jpg')) { // 获取不到远程头像，但存在本地头像，需要更新
                    $headimg = file_get_contents('./QRcode/headimg/' . $vip['openid'] . '.jpg');
                } else {
                    $headimg = file_get_contents('./QRcode/headimg/' . 'default' . '.jpg');
                }
                $mark = true;
            }
            $headimg = imagecreatefromstring($headimg);
            // 获取头像信息 结束

            // 生成二维码推广图片=======================

            // Combine QRcode and background and HeadImg
            $b_width = imagesx($background);
            $b_height = imagesy($background);
            $q_width = imagesx($qrcode);
            $q_height = imagesy($qrcode);
            //在二维码中间放上logo  2016年8月16日18:28:28加上
            if(file_exists('./Public/Public/logo.jpg')){
                $logo = imagecreatefromjpeg('./Public/Public/logo.jpg');
                imagecopyresampled($qrcode,$logo,$q_width/2-60,$q_height/2-60,0,0,120,120,120,120);
            }

            $h_width = imagesx($headimg);
            $h_height = imagesy($headimg);
            imagecopyresampled($background, $qrcode, 126, 144, 0, 0, 284, 284, $q_width, $q_height);    //二维码放进背景
            imagecopyresampled($background, $headimg, 40, 645, 0, 0, 108, 108, $h_width, $h_height); //头像放进背景

            // Set Font Type And Color
            $fonttype = './Public/Common/fonts/wqy-microhei.ttc';
            $fontcolor = imagecolorallocate($background, 0xff, 0xff, 0xff); //白色

            // Combine All And Text, Then store in local
            //根据名字的长度预估大概位置
            if(strlen($vip['nickname']) > 20){
                $fontSize = 18;
            }else{
                $fontSize = 22;
            }
            imagettftext($background, $fontSize, 0, 223, 686, $fontcolor, $fonttype, $vip['nickname']);    //名字写进背景
            imagettftext($background, 18, 0, 223, 730, $fontcolor, $fonttype, '的 校 园 购 物 商 城');
            imagejpeg($background, './QRcode/promotion/' . $vip['openid'] . '.jpg');

            // 生成二维码推广图片 结束==================

            // 上传下载相应
            if (file_exists(getcwd() . "/QRcode/promotion/" . $vip['openid'] . '.jpg')) {
                $data = array('media' => '@' . getcwd() . "/QRcode/promotion/" . $vip['openid'] . '.jpg');
                $uploadresult = self::$_wx->uploadMedia($data, 'image');
                if (empty($uploadresult['media_id'])) {
                    $msg = "专属二维码生成失败，请稍后重试！";
                    self::$_wx->text($msg)->reply();
                } else {
                    self::$_wx->image($uploadresult['media_id'])->reply();
                }
            } else {
                $msg = "专属二维码生成失败";
                self::$_wx->text($msg)->reply();
            }
            // 上传下载相应 结束

            // 过滤连续请求-关闭
            F($vip['openid'], null);

            // 后续数据操作（写入头像到本地，更新个人信息）
            if ($mark) {
                $tempvip = $this->apiClient(self::$_revdata['FromUserName']);
                $vip['nickname'] = $tempvip['nickname'];
                $vip['headimgurl'] = $tempvip['headimgurl'];
            } else {
                // 将头像文件写入
                imagejpeg($headimg, './QRcode/headimg/' . $vip['openid'] . '.jpg');
            }

        }
        //用户自定义关键词匹配
        //*********************************************************************
        $mapkey['keyword'] = $key;
        //用户自定义关键词
        $keyword = M('Wx_keyword');
        $ruser = $keyword->where($mapkey)->find();
        if ($ruser) {
            //进入用户自定义关键词回复
            $this->toKeyUser($ruser);
        }
        //*********************************************************************

        //系统自定义关键词数组
        //$osWgw=array('官网','首页','微官网','Home','home','Index','index');
        //if(in_array($key,$osWgw)){$this->toWgw('index',false);}

        //未知关键词匹配
        //*********************************************************************
        $this->toKeyUnknow();
    }


    public function checkEvent($event)
    {
        switch ($event) {
            //首次关注事件
            case 'subscribe':
                $this->subscribe();
                break;
            //取消关注事件
            case 'unsubscribe':
                $this->unsubscribe();
                break;
            //自定义菜单点击事件
            case 'CLICK':
                $key = self::$_revdata['EventKey'];
                //self::$_wx->text('菜单点击拦截'.self::$_revdata['EventKey'].'!')->reply();
                switch ($key) {
                    case '#sy':
                        break;
                }
                //不存在拦截命令,走关键词流程
                $this->checkKeyword($key);
                break;

        }
    }

    private function subscribe()
    {
        //用户关注：判断是否已存在
        //检查用户是否已存在
        $old['openid'] = self::$_revdata['FromUserName'];
        $isold = self::$_ppvip->where($old)->find();
        if ($isold) {
            $data['subscribe'] = 1;
            $data['subscribe_time'] = time();
            $data['cctime'] = time();
            $re = self::$_ppvip->where($old)->save($data);
            //增加上线关注人数
            if ($isold['pid']) {
                $fxs = self::$_fx->where('id=' . $isold['pid'])->find();
                if ($fxs) {
                    $dlog['ppid'] = $isold['pid'];
                    $dlog['from'] = $isold['id'];
                    $dlog['fromname'] = $isold['nickname'];
                    $dlog['to'] = $fxs['id'];
                    $dlog['toname'] = $fxs['nickname'];
                    $dlog['issub'] = 1;
                    $dlog['ctime'] = time();
                    $rdlog = self::$_fxlog->add($dlog);
                    $rfxs = self::$_fx->where('id=' . $isold['pid'])->setInc('total_xxsub', 1);    //下线累计关注
                } else {
                    $dlog['ppid'] = 0;
                    $dlog['from'] = $isold['id'];
                    $dlog['fromname'] = $isold['nickname'];
                    $dlog['to'] = 0;
                    $dlog['toname'] = self::$_shop['name'];
                    $dlog['issub'] = 1;
                    $dlog['ctime'] = time();
                    $rdlog = self::$_fxlog->add($dlog);
                }
            }

//            $tourl = self::$_url . '/App/Shop/index/ppid/' . $isold['id'] . '/';
//            $str = "<a href='" . $tourl . "'>" . htmlspecialchars_decode(self::$_set['wxsummary']) . "</a>";
            // self::$_wx->text($str)->reply();
            $subUser = $isold;
        } else {
            $user = $this->apiClient(self::$_revdata['FromUserName']);  //关注者信息
            //通过谁的推广二维码进来的
            $pid = 0;
            $old = array();
            if (!empty(self::$_revdata['Ticket'])) {
                $ticket = self::$_revdata['Ticket'];
                $old = self::$_ppvip->where(array("ticket" => $ticket))->find();
                if ($old) {
                    $pid = $old["id"];

                    //判断pid 三级。
                    $pdata = M('vip')->where(array('id' => $pid))->find();
                    $sub_num = M('shop_set')->field('vip_sub_num')->find();
                    if ($pdata['role'] == 1){
                        $user['role_1_id'] = $pid;
                        $user['role_2_id'] = $user['role_3_id'] = 0;
                    } elseif ($pdata['role'] == 2){
                        $user['role_1_id'] = $pdata['role_1_id'];
                        $user['role_2_id'] = $pid;
                        $user['role_3_id'] = 0;
                    } elseif ($pdata['role'] == 3){
                        $user['role_1_id'] = $pdata['role_1_id'];
                        $user['role_2_id'] = $pdata['role_2_id'];
                        $user['role_3_id'] = $pid;
                    } else {
                        $user['role_1_id'] = $pdata['role_1_id'];
                        $user['role_2_id'] = $pdata['role_2_id'];
                        $user['role_3_id'] = $pdata['role_3_id'];
                    }

                    //$user['school_id'] = $pdata['school_id'];不再绑定扫码关注着的学校id,改由进入页面提示自己选择

                    if($sub_num['vip_sub_num'] != 0 && $pdata['is_vip'] == 0 && $pdata['total_xxsub'] >= $sub_num['vip_sub_num']){
                        M('vip')->where(array('id'=>$pdata['id']))->setField(array('is_vip'=>1,'create_vip'=>time()));
                    }
                }
            }
            if ($user) {
                //新用户注册政策
                $vipset = M('Vip_set')->find();
                $user['score'] = $vipset['reg_score'];
                $user['exp'] = $vipset['reg_exp'];
                $user['cur_exp'] = $vipset['reg_exp'];
                //$level=$this->getLevel($user['exp']);报错
                $user['levelid'] = 1;
                //追入首次时间和更新时间
                $user['ctime'] = $user['cctime'] = time();

                //TODO 关注即刻成为分销商
                $user['isfx'] = 1;
                $user['reg_fx_time'] = $user['ctime'];

                //系统追入path 追入员工
                if ($old['id']) {
                    $user['pid'] = $old['id'];
                    $user['path'] = $old['path'] . '-' . $old['id'];
                    $user['plv'] = $old['plv'] + 1;
                    $user['employee'] = $old['employee'];
                } else {
                    $user['pid'] = 0;
                    $user['path'] = 0;
                    $user['plv'] = 1;
                    $user['employee'] = D('Employee')->randomEmployee();
                }

                //TODO 关注送10元
                $cur_time = time();
                $start_time = strtotime('2016-07-29 0:0:0');
                $end_time = strtotime('2016-07-30 18:0:0');
                if ($cur_time >= $start_time && $cur_time <= $end_time) {
                    $subPrizeMoney = 0;
                } else {
                    $subPrizeMoney = 0;
                }
                $user['money'] = $subPrizeMoney;

                $revip = self::$_ppvip->add($user);

                $subUser = $user;
                $subUser['id'] = $revip;

                if ($revip) {
                    //赠送操作
                    if ($vipset['isgift']) {
                        $gift = explode(",", $vipset['gift_detail']);
                        $cardnopwd = $this->getCardNoPwd();
                        $data_card['type'] = $gift[0];
                        $data_card['vipid'] = $revip;
                        $data_card['money'] = $gift[1];
                        $data_card['usemoney'] = $gift[3];
                        $data_card['cardno'] = $cardnopwd['no'];
                        $data_card['cardpwd'] = $cardnopwd['pwd'];
                        $data_card['status'] = 1;
                        $data_card['stime'] = $data_card['ctime'] = time();
                        $data_card['etime'] = time() + $gift[2] * 24 * 60 * 60;
                        $rcaSrd = M('Vip_card')->add($data_card);
                    }
                    //发送注册通知消息
                    //记录日志
                    $data_log['ip'] = 'wechat';    //源自微信注册
                    $data_log['vipid'] = $revip;
                    $data_log['ctime'] = time();
                    $data_log['openid'] = $user['openid'];
                    $data_log['nickname'] = $user['nickname'];
                    $data_log['event'] = "会员注册";
                    $data_log['score'] = $user['score'];
                    $data_log['exp'] = $user['exp'];
                    $data_log['type'] = 4;
                    $data_log['money'] = $subPrizeMoney;
                    $rlog = M('Vip_log')->add($data_log);
                }
                //追入新用户关注日志
                $dlog['ppid'] = $pid;
                $dlog['from'] = $revip;
                $dlog['fromname'] = $user['nickname'];
                $dlog['to'] = 0;
                $dlog['toname'] = self::$_shop['name'];
                $dlog['issub'] = $user['subscribe'];
                $dlog['ctime'] = time();
                $rdlog = self::$_fxlog->add($dlog);

                //TODO 发送信息给自己

                //处理上级用户信息（下线+1,推荐奖励等）
                $mvip = self::$_ppvip;
                //上级人的信息
                $old = $mvip->where(array('id' => $pid))->find();
                if ($old) {
                    $tj_score = self::$WAP['vipset']['tj_score'];
                    $tj_exp = self::$WAP['vipset']['tj_exp'];
                    $tj_money = self::$WAP['vipset']['tj_money'];
                    if ($tj_score || $tj_exp || $tj_money) {
                        $msg = "推荐新用户奖励：<br>新用户：" . $user['nickname'] . "<br>奖励内容：<br>";
                        $mglog = "获得新用户注册奖励:";
                        if ($tj_score) {
                            $old['score'] = $old['score'] + $tj_score;
                            $msg = $msg . $tj_score . "个积分<br>";
                            $mglog = $mglog . $tj_score . "个积分；";
                        }
                        if ($tj_exp) {
                            $old['exp'] = $old['exp'] + $tj_exp;
                            $msg = $msg . $tj_exp . "点经验<br>";
                            $mglog = $mglog . $tj_exp . "点经验；";
                        }
                        if ($tj_money) {
                            $old['money'] = $old['money'] + $tj_money;
                            $msg = $msg . $tj_money . "元余额<br>";
                            $mglog = $mglog . $tj_money . "元余额；";
                        }
                        $msg = $msg . "此奖励已自动打入您的帐户！感谢您的支持！";
                        $rold = $mvip->save($old);
                        if (FALSE !== $rold) {
                            $data_msg['pids'] = $old['id'];
                            $data_msg['title'] = "你获得一份推荐奖励！";
                            $data_msg['content'] = $msg;
                            $data_msg['ctime'] = time();
                            $rmsg = M('Vip_message')->add($data_msg);
                            $data_mglog['vipid'] = $old['id'];
                            $data_mglog['nickname'] = $old['nickname'];
                            $data_mglog['xxnickname'] = $user['nickname'];
                            $data_mglog['msg'] = $mglog;
                            $data_mglog['ctime'] = time();
                            $rmglog = M('Vx_log_tj')->add($data_mglog);
                        }
                    }
                    //上级用户下线数+1
                    $tmp['total_xxlink'] = $old['total_xxlink'] + 1;
                    $r1 = $mvip->where(['id' => $old['id']])->save($tmp);
                }

                //为上级、一二三级发送模板消息
                $wechatTemplate = D('WechatTemplate');
                // 发送信息给父级--自定义消息
                // user是关注用户 old是上级
                if($old['role']  == 0){     //上级是普通会员则发送此消息
                    $tpl_data['touser'] = $old['openid'];
                    $tpl_data['url'] = U('App/Vip/index');
                    $tpl_data['content'] = '【'.$user['nickname'].'】通过您的分享成为您的猫友团成员，将与您一起与价格战斗，保卫您的钱袋子。';
                    $tpl_data['id'] = $subUser['id'];
                    $tpl_data['time']   = time();

                    $wechatTemplate->sendMessage_SubordinateInc($tpl_data);
                }

                //给三级(推广人员)发送消息
                if($user['role_3_id']){
                    $tpl_data['touser'] = $mvip->where('id='.$user['role_3_id'])->getField('openid');
                    $tpl_data['content'] = '恭喜您，【' . $user['nickname'] . '】成为您的商城客户，他(她)的每次消费都将为您带来收益，加油哟，让更多的朋友认识您的小猫飞购';
                    $tpl_data['url'] = U('App/Fx/index');
                    $tpl_data['id'] = $subUser['id'];
                    $tpl_data['time']   = time();
                    $wechatTemplate->sendMessage_SubordinateInc($tpl_data);
                }
                //给二级（VIA）发送消息
                if($user['role_2_id']){
                    $tpl_data['touser'] = $mvip->where('id='.$user['role_2_id'])->getField('openid');
                    $tpl_data['content'] = "【".$user['nickname']."】通过您的推广团队，成为小猫客户。\n 推广人: 【".$old['nickname']."】";
                    $tpl_data['url'] = U('App/Fx/index');
                    $tpl_data['id'] = $subUser['id'];
                    $tpl_data['time']   = time();
                    $wechatTemplate->sendMessage_SubordinateInc($tpl_data);
                }
            } else {
//                $tourl = self::$_url . '/App/Shop/index/';
//                $str = "<a href='" . $tourl . "'>" . htmlspecialchars_decode(self::$_set['wxsummary']) . "</a>";
            }

        }
        if ($subUser) {
            $vipCnt = M('vip')->count();
            $str = htmlspecialchars_decode(self::$_set['wxsummary']);
            $str = preg_replace('/{{ID}}/', $subUser['id'], $str);
            $str = preg_replace('/{{NUM}}/', $vipCnt + self::$_set['init_vip_number'], $str);
        } else {
            $str = '欢迎关注';
        }


        $this->subscribeReturn($str);
    }

    private function unsubscribe()
    {
        //更新库内的用户关注状态字段
        $map['openid'] = self::$_revdata['FromUserName'];
        $old = self::$_ppvip->where($map)->find();
        if ($old) {
            $rold = self::$_ppvip->where($map)->save(['subscribe' => 0, 'cctime' => time()]);//setField('subscribe', 0);
            if ($old['ppid']) {
                $fxs = self::$_fx->where('id=' . $old['ppid'])->find();
                if ($fxs) {
                    $dlog['ppid'] = $old['ppid'];
                    $dlog['from'] = $old['id'];
                    $dlog['fromname'] = $old['nickname'];
                    $dlog['to'] = $fxs['id'];
                    $dlog['toname'] = $fxs['nickname'];
                    $dlog['issub'] = 0;
                    $dlog['ctime'] = time();
                    $rdlog = self::$_fxlog->add($dlog);
                    $rfxs = self::$_fx->where('id=' . $old['ppid'])->setInc('total_xxunsub', 1);    //下线累计取消关注
                }
            } else {
                $dlog['ppid'] = 0;
                $dlog['from'] = $old['id'];
                $dlog['fromname'] = $old['nickname'];
                $dlog['to'] = 0;
                $dlog['toname'] = self::$_shop['name'];
                $dlog['issub'] = 0;
                $dlog['ctime'] = time();
                $rdlog = self::$_fxlog->add($dlog);
            }
        }
    }

    /*高级调试模式 by App
    $type=调试命令
    $App-openid:获取用户openid
     */
    public function toApp($type)
    {
        $title = "App管理员模式：\n命令：" . $type . "\n结果：\n";

        switch ($type) {
            case 'dkf':
                $str = "人工客服接入！";
                self::$_wx->dkf($str)->reply();
                break;
            case 'openid':
                self::$_wx->text($title . self::$_revdata['FromUserName'])->reply();
                break;
            default:
                self::$_wx->text("App:未知命令")->reply();
        }

    }

    /*自定义关键词模式 by App
    $ruser=关键词记录
     */
    public function toKeyUser($ruser)
    {
        $type = $ruser['type'];
        switch ($type) {
            //文本
            case "1":
                self::$_wx->text($ruser['summary'])->reply();
                break;
            //单图文
            case "2":
                $news[0]['Title'] = $ruser['name'];
                $news[0]['Description'] = $ruser['summary'];
                $img = $this->getPic($ruser['pic']);
                $news[0]['PicUrl'] = $img['imgurl'];
                $news[0]['Url'] = $ruser['url'];
                self::$_wx->news($news)->reply();
                break;
            //多图文
            case "3":
                $pagelist = M('Wx_keyword_img')->where(array('kid' => $ruser['id']))->order('sorts desc')->select();
                $news = array();
                foreach ($pagelist as $k => $v) {
                    $news[$k]['Title'] = $v['name'];
                    $news[$k]['Description'] = $v['summary'];
                    $img = $this->getPic($v['pic']);
                    $news[$k]['PicUrl'] = $img['imgurl'];
                    $news[$k]['Url'] = $v['url'];
                }
                self::$_wx->news($news)->reply();
                break;
            default:
                self::$_wx->text("未知类型的关键词，请联系客服！")->reply();
                break;
        }
    }

    /*未知关键词匹配 by App
     */
    public function toKeyUnknow()
    {
        self::$_wx->text("未找到此关键词匹配！")->reply();
    }

    /*具体微管网推送方式 by App
    $type=对应应用的类型
    $imglist=true/false 是否以多条返回/最多10条
     */
    public function toWgw($type, $imglist)
    {
        $wgw = F(self::$_uid . "/config/wgw_set"); //微官网设置缓存
        switch ($type) {
            case 'index':
                //准备各项参数
                $title = $wgw['title'] ? $wgw['title'] : '欢迎访问' . self::$_userinfo['wxname'];
                $summary = $wgw['summary'];
                $picid = $wgw['pic'];
                $picurl = $picid ? $this->getPic($picid) : false;
                //封装图文信息
                $news[0]['Title'] = $title;
                $news[0]['Description'] = $summary;
                $news[0]['PicUrl'] = $picurl['imgurl'] ? $picurl['imgurl'] : '#';
                $news[0]['Url'] = self::$_url . '/App/Wgw/Index/uid/' . self::$_uid;
                //推送图文信息
                self::$_wx->news($news)->reply();
                break;
        }
    }

    /*将图文信息封装为二维数组 by App
    $array(Title,Description,PicUrl,Url),$return=false
    Return:新闻数组/或直接推送
     */
    public function makeNews($array, $return = false)
    {
        if (!$array) {
            die('no items!');
        }
        $news[0]['Title'] = $array[0];
        $news[0]['Description'] = $array[1];
        $news[0]['PicUrl'] = $array[2];
        $news[0]['Url'] = $array[3];
        if ($return) {
            return $news;
        } else {
            self::$_wx->news($news)->reply();
        }
    }

    /*获取单张图片 by App
    return
     */
    public function getPic($id)
    {
        $m = M('Upload_img');
        $map['id'] = $id;
        $list = $m->where($map)->find();
        $list['imgurl'] = $list['savepath'] . '/' . $list['savename'];
        return $list ? $list : false;
    }
    //根据微信接口获取用户信息
    //return array/false 用户信息/未获取。
    public function apiClient($openid)
    {
        $user = self::$_wx->getUserInfo($openid);
        if ($user) {
            //TODO 获取需要的数据
            $vip['openid'] = $user['openid'];
            $vip['nickname'] = $user['nickname'];
            $vip['sex'] = $user['sex'];
            $vip['city'] = $user['city'];
            $vip['province'] = $user['province'];
            $vip['country'] = $user['country'];
            $vip['language'] = $user['language'];
            $vip['headimgurl'] = $user['headimgurl'];
            $vip['subscribe_time'] = $user['subscribe_time'];
            $vip['subscribe'] = $user['subscribe'];
            $vip['remark'] = $user['remark'];
        } else {
            $vip = FALSE;
        }
        return $vip;
    }

    /*认证服务号微信用户资料更新 by App
    return
     */
    public function updateUser($openid)
    {
        $old = self::$_ppvip->where(array('openid' => $openid))->find();
        if ($old) {
            if ((time() - $old['cctime']) > 86400) {
                $user = self::$_wx->getUserInfo($openid);
                //当成功拉去数据后
                if ($user) {
                    $user['cctime'] = time();
                    unset($user['groupid']);
                    $re = self::$_ppvip->where(array('id' => $old['id']))->save($user);
                } else {
                    $str = '更新用户资料失败，用户为：' . $openid;
                    file_put_contents('./Data/app_fail.txt', '微信接口失败:' . date('Y-m-d H:i:s') . PHP_EOL . '通知信息:' . $str . PHP_EOL . PHP_EOL . PHP_EOL, FILE_APPEND);
                }
            } else {
                //1天内，直接保存最后的交互时间
                $old['cctime'] = time();
                $re = self::$_ppvip->save($old);
            }
        }
        return ture;

    }

    ///////////////////增值方法//////////////////////////
    public function getlevel($exp)
    {
        $data = M('Vip_level')->order('exp')->select();
        if ($data) {
            $level = array();
            foreach ($data as $k => $v) {
                if ($k + 1 == count($data)) {
                    if ($exp >= $data[$k]['exp']) {
                        $level['levelid'] = $data[$k]['id'];
                        $level['levelname'] = $data[$k]['name'];
                    }
                } else {
                    if ($exp >= $data[$k]['exp'] && $exp < $data[$k + 1]['exp']) {
                        $level['levelid'] = $data[$k]['id'];
                        $level['levelname'] = $data[$k]['name'];
                    }
                }
            }
        } else {
            return false;
        }
        return $level;
    }

    public function getCardNoPwd()
    {
        $dict_no = "0123456789";
        $length_no = 10;
        $dict_pwd = "ABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789";
        $length_pwd = 10;
        $card['no'] = "";
        $card['pwd'] = "";
        for ($i = 0; $i < $length_no; $i++) {
            $card['no'] .= $dict_no[rand(0, (strlen($dict_no) - 1))];
        }
        for ($i = 0; $i < $length_pwd; $i++) {
            $card['pwd'] .= $dict_pwd[rand(0, (strlen($dict_pwd) - 1))];
        }
        return $card;
    }

    // 获取头像函数
    function getRemoteHeadImage($headimgurl)
    {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_POST, 0);
        curl_setopt($ch, CURLOPT_URL, $headimgurl);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_TIMEOUT, 2);
        $headimg = curl_exec($ch);
        curl_close($ch);
        return $headimg;
    }

    public function getQRCode($id, $openid)
    {
        $scene_id = $id % 100000;
        if ($scene_id == 0) {
            $scene_id = 1;
        }
        $ticket = self::$_wx->getQRCode($scene_id, 1);

        self::$_ppvip->where(array("id" => $id))->save(array("ticket" => $ticket["ticket"]));
        $qrUrl = self::$_wx->getQRUrl($ticket["ticket"]);

        $data = file_get_contents($qrUrl);
        file_put_contents('./QRcode/qrcode/' . $openid . '.png', $data);
    }

    // 创建二维码
    function createQrcode($id, $openid)
    {
        if ($id == 0 || $openid == '') {
            return false;
        }
        if (!file_exists('./QRcode/qrcode/' . $openid . '.png')) {
            //二维码进入公众号
            $this->getQRCode($id, $openid);
        }
        $qrcode = imagecreatefromstring(file_get_contents('./QRcode/qrcode/' . $openid . '.png'));
        return $qrcode;
    }

    // 创建二维码
    function createEmployeeQrcode($id, $openid)
    {
        if ($id == 0 || $openid == '') {
            return false;
        }
        if (!file_exists('./QRcode/qrcode/' . $id . "employee" . $openid . '.png')) {
            $url = 'http://' . $_SERVER['HTTP_HOST'] . __ROOT__ . '/App/Shop/index/employee/' . $id;
            \Util\QRcode::png($url, './QRcode/qrcode/' . $id . "employee" . $openid . '.png', 'L', 6, 2);
        }
        $qrcode = imagecreatefromstring(file_get_contents('./QRcode/qrcode/' . $id . "employee" . $openid . '.png'));
        return $qrcode;
    }

    // 创建背景
    function createQrcodeBg()
    {
        $autoset = M('Autoset')->find();
        if (!file_exists('./' . $autoset['qrcode_background'])) {
            $background = imagecreatefromstring(file_get_contents('./QRcode/background/default.jpg'));
        } else {
            $background = imagecreatefromstring(file_get_contents('./' . $autoset['qrcode_background']));
        }
        return $background;
    }

    // 创建背景
    function createQrcodeBgEmp()
    {
        $autoset = M('Autoset')->find();
        if (!file_exists('./' . $autoset['qrcode_emp_background'])) {
            $background = imagecreatefromstring(file_get_contents('./QRcode/background/default.jpg'));
        } else {
            $background = imagecreatefromstring(file_get_contents('./' . $autoset['qrcode_emp_background']));
        }
        return $background;
    }

    // 关注时返回信息
    function subscribeReturn($msg)
    {
        $temp = getcwd() . $this->getSubscribePic(self::$_set['wxpicture']);
        $switchs = file_exists($temp);
        if (self::$_set['wxswitch'] == '0' || !$switchs) {
            self::$_wx->text($msg)->reply();
        } else {
            $data = array('media' => '@' . $temp);
            $uploadresult = self::$_wx->uploadMedia($data, 'image');
            self::$_wx->image($uploadresult['media_id'])->reply();
        }
    }

    // 获取单张图片
    function getSubscribePic($id)
    {
        $m = M('UploadImg');
        $temparr = split(',', $id);
        foreach ($temparr as $v) {
            if ($v != '') {
                $map['id'] = $v;
                break;
            }
        }
        if ($map) {
            $list = $m->where($map)->find();
            if ($list) {
                $list['imgurl'] = "/Upload/" . $list['savepath'] . $list['savename'];
                $temp = str_replace('/', '/', $list['imgurl']);
            }
        }
        return $temp ? $temp : '';
    }

} //API类结束