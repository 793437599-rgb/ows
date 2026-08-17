# OWS

基于 [ThinkPHP 5](http://thinkphp.cn/) 开发的 Web 项目。

## 环境要求

- PHP >= 7.0
- 扩展：`json`、`gd`、`fileinfo`、`imagick`
- MySQL（或其他兼容数据库）
- Composer

## 目录说明

| 目录 | 说明 |
|------|------|
| `application/` | 应用核心代码（控制器、模型、视图、公共模块等） |
| `public/` | 网站入口目录，Web 服务器指向此目录 |
| `themes/canada/` | **前端模板文件所在目录** |
| `thinkphp/` | ThinkPHP 5 框架核心 |
| `extend/` | 扩展类库 |
| `database/` | 数据库相关文件 |
| `vendor/` | Composer 依赖包 |

## 前端文件

项目的前端页面模板位于：

```
themes/canada/
```

该目录下包含网站的前端页面、样式及脚本相关资源。

## 快速开始

1. 克隆项目

```bash
git clone git@github.com:793437599-rgb/ows.git
cd ows
```

2. 安装依赖

```bash
composer install
```

3. 复制环境配置文件

```bash
cp .env.example .env
```

4. 根据实际情况修改 `.env` 中的数据库等配置

5. 配置 Web 服务器根目录指向 `public/`

6. 访问站点

## 技术栈

- 后端框架：ThinkPHP 5
- 前端模板：`themes/canada/`
- 数据库：MySQL（推荐）

## 注意事项

- 生产环境请将 Web 根目录设置为 `public/`，不要直接暴露项目根目录。
- 部署前请修改默认的数据库账号密码及相关敏感配置。
