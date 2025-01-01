# 哔哩哔哩直播机器人

这是一个基于 [php-bilibili-danmu-core](https://github.com/zxc7563598/php-bilibili-danmu-core) 实现的哔哩哔哩直播机器人项目，使用 [Webman](https://www.workerman.net/webman) 框架，通过 WebSocket 实现实时互动功能。

<img src="https://raw.githubusercontent.com/zxc7563598/php-bilibili-danmu/main/public/cover.png">

<div style="display: flex; justify-content: space-around;">
  <img src="https://raw.githubusercontent.com/zxc7563598/php-bilibili-danmu/main/public/iphone_6.png" style="width: 20%;">
  <img src="https://raw.githubusercontent.com/zxc7563598/php-bilibili-danmu/main/public/iphone_2.png" style="width: 20%;">
  <img src="https://raw.githubusercontent.com/zxc7563598/php-bilibili-danmu/main/public/iphone_3.png" style="width: 20%;">
  <img src="https://raw.githubusercontent.com/zxc7563598/php-bilibili-danmu/main/public/iphone_4.png" style="width: 20%;">
  <img src="https://raw.githubusercontent.com/zxc7563598/php-bilibili-danmu/main/public/iphone_5.png" style="width: 20%;">
</div>

---

## 项目特色

- **实时弹幕监控**：通过 WebSocket 接收直播间弹幕，实现多样化的弹幕处理逻辑。
- **Docker 支持**：提供一键部署的 Docker 环境，适合非服务器或 Windows 用户。

---

## 部署方式

### 1. 使用 Docker 快速部署
推荐非服务器用户使用 Docker 版本，无需复杂配置，详见仓库：[php-bilibili-danmu-docker](https://github.com/zxc7563598/php-bilibili-danmu-docker)。  
**特点**：
- 自动启动
- 自动更新
- 零配置部署

### 2. 手动部署（适合服务器环境）

#### **环境要求**
- PHP 8.1 及以上版本
- 以下 PHP 扩展：
  - Redis
  - Brotli
  - GD

#### **步骤**

1. **获取项目代码**
   ```bash
   git clone https://github.com/zxc7563598/php-bilibili-danmu.git
   ```
2. **配置项目 将 .env.example 文件复制为 .env，并根据需求调整配置**
   ```
   cp .env.example .env
   ```
   > 默认配置已经提供，通常无需修改。
3. **安装插件**
   ```
   composer install
   ```
4. **启动项目**
   ```
   php start.php start -d
   ```
5. **停止项目**
   ```
   php start.php stop
   ```

## 注意事项
- 本项目依赖 php-bilibili-danmu-core 核心库，请确保使用的版本兼容。
- 如果在非 Docker 环境中部署，建议通过宝塔面板快速安装 PHP 及相关扩展。