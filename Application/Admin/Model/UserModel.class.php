<?php
// +----------------------------------------------------------------------
// | 自定义用户模型
// +----------------------------------------------------------------------
namespace Admin\Model;

use Think\Model;

class UserModel extends Model
{

    // 验证用户名唯一
    public function checkUnique($username)
    {
        $user = $this->where(array('username' => $username))->find();
        return $user ? false : true;
    }

}

?>