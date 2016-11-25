<?php
// 本类由系统自动生成，仅供测试用途
namespace App\Controller;

class ShopController extends BaseController
{

    public function _initialize()
    {
        //你可以在此覆盖父类方法	
        parent::_initialize();
    }
    /**
     * 返回购物车中商品的数量
     */
    public function getBasket()
    {
        $data = M("shop_basket")->where(array("vipid" => self::$WAP["vipid"]))->sum("num");
        responseToJson(0, $data);
    }

    /**
     * 获取搜索数据
     */
    public function goodsItem()
    {
        $page = intval(I("page"));
        $num = intval(I("num"));
        $cid = intval(I("cid"));
        $kw = I("kw");
        if (!empty($kw)) {
            $map["name"] = array("like", "%" . $kw . "%");
        }
        if ($cid > 0) {
            $cate = M("Shop_cate")->where(array("id" => $cid))->find();
            $map["cid"] = array("in", $cate["soncate"] . $cate["id"]);
        }
        $map["status"] = 1;
        $m = M("Shop_goods");
        $data = $m->where($map)->order("sorts desc")->limit($page * $num, $num)->select();
        if ($data == null) {
            $this->ajaxReturn("");
        }
        foreach ($data as $k => $v) {
            $listpic = $this->getPic($v['listpic']);
            $data[$k]['imgurl'] = $listpic['imgurl'];
        }
        $this->assign('data', $data);
        $this->display();
    }
    /**
     * 获取团购商品
     */
    public function groupItem()
    {
        $page = intval(I("page"));
        $num = intval(I("num"));
        $map["status"] = 1;
        $map["is_group_buy"] = 1;
        $map['group_time_start'] = array('lt',time());
        $map['_string'] = '(group_time_end = "0") OR (group_time_end > '.time().') ';
        $m = M("Shop_goods");
        $data = $m->where($map)->order("sorts desc")->limit($page * $num, $num)->select();
        if ($data == null) {
            $this->ajaxReturn("");
        }
        foreach ($data as $k => $v) {
            $listpic = $this->getPic($v['listpic']);
            $data[$k]['imgurl'] = $listpic['imgurl'];
        }
        $this->assign('data', $data);
        $this->display();
    }

    /**
     * 搜索结果页面
     */
    public function goodsList()
    {
        $kw = I("kw");
        $cid = intval(I("cid"));
        $this->assign('kw', $kw);
        $this->assign('cid', $cid);
        $cate = M("Shop_cate")->where(array("id" => $cid))->find();
        $this->assign('cate', $cate);
        $this->display();
    }

    /*
     *团购商品列表
     */
    public function groupList()
    {
        $map["status"] = 1;
        $map["is_group_buy"] = 1;
        $map['group_time_start'] = array('lt',time());
        $map['_string'] = '(group_time_end = "0") OR (group_time_end > '.time().') ';
        $sum = M("Shop_goods")->where($map)->count();
        $this->assign('sum',$sum);
        $this->display();
    }

    /**
     * 商品分类页面
     * */
    public function search()
    {
        $cid = intval(I("cid"));
        $map["pid"] = $cid;
        $map["is_enable"] = 1;
        $m = M("Shop_cate");
        $data = $m->where($map)->select();
        $this->assign('cate', $data);
        $this->display();
    }

    /**
     * 上面代码为新增，黄金分割线，========================================================================================================
     * */

    private function createNonceStr($length = 16)
    {
        $chars = "abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789";
        $str = "";
        for ($i = 0; $i < $length; $i++) {
            $str .= substr($chars, mt_rand(0, strlen($chars) - 1), 1);
        }
        return $str;
    }

    public function index()
    {
        $m = M('Shop_goods');
        //$tmpgroup=M('Shop_group')->select();
        //商城推荐
        $tmpgroup = M('Shop_group')->where(array('status' => 1))->order('sorts desc')->select();
        $this->assign('group', $tmpgroup);
        // $group=array();
        // 	$group[$v['id']]=$v['goods'];
        // }
        //重磅推荐
        foreach($tmpgroup as $k=>$v) {
            $goods = $m->where(array('id' => array('in', $v['goods'])))->order('sorts desc')->select();
            if ($goods) {
                foreach($goods as $kk=>$vv){
                    if ($this->can_group_buy($vv)['status'] == 1) {      //是否开团【开团】
                        $goods[$kk]['can_group_buy'] = true;            //设置为开团
                        if ($v['issku'] == 1) {                         //是否开启sku【开启】
                            $sku_group_money = M('shop_goods_sku')->where('status=1 and goodsid=' . $v['id'])->order('group_buy_price asc')->find();
                            $goods[$kk]['price'] = $sku_group_money['group_buy_price'];     //sku最低团购价设置为当前显示价格
                            $goods[$kk]['oprice'] = $goods[$kk]['group_buy_money'];         //物品原团购价格设为商品原价显示
                        } else {//未开启sku
                            $goods[$kk]['oprice'] = $goods[$kk]['price'];                   //物品当前价格设置为商品原价显示
                            $goods[$kk]['price'] = $goods[$kk]['group_buy_money'];          //团购价设置为当前显示价格
                        }
                    }else{
                        $goods[$kk]['can_group_buy'] = false;
                    }
                    //图片
                    $listpic = $this->getPic(trim($vv['indexpic'], ","));
                    $goods[$kk]['imgurl'] = $listpic['imgurl'];
                }
            }
            $mrtj[$k]['goods'] = $goods;
            $mrtj[$k]['info'] = $v;
        }
        $this->assign('mrtj', $mrtj);

        //分组调用
        $mapx['id'] = array('in', in_parse_str(self::$WAP['shopset']['indexgroup']));
        $indexicons = M('Shop_cate')->where($mapx)->select();
        foreach ($indexicons as $k => $v) {
            $listpic = $this->getPic(trim($v['icon'], ","));
            $indexicons[$k]['iconurl'] = $listpic['imgurl'];
            //$indexicons[$k]['ison'] = $type == $v['id'] ? '1' : '0';
            // 获取下级
            if ($indexicons[$k]['soncate']) {
                $son = M('Shop_cate')->where(array('id' => array('in', in_parse_str($indexicons[$k]['soncate']))))->select();
                foreach ($son as $kk => $vv) {
                    $temp = $this->getPic(trim($vv['icon'], ","));
                    $son[$kk]['iconurl'] = $temp['imgurl'];
                    //$son[$kk]['ison'] = $type == $vv['id'] ? '1' : '0';
                    $son[$kk]['url'] = U('App/Shop/index#nav', array('type' => $v['id']));
                }
                $indexicons[$k]['son'] = 1;
                $indexicons[$k]['sonlist'] = $son;
                $indexicons[$k]['url'] = "javascript:;";
            } else {
                $indexicons[$k]['son'] = 0;
                $indexicons[$k]['url'] = U('App/Shop/index#nav', array('type' => $v['id']));
            }
            $map["cid"] = array("in", $v["soncate"] . $v["id"]);
            $map['status'] = 1;
            $cache = $m->where($map)->order('sorts desc')->limit(4)->select();
            foreach ($cache as $k_k => $v_v) {
                $listpic = $this->getPic(trim($v_v['listpic'], ","));
                $cache[$k_k]['imgurl'] = $listpic['imgurl'];
            }
            $indexicons[$k]["cache"] = $cache;
        }
        $this->assign('indexicons', $indexicons);

        //首页轮播图集\
        $cc["id"] = array('in', in_parse_str(self::$WAP['shopset']['indexalbum']));
        $indexalbum = M('Shop_ads')->where($cc)->select();
        foreach ($indexalbum as $k => $v) {
            $listpic = $this->getPic(trim($v['pic'], ","));
            $indexalbum[$k]['imgurl'] = $listpic['imgurl'];
        }
        $this->assign('indexalbum', $indexalbum);
        $this->assign('showsub', self::$WAP['vip']['subscribe'] == 1 ? 1 : 0);

        //是否选择学校
        //$this->assign('selectSchool',self::$WAP['vip']['school_id']==0 ? 1 : 0);

        //首页分类
        $classify_list = M('shop_cate as c')
            ->field('c.id,c.name,i.name as img_name,i.savepath')
            ->join('left join upload_img as i on c.icon=i.id')
            ->where('c.is_enable=1 and c.lv=1')
            ->order('c.sorts desc')
            ->limit(10)
            ->select();
        $this->assign('classify_list', $classify_list);

//        //首页团购推荐
//        $group['status'] = 1;   //正常
//        $group['is_group_buy'] = 1; //参与团购的商品
//        $group['group_time_start'] = array('lt',time());
//        $group['_string'] = 'group_time_end = 0 OR group_time_end >'.time();
//
//        $group_buy_goods = M('shop_goods')->where($group)->order('sorts desc')->limit(5)->select();
//        foreach($group_buy_goods as $k=>$v){
//            $img = $this->getPic($v['pic']);
//            $group_buy_goods[$k]['imgurl'] = $img['imgurl'];
//        }
//        //dump($group_buy_goods);
//        $this->assign('group_buy_goods',$group_buy_goods);

        //首页分享特效
        //dump(self::$WAP['vip']['ppid']);
        if (!self::$WAP['vip']['subscribe']) {
//            if (self::$WAP['vip']['pid']) {
//                $father = M('Vip')->where('id=' . self::$WAP['vip']['pid'])->find();
//                $this->assign('showsub', 1);
//                if ($father) {
//                    $this->assign('showfather', 1);
//                    $this->assign('father', $father);
//                } else {
//                    $this->assign('showfather', 0);
//                }
//
//            } else {
            //$this->assign('showsub', self::$WAP['vip']['subscribe']);
//                $this->assign('showfather', 0);
//            }
        } else {
            //$this->assign('showsub', 0);
        }

        $this->display();
    }

    public function goods()
    {
        $id = I('id') ? I('id') : $this->diemsg(0, '缺少商品信息参数!');

        $group_buy_id = intval(I('get.group_buy_id'));
        $this->assign('group_buy_id',$group_buy_id);  //为真则说明页面是从团购详情页跳转过来的，带着团购详情

        $m = M('Shop_goods');
        //商品信息
        $cache = $m->where('id=' . $id)->find();
        //判断是否可以团购
        $can_group_buy = $this->can_group_buy($cache);
        $is_group_buy = 0;  //默认：不可以团购
        if($can_group_buy['status']==1){
            $is_group_buy = 1;
//            $min_group_buy_price = M('Shop_goods_sku')->field('group_buy_price')->where(array('goodsid' => $id, 'status' => 1))->order('group_buy_price asc')->find();
//            $cache['group_buy_money'] = $min_group_buy_price['group_buy_price'];//设置显示最低团购价
        }
        $this->assign('is_group_buy',$is_group_buy);

        $cache['group_buy_num_invite'] = $cache['group_buy_num'] - 1;   //团购时需要推荐的人数

        if (!$cache) {
            $this->error('此商品已下架！', U('App/Shop/index'));
        }
        if (!$cache['status']) {
            $this->error('此商品已下架！', U('App/Shop/index'));
        }
        //自动计数
        $rclick = $m->where('id=' . $id)->setInc('clicks', 1);
        //读取标签
        foreach (explode(',', $cache['lid']) as $k => $v) {
            $label[$k] = M('ShopLabel')->where(array('id' => $v))->getField('name');
        }
        $abc = $this->getPic($cache["pic"]);
        $cache["pic"] = $abc["imgurl"];
        $cache['label'] = $label;
        $this->assign('cache', $cache);
        if ($cache['issku']) {
            if ($cache['skuinfo']) {
                $skuinfo = unserialize($cache['skuinfo']);
                $skm = M('Shop_skuattr_item');
                foreach ($skuinfo as $k => $v) {
                    $checked = explode(',', $v['checked']);
                    $attr = $skm->field('path,name')->where('pid=' . $v['attrid'])->select();
                    foreach ($attr as $kk => $vv) {
                        $attr[$kk]['checked'] = in_array($vv['path'], $checked) ? 1 : '';
                    }
                    $skuinfo[$k]['allitems'] = $attr;
                }
                $this->assign('skuinfo', $skuinfo);
            } else {
                $this->diemsg(0, '此商品还没有设置SKU属性！');
            }
            $skuitems = M('Shop_goods_sku')->field('sku,skuattr,price,group_buy_price,num,hdprice,hdnum')->where(array('goodsid' => $id, 'status' => 1))->select();
            if (!$skuitems) {
                $this->diemsg(0, '此商品还未生成SKU!');
            }
            $skujson = array();
            foreach ($skuitems as $k => $v) {
                $skujson[$v['sku']]['sku'] = $v['sku'];
                $skujson[$v['sku']]['skuattr'] = $v['skuattr'];
                $skujson[$v['sku']]['price'] = $is_group_buy?$v['group_buy_price']:$v['price'];
                $skujson[$v['sku']]['num'] = $v['num'];
                $skujson[$v['sku']]['hdprice'] = $v['hdprice'];
                $skujson[$v['sku']]['hdnum'] = $v['hdnum'];
            }
            $this->assign('skujson', json_encode($skujson));
        }

        //绑定图集
        if ($cache['album']) {
            $appalbum = $this->getAlbum($cache['album']);
            if ($appalbum) {
                $this->assign('appalbum', $appalbum);
            }
        }
        //绑定购物车数量
        $basketnum = M('Shop_basket')->where(array('sid' => 0, 'vipid' => self::$WAP['vipid']))->sum('num');
        $this->assign('basketnum', $basketnum);

        //若支持团购，则获取团购数据
        if($is_group_buy){
            $group_buy_lists = M('group_buy')->field('group_buy.*,vip.nickname,vip.headimgurl,vip.city,rand()')
                ->join('left join vip on group_buy.creator_id = vip.id')
                ->where('group_buy.status=0 and group_buy.goods_id='.$id)
                ->order('rand()')
                ->limit(3)
                ->select();
            foreach($group_buy_lists as $k=>$v){
                $group_buy_lists[$k]['need_num'] = $cache['group_buy_num'] - $v['people_num'];  //组团还需要的人数
            }
            $this->assign('group_buy_lists',$group_buy_lists);
        }

        //绑定登陆跳转地址
        $backurl = base64_encode(U('App/Shop/goods', array('id' => $id)));
        $loginback = U('App/Vip/login', array('backurl' => $backurl));
        $this->assign('loginback', $loginback);
        $this->assign('lasturl', $backurl);

        //商品分享文案
        $share['title'] = '小猫飞购，快来抢购！';
        $share['desc']  = $cache['name'];
        $share['img']   = $cache['pic'];
        $this->assign('share',$share);

        $this->display();
    }

    public function basket()
    {
        $sid = I('sid') <> '' ? I('sid') : $this->diemsg(0, '缺少SID参数');//sid可以为0
        $lasturl = I('lasturl') ? I('lasturl') : $this->diemsg(0, '缺少LastURL参数');
        $basketlasturl = base64_decode($lasturl);
        $basketurl = U('App/Shop/basket', array('sid' => $sid, 'lasturl' => $lasturl));
        $backurl = base64_encode($basketurl);
        $basketloginurl = U('App/Vip/login', array('backurl' => $backurl));
        $re = $this->checkLogin($backurl);
        //保存当前购物车地址
        $this->assign('basketurl', $basketurl);
        //保存登陆购物车地址
        $this->assign('basketloginurl', $basketloginurl);
        //保存购物车前地址
        $this->assign('basketlasturl', $basketlasturl);
        //保存购物车加密地址，用于OrderMaker正常返回
        $this->assign('lasturlencode', $lasturl);
        //已登陆
        $m = M('Shop_basket');
        $mgoods = M('Shop_goods');
        $msku = M('Shop_goods_sku');
        $returnurl = base64_decode($lasturl);
        $this->assign('returnurl', $returnurl);
        $cache = $m->where(array('sid' => $sid, 'vipid' => $_SESSION['WAP']['vipid']))->select();
        //错误标记
        $errflag = 0;
        //等待删除ID
        $todelids = '';
        //totalprice
        $totalprice = 0;
        //totalnum
        $totalnum = 0;
        foreach ($cache as $k => $v) {
            //sku模型
            $goods = $mgoods->where('id=' . $v['goodsid'])->find();
            $pic = $this->getPic($goods['pic']);
            if ($v['sku']) {
                //取商品数据				
                if ($goods['issku'] && $goods['status']) {
                    $map['sku'] = $v['sku'];
                    $sku = $msku->where($map)->find();
                    if ($sku['status']) {
                        if ($sku['num']) {
                            //调整购买量
                            $cache[$k]['name'] = $goods['name'];
                            $cache[$k]['skuattr'] = $sku['skuattr'];
                            $cache[$k]['num'] = $v['num'] > $sku['num'] ? $sku['num'] : $v['num'];
                            $cache[$k]['price'] = $sku['price'];
                            $cache[$k]['total'] = $sku['num'];
                            $cache[$k]['pic'] = $pic['imgurl'];
                            $totalnum = $totalnum + $cache[$k]['num'];
                            $totalprice = $totalprice + $cache[$k]['price'] * $cache[$k]['num'];
                        } else {
                            //无库存删除
                            $todelids = $todelids . $v['id'] . ',';
                            unset($cache[$k]);

                        }
                    } else {
                        //下架删除
                        $todelids = $todelids . $v['id'] . ',';
                        unset($cache[$k]);
                    }
                } else {
                    //下架删除
                    $todelids = $todelids . $v['id'] . ',';
                    unset($cache[$k]);
                }

            } else {
                if ($goods['status']) {
                    if ($goods['num']) {
                        //调整购买量
                        $cache[$k]['name'] = $goods['name'];
                        $cache[$k]['skuattr'] = $sku['skuattr'];
                        $cache[$k]['num'] = $v['num'] > $goods['num'] ? $goods['num'] : $v['num'];
                        $cache[$k]['price'] = $goods['price'];
                        $cache[$k]['total'] = $goods['num'];
                        $cache[$k]['pic'] = $pic['imgurl'];
                        $totalnum = $totalnum + $cache[$k]['num'];
                        $totalprice = $totalprice + $cache[$k]['price'] * $cache[$k]['num'];
                    } else {
                        //无库存删除
                        $todelids = $todelids . $v['id'] . ',';
                        unset($cache[$k]);
                    }
                } else {
                    //下架删除
                    $todelids = $todelids . $v['id'] . ',';
                    unset($cache[$k]);
                }
            }
        }
        if ($todelids) {
            $rdel = $m->delete($todelids);
            if (!$rdel) {
                $this->error('购物车获取失败，请重新尝试！');
            }
        }


        $this->assign('cache', $cache);
        $this->assign('totalprice', $totalprice);
        $this->assign('totalnum', $totalnum);
        $this->display();
    }

    public function getTotal(){
        $sid = I('sid') <> '' ? I('sid') : $this->diemsg(0, '缺少SID参数');//sid可以为0
        $m = M('Shop_basket');
        $mgoods = M('Shop_goods');
        $msku = M('Shop_goods_sku');
        $cache = $m->where(array('sid' => $sid, 'vipid' => $_SESSION['WAP']['vipid']))->select();

        $totalprice = 0;
        //totalnum
        $totalnum = 0;
        foreach ($cache as $k => $v) {
            //sku模型
            $goods = $mgoods->where('id=' . $v['goodsid'])->find();
            $pic = $this->getPic($goods['pic']);
            if ($v['sku']) {
                //取商品数据
                if ($goods['issku'] && $goods['status']) {
                    $map['sku'] = $v['sku'];
                    $sku = $msku->where($map)->find();
                    if ($sku['status']) {
                        if ($sku['num']) {
                            //调整购买量
                            $cache[$k]['name'] = $goods['name'];
                            $cache[$k]['skuattr'] = $sku['skuattr'];
                            $cache[$k]['num'] = $v['num'] > $sku['num'] ? $sku['num'] : $v['num'];
                            $cache[$k]['price'] = $sku['price'];
                            $cache[$k]['total'] = $sku['num'];
                            $cache[$k]['pic'] = $pic['imgurl'];
                            $totalnum = $totalnum + $cache[$k]['num'];
                            $totalprice = $totalprice + $cache[$k]['price'] * $cache[$k]['num'];
                        }
                    }
                }
            } else {
                if ($goods['status']) {
                    if ($goods['num']) {
                        //调整购买量
                        $cache[$k]['name'] = $goods['name'];
                        $cache[$k]['skuattr'] = $sku['skuattr'];
                        $cache[$k]['num'] = $v['num'] > $goods['num'] ? $goods['num'] : $v['num'];
                        $cache[$k]['price'] = $goods['price'];
                        $cache[$k]['total'] = $goods['num'];
                        $cache[$k]['pic'] = $pic['imgurl'];
                        $totalnum = $totalnum + $cache[$k]['num'];
                        $totalprice = $totalprice + $cache[$k]['price'] * $cache[$k]['num'];
                    }
                }
            }
        }

        $info['status'] = 1;
        $info['totalprice'] = $totalprice;
        $info['totalnum'] = $totalnum;
        $this->ajaxReturn($info);

    }

    public function save_basket_num(){
        $id = I('post.id');
        $num = I('post.num');
        $save_res = M('Shop_basket')->where('id='.$id)->save(['num'=>$num]);
        if(empty($save_res)){
            $save_res = false;
        }else{
            $save_res = true;
        }
        $this->ajaxReturn($save_res);
    }


    //添加购物车
    public function addtobasket()
    {
        if (IS_AJAX) {
            $m = M('Shop_basket');
            $data = I('post.');
            if (!$data) {
                $info['status'] = 0;
                $info['msg'] = '未获取数据，请重新尝试';
                $this->ajaxReturn($info);
            }

            //区分SKU模式
            if ($data['sku']) {
                $basket_num = $m->where(array("goodsid" => $data["goodsid"], "vipid" => $data['vipid'], "sku" => $data["sku"]))->sum("num");
                $goods_num = M("shop_goods_sku")->where(array("goodsid" => $data["goodsid"], "sku" => $data["sku"]))->getField("num");
                if (($basket_num + $data["num"]) > $goods_num) {
                    $info['total'] = $m->where(array('sid' => $data['sid'], 'vipid' => $data['vipid']))->sum('num');
                    $info['status'] = 1;
                    $info['msg'] = '商品库存不足！';
                    $this->ajaxReturn($info);
                }
                $old = $m->where(array('sid' => $data['sid'], 'vipid' => $data['vipid'], 'sku' => $data['sku']))->find();
                if ($old) {
                    $old['num'] = $data['num'] + $old['num'];
                    $rold = $m->save($old);
                    if ($rold === FALSE) {
                        $info['status'] = 0;
                        $info['msg'] = '添加购物车失败，请重新尝试！';
                    } else {
                        $total = $m->where(array('sid' => $data['sid'], 'vipid' => $data['vipid']))->sum('num');
                        $info['total'] = $total;
                        $info['status'] = 1;
                        $info['msg'] = '添加购物车成功！';
                    }
                } else {
                    $rold = $m->add($data);
                    if ($rold) {
                        $total = $m->where(array('sid' => $data['sid'], 'vipid' => $data['vipid']))->sum('num');
                        $info['total'] = $total;
                        $info['status'] = 1;
                        $info['msg'] = '添加购物车成功！';
                    } else {
                        $info['status'] = 0;
                        $info['msg'] = '添加购物车失败，请重新尝试！';
                    }
                }
            } else {
                $basket_num = $m->where(array("goodsid" => $data["goodsid"], "vipid" => $data['vipid'], "sku" => ""))->sum("num");
                $goods_num = M("shop_goods")->where(array("id" => $data["goodsid"]))->getField("num");
                if (($basket_num + $data["num"]) > $goods_num) {
                    $info['total'] = $m->where(array('sid' => $data['sid'], 'vipid' => $data['vipid']))->sum('num');
                    $info['status'] = 1;
                    $info['msg'] = '商品库存不足！';
                    $this->ajaxReturn($info);
                }
                $old = $m->where(array('sid' => $data['sid'], 'vipid' => $data['vipid'], 'goodsid' => $data['goodsid']))->find();
                if ($old) {
                    $old['num'] = $data['num'] + $old['num'];
                    $rold = $m->save($old);
                    if ($rold === FALSE) {
                        $info['status'] = 0;
                        $info['msg'] = '添加购物车失败，请重新尝试！';
                    } else {
                        $total = $m->where(array('sid' => $data['sid'], 'vipid' => $data['vipid']))->sum('num');
                        $info['total'] = $total;
                        $info['status'] = 1;
                        $info['msg'] = '添加购物车成功！';
                    }
                } else {
                    $rold = $m->add($data);
                    if ($rold) {
                        $total = $m->where(array('sid' => $data['sid'], 'vipid' => $data['vipid']))->sum('num');
                        $info['total'] = $total;
                        $info['status'] = 1;
                        $info['msg'] = '添加购物车成功！';
                    } else {
                        $info['status'] = 0;
                        $info['msg'] = '添加购物车失败，请重新尝试！';
                    }
                }
            }
            $this->ajaxReturn($info);
        } else {
            $this->diemsg(0, '禁止外部访问！');
        }
    }

    //删除购物车
    public function delbasket()
    {
        if (IS_AJAX) {
            $id = I('id');
            if (!$id) {
                $info['status'] = 0;
                $info['msg'] = '未获取ID参数,请重新尝试！';
                $this->ajaxReturn($info);
            }
            $m = M('Shop_basket');
            $re = $m->where('id=' . $id)->delete();
            if ($re) {
                $info['status'] = 1;
                $info['msg'] = '删除成功，更新购物车状态...';

            } else {
                $info['status'] = 0;
                $info['msg'] = '删除失败，自动重新加载购物车...';
            }
            $this->ajaxReturn($info);
        } else {
            $this->diemsg(0, '禁止外部访问！');
        }
    }

    //清空购物车
    public function clearbasket()
    {
        if (IS_AJAX) {
            $sid = $_GET['sid'];
            //前端必须保证登陆状态
            $vipid = $_SESSION['WAP']['vipid'];
            if (!$vipid) {
                $info['status'] = 3;
                $info['msg'] = '登陆已超时，2秒后自动跳转登陆页面！';
                $this->ajaxReturn($info);
            }
            if ($sid == '') {
                $info['status'] = 0;
                $info['msg'] = '未获取SID参数,请重新尝试！';
                $this->ajaxReturn($info);
            }
            $m = M('Shop_basket');
            $re = $m->where(array('sid' => $sid, 'vipid' => $vipid))->delete();
            if ($re) {
                $info['status'] = 2;
                $info['msg'] = '购物车已清空';
                $this->ajaxReturn($info);
            } else {
                $info['status'] = 0;
                $info['msg'] = '购物车清空失败，请重新尝试！';
                $this->ajaxReturn($info);
            }
        } else {
            $this->diemsg(0, '禁止外部访问！');
        }
    }

    //购物车库存检测
    public function checkbasket()
    {
        if (IS_AJAX) {
            $sid = $_GET['sid'];
            //前端必须保证登陆状态
            $vipid = $_SESSION['WAP']['vipid'];
            if (!$vipid) {
                $info['status'] = 3;
                $info['msg'] = '登陆已超时，2秒后自动跳转登陆页面！';
                $this->ajaxReturn($info);
            }
            $arr = $_POST;
            if ($sid == '') {
                $info['status'] = 0;
                $info['msg'] = '未获取SID参数';
                $this->ajaxReturn($info);
            }
            if (!$arr) {
                $info['status'] = 0;
                $info['msg'] = '未获取数据，请重新尝试';
                $this->ajaxReturn($info);
            }
            $m = M('Shop_basket');
            $mgoods = M('Shop_goods');
            $msku = M('Shop_goods_sku');
            $data = $m->where(array('sid' => $sid, 'vipid' => $_SESSION['WAP']['vipid']))->select();
            foreach ($data as $k => $v) {
                $goods = $mgoods->where('id=' . $v['goodsid'])->find();
                if ($v['sku']) {
                    $sku = $msku->where(array('sku' => $v['sku']))->find();
                    if ($sku && $sku['status'] && $goods && $goods['issku'] && $goods['status']) {
                        $nownum = $arr[$v['id']];
                        if ($sku['num'] - $nownum >= 0) {
                            //保存购物车新库存
                            if ($nownum <> $v['num']) {
                                $v['num'] = $nownum;
                                $rda = $m->save($v);
                            }
                        } else {
                            $info['status'] = 2;
                            $info['msg'] = '存在已下架或库存不足商品！';
                            $this->ajaxReturn($info);
                        }

                    } else {
                        $info['status'] = 2;
                        $info['msg'] = '存在已下架或库存不足商品！';
                        $this->ajaxReturn($info);
                    }
                } else {
                    if ($goods && $goods['status']) {
                        $nownum = $arr[$v['id']];
                        if ($goods['num'] - $nownum >= 0) {
                            //保存购物车新库存
                            if ($nownum <> $v['num']) {
                                $v['num'] = $nownum;
                                $rda = $m->save($v);
                            }
                        } else {
                            $info['status'] = 2;
                            $info['msg'] = '存在已下架或库存不足商品！';
                            $this->ajaxReturn($info);
                        }

                    } else {
                        $info['status'] = 2;
                        $info['msg'] = '存在已下架或库存不足商品！';
                        $this->ajaxReturn($info);
                    }

                }
            }
            $info['status'] = 1;
            $info['msg'] = '商品库存检测通过，进入结算页面！';
            $this->ajaxReturn($info);
        } else {
            $this->diemsg(0, '禁止外部访问！');
        }
    }

    //立刻购买逻辑
    public function fastbuy()
    {
        if (IS_AJAX) {
            $m = M('Shop_basket');
            $data = I('post.');
            if (!$data) {
                $info['status'] = 0;
                $info['msg'] = '未获取数据，请重新尝试';
                $this->ajaxReturn($info);
            }

            //参团时判断物品是否仍在团购时间内
            if($data['is_group_buy']){
                /*$pass_where['id']=$data['goodsid'];
                $pass_where['status']=1;//商品是否正常
                $pass_where['group_time_start']=array('ELT',time());//商品开团时间是否小于等于当前时间
                $pass_where['group_time_end']=array('EGT',time());//商品开团结束时间是否大于等于当前时间
                $is_pass_time = M('shop_goods')->where($pass_where)->find();*/
                $now = time();
                $is_pass_time = M()->query('select 1 from shop_goods where id='.$data['goodsid'].
                        ' and status=1 and group_time_start <'.$now.' and (group_time_end = 0 or group_time_end > '.$now.')');
                if(!$is_pass_time){
                    $info['status'] = 0;
                    $info['msg'] = '此商品团购时间已过！';
                    $this->ajaxReturn($info);
                }
            }
            //判断此商品是否已经正在开团
            $v = M('group_buy')->where(array('goods_id'=>$data['goodsid'],'creator_id'=>$data['vipid'],'status'=>'0'))->find();
            if($data['is_group_buy'] == '1'){
                if($v){
                    $info['status'] = 0;
                    $info['msg'] = '此商品您正在开团，不可重复开团！';
                    $this->ajaxReturn($info);
                }
            }
            //清除购物车
            $sid = 0;
            //前端必须保证登陆状态
            $vipid = $_SESSION['WAP']['vipid'];
            $re = $m->where(array('sid' => $sid, 'vipid' => $vipid))->delete();

            //区分SKU模式
            if ($data['sku']) {
                $rold = $m->add($data);
                if ($rold) {
                    $info['status'] = 1;
                    $info['msg'] = '库存检测通过！2秒后自动生成订单！';
                } else {
                    $info['status'] = 0;
                    $info['msg'] = '通讯失败，请重新尝试！';
                }
            } else {
                $rold = $m->add($data);
                if ($rold) {
                    $info['status'] = 1;
                    $info['msg'] = '库存检测通过！2秒后自动生成订单！';
                } else {
                    $info['status'] = 0;
                    $info['msg'] = '通讯失败，请重新尝试！';
                }
            }
            $this->ajaxReturn($info);
        } else {
            $this->diemsg(0, '禁止外部访问！');
        }
    }

    //团购信息页面：团购信息,团长,已买了几人
    public function groupBuyInfo(){
        $group_buy_id = I('get.group_buy_id');
        if(!$group_buy_id){
            $this->error('参数错误，请刷新后尝试');
        }
        $group_map['group_buy.id'] = $group_buy_id;
        //$group_map['status'] = 0;   //不正在团购也可以展示此页面

        $field = 'group_buy.create_time,group_buy.people_num,group_buy.status as g_status,shop_goods.*,vip.nickname,vip.headimgurl';
        $cache = M('group_buy')->field($field)
            ->join('left join shop_goods on group_buy.goods_id = shop_goods.id')
            ->join('left join vip on group_buy.creator_id = vip.id')
            ->where($group_map)->find();
        $cache['goods_pic'] = $this->getPic($cache['listpic'])['imgurl'];
        if(!$cache){
            $this->error('此团已结束，请选择其他团');
        }

        //判断是否能团购 以及，是否已成团
        //$is_in_time = $this->can_group_buy($cache);   //是否在时间范围内,因为后台可能将没有拼团成功的团变成‘成功’，所以此处暂不判断时限，暂时交给定时任务去判断

        if($cache['g_status'] != 0 && $cache['g_status'] != 1){ //不是开团或失败
                $cache['people_num'] = $cache['group_buy_num']; //组团成功的话，前台显示 参团人数 = 成团需要的人数
        }
        $cache['process']  = min(100,round($cache['people_num']*100/$cache['group_buy_num'])).'%';

        //dump($cache);
        $this->assign('cache',$cache);

        if($cache['g_status'] == 0){    //仅正在开团时，可以参团，生成带有group_buy_id参数的跳转链接
            $fromUrl = U('App/Shop/goods',array('sid'=>0,'id'=>$cache['id'],'ppid'=>self::$WAP['vip']['id'],'group_buy_id'=>$group_buy_id,));
        }else{
            $fromUrl = U('App/Shop/goods',array('sid'=>0,'id'=>$cache['id']));
        }

        $this->assign('fromUrl',$fromUrl);
        $this->assign('lasturl',base64_encode(U('App/Shop/groupBuyInfo',array('group_buy_id'=>$group_buy_id))));
        //$this->assign('is_in_time',$is_in_time);
        $this->assign('group_buy_id',$group_buy_id);

        //团购商品分享文案
        $share['title'] = '小猫飞购，优惠多多，快来拼团啦！！！';
        $share['desc']  = $cache['name'];
        $share['img']   = $cache['goods_pic'];
        $this->assign('share',$share);
        $this->display();
    }

    //团购逻辑(shop_goods页面，“几人团”按钮)
    public function groupBuy(){
        if (IS_AJAX) {
            $m = M('Shop_basket');
            $data = I('post.');
            if (!$data) {
                $info['status'] = 0;
                $info['msg'] = '未获取数据，请重新尝试';
                $this->ajaxReturn($info);
            }
            $sid = 0;

            //前端必须保证登陆状态
            $vipid = $_SESSION['WAP']['vipid'];
            $re = $m->where(array('sid' => $sid, 'vipid' => $vipid))->delete();
            //区分SKU模式
            if ($data['sku']) {
                $rold = $m->add($data);
                if ($rold) {
                    $info['status'] = 1;
                    $info['msg'] = '库存检测通过！2秒后自动生成订单！';
                } else {
                    $info['status'] = 0;
                    $info['msg'] = '通讯失败，请重新尝试！';
                }
            } else {
                $rold = $m->add($data);
                if ($rold) {
                    $info['status'] = 1;
                    $info['msg'] = '库存检测通过！2秒后自动生成订单！';
                } else {
                    $info['status'] = 0;
                    $info['msg'] = '通讯失败，请重新尝试！';
                }
            }
            $this->ajaxReturn($info);
        } else {
            $this->diemsg(0, '禁止外部访问！');
        }
    }

    //参团页面  弃用
    public function _groupBuyJoin_(){
        $data = I('get.');
        $is_group_buy = $data['is_group_buy'];  //2:跟团 1：开团
        $group_buy_id = $data['group_buy_id'];
        $goods_id = $data['goods_id'];

        $group_buy_info = M('group_buy')->join('left join shop_goods on group_buy.goods_id = shop_goods.id')
            ->where('group_buy.id='.$group_buy_id)->find();
        //dump($group_buy_info);
        $cache = M('shop_goods')->where('id='.$group_buy_info['goods_id'])->find();
        //dump($cache);

        $this->assign('cache',$cache);
        $this->assign('group_buy_info',$group_buy_info);
        $this->display();
    }
    //Order逻辑
    public function orderMake()
    {
        if (IS_POST) {      //处理付款结果
            $morder = M('Shop_order');
            $data = I('post.');
            //TODO 缓存商品所属店铺及商家
            $mgoods = M('Shop_goods');
            $itemsData = unserialize(stripslashes(htmlspecialchars_decode($data['items'])));
            $goodsid = array();
            foreach ($itemsData as $k => $v) {
                array_push($goodsid, $v["goodsid"]);
                $rlt = $mgoods->where(['id' => $v['goodsid']])->find();
                $itemsData[$k]['supplier_id'] = $rlt['supplier_id'];
                $itemsData[$k]['store_id'] = $rlt['store_id'];

                if ($rlt["num"] < $v["num"]) {
                    $this->error("库存不足，暂时无法购买");
                }
            }
            $data['items'] = serialize($itemsData);
            $data['ispay'] = 0;
            $data['status'] = 1;//订单成功，未付款
            $data['ctime'] = time();
            $data['payprice'] = $data['totalprice'];
            //代金卷流程
            if ($data['djqid']) {
                $mcard = M('Vip_card');
                $djq = $mcard->where('id=' . $data['djqid'])->find();
                if (!$djq) {
                    $this->error('通讯失败！请重新尝试支付！');
                }
                if ($djq['usetime']) {
                    $this->error('此代金卷已使用！');
                }
                $djq['status'] = 2;
                $djq['usetime'] = time();
                $rdjq = $mcard->save($djq);
                if (FALSE === $rdjq) {
                    $this->error('通讯失败！请重新尝试支付！');
                }
                //修改支付价格
                $data['payprice'] = $data['totalprice'] - $djq['money'];
            }

            //邮费逻辑
            if (self::$WAP['shopset']['isyf']) {
                if ($data['totalprice'] >= self::$WAP['shopset']['yftop']) {
                    $data['yf'] = 0;
                } else {
                    $data['yf'] = self::$WAP['shopset']['yf'];
                    $data['payprice'] = $data['payprice'] + $data['yf'];
                }
            } else {
                $data['yf'] = 0;
            }
            //组团、跟团的判断
            if($data['is_group_buy']){
                //判断商品是否可以团购
                if($rlt['group_time_start'] < time() && $rlt['is_group_buy'] == 1){
                    if($rlt['group_time_end'] > time() || $rlt['group_time_end'] == "0"){

                        if($data['is_group_buy'] == 2){ //is_group_buy =2;为跟团，1:为组团
                            $group_buy_info = M('group_buy')->where('id='.$data['group_buy_id'])->find();
                            if($group_buy_info['status'] != 0){
                                $this->error('此团已满，请选择其他团');
                                exit;
                            }
                            $is_group_buy = 2;
                            $data['is_group_buy'] = 1;  //数据库中只用1表示团购
                        }else if($data['is_group_buy'] == 1){
                            $is_group_buy = 1;
                        }

                    }else{
                        $this->error('此商品团购时间已过！');
                        exit;
                    }
                }else{
                    $this->error('此商品团购时间已过！');
                    exit;
                }
            } else{
                $is_group_buy = 0;  //不是团购
            }
            $re = $morder->add($data);      //添加订单记录
            if ($re) {
                $old = $morder->where('id=' . $re)->setField('oid',getOrderCode(self::$WAP["vipid"], 0));
                $pld = $morder->where('id=' . $re)->setField('pickid',getPickCode(self::$WAP["vipid"]));
                if (FALSE !== $old && FALSE !== $pld) {
                    //后端日志
                    $mlog = M('Shop_order_syslog');
                    $dlog['oid'] = $re;
                    $dlog['msg'] = '订单创建成功';
                    $dlog['type'] = 1;
                    $dlog['ctime'] = time();
                    $rlog = $mlog->add($dlog);
                    //清空购物车
                    $rbask = M('Shop_basket')->where(array('sid' => $data['sid'], 'vipid' => $data['vipid']))->delete();
                    $this->redirect('App/Shop/pay/', array('sid' => $data['sid'], 'orderid' => $re,'is_group_buy'=>$is_group_buy,'group_buy_id'=>$data['group_buy_id']));
                } else {
                    $old = $morder->delete($re);
                    $this->error('订单生成失败！请重新尝试！');
                }
            } else {
                //可能存在代金卷问题
                $this->error('订单生成失败！请重新尝试！');
            }
        } else {        //展示付款页面
            //非提交状态
            $sid = $_GET['sid'] <> '' ? $_GET['sid'] : $this->diemsg(0, '缺少SID参数');//sid可以为0
            $lasturl = $_GET['lasturl'] ? $_GET['lasturl'] : $this->diemsg(0, '缺少LastURL参数');
            $basketlasturl = base64_decode($lasturl);
            $basketurl = U('App/Shop/basket', array('sid' => $sid, 'lasturl' => $lasturl));
            $backurl = base64_encode($basketurl);
            $basketloginurl = U('App/Vip/login', array('backurl' => $backurl));
            $re = $this->checkLogin($backurl);
            //保存当前购物车地址
            $this->assign('basketurl', $basketurl);
            //保存登陆购物车地址
            $this->assign('basketloginurl', $basketloginurl);
            //保存购物车前地址
            $this->assign('basketlasturl', $basketlasturl);
            //保存lasturlencode
            //保存购物车加密地址，用于OrderMaker正常返回
            $this->assign('lasturlencode', $lasturl);
            $this->assign('sid', $sid);
            //清空临时地址
            unset($_SESSION['WAP']['orderURL']);
            //已登陆
            $m = M('Shop_basket');
            $mgoods = M('Shop_goods');
            $msku = M('Shop_goods_sku');

            $cache = $m->where(array('sid' => $sid, 'vipid' => $_SESSION['WAP']['vipid']))->select();
            //错误标记
            $errflag = 0;
            //等待删除ID
            $todelids = '';
            //totalprice
            $totalprice = 0;
            //totalnum
            $totalnum = 0;
            foreach ($cache as $k => $v) {
                //sku模型
                $goods = $mgoods->where('id=' . $v['goodsid'])->find();
                $pic = $this->getPic($goods['pic']);
                //分sku和非sku
                if ($v['sku']) {
                    //取商品数据				
                    if ($goods['issku'] && $goods['status']) {
                        $map['sku'] = $v['sku'];
                        $sku = $msku->where($map)->find();
                        if ($sku['status']) {
                            if ($sku['num']) {
                                //调整购买量
                                $cache[$k]['goodsid'] = $goods['id'];
                                $cache[$k]['skuid'] = $sku['id'];
                                $cache[$k]['name'] = $goods['name'];
                                $cache[$k]['skuattr'] = $sku['skuattr'];
                                $cache[$k]['num'] = $v['num'] > $sku['num'] ? $sku['num'] : $v['num'];

                                if($v['is_group_buy']){
                                    //是团购时，且商品开启了sku,去sku中 取团购价格
                                    $cache[$k]['price'] = $sku['group_buy_price'];
                                    $this->assign('is_group_buy',$v['is_group_buy']);
                                    $this->assign('group_buy_id',$v['group_buy_id']);
                                }else{
                                    $cache[$k]['price'] = $sku['price'];
                                }
                                $cache[$k]['total'] = $v['num'] * $sku['price'];
                                $cache[$k]['pic'] = $pic['imgurl'];
                                $totalnum = $totalnum + $cache[$k]['num'];
                                $totalprice = $totalprice + $cache[$k]['price'] * $cache[$k]['num'];
                            } else {
                                //无库存删除
                                $todelids = $todelids . $v['id'] . ',';
                                unset($cache[$k]);
                            }
                        } else {
                            //下架删除
                            $todelids = $todelids . $v['id'] . ',';
                            unset($cache[$k]);
                        }
                    } else {
                        //下架删除
                        $todelids = $todelids . $v['id'] . ',';
                        unset($cache[$k]);
                    }

                } else {    //如果没有sku数据
                    if ($goods['status']) {
                        if ($goods['num']) {
                            //调整购买量
                            $cache[$k]['goodsid'] = $goods['id'];
                            $cache[$k]['skuid'] = 0;
                            $cache[$k]['name'] = $goods['name'];
                            $cache[$k]['skuattr'] = $sku['skuattr'];
                            $cache[$k]['num'] = $v['num'] > $goods['num'] ? $goods['num'] : $v['num'];
                            if($v['is_group_buy']){
                                //是团购则获取团购价格
                                $cache[$k]['price'] = $goods['group_buy_money'];
                                $this->assign('is_group_buy',$v['is_group_buy']);
                                $this->assign('group_buy_id',$v['group_buy_id']);
                            }else{
                                $cache[$k]['price'] = $goods['price'];
                            }
                            $cache[$k]['total'] = $v['num'] * $goods['price'];
                            $cache[$k]['pic'] = $pic['imgurl'];
                            $totalnum = $totalnum + $cache[$k]['num'];
                            $totalprice = $totalprice + $cache[$k]['price'] * $cache[$k]['num'];
                        } else {
                            //无库存删除
                            $todelids = $todelids . $v['id'] . ',';
                            unset($cache[$k]);
                        }
                    } else {
                        //下架删除
                        $todelids = $todelids . $v['id'] . ',';
                        unset($cache[$k]);
                    }
                }
            }
            if ($todelids) {  //是团购的时候，不去处理购物车
                $rdel = $m->delete($todelids);
                if (!$rdel) {
                    $this->error('购物车获取失败，请重新尝试！');
                }
            }
            //将商品列表
            sort($cache);
            $allitems = serialize($cache);
            $this->assign('allitems', $allitems);
            //VIP信息
            $vipadd = I('vipadd');
            if ($vipadd) {
                $vip = M('Vip_address')->where('id=' . $vipadd)->find();
            } else {
                $vip = M('Vip_address')->where('vipid=' . $_SESSION['WAP']['vipid'])->find();
            }
            $this->assign('vip', $vip);
            //可用代金卷
            $mdjq = M('Vip_card');
            $mapdjq['type'] = 2;
            $mapdjq['vipid'] = $_SESSION['WAP']['vipid'];
            $mapdjq['status'] = 1;//1为可以使用
            $mapdjq['usetime'] = 0;
            $mapdjq['etime'] = array('gt', time());
            $mapdjq['usemoney'] = array('lt', $totalprice);
            $djq = $mdjq->field('id,money')->where($mapdjq)->select();
            $this->assign('djq', $djq);
            //邮费逻辑
            if (self::$WAP['shopset']['isyf']) {
                $this->assign('isyf', 1);
                $yf = $totalprice >= self::$WAP['shopset']['yftop'] ? 0 : self::$WAP['shopset']['yf'];
                $this->assign('yf', $yf);
                $this->assign('yftop', self::$WAP['shopset']['yftop']);
            } else {
                $this->assign('isyf', 0);
                $this->assign('yf', 0);
            }
            //是否可以用余额支付
            $useryue = $_SESSION['WAP']['vip']['money'];
            $isyue = $_SESSION['WAP']['vip']['money'] - $totalprice >= 0 ? 0 : 1;
            $this->assign('isyue', $isyue);
            //
            $this->assign('cache', $cache);
            $this->assign('totalprice', $totalprice);
            $this->assign('totalnum', $totalnum);
            $this->display();
        }

    }

    //订单地址跳转
    public function orderAddress()
    {
        $sid = I('sid');
        $lasturlencode = I('lasturl');
        $backurl = U('App/Shop/orderMake', array('sid' => $sid, 'lasturl' => $lasturlencode));
        $_SESSION['WAP']['orderURL'] = $backurl;
        $this->redirect('App/Vip/address');
    }

    //订单列表
    public function orderList()
    {
        $sid = I('sid') <> '' ? I('sid') : $this->diemsg(0, '缺少SID参数');//sid可以为0
        $type = I('type') ? I('type') : 4;
        $this->assign('type', $type);
        $bkurl = U('App/Shop/orderList', array('sid' => $sid, 'type' => $type));
        $backurl = base64_encode($bkurl);
        $loginurl = U('App/Vip/login', array('backurl' => $backurl));
        $re = $this->checkLogin($backurl);
        //已登陆
        $time = array(array('egt', strtotime("-1 month")), array('lt', strtotime("+1 day")), 'and');
        $m = M('Shop_order');
        $vipid = $_SESSION['WAP']['vipid'];
        $map['sid'] = $sid;
        $map['vipid'] = $vipid;
        $map["ctime"] = $time;
        switch ($type) {
            case '1':
                $map['status'] = 1;
                $where["status"] = 0;
                $where["is_pay"] = 0;
                break;
            case '2':
                $map['status'] = array('in', array('2', '3'));
                $where["status"] = 1;
                $where["is_pay"] = 1;
                break;
            case '3':
                $map['status'] = array('in', array('5', '6'));
                $where["status"] = 2;
                break;
            case '4':
                //全部
                $map['status'] = array('neq', '0');
                $where["status"] = array('egt', '0');
                break;
            default:
                $map['status'] = 1;
                $where["status"] = 0;
                $where["is_pay"] = 0;
                break;
        }
        $map['is_group_buy'] = 0;
        $cache = array();
        $cache_sc = $m->where($map)->order('ctime desc')->select();
        if ($cache_sc) {
            foreach ($cache_sc as $k => $v) {
                if($v['items']){
                    $cache_sc[$k]['items'] = unserialize($v['items']);
                }else{
                    $cache_sc[$k]['items'] = array();
                }
                $cache[$k]["order_type"] = 0;
                $cache[$k]["is_play"] = $cache_sc[$k]['is_play'];
                $cache[$k]["oid"] = $cache_sc[$k]['oid'];
                $cache[$k]["status"] = $cache_sc[$k]['status'];
                $cache[$k]["totalnum"] = $cache_sc[$k]['totalnum'];
                $cache[$k]["payprice"] = $cache_sc[$k]['payprice'];
                $cache[$k]["id"] = $cache_sc[$k]['id'];
                $cache[$k]["paytype"] = $cache_sc[$k]['paytype'];
                $cache[$k]["items"] = $cache_sc[$k]['items'];
                $cache[$k]["ctime"] = $cache_sc[$k]['ctime'];
                $cache[$k]["totalprice"] = $cache_sc[$k]["totalprice"];
                $cache[$k]["other"] = 0; //代支付
            }
        }
        $where["order_time"] = $time;
        $where["vip_buyer_id"] = $vipid;
        $cache_sm = M("supplier_order")->where($where)->select();
        if ($cache_sm) {
            $i = count($cache_sc);
            foreach ($cache_sm as $k => $v) {
                $store = M("supplier_store")->where(array("id" => $cache_sm[$k]['store_id']))->find();
                if ($store) {
                    $listpic = $this->getPic($store["thumb"]);
                    $cache_sm[$k]['items'] = array(array("name" => $store["name"], "skuattr" => $v["memo"], "pic" => $listpic['imgurl'], "price" => $cache_sm[$k]['total_price'], "num" => 1));
                } else {
                    $cache_sm[$k]['items'] = array();
                }
                $cache[$i + $k]["order_type"] = 1;
                $cache[$i + $k]["is_play"] = $cache_sm[$k]['is_play'];
                $cache[$i + $k]["oid"] = $cache_sm[$k]['order_code'];

                if($cache_sm[$k]['status'] == 3){
                    $cache[$i + $k]["status"] = 13;
                }else{
                    $cache[$i + $k]["status"] = ($cache_sm[$k]['is_play'] == 1 && $cache_sm[$k]['status'] == 0) ? intval($cache_sm[$k]['status']) + 10 : 14;
                }

                $cache[$i + $k]["totalnum"] = 1;
                $cache[$i + $k]["payprice"] = $cache_sm[$k]['total_price'];
                $cache[$i + $k]["id"] = $cache_sm[$k]['id'];
                $cache[$i + $k]["paytype"] = $cache_sm[$k]['paytype'];
                $cache[$i + $k]["items"] = $cache_sm[$k]['items'];
                $cache[$i + $k]["ctime"] = $cache_sm[$k]['order_time'];
                $cache[$i + $k]["totalprice"] = $cache_sm[$k]["total_price"];
                $cache[$i + $k]["other"] = $cache_sm[$k]["is_payforother"]; //代支付
                $cache[$i + $k]["is_my"] = $cache_sm[$k]["creator_id"] == self::$WAP["vipid"] ? 1 : 0;
            }
        }
        $sort = array();
        foreach ($cache as $item) {
            $sort[] = $item['ctime'];
        }
        array_multisort($sort, SORT_DESC, $cache);
        $this->assign('cache', $cache);

        //高亮底导航
        $this->assign('actname', 'ftorder');
        $this->display();
    }

    //团购订单列表
    public function groupOrderList(){
        $sid = I('sid') <> '' ? I('sid') : $this->diemsg(0, '缺少SID参数');//sid可以为0
        $type = I('type') ? I('type') : 4;
        $this->assign('type', $type);
        $bkurl = U('App/Shop/groupOrderList', array('sid' => $sid, 'type' => $type));
        $backurl = base64_encode($bkurl);
        $loginurl = U('App/Vip/login', array('backurl' => $backurl));
        $re = $this->checkLogin($backurl);
        //已登陆
        //$time = array(array('egt', strtotime("-1 month")), array('lt', strtotime("+1 day")), 'and');
        $m = M('Shop_order');
        $vipid = $_SESSION['WAP']['vipid'];
        $map['sid'] = $sid;
        $map['vipid'] = $vipid;
        //$map["ctime"] = $time;
        $map['shop_order.status'] = array('gt','0');
        switch ($type) {
            case '1': //正在拼团
               $map['group_buy.status'] = 0;
                break;
            case '2': //拼团成功
               $map['group_buy.status'] = array('in','2,3');
                break;
            case '3': //拼团失败
                $map['group_buy.status'] = 1;
                break;
            case '4': //全部
                $map['shop_order.status'] = array('gt','0');
                break;
            case '5': //未支付
                $map['shop_order.status'] = 1;
                break;
        }
        $map['is_group_buy'] = 1;
        $cache = array();
        $field = "shop_order.id as id,group_buy.id as group_id,oid,group_buy.status as gstatus,shop_order.status as sstatus,totalnum,payprice,items,paytype,ctime";
        $cache_sc = $m->join('left join group_buy ON shop_order.group_buy_id = group_buy.id')->field($field)->where($map)->order('ctime desc')->select();
        if ($cache_sc) {
            foreach ($cache_sc as $k => $v) {
                if ($v['items']){
                    $cache_sc[$k]['items'] = unserialize($v['items']);
                } else {
                    $cache_sc[$k]['items'] = array();
                }
                $cache[$k]["order_type"] = 0;
                $cache[$k]["is_play"] = $cache_sc[$k]['is_play'];
                $cache[$k]["oid"] = $cache_sc[$k]['oid'];
                $cache[$k]["gstatus"] = $cache_sc[$k]['gstatus'];
                $cache[$k]["sstatus"] = $cache_sc[$k]['sstatus'];
                $cache[$k]["totalnum"] = $cache_sc[$k]['totalnum'];
                $cache[$k]["payprice"] = $cache_sc[$k]['payprice'];
                $cache[$k]["id"] = $cache_sc[$k]['id'];
                $cache[$k]["group_id"] = $cache_sc[$k]['group_id'];
                $cache[$k]["paytype"] = $cache_sc[$k]['paytype'];
                $cache[$k]["items"] = $cache_sc[$k]['items'];
                $cache[$k]["ctime"] = $cache_sc[$k]['ctime'];
                $cache[$k]["totalprice"] = $cache_sc[$k]["totalprice"];
                $cache[$k]["other"] = 0; //代支付
            }
        }
   /*     $sort = array();
        foreach ($cache as $item) {
            $sort[] = $item['ctime'];
        }
        array_multisort($sort, SORT_ASC, $cache);*/
        $this->assign('cache', $cache);

        //高亮底导航
        $this->assign('actname', 'ftorder');
        $this->display();
    }


    //订单详情
    //订单列表
    public function orderDetail()
    {
        $cate = intval(I("cate")); //0:商城，1:商盟
        $sid = I('sid') <> '' ? I('sid') : $this->diemsg(0, '缺少SID参数');//sid可以为0
        $orderid = I('orderid') <> '' ? I('orderid') : $this->diemsg(0, '缺少ORDERID参数');
        $bkurl = U('App/Shop/orderDetail', array('sid' => $sid, 'orderid' => $orderid));
        $backurl = base64_encode($bkurl);
        $loginurl = U('App/Vip/login', array('backurl' => $backurl));
        $re = $this->checkLogin($backurl);
        $type = I('get.type');
        $this->assign('type',$type);
        //已登陆
        if ($cate == 0) {
            $m = M('Shop_order');
            $vipid = $_SESSION['WAP']['vipid'];
            $map['sid'] = $sid;
            $map['id'] = $orderid;
            $cache = $m->where($map)->find();
            if (!$cache) {
                $this->diemsg('此订单不存在!');
            }
            $cache['items'] = unserialize($cache['items']);
            //order日志
            $mlog = M('Shop_order_log');
            $log = $mlog->where('oid=' . $cache['id'])->select();
            $this->assign('log', $log);
            if (!$cache['status'] == 1) {
                //是否可以用余额支付
                $useryue = $_SESSION['WAP']['vip']['money'];
                $isyue = $_SESSION['WAP']['vip']['money'] - $cache['payprice'] >= 0 ? 0 : 1;
                $this->assign('isyue', $isyue);
            }
            $cache["other"] = 0;//代支付
            $cache["type"] = 0;
            $this->assign('cache', $cache);
            //代金卷调用
            if ($cache['djqid']) {
                $djq = M('Vip_card')->where('id=' . $cache['djqid'])->find();
                $this->assign('djq', $djq);
            }
            //高亮底导航
            $this->assign('actname', 'ftorder');
        } else {
            $m = M('Supplier_order');
            $vipid = $_SESSION['WAP']['vipid'];
            $map['sid'] = $sid;
            $map['id'] = $orderid;
            $cache = $m->where($map)->find();
            if(!$cache){
                $this->diemsg('此订单不存在!');
            }
            $store = M("supplier_store")->where(array("id" => $cache['store_id']))->find();
            $listpic = $this->getAlbum($store["pics"]);
            $cache['items'] = array(array("name" => $store["name"], "skuattr" => "", "pic" => $listpic[0]['imgurl'], "price" => $cache['pay_price'], "num" => 1));
            //order日志
            $mlog = M('Supplier_order_log');
            $log = $mlog->where('oid=' . $cache['id'])->select();
            $this->assign('log', $log);
            if (!$cache['status'] == 1) {
                //是否可以用余额支付
                $useryue = $_SESSION['WAP']['vip']['money'];
                $isyue = $_SESSION['WAP']['vip']['money'] - $cache['total_price'] >= 0 ? 0 : 1;
                $this->assign('isyue', $isyue);
            }
            $cache['status'] = intval($cache['status']) + 10;
            $cache["payprice"] = $cache["total_price"];
            $cache["totalprice"] = $cache["total_price"];
            $cache["oid"] = $cache["order_code"];
            $cache["ctime"] = $cache["order_time"];
            $cache["yf"] = 0;
            $M_VIP = M("vip")->where(array("id" => $vipid))->find();
            $cache["vipname"] = $M_VIP["name"];
            $cache["vipmobile"] = $M_VIP["mobile"];
            $cache["vipaddress"] = "";
            $cache["msg"] = "";
            $cache["fahuokd"] = "";
            $cache["totalnum"] = 1;
            $cache["paytype"] = $cache["pay_type"];
            $cache["type"] = 1;
            $cache["etime"] = intval($cache["is_payforother"]) == 0 ? $cache["pay_time"] : $cache["check_account_time"];
            $cache["closetime"] = $cache["close_time"];
            $cache["other"] = $cache["is_payforother"];//代支付
            $this->assign('cache', $cache);
            $this->assign('djq', "");
        }

        //饭回来列表按钮返回页面的url
        if($cache['is_group_buy']){
            $backUrl = U('App/Shop/groupOrderList',array('sid'=>0,'type'=>$type));
        }else{
            $backUrl = U('App/Shop/orderList',array('sid'=>0,'type'=>$type));
        }
        $this->assign('backUrl',$backUrl);

        $this->display();
    }

    //订单取消
    public function orderCancel()
    {
        $sid = I('sid') <> '' ? I('sid') : $this->diemsg(0, '缺少SID参数');//sid可以为0
        $orderid = I('orderid') <> '' ? I('orderid') : $this->diemsg(0, '缺少ORDERID参数');
        $bkurl = U('App/Shop/orderDetail', array('sid' => $sid, 'orderid' => $orderid));
        $backurl = base64_encode($bkurl);
        $loginurl = U('App/Vip/login', array('backurl' => $backurl));
        $re = $this->checkLogin($backurl);
        //已登陆
        $m = M('Shop_order');
        $map['sid'] = $sid;
        $map['id'] = $orderid;
        $cache = $m->where($map)->find();
        if (!$cache) {
            $this->diemsg(0, '此订单不存在!');
        }
        if ($cache['status'] <> 1) {
            $this->error('只有未付款订单可以取消！');
        }
        $re = $m->where($map)->setField('status', 0);
        if ($re) {
            //订单取消只有后端日志
            $mslog = M('Shop_order_syslog');
            $dlog['oid'] = $cache['id'];
            $dlog['msg'] = '订单取消';
            $dlog['type'] = 0;
            $dlog['ctime'] = time();
            $rlog = $mslog->add($dlog);
            $this->success('订单取消成功！');
        } else {
            $this->error('订单取消失败,请重新尝试！');
        }
    }

    //确认收货
    public function orderOK()
    {
        $sid = I('sid') <> '' ? I('sid') : $this->diemsg(0, '缺少SID参数');//sid可以为0
        $orderid = I('orderid') <> '' ? I('orderid') : $this->diemsg(0, '缺少ORDERID参数');
        $bkurl = U('App/Shop/orderDetail', array('sid' => $sid, 'orderid' => $orderid));
        $backurl = base64_encode($bkurl);
        $loginurl = U('App/Vip/login', array('backurl' => $backurl));
        $re = $this->checkLogin($backurl);
        //已登陆
        $m = M('Shop_order');
        $map['sid'] = $sid;
        $map['id'] = $orderid;
        $cache = $m->where($map)->find();
        if (!$cache) {
            $this->diemsg(0, '此订单不存在!');
        }
        if ($cache['status'] <> 3) {
            $this->error('只有待收货订单可以确认收货！');
        }
        $cache['etime'] = time();//交易完成时间
        $cache['status'] = 5;
        $rod = $m->save($cache);
        if (FALSE !== $rod) {
            $commission = D('Commission');
            $rlt = $commission->process($orderid, self::$WAP['shopset'], 'app', OT_SC);
            $this->success($rlt['msg']);
        } else {
            //后端日志
            $mlog = M('Shop_order_syslog');
            $dlog['oid'] = $cache['id'];
            $dlog['msg'] = '确认收货失败';
            $dlog['type'] = -1;
            $dlog['paytype'] = $cache['paytype'];
            $dlog['ctime'] = time();
            $rlog = $mlog->add($dlog);
            $this->error('确认收货失败，请重新尝试！');
        }
    }

    //订单退货
    public function orderTuihuo()
    {
        $sid = I('sid') <> '' ? I('sid') : $this->diemsg(0, '缺少SID参数');//sid可以为0
        $orderid = I('orderid') <> '' ? I('orderid') : $this->diemsg(0, '缺少ORDERID参数');
        $bkurl = U('App/Shop/orderTuihuo', array('sid' => $sid, 'orderid' => $orderid));
        $backurl = base64_encode($bkurl);
        $loginurl = U('App/Vip/login', array('backurl' => $backurl));
        $re = $this->checkLogin($backurl);
        //已登陆
        $m = M('Shop_order');
        $vipid = $_SESSION['WAP']['vipid'];
        $map['sid'] = $sid;
        $map['id'] = $orderid;
        $cache = $m->where($map)->find();
        if (!$cache) {
            $this->diemsg('此订单不存在!');
        }
        $cache['items'] = unserialize($cache['items']);

        $this->assign('cache', $cache);
        //代金卷调用
        if ($cache['djqid']) {
            $djq = M('Vip_card')->where('id=' . $cache['djqid'])->find();
            $this->assign('djq', $djq);
        }
        //高亮底导航
        $this->assign('actname', 'ftorder');
        $this->display();
    }

    //订单取消
    public function orderTuihuoSave()
    {
        $sid = I('sid') <> '' ? I('sid') : $this->diemsg(0, '缺少SID参数');//sid可以为0
        $orderid = I('orderid') <> '' ? I('orderid') : $this->diemsg(0, '缺少ORDERID参数');
        $bkurl = U('App/Shop/orderTuihuo', array('sid' => $sid, 'orderid' => $orderid));
        $backurl = base64_encode($bkurl);
        $loginurl = U('App/Vip/login', array('backurl' => $backurl));
        $re = $this->checkLogin($backurl);
        //已登陆
        $m = M('Shop_order');
        $map['sid'] = $sid;
        $map['id'] = $orderid;
        $cache = $m->where($map)->find();
        if (!$cache) {
            $this->diemsg(0, '此订单不存在!');
        }
        if ($cache['status'] <> 3) {
            $this->error('只有待收货订单可以办理退货！');
        }
        $data = I('post.');
        $cache['status'] = 4;
        $cache['tuihuoprice'] = $data['tuihuoprice'];
        $cache['tuihuokd'] = $data['tuihuokd'];
        $cache['tuihuokdnum'] = $data['tuihuokdnum'];
        $cache['tuihuomsg'] = $data['tuihuomsg'];
        //退货申请时间
        $cache['tuihuosqtime'] = time();
        $re = $m->where($map)->save($cache);
        if ($re) {
            //后端日志
            $mlog = M('Shop_order_log');
            $mslog = M('Shop_order_syslog');
            $dlog['oid'] = $cache['id'];
            $dlog['msg'] = '申请退货';
            $dlog['ctime'] = time();
            $rlog = $mlog->add($dlog);
            $dlog['type'] = 4;
            $rslog = $mslog->add($dlog);
            $this->success('申请退货成功！请等待工作人员审核！');
        } else {
            $this->error('申请退货失败,请重新尝试！');
        }
    }

    //订单支付
    public function pay()
    {
        //$is_group_buy = I('get.is_group_buy/d');    // 1：开团 2：跟团

        $sid = I('sid') <> '' ? I('sid') : $this->diemsg(0, '缺少SID参数');//sid可以为0
        $orderid = I('orderid') <> '' ? I('orderid') : $this->diemsg(0, '缺少ORDERID参数');
        $type = I('type');
        $bkurl = U('App/Shop/pay', array('sid' => $sid, 'orderid' => $orderid, 'type' => $type));
//		$backurl=base64_encode($orderdetail);
        $backurl = base64_encode($bkurl);
        $loginurl = U('App/Vip/login', array('backurl' => $backurl));
        $re = $this->checkLogin($backurl);
        //已登陆
        $m = M('Shop_order');
        $order = $m->where('id=' . $orderid)->find();
        if (!$order) {
            $this->error('此订单不存在！');
        }
        if ($order['status'] <> 1) {
            $this->error('此订单不可以支付！');
        }
        $itemsData = unserialize(stripslashes(htmlspecialchars_decode($order['items'])));
        foreach ($itemsData as $k => $v) {
            $rlt = M("shop_goods")->where(['id' => $v['goodsid']])->find();
            if($rlt['is_group_buy']) {
                //$goodsid = $v['goodsid'];   //此处团购订单用，团购时只有一种商品，所以不用数组
                $is_group_buy = 1;
                if($rlt['group_buy_id']){   //如果是跟团
                    $group_status = M('group_buy')->where('id='.$rlt['group_buy_id'])->getField('status');
                    if($group_status != 0){
                        $this->error('此团无法参加，请更换其他团;');
                    }
                }
            }
            if ($rlt["num"] < $v["num"]) {
                $this->error("库存不足，订单无法支付");
            }
        }
        $paytype = I('type') ? I('type') : $order['paytype'];
        $orderCode = $order['oid'];
        switch ($paytype) {
            case 'money':
                $balancePay = D('BalancePay');
                $rltPay = $balancePay->pay($orderCode, OT_SC);
                if ($rltPay) {
                    if ($rltPay['status'] == 0) {
                        if($is_group_buy){
                            $backUrl = U('App/Shop/groupBuyInfo',array('group_buy_id'=>$rltPay['group_buy_id'],'show_layer'=>1));   //show_layer:展示分享提示图层
                            if($rltPay['group_buy_id'] == ""){
                                $backUrl = U('App/Shop/orderList', array('sid' => 0));
                            }
                        }else{
                            $backUrl = U('App/Shop/orderList', array('sid' => 0));
                        }
                        $this->success('余额付款成功！', $backUrl);
                    } else if ($rltPay['status'] == 4) {
                        //余额不足
                        $this->error('余额不足，请使用其它方式付款！');
                    } else {
                        //支付失败
                        $this->error('余额支付失败，请重新尝试！');
                    }
                } else {
                    $this->error('余额付款失败！请联系客服！');
                }
                break;
            case  'alipayApp':  //已弃用
                $this->redirect("App/Alipay/alipay", array('sid' => $sid, 'price' => $order['payprice'], 'oid' => $order['oid'], "cate" => 0));
                break;
            case 'wxpay':
                $_SESSION['wxpaysid'] = 0;
                $_SESSION['wxpayopenid'] = $_SESSION['WAP']['vip']['openid'];//追入会员openid
                $this->redirect('Home/Wxpay/pay', array('oid' => $order['oid'], "cate" => 0,'is_group_buy'=>$is_group_buy));
                break;
            default:
                $this->error('支付方式未知！');
                break;
        }
    }

    /**
     * 团购订单微信支付成功后，跳转此页面，判断团购id是多少，然后跳转团购页面
     */
    public function groupBuyJudge(){
        $oid = I('get.oid');
        if($oid) {
            $info = M('shop_order')->field('is_group_buy,group_buy_id')->where('oid="'.$oid.'"')->find();
            if ($info['group_buy_id']) {
                $this->redirect('App/Shop/groupBuyInfo', array('group_buy_id' => $info['group_buy_id'], 'show_layer' => 1));
            }
        }
        $this->redirect('App/Shop/groupOrderList',array('sid'=>0,'type'=>0));
    }

    /**
     *   判断这款商品能否参与团购
     *  @param $goods 商品信息：从数据库中取出的商品数据
     *  @param $group_buy_id 是否个是跟团 1：是  0：不是
     * @return array
     */
    public function can_group_buy($goods,$group_buy_id = 0){
        //判断团购时间
        if($goods['is_group_buy']==1 && $goods['group_time_start'] < time()){
            if($goods['group_time_end'] == 0 || $goods['group_time_end'] > time()){

                //如果是跟团，判断此团的总人数
                if($group_buy_id){
                    $data = M('group_buy')->where('id='.$group_buy_id)->find();
                    if($data['status'] == 0){ //仅正在开团 才能参与
                        return ['status'=>1,'msg'=>'可以参团'];
                    }else{
                        return ['status'=>0,'msg'=>'此团已拼团成功，请您参加其他团'];
                    }
                }
                $info['status'] = 1;
                $info['msg'] = '此商品可以团购';
                return $info;
            }else{
                return ['status'=>0,'msg'=>'此商品已超过团购时间啦~~~'];
            }
        }else{
            return ['status'=>0,'msg'=>'此商品暂时无法团购'];
        }

        $info['status'] = 0;
        $info['msg'] = '此商品已超过团购时限';
        return $info;
    }

    //前台选择自己所在的学校
    public function selectSchool(){
        $school = M('location_school')->field('id,school_name as name')->where('is_open=1')->select();
        $this->ajaxReturn($school);
    }
    //保存学校信息
    public function saveSchool(){
        $info = M('location_school')->find();

        if(self::$WAP['vip']['school_id'] != 0){
            $this->ajaxReturn(['status'=>0,'msg'=>'您已经设置过学校，不能再次设置']);
        }

        $id = I('id/d');
        $school = I('school_remark','','htmlspecialchars');
        //$data = ['id'=>$id,'remark_school'=>$school];
        if(self::$WAP['vip']['role'] == 0) {    //普通消费者
            if ($id == -1) {
                if (!$school) {
                    $this->ajaxReturn(['status' => 0, 'msg' => '请输入您的学校名称']);
                }
                //目前全部学校的总监都是一个人，选择任何学校总监都是此人
                $info = M('location_school')->find();
                $data = ['role_1_id' => $info['role_1_id'], 'role_2_id' => 0, 'school_id' => $id, 'remark_school' => $school];
            } else {
                //找到相应学校的一二级（第三级不管：讨论结果，三级不论是哪个学校的，只要是他推广的，就是他的下线）
                //学校信息不做绑定
                $s_info = M('location_school')->where('id=' . $id)->find();
                $data = ['role_1_id' => $s_info['role_1_id'], 'role_2_id' => $s_info['role_2_id'],'school_id'=>$id];
            }
        }else{
            if($id == -1){
                $this->ajaxReturn(['stauts'=>0,'msg'=>'其他学校暂不设置推广人员']);
            }
            $data = ['school_id'=>$id];
        }

        $_SESSION['WAP']['vip']['school_id'] = $id;//有时设置过后，覆盖层还出现，所以直接覆盖session值

        $res = M('vip')->where('id='.self::$WAP['vip']['id'])->save($data);
        if($res){
            $this->ajaxReturn(['status'=>1,'msg'=>'设置成功,感谢您的配合，祝您购物愉快！']);
        }else{
            $this->ajaxReturn(['stauts'=>0,'msg'=>'保存失败，请刷新后尝试']);
        }
    }
}
