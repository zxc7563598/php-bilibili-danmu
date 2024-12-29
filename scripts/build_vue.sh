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
