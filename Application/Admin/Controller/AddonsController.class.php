<?php
/**
 * Created by PhpStorm.
 * User: heqing
 * Date: 15/7/31
 * Time: 09:32
 */

namespace Admin\Controller;


class AddonsController extends BaseController
{
    public function index()
    {
        $addons = getDir(ADDON_PATH);
        $info = array();
        foreach ($addons as $key => $value) {
            if (is_file(ADDON_PATH . '/' . $value . '/' . 'config.php')) {
                $config = require ADDON_PATH . '/' . $value . '/' . 'config.php';
                $_info = $config['info'];
                $name = $_info['name'];

                $_info['addons_setting_url'] = u_addons($name . '://Admin/setting/index');
                $_info['addons_admin_url'] = u_addons($name . '://Admin/admin/index');
                $_info['addons_install_url'] = u_addons($name . '://Admin/init/install');
                $_info['addons_uninstall_url'] = u_addons($name . '://Admin/init/uninstall');
                array_push($info, $_info);
            }
        }

        $bread = array(
            '0' => array(
                'name' => '插件管理',
                'url' => U('Admin/Addons/index'),
            ),
        );
        $this->assign('breadhtml', $this->getBread($bread));
        $this->assign('addons', $info);
        $this->assign('url', "http://" . I("server.HTTP_HOST"));
        $this->display();
    }
}