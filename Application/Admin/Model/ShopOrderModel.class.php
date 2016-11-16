<?php
// +----------------------------------------------------------------------
// | 自定义用户模型
// +----------------------------------------------------------------------
namespace Admin\Model;

use Think\Model;

class ShopOrderModel extends Model
{

    public function today()
    {
        $timelimit = strtotime(date("Y-m-d"));
        $orders = $this->where('ctime>' . $timelimit)->select();

        $result = array();
        foreach ($goods as $key => $value) {
            $goods[$key]['skuinfo'] = unserialize($goods[$key]['skuinfo']);
        }
        // 提取数据
        foreach ($orders as $k => $v) {
            // 提取每条订单Items
            $temp = unserialize($orders[$k]['items']);
            foreach ($temp as $kk => $vv) {
                // 记录每条订单内部内容
                if (!$result[$vv['goodsid']][$vv['skuid']][$vv['num']])
                    $result[$vv['goodsid']][$vv['skuid']][$vv['num']] = 1;
                else
                    $result[$vv['goodsid']][$vv['skuid']][$vv['num']] += 1;
            }
        }
        return $result;
    }

}

?>