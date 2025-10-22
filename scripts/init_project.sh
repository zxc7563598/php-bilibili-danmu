#!/bin/bash

# 项目根目录
PROJECT_DIR="/var/www/bilibili_danmu"
ENV_EXAMPLE_FILE="$PROJECT_DIR/.env.example"
ENV_FILE="$PROJECT_DIR/.env"
LOG_DIR="$PROJECT_DIR/runtime/logs"
LOG_FILE="$LOG_DIR/script.log"
OS_TYPE=$(uname)

mkdir -p "$LOG_DIR"
echo -e "\n========== 环境初始化开始：$(date '+%Y-%m-%d %H:%M:%S') ==========\n" >> "$LOG_FILE"

# 记录并执行命令
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

# 封装 sed，兼容 macOS 和 Linux，暂不支持 Windows
replace_in_env() {
    local FILE="$1"
    local PATTERN="$2"
    local REPLACEMENT="$3"
    if [[ "$OS_TYPE" == "Darwin" ]]; then
        sed -i '' "s|$PATTERN|$REPLACEMENT|" "$FILE"
    else
        sed -i "s|$PATTERN|$REPLACEMENT|" "$FILE"
    fi
}

# 生成随机字符串
generate_random_string() {
    local LENGTH=$1
    if command -v openssl >/dev/null 2>&1; then
        openssl rand -base64 "$LENGTH" | tr -dc 'A-Za-z0-9' | head -c "$LENGTH"
    else
        tr -dc 'A-Za-z0-9' </dev/urandom | head -c "$LENGTH"
    fi
}

# 检查项目目录是否存在
if [ ! -d "$PROJECT_DIR" ]; then
    echo "❌ 项目目录不存在: $PROJECT_DIR" | tee -a "$LOG_FILE"
    exit 1
fi

# 如果 .env 不存在则生成并初始化
if [ ! -f "$ENV_FILE" ]; then
    run_command "复制 .env.example 为 .env" cp "$ENV_EXAMPLE_FILE" "$ENV_FILE"

    # 配置信息
    SECURE_API_KEY=$(generate_random_string 32)
    replace_in_env "$ENV_FILE" "^SYSTEM_API_URL=.*" "SYSTEM_API_URL="
    replace_in_env "$ENV_FILE" "^SECURE_API_KEY=.*" "SECURE_API_KEY=$SECURE_API_KEY"
    replace_in_env "$ENV_FILE" "^SHOP_URL=.*" "SHOP_URL="
    replace_in_env "$ENV_FILE" "^HOST=.*" "HOST=http://php"
    replace_in_env "$ENV_FILE" "^LISTEN=.*" "LISTEN=7776"
    replace_in_env "$ENV_FILE" "^RE_OPEN_HOST=.*" "RE_OPEN_HOST=http://php"
    replace_in_env "$ENV_FILE" "^REDIS_HOST=.*" "REDIS_HOST=redis"
    replace_in_env "$ENV_FILE" "^REDIS_PORT=.*" "REDIS_PORT=6379"

    # MySQL 相关配置
    DB_USER="bilibili_danmu"
    DB_PASSWORD=$(generate_random_string 12)
    DB_NAME="bilibili_danmu"

    echo "⏳ 正在等待 MySQL 启动..." >> "$LOG_FILE"
    until mariadb-admin ping -h "mysql" --skip-ssl --silent; do
        echo "⏳ Waiting for MySQL to be ready..." >> "$LOG_FILE"
        sleep 5
    done

    echo "✅ MySQL 已就绪，开始创建数据库及用户..." >> "$LOG_FILE"
    mariadb -h mysql -u root -pinit0925 --skip-ssl <<EOF >> "$LOG_FILE" 2>&1
CREATE DATABASE IF NOT EXISTS \`${DB_NAME}\` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
CREATE USER IF NOT EXISTS '${DB_USER}'@'%' IDENTIFIED BY '${DB_PASSWORD}';
ALTER USER '${DB_USER}'@'%' IDENTIFIED BY '${DB_PASSWORD}';
GRANT ALL PRIVILEGES ON *.* TO '${DB_USER}'@'%' WITH GRANT OPTION;
DELETE FROM mysql.user WHERE user = 'root' AND host != 'localhost';
FLUSH PRIVILEGES;
EOF

    # 替换 .env 中的数据库配置
    replace_in_env "$ENV_FILE" "^DB_HOST=.*" "DB_HOST=mysql"
    replace_in_env "$ENV_FILE" "^DB_PORT=.*" "DB_PORT=3306"
    replace_in_env "$ENV_FILE" "^DB_USER=.*" "DB_USER=$DB_USER"
    replace_in_env "$ENV_FILE" "^DB_NAME=.*" "DB_NAME=$DB_NAME"
    replace_in_env "$ENV_FILE" "^DB_PASSWORD=.*" "DB_PASSWORD=$DB_PASSWORD"

    echo "✅ MySQL 初始化完成，账户：$DB_USER，密码：$DB_PASSWORD" >> "$LOG_FILE"
fi

# 执行数据库迁移
cd "$PROJECT_DIR" || exit 1
run_command "执行数据库迁移" php vendor/bin/phinx migrate -e development

# 启动项目
run_command "启动项目" php start.php start -d

echo -e "\n✅ 环境初始化完成：$(date '+%Y-%m-%d %H:%M:%S')" >> "$LOG_FILE"
exit 0
