## 安装步骤

### 1. 导入数据库脚本
> 默认用户名/密码：admin/admin

### 2. 修改目录权限
> chmod -R 777

* /Upload
* /Application/Runtime
* /QRcode
* /Public/Admin/ueditor/php/upload

### 3. cp db.bak.php db.conf，修改db.conf数据库配置

### 4. 配置微信公众号
#### 基础配置
* 开发--基本配置--服务器配置 对应平台 系统设置--微信设置--微信TOKEN及链接
* 开发--接口权限--网页账号，修改“授权回调页面域名”
* 设置--公众号设置--功能设置，修改“业务域名”、“JS接口安全域名”


#### 微信支付配置
> 公众号支付 -- 测试授权目录 http://kitty.gm365.cc/ ，即wxpay.php所在目录
> 下载api安全证书，微信红包需要，放置到项目/Data/cacert目录下


### 5. /Data目录保存了支付、佣金发放失败、团长提成发放失败等错误信息
> chmod -R 777 /Data

### 6. 所需crontab任务
1. 定期更新红包领取状态
> 

### 7. 系统设置
* 微信设置
* 商城设置
* 返现佣金设置
* 模板消息配置
* 