<?php
namespace Admin\Model;

use Think\Model\RelationModel;

class ScoreOrderModel extends RelationModel
{
    protected $_link = array(
        'VipAddress' => array(
            'mapping_type' => self::BELONGS_TO,
            'mapping_name' => 'contact',
            'foreign_key' => 'address_id',//关联id
        ),
        'Score' => array(
            'mapping_type' => self::BELONGS_TO,
            'mapping_name' => 'score',
            'foreign_key' => 'score_id',//关联id
        ),
    );
}