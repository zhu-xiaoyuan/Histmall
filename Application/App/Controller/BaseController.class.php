<?php
namespace App\Controller;

use Think\Controller;

class BaseController extends Controller
{
    public static $SET;//全局静态配置
    public static $WAP;//WAP全局静态变量
    public static $SHOP;// Shop全局静态变量
    //微信缓存
    protected static $_wxappid;
    protected static $_wxappsecret;
    //授权Session
    protected static $_sqmode;//对应$_SESSION['sqmode']-wecha,-yicha,-wap
    //其他初始化
    protected static $_trueurl;//真实的访问路径

    protected static $WECHAT = null;

    //初始化验证模块
    protected function _initialize()
    {
        //session(null);
        //die('session null');
        //dump($_SESSION);lo
        //die();
        //缓存全局SET
        self::$SET = $_SESSION['SET'] = $this->checkSet();
        self::$_wxappid = self::$SET['wxappid'];
        self::$_wxappsecret = self::$SET['wxappsecret'];

        //绑定高级鉴权返回地址和高级鉴权ppid
        $_SESSION['oappid'] = intval($_GET['ppid']) ? intval($_GET['ppid']) : 0;
        $_SESSION['oaemployee'] = intval($_GET['employee']) ? intval($_GET['employee']) : 0;
        $_SESSION['oaurl'] = 'http://' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'];

        if ($_SESSION['SET']['wxdebug']) {
            //开启调试模式oauth_debug
            $_SESSION['sqmode'] = $_SESSION['SET']['test_sqmode'];
            $_SESSION['sqopenid'] = $_SESSION['SET']['test_sqopenid'];
            $_SESSION['userId'] = $_SESSION['SET']['test_vip_id'];
        }

        //鉴权判定流程
        //判断是否进行base鉴权
        if (!$_SESSION['sqmode']) {

            //绑定高级鉴权返回地址和高级鉴权ppid
            //if(!$_GET['code']){
            //	$_SESSION['oappid']=intval($_GET['ppid'])?intval($_GET['ppid']):0;
            //	$_SESSION['oaurl']='http://'.$_SERVER['HTTP_HOST'].$_SERVER['REQUEST_URI'];
            //}
            //微信授权 TODO 有的手机没有HTTP_USER_AGENT
//            if (strpos($_SERVER["HTTP_USER_AGENT"], "MicroMessenger")) {

                if ($_GET['code']) {
                    //第二次鉴权
                    $_url = 'http://' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'];
                    if ($_GET['code'] != 'authdeny') {
                        //用户授权
                        $options['appid'] = self::$_wxappid;
                        $options['appsecret'] = self::$_wxappsecret;
                        $wx = new \Util\Wx\Wechat($options);
                        $re = $wx->getOauthAccessToken($_GET['code']);//获取access_token和openid
                        $access_token = $re['access_token'];
                        $openid = $re['openid'];
                        if ($re) {
                            $_SESSION['sqmode'] = 'wecha';
                            $_SESSION['sqopenid'] = $openid;
                        }
                        //正常处理完成，返回原链接
                        $rurl = $_SESSION['oaurl'];
                        header("Location:" . $rurl);
                    } else {
                        //用户未授权
                        $this->diemsg(0, '本应用需要您的授权才可以使用!');
                    }
                } else {
                    $_url = 'http://' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'];
                    $options['appid'] = self::$_wxappid;
                    $options['appsecret'] = self::$_wxappsecret;
                    $wx = new \Util\Wx\Wechat($options);
                    $squrl = $wx->getOauthRedirect($_url, '1', 'snsapi_base');
                    header("Location:" . $squrl);
                }

//            } else {
//                //其他浏览器不做授权跳出
//                $this->diemsg(0, '请使用微信浏览器访问本应用！');
//            }

        }

        //检查是否存在VIP
        if ($_SESSION['sqmode'] && $_SESSION['sqopenid']) {
            $openid = $_SESSION['sqopenid'];
            $vip = M('Vip')->where(array('openid' => $openid))->find();

            if (!$vip) {
                $this->redirect('App/Baseoa/index');
            }
            self::$WAP['vipid'] = $_SESSION['WAP']['vipid'] = $vip['id'];
            self::$WAP['vip'] = $_SESSION['WAP']['vip'] = $vip;
            //判断身份
            self::$WAP['vip']['roleName'] = $this->getRoleName(self::$WAP['vip']['role']);
            //会员等级
            $sub_num = D('vip')->getSubNum(self::$WAP['vip']['id']);        //下级人数
            $levels = M('vip_level')->find();
            self::$WAP['vip']['levelName'] = $sub_num > $levels['sub_num'] ? $levels['name'] : '';
            //学校名称
            if(self::$WAP['vip']['school_id'] == -1){   //-1：其他
                self::$WAP['vip']['school_name'] = $_SESSION['WAP']['vip']['school_name']  = self::$WAP['vip']['remark_school'];
            }else{
                self::$WAP['vip']['school_name'] = $_SESSION['WAP']['vip']['school_name']  = M('location_school')->where('id='.self::$WAP['vip']['school_id'])->getField('school_name');
            }

            //绑定底部链接
            if (strtolower(CONTROLLER_NAME) == 'shop') {
                //默认底部链接
                $footlasturl = base64_encode('http://' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI']);
                if (strtolower(ACTION_NAME) == 'index') {
                    $footlasturl = base64_encode(U('App/Shop/index'));
                    //临时处理不能分享的问题
                    $this->assign('allowshare', 1);
                }
                if (strtolower(ACTION_NAME) == 'goods') {
                    $footlasturl = base64_encode(U('App/Shop/goods', array('id' => $_GET['id'])));
                    //临时处理不能分享的问题
                    $this->assign('allowshare', 1);
                }
            } else {
                $footlasturl = base64_encode($_SESSION['oaurl']);
            }
            $this->assign('footlasturl', $footlasturl);
            //注销高级鉴权缓存
            unset($_SESSION['oappid']);
            unset($_SESSION['oaurl']);
        } else {
            session(null);
            $this->diemsg(0, '未正常获取会员数据，请尝试重新访问！');
        }

        //全局初始化完成
        //临时处理分享问题
        if (strtolower(CONTROLLER_NAME) == 'shop' && $_GET['ppid'] <> self::$WAP['vipid']) {
            if (strtolower(ACTION_NAME) == 'index') {
                $this->redirect('App/Shop/index', array('ppid' => self::$WAP['vipid'], 'type' => I('type')));
            }
            if (strtolower(ACTION_NAME) == 'goods') {
                $this->redirect('App/Shop/goods', array('id' => $_GET['id'], 'ppid' => self::$WAP['vipid']));
            }
        }
        //初始化全局会员配置
        self::$WAP['vipset'] = $_SESSION['WAP']['vipset'] = $this->checkVipSet();
        self::$SHOP['set'] = $_SESSION['SHOP']['set'] = $this->checkShopSet();
        self::$WAP['shopset'] = $_SESSION['WAP']['shopset'] = self::$SHOP['set'];   //此处较乱，需优化
        $this->assign('shopset', self::$WAP['shopset']);

        if (empty(self::$WECHAT)) {
            $set = self::$SET;
            $options['appid'] = $set['wxappid'];
            $options['appsecret'] = $set['wxappsecret'];
            $options['debug'] = true;
            $options['logcallback'] = 'wx_log_write';
            self::$WECHAT = new \Util\Wx\Wechat($options);

        }

        $token = self::$WECHAT->getJsSign("http://" . $_SERVER['HTTP_HOST'] . __SELF__);
        $this->assign('jssdktoken', $token);
    }

    //返回全局配置
    public function checkSet()
    {
        $set = M('Set')->find();
        return $set ? $set : utf8error('系统全局设置未定义！');
    }

    //返回VIP配置
    public function checkVipSet()
    {
        $set = M('Vip_set')->find();
        return $set ? $set : utf8error('会员设置未定义！');
    }

    // 返回商城设置
    public function checkShopSet()
    {
        $set = M('Shop_set')->find();
        $img = $this->getPic($set['pic']);
        $set["share_pic"] = $img["imgurl"];
        return $set ? $set : utf8error('商城设置未定义！');
    }


    //检查用户是否登陆,返回TRUE或跳转登陆
    public function checkLogin($backurl)
    {
        if (!isset($_SESSION['WAP']['vipid'])) {
            //$this->redirect(U('App/Vip/login',array('backurl'=>$backurl)));
            session(null);
            $this->diemsg('未正常获取会员数据，请尝试重新访问！');
        } else {
            //$map['id']=self::$WAP['vipid']=$_SESSION['WAP']['vipid'];
            //self::$WAP['vip']=$_SESSION['WAP']['vip']=M('vip')->where($map)->find();
            //$levelname = M('Vip_level')->where('id=' . self::$WAP['vip']['levelid'])->getField('name');
            //self::$WAP['vip']['levelname'] = $_SESSION['WAP']['vip']['levelname'] = $levelname;
            $this->checkMonthexp();
            return TRUE;
        }
    }

    //检查上月是否已保存每月经验
    public function checkMonthexp()
    {
        $vipid = self::$WAP['vipid'];
        $m = M('vip_monthexp');
        $last_month = getdate(strtotime("-1 month"));
        $map['month'] = $last_month['year'] . "-" . $last_month['mon'];
        $map['vipid'] = $vipid;
        $re = $m->where($map)->find();
        if (!$re) {
            //所有保存过的经验总计
            $exp_record = $m->where('vipid=' . $vipid)->sum('exp');
            //需要保存的经验=总经验-保存过的经验总计
            $data['exp'] = self::$WAP['vip']['exp'] - $exp_record;
            $data['month'] = $map['month'];
            $data['ctime'] = time();
            $data['vipid'] = $vipid;
            $m->add($data);

            //判断用户当前经验设定用户等级
            if (self::$WAP['vipset']['level_period'] > 0) {
                $month_arr = "";
                for ($i = 1; $i <= self::$WAP['vipset']['level_period']; $i++) {
                    $temp = getdate(strtotime("-" . $i . " month"));
                    $month_arr .= $temp['year'] . "-" . $temp['mon'] . ",";
                }
                $map['month'] = array('in', in_parse_str($month_arr));
                $cur_exp = $m->where($map)->sum('exp');
            } else {
                $cur_exp = self::$WAP['vip']['exp'];
            }
            $data_vip['cur_exp'] = $cur_exp;
            //$level = $this->getlevel($cur_exp);
            //$data_vip['levelid'] = $level['levelid'];
            $r = M('vip')->where('id=' . $vipid)->save($data_vip);
            if ($r) {
                self::$WAP['vip']['cur_exp'] = $_SESSION['WAP']['vip']['cur_exp'] = $data_vip['cur_exp'];
                //self::$WAP['vip']['levelid'] = $_SESSION['WAP']['vip']['levelid'] = $level['levelid'];
                //self::$WAP['vip']['levelname'] = $_SESSION['WAP']['vip']['levelname'] = $level['levelname'];
            }
        }
    }

//	public function getLevel($exp) {
//		$level_rule=explode(",",self::$WAP['vipset']['level_rule']);
//		$level;
//		foreach ($level_rule as $k=>$v) {
//			$level_rule[$k]=explode(":",$v);
//		}
//		foreach ($level_rule as $k=>$v) {
//			if ($k+1==count($level_rule)) {
//				if ($exp>=$level_rule[$k][1]) {
//					$level['level']=$k;
//					$level['levelname']=$level_rule[$k][0];
//				}
//			} else {
//				if ($exp>=$level_rule[$k][1] && $exp<$level_rule[$k+1][1]) {
//					$level['level']=$k;
//					$level['levelname']=$level_rule[$k][0];
//				}
//			}
//		}
//		return $level;
//	}

    public function getlevel($exp)
    {
        $data = M('vip_level')->order('exp')->select();
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
            return utf8error('会员等级未定义！');
        }
        return $level;
    }

    //停止不动的信息通知页面处理
    public function diemsg($status, $msg)
    {
        //成功为1，失败为0
        $status = $status ? $status : '0';
        $this->assign('status', $status);
        $this->assign('msg', $msg);

        $tpl = $this->fetch('./Tpl/App/Base_diemsg.html');
        $this->show($tpl);
        die();
    }

    //获取单张图片
    public function getPic($id)
    {
        $id = explode(",", trim($id, ","));
        $m = M('UploadImg');
        $map['id'] = $id[0];
        $list = $m->where($map)->find();
        if ($list) {
            $data['imgurl'] = $list['savepath'] . "/" . $list['savename'];
        } else {
            $data['imgurl'] = "";
        }
        return $data;
    }

    //获取图集合
    public function getAlbum($ids)
    {
        if (empty($ids)) {
            return "";
        }
        $m = M('UploadImg');
        $map['id'] = array('in', in_parse_str(trim($ids, ",")));
        $list = $m->where($map)->select();
        foreach ($list as $k => $v) {
            $list[$k]['imgurl'] = $list[$k]['savepath'] . "/" . $list[$k]['savename'];
        }
        return $list ? $list : "";
    }

    //推广人员、或普通会员 名称
    public function getRoleName($role){
        $roleName =  [
            1   => '<span class="home-label role_1">营销总监</span>',
            2   => '<span class="home-label role_2">VIA</span>',
            3   => '<span class="home-label role_3">推广人员</span>',
        ];
        if($role == 0){
            return self::$WAP['vip']['is_vip'] ? '<span class="home-label role_4">VIP</span>':'<span class="home-label role_5">普通会员</span>';
        }else{
            return $roleName[$role];
        }
    }
}