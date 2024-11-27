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
