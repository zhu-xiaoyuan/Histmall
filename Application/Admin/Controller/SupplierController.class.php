<?php
/**
 * 商家入驻
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2016/6/17 0017
 * Time: 16:19
 */
namespace Admin\Controller;

//use Think\Model;

class SupplierController extends BaseController
{
    public function _initialize()
    {
        parent::_initialize();
    }

    //添加商家
    public function add(){
        //设置面包导航，主加载器请配置
        $bread = array(
            '0' => array('name' => '商家入驻'),
            '1' => array('name' => '添加商家'),
        );

        //获取页面所需信息
        //默认获取河南省及市区
        $provinces = $this->getProvinces();
        $cities = $this->getCities(16);
        $areas = $this->getAreas(152);
        $shopCate  = $this->getShopCate();


        $this->assign('provinces',$provinces);
        $this->assign('cities',$cities);
        $this->assign('areas',$areas);
        $this->assign('shopCate',$shopCate);
        $this->assign('breadhtml', $this->getBread($bread));
        $this->display();
    }

    /**
     * 保存信息
     */
    public function save(){
        $vip_id = I('post.vip_id/d');
        if(!I('post.su_id/d')) {   //不存在为新增;(仅新增时判断vip_id,编辑时不检验、不编辑vip_id)
            //如果传过来vip_id ,先判断vip_id 是否已经是商家
            if ($vip_id) {
                $vip_info = $this->isSupplier($vip_id, false); //返回数据，不是ajaxReturn
                if ($vip_info['status'] == 0) {
                    return $vip_info; //包含有错误信息
                }
                //如果vip表中，没有填写name/mobile，则更新之，如果填写了，则不更新
                if(!$vip_info['name'] || !$vip_info['mobile']){     //vip.name/vip.mobile其中一个没有填写的时候，做填充。
                    $vip_info['name'] ? '' : $vip_data['name'] = I('post.su_contact_name','','htmlspecialchars');
                    $vip_info['mobile'] ? '' : $vip_data['mobile'] = I('post.su_contact_phone','','htmlspecialchars');
                    M('vip')->where('id='.$vip_id)->save($vip_data);
                }

            }
        }

        $res = $this->save_supplier_info(); //返回状态码和ID
        if($res['status'] == 0){
            $this->ajaxReturn($res,'json');
        }

        $res = $this->save_supplier_store($res['supplier_id']);
        $this->ajaxReturn($res);
    }

    /**
     * 商家列表页
     */
    public function lists(){
        //设置面包导航，主加载器请配置
        $bread = array(
            '0' => array('name' => '管理商家'),
            '1' => array('name' => '商家列表'),
        );

        $model = M('supplier');
        $p = $_GET['p'] ? $_GET['p'] : 1;
        $psize = self::$CMS['set']['pagesize'] ? self::$CMS['set']['pagesize'] : 20;

        $map['supplier.status']=0;  //搜索条件，status=0

        //搜索条件
        if(isset($_GET['name']) && $_GET['name']){
            $name = I('get.name','','htmlspecialchars');
            $map['_string'] = 'vip.id = "'.$name.'" or supplier.name like "'.$name.'%"';
        }

        //获取列表及分页
        $fields = 'vip.id as vip_id,vip.score,vip.exp,vip.total_xxsub,vip.money,supplier.id as s_id,supplier.name,supplier.contact_phone,supplier.create_time,supplier.total_online_order,supplier.total_online_money,supplier.inviter_id,supplier.inviter_name,supplier_store.order_index';

        $lists_info = $model->field($fields)->where($map)
                    ->join('left join `vip` on supplier.id = vip.supplier_id')
                    ->join('left join `supplier_store` on supplier.id = supplier_store.supplier_id')
                    ->order('supplier_store.order_index desc')
                    ->page($p,$psize)->select();

        //echo $lists_info;
        $count = $model->join('left join `vip` on supplier.id = vip.supplier_id')
            ->where($map)->count();

        $this->getPage($count, $psize, 'App-loader', '商品管理', 'App-search');

        //var_dump($lists_info);
        $this->assign('lists_info',$lists_info);
        $this->assign('breadhtml', $this->getBread($bread));
        $this->display();
    }

    /**
     * 商家 详情/编辑 页
     */
    Public function view(){
        //设置面包导航，主加载器请配置
        $bread = array(
            '0' => array('name' => '管理商家'),
            '1' => array('name' => '商家列表'),
        );

        //获取商家信息
        $supplier_id = I('get.supplier_id/d');

        //因为字段有相同的，所以分开获取
        $supplier = M('supplier')->where('status=0')->where('id=%d',$supplier_id)->find();
        $supplier_store = M('supplier_store')->where('status=0')->where('supplier_id=%d',$supplier_id)->find();
        //var_dump($supplier);
        //var_dump($supplier_store);
        $vip_id = M('vip')->where('supplier_id='.$supplier['id'])->getField('id');

        //获取额外的页面所需信息
        $provinces = $this->getProvinces(); //省信息
        $shopCate  = $this->getShopCate();  //经营分类


        $this->assign('provinces',$provinces);
        $this->assign('shopCate',$shopCate);
        $this->assign('supplier',$supplier);
        $this->assign('vip_id',$vip_id);
        $this->assign('supplier_store',$supplier_store);
        //$this->assign('img_urls',$img_urls);
        $this->assign('breadhtml', $this->getBread($bread));
        $this->display(T('Supplier/view'));
    }

    /**
     * 删除商家
     */
    public function delete(){
        $supplier_id = I('get.supplier_id/d');
        $vip_id = I('get.vip_id/d');
        $via_id = I('get.via_id/d');

        //删除supplier 和supplier_store
        $supplier = M('supplier');
        $supplier->startTrans();
        //删除商家
        $res1 = $supplier->where('id='.$supplier_id)->setField('status',1);
        //删除店铺
        $res2 = M('supplier_store')->where('supplier_id='.$supplier_id)->setField('status',1);

        if($vip_id){    //如果vip_id不存在，则没有绑定，不用操作
            $res3 = M('vip')->where('id='.$vip_id)->setField('supplier_id',0);
            if(!$res3){
                $supplier->rollback();
                $this->ajaxReturn(array('status'=>0,'msg'=>'删除失败'));
            }
        }

        //推荐人的发展商家数 -1
        M()->execute('update `vip` set `total_supplier_number`=IF(`total_supplier_number`<1, 0, `total_supplier_number`-1) where id='.$via_id);

        //提交或回滚
        if($res1 && $res2){
            $supplier->commit();
            $this->ajaxReturn(array('status'=>1,'msg'=>'删除成功'));
        }else{
            $supplier->rollback();
            $this->ajaxReturn(array('status'=>0,'msg'=>'删除失败，请重试'));
        }
    }

    /**
     * 商家订单 --- 商盟订单
     */
    public function order(){
        //设置面包导航，主加载器请配置
        $bread = array(
            '0' => array('name' => '商城首页'),
            '1' => array('name' => '订单管理')
        );
        $this->assign('breadhtml', $this->getBread($bread));

        $supplier_id = I('get.supplier_id/d');
        $vip_id = I('get.vip_id/d');    //vip_id,可能会因为没有绑定而没有传过来
        $p = $_GET['p'] ? $_GET['p'] : 1;
        $psize = self::$CMS['set']['pagesize'] ? self::$CMS['set']['pagesize'] : 20;

        $model = M('supplier_order');
        $supplier_order = $model->page($p,$psize)->where('supplier_id='.$supplier_id)->order('order_time desc')->select();
        foreach($supplier_order as $k=>$v){
            $supplier_order[$k]['status']    = $this->getOrderStatus($v['status']);
            $supplier_order[$k]['pay_type'] = $v['pay_type'] ? $this->getPayType($v['pay_type']) : '----';
            $supplier_order[$k]['pay_time'] = $v['pay_time'] ? date('Y-m-d H:i:s',$v['pay_time']) : '----';
            $supplier_order[$k]['check_account_time'] = $v['check_account_time'] ? date('Y-m-d H:i:d',$v['check_account_time']) : '----';
        }
        $count = $model->where('supplier_id='.$supplier_id)->count();
        $this->getPage($count, $psize, 'App-loader', '商城订单', 'App-search');
        $this->assign('ids',array('supplier_id'=>$supplier_id,'vip_id'=>$vip_id));

        $this->assign('supplier_order',$supplier_order);
        $this->display();
    }
    /**
     * 商家订单 --- 消费订单
     */
    public function consumerOrder(){

        //$_GET['vip_id'] = 1;    //测试用id，测试完后需要删除

        $supplier_id = I('get.supplier_id/d');
        $vip_id = I('get.vip_id/d');    //vip_id,可能会因为没有绑定而没有传过来

        //设置面包导航，主加载器请配置
        $bread = array(
            '0' => array('name' => '商城首页'),
            '1' => array('name' => '订单管理')
        );
        $this->assign('breadhtml', $this->getBread($bread));

        if($vip_id){    //如果没有绑定，无法查找订单信息
            //绑定搜索条件与分页
            $m = M('Shop_order');
            $p = $_GET['p'] ? $_GET['p'] : 1;
            $psize = self::$CMS['set']['pagesize'] ? self::$CMS['set']['pagesize'] : 20;

            $map = array('vipid='.$vip_id);

            $order = $m->where($map)->page($p, $psize)->order('ctime desc')->select();
            $count = $m->where($map)->count();
            $this->getPage($count, $psize, 'App-loader', '商城订单', 'App-search');
            $this->assign('ids',array('supplier_id'=>$supplier_id,'vip_id'=>$vip_id));
            $this->assign('order',$order);
        }

        $this->display();

    }

    /**
     * 保存商家信息
     *      更新vip中的supplier_id和total_supplier_number(招商总数)
     */
    public function save_supplier_info(){
        //先判断是否已经开通了店铺（放在判断更新、新增之后）
        $vip_id = I('post.vip_id/d');

        // 2 、 整理数据并保存
        $supplier['name'] = I('post.su_name');
        $supplier['province'] = I('post.su_province');
        $supplier['city'] = I('post.su_city');
        $supplier['area'] = I('post.su_area');
        $supplier['address'] = I('post.su_address');
        $supplier['contract_code'] = I('post.su_contract_code');

        $supplier['contact_name'] = I('post.su_contact_name');
        $supplier['contact_phone'] = I('post.su_contact_phone');
        $supplier['contact_sex'] = I('post.su_sex');
        $supplier['contact_idcard'] = I('post.su_contact_idcard');
        $supplier['contact_birthday'] = I('post.su_contact_birthday');
        $supplier['alipay_code'] = I('post.su_alipay_code');
        $supplier['bank_name'] = I('post.su_bank_name');
        $supplier['sub_bank_name'] = I('post.su_sub_bank_name');
        $supplier['cardholder'] = I('post.su_cardholder');
        $supplier['bank_code'] = I('post.su_bank_code');

        $supplier_model = D('Supplier');
        // 3 、 做数据验证
        if(!$supplier_model->create($supplier)){
            return array('status'=>0,'msg'=>$supplier_model->getError());
        }else{
            // 4 、 验证通过，判断编辑/新增
                // 4.1 存在su_id为编辑
            if($supplier_id = I('post.su_id/d')){

                $supplier_model->update_time = time();
                $supplier_model->updator = $_SESSION['CMS']['uid'];

                $supplier_model->where('id='.$supplier_id)->save();

                return array('status'=>1,'supplier_id'=>$supplier_id);   //返回状态码和supplier的id值
            }else{
                // 4.2 、新增时 ：填写了vip_id则验证是否已经开通店铺，没有开通，继续向下执行(移到外部)

                // 1 、新增时：判断 推荐人ID是否为团长
                $supplier_model->inviter_id = I('post.su_inviter_id/d');
                $vip_info = M('vip')->field('is_tuanzhang,name,nickname,mobile')->where('id='.$supplier_model->inviter_id)->find();

                if(!$vip_info['is_tuanzhang']){ //为零，或为假
                    return array('status'=>0,'msg'=>'推荐人ID不是团长，无法推荐开通店铺');
                }else{
                    $supplier_model->inviter_name = $vip_info['nickname'];
                    $supplier_model->inviter_phone = $vip_info['mobile'];
                }

                /*if($vip_id){
                    $isSupplier = $this->isSupplier($vip_id,false); //返回数据，不是ajaxReturn
                    if($isSupplier['status'] == 0){
                        return $isSupplier; //包含有错误信息
                    }
                }*/

                $supplier_model->creator = $_SESSION['CMS']['uid'];
                $supplier_model->create_time = time();
                //新增操作

                $supplier_id = $supplier_model->add();   //返回Supplier_id!!!第二步要用到
                //新增时，更新inviter的total_supplier_number(招商总数)
                M('vip')->where('id='.I('post.su_inviter_id/d'))->setInc('total_supplier_number');
                //新增时，如果填写了vip_id，则更新vip表中的supplier_id
                if($vip_id){
                    M('vip')->where('id='.$vip_id)->save(array('supplier_id'=>$supplier_id));
                }
                return array('status'=>1,'supplier_id'=>$supplier_id);  //返回刚才的supplier_id，供下一步使用
            }
        }
    }
    /**
     * 保存商家商店信息
     */
    public function save_supplier_store($supplier_id){
        //$supplier_store['supplier_id'] = $supplier_id;

        // 1 、 整理数据
        $supplier_store['name'] = I('post.st_name');
        $supplier_store['province'] = I('post.st_province');
        $supplier_store['city'] = I('post.st_city');
        $supplier_store['area'] = I('post.st_area');
        $supplier_store['address'] = I('post.st_address');
        $supplier_store['lat'] = I('post.st_lat/f');
        $supplier_store['lng'] = I('post.st_lng/f');
        $supplier_store['caption'] = I('post.st_caption');
        $supplier_store['phone'] = I('post.st_phone','','htmlspecialchars');
        $supplier_store['pics'] = I('post.st_pics');
        $supplier_store['thumb'] = I('post.thumb','','htmlspecialchars');
        $supplier_store['category_id'] = I('post.st_category_id/d');
        $supplier_store['order_index'] = I('post.st_order_index/d');
        $supplier_store['business_hours'] = I('post.st_business_hours','','htmlspecialchars');
        $supplier_store['desc'] = I('post.st_desc','','htmlspecialchars');
        $supplier_store['create_time'] = time();
        $supplier_store['creator'] = $_SESSION['CMS']['uid'];

        // 2 、 判断新增、更新
            // 2.1 存在st_id，为更新
        if($st_id = I('post.st_id/d')){    //如果传过来了st_id，则为更新
            $res = M('supplier_store')->where('id='.$st_id)->save($supplier_store);
        }else{  // 2.2 否则为新增
            //新增时，如果没有绑定会员ID，则status=2；（1:删除 0：正常）
            $vip_id = I('post.vip_id/d');
            $supplier_store['status'] = $vip_id? 0 : 2;

            $supplier_store['supplier_id'] = $supplier_id;
            $res = M('supplier_store')->add($supplier_store);
        }

        if($res){
            return array('status'=>1,'msg'=>'保存成功');
        }else{
            return array('status'=>0,'msg'=>'店铺信息保存失败');
        }
    }

    /**
     * 更改vip对应的supplier_id
     * 1、查看new_vip_id是否已经开通店铺，已经开通则返回false
     * 2、根据supplier_id找到old_vip_id
     *      ①、删除old_vip_id对应的supplier_id
     * 3、更新new_vip_id中的supplier_id
     */
    public function changeVipId(){
        $supplier_id = I('get.supplier_id/d');
        $new_vip_id = I('get.new_vip_id/d');
        // 0、首先必须传递了new_vip_id的信息
        if(!$supplier_id || !$new_vip_id ){
            $this->ajaxReturn(array('status'=>0,'msg'=>'参数错误'));
        }
        // 1 、先查看此新的vip_id是否已经开通了店铺
        $isSupplier = $this->isSupplier($new_vip_id,false);
        if($isSupplier['status'] == 0){
            $this->ajaxReturn($isSupplier); //如果已经开通了店铺，则返回错误信息
        }

        // 2、 如果supplier_id已经属于某一个人，删除之
        M('vip')->where('supplier_id='.$supplier_id)->save(array('supplier_id'=>0));
        //3、supplier_store的status 应为正常
        M('supplier_store')->where('supplier_id='.$supplier_id)->save(array('status'=>0));

        // 4、把supplier_id插入到新的vip中
        $res3 = M('vip')->where('id='.$new_vip_id)->save(array('supplier_id'=>$supplier_id));
        if($res3){
            $this->ajaxReturn(array('status'=>1,'msg'=>'更新成功'));
        }else{
            $this->ajaxReturn(array('status'=>0,'msg'=>'更新失败'));
        }
    }
    /**
     * 更改推荐人ID
     *      1、把老的推荐人的total_supplier_number -1
     *      2、把新的 +1
     */
    public function changeInviterId(){
        //$res2 = M()->execute('update `vip` set `supplier_id`=0 and `total_supplier_number` = IF(`total_supplier_number`<1, 0, `total_supplier_number`-1) where id =');
        $id = I('post.su_id/d');
        $new_via_id = I('post.new_via_id/d');
        $old_via_id = I('post.old_via_id/d');
        if(!$new_via_id || !$id)
            $this->ajaxReturn(array('status'=>0,'msg'=>'参数不完整，修改失败'));
        // 1、判断是否为团长
        $via_info = $this->isTuanZhang($new_via_id,0);
        // 2.1、不为团长返回错误信息
        if($via_info['status'] == 0){
            $this->ajaxReturn($via_info);
        }else{
            // 2.2、是团长，更新团长信息
            $data['inviter_id'] = $new_via_id;
            $data['inviter_name'] = $via_info['vip_info']['name'];
            $data['inviter_phone']= $via_info['vip_info']['mobile'];
            $res = M('supplier')->where('id='.$id)->save($data);
            if($res){
                //新团长的(total_supplier_number)招商数+1；原团长-1；
                M()->execute('update `vip` set `total_supplier_number`=IF(`total_supplier_number`<1, 0, `total_supplier_number`-1) where id='.$old_via_id);
                M()->execute('update `vip` set `total_supplier_number`=`total_supplier_number`+1 where id='.$new_via_id);
                $this->ajaxReturn(array('status'=>1,'msg'=>'更新成功'));
            }else{
                $this->ajaxReturn(array('status'=>0,'msg'=>'更新失败，请重试'));
            }
        }
    }
    /**
     * 判断此VIP_ID是否已经开通商店
     */
    public function isSupplier($vip_id = 0,$returnJson = true){

        //用IS_AJAX无法兼容其他函数的调用,做适当更改
        $vip_id = $vip_id ? $vip_id : I('get.vip_id/d');
        $vip_info = M('vip')->where('id='.$vip_id)->field('supplier_id,headimgurl,nickname,name,mobile')->find();

        if(!$vip_info){
            $this->ajaxReturn(array('status'=>0,'msg'=>'ID不存在,请重新确认后再填写'));
        }

        if($vip_info['supplier_id'] == 0){  //如果没有开通，顺带把一部分vip信息返回
            $info = array('status'=>1,'msg'=>'此ID会员未开通店铺，可以开通','vip_info'=>$vip_info);
            if($returnJson){
                $this->ajaxReturn($info,'json');
            }else{
                return $info;
            }
        }else {
            $info = array('status' => 0, 'msg' => '此ID会员已经开通了店铺，无法再次开通!');
            if ($returnJson) {
                $this->ajaxReturn($info,'json');
            } else {
                return $info;
            }
        }
    }

    /**
     * 判断inviter_id是否为团长
     * $type 1:前台请求，直接返回ajax信息
     *      0：控制器内访问，返回数组
     */
    public function isTuanZhang($inviter_id = null,$type='1'){
        $id = $inviter_id ? $inviter_id : I('get.inviter_id/d');
        $vip_info = M('vip')->where('id='.$id)->field('mobile,headimgurl,nickname,name,is_tuanzhang')->find();
        if(!$vip_info){
            if($type){
                $this->ajaxReturn(array('status'=>0,'msg'=>'此ID不不存在'));
            }else{
                return array('status'=>1,'msg'=>'此ID不存在');
            }
        }

        if($vip_info['is_tuanzhang'] == 1){ //是团长
            //return true;
            $info = array('status'=>1,'msg'=>'此人是团长，可以作为推荐人','vip_info'=>$vip_info);
            if($type){
                $this->ajaxReturn($info);
            }else{
                return $info;
            }
        }else{
            //return false;
            $info = array('status'=>0,'msg'=>'此人不是团长，无法作为推荐人');
            if($type){
                $this->ajaxReturn($info);
            }else{
                return $info;
            }
        }
    }

    /**
     * 开通学校管理
     */
    public function schoolManage(){
        if(IS_POST){
            $data['city_id'] = I('post.city_id/d');
            $data['school_name'] = trim(I('post.school_name/s'));
            $data['role_1_id'] = I('post.role_1_id/d'); //总监
            $data['role_2_id'] = I('post.role_2_id/d'); //via
            //总监和VIA不能是同一人。
            if($data['role_1_id'] == $data['role_2_id']){
                $this->ajaxReturn(array('status'=>0,'msg'=>'总监和VIA不可是同一人!'));
            }
            $school = M('location_school');
            $vip = M('vip');
            //判断role_1_id，role_2_id是否已被注册。
            $isvia = $school->where(array('role_2_id'=>$data['role_1_id']))->find();
            $revia = $school->where(array('role_1_id|role_2_id'=>$data['role_2_id']))->find();
            if($isvia){
                $this->ajaxReturn(array('status'=>0,'msg'=>"此总监ID已被设置！"));
            }
            if($revia){
                $this->ajaxReturn(array('status'=>0,'msg'=>"此VIA-ID已被设置！"));
            }
            //设置总监vip用户三级全为0。
            $vip->startTrans();
            $setZj = $vip->where(array('id'=>$data['role_1_id']))->setField(array('role'=>'1','role_1_id'=>0,'role_2_id'=>0,'role_3_id'=>0));
            $setVia = $vip->where(array('id'=>$data['role_2_id']))->setField(array('role'=>'2','role_1_id'=>$data['role_1_id'],'role_2_id'=>0,'role_3_id'=>0));
            $rel = $school->add($data);
            if(false !== $setZj && false !== $setVia && $rel){
                $vip->commit();
                $this->ajaxReturn(array('status'=>1,'msg'=>"开通成功!"));
            }
                $vip->rollback();
                $this->ajaxReturn(array('status'=>0,'msg'=>'设置失败，请重试'));
        }
        //设置面包导航，主加载器请配置
        $bread = array(
            '0' => array('name' => '商家入驻'),
            '1' => array('name' => '添加商家'),
        );
        $this->assign('breadhtml', $this->getBread($bread));

        $provinces = $this->getProvinces('id,name');
        $cities = $this->getCities(16);      //默认获取河南省，及以下市区

        $p = $_GET['p'] ? $_GET['p'] : 1;
        $psize = self::$CMS['set']['pagesize'] ? self::$CMS['set']['pagesize'] : 20;

        $openSchool = M('location_school')
            ->join('vip as role_1 on location_school.role_1_id = role_1.id')
            ->join('vip as role_2 on location_school.role_2_id = role_2.id')
            ->field('location_school.*,role_1.id as zj_id,role_1.name as zj_name,role_1.nickname as zj_nickname,role_2.id as via_id,role_2.nickname as via_nickname,role_2.name as via_name')
            ->page($p, $psize)->select();
        $count = M('location_school')->count();
        $this->getPage($count, $psize, 'App-loader', '商盟订单', 'App-search');

        $this->assign('provinces',$provinces);
        $this->assign('cities',$cities);
        $this->assign('openSchool',$openSchool);

        $this->display();
    }

    /*
     * 设置学校
     */
    public function setSchool(){
        $m = M('location_school');
        $id = I('id');
        $cache = $m->join('location_city on location_school.city_id = location_city.id')->where('location_school.id='.$id)->find();
        $p = $_GET['p'] ? $_GET['p'] : 1;
        $psize = self::$CMS['set']['pagesize'] ? self::$CMS['set']['pagesize'] : 20;
        $tgInfo = M('vip')->where(array('role_2_id'=>$cache['role_2_id'],'role'=>'3'))->page($p, $psize)->select();
        $count = M('vip')->where(array('role_2_id'=>$cache['role_2_id'],'role'=>'3'))->count();
        $this->getPage($count, $psize, 'App-loader', '推广人员列表', 'App-search');
        $this->assign('tgInfo',$tgInfo);
        $this->assign('cache',$cache);
        if(IS_POST) {
            $data['id'] = $id;
            $data['school_name'] = trim(I('post.school_name/s'));
            $data['role_1_id'] = I('post.role_1_id/d');
            $data['role_2_id'] = I('post.role_2_id/d');
            //总监和VIA不能是同一人。
            if($data['role_1_id'] == $data['role_2_id']){
                $this->ajaxReturn(array('status'=>0,'msg'=>'总监和VIA不可是同一人!'));
            }
            //判断role_1_id，role_2_id是否已被注册。
            $isvia = $m->where(array('role_2_id'=>$data['role_1_id'],'id'=>array('neq',$id),'is_open'=>1))->find();
            $revia = $m->where(array('role_1_id|role_2_id'=>$data['role_2_id'],'id'=>array('neq',$id),'is_open'=>1))->find();
            if($isvia){
                $this->ajaxReturn(array('status'=>0,'msg'=>"此总监ID已被设置！"));
            }
            if($revia){
                $this->ajaxReturn(array('status'=>0,'msg'=>"此VIA-ID已被设置！"));
            }
            $vip = M('vip');
            $vip->startTrans();
            $role_1 = $vip->where(array('id'=>$data['role_1_id']))->save(array('role'=>'1','role_1_id'=>'0','role_2_id'=>'0','role_3_id'=>'0'));
            $role_2 = $vip->where(array('id'=>$data['role_2_id']))->save(array('role'=>'2','role_1_id'=>$data['role_1_id'],'role_2_id'=>'0','role_3_id'=>'0'));
            $sta1 = $vip->where(array('role_1_id'=>$cache['role_1_id'],'role'=>'0'))->save(array('role_1_id'=>$data['role_1_id']));
            $sta2 = $vip->where(array('school_id'=>$data['id'],'role'=>'0'))->save(array('role_2_id'=>$data['role_2_id']));
            $rel = $m->save($data);
            if(false !== $role_1 && false !== $role_2 && false !== $sta1 && false !== $sta2 && false !== $rel){
                $vip->commit();
                $info['status'] = 1 ;
                $info['msg'] = "设置成功!";
                $this->ajaxReturn($info);
            }else{
                $vip->rollback();
                $info['status'] = 0;
                $info['msg'] = "服务器繁忙，请重试!";
                $this->ajaxReturn($info);
            }
            $info['status'] = 0 ;
            $info['msg'] = "服务器繁忙，请重试!";
            $this->ajaxReturn($info);
        }
        $this->display();
    }

    function closeTg(){
        $id = I('get.id');
        $vip = M('vip');
        $setV = $vip->where(array('id'=>$id))->setField(array('role'=>'0'));
        $setP = $vip->where(array('role_3_id'=>$id))->setField(array('role_3_id'=>'0'));
        if(false !== $setV && false !== $setP){
            $info['status'] = 1 ;
            $info['msg'] = "取消成功!";
            $this->ajaxReturn($info);
        }
        $info['status'] = 0 ;
        $info['msg'] = "服务器繁忙，请重试!";
        $this->ajaxReturn($info);
    }
    /**
     * 学校关闭
     */
    public function editSchool(){
        $city_id = I('get.School_id/d');
        $isClose = I('get.isClose/d');
        $school = M('location_school');
        if($isClose == 1){
            if($city_id){
                $res = $school->where('id=%d',$city_id)->setField('is_open',0);
                if($res){
                    $this->ajaxReturn(array('status'=>1,'msg'=>'关闭成功'));
                }
            }
            $this->ajaxReturn(array('status'=>0,'msg'=>'关闭失败，请重试!'));
        }else{
            if($city_id){
                $res = $school->where('id=%d',$city_id)->setField('is_open',1);
                if($res){
                    $this->ajaxReturn(array('status'=>1,'msg'=>'开通成功'));
                }
            }
            $this->ajaxReturn(array('status'=>0,'msg'=>'开通失败，请重试!'));
        }
    }

    public function setTg(){
        $id = I('post.id/d');
        $tg_id = I('post.tg_id/d');
        $zj_id = I('post.zj_id/d');
        $vai_id = I('post.vai_id/d');
        $vip = M('vip');
        //检测所填推广是否已是推广人员。
        $v = $vip->where(array('id'=>$tg_id))->field('role')->find();
        if($v['role'] > 0){
            $info['status'] = 0;
            $info['msg'] = "此会员已是推广人员。";
            $this->ajaxReturn($info);
        }
        //查找所属学校的总监，VIA ID
        $rel = $vip->where(array('id'=>$tg_id,'role'=>array('eq',0)))->setField(array('role'=>'3','role_1_id'=>$zj_id,'role_2_id'=>$vai_id));
        if(false !== $rel){
            $info['status'] = 1;
            $info['msg'] = "设置成功!";
            $this->ajaxReturn($info);
        }

            $info['status'] = 0;
            $info['msg'] = "添加失败，请重试!";
            $this->ajaxReturn($info);
    }

    /**
     * 商盟开通城市管理
     *  城市开通
     */
    public function cityManage(){
        $city_id = I('get.city_id/d');
        //如果city_id存在，则为开通操作
            //开通后，接着运行页面展示，正好把数据展示出来
        if($city_id){
            M('location_city')->where('id=%d',$city_id)->setField('is_open',1);
        }

        //设置面包导航，主加载器请配置
        $bread = array(
            '0' => array('name' => '商家入驻'),
            '1' => array('name' => '添加商家'),
        );
        $this->assign('breadhtml', $this->getBread($bread));

        $provinces = $this->getProvinces('id,name');
        $cities = $this->getCities(16);      //默认获取河南省，及以下市区

        $p = $_GET['p'] ? $_GET['p'] : 1;
        $psize = self::$CMS['set']['pagesize'] ? self::$CMS['set']['pagesize'] : 20;

        $openCities = M('location_city')->where('is_open=1')->page($p, $psize)->select();
        $count = M('location_city')->where('is_open=1')->count();
        $this->getPage($count, $psize, 'App-loader', '商盟订单', 'App-search');

        $this->assign('provinces',$provinces);
        $this->assign('cities',$cities);
        $this->assign('openCities',$openCities);

        $this->display();
    }

    /**
     * 城市关闭
     */
    public function closeCity(){
        $city_id = I('get.city_id/d');
        if($city_id){
            $res = M('location_city')->where('id=%d',$city_id)->setField('is_open',0);

            if($res)
            $this->ajaxReturn(array('status'=>1,'msg'=>'关闭成功'));
        }
        $this->ajaxReturn(array('status'=>0,'msg'=>'关闭失败，请重试'));
    }

    /**
     * 商盟管理 -- 订单管理  主页（全部订单）
     * 即使店铺被删除，订单也展示出来，以订单为主
     */
    public function orderManage(){
        //设置面包导航，主加载器请配置
        $bread = array(
            '0' => array('name' => '商盟管理'),
            '1' => array('name' => '订单管理')
        );
        $this->assign('breadhtml', $this->getBread($bread));

        //$model = M('supplier_order');
        //order_status ： 0、已下单(待付款) 1、待核对 2 已完成 3 已关闭  4、全部订单   9、当天订单记录

        // 1    组装基本sql
        /*$model = M('supplier_order')->field('supplier_order.*,supplier.name as su_name')
                ->join('left join supplier on supplier_order.supplier_id = supplier.id');*/

        // 2.1  订单状态过滤
            // 2.1.1 订单排序
            $orderBy = 'id desc';   //默认根据id排序（等同下单时间）
        if(isset($_GET['order_status'])){
            switch($_GET['order_status']){
                case 0 : //已下单  代付款
                    $map['supplier_order.status'] = 0;
                    break;
                case 1 :    //待核对
                    $map['supplier_order.status'] = 1;
                    break;
                case 2: //已完成订单，根据pay_time desc排序
                    $map['supplier_order.status'] = 2;
                    $orderBy = 'pay_time desc';
                    break;
                case 3: //已关闭，
                    $map['supplier_order.status'] = 3;
                    $orderBy = 'close_time desc';
                    break;
                case 9 :    //当天订单记录
                    $start = strtotime(date('Y-m-d'));
                    $end = time();
                    $map['supplier_order.order_time'] = array('between',array($start,$end));
            }
        }

        // 2.2  搜索字段过滤
        if(isset($_GET['name'])){
            $name = I('get.name','','htmlspecialchars');
            if($name){
                $map['_string'] = 'supplier_order.order_code like "'.$name.'%" or supplier_order.vip_seller_id="'.$name.'"';
            }
        }

        //  3   分页
        $p = $_GET['p'] ? $_GET['p'] : 1;
        $psize = self::$CMS['set']['pagesize'] ? self::$CMS['set']['pagesize'] : 20;

        // 4    获取数据
        //$map['supplier.status'] = 0;
            //count时，where条件怎么都加不上去，用这种方法解决
        $orders = M('supplier_order')->field('supplier_order.*,supplier.name as su_name')
            ->join('left join supplier on supplier_order.supplier_id = supplier.id')
            ->where($map)->order($orderBy)->page($p,$psize)->select();

        $count = M('supplier_order')->where($map)->count();

        $this->getPage($count, $psize, 'App-loader', '商盟订单', 'App-search','');

        $this->assign('status',$_GET['order_status']);
        $this->assign('orders',$orders);
        $this->display();
    }

    /**
     * 核对订单
     */
    Public function orderVerify(){
        $order_id = I('post.order_id/d');
        $order['tn'] = I('post.tn','','htmlspecialchars');
        if(!$order_id || !$order['tn']){
            $this->ajaxReturn(array('status'=>0,'msg'=>'未输入流水号!或数据不完整'));
        }
        $order['status']    = 2;    //订单状态已完成
        $order['is_pay']    = 1;    //已支付
        $order['pay_time']  = time();
        $order['end_time']  = time();
        $res = M('supplier_order')->where('id='.$order_id)->save($order);

        if ($res) {
            $commission = D('Commission');
            $res1 = $commission->process($order_id, self::$CMS['shopset'],'admin',1);

            $this->addSupplierLog($order_id,'核对成功',5,'offline');
            $this->ajaxReturn(array('status'=>1,'msg'=>'核对成功'));
        } else {
            $this->addSupplierLog($order_id,'核对失败',0,'offline',true);   //失败只记录后台系统日志
            $this->ajaxReturn(array('status'=>0,'msg'=>'核对失败,请重试'));
        }
    }

    /**
     * 添加日志
     */
    private function addSupplierLog($oid,$msg,$type='',$paytype,$is_sys = false){ //$is_sys:是否只记录系统之日志
        $data['oid'] = $oid;
        $data['msg'] = $msg;
        $data['type'] = $type;
        $data['ctime'] = time();
        if(!$is_sys){   // is_sys，是否只记录系统日志
            $log = M('supplier_order_log');
            $log->add($data);
        }

        $data['paytype'] = $paytype;
        $syslog = M('supplier_order_syslog');
        $syslog->add($data);
    }
    /**
     * 创建订单 页面
     */
    public function orderManage_createOrder(){
        //设置面包导航，主加载器请配置
        $bread = array(
            '0' => array('name' => '商盟管理'),
            '1' => array('name' => '订单管理'),
            '2' => array('name' => '创建订单'),
        );
        $this->assign('breadhtml', $this->getBread($bread));
        //获取打款比率
        $discount = M('shop_set')->where('id=1')->getField('settlement_rate');
        $ratio = round((10-$discount)/10,3);
        $this->assign('ratio',$ratio);
        $this->display();
    }

    /**
     * 创建订单保存
     */
    public function createOrderSave(){
        $data['vip_seller_id'] = I('post.supplier_id/d');   //对外的商家ID即为vipID
        $data['vip_buyer_id'] = I('post.vip_buyer_id/d');
        $data['pay_price'] = I('post.pay_price','','float');
        $data['memo'] = I('post.memo','','htmlspecialchars');
        $tn = I('post.tn','','htmlspecialchars');
        //新添加是否已支付/打款
        $pay = I('post.pay/d');
        if($data['pay_price']<=0){
            $this->ajaxReturn(array('status'=>0,'msg'=>'应打款金额不能小于0'));
        }
        if($pay == 1){
            if($tn == ''){
                $this->ajaxReturn(array('status'=>0,'msg'=>'请填写打款流水号'));
            }
            $data['tn'] = $tn;
        }

        //若信息存在则获取与之有关的相关数据
        if(!$data['vip_seller_id'] || !$data['vip_buyer_id'] || !$data['pay_price']){
            $this->ajaxReturn(array('status'=>0,'msg'=>'请填写完整后提交'));
        }

        //获取购买者用户信息
        $vip_info = M('vip')->field('nickname,mobile,openid')->where('id='.$data['vip_buyer_id'])->find();

        if(!$vip_info){
            $this->ajaxReturn(array('status'=>0,'msg'=>'查询不到购买者信息'));
        }
        //获取卖家用户信息

        $supplier_info = $this->getSupplierInfo($data['vip_seller_id'],1);
        if($supplier_info['status'] == 0){
            $this->ajaxReturn($supplier_info);
        }

        //分为已到账、未到账两种状态
        // 1. 公共参数
        $data['order_code']     = getOrderCode($_SESSION['CMS']['uid'],1);              //订单号
        $data['supplier_id']  = $supplier_info['info']['su_id'];                        //商家ID
        $data['store_id'] = $supplier_info['info']['st_id'];                            //店铺ID
        $data['total_price'] = $this->createOrder_getTotalPrice($data['pay_price'],1);    //支付价格
        $data['vip_buyer_name']     = $vip_info['nickname'];                       //购买者昵称
        $data['vip_buyer_mobile']   = $vip_info['mobile'];                         //购买者电话
        $data['vip_buyer_openid']   = $vip_info['openid'];                         //购买者openid
        $data['creator_type'] = 2;                                                 //平台创建
        $data['creator_id'] = $_SESSION['CMS']['uid'];                             //创建订单用户ID

        $data['is_payforother']     = 1;                                           //是待支付
        $data['pay_type']  = 'offline';
        $data['is_pay'] = 1;    //已支付
        $data['pay_time'] = time();     //支付时间
        $data['order_time'] = time();

        if($pay == 1){              //1：表示已经付过款
            // 2.1 已到账/已支付
            $data['status'] = 2;            //已完成
            $data['is_need_return'] = 0;    //是否需要对账：否！！！重要
            $data['end_time'] = time();     //完成时间
            //is_check_account 没有用，默认即可

            //日志部分
            $type = 5;  //订单已完成
            $msg  = '订单已完成';

        }else if($pay == 0){        //0：表示还没有到账
            // 2.2 未到账/未支付
            $data['status'] = 0;                //订单状态  已下单
            $data['is_need_return'] = 1;        //需要对账
            $data['is_check_account'] = 0;      //没有对过账
            //日志部分
            $type = 1;  //新增订单
            $msg  = '订单已支付';
        }

        $id = M('supplier_order')->add($data);
        if($id){
            //如果已经如果款则执行
            if($pay){
                $commission = D('Commission');
                $commission->process($id, self::$CMS['shopset'],'admin',1);
            }

            $this->addSupplierLog($id,'创建订单',$type,'offline');        //添加日志:已下单
            $this->addSupplierLog($id,$msg,$type,'offline');             //添加日志：已支付
            $this->ajaxReturn(array('status'=>1,'msg'=>'添加成功'));
        }else{
            $this->ajaxReturn(array('status'=>0,'msg'=>'添加失败，请重试'));
        }
    }

    /**
     * 商盟订单详情
     */
    public function orderDetail(){
        $id = I('get.id/d');    //order_id
        $info = M('supplier_order')->alias('s')->field('s.*,supplier.name as su_name,vip.nickname as vip_nickname')
                ->join('left join `supplier` on supplier.id=s.supplier_id')
                ->join('left join `vip` on vip.id=s.vip_buyer_id')
                ->where('s.id='.$id)
                ->find();
        $process = M('supplier_order_syslog')->where('oid='.$id)->order('ctime asc')->select();

        $info['pay_type'] = $this->getPayType($info['pay_type']);
        $info['order_time'] = date('Y-m-d H:i:s',$info['order_time']);
        $info['status_name'] = $this->getOrderStatus($info['status']);

        $this->assign('process',$process);
        $this->assign('info',$info);
        $this->display();
    }

    /**
     * 关闭商盟订单
     */
    public function closeOrder(){
        $order_id = I('get.order_id/d');
        //目前只关闭代付款订单
        $res = M('supplier_order')->where('status=0 and id='.$order_id)->save(array('status'=>3,'close_time'=>time()));

        if($res){
            $this->addSupplierLog($order_id,'关闭订单成功',6,'');
            $this->ajaxReturn(array('status'=>1,'msg'=>'关闭成功'));
        }else{
            $this->addSupplierLog($order_id,'关闭订单失败',6,'',true);    //失败，只记录后台系统日志
            $this->ajaxReturn(array('status'=>0,'msg'=>"关闭失败"));
        }
    }

    /**
     * 创建订单时获取 需要支付的价格
     */
    public function createOrder_getTotalPrice($payPrice = null,$type=0){ //0:ajax调用 1：类内调用
        if(!$type){ //若ajax调用
            $payPrice = I('get.payPrice','','float');
        }
        $discount = M('shop_set')->where('id=1')->getField('settlement_rate');
        $ratio = round((10-$discount)/10,3);

        if($type){  //类内调用：直接返回数据
            return round($payPrice/$ratio,2);
        }else{  //ajax调用，ajaxReturn
            $this->ajaxReturn(array('status'=>1,'msg'=>round($payPrice/$ratio,2)));
        }
    }
    /**
     * 获取数据的Excel表格
     */
    public function getExcel(){
        $status = isset($_GET['order_status']) ? I('get.order_status/d') : 4;   //如果没有传过来status,则下载全部订单
        $model = M('supplier_order')->field('supplier_order.*,supplier.name as su_name')->join('left join supplier on supplier_order.supplier_id = supplier.id');
        if($status == 4){   //全部
            $orders = $model->select();
        }else if($status == 9){ //当天
            $start = strtotime(date('Y-m-d'));
            $end = time();
            $map['supplier_order.order_time'] = array('between',array($start,$end));
            $orders = $model->where($map)->select();
        } else {
            $orders = $model->where('supplier_order.status='.$status)->select();
        }
        //订单状态、支付方式
        $statusName = array(0=>'待支付',1=>'待核对',2=>'已完成',3=>'已关闭');
        $payName    = array('money'=>'余额支付','alipayApp'=>'支付宝手机客户端','wxpay'=>'微信支付','offline'=>'线下打款');
        $fileNameArr= array(0=>'待支付订单',1=>'待核对订单',2=>'已完成订单',3=>'已关闭订单',4=>'全部订单',9=>'当天订单');

        $i = 0;
        while($order = array_shift($orders)){
            //var_dump($order);
            //echo '<hr/>';
            $new_order[$i]['order_code']    = $order['order_code'];     //订单号
            $new_order[$i]['su_id']       = $order['vip_seller_id'];        //商家ID
            $new_order[$i]['su_name']       = $order['su_name'];        //商家名称
            $new_order[$i]['buyer_id']    = $order['vip_buyer_id']; //买家id
            $new_order[$i]['buyer_name']    = $order['vip_buyer_name']; //买家昵称
            $new_order[$i]['total_price']   = $order['total_price'];    //消费金额
            $new_order[$i]['status']        = $statusName[$order['status']];    //订单状态
            $new_order[$i]['pay_type']      = $payName[$order['pay_type']];    //支付方式
            $new_order[$i]['pay_price']     = $order['pay_price'];      //支付金额
            $new_order[$i]['order_time']    = date('Y-m-d H:i:s',$order['order_time']);     //下单时间
            $new_order[$i]['pay_time']      = $order['pay_time']?date('Y-m-d H:i:s',$order['pay_time']):''; //支付时间
            $new_order[$i]['close_time']    = $order['close_time']?date('Y-m-d H:i:s',$order['close_time']):'';//关闭时间
            $new_order[$i]['check_account'] = $order['is_check_account']?'是':'否';       //是否对过账
            $new_order[$i]['check_time']    = $order['check_account_time']?date('Y-m-d H:i:s',$order['check_account_time']):'';//对账时间
            $new_order[$i]['tn']            = $order['tn'];             //银行/支付宝等流水号
            //var_dump($new_order);
            //exit;
            $i++;
        }
        $fileName = '商盟订单--'.$fileNameArr[$status].'--'.date('Y-m-d',time());

        $head = array('order_code'=>'订单号','su_id'=>'商家ID','su_name'=>'商家名称','buyer_id'=>'买家ID','buyer_name'=>'买家昵称','total_price'=>'消费金额','status'=>'订单状态','pay_type'=>'支付方式','pay_price'=>'支付金额',
            'order_time'=>'下单时间','pay_time'=>'支付时间','close_time'=>'关闭时间','check_account'=>'是否对过账','check_time'=>'对账时间','tn'=>'银行/支付宝等流水号');

        export_excel($new_order,$head,$fileName);
    }

    //获取基本的vip信息
    public function getVipInfo(){
        $id = I('get.vip_id/d');
        $info = M('vip')->field('mobile,nickname,headimgurl')->where('id='.$id)->find();
        if($info){
            $this->ajaxReturn(array('status'=>1,'msg'=>'','info'=>$info));
        }else{
            $this->ajaxReturn(array('status'=>0,'msg'=>'此用户不存在'));
        }
    }
    //获取基本的商家信息
    public function getSupplierInfo($supplier_id=null,$type = null){  //为真实为内部调用，直接return数据
        //商家ID,即为vip_id (supplier.id对外不可见)
        $vip_id = $supplier_id?$supplier_id:I('get.supplier_id/d');
        $real_supplier_id = M('vip')->where('id='.$vip_id)->getField('supplier_id');
        if($real_supplier_id === null){
            $this->ajaxReturn(array('status'=>0,'msg'=>'此ID信息不存在'));
        }
        if($real_supplier_id == 0){
            $this->ajaxReturn(array('status'=>0,'msg'=>'此ID未开通商铺'));
        }
        $info = M()->query('select su.id as su_id, su.name as su_name,su.contact_phone,su.contact_name,st.name as st_name,st.id as st_id
                from supplier su join supplier_store st on su.id=st.supplier_id where su.id='.$real_supplier_id.' limit 1');
        if($info){
            if($type){  //如果为内部调用
                return array('status'=>1,'info'=>$info[0]);
            }else{
                $this->ajaxReturn(array('status'=>1,'msg'=>'','info'=>$info[0]));
            }

        }else{
            if($type){
                return array('status'=>0,'msg'=>'未查到此商家信息');
            }else {
                $this->ajaxReturn(array('status'=>0,'msg'=>'未查到此商家信息'));
            }
        }
    }
    //获取省信息
    public function getProvinces($fields='*'){
        return D('Location')->getProvinces($fields);
    }

    //根据省ID获取市信息
    public function AjaxGetCities(){
        $cities = $this->getCities();
        $this->ajaxReturn($cities,'json');
    }
    public function getCities($pid = null){
        $pid = $pid ? $pid : I('get.pid');
        $cities = D('Location')->getCities($pid);
        return $cities;
    }
    //根据市ID获取区信息
    public function AjaxGetAreas(){
        $this->ajaxReturn($this->getAreas(),'json');
    }
    public function getAreas($pid = null){
        $pid = $pid ? $pid : I('get.pid');
        return D('Location')->getAreas($pid);
    }
    //获取经营范围列表
    public function getShopCate(){
        return M('shop_cate')->field('id,name')->where('pid=0 and is_enable=1')->select();
    }

    //supplier_order支付状态
    public function getOrderStatus($status){
        $statusArr = array('待付款','待核对','已完成','已关闭');
        return $statusArr[$status];
    }
    //supplier_order支付方式
    public function getPayType($type){
        $typeArr = array(
            'money'     => '余额支付',
            'alipayApp' => '支付宝手机客户端',
            'wxpay'     => '微信支付',
            'offline'   => '线下支付'
        );
        return $typeArr[$type];
    }
}