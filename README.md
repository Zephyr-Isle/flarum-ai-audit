# Flarum AI Audit

AI 内容审核扩展，支持 OpenAI 兼容 API，覆盖帖子、讨论、私信、用户资料等全场景。

## 功能

- **全类型审核**：帖子内容、讨论标题、私信消息、用户名/头像/昵称/签名/封面图、上传文件、帖子图片
- **双阶段策略**：本地信号评分（敏感词/联系方式/垃圾文本等） + 可选 LLM 判定（JSON Schema 输出）
- **自杀倾向检测**：中英文关键词匹配 → 触发暖心弹窗 + 管理员通知（不处罚用户）
- **自动动作**：隐藏/删除/封禁/重命名/重置资料等，支持阈值控制
- **预审核模式**：新内容默认标记为未审核，通过后再发布
- **审核日志**：后台列表/详情页，支持重试

## 环境要求

- Flarum `^2.0`
- PHP `^8.0`
- 可选依赖：
  - `flarum/flags` — 启用人工复核标记
  - `flarum/messages` — 私信消息审核
  - `flarum/nicknames` — 昵称审核
  - `fof/user-bio` — 签名审核
  - `fof/upload` — 上传文件审核
  - `forumaker/profile-cover` — 封面图审核

## 安装

```bash
composer require zephyrisle/flarum-ai-audit
php flarum migrate
php flarum cache:clear
```

构建前端（修改源码后需要）：

```bash
cd js
npm install && npm run build
cd ..
php flarum cache:clear
```

## 后台配置

扩展设置页：

| 设置 | 说明 | 默认值 |
|------|------|--------|
| `api_endpoint` | API Endpoint | `https://api.openai.com/v1` |
| `api_key` | API Key | — |
| `model` | 模型名 | `gpt-4o-mini` |
| `temperature` | 温度 | `0.2` |
| `max_tokens` | 最大输出 token | `800` |
| `timeout` | 请求超时（秒） | `30` |
| `system_prompt` | 系统提示词（留空使用内置默认） | — |
| `use_json_schema` | JSON Schema 输出（非 OpenAI API 需关闭） | `true` |

### 内容类型开关

独立开关控制每种内容的审核：

- `enable_post_content_audit` — 帖子/回复内容
- `enable_post_image_audit` — 帖子中的外部图片
- `enable_discussion_title_audit` — 讨论标题
- `enable_message_audit` — 私信消息
- `enable_username_audit` — 用户名变更
- `enable_avatar_audit` — 头像变更
- `enable_nickname_audit` — 昵称变更
- `enable_bio_audit` — 签名变更
- `enable_cover_audit` — 封面图变更
- `enable_upload_audit` — 上传文件

### 行为设置

| 设置 | 说明 | 默认值 |
|------|------|--------|
| `pre_approve_enabled` | 预审核模式 | `false` |
| `review_threshold` | 人工复核阈值 | `0.55` |
| `action_threshold` | 自动处理阈值 | `0.75` |
| `suspend_days` | 封禁天数 | `7` |
| `download_images` | 下载图片用于 AI 识别 | `true` |
| `image_download_timeout` | 图片下载超时（秒） | `8` |
| `enable_notifications` | 违规时向用户发送通知 | `true` |
| `enable_context` | 分析时包含上下文 | `true` |

> 未配置 `api_key` 时不会调用 LLM，仅使用本地信号评分。

## 审核动作

| 动作 | 说明 |
|------|------|
| `none` | 无违规，内容正常 |
| `review` | 加入人工复核队列 |
| `hide` | 隐藏内容（`is_approved = false`） |
| `delete` | 删除私信消息 |
| `suspend` | 封禁用户 |
| `rename` | 重命名用户名 |
| `delete_avatar` | 删除头像 |
| `reset_nickname` | 重置昵称 |
| `reset_bio` | 重置签名 |
| `delete_cover` | 删除封面图 |
| `flag` | 标记供管理员审查 |
| `suicide_alert` | 自杀/自残预警（不处罚，仅通知） |

## 自杀预警流程

检测到自杀倾向信号后：

1. **暖心弹窗** — 用户收到通知，下次访问论坛时弹出全屏遮罩，显示关怀信息与心理援助热线
2. **管理员通知** — 所有管理员及有 `viewAuditLogs` 权限的用户收到站内通知
3. **审核标记** — 在 flags 中创建 `suicide_alert` 类型标记，供管理员及时介入

> 自杀预警不会隐藏、删除或处罚用户的任何内容。

## 权限

| 权限 | 说明 |
|------|------|
| `viewAuditLogs` | 查看审核日志 |
| `viewFullAuditLogs` | 查看完整日志（含快照/分析） |
| `retryAudit` | 重试审核 |
| `bypassAudit` | 跳过 AI 审核 |
| `bypassPreApprove` | 跳过预审核（有权限的用户发帖直接发布） |

## API

| 方法 | 路径 | 说明 |
|------|------|------|
| `GET` | `/api/ai-audit/logs` | 审核日志列表（分页） |
| `GET` | `/api/ai-audit/logs/{id}` | 审核日志详情 |
| `POST` | `/api/ai-audit/logs/{id}/retry` | 重试指定审核 |

## 测试

```bash
composer install
composer test
```

## 安全说明

- 图片快照下载仅允许外部 `http/https` 地址，默认阻止 `localhost`、内网 IP、保留地址
- `pending` / `retrying` 状态的日志不可重复重试
- 所有审核 API 要求登录且具备对应权限
