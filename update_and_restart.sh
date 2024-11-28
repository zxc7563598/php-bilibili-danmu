#!/bin/bash

# 项目目录路径
PROJECT_DIR="/var/www/bilibili_danmu"
# 日志文件路径
LOG_FILE="/var/log/update_and_restart.log"
# 锁文件路径
LOCK_FILE="/tmp/update_and_restart.lock"

# 函数：写入日志
log_message() {
    echo "$(date '+%Y-%m-%d %H:%M:%S') - $1" >> $LOG_FILE
}

# 检查锁文件
if [ -f "$LOCK_FILE" ]; then
    log_message "Script is already running. Exiting."
    exit 1
fi

# 创建锁文件
touch $LOCK_FILE
# 确保删除锁文件，即使脚本中断
trap 'rm -f "$LOCK_FILE"' EXIT

log_message "Starting update and restart process."

# 进入项目目录
cd $PROJECT_DIR || { log_message "Failed to change directory to $PROJECT_DIR"; exit 1; }

# 拉取最新代码
log_message "Pulling latest code from Git..."
git pull origin main >> $LOG_FILE 2>&1
if [ $? -ne 0 ]; then
    log_message "Git pull failed. Exiting."
    exit 1
fi

# 停止 Webman 服务
log_message "Stopping Webman..."
php start.php stop >> $LOG_FILE 2>&1
if [ $? -ne 0 ]; then
    log_message "Failed to stop Webman. Exiting."
    exit 1
fi

# 等待端口释放
log_message "Checking if port 7776 is still in use..."
RETRY_COUNT=10  # 最多重试次数
while [ $RETRY_COUNT -gt 0 ]; do
    if sudo lsof -i:7776 >/dev/null 2>&1 || ps aux | grep '[p]hp start.php' >/dev/null 2>&1; then
        log_message "Port 7776 is still in use. Retrying..."
        sleep 1
        RETRY_COUNT=$((RETRY_COUNT - 1))
    else
        log_message "Port 7776 is free."
        break
    fi
done

if [ $RETRY_COUNT -eq 0 ]; then
    log_message "Timeout waiting for port 7776 to be released. Exiting."
    exit 1
fi

# 启动 Webman 服务
log_message "Starting Webman..."
nohup php start.php start -d >> $LOG_FILE 2>&1 &
if [ $? -ne 0 ]; then
    log_message "Failed to start Webman. Exiting."
    exit 1
fi

log_message "Update and restart process completed successfully."
