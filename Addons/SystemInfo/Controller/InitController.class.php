<?php
/**
 * Created by PhpStorm.
 * User: heqing
 * Date: 15/7/30
 * Time: 12:11
 */

namespace Addons\SystemInfo\Controller;


use Common\Controller\Addon;

class InitController extends Addon
{

    public function install()
    {
        $install_sql = './Addons/SystemInfo/Data/install.sql';
        if (file_exists($install_sql)) {
            execute_sql_file($install_sql);
        }
        return true;
    }

    public function uninstall()
    {
        $uninstall_sql = './Addons/SystemInfo/Data/uninstall.sql';
        if (file_exists($uninstall_sql)) {
            execute_sql_file($uninstall_sql);
        }
        return true;
    }
}