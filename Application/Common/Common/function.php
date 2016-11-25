<?php

/**
 * 导出数据为excel表格
 * @param $data    一个二维数组,结构如同从数据库查出来的数组
 * @param $title   excel的第一行标题,一个数组
 * @param $filename 下载的文件名
 * @param $custom_func 自定义处理函数
 * @examlpe
 * export_excel([['id'=>1,'name'=>'amose'],['id'=>2,'name'=>'wang']],['id'=>'会员ID','name'=>'姓名'],'文件名');
 */
function export_excel($data = array(), $title = array(), $filename = 'report', $custom_func = null)
{
    vendor('PHPExcel.PHPExcel');
    // Create new PHPExcel object
    $objPHPExcel = new \PHPExcel();

    $objPHPExcel->setActiveSheetIndex(0);
//    $objPHPExcel->getActiveSheet()->setTitle('Simple');

    $activeSheet = $objPHPExcel->getActiveSheet();

    if ($custom_func) {
        call_user_func($custom_func, $objPHPExcel);
        $objWriter = \PHPExcel_IOFactory::createWriter($objPHPExcel, 'Excel5');
        $objWriter->save('php://output');
        exit;
    }

//    if (!empty($title)) {
//        array_unshift($data, $title);
//    }

    // Add data
//    list ($startColumn, $startRow) = PHPExcel_Cell::coordinateFromString('A1');
    //$activeSheet->fromArray($data);
    $startRow = 1;
    $startColumn = 0;
    $maxCol = count($title);
    // 设置title
    $titleKeys = array_keys($title);

    $currentColumn = $startColumn;
    foreach ($titleKeys as $key) {
        $activeSheet->getCellByColumnAndRow($currentColumn, $startRow)->setValue($title[$key]);
        ++$currentColumn;
    }

    foreach ($data as $rowData) {
        $currentColumn = $startColumn;
        ++$startRow;
        //转换成文本
        $objPHPExcel->getActiveSheet()->getStyle($startRow)->getNumberFormat()->setFormatCode(PHPExcel_Style_NumberFormat::FORMAT_TEXT);
        foreach ($titleKeys as $key) {
            $cellValue = $rowData[$key];
            if ($cellValue != null) {
                //转换为文本依然会科学计数法。在$cellValue前加一个空格
                $activeSheet->getCellByColumnAndRow($currentColumn, $startRow)->setValue(' '.$cellValue)->setDataType(PHPExcel_Cell_DataType::TYPE_STRING);
            }
            ++$currentColumn;
        }
    }

    header('Content-Type: application/vnd.ms-excel');
    header('Content-Disposition: attachment;filename="' . $filename . '.xls"');
    header('Cache-Control: max-age=0');
// If you're serving to IE 9, then the following may be needed
    header('Cache-Control: max-age=1');

// If you're serving to IE over SSL, then the following may be needed
    header('Expires: Mon, 26 Jul 1997 05:00:00 GMT'); // Date in the past
    header('Last-Modified: ' . gmdate('D, d M Y H:i:s') . ' GMT'); // always modified
    header('Cache-Control: cache, must-revalidate'); // HTTP/1.1
    header('Pragma: public'); // HTTP/1.0

    $objWriter = \PHPExcel_IOFactory::createWriter($objPHPExcel, 'Excel5');
    $objWriter->save('php://output');
    exit;
}

/**
 * 微信日志
 * @param $log
 */
function wx_log_write($log)
{
    if (is_array($log)) {
        $log = json_encode($log);
    }
    file_put_contents('./Data/wx_log/' . date('Y_m_d') . '.log', $log . PHP_EOL . PHP_EOL, FILE_APPEND);
}

/**
 * 分红/返现日志
 * @param $log
 */
function bonus_log_write($log)
{
    if (is_array($log)) {
        $log = json_encode($log);
    }
    file_put_contents('./Data/bonus_log/' . date('Y_m_d') . '.log', $log . PHP_EOL . PHP_EOL, FILE_APPEND);
}


/**
 * @param $vipid 会员ID
 * @param int $type 订单类型
 */
function getOrderCode($vipid, $type = 0)
{
    $prefix = "";
    switch ($type) {
        case 0:
            $prefix = "SC"; //商城
            break;
        case 1:
            $prefix = "SM"; //商盟
            break;
        case 2:
            $prefix = "BC";//对账单
            break;
    }
    return $prefix . $vipid . date("YmdHis", time());
}


/**
 * @param $vipid 会员ID
 * @param int $type 订单类型
 */
function getPickCode($vipid)
{
    $tmp = array('a','b','c','d','e','f','g','h','i','j','k','l','m','n','o','p','q','r','s','t','u','v','w','x','y','z','A','B','C','D','E','F','G','H','I','J','K','L','M','N','O','P','Q','R','S','T','U','V','W','X','Y','Z');
    return $vipid .$tmp[date("m", time())] .$tmp[date("d", time())].date("His", time());
}

/**
 * 获取支付方式中文表示
 * @param $payType
 * @return string
 */
function getPayTypeMsg($payType)
{
    switch ($payType) {
        case PT_WXPAY:
            return '微信';
        case PT_ALIPAYAPP:
            return '支付宝';
        case PT_MONEY:
            return '余额';
        case PT_OFFLINE:
            return '银行转账';
        default :
            return '';
    }
}

/*
 * 格式化返回结果
 * */
function responseToJson($code = 0, $message = '', $paras = null)
{
    $res["code"] = $code;
    $res["message"] = $message;
    if (!empty($paras)) {
        $res["result"] = $paras;
    }
    //response()->json($res);
    echo json_encode($res);
    exit;
}

function request_by_fsockopen($url, $post_data = array())
{
    $url_array = parse_url($url);
    $hostname = $url_array['host'];
    $port = isset($url_array['port']) ? $url_array['port'] : 80;
    $requestPath = $url_array['path'] . "?" . $url_array['query'];
    $fp = fsockopen($hostname, $port, $errno, $errstr, 10);
    if (!$fp) {
        echo "$errstr ($errno)";
        return false;
    }
    $method = "GET";
    if (!empty($post_data)) {
        $method = "POST";
    }
    $header = "$method $requestPath HTTP/1.1\r\n";
    $header .= "Host: $hostname\r\n";
    if (!empty($post_data)) {
        $_post = strval(NULL);
        foreach ($post_data as $k => $v) {
            $_post[] = $k . "=" . urlencode($v);//必须做url转码以防模拟post提交的数据中有&符而导致post参数键值对紊乱
        }
        $_post = implode('&', $_post);
        $header .= "Content-Type: application/x-www-form-urlencoded\r\n";//POST数据
        $header .= "Content-Length: " . strlen($_post) . "\r\n";//POST数据的长度
        $header .= "Connection: Close\r\n\r\n";//长连接关闭
        $header .= $_post; //传递POST数据
    } else {
        $header .= "Connection: Close\r\n\r\n";//长连接关闭
    }
    fwrite($fp, $header);
    //-----------------调试代码区间-----------------
    /*$html = '';
    while (!feof($fp)) {
        $html.=fgets($fp);
    }
    echo $html;*/
    //-----------------调试代码区间-----------------
    fclose($fp);
}

/**
 * 其它版本
 * 使用方法：
 * $post_string = "app=request&version=beta";
 * request_by_other('http://facebook.cn/restServer.php',$post_string);
 */
function request_by_other($remote_server, $post_string)
{
    $context = array(
        'http' => array(
            'method' => 'POST',
            'header' => 'Content-type: application/x-www-form-urlencoded' .
                '\r\n' . 'User-Agent : Jimmy\'s POST Example beta' .
                '\r\n' . 'Content-length:' . strlen($post_string) + 8,
            'content' => 'mypost=' . $post_string)
    );
    $stream_context = stream_context_create($context);
    $data = file_get_contents($remote_server, false, $stream_context);
    return $data;
}

/**
 * Goofy 2011-11-30
 * getDir()去文件夹列表，getFile()去对应文件夹下面的文件列表,二者的区别在于判断有没有“.”后缀的文件，其他都一样
 */

//获取文件目录列表,该方法返回数组
function getDir($dir)
{
    $dirArray[] = NULL;
    if (false != ($handle = opendir($dir))) {
        $i = 0;
        while (false !== ($file = readdir($handle))) {
            //去掉"“.”、“..”以及带“.xxx”后缀的文件
            if ($file != "." && $file != ".." && !strpos($file, ".") && $file != '.DS_Store') {
                $dirArray[$i] = $file;
                $i++;
            }
        }
        //关闭句柄
        closedir($handle);
    }
    return $dirArray;
}

//获取文件列表
function getFile($dir)
{
    $fileArray[] = NULL;
    if (false != ($handle = opendir($dir))) {
        $i = 0;
        while (false !== ($file = readdir($handle))) {
            //去掉"“.”、“..”以及带“.xxx”后缀的文件
            if ($file != "." && $file != ".." && strpos($file, ".")) {
                $fileArray[$i] = "./imageroot/current/" . $file;
                if ($i == 100) {
                    break;
                }
                $i++;
            }
        }
        //关闭句柄
        closedir($handle);
    }
    return $fileArray;
}

//调用方法getDir("./dir")……
function displayDir($str)
{
    if (!is_dir($str))
        die ('不是一个目录！');
    $files = array();
    if ($hd = opendir($str)) {
        while ($file = readdir($hd)) {
            if ($file != '.' && $file != '..') {
                if (is_dir($str . '/' . $file)) {
                    $files [$file] = displayDir($str . '/' . $file);
                } else {
                    $files [] = $file;
                }
            }
        }
    }
    return $files;
}

/**
 * 执行SQL文件
 */
function execute_sql_file($sql_path)
{
    // 读取SQL文件
    $sql = wp_file_get_contents($sql_path);
    $sql = str_replace("\r", "\n", $sql);
    $sql = explode(";\n", $sql);

    // 替换表前缀
    $orginal = 'wp_';
    $prefix = C('DB_PREFIX');
    $sql = str_replace("{$orginal}", "{$prefix}", $sql);

    // 开始安装
    foreach ($sql as $value) {
        $value = trim($value);
        if (empty ($value))
            continue;

        $res = M()->execute($value);
        // dump($res);
        // dump(M()->getLastSql());
    }
}

// 防超时的file_get_contents改造函数
function wp_file_get_contents($url)
{
    $context = stream_context_create(array(
        'http' => array(
            'timeout' => 30
        )
    )); // 超时时间，单位为秒

    return file_get_contents($url, 0, $context);
}

/**
 * 插件显示内容里生成访问插件的url
 * @param string $url url
 * @param array $param 参数
 * @author better
 * @useage u_addons('apply://App/Index/addorder',array('id'=>'1'))
 */
function u_addons($url, $param = array())
{
    $url = explode('://', $url);
    $addon = $url[0];
    $url = $url[1];

    $url = U($url, $param, false);
    return $url . '/addon/' . $addon;
}

/**
 * 系统公共库文件
 * 主要定义系统公共函数库
 */

/**
 * UTF-8错误信息输出
 * @param  string $msg 错误名称
 * @return null
 * @author App <2094157689@qq.com>
 */
function utf8error($msg)
{
    header("Content-type: text/html; charset=utf-8");
    die($msg);
}

/**
 * UE编辑器处理
 * @param  string $data 编辑器内容
 * @return null
 * @author App <2094157689@qq.com>
 */
function trimUE($data)
{
    $data = stripslashes(htmlspecialchars_decode($data));
    $find = array("<p><br/></p>", "<p>      </p>", "<p>         </p>");
    $data = htmlspecialchars(str_replace($find, "", $data));
    return $data;
}

/**
 * 分类路径配置
 * @param  string $m ,$pid 数据表,父id
 * @return null
 * @author App <2094157689@qq.com>
 */
function setPath($m, $pid)
{
    $cate = $m;
    $map['id'] = $pid;
    $list = $cate->field('id,path')->where($map)->limit(1)->find();
    $path = $list['path'] . '-' . $list['id'];
    $lv = count(explode('-', $path));
    return array('path' => $path, 'lv' => $lv);
}

/**
 * SonCate配置
 * @param  string $m ,$pid 数据表,父id
 * @return null
 * @author App <2094157689@qq.com>
 */
function setSoncate($m, $pid)
{
    $cate = $m;
    $map['pid'] = $pid;
    $father = $cate->where('id=' . $pid)->limit(1)->find();
    $son = $cate->field('id')->where($map)->select();
    if ($son && $father) {
        //存在子栏目
        $arr = '';
        foreach ($son as $k => $v) {
            $arr = $arr . $v['id'] . ',';
        }
        $father['soncate'] = $arr;
        $rf = $m->save($father);
        if ($rf === FALSE) {
            return FALSE;
        } else {
            return TRUE;
        }
    } elseif (!$son && $father) {
        //子栏目为空
        $father['soncate'] = '';
        $rf = $m->save($father);
        if ($rf === FALSE) {
            return FALSE;
        } else {
            return TRUE;
        }
    } else {
        //未知错误
        return FALSE;
    }
}

/**
 * AppTree快速无限分类树
 * @param  string $m ,$pid 数据表,父id
 * @return null
 * @author App <2094157689@qq.com>
 */
function appTree($m, $pid, $field, $map, $order = 'sorts desc', $keyid = 'id', $keypid = 'pid', $keychild = '_child')
{
    $list = $m->where($map)->field($field)->order('sorts desc')->select();
    $list = list_to_tree($list, $keyid, $keypid, $keychild, $root = $pid);
    return $list;
}

/**
 * 所有数组的笛卡尔积
 *
 * @param unknown_type $data
 */
function Descartes()
{
    $t = func_get_args();
    if (func_num_args() == 1) {
        return call_user_func_array(__FUNCTION__, $t[0]);
    }

    $a = array_shift($t);
    if (!is_array($a)) {
        $a = array($a);
    }

    $a = array_chunk($a, 1);
    do {
        $r = array();
        $b = array_shift($t);
        if (!is_array($b)) {
            $b = array($b);
        }

        foreach ($a as $p) {
            foreach (array_chunk($b, 1) as $q) {
                $r[] = array_merge($p, $q);
            }
        }

        $a = $r;
    } while ($t);
    return $r;
}

/**
 * 插件模板显示方法
 * @param  string $tpl 模板名称,可以为空值
 * @return null
 * @author App <2094157689@qq.com>
 */
function adisplay($tpl)
{
    if ($tpl) {
        C('VIEW_PATH', MODULE_PATH . '/Addon/');
    } else {

    }
}

/**
 * 字符串转换为数组，主要用于把分隔符调整到第二个参数
 * @param  string $str 要分割的字符串
 * @param  string $glue 分割符
 * @return array
 * @author 麦当苗儿 <zuojiazi@vip.qq.com>
 */
function str2arr($str, $glue = ',')
{
    return explode($glue, $str);
}

/**
 * 数组转换为字符串，主要用于把分隔符调整到第二个参数
 * @param  array $arr 要连接的数组
 * @param  string $glue 分割符
 * @return string
 * @author 麦当苗儿 <zuojiazi@vip.qq.com>
 */
function arr2str($arr, $glue = ',')
{
    return implode($glue, $arr);
}

/**
 * 字符串截取，支持中文和其他编码
 * @static
 * @access public
 * @param string $str 需要转换的字符串
 * @param string $start 开始位置
 * @param string $length 截取长度
 * @param string $charset 编码格式
 * @param string $suffix 截断显示字符
 * @return string
 */
function msubstr($str, $start = 0, $length, $charset = "utf-8", $suffix = true)
{
    if (function_exists("mb_substr")) {
        $slice = mb_substr($str, $start, $length, $charset);
    } elseif (function_exists('iconv_substr')) {
        $slice = iconv_substr($str, $start, $length, $charset);
        if (false === $slice) {
            $slice = '';
        }
    } else {
        $re['utf-8'] = "/[\x01-\x7f]|[\xc2-\xdf][\x80-\xbf]|[\xe0-\xef][\x80-\xbf]{2}|[\xf0-\xff][\x80-\xbf]{3}/";
        $re['gb2312'] = "/[\x01-\x7f]|[\xb0-\xf7][\xa0-\xfe]/";
        $re['gbk'] = "/[\x01-\x7f]|[\x81-\xfe][\x40-\xfe]/";
        $re['big5'] = "/[\x01-\x7f]|[\x81-\xfe]([\x40-\x7e]|\xa1-\xfe])/";
        preg_match_all($re[$charset], $str, $match);
        $slice = join("", array_slice($match[0], $start, $length));
    }
    return $suffix ? $slice . '...' : $slice;
}

/**
 * 系统加密方法
 * @param string $data 要加密的字符串
 * @param string $key 加密密钥
 * @param int $expire 过期时间 单位 秒
 * @return string
 * @author 麦当苗儿 <zuojiazi@vip.qq.com>
 */
function think_encrypt($data, $key = '', $expire = 0)
{
    $key = md5(empty($key) ? C('DATA_AUTH_KEY') : $key);
    $data = base64_encode($data);
    $x = 0;
    $len = strlen($data);
    $l = strlen($key);
    $char = '';

    for ($i = 0; $i < $len; $i++) {
        if ($x == $l) {
            $x = 0;
        }

        $char .= substr($key, $x, 1);
        $x++;
    }

    $str = sprintf('%010d', $expire ? $expire + time() : 0);

    for ($i = 0; $i < $len; $i++) {
        $str .= chr(ord(substr($data, $i, 1)) + (ord(substr($char, $i, 1))) % 256);
    }
    return str_replace(array('+', '/', '='), array('-', '_', ''), base64_encode($str));
}

/**
 * 系统解密方法
 * @param  string $data 要解密的字符串 （必须是think_encrypt方法加密的字符串）
 * @param  string $key 加密密钥
 * @return string
 * @author 麦当苗儿 <zuojiazi@vip.qq.com>
 */
function think_decrypt($data, $key = '')
{
    $key = md5(empty($key) ? C('DATA_AUTH_KEY') : $key);
    $data = str_replace(array('-', '_'), array('+', '/'), $data);
    $mod4 = strlen($data) % 4;
    if ($mod4) {
        $data .= substr('====', $mod4);
    }
    $data = base64_decode($data);
    $expire = substr($data, 0, 10);
    $data = substr($data, 10);

    if ($expire > 0 && $expire < time()) {
        return '';
    }
    $x = 0;
    $len = strlen($data);
    $l = strlen($key);
    $char = $str = '';

    for ($i = 0; $i < $len; $i++) {
        if ($x == $l) {
            $x = 0;
        }

        $char .= substr($key, $x, 1);
        $x++;
    }

    for ($i = 0; $i < $len; $i++) {
        if (ord(substr($data, $i, 1)) < ord(substr($char, $i, 1))) {
            $str .= chr((ord(substr($data, $i, 1)) + 256) - ord(substr($char, $i, 1)));
        } else {
            $str .= chr(ord(substr($data, $i, 1)) - ord(substr($char, $i, 1)));
        }
    }
    return base64_decode($str);
}

/**
 * 对查询结果集进行排序
 * @access public
 * @param array $list 查询结果
 * @param string $field 排序的字段名
 * @param array $sortby 排序类型
 * asc正向排序 desc逆向排序 nat自然排序
 * @return array
 */
function list_sort_by($list, $field, $sortby = 'asc')
{
    if (is_array($list)) {
        $refer = $resultSet = array();
        foreach ($list as $i => $data) {
            $refer[$i] = &$data[$field];
        }

        switch ($sortby) {
            case 'asc':    // 正向排序
                asort($refer);
                break;
            case 'desc':    // 逆向排序
                arsort($refer);
                break;
            case 'nat':    // 自然排序
                natcasesort($refer);
                break;
        }
        foreach ($refer as $key => $val) {
            $resultSet[] = &$list[$key];
        }

        return $resultSet;
    }
    return false;
}

/**
 * 把返回的数据集转换成Tree
 * @param array $list 要转换的数据集
 * @param string $pid parent标记字段
 * @param string $level level标记字段
 * @return array
 * @author 麦当苗儿 <zuojiazi@vip.qq.com>
 */
function list_to_tree($list, $pk = 'id', $pid = 'pid', $child = '_child', $root = 0)
{
    // 创建Tree
    $tree = array();
    if (is_array($list)) {
        // 创建基于主键的数组引用
        $refer = array();
        foreach ($list as $key => $data) {
            $refer[$data[$pk]] = &$list[$key];
        }
        foreach ($list as $key => $data) {
            // 判断是否存在parent
            $parentId = $data[$pid];
            if ($root == $parentId) {
                $tree[] = &$list[$key];
            } else {
                if (isset($refer[$parentId])) {
                    $parent = &$refer[$parentId];
                    $parent[$child][] = &$list[$key];
                }
            }
        }
    }
    return $tree;
}

/**
 * 将list_to_tree的树还原成列表
 * @param  array $tree 原来的树
 * @param  string $child 孩子节点的键
 * @param  string $order 排序显示的键，一般是主键 升序排列
 * @param  array $list 过渡用的中间数组，
 * @return array        返回排过序的列表数组
 * @author yangweijie <yangweijiester@gmail.com>
 */
function tree_to_list($tree, $child = '_child', $order = 'id', &$list = array())
{
    if (is_array($tree)) {
        $refer = array();
        foreach ($tree as $key => $value) {
            $reffer = $value;
            if (isset($reffer[$child])) {
                unset($reffer[$child]);
                tree_to_list($value[$child], $child, $order, $list);
            }
            $list[] = $reffer;
        }
        $list = list_sort_by($list, $order, $sortby = 'asc');
    }
    return $list;
}

/**
 * 格式化字节大小
 * @param  number $size 字节数
 * @param  string $delimiter 数字和单位分隔符
 * @return string            格式化后的带单位的大小
 * @author 麦当苗儿 <zuojiazi@vip.qq.com>
 */
function format_bytes($size, $delimiter = '')
{
    $units = array('B', 'KB', 'MB', 'GB', 'TB', 'PB');
    for ($i = 0; $size >= 1024 && $i < 5; $i++) {
        $size /= 1024;
    }

    return round($size, 2) . $delimiter . $units[$i];
}

/**
 * 设置跳转页面URL
 * 使用函数再次封装，方便以后选择不同的存储方式（目前使用cookie存储）
 * @author 麦当苗儿 <zuojiazi@vip.qq.com>
 */
function set_redirect_url($url)
{
    cookie('redirect_url', $url);
}

/**
 * 获取跳转页面URL
 * @return string 跳转页URL
 * @author 麦当苗儿 <zuojiazi@vip.qq.com>
 */
function get_redirect_url()
{
    $url = cookie('redirect_url');
    return empty($url) ? __APP__ : $url;
}

/**
 * 处理插件钩子
 * @param string $hook 钩子名称
 * @param mixed $params 传入参数
 * @return void
 */
function hook($hook, $params = array())
{
    \Think\Hook::listen($hook, $params);
}

/**
 * 获取插件类的类名
 * @param strng $name 插件名
 */
function get_addon_class($name)
{
    $class = "Addons\\{$name}\\{$name}Addon";
    return $class;
}

/**
 * 获取插件类的配置文件数组
 * @param string $name 插件名
 */
function get_addon_config($name)
{
    $class = get_addon_class($name);
    if (class_exists($class)) {
        $addon = new $class();
        return $addon->getConfig();
    } else {
        return array();
    }
}

/**
 * 插件显示内容里生成访问插件的url
 * @param string $url url
 * @param array $param 参数
 * @author 麦当苗儿 <zuojiazi@vip.qq.com>
 */
function addons_url($url, $param = array())
{
    $url = parse_url($url);
    $case = C('URL_CASE_INSENSITIVE');
    $addons = $case ? parse_name($url['scheme']) : $url['scheme'];
    $controller = $case ? parse_name($url['host']) : $url['host'];
    $action = trim($case ? strtolower($url['path']) : $url['path'], '/');

    /* 解析URL带的参数 */
    if (isset($url['query'])) {
        parse_str($url['query'], $query);
        $param = array_merge($query, $param);
    }

    /* 基础参数 */
    $params = array(
        '_addons' => $addons,
        '_controller' => $controller,
        '_action' => $action,
    );
    $params = array_merge($params, $param); //添加额外参数

    return U('Addons/execute', $params);
}

/**
 * 时间戳格式化
 * @param int $time
 * @return string 完整的时间显示
 * @author huajie <banhuajie@163.com>
 */
function time_format($time = NULL, $format = 'Y-m-d H:i')
{
    $time = $time === NULL ? NOW_TIME : intval($time);
    return date($format, $time);
}

/**
 * 记录行为日志，并执行该行为的规则
 * @param string $action 行为标识
 * @param string $model 触发行为的模型名
 * @param int $record_id 触发行为的记录id
 * @param int $user_id 执行行为的用户id
 * @return boolean
 * @author huajie <banhuajie@163.com>
 */
function action_log($action = null, $model = null, $record_id = null, $user_id = null)
{

    //参数检查
    if (empty($action) || empty($model) || empty($record_id)) {
        return '参数不能为空';
    }
    if (empty($user_id)) {
        $user_id = is_login();
    }

    //查询行为,判断是否执行
    $action_info = M('Action')->getByName($action);
    if ($action_info['status'] != 1) {
        return '该行为被禁用或删除';
    }

    //插入行为日志
    $data['action_id'] = $action_info['id'];
    $data['user_id'] = $user_id;
    $data['action_ip'] = ip2long(get_client_ip());
    $data['model'] = $model;
    $data['record_id'] = $record_id;
    $data['create_time'] = NOW_TIME;

    //解析日志规则,生成日志备注
    if (!empty($action_info['log'])) {
        if (preg_match_all('/\[(\S+?)\]/', $action_info['log'], $match)) {
            $log['user'] = $user_id;
            $log['record'] = $record_id;
            $log['model'] = $model;
            $log['time'] = NOW_TIME;
            $log['data'] = array('user' => $user_id, 'model' => $model, 'record' => $record_id, 'time' => NOW_TIME);
            foreach ($match[1] as $value) {
                $param = explode('|', $value);
                if (isset($param[1])) {
                    $replace[] = call_user_func($param[1], $log[$param[0]]);
                } else {
                    $replace[] = $log[$param[0]];
                }
            }
            $data['remark'] = str_replace($match[0], $replace, $action_info['log']);
        } else {
            $data['remark'] = $action_info['log'];
        }
    } else {
        //未定义日志规则，记录操作url
        $data['remark'] = '操作url：' . $_SERVER['REQUEST_URI'];
    }

    M('ActionLog')->add($data);

    if (!empty($action_info['rule'])) {
        //解析行为
        $rules = parse_action($action, $user_id);

        //执行行为
        $res = execute_action($rules, $action_info['id'], $user_id);
    }
}

/**
 * 解析行为规则
 * 规则定义  table:$table|field:$field|condition:$condition|rule:$rule[|cycle:$cycle|max:$max][;......]
 * 规则字段解释：table->要操作的数据表，不需要加表前缀；
 *              field->要操作的字段；
 *              condition->操作的条件，目前支持字符串，默认变量{$self}为执行行为的用户
 *              rule->对字段进行的具体操作，目前支持四则混合运算，如：1+score*2/2-3
 *              cycle->执行周期，单位（小时），表示$cycle小时内最多执行$max次
 *              max->单个周期内的最大执行次数（$cycle和$max必须同时定义，否则无效）
 * 单个行为后可加 ； 连接其他规则
 * @param string $action 行为id或者name
 * @param int $self 替换规则里的变量为执行用户的id
 * @return boolean|array: false解析出错 ， 成功返回规则数组
 * @author huajie <banhuajie@163.com>
 */
function parse_action($action = null, $self)
{
    if (empty($action)) {
        return false;
    }

    //参数支持id或者name
    if (is_numeric($action)) {
        $map = array('id' => $action);
    } else {
        $map = array('name' => $action);
    }

    //查询行为信息
    $info = M('Action')->where($map)->find();
    if (!$info || $info['status'] != 1) {
        return false;
    }

    //解析规则:table:$table|field:$field|condition:$condition|rule:$rule[|cycle:$cycle|max:$max][;......]
    $rules = $info['rule'];
    $rules = str_replace('{$self}', $self, $rules);
    $rules = explode(';', $rules);
    $return = array();
    foreach ($rules as $key => &$rule) {
        $rule = explode('|', $rule);
        foreach ($rule as $k => $fields) {
            $field = empty($fields) ? array() : explode(':', $fields);
            if (!empty($field)) {
                $return[$key][$field[0]] = $field[1];
            }
        }
        //cycle(检查周期)和max(周期内最大执行次数)必须同时存在，否则去掉这两个条件
        if (!array_key_exists('cycle', $return[$key]) || !array_key_exists('max', $return[$key])) {
            unset($return[$key]['cycle'], $return[$key]['max']);
        }
    }

    return $return;
}

/**
 * 执行行为
 * @param array $rules 解析后的规则数组
 * @param int $action_id 行为id
 * @param array $user_id 执行的用户id
 * @return boolean false 失败 ， true 成功
 * @author huajie <banhuajie@163.com>
 */
function execute_action($rules = false, $action_id = null, $user_id = null)
{
    if (!$rules || empty($action_id) || empty($user_id)) {
        return false;
    }

    $return = true;
    foreach ($rules as $rule) {

        //检查执行周期
        $map = array('action_id' => $action_id, 'user_id' => $user_id);
        $map['create_time'] = array('gt', NOW_TIME - intval($rule['cycle']) * 3600);
        $exec_count = M('ActionLog')->where($map)->count();
        if ($exec_count > $rule['max']) {
            continue;
        }

        //执行数据库操作
        $Model = M(ucfirst($rule['table']));
        $field = $rule['field'];
        $res = $Model->where($rule['condition'])->setField($field, array('exp', $rule['rule']));

        if (!$res) {
            $return = false;
        }
    }
    return $return;
}

//基于数组创建目录和文件
function create_dir_or_files($files)
{
    foreach ($files as $key => $value) {
        if (substr($value, -1) == '/') {
            mkdir($value);
        } else {
            @file_put_contents($value, '');
        }
    }
}

if (!function_exists('array_column')) {
    function array_column(array $input, $columnKey, $indexKey = null)
    {
        $result = array();
        if (null === $indexKey) {
            if (null === $columnKey) {
                $result = array_values($input);
            } else {
                foreach ($input as $row) {
                    $result[] = $row[$columnKey];
                }
            }
        } else {
            if (null === $columnKey) {
                foreach ($input as $row) {
                    $result[$row[$indexKey]] = $row;
                }
            } else {
                foreach ($input as $row) {
                    $result[$row[$indexKey]] = $row[$columnKey];
                }
            }
        }
        return $result;
    }
}

/**
 * 获取表名（不含表前缀）
 * @param string $model_id
 * @return string 表名
 * @author huajie <banhuajie@163.com>
 */
function get_table_name($model_id = null)
{
    if (empty($model_id)) {
        return false;
    }
    $Model = M('Model');
    $name = '';
    $info = $Model->getById($model_id);
    if ($info['extend'] != 0) {
        $name = $Model->getFieldById($info['extend'], 'name') . '_';
    }
    $name .= $info['name'];
    return $name;
}

/**
 * 获取属性信息并缓存
 * @param  integer $id 属性ID
 * @param  string $field 要获取的字段名
 * @return string         属性信息
 */
function get_model_attribute($model_id, $group = true)
{
    static $list;

    /* 非法ID */
    if (empty($model_id) || !is_numeric($model_id)) {
        return '';
    }

    /* 读取缓存数据 */
    if (empty($list)) {
        $list = S('attribute_list');
    }

    /* 获取属性 */
    if (!isset($list[$model_id])) {
        $map = array('model_id' => $model_id);
        $extend = M('Model')->getFieldById($model_id, 'extend');

        if ($extend) {
            $map = array('model_id' => array("in", array($model_id, $extend)));
        }
        $info = M('Attribute')->where($map)->select();
        $list[$model_id] = $info;
        //S('attribute_list', $list); //更新缓存
    }

    $attr = array();
    foreach ($list[$model_id] as $value) {
        $attr[$value['id']] = $value;
    }

    if ($group) {
        $sort = M('Model')->getFieldById($model_id, 'field_sort');

        if (empty($sort)) {
            //未排序
            $group = array(1 => array_merge($attr));
        } else {
            $group = json_decode($sort, true);

            $keys = array_keys($group);
            foreach ($group as &$value) {
                foreach ($value as $key => $val) {
                    $value[$key] = $attr[$val];
                    unset($attr[$val]);
                }
            }

            if (!empty($attr)) {
                $group[$keys[0]] = array_merge($group[$keys[0]], $attr);
            }
        }
        $attr = $group;
    }
    return $attr;
}

/**
 * 调用系统的API接口方法（静态方法）
 * api('User/getName','id=5'); 调用公共模块的User接口的getName方法
 * api('Admin/User/getName','id=5');  调用Admin模块的User接口
 * @param  string $name 格式 [模块名]/接口名/方法名
 * @param  array|string $vars 参数
 */
function api($name, $vars = array())
{
    $array = explode('/', $name);
    $method = array_pop($array);
    $classname = array_pop($array);
    $module = $array ? array_pop($array) : 'Common';
    $callback = $module . '\\Api\\' . $classname . 'Api::' . $method;
    if (is_string($vars)) {
        parse_str($vars, $vars);
    }
    return call_user_func_array($callback, $vars);
}

/**
 * 根据条件字段获取指定表的数据
 * @param mixed $value 条件，可用常量或者数组
 * @param string $condition 条件字段
 * @param string $field 需要返回的字段，不传则返回整个数据
 * @param string $table 需要查询的表
 * @author huajie <banhuajie@163.com>
 */
function get_table_field($value = null, $condition = 'id', $field = null, $table = null)
{
    if (empty($value) || empty($table)) {
        return false;
    }

    //拼接参数
    $map[$condition] = $value;
    $info = M(ucfirst($table))->where($map);
    if (empty($field)) {
        $info = $info->field(true)->find();
    } else {
        $info = $info->getField($field);
    }
    return $info;
}

/**
 * where in 数组为空时返回不存在的字符例如(-10000000000)
 * @param $value
 * @return string
 */
function in_parse_str($value)
{
    if (!$value) {
        $value = '-10000000000';
    }
    return $value;
}

/**
 * @param $file
 * @param $word
 *
 * 日志功能
 */
function  log_result($file, $word)
{
    $fp = fopen($file, "a");
    flock($fp, LOCK_EX);
    fwrite($fp, "执行日期：" . strftime("%Y-%m-%d-%H：%M：%S", time()) . "\n" . $word . "\n\n");
    flock($fp, LOCK_UN);
    fclose($fp);
}

/**
 * 获取服务器端IP地址
 * @return string
 */
function get_server_ip()
{
    if (isset($_SERVER)) {
        if ($_SERVER['SERVER_ADDR']) {
            $server_ip = $_SERVER['SERVER_ADDR'];
        } else {
            $server_ip = $_SERVER['LOCAL_ADDR'];
        }
    } else {
        $server_ip = getenv('SERVER_ADDR');
    }
    return $server_ip;
}

/**
 * @param $num
 * @return float
 * 保留两位小数
 */
function money_floor($num)
{
    return ((int)($num * 100)) / 100;
}

function sort_by_time($a, $b)
{
    if ($a['time'] == $b['time']) {
        return 0;
    } else {
        return $a['time'] < $b['time'] ? 1 : -1;
    }
}