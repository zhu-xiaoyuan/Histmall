<?php
/**
 * 团长
 */
namespace Admin\Controller;

class TuanzhangController extends BaseController
{
    public function _initialize()
    {
        //你可以在此覆盖父类方法
        parent::_initialize();
    }

    /**
     * 团长列表页
     */
    public function lists(){
        //设置面包导航，主加载器请配置
        $bread = array(
            '0' => array('name' => '团长招商'),
            '1' => array('name' => '团长列表'),
        );
        $this->assign('breadhtml', $this->getBread($bread));

        $model = M('vip');
        $search = I('get.name');
        $map['is_tuanzhang'] = 1;

        if($search){
            $map['_string'] = 'id="'.$search.'" or nickname like "'.$search.'%"';
        }

        $p = $_GET['p'] ? $_GET['p'] : 1;
        $psize = self::$CMS['set']['pagesize'] ? self::$CMS['set']['pagesize'] : 20;
        //获取团长列表
        //echo $lists = $model->fetchSql(true)->where($map)->page($p,$psize)->field('id,nickname,name,mobile,total_supplier_number,total_tuanzhang_tc,reg_tuanzhang_time')->select();
        $lists = $model->where($map)->page($p,$psize)->field('id,nickname,name,mobile,total_supplier_number,total_tuanzhang_tc,reg_tuanzhang_time')->select();
        $count = $model->where($map)->count();
        $this->getPage($count, $psize, 'App-loader', '商品管理', 'App-search');

        $this->assign('lists',$lists);

        $this->display();
    }

    /**
     * 团长招商详情
     */
    public function detail(){
        //设置面包导航，主加载器请配置
        $bread = array(
            '0' => array('name' => '团长招商'),
            '1' => array('name' => '团长列表'),
            '2' => array('name' => '详情'),
        );
        $this->assign('breadhtml', $this->getBread($bread));

        $p = $_GET['p'] ? $_GET['p'] : 1;
        $psize = self::$CMS['set']['pagesize'] ? self::$CMS['set']['pagesize'] : 20;

        $id = I('get.id/d');
        //获取团长列表
        $tuanzhang_info = M('vip')->where('id='.$id)->field('id,nickname,name,mobile,total_supplier_number,total_tuanzhang_tc,reg_tuanzhang_time')->find();
        $supplier_info = M('supplier')->where(array('supplier.inviter_id'=>$id,'supplier.status'=>0))->page($p,$psize)
            ->field('vip.id as vip_id,supplier.id,supplier.name,supplier.province,supplier.city,supplier.area,supplier.address,supplier.create_time,supplier.total_order,supplier.total_money')
            ->join('left join vip on vip.supplier_id=supplier.id')->select();

        $count = M('supplier')->where(array('inviter_id'=>$id,'status'=>0))->count();
        $this->getPage($count, $psize, 'App-loader', '商品管理', 'App-search');

        $this->assign('tuanzhang_info',$tuanzhang_info);
        $this->assign('supplier_info',$supplier_info);

        $this->display();
    }
}