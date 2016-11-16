<?php
// +----------------------------------------------------------------------
// | 自定义微信模型
// +----------------------------------------------------------------------
namespace Common\Model;

class WechatModel
{

    // 发送XXOO模板消息
    public function getTemplateId($templateidshort)
    {
        $template = M('Wx_template')->where(array('templateidshort' => $templateidshort))->find();
        if ($template) {
            return $template['templateid'];
        } else {
            return false;
        }
    }

}

?>
