#!/bin/bash

# 获取脚本执行时的根目录
ROOT_DIR="$(pwd)"
LOG_DIR="$ROOT_DIR/runtime/logs"
LOG_FILE="$LOG_DIR/script.log"
OS_TYPE=$(uname)

# 创建日志目录
mkdir -p "$LOG_DIR"
echo -e "\n========== 部署开始：$(date '+%Y-%m-%d %H:%M:%S') ==========\n" >> "$LOG_FILE"

# 函数：执行命令并记录日志
run_command() {
    local DESC="$1"
    shift
    echo "[$(date '+%Y-%m-%d %H:%M:%S')] $DESC" >> "$LOG_FILE"
    "$@" >> "$LOG_FILE" 2>&1
    if [ $? -ne 0 ]; then
        echo "❌ 失败：$DESC" >> "$LOG_FILE"
        exit 1
    else
        echo "✅ 成功：$DESC" >> "$LOG_FILE"
    fi
}

# 函数：替换 .env 配置的通用函数
replace_in_env() {
    local FILE="$1"
    local PATTERN="$2"
    local REPLACEMENT="$3"
    if [[ "$OS_TYPE" == "Darwin" ]]; then
        # macOS
        sed -i '' "s|$PATTERN|$REPLACEMENT|" "$FILE"
    else
        # Linux
        sed -i "s|$PATTERN|$REPLACEMENT|" "$FILE"
    fi
}

# 读取配置文件
ENV_PATH="$ROOT_DIR/.env"
SHOP_NAME=$(awk -F'=' '/^SHOP_NAME/ {print $2}' "$ENV_PATH")
SYSTEM_API_URL=$(awk -F'=' '/^SYSTEM_API_URL/ {print $2}' "$ENV_PATH")
SYSTEM_AES_KEY=$(awk -F'=' '/^SYSTEM_AES_KEY/ {print $2}' "$ENV_PATH")
SYSTEM_AES_IV=$(awk -F'=' '/^SYSTEM_AES_IV/ {print $2}' "$ENV_PATH")
SYSTEM_KEY=$(awk -F'=' '/^SYSTEM_KEY/ {print $2}' "$ENV_PATH")

# ---------- 商城项目 ----------

SHOP_DIR="$ROOT_DIR/public/shop"
run_command "删除旧商城目录" rm -rf "$SHOP_DIR"
run_command "克隆商城项目" git clone https://github.com/zxc7563598/vue-bilibili-danmu-shop.git "$SHOP_DIR"
cp "$SHOP_DIR/.env.example" "$SHOP_DIR/.env"

# 替换商城 .env 配置
replace_in_env "$SHOP_DIR/.env" "^VITE_APP_NAME=.*" "VITE_APP_NAME=$SHOP_NAME"
replace_in_env "$SHOP_DIR/.env" "^VITE_API_URL=.*" "VITE_API_URL=$SYSTEM_API_URL"
replace_in_env "$SHOP_DIR/.env" "^VITE_API_AES_KEY=.*" "VITE_API_AES_KEY=$SYSTEM_AES_KEY"
replace_in_env "$SHOP_DIR/.env" "^VITE_API_AES_IV=.*" "VITE_API_AES_IV=$SYSTEM_AES_IV"
replace_in_env "$SHOP_DIR/.env" "^VITE_API_KEY=.*" "VITE_API_KEY=$SYSTEM_KEY"

cd "$SHOP_DIR" || { echo "❌ 无法进入目录 $SHOP_DIR" >> "$LOG_FILE"; exit 1; }
run_command "安装商城依赖" npm install
run_command "构建商城项目" npm run build

# ---------- 后台项目 ----------

# ADMIN_REPO="https://github.com/zxc7563598/vue-bilibili-danmu-admin.git"
# ADMIN_BUILD_DIR="$ROOT_DIR/public/dist_build"
# ADMIN_DIST_DIR="$ROOT_DIR/public/dist"

# run_command "删除旧后台目录" rm -rf "$ADMIN_DIST_DIR"
# run_command "删除旧后台构建目录" rm -rf "$ADMIN_BUILD_DIR"
# run_command "克隆后台项目" git clone "$ADMIN_REPO" "$ADMIN_BUILD_DIR"
# cp "$ADMIN_BUILD_DIR/.env.example" "$ADMIN_BUILD_DIR/.env"

# # 替换后台 .env 配置
# replace_in_env "$ADMIN_BUILD_DIR/.env" "^VITE_PUBLIC_PATH=.*" "VITE_PUBLIC_PATH=/dist"
# replace_in_env "$ADMIN_BUILD_DIR/.env" "^VITE_AXIOS_BASE_URL=.*" "VITE_AXIOS_BASE_URL=$SYSTEM_API_URL/admin-api"
# replace_in_env "$ADMIN_BUILD_DIR/.env" "^VITE_PROXY_TARGET=.*" "VITE_PROXY_TARGET=$SYSTEM_API_URL"
# replace_in_env "$ADMIN_BUILD_DIR/.env" "^VITE_AES_KEY=.*" "VITE_AES_KEY=$SYSTEM_AES_KEY"
# replace_in_env "$ADMIN_BUILD_DIR/.env" "^VITE_AES_IV=.*" "VITE_AES_IV=$SYSTEM_AES_IV"
# replace_in_env "$ADMIN_BUILD_DIR/.env" "^VITE_SIGN_KEY=.*" "VITE_SIGN_KEY=$SYSTEM_KEY"

# cd "$ADMIN_BUILD_DIR" || { echo "❌ 无法进入目录 $ADMIN_BUILD_DIR" >> "$LOG_FILE"; exit 1; }
# run_command "安装后台依赖" npm install
# run_command "构建后台项目" npm run build

# # 替换 dist 目录
# cd "$ROOT_DIR/../.." || exit
# run_command "替换 dist 目录" cp -R "$ADMIN_BUILD_DIR/dist" "$ADMIN_DIST_DIR"
# run_command "清理构建目录" rm -rf "$ADMIN_BUILD_DIR"

# 部署成功
echo -e "\n✅ 项目部署完成：$(date '+%Y-%m-%d %H:%M:%S')" >> "$LOG_FILE"
exit 0
