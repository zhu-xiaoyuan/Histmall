-- init
-- 删除商品
-- DELETE FROM `shop_goods`;
-- DELETE FROM shop_goods_sku;
-- DELETE FROM `shop_skuattr`;
-- DELETE FROM `shop_skuattr_item`;


DELETE FROM bonus_detail_record;
DELETE FROM bonus_record;
DELETE FROM shop_bonus_pool_record;

DELETE FROM fx_dslog;
DELETE FROM fx_log_tj;
DELETE FROM fx_syslog;

DELETE FROM score;
DELETE FROM score_order;

-- DELETE FROM shop_ads;

DELETE FROM shop_basket;
DELETE FROM shop_order;
DELETE FROM shop_order_log;
DELETE FROM shop_order_syslog;
DELETE FROM shop_set_syslog;
DELETE FROM shop_syslog_sells;

DELETE FROM group_buy;
--
-- DELETE FROM supplier;
DELETE FROM supplier_bill;
DELETE FROM supplier_order;
DELETE FROM supplier_order_log;
DELETE FROM supplier_order_syslog;
-- DELETE FROM supplier_store;
DELETE FROM supplier_trade_syslog;

DELETE FROM tuanzhang_tc_log;

DELETE FROM vip;
DELETE FROM vip_address;
DELETE FROM vip_log;
DELETE FROM vip_card;
DELETE FROM vip_log_sub;
DELETE FROM vip_log_tx;
DELETE FROM vip_message;
DELETE FROM vip_monthexp;
DELETE FROM vip_trade_success_log;
DELETE FROM vip_tx;
DELETE FROM vip_wxtx;

-- DELETE FROM wx_keyword;
-- DELETE FROM wx_keyword_img;

DELETE FROM sys_log_me;

update shop_set set total_sales_amount=0,capital_pool_remainder=0,bonus_capital_pool_remainder=0,total_bonus_amount=0,total_tx_fee_amount=0,total_retained_funds=0;