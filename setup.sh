#!/bin/bash

# 项目目录
PROJECT_DIR="/var/www/bilibili_danmu"

# 环境变量文件路径
ENV_EXAMPLE_FILE="$PROJECT_DIR/.env.example"
ENV_FILE="$PROJECT_DIR/.env"

# 确保项目目录存在
if [ ! -d "$PROJECT_DIR" ]; then
    echo "Project directory does not exist: $PROJECT_DIR"
    exit 1
fi

# 复制 .env.example 为 .env
cp $ENV_EXAMPLE_FILE $ENV_FILE

# 动态生成随机字符串
generate_random_string() {
    local LENGTH=$1
    if command -v openssl >/dev/null 2>&1; then
        openssl rand -base64 $LENGTH | tr -dc 'A-Za-z0-9' | head -c $LENGTH
    else
        tr -dc 'A-Za-z0-9' </dev/urandom | head -c $LENGTH
    fi
}

# 生成随机密钥
SECURE_API_KEY=$(generate_random_string 32)

# 替换特定值
if grep -q "SECURE_API_KEY=" $ENV_FILE; then
    sed -i "s|SECURE_API_KEY=.*|SECURE_API_KEY=$SECURE_API_KEY|" $ENV_FILE
else
    echo "SECURE_API_KEY=$SECURE_API_KEY" >> $ENV_FILE
fi

# 生成随机的 MySQL root 密码和普通用户密码
DB_USER="bilibili_$(openssl rand -hex 4)"
DB_PASSWORD=$(openssl rand -base64 12)
DB_NAME=bilibili_danmu

# 等待 MySQL 启动完成
until mysqladmin ping -h "mysql" --silent; do
    echo "Waiting for MySQL to be ready..."
    sleep 5
done

# 使用初始 root 密码登录，更新 root 密码和创建普通用户
mysql -h mysql -u root -pinit0925 <<EOF
CREATE DATABASE IF NOT EXISTS \`${DB_USER}\` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
CREATE USER IF NOT EXISTS '${DB_NAME}'@'%' IDENTIFIED BY '${DB_PASSWORD}';
GRANT ALL PRIVILEGES ON *.* TO '${DB_NAME}'@'%' WITH GRANT OPTION;
FLUSH PRIVILEGES;
EOF

# 替换 .env 文件中的占位符
sed -i "s/^DB_HOST=.*/DB_HOST=mysql/" /var/www/bilibili_danmu/.env
sed -i "s/^DB_PORT=.*/DB_PORT=3306/" /var/www/bilibili_danmu/.env
sed -i "s/^DB_USER=.*/DB_USER=${DB_USER}/" /var/www/bilibili_danmu/.env
sed -i "s/^DB_NAME=.*/DB_NAME=${DB_NAME}/" /var/www/bilibili_danmu/.env
sed -i "s/^DB_PASSWORD=.*/DB_PASSWORD=${DB_PASSWORD}/" /var/www/bilibili_danmu/.env

# 输出新生成的密码信息（可选，生产环境中建议避免直接打印）
echo "MySQL setup complete."
echo "Database: ${DB_NAME}"
echo "User Password: ${DB_PASSWORD}"

