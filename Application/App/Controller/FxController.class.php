<?php
//
namespace App\Controller;

class FxController extends BaseController
{
    public function _initialize()
    {
        //你可以在此覆盖父类方法
        parent::_initialize();
    }

    public function index()
    {
        if(self::$WAP['vip']['role'] == 0){ //普通会员不能进入
            $this->error('您没有权限');
        }

        $data = self::$WAP['vip'];
        $mvip = M('Vip');

        if($data['role'] == 1){ //总监的下级
            $data['sub'][1]['num'] = $mvip->where('role_1_id='.$data['id'].' and role=2')->count();     //via人数
            $data['sub'][1]['name'] = 'VIA';
            $data['sub'][1]['role'] = 2;
            $data['sub'][2]['num'] = $mvip->where('role_1_id='.$data['id'].' and role=3')->count();//推广人员人数
            $data['sub'][2]['name'] = '推广人员';
            $data['sub'][2]['role'] = 3;
            $data['sub'][3]['num'] = $mvip->where('role_1_id='.$data['id'].' and role=0')->count();  //普通会员人数
            $data['sub'][3]['name'] = '普通会员';
            $data['sub'][3]['role'] = 0;
            $data['total_count'] = $data['sub'][1]['num']+$data['sub'][2]['num']+$data['sub'][3]['num'];
        }else if($data['role'] == 2){   //via下级
            $data['sub'][1]['num'] = $mvip->where('role_2_id='.$data['id'].' and role=3')->count();//推广人员人数
            $data['sub'][1]['name'] = '推广人员';
            $data['sub'][1]['role'] = 3;
            $data['sub'][2]['num'] = $mvip->where('role_2_id='.$data['id'].' and role=0')->count();  //普通会员人数
            $data['sub'][2]['name'] = '普通会员';
            $data['sub'][2]['role'] = 0;
            $data['total_count'] = $data['sub'][1]['num']+$data['sub'][2]['num'];
        }else if($data['role'] == 3){   //推广人员下级
            $data['total_count'] = $data['sub'][1]['num'] = $mvip->where('role_3_id='.$data['id'].' and role=0')->count();  //普通会员人数
            $data['sub'][1]['name'] = '普通会员';
            $data['sub'][1]['role'] = 0;
        }

        $maptx['vipid'] = $data['id'];
        $maptx['status'] = 1;
        $txtotal = M('VipTx')->where($maptx)->sum('txprice');
        if ($txtotal > 0) {
            $data['txmoney'] = number_format($txtotal, 2);
        } else {
            $data['txmoney'] = number_format(0, 2);
        }
        //dump($txtotal);
        $this->assign('data', $data);
        $this->display();
    }


    public function paihang()
    {
        $m = M('Vip');
        $map['isfx'] = 1;
        $map['total_yj'] = array('gt', 0);
        $cache = $m->where($map)->limit(20)->order('total_yj desc')->select();
        $this->assign('cache', $cache);
        $this->display();
    }

    public function myqrcode()
    {
        $vip = self::$WAP['vip'];
        $img = __ROOT__ . "/QRcode/promotion/" . "employee" . $vip['openid'] . '.jpg';
        $this->assign('img', $img);
        $this->assign('vip', $vip);
        $this->display();
    }

    public function getqrcode()
    {
        $set = M('Set')->find();
        $url = $set['wxurl'] . '/App/Shop/index/ppid/' . self::$WAP['vipid'];
        $QR = new \Util\QRcode();
        $QR::png($url);
    }

    //小猫飞购：获取我的团队成员 2016年8月10日12:46:20
    public function myGroupItem(){
        $m = D('vip');
        $cache = $m->getSubMember(self::$WAP['vip']);
        $this->assign('cache', $cache);
        $this->display(T('Fx/myuserItem'));
    }

    public function myuserItem(){
        $page = intval(I("pg"));
        $page_count = intval(I("pc"));
        if ($page_count > 20) {
            $page_count = 20;
        }
        $type = I('get.type/d') ? I('get.type/d') : 0;  //2:取via 3:取推广人员 0:取普通会员
        $data = self::$WAP['vip'];
        /*if($data['role'] == 0 || !in_array($type,[0,2,3])){ //普通会员不能访问，一级只能查看部分等级
            return;
        }*/
        $m = M('vip');
        if($data['role'] == 1){
            $map['role_1_id'] = $data['id'];
            switch($type){
                case 2:
                    $map['role'] = 2;
                    break;
                case 3:
                    $map['role'] = 3;
                    break;
                case 0:
                    $map['role'] = 0;
            }
        }else if($data['role'] == 2){
            $map['role_2_id'] = $data['id'];
            switch($type){
                case 3:
                    $map['role'] = 3;
                    break;
                case 0:
                    $map['role'] = 0;
            }
        }else if($data['role'] == 3){
            $map['role_3_id'] = $data['id'];
            $map['role'] = 0;
        }else if($data['role'] == 0){
            $map['pid'] = $data['id'];
            $map['role'] = 0;
        }
        $cache = $m->where($map)
            ->order('ctime desc')
            ->limit($page_count * $page, $page_count)
            ->select();
        $this->assign('cache', $cache);
        $this->display();
    }

    //美林：获取我的 某级 团队成员
    public function myuserItem_old()
    {
        $page = intval(I("pg"));
        $page_count = intval(I("pc"));
        if ($page_count > 20) {
            $page_count = 20;
        }
        $m = M('vip');
        $type = intval(I('type')) ? intval(I('type')) : 1;
        $vipid = self::$WAP["vipid"];
        if ($type == 1) {
            $this->assign('type', self::$SHOP['set']['fx1name']);
            $cache = $m->where(array('pid' => $vipid))
                ->order('ctime desc')
                ->limit($page_count * $page, $page_count)
                ->select();
        }
        if ($type == 2) {
            $this->assign('type', self::$SHOP['set']['fx2name']);
            $arr = array();
            $tmp = $m->field('id')->where(array('pid' => $vipid))->order('ctime desc')->select();
            foreach ($tmp as $v) {
                array_push($arr, $v['id']);
            }
            $cache = $m->where(array('pid' => array('in', in_parse_str($arr))))
                ->order('ctime desc')
                ->limit($page_count * $page, $page_count)
                ->select();
        }
        if ($type == 3) {
            $this->assign('type', self::$SHOP['set']['fx3name']);
            $arr = array();
            $tmp = $m->field('id')->where(array('pid' => $vipid))->select();
            foreach ($tmp as $v) {
                array_push($arr, $v['id']);
            }
            $tmp2 = $m->field('id')->where(array('pid' => array('in', in_parse_str($arr))))->select();
            $arr2 = array();
            foreach ($tmp2 as $v) {
                array_push($arr2, $v['id']);
            }

            if (!$arr2) {
                $arr2 = '';
            }
            $cache = $m->where(array('pid' => array('in', in_parse_str($arr2))))
                ->order('ctime desc')
                ->limit($page_count * $page, $page_count)
                ->select();
        }
        $this->assign('cache', $cache);
        $this->display();
    }

    public function myuser()
    {
        $type = intval(I('type'));
        if(self::$WAP['vip']['role'] == 0){ //角色是普通会员
            $backurl = U('App/Vip/index');
        }else{
            $backurl = U('App/Fx/index');
        }
        $this->assign('type', $type);
        $this->assign('backurl', $backurl);
        $this->display();
    }

    public function dslog()
    {
        $m = M('fx_dslog');
        $map['to'] = self::$WAP['vipid'];
        $map['status'] = 1;
        $cache = $m->where($map)->limit(50)->order('ctime desc')->select();
        $this->assign('cache', $cache);
        $this->display();
    }

    public function fxlogItem()
    {
        $page = intval(I("pg"));
        $page_count = intval(I("pc"));
        if ($page_count > 20) {
            $page_count = 20;
        }
        //TODO 加载分页
        $m = M('fx_syslog');
        $map['to'] = self::$WAP['vipid'];
        $map['status'] = 1;
        $cache = $m->where($map)->order('ctime desc')->limit($page_count * $page, $page_count)->select();

        if ($cache) {
            $orderIdSC = [];
            $orderIdSM = [];
            foreach ($cache as $k => $v) {
                if ($v['order_type'] == OT_SM) {
                    $orderIdSM[] = $v['oid'];
                } else {
                    $orderIdSC[] = $v['oid'];
                }
            }
            if (!empty($orderIdSC)) {
                $rlt = M('Shop_order')->where(['id' => array('in', implode(',', $orderIdSC))])->select();
                if ($rlt) {
                    $map = [];
                    foreach ($rlt as $k => $v) {
                        $map[$v['id']] = $v;
                    }

                    foreach ($cache as $k => $v) {
                        if ($v['order_type'] == OT_SC) {
                            $cache[$k]['order_code'] = $map[$v['oid']]['oid'];
                        }
                    }
                }
            }
            $i = count($orderIdSC);
            if (!empty($orderIdSM)) {
                $rlt = M('Supplier_order')->where(['id' => array('in', implode(',', $orderIdSM))])->select();
                if ($rlt) {
                    $map = [];
                    foreach ($rlt as $k => $v) {
                        $map[$v['id']] = $v;
                    }

                    foreach ($cache as $k => $v) {
                        if ($v['order_type'] == OT_SM) {
                            $cache[$k]['order_code'] = $map[$v['oid']]['order_code'];
                        }
                    }
                }
            }
        }
        //addLogs(json_encode($cache));
        $this->assign('cache', $cache);
        $this->display();
    }

    public function tjlog()
    {
        $m = M('fx_log_tj');
        $map['vipid'] = self::$WAP['vipid'];
        $cache = $m->where($map)->limit(50)->order('ctime desc')->select();
        $this->assign('cache', $cache);
        $this->display();
    }

    /*public function about()
    {
        $ratio = self::$SHOP['set'];
        //所占销售额的真是比例
        $real_total_ratio = floatval($ratio['total_commission_rate']*$ratio['fx_rate']);
        //1-3级所占比例
        $rate[1] = round($real_total_ratio * floatval($ratio['fx1rate']) / 10000, 2);
        $rate[2] = round($real_total_ratio * floatval($ratio['fx2rate']) / 10000, 2);
        $rate[3] = round($real_total_ratio * floatval($ratio['fx3rate']) / 10000, 2);

        //举例数据
        $example['total'] = 10000; //若总数为10000元
        $example[1]     = round($example['total'] * $rate[1] /100,2);
        $example[2]     = round($example['total'] * $rate[2] /100,2);
        $example[3]     = round($example['total'] * $rate[3] /100,2);

        $this->assign('rate',$rate);
        $this->assign('name',self::$SHOP['set']['name']);
        $this->assign('example',$example);
        $this->display();
    }*/

    //via招募下线二维码界面
    public function promotion(){
        //只有via可以进入
        if(self::$WAP['vip']['role'] != 2){
            $this->diemsg(0,'只有via可以进入本页面');
        }
        $this->display();
    }

    //招募二维码生成
    public function promotionQrcode(){
        $expire = I('get.expire') ? I('get.expire') : 60;
        $expireTime = $expire * 60 + time();    //过期的时间
        $qrcode = new \Util\QRcode();
        $url = 'http://'.$_SERVER['HTTP_HOST'].'/App/Vip/applyTg/viaId/'.self::$WAP['vip']['id'].'/expireTime/'.$expireTime;
        $qrcode->png($url,false,0,15,1);
    }

    //via审核申请推广人员页面
    public function confirmTg(){
        if(IS_POST){
            $id = I('post.id/d');
            $status = I('post.status/d');
            if($id && $status){
                $info = M('apply_tg')->where('id='.$id)->find();
                if($info['status'] == 0){
                    switch($status){
                        case 1 :
                            $res = M('apply_tg')->where('id='.$id)->save(['status'=>1,'update_time'=>time()]);  //同意
                            M('vip')->where('id='.$info['apply_id'])->save(
                                [
                                    'role'=>3,
                                    'role_1_id'=>self::$WAP['vip']['role_1_id'],
                                    'role_2_id'=>self::$WAP['vip']['id'],
                                    'role_3_id'=>0
                                ]);    //身份变为推广人员,同时修改一二三级(推广人员无所谓学校)
                            break;
                        case 2:
                            $res = M('apply_tg')->where('id='.$id)->save(['status'=>2,'update_time'=>time()]);  //拒绝
                            break;
                    }
                    if($res){
                        //模板消息数据
                        $wechatTemplate = D('WechatTemplate');
                        $touser = M('vip')->where('id='.$info['apply_id'])->getField('openid');
                        $time = date('Y-m-d H:i:s',$info['create_time']);//申请时间
                        switch($status){
                            //发送模板消息
                            case 1 :
                                $remark = '您可以开始发展自己的团队啦，点击进入团队中心，查看团队详情';
                                $url = "http://" . $_SERVER['HTTP_HOST'] . U('App/Fx/index');
                                $wechatTemplate->sendMessage_applyResult(['to_user'=>$touser,'result'=>'申请通过' ,'time'=>$time,'remark'=>$remark,'url'=>$url]);
                                break;
                            case 2:
                                $wechatTemplate->sendMessage_applyResult(['to_user'=>$touser,'result'=>'申请被拒绝' ,'time'=>$time]);    //模板消息
                                break;
                        }
                        $this->ajaxReturn(['status'=>1,'msg'=>'已审核']);
                    }else{
                        $this->ajaxReturn(['status'=>0,'msg'=>'保存失败，请重试']);
                    }
                }else{
                    $this->ajaxReturn(['status'=>0,'msg'=>'请勿重复审核']);
                }
            }else{
                $this->ajaxReturn(['status'=>0,'msg'=>'参数错误']);
            }
        }else{
            $id = I('get.id/d');
            if($id){
                //$data = M('apply_tg')->where('id='.$id)->find();
                $data = M('apply_tg')->join('left join vip on apply_tg.apply_id = vip.id')
                    ->field('apply_tg.*,vip.headimgurl')
                    ->where(['apply_tg.id'=>$id])->find();

                if($data['status'] != 0){
                    $this->error('已审核，请勿重复处理');
                }
                $this->assign('data',$data);
                $this->display();
            }else{
                $this->ajaxReturn(['status'=>0,'msg'=>'参数错误']);
            }
        }
    }

    //via查看申请记录
    public function promotionLogItem(){
        if(self::$WAP['vip']['role'] != 2){
            $this->error('您没有权限查看记录');
        }
        $page = intval(I("pg"));
        $page_count = intval(I("pc"));
        if ($page_count > 20) {
            $page_count = 20;
        }
        $m = M('apply_tg');
        $map['apply_tg.via_id'] = self::$WAP['vip']['id'];
        $cache = $m->join('left join vip on apply_tg.apply_id = vip.id')
            ->field('apply_tg.*,vip.headimgurl')
            ->where($map)->order('create_time desc')
            ->limit($page_count * $page, $page_count)->select();
        $this->assign('cache', $cache);
        $this->display();
    }
}
