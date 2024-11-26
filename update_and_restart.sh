#!/bin/bash

# 项目目录路径
PROJECT_DIR="/var/www/bilibili_danmu"

# 进入项目目录
cd $PROJECT_DIR

# 拉取最新代码
echo "Pulling latest code from Git..."
git pull origin main

sleep 2

# 重启 Webman 服务
echo "Restarting Webman..."
php start.php stop

sleep 2

php start.php start -d

# 完成
echo "Update and restart completed!"
