#!/bin/bash

# 设置日志路径（可选）
LOG_FILE="/var/log/script.log"

# 拉取项目
echo "正在拉取项目..."
git clone https://github.com/zxc7563598/vue-bilibili-danmu-shop.git /var/www/bilibili_danmu/public/shop >> $LOG_FILE 2>&1
if [ $? -ne 0 ]; then
    echo "拉取项目失败" >> $LOG_FILE
    exit 1  # 如果 git clone 失败，则退出并返回非零状态
fi

# 复制 .env.example 为 .env
cp /var/www/bilibili_danmu/public/shop/.env.example /var/www/bilibili_danmu/public/shop/.env

# 提取系统配置文件中的值
SYSTEM_API_URL=$(grep -oP '^SYSTEM_API_URL=\K.*' /var/www/bilibili_danmu/.env)
SYSTEM_AES_KEY=$(grep -oP '^SYSTEM_AES_KEY=\K.*' /var/www/bilibili_danmu/.env)
SYSTEM_AES_IV=$(grep -oP '^SYSTEM_AES_IV=\K.*' /var/www/bilibili_danmu/.env)
SYSTEM_KEY=$(grep -oP '^SYSTEM_KEY=\K.*' /var/www/bilibili_danmu/.env)

# 替换 .env 文件中的配置项
sed -i "s/^VITE_APP_NAME=.*/VITE_APP_NAME=积分商城/" /var/www/bilibili_danmu/public/shop/.env
sed -i "s|^VITE_API_URL=.*|VITE_API_URL=$SYSTEM_API_URL|" /var/www/bilibili_danmu/public/shop/.env
sed -i "s|^VITE_API_AES_KEY=.*|VITE_API_AES_KEY=$SYSTEM_AES_KEY|" /var/www/bilibili_danmu/public/shop/.env
sed -i "s|^VITE_API_AES_IV=.*|VITE_API_AES_IV=$SYSTEM_AES_IV|" /var/www/bilibili_danmu/public/shop/.env
sed -i "s|^VITE_API_KEY=.*|VITE_API_KEY=$SYSTEM_KEY|" /var/www/bilibili_danmu/public/shop/.env

# 前往目录
echo "进入项目目录..."
cd /var/www/bilibili_danmu/public/shop || { echo "无法进入目录" >> $LOG_FILE; exit 1; }

# 安装依赖
echo "开始安装项目依赖..."
npm install >> $LOG_FILE 2>&1
if [ $? -ne 0 ]; then
    echo "安装依赖失败" >> $LOG_FILE
    exit 1  # 如果 npm install 失败，则退出并返回非零状态
fi

# 构建项目
echo "开始构建项目..."
npm run build >> $LOG_FILE 2>&1
if [ $? -ne 0 ]; then
    echo "构建失败" >> $LOG_FILE
    exit 1  # 如果构建失败，则退出并返回非零状态
fi

# 如果一切正常，返回成功
echo "脚本执行成功" >> $LOG_FILE
exit 0
