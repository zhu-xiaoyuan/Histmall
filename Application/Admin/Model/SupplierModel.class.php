<?php
/**
 * 店铺 数据表Model 2016年6月18日
 */

namespace Admin\Model;

use Think\Model;

class SupplierModel extends Model{

    protected $tableName = 'supplier';

    //验证
    protected $_validate = array(
        array('name','require','商家名称必须填写'),
        array('province','require','请选择省份'),
        array('city','require','请选择城市'),
        array('address','require','请填写详细地址'),
        array('area','require','请选择区'),
        //array('contract_code','require','请填写纸质合同编号'),
        //array('inviter_id','require','请填写邀请人ID'),
        array('contant_name','require','请填写联系人姓名'),
        array('contact_phone','require','请填写联系人电话'),
        //array('contact_idcard','require','请填写联系人身份证号'),
        //array('alipay_code','require','请填写支付宝账号'),
        array('bank_name','require','请填写银行名称'),
        //array('sub_bank_name','require','请填写银行分行名称'),
        array('cardholder','require','请填写持卡人姓名'),
        array('bank_code','require','请填写银行账号'),
    );
}