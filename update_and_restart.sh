#!/bin/bash

# 项目目录路径
PROJECT_DIR="/var/www/bilibili_danmu"
# 日志文件路径
LOG_FILE="/var/log/check_and_update.log"
# 锁文件路径
LOCK_FILE="/tmp/check_and_update.lock"
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
    log_message "脚本已经在运行 - 退出."
    exit 1
fi

# 创建锁文件
touch $LOCK_FILE
# 确保删除锁文件，即使脚本中断
trap 'rm -f "$LOCK_FILE"' EXIT

log_message "启动检查和更新程序."

# 进入项目目录
cd $PROJECT_DIR || { log_message "将执行目录更改为 $PROJECT_DIR"; exit 1; }

# 获取当前本地 Git 提交 ID
LOCAL_COMMIT=$(git rev-parse HEAD)

# 获取远程 Git 提交 ID
REMOTE_COMMIT=$(git ls-remote origin -h refs/heads/main | cut -f1)

# 比较本地和远程的提交 ID
if [ "$LOCAL_COMMIT" != "$REMOTE_COMMIT" ]; then
    log_message "本地提交 ($LOCAL_COMMIT) 与远程提交 ($REMOTE_COMMIT). 不匹配，需要获取最新更改."

    # 拉取最新代码
    git pull origin main >> $LOG_FILE 2>&1
    if [ $? -ne 0 ]; then
        log_message "Git获取失败 - 退出."
        exit 1
    fi

    log_message "成功获取最新代码."
else
    log_message "本地提交与远程提交一致，无需更新."
fi

# 停止 Webman 服务
log_message "停止项目..."
php start.php stop >> $LOG_FILE 2>&1
if [ $? -ne 0 ]; then
    log_message "使用 'php start.php stop' 停止失败。继续强制停止."
fi

# 强制停止 Webman 进程
log_message "确保停止所有与项目相关的进程."
PIDS=$(pgrep -f "php start.php")
if [ -n "$PIDS" ]; then
    log_message "发现与项目相关的进程: $PIDS"
    echo $PIDS | xargs kill -9 >> $LOG_FILE 2>&1
    if [ $? -ne 0 ]; then
        log_message "相关进程关闭失败: $PIDS"
    else
        log_message "成功关闭相关进程: $PIDS"
    fi
else
    log_message "未能找到与项目相关进程."
fi

# 检查端口是否被占用
log_message "确保端口 $PORT 当前未被使用."
if netstat -tuln | grep ":$PORT " >/dev/null 2>&1; then
    log_message "通过杀死相关进程强制释放 $PORT ."
    PID=$(netstat -tulnp 2>/dev/null | grep ":$PORT " | awk '{print $7}' | cut -d'/' -f1)
    if [ -n "$PID" ]; then
        log_message "发现使用端口的进程 $PORT: $PID"
        kill -9 $PID >> $LOG_FILE 2>&1
        if [ $? -ne 0 ]; then
            log_message "使用端口 $PID 关闭进程 $PORT 失败."
        else
            log_message "使用端口 $PID 成功关闭进程 $PORT."
        fi
    else
        log_message "未找到使用端口的进程 $PORT."
    fi
else
    log_message "端口 $PORT 已经空闲."
fi

# 再次检查端口是否仍在使用
log_message "检查 $PORT 端口是否仍在使用中..."
RETRY_COUNT=10  # 最多重试次数
while [ $RETRY_COUNT -gt 0 ]; do
    if netstat -tuln | grep ":$PORT " >/dev/null 2>&1; then
        log_message "端口 $PORT 仍在使用中，2秒后重试..."
        sleep 2
        RETRY_COUNT=$((RETRY_COUNT - 1))
    else
        log_message "端口 $PORT 已经空闲."
        break
    fi
done

if [ $RETRY_COUNT -eq 0 ]; then
    log_message "等待端口 $PORT 释放超时 - 退出."
    exit 1
fi

# 启动 Webman 服务
log_message "启动项目..."
nohup php start.php start -d >> $LOG_FILE 2>&1 &
if [ $? -ne 0 ]; then
    log_message "项目启动失败 - 退出."
    exit 1
fi

log_message "检查和更新过程已成功完成."
log_message "------------------------------"
