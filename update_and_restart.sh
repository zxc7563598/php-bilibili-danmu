#!/bin/bash

# 项目目录路径
PROJECT_DIR="/var/www/bilibili_danmu"

# 进入项目目录
cd $PROJECT_DIR

# 拉取最新代码
echo "Pulling latest code from Git..."
git pull origin main

# 重启 Webman 服务
echo "Restarting Webman..."
php start.php restart

# 完成
echo "Update and restart completed!"
