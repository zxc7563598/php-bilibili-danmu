#!/bin/bash

set -Eeuo pipefail

## 项目配置
PROJECT_DIR="/var/www/bilibili_danmu"
LOG_FILE="$PROJECT_DIR/runtime/check_for_updates.log"
LOCK_FILE="/tmp/check_for_updates.lock"
PORT=7776
PID_FILE="$PROJECT_DIR/runtime/webman.pid"

## 确保 PATH 完整
export PATH="/usr/local/sbin:/usr/local/bin:/usr/sbin:/usr/bin:/sbin:/bin"

## 日志函数
log_message() {
    echo "$(date '+%Y-%m-%d %H:%M:%S') - $1" >> "$LOG_FILE"
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

## 致命错误：记录日志 + 发送邮件 + 退出
fatal() {
    local msg="$1"
    log_error "$msg"
    send_email "$msg"
    exit 1
}

## 发送告警邮件（通过 HTTP API）
send_email() {
    local message="$1"
    local account_file="/var/www/bilibili_danmu/runtime/tmp/account.cfg"
    local uid_file="/var/www/bilibili_danmu/runtime/tmp/uid.cfg"
    local mail="junjie.he.925@gmail.com"

    local account uid
    account="unknown"
    uid="unknown"

    [ -s "$account_file" ] && account=$(cat "$account_file")
    [ -s "$uid_file" ] && uid=$(cat "$uid_file")

    # 使用 bash 内置参数替换转义双引号，避免对 sed 的外部依赖
    local escaped_msg="${message//\"/\\\"}"
    local escaped_acc="${account//\"/\\\"}"
    local escaped_uid="${uid//\"/\\\"}"
    local escaped_mail="${mail//\"/\\\"}"

    local json="{\"message\":\"$escaped_msg\",\"account\":\"$escaped_acc\",\"uid\":\"$escaped_uid\",\"mail\":\"$escaped_mail\"}"

    curl -s --connect-timeout 10 --max-time 30 \
        -X POST "https://tools.api.hejunjie.live/bilibilidanmu-api/update-error-email" \
        -H "Content-Type: application/json" \
        -d "$json" >> "$LOG_FILE" 2>&1 || true
}

## 单实例运行
exec {LOCK_FD}>"$LOCK_FILE"
if ! flock -n "$LOCK_FD"; then
    log_warn "脚本已在运行，跳过本次执行"
    exit 0
fi

log_message "=============================="
log_stage "启动检查和更新程序"

## 进入项目目录
cd "$PROJECT_DIR" || fatal "进入目录 $PROJECT_DIR 失败"

## 拉取远程更新并比较
log_step "拉取远程更新"
git fetch origin >> "$LOG_FILE" 2>&1 || fatal "git fetch 失败"

log_step "对比提交版本"
LOCAL_COMMIT=$(git rev-parse HEAD)
REMOTE_COMMIT=$(git rev-parse origin/main)
log_info "本地提交 ID: $LOCAL_COMMIT"
log_info "远程提交 ID: $REMOTE_COMMIT"

if [ "$LOCAL_COMMIT" != "$REMOTE_COMMIT" ]; then
    ## 更新代码
    log_stage "执行 Git 更新"
    git reset --hard origin/main >> "$LOG_FILE" 2>&1 || fatal "git reset --hard 失败"
    log_info "Git 更新成功"

    ## 数据库迁移
    log_stage "执行数据库迁移"
    if vendor/bin/phinx migrate >> "$LOG_FILE" 2>&1; then
        log_info "phinx migrate 执行成功"
    else
        log_warn "phinx migrate 执行异常，继续后续流程"
    fi

    ## 停止 Webman 服务
    log_stage "停止 Webman 服务"
    log_step "尝试优雅停止"
    php start.php stop >> "$LOG_FILE" 2>&1 || true

    RETRY=5
    while [ "$RETRY" -gt 0 ]; do
        if ! pgrep -f "work" >/dev/null 2>&1; then
            log_info "Webman 服务已成功停止"
            break
        fi
        log_warn "服务仍在运行，等待 2 秒重试..."
        sleep 2
        RETRY=$((RETRY - 1))
    done

    if pgrep -f "work" >/dev/null 2>&1; then
        log_warn "优雅停止失败，执行强杀..."
        PIDS=$(pgrep -f "work")
        log_info "强杀进程: $PIDS"
        echo "$PIDS" | xargs kill -9 >> "$LOG_FILE" 2>&1 || true
        sleep 2
    fi

    if pgrep -f "work" >/dev/null 2>&1; then
        fatal "进程仍未关闭，触发告警通知"
    fi
    log_info "所有 Webman 相关进程已停止"

    ## 清理残留 PID 文件
    log_step "清理残留 PID 文件"
    if [ -f "$PID_FILE" ]; then
        log_info "发现 PID 文件，删除中: $PID_FILE"
        rm -f "$PID_FILE"
        log_info "PID 文件删除完成"
    fi

    ## 检查并释放端口
    log_step "检查端口占用"
    if ss -tuln | grep -q ":$PORT "; then
        log_warn "端口 $PORT 被占用，尝试释放..."
        PID=$(ss -tulnp 2>/dev/null | grep ":$PORT " | sed -n 's/.*pid=\([0-9]*\).*/\1/p' || true)
        if [ -n "$PID" ]; then
            log_info "杀死占用端口 $PORT 的进程 $PID"
            kill -9 "$PID" >> "$LOG_FILE" 2>&1 || true
        fi
    fi

    RETRY_COUNT=10
    while [ "$RETRY_COUNT" -gt 0 ]; do
        if ss -tuln | grep -q ":$PORT "; then
            log_warn "端口 $PORT 仍被占用，等待释放..."
            sleep 2
            RETRY_COUNT=$((RETRY_COUNT - 1))
        else
            log_info "端口 $PORT 已释放"
            break
        fi
    done

    if [ "$RETRY_COUNT" -eq 0 ]; then
        fatal "端口 $PORT 长时间未释放，触发告警"
    fi

    ## 安装 Composer 依赖
    log_stage "安装 Composer 依赖"
    composer install --no-dev --optimize-autoloader >> "$LOG_FILE" 2>&1 \
        || fatal "composer install 失败"

    ## 启动 Webman 服务
    log_stage "启动 Webman 服务"
    nohup php start.php start -d >> "$LOG_FILE" 2>&1 &
    sleep 2

    if ! pgrep -f "work" >/dev/null 2>&1; then
        fatal "Webman 启动失败，触发告警"
    fi
    log_info "Webman 启动成功"

    ## 构建前端
    if [ -f "scripts/build_vue.sh" ]; then
        log_info "开始执行 build_vue.sh 脚本"
        sh scripts/build_vue.sh >> "$LOG_FILE" 2>&1 || log_warn "build_vue.sh 执行异常"
        log_info "build_vue.sh 执行完毕"
    else
        log_warn "build_vue.sh 脚本未找到，跳过执行"
    fi
else
    log_info "提交一致，无需更新"
fi

log_stage "更新流程完成"
exit 0
