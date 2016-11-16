<?php
// +----------------------------------------------------------------------
// | 菜单
// +----------------------------------------------------------------------
namespace Admin\Model;
use Think\Model;

class MenuModel extends Model
{
    //获取用户权限内的菜单
    public function getUserMenu(){
        $role_id = $_SESSION['CMS']['user']['role_id'];
        if($role_id == 0){  //如果为0,则为超级管理员
            return $this->getMenu();
        }
        $tmp_ids =  M('role_menu')->where('role_id='.$role_id)->field('menu_id')->select();
        foreach($tmp_ids as $v){
            $menu_ids[] = $v['menu_id'];
        }
        //$menu_id = implode(',',$menu_ids);
        //组装筛选条件
        $map['level'] = ['in','1,2'];   //只取1,2级菜单
        $map['id'] = ['in',$menu_ids];  //对应权限id
        $map['type'] = 0;               //菜单
        return $this->getMenu($map);
    }

    //获取全部菜单（除去 非菜单 ）
    public function getMenu($map = null){
        // 1. 取出数据
        if($map){
            $tmp_menu = $this->field('id,pid,name,url,icon,level,code')->where($map)
                ->order('code asc')->select();
        }else{  //取全部数据
            $tmp_menu = $this->where('type=0 and level in (1,2)')->field('id,pid,name,url,icon,level,code')->order('code asc')->select();
        }
        $menu = [];

        // 2.pid==0则为父菜单，!=0位子菜单
        foreach($tmp_menu as $v){
            if($v['pid'] == 0){
                $menu[$v['id']][0] = $v;    //父菜单
            }else{
                $menu[$v['pid']][1][] = $v; //子菜单
            }
        }
        return $menu;
    }

    //仅获取权限内的url(包括一二三级,非菜单，默认)
    public function getAuthUrl(){
        $role_id = $_SESSION['CMS']['user']['role_id'];
        $tmp_ids =  M('role_menu')->where('role_id='.$role_id)->field('menu_id')->select();
        foreach($tmp_ids as $v){
            $menu_ids[] = $v['menu_id'];
        }
        $menu_id = implode(',',$menu_ids);
        $sql = 'select `url` from `menu` where `url`!= "#" and `id` in ('.$menu_id.') or (`level`=3 and `pid` in ('.$menu_id.') or `type`=1)';
        $tmp_urls = M()->query($sql);
        foreach($tmp_urls as $v){
            $urls[] = $v['url'];
        }
        return $urls;
    }

}