#!/bin/bash

# 项目目录路径
PROJECT_DIR="/var/www/bilibili_danmu"
# 日志文件路径
LOG_FILE="/var/log/update_and_restart.log"
# 锁文件路径
LOCK_FILE="/tmp/update_and_restart.lock"
# Webman 服务端口
PORT=7776

# 设置 PATH，确保环境变量完整
export PATH="/usr/local/sbin:/usr/local/bin:/usr/sbin:/usr/bin:/sbin:/bin"

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
    log_message "Failed to stop Webman. Attempting to force stop."
fi

# 强制清理 Webman 相关进程
log_message "Ensuring all Webman-related processes are stopped."
pkill -f "php start.php" >> $LOG_FILE 2>&1
if [ $? -ne 0 ]; then
    log_message "Failed to kill processes matching 'php start.php'. Continuing."
fi

# 强制释放端口
log_message "Ensuring port $PORT is not in use."
if lsof -i:$PORT >/dev/null 2>&1; then
    log_message "Forcing release of port $PORT by killing associated processes."
    kill -9 $(lsof -t -i:$PORT) >> $LOG_FILE 2>&1
    if [ $? -ne 0 ]; then
        log_message "Failed to kill processes on port $PORT. Continuing."
    fi
else
    log_message "Port $PORT is already free."
fi

# 再次检查端口是否仍在使用
log_message "Checking if port $PORT is still in use..."
RETRY_COUNT=10  # 最多重试次数
while [ $RETRY_COUNT -gt 0 ]; do
    if lsof -i:$PORT >/dev/null 2>&1; then
        log_message "Port $PORT is still in use. Retrying in 2 seconds..."
        sleep 2
        RETRY_COUNT=$((RETRY_COUNT - 1))
    else
        log_message "Port $PORT is free."
        break
    fi
done

if [ $RETRY_COUNT -eq 0 ]; then
    log_message "Timeout waiting for port $PORT to be released. Exiting."
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
