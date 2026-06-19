# Flarum AI Audit

一个用于 Flarum 的 AI 内容审核扩展，支持 OpenAI 兼容的 Chat Completions API（`/v1/chat/completions`）。

## 功能

- 自动采集内容快照：新回复、新讨论、用户资料变更
- 双阶段策略：本地信号评分 + 可选 LLM 判定（JSON 输出）
- 自动动作：进入人工复核、隐藏（基于 `is_approved`）、可选封禁
- 审核日志：后台列表/详情页 + 支持重试

## 环境要求

- Flarum：`^1.8` 或 `^2.0`
- 可选依赖：`flarum/flags`（启用“加入待审核队列”的 Flag 功能）

## 安装

在 Flarum 根目录执行：

```bash
composer require zephyrisle/flarum-ai-audit
php flarum migrate
php flarum cache:clear
```

如果你修改了扩展前端源码，请重新构建并清理缓存：

```bash
cd js
npm install
npm run build
cd ..
php flarum cache:clear
```

## 配置项

后台 → 扩展 → AI Audit：

- `zephyrisle.ai-audit.api_endpoint`：API Endpoint（默认 `https://api.openai.com/v1`）
- `zephyrisle.ai-audit.api_key`：API Key
- `zephyrisle.ai-audit.model`：模型名（默认 `gpt-4o-mini`）
- `zephyrisle.ai-audit.temperature`：温度（默认 `0.2`）
- `zephyrisle.ai-audit.max_tokens`：最大输出 token（默认 `800`）
- `zephyrisle.ai-audit.timeout`：请求超时秒数（默认 `30`）
- `zephyrisle.ai-audit.system_prompt`：系统提示词（留空使用内置默认提示）
- `zephyrisle.ai-audit.pre_approve_enabled`：开启“预审核”（新内容先标记为未审核）
- `zephyrisle.ai-audit.download_images`：是否下载图片并写入快照（默认 `true`）
- `zephyrisle.ai-audit.review_threshold`：进入人工复核阈值（默认 `0.55`）
- `zephyrisle.ai-audit.action_threshold`：自动动作阈值（默认 `0.75`）
- `zephyrisle.ai-audit.suspend_days`：封禁天数（默认 `7`）

说明：
- `is_approved` 字段并非所有安装都存在；扩展会在运行时检测字段是否存在，避免数据库报错。
- 未配置 `api_key` 时不会调用 LLM，仅使用本地信号评分。

## 运行行为

- 审核日志列表、详情、重试接口均要求已登录且具备对应后台权限。
- 列表查询参数会自动收敛：`page.limit` 最大 `100`，`page.offset` 最小 `0`。
- `pending` 或 `retrying` 状态的日志不可重复重试，避免重复入队。
- 图片快照下载仅允许外部 `http/https` 地址，默认阻止 `localhost`、内网 IP、保留地址等高风险目标。

## 权限

后台权限页可配置：
- `zephyrisle-ai-audit.viewAuditLogs`：查看审核日志
- `zephyrisle-ai-audit.viewFullAuditLogs`：查看完整日志（含快照/分析/错误）
- `zephyrisle-ai-audit.retryAudit`：重试审核
- `zephyrisle-ai-audit.bypassAudit`：跳过 AI 审核
- `zephyrisle-ai-audit.bypassPreApprove`：跳过预审核

## API

- `GET /api/ai-audit/logs`
- `GET /api/ai-audit/logs/{id}`
- `POST /api/ai-audit/logs/{id}/retry`

## 测试

```bash
composer install
composer test
```
