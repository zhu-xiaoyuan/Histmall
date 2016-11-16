<?php
namespace Addons\Apply\Model;

use Think\Model\RelationModel;

class ApplyRecordModel extends RelationModel
{
    protected $_link = array(
        'VipAddress' => array(
            'mapping_type' => self::BELONGS_TO,
            'mapping_name' => 'contact',
            'foreign_key' => 'contact_id',//关联id
        ),
    );
}