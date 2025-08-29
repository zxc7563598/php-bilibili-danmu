#!/bin/bash

# 项目目录路径
PROJECT_DIR="/var/www/bilibili_danmu"
# 日志文件路径
LOG_FILE="/var/www/bilibili_danmu/runtime/check_for_updates.log"
# 锁文件路径
LOCK_FILE="/tmp/check_for_updates.lock"
# Webman 服务端口
PORT=7776
# Webman PID 文件路径
PID_FILE="$PROJECT_DIR/runtime/webman.pid"

# 设置 PATH，确保环境变量完整
export PATH="/usr/local/sbin:/usr/local/bin:/usr/sbin:/usr/bin:/sbin:/bin"

# 日志辅助函数
log_message() {
    echo "$(date '+%Y-%m-%d %H:%M:%S') - $1" >> $LOG_FILE
}
log_stage() {
    log_message "=== 阶段: $1 ==="
}
log_step() {
    log_message "--- $1 ---"
}
log_info() {
    log_message "[INFO] $1"
}
log_warn() {
    log_message "[WARN] $1"
}
log_error() {
    log_message "[ERROR] $1"
}

# 占位：发送失败邮件（你自己实现）
send_email() {
    MESSAGE="$1"

    ACCOUNT_FILE="/var/www/bilibili_danmu/runtime/tmp/account.cfg"
    UID_FILE="/var/www/bilibili_danmu/runtime/tmp/uid.cfg"
    MAIL="junjie.he.925@gmail.com"

    if [ -s "$ACCOUNT_FILE" ]; then
        ACCOUNT=$(cat "$ACCOUNT_FILE")
    else
        ACCOUNT="unknown"
    fi

    if [ -s "$UID_FILE" ]; then
        UID=$(cat "$UID_FILE")
    else
        UID="unknown"
    fi

    # JSON 中转义双引号
    ESCAPED_MSG=$(echo "$MESSAGE" | sed 's/"/\\"/g')
    ESCAPED_ACC=$(echo "$ACCOUNT" | sed 's/"/\\"/g')
    ESCAPED_UID=$(echo "$UID" | sed 's/"/\\"/g')
    ESCAPED_MAIL=$(echo "$MAIL" | sed 's/"/\\"/g')

    JSON="{\"message\":\"$ESCAPED_MSG\",\"account\":\"$ESCAPED_ACC\",\"uid\":\"$ESCAPED_UID\",\"mail\":\"$ESCAPED_MAIL\"}"

    curl -s -X POST https://tools.api.hejunjie.life/bilibilidanmu-api/update-error-email \
        -H "Content-Type: application/json" \
        -d "$JSON" >> $LOG_FILE 2>&1
}


log_message "=============================="

# 检查锁文件
if [ -f "$LOCK_FILE" ]; then
    log_warn "脚本已经在运行 - 退出."
    exit 1
fi

# 创建锁文件
touch $LOCK_FILE
trap 'rm -f "$LOCK_FILE"' EXIT

log_stage "启动检查和更新程序"

cd $PROJECT_DIR || { log_error "进入目录 $PROJECT_DIR 失败"; exit 1; }

log_step "对比提交版本"
LOCAL_COMMIT=$(git rev-parse HEAD)
REMOTE_COMMIT=$(git ls-remote origin -h refs/heads/main | cut -f1)
log_info "本地提交 ID: $LOCAL_COMMIT"
log_info "远程提交 ID: $REMOTE_COMMIT"

if [ "$LOCAL_COMMIT" != "$REMOTE_COMMIT" ]; then
    log_stage "执行 Git 更新"
    git fetch origin >> $LOG_FILE 2>&1
    git reset --hard origin/main >> $LOG_FILE 2>&1 || { log_error "Git 更新失败"; exit 1; }
    log_info "Git 更新成功"

    log_stage "执行数据库迁移"
    vendor/bin/phinx migrate >> $LOG_FILE 2>&1

    log_stage "停止 Webman 服务"
    log_step "尝试优雅停止"
    php start.php stop >> $LOG_FILE 2>&1

    RETRY=5
    while [ $RETRY -gt 0 ]; do
        if ! pgrep -f "work" >/dev/null 2>&1; then
            log_info "Webman 服务已成功停止"
            break
        else
            log_warn "服务仍在运行，等待 2 秒重试..."
            sleep 2
            RETRY=$((RETRY - 1))
        fi
    done

    if pgrep -f "work" >/dev/null 2>&1; then
        log_warn "优雅停止失败，执行强杀..."
        PIDS=$(pgrep -f "work")
        log_info "强杀进程: $PIDS"
        echo "$PIDS" | xargs kill -9 >> $LOG_FILE 2>&1
        sleep 2
    fi

    if pgrep -f "work" >/dev/null 2>&1; then
        log_error "进程仍未关闭，触发告警通知"
        send_email "进程仍未关闭，触发告警通知"
        exit 1
    else
        log_info "所有 Webman 相关进程已停止"
    fi

    log_step "清理残留 PID 文件"
    if [ -f "$PID_FILE" ]; then
        log_info "发现 PID 文件，删除中: $PID_FILE"
        rm -f "$PID_FILE"
        log_info "PID 文件删除完成"
    fi

    log_step "检查端口占用"
    if netstat -tuln | grep ":$PORT " >/dev/null 2>&1; then
        log_warn "端口 $PORT 被占用，尝试释放..."
        PID=$(netstat -tulnp 2>/dev/null | grep ":$PORT " | awk '{print $7}' | cut -d'/' -f1)
        if [ -n "$PID" ]; then
            log_info "杀死占用端口 $PORT 的进程 $PID"
            kill -9 "$PID" >> $LOG_FILE 2>&1
        fi
    fi

    RETRY_COUNT=10
    while [ $RETRY_COUNT -gt 0 ]; do
        if netstat -tuln | grep ":$PORT " >/dev/null 2>&1; then
            log_warn "端口 $PORT 仍被占用，等待释放..."
            sleep 2
            RETRY_COUNT=$((RETRY_COUNT - 1))
        else
            log_info "端口 $PORT 已释放"
            break
        fi
    done

    if [ $RETRY_COUNT -eq 0 ]; then
        log_error "端口 $PORT 长时间未释放，触发告警"
        send_email "端口 $PORT 长时间未释放，触发告警"
        exit 1
    fi

    log_stage "安装 Composer 依赖"
    composer clear-cache >> $LOG_FILE 2>&1
    composer install >> $LOG_FILE 2>&1
    composer update hejunjie/bililive hejunjie/cache hejunjie/china-division hejunjie/error-log hejunjie/mobile-locator hejunjie/utils hejunjie/address-parser hejunjie/url-signer hejunjie/google-authenticator hejunjie/simple-rule-engine >> $LOG_FILE 2>&1

    log_stage "启动 Webman 服务"
    nohup php start.php start -d >> $LOG_FILE 2>&1 &
    sleep 2

    if ! pgrep -f "work" >/dev/null 2>&1; then
        log_error "Webman 启动失败，触发告警"
        send_email "Webman 启动失败，触发告警"
        exit 1
    fi

    log_info "Webman 启动成功"

    if [ -f "scripts/build_vue.sh" ]; then
        log_info "开始执行 build_vue.sh 脚本"
        sh scripts/build_vue.sh >> $LOG_FILE 2>&1
        log_info "build_vue.sh 执行完毕"
    else
        log_warn "build_vue.sh 脚本未找到，跳过执行"
    fi
else
    log_info "提交一致，无需更新"
fi

log_stage "更新流程完成"
exit 0
