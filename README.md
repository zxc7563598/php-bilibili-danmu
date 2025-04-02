# 哔哩哔哩直播机器人
<img src="https://img.shields.io/badge/Docker-Support-blue">
<img src="https://img.shields.io/badge/PHP-8.1%2B-purple">
<img src="https://img.shields.io/badge/WebSocket-Realtime-orange">

基于 [php-bilibili-danmu-core](https://github.com/zxc7563598/php-bilibili-danmu-core) 核心库开发

---

## ✨ 核心功能

* **积分商城**：用户可通过每日签到或开通直播间大航海获取积分，兑换商城中的虚拟道具或实体礼品。
* **直播间打卡签到**：用户每日完成直播间签到，可累计/连续记录签到天数，并获得相应积分，用于兑换积分商城内的各类商品。
* **PK播报**：PK对战开启前，系统将自动播报对手直播间成员活跃度及贡献榜单信息，可自定义播报内容。
* **礼物答谢**：收到观众礼物时自动触发答谢功能，支持自定义答谢金额门槛和多条个性化答谢文案。
* **定时广告**：定时发送预设内容至直播间，支持配置多条文案并智能随机轮播。
* **进房欢迎**：用户进入直播间时自动欢迎，并支持配置多条差异化欢迎话术随机展示。
* **感谢关注**：用户关注直播间时自动感谢，并支持配置多条差异化欢迎话术随机展示。
* **感谢分享**：用户分享直播间时自动感谢，并支持配置多条差异化欢迎话术随机展示。
* **自动回复**：当用户弹幕触发预设关键词时，系统将智能匹配并随机推送差异化回复内容，支持自定义多套回复方案。
* **自动禁言**：基于自动回复功能，当触发自动回复规则时，可对违规用户执行临时禁言处罚，同时支持用户通过赠送指定价值的电池礼物来提前解除禁言状态。

## 🚀 部署方案

### 推荐方案：Docker一键部署

```bash
# 获取Docker专用版本
git clone https://github.com/zxc7563598/php-bilibili-danmu-docker.git
cd php-bilibili-danmu-docker
sh ./setup.sh
docker-compose build
docker-compose up -d
```

点击查看：[视频教程 ](https://www.bilibili.com/video/BV1PBrSYxEQn)| [图文教程](https://hejunjie.life/posts/b06795f9.html)

### 手动部署方案

```bash
# 环境要求：LNMP环境，PHP8.1+、Redis、Brotli、GD扩展
# Nginx需要设置反向代理，详见官方文档：https://www.workerman.net/doc/webman/others/nginx-proxy.html
# 手动部署需要自行配置 .env 文件
git clone https://github.com/zxc7563598/php-bilibili-danmu.git
cp .env.example .env
composer install
php vendor/bin/phinx migrate -e development
php start.php start -d
```


## 👀预览


<img src="https://raw.githubusercontent.com/zxc7563598/php-bilibili-danmu/main/public/cover.png">

<div style="display: flex; justify-content: space-around;">
  <img src="https://raw.githubusercontent.com/zxc7563598/php-bilibili-danmu/main/public/iphone_6.png" style="width: 20%;">
  <img src="https://raw.githubusercontent.com/zxc7563598/php-bilibili-danmu/main/public/iphone_2.png" style="width: 20%;">
  <img src="https://raw.githubusercontent.com/zxc7563598/php-bilibili-danmu/main/public/iphone_3.png" style="width: 20%;">
  <img src="https://raw.githubusercontent.com/zxc7563598/php-bilibili-danmu/main/public/iphone_4.png" style="width: 20%;">
  <img src="https://raw.githubusercontent.com/zxc7563598/php-bilibili-danmu/main/public/iphone_5.png" style="width: 20%;">
</div>